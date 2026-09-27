<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '0');
ob_start();

require_once 'conexao.php';
require_once 'email_helper.php';

header('Content-Type: application/json; charset=utf-8');

function responderJson($dados) {
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode($dados);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['email']) || !isset($input['senha'])) {
    responderJson(['sucesso' => false, 'mensagem' => 'Dados inválidos.']);
}

$email = trim($input['email']);
$senha = $input['senha'];

try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        // Se a verificação em duas etapas estiver ativada
        if (!empty($usuario['dois_fatores_ativo'])) {
            $codigo = sprintf("%06d", mt_rand(100000, 999999));
            $stmtUp = $pdo->prepare("UPDATE usuarios SET codigo_2fa = :codigo, codigo_2fa_expira = (NOW() + INTERVAL '10 minutes') WHERE id = :id");
            $stmtUp->execute([
                'codigo' => $codigo,
                'id' => $usuario['id']
            ]);

            $_SESSION['temp_2fa_user_id'] = $usuario['id'];
            $_SESSION['temp_2fa_email'] = $usuario['email'];
            $_SESSION['temp_2fa_ultimo_envio'] = time();

            $envio = enviarEmail2FA($usuario['email'], $usuario['nome'], $codigo);

            responderJson([
                'sucesso' => true,
                'requer_2fa' => true,
                'email_mascarado' => mascararEmail($usuario['email']),
                'debug_codigo' => $envio['debug_codigo'] ?? null
            ]);
        }

        salvarSessaoUsuario($usuario['id'], $usuario['nome']);

        // Notifica o usuário por e-mail sobre o novo login
        enviarEmailNovoLogin($usuario['email'], $usuario['nome'], [
            'metodo' => 'Senha e E-mail'
        ]);

        responderJson(['sucesso' => true]);
    } else {
        responderJson(['sucesso' => false, 'mensagem' => 'Email ou senha incorretos.']);
    }
} catch (PDOException $e) {
    responderJson(['sucesso' => false, 'mensagem' => 'Erro no servidor: ' . $e->getMessage()]);
}
?>
