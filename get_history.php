<?php
require_once 'conexao.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([], JSON_UNESCAPED_UNICODE);
    exit;
}

$sessao = $_GET['sessao'] ?? '';

if (empty($sessao)) {
    echo json_encode([], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $pdo->prepare("SELECT mensagem, resposta FROM historico_chat WHERE usuario_id = ? AND sessao_token = ? ORDER BY criado_em ASC");
$stmt->execute([$_SESSION['usuario_id'], $sessao]);
$mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($mensagens ?: [], JSON_UNESCAPED_UNICODE);

