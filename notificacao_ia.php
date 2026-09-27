<?php
require_once 'conexao.php';
require_once 'notificacao_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['mostrar' => false, 'notificacoes' => []]);
    exit;
}

$id = $_SESSION['usuario_id'];
garantirTabelaNotificacoesSistema($pdo);

$acao = $_GET['acao'] ?? '';

if ($acao === 'limpar') {
    // Marca como limpas (lida = 2) para sumir do painel e não recriar a mesma streak
    $stmt = $pdo->prepare("UPDATE notificacoes_sistema SET lida = 2 WHERE usuario_id = ?");
    $stmt->execute([$id]);
    echo json_encode(['sucesso' => true]);
    exit;
}

if ($acao === 'marcar_lidas' || $acao === 'marcar_toast_visto') {
    $notifId = $_GET['id'] ?? null;
    if (!empty($notifId)) {
        $stmt = $pdo->prepare("UPDATE notificacoes_sistema SET lida = 1 WHERE usuario_id = ? AND (id::text = ? OR chave = ?) AND lida = 0");
        $stmt->execute([$id, (string)$notifId, (string)$notifId]);
    } else {
        $stmt = $pdo->prepare("UPDATE notificacoes_sistema SET lida = 1 WHERE usuario_id = ? AND lida = 0");
        $stmt->execute([$id]);
    }
    echo json_encode(['sucesso' => true]);
    exit;
}

// Obter/verificar notificações ativas do usuário
$ativas = obterNotificacoesAtivasUsuario($pdo, $id);

if (empty($ativas)) {
    echo json_encode(['mostrar' => false, 'notificacoes' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

// Retorna o item mais relevante no topo, incluindo a lista completa para clientes modernos
$recente = $ativas[0];
$recente['notificacoes'] = $ativas;

echo json_encode($recente, JSON_UNESCAPED_UNICODE);
exit;
