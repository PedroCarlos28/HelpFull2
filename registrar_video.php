<?php
require_once 'conexao.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    exit;
}

$id = $_SESSION['usuario_id'];
$hoje = date('Y-m-d');

try {
    // Verifica se já assistiu hoje antes de atualizar
    $stmtCheck = $pdo->prepare("SELECT ultimo_video_data FROM usuarios WHERE id = ?");
    $stmtCheck->execute([$id]);
    $ultimo = $stmtCheck->fetchColumn();
    $jaConcluiu = ($ultimo === $hoje);

    // Incrementa o contador e atualiza a data
    $stmt = $pdo->prepare("UPDATE usuarios SET videos_assistidos = COALESCE(videos_assistidos, 0) + 1, ultimo_video_data = ? WHERE id = ?");
    $stmt->execute([$hoje, $id]);
    
    echo json_encode([
        'success' => true, 
        'meta_concluida' => !$jaConcluiu
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
