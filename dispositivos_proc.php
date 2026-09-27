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
$tokenAtual = $_SESSION['token_sessao'] ?? null;

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!$input) {
    $input = $_POST;
}

$acao = $input['acao'] ?? $_GET['acao'] ?? 'listar';

try {
    switch ($acao) {
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

            $quantidade = desconectarOutrasSessoes($pdo, $usuarioId, $tokenAtual);

            // Notificação de segurança interna
            if (file_exists(__DIR__ . '/notificacao_helper.php')) {
                require_once __DIR__ . '/notificacao_helper.php';
                if (function_exists('registrarNotificacaoSistema')) {
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
