<?php
require_once 'conexao.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['mostrar' => false]);
    exit;
}

$id = $_SESSION['usuario_id'];

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS notificacoes_sistema (
        id SERIAL PRIMARY KEY,
        usuario_id UUID NOT NULL,
        chave VARCHAR(180) NOT NULL,
        tipo VARCHAR(40) NOT NULL,
        titulo TEXT,
        mensagem TEXT,
        intensidade VARCHAR(10) DEFAULT 'media',
        link VARCHAR(80),
        texto_botao VARCHAR(40),
        lida SMALLINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE (usuario_id, chave)
    )");
} catch (PDOException $e) {
    echo json_encode(['mostrar' => false]);
    exit;
}

$acao = $_GET['acao'] ?? '';

if ($acao === 'limpar') {
    $stmt = $pdo->prepare("DELETE FROM notificacoes_sistema WHERE usuario_id = ?");
    $stmt->execute([$id]);
    echo json_encode(['sucesso' => true]);
    exit;
}

if ($acao === 'marcar_lidas') {
    $stmt = $pdo->prepare("UPDATE notificacoes_sistema SET lida = 1 WHERE usuario_id = ? AND lida = 0");
    $stmt->execute([$id]);
    echo json_encode(['sucesso' => true]);
    exit;
}

function formatarNotif(array $row, bool $nova): array
{
    return [
        'mostrar' => true,
        'nova' => $nova,
        'titulo' => $row['titulo'],
        'mensagem' => $row['mensagem'],
        'intensidade' => $row['intensidade'] ?: 'media',
        'link' => $row['link'] ?: 'Diario.php',
        'textoBotao' => $row['texto_botao'] ?: 'Ver',
    ];
}

function buscarPorChave(PDO $pdo, $usuarioId, string $chave)
{
    $stmt = $pdo->prepare("SELECT * FROM notificacoes_sistema WHERE usuario_id = ? AND chave = ?");
    $stmt->execute([$usuarioId, $chave]);
    return $stmt->fetch();
}

function salvarNotif(PDO $pdo, $usuarioId, string $chave, string $tipo, array $dados): array
{
    $existente = buscarPorChave($pdo, $usuarioId, $chave);
    if ($existente) {
        if ((int) $existente['lida'] === 1) {
            return ['mostrar' => false];
        }
        return formatarNotif($existente, false);
    }

    $stmt = $pdo->prepare("INSERT INTO notificacoes_sistema
        (usuario_id, chave, tipo, titulo, mensagem, intensidade, link, texto_botao, lida)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)");
    $stmt->execute([
        $usuarioId,
        $chave,
        $tipo,
        $dados['titulo'],
        $dados['mensagem'],
        $dados['intensidade'],
        $dados['link'],
        $dados['textoBotao'],
    ]);

    return array_merge(['mostrar' => true, 'nova' => true], $dados);
}

