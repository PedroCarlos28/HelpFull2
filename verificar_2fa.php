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

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    responderJson(['sucesso' => false, 'mensagem' => 'Dados inválidos.']);
}

$acao = $input['acao'] ?? 'verificar';
$email = trim($input['email'] ?? ($_SESSION['temp_2fa_email'] ?? ''));
$userId = $_SESSION['temp_2fa_user_id'] ?? null;

if (!$email && !$userId) {
    responderJson(['sucesso' => false, 'mensagem' => 'Sessão de verificação expirada. Faça login novamente.']);
}

try {
    if ($userId) {
        $stmt = $pdo->prepare("SELECT id, nome, email, codigo_2fa, codigo_2fa_expira FROM usuarios WHERE id = :id");
        $stmt->execute(['id' => $userId]);
    } else {
        $stmt = $pdo->prepare("SELECT id, nome, email, codigo_2fa, codigo_2fa_expira FROM usuarios WHERE email = :email");
        $stmt->execute(['email' => $email]);
    }
    $usuario = $stmt->fetch();

    if (!$usuario) {
        responderJson(['sucesso' => false, 'mensagem' => 'Usuário não encontrado.']);
    }

    // AÇÃO: REENVIAR CÓDIGO
    if ($acao === 'reenviar') {
        $ultimoEnvio = $_SESSION['temp_2fa_ultimo_envio'] ?? 0;
        $agora = time();
        $cooldown = 25; // 25 segundos entre reenvios

        if (($agora - $ultimoEnvio) < $cooldown) {
            $espera = $cooldown - ($agora - $ultimoEnvio);
            responderJson(['sucesso' => false, 'mensagem' => "Aguarde {$espera}s antes de solicitar um novo código."]);
        }

        $novoCodigo = sprintf("%06d", mt_rand(100000, 999999));
        $stmtUp = $pdo->prepare("UPDATE usuarios SET codigo_2fa = :codigo, codigo_2fa_expira = (NOW() + INTERVAL '10 minutes') WHERE id = :id");
        $stmtUp->execute([
            'codigo' => $novoCodigo,
            'id' => $usuario['id']
        ]);

        $_SESSION['temp_2fa_ultimo_envio'] = $agora;

        $envio = enviarEmail2FA($usuario['email'], $usuario['nome'], $novoCodigo);

        responderJson([
            'sucesso' => true,
            'mensagem' => 'Um novo código foi enviado para ' . mascararEmail($usuario['email']),
            'debug_codigo' => $envio['debug_codigo'] ?? null
        ]);
    }

    // AÇÃO: VERIFICAR CÓDIGO
    $codigoDigitado = preg_replace('/\D/', '', $input['codigo'] ?? '');

    if (empty($codigoDigitado) || strlen($codigoDigitado) !== 6) {
        responderJson(['sucesso' => false, 'mensagem' => 'Por favor, digite o código de 6 dígitos.']);
    }

    if (empty($usuario['codigo_2fa'])) {
        responderJson(['sucesso' => false, 'mensagem' => 'Nenhum código pendente. Faça login novamente.']);
    }

    // Valida expiração
    $expiraEm = strtotime($usuario['codigo_2fa_expira']);
    if ($expiraEm && $expiraEm < time()) {
        responderJson(['sucesso' => false, 'mensagem' => 'O código de verificação expirou. Clique em "Reenviar Código".']);
    }

    // Valida exatidão do código
    if ($usuario['codigo_2fa'] !== $codigoDigitado) {
        responderJson(['sucesso' => false, 'mensagem' => 'Código incorreto. Verifique seu e-mail e tente novamente.']);
    }

    // Código correto! Limpa dados temporários e confirma login
    $stmtClean = $pdo->prepare("UPDATE usuarios SET codigo_2fa = NULL, codigo_2fa_expira = NULL WHERE id = :id");
    $stmtClean->execute(['id' => $usuario['id']]);

    unset($_SESSION['temp_2fa_user_id']);
    unset($_SESSION['temp_2fa_email']);
    unset($_SESSION['temp_2fa_ultimo_envio']);

    salvarSessaoUsuario($usuario['id'], $usuario['nome']);

    // Notifica o usuário por e-mail sobre o novo login após confirmação do 2FA
    enviarEmailNovoLogin($usuario['email'], $usuario['nome'], [
        'metodo' => 'Autenticação em Duas Etapas (2FA)'
    ]);

    // Registra também na central de notificações do site
    require_once __DIR__ . '/notificacao_helper.php';
    registrarNotificacaoNovoLogin($pdo, $usuario['id']);

    responderJson([
        'sucesso' => true,
        'mensagem' => 'Autenticação confirmada com sucesso!'
    ]);

} catch (PDOException $e) {
    responderJson(['sucesso' => false, 'mensagem' => 'Erro ao processar verificação: ' . $e->getMessage()]);
}
?>
