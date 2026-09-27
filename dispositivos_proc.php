<?php
// =======================================================
// HELPFULL - PROCESSAMENTO DE DISPOSITIVOS E SESSÕES
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

/**
 * Valida a autorização de segurança para desconectar dispositivos:
 * - Se 2FA ativo: exige código de 6 dígitos enviado ao e-mail.
 * - Se 2FA inativo: exige confirmação da senha do usuário.
 */
function validarSegurancaDesconexao($pdo, $usuarioId, $input) {
    $stmtUser = $pdo->prepare("SELECT id, nome, email, senha, dois_fatores_ativo, codigo_2fa, codigo_2fa_expira FROM usuarios WHERE id = :id");
    $stmtUser->execute([':id' => $usuarioId]);
    $usuario = $stmtUser->fetch();

    if (!$usuario) {
        return ['ok' => false, 'msg' => 'Usuário não encontrado.'];
    }

    $doisFatoresAtivo = !empty($usuario['dois_fatores_ativo']);

    if ($doisFatoresAtivo) {
        $codigoDigitado = preg_replace('/\D/', '', (string)($input['codigo_2fa'] ?? ''));

        if (empty($codigoDigitado) || strlen($codigoDigitado) !== 6) {
            return ['ok' => false, 'msg' => 'Por favor, digite o código de verificação de 6 dígitos enviado ao seu e-mail.'];
        }

        if (empty($usuario['codigo_2fa'])) {
            return ['ok' => false, 'msg' => 'Nenhum código pendente. Clique em "Reenviar código".'];
        }

        $expiraEm = strtotime($usuario['codigo_2fa_expira']);
        if ($expiraEm && $expiraEm < time()) {
            return ['ok' => false, 'msg' => 'O código de verificação expirou. Solicite um novo código.'];
        }

        if ($usuario['codigo_2fa'] !== $codigoDigitado) {
            return ['ok' => false, 'msg' => 'Código de verificação incorreto. Verifique sua caixa de entrada.'];
        }

        // Limpa o código utilizado após validação com sucesso
        $pdo->prepare("UPDATE usuarios SET codigo_2fa = NULL, codigo_2fa_expira = NULL WHERE id = :id")->execute([':id' => $usuarioId]);
        unset($_SESSION['disp_2fa_ultimo_envio']);
        return ['ok' => true];
    } else {
        $senha = (string)($input['senha'] ?? '');

        if (empty($senha)) {
            return ['ok' => false, 'msg' => 'Por favor, digite sua senha para confirmar a desconexão.'];
        }

        if (!empty($usuario['senha']) && !password_verify($senha, $usuario['senha'])) {
            return ['ok' => false, 'msg' => 'A senha informada está incorreta.'];
        }

        return ['ok' => true];
    }
}

if (!isset($_SESSION['usuario_id'])) {
    responderJson([
        'sucesso' => false,
        'mensagem' => 'Sessão expirada. Faça login novamente.',
        'redirecionar' => 'Comeco.php'
    ], 401);
}

$usuarioId = $_SESSION['usuario_id'];
$tokenAtual = $_SESSION['token_sessao'] ?? null;

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!$input) {
    $input = $_POST;
}

$acao = $input['acao'] ?? $_GET['acao'] ?? 'listar';

