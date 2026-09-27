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
    responderJson(['sucesso' => false, 'mensagem' => 'Sessão expirada. Faça login novamente.']);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['ativo'])) {
    responderJson(['sucesso' => false, 'mensagem' => 'Parâmetro inválido.']);
}

$ativo = (bool)$input['ativo'];
$usuarioId = $_SESSION['usuario_id'];

try {
    $stmt = $pdo->prepare("UPDATE usuarios SET dois_fatores_ativo = :ativo WHERE id = :id");
    $stmt->bindValue(':ativo', $ativo, PDO::PARAM_BOOL);
    $stmt->bindValue(':id', $usuarioId);
    $stmt->execute();

    require_once __DIR__ . '/notificacao_helper.php';
    registrarNotificacao2FA($pdo, $usuarioId, $ativo);

    $msg = $ativo 
        ? 'Verificação em duas etapas ativada com sucesso! Um código será exigido no próximo login.' 
        : 'Verificação em duas etapas desativada.';

    responderJson([
        'sucesso' => true, 
        'ativo' => $ativo,
        'mensagem' => $msg
    ]);
} catch (PDOException $e) {
    responderJson(['sucesso' => false, 'mensagem' => 'Erro ao salvar configuração: ' . $e->getMessage()]);
}
?>
