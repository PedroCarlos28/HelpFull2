<?php
require_once 'conexao.php';
session_start();

header('Content-Type: application/json');

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['reply' => 'Você precisa estar logado para usar o chat.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? '';

if (empty($userMessage)) {
    echo json_encode(['reply' => 'Por favor, digite uma mensagem.']);
    exit;
}

if (file_exists(__DIR__ . '/config_keys.php')) {
    require_once __DIR__ . '/config_keys.php';
}

// Lista de chaves disponíveis (permite chave principal e backup)
$apiKeys = [];
if (defined('GEMINI_API_KEYS') && is_array(GEMINI_API_KEYS)) {
    $apiKeys = GEMINI_API_KEYS;
} else {
    if (defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY)) $apiKeys[] = GEMINI_API_KEY;
    if (defined('GEMINI_API_KEY_BACKUP') && !empty(GEMINI_API_KEY_BACKUP)) $apiKeys[] = GEMINI_API_KEY_BACKUP;
    $envKey = getenv('GEMINI_API_KEY');
    if (!empty($envKey) && !in_array($envKey, $apiKeys)) $apiKeys[] = $envKey;
}

if (empty($apiKeys)) {
    echo json_encode(['reply' => 'Nenhuma chave de API configurada para o chatbot.']);
    exit;
}

// Lista de modelos ordenados por prioridade (fallback inteligente)
$modelos = [
    'gemini-3.5-flash',       // Modelo 3.5 mais recente da Google
    'gemini-2.5-flash',       // Modelo estável e de alta performance
    'gemini-3.5-flash-lite',  // Variante leve e rápida do 3.5
    'gemini-3-flash-preview'  // Linha 3.0 preview
];

$data = [
    "contents" => [
        [
            "parts" => [
                ["text" => "Aja como o assistente Helpy do HelpFull. Sua missão é apoiar a saúde mental do usuário, sendo gentil, empático e oferecendo conselhos práticos de bem-estar. Se o usuário estiver em crise grave, sugira procurar ajuda profissional (CVV 188). Responda sempre em Português do Brasil de forma concisa.\n\nMensagem do usuário: " . $userMessage]
            ]
        ]
    ]
];

$botReply = null;
$houveErroQuota = false;
$ultimoErroHttp = 0;
$ultimoDetalheErro = 'Não foi possível obter resposta.';

// Tenta as chaves e modelos em cascata (fallback inteligente)
foreach ($apiKeys as $chaveAtual) {
    foreach ($modelos as $modeloAtual) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modeloAtual}:generateContent?key=" . $chaveAtual;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                $botReply = $result['candidates'][0]['content']['parts'][0]['text'];
                break 2; // Resposta obtida com sucesso! Encerra as tentativas.
            }
        }

        // Analisa o motivo da falha para decidir fallback
        $errorData = json_decode($response, true);
        $detalheErro = $errorData['error']['message'] ?? "Código HTTP $httpCode";
        $errorCode = (int)($errorData['error']['code'] ?? $httpCode);
        $errorStatus = $errorData['error']['status'] ?? '';

        $isQuota = ($httpCode === 429)
            || ($errorCode === 429)
            || ($errorStatus === 'RESOURCE_EXHAUSTED')
            || (stripos($detalheErro, 'quota') !== false)
            || (stripos($detalheErro, 'exhausted') !== false)
            || (stripos($detalheErro, 'rate limit') !== false);

        if ($isQuota) {
            $houveErroQuota = true;
        }

        $ultimoErroHttp = $httpCode;
        $ultimoDetalheErro = $detalheErro;

        // Se falhou (429, 503, 404, etc.), segue automaticamente para o próximo modelo/chave
    }
}

// Se todos os modelos e chaves falharam:
if (empty($botReply)) {
    if ($houveErroQuota) {
        $msgQuota = "Olá! Peço desculpas pelo transtorno. 💙\n\n"
                  . "Por se tratar de um **projeto da faculdade sem apoio financeiro**, nossos tokens de inteligência artificial são limitados e infelizmente se esgotaram no momento.\n\n"
                  . "⏳ As cotas de mensagens são renovadas periodicamente pelo provedor. Por favor, tente conversar comigo novamente mais tarde ou amanhã!\n\n"
                  . "Se você estiver precisando de apoio e acolhimento agora, lembre-se de que você não está sozinho(a):\n"
                  . "• Você pode ligar gratuitamente para o **CVV no número 188** (apoio emocional 24 horas por dia);\n"
                  . "• Sinta-se à vontade para registrar suas emoções no nosso **Diário** ou praticar os exercícios na aba de **Atividades**. 🌿✨";

        echo json_encode([
            'reply' => $msgQuota,
            'quota_error' => true
        ]);
        exit;
    }

    echo json_encode(['reply' => "Erro do Google ($ultimoErroHttp): $ultimoDetalheErro"]);
    exit;
}

// Salvar no histórico do banco de dados e verificar meta
$metaConcluida = false;
try {
    $sessao = date('Y-m-d');

    // Verifica se é a primeira vez hoje para a meta
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM historico_chat WHERE usuario_id = ? AND sessao_token = ?");
    $stmtCheck->execute([$_SESSION['usuario_id'], $sessao]);
    if ($stmtCheck->fetchColumn() == 0) {
        $metaConcluida = true;
    }

    $stmt = $pdo->prepare("INSERT INTO historico_chat (usuario_id, mensagem, resposta, sessao_token) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['usuario_id'], $userMessage, $botReply, $sessao]);
} catch (PDOException $e) {
    // Silencioso
}

// Devolve a resposta pra tela
echo json_encode([
    'reply' => $botReply,
    'meta_concluida' => $metaConcluida
]);
?>