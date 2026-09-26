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

if (!isset($_SESSION['usuario_id'])) {
    responderJson(['sucesso' => false, 'mensagem' => 'Usuário não autenticado.']);
}

$usuarioId = $_SESSION['usuario_id'];

try {
    $stmt = $pdo->prepare("UPDATE usuarios SET onboarding_concluido = true WHERE id = ?");
    $stmt->execute([$usuarioId]);
    responderJson(['sucesso' => true]);
} catch (Exception $e) {
    responderJson(['sucesso' => false, 'mensagem' => 'Erro ao salvar: ' . $e->getMessage()]);
}
