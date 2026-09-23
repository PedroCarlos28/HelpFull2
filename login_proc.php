<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '0');
ob_start();

require_once 'conexao.php';

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
        salvarSessaoUsuario($usuario['id'], $usuario['nome']);
        responderJson(['sucesso' => true]);
    } else {
        responderJson(['sucesso' => false, 'mensagem' => 'Email ou senha incorretos.']);
    }
} catch (PDOException $e) {
    responderJson(['sucesso' => false, 'mensagem' => 'Erro no servidor: ' . $e->getMessage()]);
}
?>
