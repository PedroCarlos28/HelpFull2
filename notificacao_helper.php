<?php
/**
 * Helper centralizado para notificações inteligentes e detecção de sequências de humor
 */

function garantirTabelaNotificacoesSistema(PDO $pdo): void
{
    static $garantida = false;
    if ($garantida) return;

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
            created_at TIMESTAMPTZ DEFAULT NOW(),
            UNIQUE (usuario_id, chave)
        )");
        try {
            $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_notificacoes_sistema_usr_chave ON notificacoes_sistema (usuario_id, chave)");
        } catch (PDOException $eIndex) {}
        $garantida = true;
    } catch (PDOException $e) {}
}

/**
 * Avalia se o usuário possui 3 ou mais reações ruins (ou positivas) seguidas
 * e gera/retorna a notificação correspondente.
 */
function verificarOuGerarNotificacaoStreak(PDO $pdo, $usuarioId): ?array
{
    if (empty($usuarioId)) {
        return null;
    }

    garantirTabelaNotificacoesSistema($pdo);

    $emocoesRuins = ['irritado', 'ansioso', 'triste'];
    $emocoesBoas  = ['feliz', 'calmo', 'amoroso'];

    try {
        // Busca os últimos 20 registros que possuem emoção selecionada
        $stmt = $pdo->prepare("SELECT id, emocao_selecionada, texto_diario, criado_em 
                               FROM diario 
                               WHERE usuario_id = ? 
                                 AND emocao_selecionada IS NOT NULL 
                                 AND TRIM(emocao_selecionada) != '' 
                                 AND LOWER(TRIM(emocao_selecionada)) != 'null'
                               ORDER BY criado_em DESC 
                               LIMIT 20");
        $stmt->execute([$usuarioId]);
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($registros)) {
            return null;
        }

        // 1. Contagem de reações ruins seguidas (do mais recente para o mais antigo)
        $consecutivasRuins = 0;
        $idsRuins = [];
        $emocoesSeqRuins = [];
        $contextoTexto = "";

        foreach ($registros as $reg) {
            $emocao = mb_strtolower(trim((string)$reg['emocao_selecionada']), 'UTF-8');
            if (in_array($emocao, $emocoesRuins, true)) {
                $consecutivasRuins++;
                $idsRuins[] = (string)$reg['id'];
                $emocoesSeqRuins[] = ucfirst($emocao);
                if (!empty($reg['texto_diario'])) {
                    $contextoTexto .= " - " . $reg['texto_diario'] . "\n";
                }
            } else {
                // Interrompe na primeira emoção que não seja ruim (ex: feliz, calmo, amoroso)
                break;
            }
        }

        if ($consecutivasRuins >= 3) {
            $idMaisRecente = $idsRuins[0];
            $chave = 'ia_humor_streak:' . $idMaisRecente;

            // Verifica se a notificação para esse registro mais recente já existe
            $stmtExist = $pdo->prepare("SELECT * FROM notificacoes_sistema WHERE usuario_id = ? AND chave = ?");
            $stmtExist->execute([$usuarioId, $chave]);
            $existente = $stmtExist->fetch(PDO::FETCH_ASSOC);

            if ($existente) {
                return [
                    'id'          => (int)$existente['id'],
                    'chave'       => $existente['chave'],
                    'tipo'        => $existente['tipo'],
                    'titulo'      => $existente['titulo'],
                    'mensagem'    => $existente['mensagem'],
                    'intensidade' => $existente['intensidade'] ?: 'media',
                    'link'        => $existente['link'] ?: 'Atividades.php',
                    'textoBotao'  => $existente['texto_botao'] ?: 'Ver',
                    'lida'        => (int)$existente['lida'],
                    'mostrar'     => ((int)$existente['lida'] < 2),
                    'nova'        => ((int)$existente['lida'] === 0)
                ];
            }

            // Notificação ainda não criada para essa streak: gerar nova
            $acoes = [
                3 => [
                    'msg'  => 'Percebemos que você registrou emoções difíceis em sequência. Que tal experimentar uma atividade de relaxamento?',
                    'link' => 'Atividades.php',
                    'btn'  => 'Ver atividades'
                ],
                4 => [
                    'msg'  => 'Você registrou várias emoções pesadas seguidas. Uma conversa pode ajudar — o Helpy está aqui por você.',
                    'link' => 'ChatBOT.php',
                    'btn'  => 'Conversar'
                ],
                5 => [
                    'msg'  => 'Temos acompanhado seus registros recentes. Você não precisa passar por isso sozinho — o Helpy está disponível para te acolher.',
                    'link' => 'ChatBOT.php',
                    'btn'  => 'Buscar apoio'
                ],
            ];

            $nivel = $consecutivasRuins >= 5 ? 5 : ($consecutivasRuins >= 4 ? 4 : 3);
            $intensidade = $consecutivasRuins >= 5 ? 'alta' : ($consecutivasRuins >= 4 ? 'alta' : 'media');
            $titulo = 'Cuidando de você ✦';
            $mensagem = $acoes[$nivel]['msg'];
            $link = $acoes[$nivel]['link'];
            $textoBotao = $acoes[$nivel]['btn'];

            // Tenta obter mensagem personalizada via Gemini
            if (file_exists(__DIR__ . '/config_keys.php')) {
                require_once __DIR__ . '/config_keys.php';
            }
            $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : (getenv('GEMINI_API_KEY') ?: '');
            if (!empty($apiKey)) {
                try {
                    $emocoesStr = implode(', ', array_reverse(array_slice($emocoesSeqRuins, 0, 3)));
                    $prompt = "Aja como o assistente empático do aplicativo de bem-estar HelpFull. O usuário acabou de registrar $consecutivasRuins reações emocionais difíceis seguidas ($emocoesStr)." .
                        (!empty($contextoTexto) ? " Diário recente: $contextoTexto" : "") .
                        " Escreva uma frase acolhedora e empática de até 20 palavras sugerindo uma pausa ou conversa de apoio.";

                    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                        "contents" => [["parts" => [["text" => $prompt]]]]
                    ]));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 4);
                    $resp = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($httpCode === 200) {
                        $resJson = json_decode($resp, true);
                        $textoIa = trim($resJson['candidates'][0]['content']['parts'][0]['text'] ?? '');
                        if (!empty($textoIa) && mb_strlen($textoIa) <= 220) {
                            $mensagem = $textoIa;
                        }
                    }
                } catch (Exception $e) {}
            }

            $stmtIns = $pdo->prepare("INSERT INTO notificacoes_sistema 
                (usuario_id, chave, tipo, titulo, mensagem, intensidade, link, texto_botao, lida) 
                VALUES (?, ?, 'ia_humor', ?, ?, ?, ?, ?, 0)");
            $stmtIns->execute([
                $usuarioId,
                $chave,
                $titulo,
                $mensagem,
                $intensidade,
                $link,
                $textoBotao
            ]);

            return [
                'id'          => (int)$pdo->lastInsertId(),
                'chave'       => $chave,
                'tipo'        => 'ia_humor',
                'titulo'      => $titulo,
                'mensagem'    => $mensagem,
                'intensidade' => $intensidade,
                'link'        => $link,
                'textoBotao'  => $textoBotao,
                'lida'        => 0,
                'mostrar'     => true,
                'nova'        => true
            ];
        }

        // 2. Contagem de reações positivas seguidas (caso não esteja em streak ruim)
        $consecutivasBoas = 0;
        $idsBoas = [];
        foreach ($registros as $reg) {
            $emocao = mb_strtolower(trim((string)$reg['emocao_selecionada']), 'UTF-8');
            if (in_array($emocao, $emocoesBoas, true)) {
                $consecutivasBoas++;
                $idsBoas[] = (string)$reg['id'];
            } else {
                break;
            }
        }

        if ($consecutivasBoas >= 3) {
            $idMaisRecente = $idsBoas[0];
            $chave = 'ia_positivo_streak:' . $idMaisRecente;

            $stmtExist = $pdo->prepare("SELECT * FROM notificacoes_sistema WHERE usuario_id = ? AND chave = ?");
            $stmtExist->execute([$usuarioId, $chave]);
            $existente = $stmtExist->fetch(PDO::FETCH_ASSOC);

            if ($existente) {
                return [
                    'id'          => (int)$existente['id'],
                    'chave'       => $existente['chave'],
                    'tipo'        => $existente['tipo'],
                    'titulo'      => $existente['titulo'],
                    'mensagem'    => $existente['mensagem'],
                    'intensidade' => $existente['intensidade'] ?: 'baixa',
                    'link'        => $existente['link'] ?: 'Diario.php',
                    'textoBotao'  => $existente['texto_botao'] ?: 'Abrir diário',
                    'lida'        => (int)$existente['lida'],
                    'mostrar'     => ((int)$existente['lida'] < 2),
                    'nova'        => ((int)$existente['lida'] === 0)
                ];
            }

            $stmtIns = $pdo->prepare("INSERT INTO notificacoes_sistema 
                (usuario_id, chave, tipo, titulo, mensagem, intensidade, link, texto_botao, lida) 
                VALUES (?, ?, 'ia_positivo', 'Continue assim ✦', 'Você registrou emoções positivas seguidas. Continue cuidando de você!', 'baixa', 'Diario.php', 'Abrir diário', 0)");
            $stmtIns->execute([$usuarioId, $chave]);

            return [
                'id'          => (int)$pdo->lastInsertId(),
                'chave'       => $chave,
                'tipo'        => 'ia_positivo',
                'titulo'      => 'Continue assim ✦',
                'mensagem'    => 'Você registrou emoções positivas seguidas. Continue cuidando de você!',
                'intensidade' => 'baixa',
                'link'        => 'Diario.php',
                'textoBotao'  => 'Abrir diário',
                'lida'        => 0,
                'mostrar'     => true,
                'nova'        => true
            ];
        }

    } catch (Exception $e) {
        error_log("Erro ao verificar streak de reações: " . $e->getMessage());
    }

    return null;
}

