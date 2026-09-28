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
$apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : (getenv('GEMINI_API_KEY') ?: '');

// URL do modelo estável (gemini-2.5-flash)
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

// Estrutura simplificada e direta
$data = [
    "contents" => [
        [
            "parts" => [
                ["text" => "Aja como o assistente Helpy do HelpFull. Sua missão é apoiar a saúde mental do usuário, sendo gentil, empático e oferecendo conselhos práticos de bem-estar. Se o usuário estiver em crise grave, sugira procurar ajuda profissional (CVV 188). Responda sempre em Português do Brasil de forma concisa.\n\nMensagem do usuário: " . $userMessage]
            ]
        ]
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

// Ignorar erros de SSL (comum em localhost/XAMPP)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// VERIFICAÇÃO DE ERRO CIRÚRGICA
if ($httpCode !== 200) {
    $errorData = json_decode($response, true);
    $detalheErro = isset($errorData['error']['message']) ? $errorData['error']['message'] : "Motivo desconhecido";
    $errorCode = isset($errorData['error']['code']) ? (int)$errorData['error']['code'] : $httpCode;
    $errorStatus = isset($errorData['error']['status']) ? $errorData['error']['status'] : '';

    // Verifica se é erro 429 ou cota de tokens excedida
    $isQuota = ($httpCode === 429)
        || ($errorCode === 429)
        || ($errorStatus === 'RESOURCE_EXHAUSTED')
        || (stripos($detalheErro, 'quota') !== false)
        || (stripos($detalheErro, 'exhausted') !== false)
        || (stripos($detalheErro, 'rate limit') !== false);

    if ($isQuota) {
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

    echo json_encode(['reply' => "Erro do Google ($httpCode): $detalheErro"]);
    exit;
}

$result = json_decode($response, true);

// Pega a resposta de forma segura
if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    $botReply = $result['candidates'][0]['content']['parts'][0]['text'];
} else {
    $botReply = 'Não consegui processar sua mensagem corretamente. O retorno da IA veio vazio.';
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