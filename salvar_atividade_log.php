<?php
require_once 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['sucesso' => false, 'erro' => 'Não autenticado']);
    exit;
}

$usuarioId = $_SESSION['usuario_id'];
$tipo = trim($_POST['tipo'] ?? '');
$termoOuTitulo = trim($_POST['termo_ou_titulo'] ?? '');
$categoria = trim($_POST['categoria'] ?? '');

if (empty($tipo) || empty($termoOuTitulo)) {
    echo json_encode(['sucesso' => false, 'erro' => 'Dados incompletos']);
    exit;
}

// Limita tamanho
$termoOuTitulo = mb_substr($termoOuTitulo, 0, 255);
$categoria = mb_substr($categoria, 0, 50);

try {
    // Garante que a tabela exista
    $pdo->exec("CREATE TABLE IF NOT EXISTS registro_atividades_usuario (
        id SERIAL PRIMARY KEY,
        usuario_id UUID NOT NULL,
        tipo VARCHAR(50) NOT NULL,
        termo_ou_titulo TEXT NOT NULL,
        categoria VARCHAR(50) DEFAULT '',
        criado_em TIMESTAMP WITH TIME ZONE DEFAULT NOW()
    )");

    $stmt = $pdo->prepare("INSERT INTO registro_atividades_usuario (usuario_id, tipo, termo_ou_titulo, categoria) VALUES (?, ?, ?, ?)");
    $stmt->execute([$usuarioId, $tipo, $termoOuTitulo, $categoria]);

    echo json_encode(['sucesso' => true]);
} catch (PDOException $e) {
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}