try {
    switch ($acao) {
        case 'solicitar_codigo':
            $stmtUser = $pdo->prepare("SELECT id, nome, email, dois_fatores_ativo FROM usuarios WHERE id = :id");
            $stmtUser->execute([':id' => $usuarioId]);
            $usuario = $stmtUser->fetch();

            if (!$usuario || empty($usuario['dois_fatores_ativo'])) {
                responderJson(['sucesso' => false, 'mensagem' => 'A verificação em duas etapas não está ativa nesta conta.']);
            }

            $ultimoEnvio = $_SESSION['disp_2fa_ultimo_envio'] ?? 0;
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

            $_SESSION['disp_2fa_ultimo_envio'] = $agora;

            $envio = enviarEmail2FA($usuario['email'], $usuario['nome'], $codigo);

            responderJson([
                'sucesso' => true,
                'mensagem' => 'Código de segurança enviado para ' . mascararEmail($usuario['email']),
                'email_mascarado' => mascararEmail($usuario['email']),
                'debug_codigo' => $envio['debug_codigo'] ?? null
            ]);
            break;

        case 'listar':
            $sessoes = listarSessoesUsuario($pdo, $usuarioId, $tokenAtual);
            
            $totalOutros = 0;
            foreach ($sessoes as $s) {
                if (!$s['is_atual']) {
                    $totalOutros++;
                }
            }

            responderJson([
                'sucesso' => true,
                'total' => count($sessoes),
                'total_outros' => $totalOutros,
                'sessoes' => $sessoes
            ]);
            break;

        case 'desconectar':
            $sessaoId = $input['id'] ?? null;
            if (!$sessaoId) {
                responderJson([
                    'sucesso' => false,
                    'mensagem' => 'Identificador de dispositivo não informado.'
                ], 400);
            }

            // Validação de segurança (código de 2FA ou senha da conta)
            $checkSeg = validarSegurancaDesconexao($pdo, $usuarioId, $input);
            if (!$checkSeg['ok']) {
                responderJson([
                    'sucesso' => false,
                    'mensagem' => $checkSeg['msg']
                ], 400);
            }

            // Busca detalhes da sessão antes de desconectar para verificar se é a atual
            $stmtCheck = $pdo->prepare("
                SELECT id, token_sessao, dispositivo, tipo_dispositivo 
                FROM sessoes_usuario 
                WHERE id = :id AND usuario_id = :usuario_id
            ");
            $stmtCheck->execute([
                ':id' => (int)$sessaoId,
                ':usuario_id' => $usuarioId
            ]);
            $sessaoAlvo = $stmtCheck->fetch();

            if (!$sessaoAlvo) {
                responderJson([
                    'sucesso' => false,
                    'mensagem' => 'Dispositivo não encontrado ou já desconectado.'
                ], 404);
            }

            $isAtual = ($tokenAtual !== null && $sessaoAlvo['token_sessao'] === $tokenAtual);

            $ok = desconectarSessao($pdo, $usuarioId, (int)$sessaoId);
            if ($ok) {
                if ($isAtual) {
                    limparSessaoUsuario();
                    responderJson([
                        'sucesso' => true,
                        'logout_atual' => true,
                        'mensagem' => 'Este dispositivo foi desconectado.',
                        'redirecionar' => 'Comeco.php'
                    ]);
                } else {
                    // Notificação de segurança interna
                    if (file_exists(__DIR__ . '/notificacao_helper.php')) {
                        require_once __DIR__ . '/notificacao_helper.php';
                        if (function_exists('registrarNotificacaoSistema')) {
                            try {
                                registrarNotificacaoSistema(
                                    $pdo,
                                    $usuarioId,
                                    'disp_desc_' . time() . '_' . mt_rand(100, 999),
                                    'seguranca_dispositivo',
                                    'Dispositivo desconectado',
                                    'Uma sessão (' . htmlspecialchars($sessaoAlvo['dispositivo']) . ') foi desconectada da sua conta.',
                                    'baixa',
                                    'Perfil.php',
                                    'Segurança'
                                );
                            } catch (Throwable $tNotif) {
                                error_log("Erro ao registrar notificação: " . $tNotif->getMessage());
                            }
                        }
                    }

                    responderJson([
                        'sucesso' => true,
                        'logout_atual' => false,
                        'mensagem' => 'O dispositivo ' . $sessaoAlvo['dispositivo'] . ' foi desconectado com sucesso.'
                    ]);
                }
            } else {
                responderJson([
                    'sucesso' => false,
                    'mensagem' => 'Não foi possível desconectar o dispositivo. Tente novamente.'
                ], 500);
            }
            break;

        case 'desconectar_outros':
            if (empty($tokenAtual)) {
                responderJson([
                    'sucesso' => false,
                    'mensagem' => 'Não foi possível identificar a sessão atual.'
                ], 400);
            }

            // Validação de segurança (código de 2FA ou senha da conta)
            $checkSeg = validarSegurancaDesconexao($pdo, $usuarioId, $input);
            if (!$checkSeg['ok']) {
                responderJson([
                    'sucesso' => false,
                    'mensagem' => $checkSeg['msg']
                ], 400);
            }

            $quantidade = desconectarOutrasSessoes($pdo, $usuarioId, $tokenAtual);

            // Notificação de segurança interna
            if (file_exists(__DIR__ . '/notificacao_helper.php')) {
                require_once __DIR__ . '/notificacao_helper.php';
                if (function_exists('registrarNotificacaoSistema')) {
                    try {
                        registrarNotificacaoSistema(
                            $pdo,
                            $usuarioId,
                            'disp_desc_outros_' . time(),
                            'seguranca_dispositivo',
                            'Dispositivos desconectados',
                            'Todas as outras sessões ativas foram desconectadas da sua conta.',
                            'baixa',
                            'Perfil.php',
                            'Segurança'
                        );
                    } catch (Throwable $tNotif) {
                        error_log("Erro ao registrar notificação: " . $tNotif->getMessage());
                    }
                }
            }

            responderJson([
                'sucesso' => true,
                'desconectados' => $quantidade,
                'mensagem' => $quantidade === 1 
                    ? '1 outro dispositivo foi desconectado com sucesso.'
                    : "{$quantidade} outros dispositivos foram desconectados com sucesso."
            ]);
            break;

        case 'desconectar_tudo':
            // Validação de segurança (código de 2FA ou senha da conta)
            $checkSeg = validarSegurancaDesconexao($pdo, $usuarioId, $input);
            if (!$checkSeg['ok']) {
                responderJson([
                    'sucesso' => false,
                    'mensagem' => $checkSeg['msg']
                ], 400);
            }

            // Desconecta todas as sessões do usuário no banco
            $stmtAll = $pdo->prepare("UPDATE sessoes_usuario SET ativo = FALSE WHERE usuario_id = :usuario_id");
            $stmtAll->execute([':usuario_id' => $usuarioId]);

            limparSessaoUsuario();

            responderJson([
                'sucesso' => true,
                'logout_atual' => true,
                'mensagem' => 'Todas as sessões foram encerradas.',
                'redirecionar' => 'Comeco.php'
            ]);
            break;

        default:
            responderJson([
                'sucesso' => false,
                'mensagem' => 'Ação não reconhecida.'
            ], 400);
    }
} catch (Exception $e) {
    responderJson([
        'sucesso' => false,
        'mensagem' => 'Erro interno ao processar requisição: ' . $e->getMessage()
    ], 500);
}
