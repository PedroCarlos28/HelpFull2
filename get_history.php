<?php
require_once 'conexao.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([]);
    exit;
}

$sessao = $_GET['sessao'] ?? '';

if (empty($sessao)) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT mensagem, resposta FROM historico_chat WHERE usuario_id = ? AND sessao_token = ? ORDER BY criado_em ASC");
$stmt->execute([$_SESSION['usuario_id'], $sessao]);
$mensagens = $stmt->fetchAll();

echo json_encode($mensagens);
