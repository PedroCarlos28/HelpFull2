<?php
require_once 'conexao.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([]);
    exit;
}

$id = $_SESSION['usuario_id'];

$acao = $_GET['acao'] ?? '';

if ($acao === 'limpar') {
    $stmt = $pdo->prepare("UPDATE notificacoes_sociais SET lida = 1 WHERE usuario_destino_id = ? AND (lida = 0 OR lida IS NULL)");
    $stmt->execute([$id]);
    echo json_encode(['sucesso' => true]);
    exit;
}

if ($acao === 'marcar_lidas') {
    $stmt = $pdo->prepare("UPDATE notificacoes_sociais SET lida = 1 WHERE usuario_destino_id = ? AND (lida = 0 OR lida IS NULL)");
    $stmt->execute([$id]);
    echo json_encode(['sucesso' => true]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT n.*, u.nome as autor_nome, u.foto_perfil as autor_foto 
                           FROM notificacoes_sociais n 
                           JOIN usuarios u ON n.usuario_origem_id = u.id 
                           WHERE n.usuario_destino_id = ? AND (n.lida = 0 OR n.lida IS NULL)
                           ORDER BY n.data_criacao DESC LIMIT 10");
    $stmt->execute([$id]);
    $notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // === TEMPLATES DE NOTIFICAÇÕES SOCIAIS ===
    $TEMPLATES = [
        'curtida'  => '[Nome] curtiu sua publicação.',
        'reacao'   => '[Nome] reagiu \'[Reação]\' à sua publicação.',
    ];

    $resultado = [];
    foreach ($notificacoes as $n) {
        if ($n['tipo'] === 'curtida') {
            $msg = str_replace('[Nome]', $n['autor_nome'], $TEMPLATES['curtida']);
        } else {
            $msg = str_replace(
                ['[Nome]', '[Reação]'],
                [$n['autor_nome'], $n['detalhe']],
                $TEMPLATES['reacao']
            );
        }

        $resultado[] = [
            'id' => $n['id'],
            'titulo' => $n['autor_nome'],
            'mensagem' => $msg,
            'foto' => $n['autor_foto'],
            'intensidade' => 'baixa',
            'link' => 'Comunidade.php',
            'textoBotao' => 'Ver'
        ];
    }

    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    echo json_encode([]);
}
?>
