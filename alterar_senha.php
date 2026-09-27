<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '0');
ob_start();

require_once 'conexao.php';
require_once __DIR__ . '/email_helper.php';

header('Content-Type: application/json; charset=utf-8');

function responderJson($dados) {
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode($dados);
    exit();
}

if (!isset($_SESSION['usuario_id'])) {
    responderJson(['sucesso' => false, 'mensagem' => 'Sessão expirada. Faça login novamente.']);
}

$usuarioId = $_SESSION['usuario_id'];

try {
    $stmt = $pdo->prepare("SELECT id, nome, email, senha, dois_fatores_ativo, codigo_2fa, codigo_2fa_expira FROM usuarios WHERE id = :id");
    $stmt->execute(['id' => $usuarioId]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        responderJson(['sucesso' => false, 'mensagem' => 'Usuário não encontrado.']);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $acao = $input['acao'] ?? 'salvar_senha';
    $doisFatoresAtivo = !empty($usuario['dois_fatores_ativo']);

    // AÇÃO: SOLICITAR CÓDIGO POR E-MAIL (quando 2FA está ativo)
    if ($acao === 'solicitar_codigo') {
        if (!$doisFatoresAtivo) {
            responderJson(['sucesso' => false, 'mensagem' => 'A verificação em duas etapas não está ativa nesta conta.']);
        }

        $ultimoEnvio = $_SESSION['alt_senha_ultimo_envio'] ?? 0;
        $agora = time();
        $cooldown = 20; // 20 segundos de espera entre envios

        if (($agora - $ultimoEnvio) < $cooldown) {
            $espera = $cooldown - ($agora - $ultimoEnvio);
            responderJson(['sucesso' => false, 'mensagem' => "Aguarde {$espera}s antes de solicitar um novo código."]);
        }

        $codigo = sprintf("%06d", mt_rand(100000, 999999));
        $stmtUp = $pdo->prepare("UPDATE usuarios SET codigo_2fa = :codigo, codigo_2fa_expira = (NOW() + INTERVAL '10 minutes') WHERE id = :id");
        $stmtUp->execute([
            'codigo' => $codigo,
            'id' => $usuarioId
        ]);

        $_SESSION['alt_senha_ultimo_envio'] = $agora;

        $envio = enviarEmailCodigoAlterarSenha($usuario['email'], $usuario['nome'], $codigo);

        responderJson([
            'sucesso' => true,
            'mensagem' => 'Código de segurança enviado para ' . mascararEmail($usuario['email']),
            'email_mascarado' => mascararEmail($usuario['email']),
            'debug_codigo' => $envio['debug_codigo'] ?? null
        ]);
    }

    // AÇÃO: SALVAR NOVA SENHA
    if ($acao === 'salvar_senha') {
        $novaSenha = (string)($input['nova_senha'] ?? '');
        $confirmarSenha = (string)($input['confirmar_senha'] ?? '');

        if (strlen($novaSenha) < 6) {
            responderJson(['sucesso' => false, 'mensagem' => 'A nova senha deve ter no mínimo 6 caracteres.']);
        }

        if ($novaSenha !== $confirmarSenha) {
            responderJson(['sucesso' => false, 'mensagem' => 'A confirmação de senha não confere com a nova senha.']);
        }

        if ($doisFatoresAtivo) {
            // Com 2FA ativo: exige apenas o código enviado no e-mail
            $codigoDigitado = preg_replace('/\D/', '', (string)($input['codigo_2fa'] ?? ''));

            if (empty($codigoDigitado) || strlen($codigoDigitado) !== 6) {
                responderJson(['sucesso' => false, 'mensagem' => 'Digite o código de verificação de 6 dígitos enviado ao seu e-mail.']);
            }

            if (empty($usuario['codigo_2fa'])) {
                responderJson(['sucesso' => false, 'mensagem' => 'Nenhum código pendente. Clique em "Reenviar Código".']);
            }

            $expiraEm = strtotime($usuario['codigo_2fa_expira']);
            if ($expiraEm && $expiraEm < time()) {
                responderJson(['sucesso' => false, 'mensagem' => 'O código de verificação expirou. Solicite um novo código.']);
            }

            if ($usuario['codigo_2fa'] !== $codigoDigitado) {
                responderJson(['sucesso' => false, 'mensagem' => 'Código de verificação incorreto. Verifique seu e-mail.']);
            }
        } else {
            // Sem 2FA: exige obrigatoriamente a senha antiga
            $senhaAntiga = (string)($input['senha_antiga'] ?? '');

            if (empty($senhaAntiga)) {
                responderJson(['sucesso' => false, 'mensagem' => 'Por favor, informe sua senha atual para confirmação.']);
            }

            if (!empty($usuario['senha']) && !password_verify($senhaAntiga, $usuario['senha'])) {
                responderJson(['sucesso' => false, 'mensagem' => 'A senha atual informada está incorreta.']);
            }
        }

        // Tudo validado: atualiza a senha no banco
        $novoHash = password_hash($novaSenha, PASSWORD_DEFAULT);
        $stmtUpdate = $pdo->prepare("UPDATE usuarios SET senha = :senha, codigo_2fa = NULL, codigo_2fa_expira = NULL WHERE id = :id");
        $stmtUpdate->execute([
            'senha' => $novoHash,
            'id'    => $usuarioId
        ]);

        unset($_SESSION['alt_senha_ultimo_envio']);

        // Envia e-mail de notificação de segurança informando que a senha foi alterada
        enviarEmailSenhaAlterada($usuario['email'], $usuario['nome']);

        responderJson([
            'sucesso' => true,
            'mensagem' => 'Senha alterada com sucesso!'
        ]);
    }

    responderJson(['sucesso' => false, 'mensagem' => 'Ação não reconhecida.']);

} catch (PDOException $e) {
    responderJson(['sucesso' => false, 'mensagem' => 'Erro no banco de dados: ' . $e->getMessage()]);
} catch (Exception $e) {
    responderJson(['sucesso' => false, 'mensagem' => 'Erro ao processar: ' . $e->getMessage()]);
}