/**
 * Identifica o dispositivo de forma simples e direta para o texto da notificação
 */
function identificarDispositivoSimples(): string
{
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (preg_match('/iphone/i', $ua)) return 'iPhone';
    if (preg_match('/ipad/i', $ua)) return 'iPad';
    if (preg_match('/android/i', $ua)) return 'Android';
    if (preg_match('/macintosh|mac os x/i', $ua)) return 'Mac';
    if (preg_match('/windows nt 10/i', $ua)) return 'Windows';
    if (preg_match('/windows/i', $ua)) return 'Windows';
    if (preg_match('/linux/i', $ua)) return 'Linux';
    return '';
}

/**
 * Registra ou atualiza uma notificação simples no sistema (ex: eventos de conta e segurança)
 */
function registrarNotificacaoSistema(
    PDO $pdo,
    $usuarioId,
    string $chave,
    string $tipo,
    string $titulo,
    string $mensagem,
    string $intensidade = 'baixa',
    string $link = 'Perfil.php',
    string $textoBotao = 'Ver'
): bool {
    if (empty($usuarioId)) {
        return false;
    }

    garantirTabelaNotificacoesSistema($pdo);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO notificacoes_sistema 
                (usuario_id, chave, tipo, titulo, mensagem, intensidade, link, texto_botao, lida, created_at)
            VALUES 
                (:usuario_id, :chave, :tipo, :titulo, :mensagem, :intensidade, :link, :texto_botao, 0, NOW())
            ON CONFLICT (usuario_id, chave) DO UPDATE SET
                tipo = EXCLUDED.tipo,
                titulo = EXCLUDED.titulo,
                mensagem = EXCLUDED.mensagem,
                intensidade = EXCLUDED.intensidade,
                link = EXCLUDED.link,
                texto_botao = EXCLUDED.texto_botao,
                lida = 0,
                created_at = NOW()
        ");

        return $stmt->execute([
            ':usuario_id'   => $usuarioId,
            ':chave'        => $chave,
            ':tipo'         => $tipo,
            ':titulo'       => $titulo,
            ':mensagem'     => $mensagem,
            ':intensidade'  => $intensidade,
            ':link'         => $link,
            ':texto_botao'  => $textoBotao
        ]);
    } catch (Exception $e) {
        // Fallback para bancos que não tenham a restrição nomeada direta no ON CONFLICT
        try {
            $stmtUpd = $pdo->prepare("
                UPDATE notificacoes_sistema 
                SET tipo = :tipo, titulo = :titulo, mensagem = :mensagem, intensidade = :intensidade,
                    link = :link, texto_botao = :texto_botao, lida = 0, created_at = NOW()
                WHERE usuario_id = :usuario_id AND chave = :chave
            ");
            $stmtUpd->execute([
                ':tipo'         => $tipo,
                ':titulo'       => $titulo,
                ':mensagem'     => $mensagem,
                ':intensidade'  => $intensidade,
                ':link'         => $link,
                ':texto_botao'  => $textoBotao,
                ':usuario_id'   => $usuarioId,
                ':chave'        => $chave
            ]);

            if ($stmtUpd->rowCount() === 0) {
                $stmtIns = $pdo->prepare("
                    INSERT INTO notificacoes_sistema 
                        (usuario_id, chave, tipo, titulo, mensagem, intensidade, link, texto_botao, lida, created_at)
                    VALUES 
                        (:usuario_id, :chave, :tipo, :titulo, :mensagem, :intensidade, :link, :texto_botao, 0, NOW())
                ");
                return $stmtIns->execute([
                    ':usuario_id'   => $usuarioId,
                    ':chave'        => $chave,
                    ':tipo'         => $tipo,
                    ':titulo'       => $titulo,
                    ':mensagem'     => $mensagem,
                    ':intensidade'  => $intensidade,
                    ':link'         => $link,
                    ':texto_botao'  => $textoBotao
                ]);
            }
            return true;
        } catch (Exception $ex2) {
            error_log("Erro ao salvar notificacao sistema: " . $ex2->getMessage());
            return false;
        }
    }
}

/**
 * Notificação simples de novo login na conta (espelho do e-mail)
 */
function registrarNotificacaoNovoLogin(PDO $pdo, $usuarioId, $dispositivo = null): bool
{
    $disp = $dispositivo ?: identificarDispositivoSimples();
    $sufixo = !empty($disp) ? " via {$disp}" : "";

    return registrarNotificacaoSistema(
        $pdo,
        $usuarioId,
        'login_' . date('Y-m-d'),
        'seguranca_login',
        'Novo acesso à conta',
        'Identificamos um novo login recente na sua conta' . $sufixo . '.',
        'baixa',
        'Perfil.php',
        'Ver'
    );
}

/**
 * Notificação simples de senha alterada (espelho do e-mail)
 */
function registrarNotificacaoSenhaAlterada(PDO $pdo, $usuarioId): bool
{
    return registrarNotificacaoSistema(
        $pdo,
        $usuarioId,
        'senha_alterada_' . date('Y-m-d'),
        'seguranca_senha',
        'Senha alterada',
        'Sua senha de acesso foi atualizada com sucesso.',
        'media',
        'Perfil.php',
        'Segurança'
    );
}

/**
 * Notificação simples de boas-vindas / conta criada (espelho do e-mail)
 */
function registrarNotificacaoBoasVindas(PDO $pdo, $usuarioId): bool
{
    return registrarNotificacaoSistema(
        $pdo,
        $usuarioId,
        'boas_vindas',
        'conta_boas_vindas',
        'Bem-vindo(a) ao HelpFull! 🌿',
        'Sua conta foi criada. Comece explorando seu diário e atividades.',
        'baixa',
        'inicio.php',
        'Explorar'
    );
}

/**
 * Notificação simples de 2FA alterado
 */
function registrarNotificacao2FA(PDO $pdo, $usuarioId, bool $ativo): bool
{
    $msg = $ativo 
        ? 'A verificação em duas etapas foi ativada na sua conta.' 
        : 'A verificação em duas etapas foi desativada no seu perfil.';

    return registrarNotificacaoSistema(
        $pdo,
        $usuarioId,
        '2fa_status_' . date('Y-m-d'),
        'seguranca_2fa',
        'Segurança da conta',
        $msg,
        'baixa',
        'Perfil.php',
        'Configurar'
    );
}

/**
 * Retorna a lista de notificações ativas para o painel
 */
function obterNotificacoesAtivasUsuario(PDO $pdo, $usuarioId): array
{
    garantirTabelaNotificacoesSistema($pdo);

    // Garante que o streak atual seja avaliado
    verificarOuGerarNotificacaoStreak($pdo, $usuarioId);

    // Se o usuário logado ainda não possui nenhuma notificação de login ou conta registrada hoje,
    // registra a notificação simples de acesso para a sessão atual
    try {
        $stmtChk = $pdo->prepare("SELECT id FROM notificacoes_sistema 
                                  WHERE usuario_id = ? AND tipo = 'seguranca_login' 
                                  LIMIT 1");
        $stmtChk->execute([$usuarioId]);
        if (!$stmtChk->fetch()) {
            registrarNotificacaoNovoLogin($pdo, $usuarioId);
        }
    } catch (Exception $e) {}

    try {
        $stmt = $pdo->prepare("SELECT * FROM notificacoes_sistema 
                               WHERE usuario_id = ? AND lida < 2 
                               ORDER BY created_at DESC 
                               LIMIT 10");
        $stmt->execute([$usuarioId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [];
        foreach ($rows as $r) {
            $resultado[] = [
                'id'          => (int)$r['id'],
                'chave'       => $r['chave'],
                'tipo'        => $r['tipo'],
                'titulo'      => $r['titulo'],
                'mensagem'    => $r['mensagem'],
                'intensidade' => $r['intensidade'] ?: 'media',
                'link'        => $r['link'] ?: 'Diario.php',
                'textoBotao'  => $r['texto_botao'] ?: 'Ver',
                'lida'        => (int)$r['lida'],
                'mostrar'     => true,
                'nova'        => ((int)$r['lida'] === 0)
            ];
        }
        return $resultado;
    } catch (Exception $e) {
        return [];
    }
}