function gerarComIA(string $contextoTexto, int $contagemRuim): ?array
{
    if (file_exists(__DIR__ . '/config_keys.php')) {
        require_once __DIR__ . '/config_keys.php';
    }
    $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : (getenv('GEMINI_API_KEY') ?: '');
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

    $prompt = "Aja como o assistente do app HelpFull. O usuário registrou $contagemRuim emoções negativas nos últimos dias.
        Conteúdo recente do diário:
        $contextoTexto

        Gere uma frase curta (máx 20 palavras), empática e acolhedora indicando uma atividade de relaxamento ou conversa.";

    $data = [
        "contents" => [["parts" => [["text" => $prompt]]]],
        "generationConfig" => [
            "responseMimeType" => "application/json",
            "responseSchema" => [
                "type" => "OBJECT",
                "properties" => [
                    "titulo" => ["type" => "STRING", "description" => "Um título curto (ex: 'Cuidando de você')"],
                    "mensagem" => ["type" => "STRING", "description" => "A frase empática"],
                    "intensidade" => ["type" => "STRING", "description" => "Escolha entre: baixa, media, alta"],
                    "link" => ["type" => "STRING", "description" => "Deve ser 'Atividades.php' ou 'ChatBOT.php'"],
                    "textoBotao" => ["type" => "STRING", "description" => "Texto curto para o botão (ex: 'Ir para Chat')"]
                ]
            ]
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return null;
    }

    $result = json_decode($response, true);
    $jsonString = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $dadosIA = json_decode($jsonString, true);

    if (!$dadosIA || empty($dadosIA['mensagem'])) {
        return null;
    }

    $intensidade = $dadosIA['intensidade'] ?? 'alta';
    if (!in_array($intensidade, ['baixa', 'media', 'alta'], true)) {
        $intensidade = 'alta';
    }
    $link = $dadosIA['link'] ?? 'ChatBOT.php';
    if (!in_array($link, ['Atividades.php', 'ChatBOT.php'], true)) {
        $link = 'ChatBOT.php';
    }

    return [
        'titulo' => $dadosIA['titulo'] ?? 'Cuidando de você',
        'mensagem' => $dadosIA['mensagem'],
        'intensidade' => $intensidade,
        'link' => $link,
        'textoBotao' => $dadosIA['textoBotao'] ?? 'Ver',
    ];
}

try {
    $stmt = $pdo->prepare("SELECT id, emocao_selecionada, texto_diario FROM diario WHERE usuario_id = ? ORDER BY criado_em DESC LIMIT 5");
    $stmt->execute([$id]);
    $ultimosDiarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $emocoesDificeis = ['irritado', 'ansioso', 'triste'];
    $emocoesPositivas = ['feliz', 'calmo', 'amoroso'];
    $idsRuim = [];
    $idsPositivo = [];
    $contextoTexto = "";

    foreach ($ultimosDiarios as $diario) {
        $emocao = mb_strtolower(trim((string) ($diario['emocao_selecionada'] ?? '')), 'UTF-8');
        if (in_array($emocao, $emocoesDificeis, true)) {
            $idsRuim[] = (string) $diario['id'];
        } elseif (in_array($emocao, $emocoesPositivas, true)) {
            $idsPositivo[] = (string) $diario['id'];
        }
        if (!empty($diario['texto_diario'])) {
            $contextoTexto .= " - " . $diario['texto_diario'] . "\n";
        }
    }

    $contagemRuim = count($idsRuim);
    $contagemPositivo = count($idsPositivo);

    if ($contagemRuim >= 3) {
        // Chave baseada na SEMANA — permite nova notificação toda semana
        $semana = date('Y-W');
        $chave = 'ia_humor:' . $semana;

        $existente = buscarPorChave($pdo, $id, $chave);
        if ($existente) {
            // Notificação desta semana já existe: mostrar se não foi lida
            echo json_encode(
                (int) $existente['lida'] === 1 ? ['mostrar' => false] : formatarNotif($existente, false),
                JSON_UNESCAPED_UNICODE
            );
            exit;
        }

        // Gera nova notificação de IA (ou usa fallback se a API falhar)
        $dadosIA = gerarComIA($contextoTexto, $contagemRuim);
        if (!$dadosIA) {
            // Mensagem de fallback — funciona sem API
            $acoes = [
                3 => ['msg' => 'Percebemos que você teve alguns dias difíceis. Que tal experimentar uma atividade de relaxamento?', 'link' => 'Atividades.php', 'btn' => 'Ver atividades'],
                4 => ['msg' => 'Você registrou várias emoções pesadas. Uma conversa pode ajudar — o ChatBOT está aqui por você.', 'link' => 'ChatBOT.php', 'btn' => 'Conversar'],
                5 => ['msg' => 'Temos acompanhado sua semana. Considere falar com um profissional de saúde mental. Você merece apoio.', 'link' => 'ChatBOT.php', 'btn' => 'Buscar apoio'],
            ];
            $nivel = $contagemRuim >= 5 ? 5 : ($contagemRuim >= 4 ? 4 : 3);
            $intensidade = $contagemRuim >= 5 ? 'alta' : ($contagemRuim >= 4 ? 'alta' : 'media');
            $dadosIA = [
                'titulo'      => 'Cuidando de você ✦',
                'mensagem'    => $acoes[$nivel]['msg'],
                'intensidade' => $intensidade,
                'link'        => $acoes[$nivel]['link'],
                'textoBotao'  => $acoes[$nivel]['btn'],
            ];
        }

        echo json_encode(salvarNotif($pdo, $id, $chave, 'ia_humor', $dadosIA), JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($contagemRuim >= 1) {
        sort($idsRuim);
        $chave = 'tpl_ruim:' . implode(',', $idsRuim);
        $palavra = $contagemRuim === 1 ? 'emoção difícil' : 'emoções difíceis';
        $dados = [
            'titulo' => 'Um momento de cuidado',
            'mensagem' => "Você registrou $contagemRuim $palavra recentemente. Que tal uma pausa?",
            'intensidade' => 'media',
            'link' => 'Atividades.php',
            'textoBotao' => 'Ver atividades',
        ];
        echo json_encode(salvarNotif($pdo, $id, $chave, 'tpl_ruim', $dados), JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($contagemPositivo >= 3) {
        sort($idsPositivo);
        $chave = 'tpl_positivo:' . implode(',', $idsPositivo);
        $dados = [
            'titulo' => 'Continue assim',
            'mensagem' => "Você registrou $contagemPositivo emoções positivas seguidas. Continue cuidando de você.",
            'intensidade' => 'baixa',
            'link' => 'Diario.php',
            'textoBotao' => 'Abrir diário',
        ];
        echo json_encode(salvarNotif($pdo, $id, $chave, 'tpl_positivo', $dados), JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['mostrar' => false]);
} catch (PDOException $e) {
    echo json_encode(['mostrar' => false, 'erro' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
