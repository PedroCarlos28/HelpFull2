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
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit();
}

$acao = $_GET['acao'] ?? '';

// 1. Obter preferências
if ($acao === 'obter' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $usuario = obterUsuarioLogado($pdo);
    if (!$usuario) {
        responderJson([
            'sucesso' => true,
            'logado' => false,
            'preferencias' => [
                'cor_tema' => 'padrao',
                'idioma' => 'pt-BR',
                'tema_modo' => 'claro',
                'escuro' => false,
                'contraste' => false,
                'textoGrande' => false,
                'sublinhar' => false,
                'semAnimacao' => false,
                'libras' => false
            ]
        ]);
    }

    $prefs = $usuario['preferencias'] ?? [];
    if (is_string($prefs)) {
        $prefs = json_decode($prefs, true) ?: [];
    }
    responderJson([
        'sucesso' => true,
        'logado' => true,
        'preferencias' => $prefs
    ]);
}

// 2. Salvar ou Resetar preferências (requer login)
if (empty($_SESSION['usuario_id'])) {
    responderJson(['sucesso' => false, 'mensagem' => 'Usuário não autenticado.']);
}

$usuarioId = $_SESSION['usuario_id'];

// Carrega preferências atuais
$usuario = obterUsuarioLogado($pdo, true);
$prefsAtuais = $usuario['preferencias'] ?? [];
if (is_string($prefsAtuais)) {
    $prefsAtuais = json_decode($prefsAtuais, true) ?: [];
}
if (!is_array($prefsAtuais)) {
    $prefsAtuais = [];
}

if ($acao === 'resetar') {
    $prefsPadrao = [
        'cor_tema' => 'padrao',
        'idioma' => 'pt-BR',
        'tema_modo' => 'claro',
        'escuro' => false,
        'contraste' => false,
        'textoGrande' => false,
        'sublinhar' => false,
        'semAnimacao' => false,
        'libras' => false
    ];

    try {
        $json = json_encode($prefsPadrao, JSON_UNESCAPED_UNICODE);
        $stmt = $pdo->prepare("UPDATE usuarios SET preferencias = :pref::jsonb WHERE id = :id");
        $stmt->execute(['pref' => $json, 'id' => $usuarioId]);

        if (isset($_SESSION['usuario_cache'])) {
            $_SESSION['usuario_cache']['preferencias'] = $prefsPadrao;
            $_SESSION['usuario_cache_time'] = time();
        }

        responderJson(['sucesso' => true, 'preferencias' => $prefsPadrao]);
    } catch (Exception $e) {
        responderJson(['sucesso' => false, 'mensagem' => 'Erro ao resetar: ' . $e->getMessage()]);
    }
}

// Salvar preferências recebidas via JSON payload ou POST
$rawInput = file_get_contents('php://input');
$dadosRecebidos = json_decode($rawInput, true);
if (!is_array($dadosRecebidos)) {
    $dadosRecebidos = $_POST;
}

$chavesPermitidas = [
    'cor_tema', 'idioma', 'tema_modo', 'escuro', 'contraste',
    'textoGrande', 'sublinhar', 'semAnimacao', 'libras'
];

foreach ($chavesPermitidas as $chave) {
    if (array_key_exists($chave, $dadosRecebidos)) {
        $valor = $dadosRecebidos[$chave];

        // Normalização de tipos
        if (in_array($chave, ['escuro', 'contraste', 'textoGrande', 'sublinhar', 'semAnimacao', 'libras'])) {
            $prefsAtuais[$chave] = filter_var($valor, FILTER_VALIDATE_BOOLEAN);
        } elseif ($chave === 'cor_tema') {
            $prefsAtuais[$chave] = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$valor);
        } elseif ($chave === 'idioma') {
            $prefsAtuais[$chave] = in_array($valor, ['pt-BR', 'en']) ? $valor : 'pt-BR';
        } elseif ($chave === 'tema_modo') {
            $prefsAtuais[$chave] = in_array($valor, ['claro', 'escuro', 'sistema', 'manual']) ? $valor : 'claro';
        }
    }
}

try {
    $json = json_encode($prefsAtuais, JSON_UNESCAPED_UNICODE);
    $stmt = $pdo->prepare("UPDATE usuarios SET preferencias = :pref::jsonb WHERE id = :id");
    $stmt->execute(['pref' => $json, 'id' => $usuarioId]);

    if (isset($_SESSION['usuario_cache'])) {
        $_SESSION['usuario_cache']['preferencias'] = $prefsAtuais;
        $_SESSION['usuario_cache_time'] = time();
    }

    responderJson(['sucesso' => true, 'preferencias' => $prefsAtuais]);
} catch (Exception $e) {
    responderJson(['sucesso' => false, 'mensagem' => 'Erro ao salvar: ' . $e->getMessage()]);
}
