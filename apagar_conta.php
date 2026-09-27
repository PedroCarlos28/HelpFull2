<?php
// =======================================================
// HELPFULL - PROCESSAMENTO DE EXCLUSÃO DE CONTA
// Design do sistema HelpFull - Sem emojis
// =======================================================

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '0');
ob_start();

require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/sessao_helper.php';
require_once __DIR__ . '/email_helper.php';

header('Content-Type: application/json; charset=utf-8');

function responderJson($dados, $statusHttp = 200) {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($statusHttp);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit();
}

if (!isset($_SESSION['usuario_id'])) {
    responderJson([
        'sucesso' => false,
        'mensagem' => 'Sessão expirada. Faça login novamente.',
        'redirecionar' => 'Comeco.php'
    ], 401);
}

$usuarioId = $_SESSION['usuario_id'];

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!$input) {
    $input = $_POST;
}

$acao = $input['acao'] ?? $_GET['acao'] ?? '';

try {
    switch ($acao) {
        case 'solicitar_codigo':
            $stmtUser = $pdo->prepare("SELECT id, nome, email, dois_fatores_ativo FROM usuarios WHERE id = :id");
            $stmtUser->execute([':id' => $usuarioId]);
            $usuario = $stmtUser->fetch();

            if (!$usuario || empty($usuario['dois_fatores_ativo'])) {
                responderJson(['sucesso' => false, 'mensagem' => 'A verificação em duas etapas não está ativa nesta conta.']);
            }

            $ultimoEnvio = $_SESSION['del_conta_ultimo_envio'] ?? 0;
            $agora = time();
            $cooldown = 20;

            if (($agora - $ultimoEnvio) < $cooldown) {
                $espera = $cooldown - ($agora - $ultimoEnvio);
                responderJson(['sucesso' => false, 'mensagem' => "Aguarde {$espera}s antes de solicitar um novo código."]);
            }

            $codigo = sprintf("%06d", mt_rand(100000, 999999));
            $stmtUp = $pdo->prepare("UPDATE usuarios SET codigo_2fa = :codigo, codigo_2fa_expira = (NOW() + INTERVAL '10 minutes') WHERE id = :id");
            $stmtUp->execute([
                ':codigo' => $codigo,
                ':id'     => $usuarioId
            ]);

            $_SESSION['del_conta_ultimo_envio'] = $agora;

            $envio = enviarEmailCodigoApagarConta($usuario['email'], $usuario['nome'], $codigo);

            responderJson([
                'sucesso' => true,
                'mensagem' => 'Código de segurança enviado para ' . mascararEmail($usuario['email']),
                'email_mascarado' => mascararEmail($usuario['email']),
                'debug_codigo' => $envio['debug_codigo'] ?? null
            ]);
            break;

        case 'confirmar_apagar':
            $stmtUser = $pdo->prepare("SELECT id, nome, email, senha, dois_fatores_ativo, codigo_2fa, codigo_2fa_expira FROM usuarios WHERE id = :id");
            $stmtUser->execute([':id' => $usuarioId]);
            $usuario = $stmtUser->fetch();

            if (!$usuario) {
                responderJson(['sucesso' => false, 'mensagem' => 'Usuário não encontrado.']);
            }

            $doisFatoresAtivo = !empty($usuario['dois_fatores_ativo']);

            if ($doisFatoresAtivo) {
                $codigoDigitado = preg_replace('/\D/', '', (string)($input['codigo_2fa'] ?? ''));

                if (empty($codigoDigitado) || strlen($codigoDigitado) !== 6) {
                    responderJson(['sucesso' => false, 'mensagem' => 'Por favor, digite o código de verificação de 6 dígitos enviado ao seu e-mail.']);
                }

                if (empty($usuario['codigo_2fa'])) {
                    responderJson(['sucesso' => false, 'mensagem' => 'Nenhum código pendente. Clique em "Reenviar código".']);
                }

                $expiraEm = strtotime($usuario['codigo_2fa_expira']);
                if ($expiraEm && $expiraEm < time()) {
                    responderJson(['sucesso' => false, 'mensagem' => 'O código de verificação expirou. Solicite um novo código.']);
                }

                if ($usuario['codigo_2fa'] !== $codigoDigitado) {
                    responderJson(['sucesso' => false, 'mensagem' => 'Código de verificação incorreto. Verifique sua caixa de entrada.']);
                }
            } else {
                $senha = (string)($input['senha'] ?? '');

                if (empty($senha)) {
                    responderJson(['sucesso' => false, 'mensagem' => 'Por favor, digite sua senha para confirmar a exclusão da conta.']);
                }

                if (!empty($usuario['senha']) && !password_verify($senha, $usuario['senha'])) {
                    responderJson(['sucesso' => false, 'mensagem' => 'A senha informada está incorreta.']);
                }
            }

            // Exclusão definitiva em transação segura
            $pdo->beginTransaction();
            try {
                // Remove dependências conhecidas caso não haja ON DELETE CASCADE ativo
                try {
                    $pdo->prepare("DELETE FROM sessoes_usuario WHERE usuario_id = ?")->execute([$usuarioId]);
                } catch (Exception $e) {}

                try {
                    $pdo->prepare("DELETE FROM diario WHERE usuario_id = ?")->execute([$usuarioId]);
                } catch (Exception $e) {}

                try {
                    $pdo->prepare("DELETE FROM historico_chat WHERE usuario_id = ?")->execute([$usuarioId]);
                } catch (Exception $e) {}

                try {
                    $pdo->prepare("DELETE FROM registro_atividades_usuario WHERE usuario_id = ?")->execute([$usuarioId]);
                } catch (Exception $e) {}

                try {
                    $pdo->prepare("DELETE FROM notificacoes_sistema WHERE usuario_id = ?")->execute([$usuarioId]);
                } catch (Exception $e) {}

                $stmtDel = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmtDel->execute([$usuarioId]);

                $pdo->commit();
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                responderJson(['sucesso' => false, 'mensagem' => 'Erro ao excluir a conta: ' . $e->getMessage()]);
            }

            // Encerra sessão do usuário
            if (function_exists('encerrarSessaoAtual')) {
                encerrarSessaoAtual($pdo);
            }

            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();

            responderJson([
                'sucesso' => true,
                'mensagem' => 'Conta excluída com sucesso.',
                'redirecionar' => 'Comeco.php'
            ]);
            break;

        default:
            responderJson(['sucesso' => false, 'mensagem' => 'Ação inválida.']);
            break;
    }
} catch (Exception $e) {
    responderJson(['sucesso' => false, 'mensagem' => 'Erro interno no servidor: ' . $e->getMessage()], 500);
}
