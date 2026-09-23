<?php
require_once 'conexao.php';
session_start();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['email']) || !isset($input['senha'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos.']);
    exit();
}

$email = trim($input['email']);
$senha = $input['senha'];

try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        salvarSessaoUsuario($usuario['id'], $usuario['nome']);
        echo json_encode(['sucesso' => true]);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Email ou senha incorretos.']);
    }
} catch (PDOException $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro no servidor: ' . $e->getMessage()]);
}
?>
