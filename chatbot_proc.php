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
    // Chave principal
    $key1 = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : (getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? ($_SERVER['GEMINI_API_KEY'] ?? '')));
    if (!empty($key1) && !in_array($key1, $apiKeys)) $apiKeys[] = $key1;

    // Chave reserva / backup (para Vercel e local)
    $key2 = defined('GEMINI_API_KEY_BACKUP') ? GEMINI_API_KEY_BACKUP : (getenv('GEMINI_API_KEY_BACKUP') ?: ($_ENV['GEMINI_API_KEY_BACKUP'] ?? ($_SERVER['GEMINI_API_KEY_BACKUP'] ?? '')));
    if (!empty($key2) && !in_array($key2, $apiKeys)) $apiKeys[] = $key2;

    // Terceira chave opcional
    $key3 = defined('GEMINI_API_KEY_3') ? GEMINI_API_KEY_3 : (getenv('GEMINI_API_KEY_3') ?: ($_ENV['GEMINI_API_KEY_3'] ?? ($_SERVER['GEMINI_API_KEY_3'] ?? '')));
    if (!empty($key3) && !in_array($key3, $apiKeys)) $apiKeys[] = $key3;
}

if (empty($apiKeys)) {
    echo json_encode(['reply' => 'Nenhuma chave de API configurada para o chatbot.']);
    exit;
}

// Lista de modelos ordenados por prioridade (resposta rápida e fallback inteligente)
$modelos = [
    'gemini-2.5-flash',       // Resposta instantânea e alta estabilidade
    'gemini-3.5-flash',       // Modelo mais avançado da linha 3.5
    'gemini-flash-latest',    // Alias oficial Google
    'gemini-3.5-flash-lite',  // Variante leve e rápida
    'gemini-3-flash-preview'  // Linha 3.0 preview
];

// Instrução de Sistema Unificada e Ética para o Helpy (Saúde Mental)
$systemPrompt = "Você é o Helpy, o assistente virtual empático, acolhedor e humanizado do aplicativo HelpFull.
Sua missão ÚNICA e EXCLUSIVA é apoiar o bem-estar emocional, a saúde mental e o autocuidado dos usuários.

REGRAS E DIRETRIZES ÉTICAS OBRIGATÓRIAS:
1. ESCOPO ESTRITO (APENAS SAÚDE MENTAL E BEM-ESTAR):
- Responda apenas a mensagens sobre emoções, sentimentos, ansiedade, estresse, rotina de autocuidado, desabafos e reflexões de bem-estar.
- NUNCA responda a perguntas fora desse tema (ex: programação, receitas, política, esportes, compras, tarefas gerais, etc.). Se o usuário perguntar algo fora do contexto, recuse com gentileza, carinho e acolhimento, explicando que seu foco exclusivo no HelpFull é apoiar a saúde emocional dele e convidando-o a falar sobre como ele está se sentindo.

2. NUNCA FORNEÇA DIAGNÓSTICOS MÉDICOS OU PSICOLÓGICOS:
- Você é uma IA de apoio e acolhimento, NÃO um médico ou psicólogo.
- Jamais emita diagnósticos clínicos (ex: 'você tem depressão', 'você tem fobia social', 'isso é TDAH').
- Jamais recomende medicamentos, remédios ou substâncias.
- Lembre sempre de forma humilde que apenas profissionais habilitados de saúde podem realizar diagnósticos e tratamentos.

3. CASOS CRÍTICOS E INDICAÇÃO DE AJUDA PROFISSIONAL:
- Se o usuário demonstrar sofrimento intenso, crise aguda, desespero, automutilação ou pensamentos de morte/suicídio:
  a) Acolha com calma, profundo respeito, empatia e sem julgamentos;
  b) Encoraje com carinho a busca por um profissional de psicologia ou psiquiatria;
  c) Indique de imediato os canais de apoio gratuitos no Brasil: ligar gratuitamente para o CVV (Centro de Valorização da Vida) no número 188 (apoio 24h, gratuito e confidencial) ou cvv.org.br. Em caso de emergência física imediata, mencione o SAMU 192.

4. TOM DE VOZ:
- Responda sempre em Português do Brasil com acolhimento, empatia, carinho e concisão, com parágrafos curtos e leitura leve.";

$data = [
    "system_instruction" => [
        "parts" => [
            ["text" => $systemPrompt]
        ]
    ],
    "contents" => [
        [
            "parts" => [
                ["text" => $userMessage]
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

// Se o Gemini falhou ou esgotou a cota, tenta a Groq como contingência gratuita e ultrarrápida
if (empty($botReply)) {
    $groqKey = defined('GROQ_API_KEY') ? GROQ_API_KEY : (getenv('GROQ_API_KEY') ?: ($_ENV['GROQ_API_KEY'] ?? ($_SERVER['GROQ_API_KEY'] ?? '')));
    if (!empty($groqKey)) {
        $groqModels = ['openai/gpt-oss-120b', 'openai/gpt-oss-20b', 'qwen/qwen3.8-27b'];
        $groqUrl = 'https://api.groq.com/openai/v1/chat/completions';

        foreach ($groqModels as $gModel) {
            $groqPayload = [
                'model' => $gModel,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt
                    ],
                    [
                        'role' => 'user',
                        'content' => $userMessage
                    ]
                ],
                'max_tokens' => 350,
                'temperature' => 0.7
            ];

            $chGroq = curl_init($groqUrl);
            curl_setopt($chGroq, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chGroq, CURLOPT_POST, true);
            curl_setopt($chGroq, CURLOPT_POSTFIELDS, json_encode($groqPayload));
            curl_setopt($chGroq, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $groqKey
            ]);
            curl_setopt($chGroq, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($chGroq, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($chGroq, CURLOPT_TIMEOUT, 8);

            $groqResp = curl_exec($chGroq);
            $groqCode = curl_getinfo($chGroq, CURLINFO_HTTP_CODE);
            curl_close($chGroq);

            if ($groqCode === 200) {
                $groqData = json_decode($groqResp, true);
                if (!empty($groqData['choices'][0]['message']['content'])) {
                    $botReply = trim($groqData['choices'][0]['message']['content']);
                    break;
                }
            }
        }
    }
}

// Se o Gemini e a Groq falharem, tenta o OpenRouter como terceira camada de contingência gratuita
if (empty($botReply)) {
    $orKey = defined('OPENROUTER_API_KEY') ? OPENROUTER_API_KEY : (getenv('OPENROUTER_API_KEY') ?: ($_ENV['OPENROUTER_API_KEY'] ?? ($_SERVER['OPENROUTER_API_KEY'] ?? '')));
    if (!empty($orKey)) {
        $orModels = ['liquid/lfm-2.5-2.6b:free', 'openrouter/free'];
        $orUrl = 'https://openrouter.ai/api/v1/chat/completions';

        foreach ($orModels as $oModel) {
            $orPayload = [
                'model' => $oModel,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt
                    ],
                    [
                        'role' => 'user',
                        'content' => $userMessage
                    ]
                ],
                'max_tokens' => 350
            ];

            $chOr = curl_init($orUrl);
            curl_setopt($chOr, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chOr, CURLOPT_POST, true);
            curl_setopt($chOr, CURLOPT_POSTFIELDS, json_encode($orPayload));
            curl_setopt($chOr, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $orKey,
                'HTTP-Referer: http://localhost/HelpFull2',
                'X-Title: HelpFull'
            ]);
            curl_setopt($chOr, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($chOr, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($chOr, CURLOPT_TIMEOUT, 10);

            $orResp = curl_exec($chOr);
            $orCode = curl_getinfo($chOr, CURLINFO_HTTP_CODE);
            curl_close($chOr);

            if ($orCode === 200) {
                $orData = json_decode($orResp, true);
                $respContent = $orData['choices'][0]['message']['content'] ?? '';
                if (empty($respContent)) {
                    $respContent = $orData['choices'][0]['message']['reasoning'] ?? '';
                }
                if (!empty($respContent)) {
                    $botReply = trim($respContent);
                    break;
                }
            }
        }
    }
}

// Se todos os modelos e chaves falharam, analisa o motivo para exibir a mensagem mais adequada e acolhedora:
if (empty($botReply)) {
    // 1. Erro de Quota / Limite de Requisições (429 / RESOURCE_EXHAUSTED / Rate Limit)
    if ($houveErroQuota) {
        $msgResposta = "Olá! Peço desculpas pelo transtorno. 💙\n\n"
                     . "Por se tratar de um **projeto da faculdade sem apoio financeiro**, nossos tokens de inteligência artificial são limitados e infelizmente se esgotaram no momento.\n\n"
                     . "⏳ As cotas de mensagens são renovadas periodicamente pelos provedores. Por favor, tente conversar comigo novamente mais tarde ou amanhã!\n\n"
                     . "Se você estiver precisando de apoio e acolhimento agora, lembre-se de que você não está sozinho(a):\n"
                     . "• Você pode ligar gratuitamente para o **CVV no número 188** (apoio emocional 24 horas por dia);\n"
                     . "• Sinta-se à vontade para registrar suas emoções no nosso **Diário** ou praticar os exercícios na aba de **Atividades**. 🌿✨";
    }
    // 2. Erro de Servidores Ocupados / Alta Demanda / Sobrecarga (503 / 502 / 504 / 500 / UNAVAILABLE / High Demand)
    elseif ($ultimoErroHttp === 503 || $ultimoErroHttp === 502 || $ultimoErroHttp === 504 || stripos($ultimoDetalheErro, 'demand') !== false || stripos($ultimoDetalheErro, 'unavailable') !== false || stripos($ultimoDetalheErro, 'overloaded') !== false) {
        $msgResposta = "Puxa, peço um pouquinho de paciência! ☁️💙\n\n"
                     . "Os servidores de Inteligência Artificial estão com uma **alta demanda de acessos simultâneos** ou passando por instabilidade técnica no momento.\n\n"
                     . "Por favor, aguarde de 1 a 2 minutinhos e tente me enviar sua mensagem novamente!\n\n"
                     . "Enquanto isso, você pode fazer uma pausa com os exercícios de respiração na aba de **Atividades** ou desabafar no seu **Diário**. Se for algo urgente, o **CVV atende 24h no 188**. 🌿";
    }
    // 3. Erro de Filtro de Segurança / Moderação (SAFETY / BLOCKED / HARM)
    elseif (stripos($ultimoDetalheErro, 'safety') !== false || stripos($ultimoDetalheErro, 'block') !== false || stripos($ultimoDetalheErro, 'harm') !== false) {
        $msgResposta = "Olá! Compreendo o que você está sentindo, mas os filtros automáticos de segurança não conseguiram processar a forma como a mensagem foi escrita. 🛡️💙\n\n"
                     . "Como o HelpFull é um espaço focado no seu **cuidado emocional e acolhimento**, tente reescrever com outras palavras focando em como você se sente no momento.\n\n"
                     . "Se você estiver em sofrimento agudo ou desespero, por favor, busque ajuda humana imediata: ligue gratuitamente para o **CVV no 188** (24 horas) ou **SAMU 192**. Você não está sozinho(a)! 🫂";
    }
    // 4. Erro de Conexão / Rede / Timeout (Código 0 / timeout / cURL error)
    elseif ($ultimoErroHttp === 0 || stripos($ultimoDetalheErro, 'timeout') !== false || stripos($ultimoDetalheErro, 'timed out') !== false || stripos($ultimoDetalheErro, 'could not resolve') !== false) {
        $msgResposta = "Ops! Tivemos uma oscilação na conexão com a internet e não consegui receber sua mensagem a tempo. 🌐💙\n\n"
                     . "Dê uma olhadinha na sua conexão e **tente me enviar novamente em instantes**!\n\n"
                     . "Estou aqui pronto para te ouvir e acolher seus sentimentos. ✨";
    }
    // 5. Erro Geral Inesperado
    else {
        $msgResposta = "Olá! Tive uma pequena dificuldade técnica para processar essa resposta agora. 🤖💙\n\n"
                     . "Como o HelpFull é um **projeto acadêmico independente desenvolvido para a faculdade**, nossos serviços às vezes passam por pequenas manutenções.\n\n"
                     . "Por favor, tente me mandar a mensagem novamente daqui a alguns instantes! Se estiver precisando de acolhimento urgente, o **CVV está disponível 24h pelo número 188**. 🌿✨";
    }

    echo json_encode([
        'reply' => $msgResposta,
        'quota_error' => $houveErroQuota,
        'aviso_sistema' => true
    ]);
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