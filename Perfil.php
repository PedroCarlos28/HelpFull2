<?php
require_once 'conexao.php';

$usuarioLogado = null;
if (isset($_SESSION['usuario_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT id, nome, email, foto_perfil, videos_assistidos, ultimo_video_data, COALESCE(dois_fatores_ativo, false) as dois_fatores_ativo FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['usuario_id']]);
        $usuarioLogado = $stmt->fetch();
    } catch (Exception $e) {
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if ($_POST['acao'] === 'editar_conta') {
        $novoNome = trim($_POST['nome']);
        $novoEmail = trim($_POST['email']);
        $novaFoto = $_POST['foto_base64'] ?? null;
        $novo2FA = isset($_POST['dois_fatores_ativo']) && $_POST['dois_fatores_ativo'] === '1';
        if (!empty($novoNome) && !empty($novoEmail)) {
            // Atualiza sempre a foto, permitindo que ela seja removida (vazia)
            $stmtUp = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, foto_perfil = ?, dois_fatores_ativo = ? WHERE id = ?");
            $stmtUp->execute([$novoNome, $novoEmail, $novaFoto, $novo2FA ? 'true' : 'false', $_SESSION['usuario_id']]);
            $_SESSION['usuario_nome'] = $novoNome;
        }
        header("Location: Perfil.php?sucesso=1");
        exit;
    }
    if ($_POST['acao'] === 'apagar_diario') {
        $diarioId = $_POST['diario_id'];
        $stmtDel = $pdo->prepare("DELETE FROM diario WHERE id = ? AND usuario_id = ?");
        $stmtDel->execute([$diarioId, $_SESSION['usuario_id']]);
        header("Location: Perfil.php?apagado=1");
        exit;
    }
}

if (!$usuarioLogado) {
    header("Location: Comeco.php");
    exit;
}

$fotoPerfilDb = !empty($usuarioLogado['foto_perfil']) ? $usuarioLogado['foto_perfil'] : '';
$id = $usuarioLogado['id'];
$hoje = date('Y-m-d');

$stmtPosts = $pdo->prepare("SELECT COUNT(*) FROM posts_comunidade WHERE usuario_id = ?");
$stmtPosts->execute([$id]);
$totalPosts = $stmtPosts->fetchColumn();

$stmtChats = $pdo->prepare("SELECT COUNT(DISTINCT sessao_token) FROM historico_chat WHERE usuario_id = ?");
$stmtChats->execute([$id]);
$totalChats = $stmtChats->fetchColumn();

$stmtChatHoje = $pdo->prepare("SELECT COUNT(*) FROM historico_chat WHERE usuario_id = ? AND sessao_token = ?");
$stmtChatHoje->execute([$id, $hoje]);
$usouChatHoje = ($stmtChatHoje->fetchColumn() > 0);

$totalVideos = $usuarioLogado['videos_assistidos'] ?? 0;
$assistiuVideoHoje = (($usuarioLogado['ultimo_video_data'] ?? '') === $hoje);

// Contar total de curtidas recebidas em todos os posts
$stmtLikesRec = $pdo->prepare("SELECT COUNT(*) FROM curtidas_comunidade c JOIN posts_comunidade p ON c.post_id = p.id WHERE p.usuario_id = ?");
$stmtLikesRec->execute([$id]);
$totalLikesRecebidos = $stmtLikesRec->fetchColumn();

$totalDiarios = 0;
$totalEmocoes = 0;
$diarioHoje = false;
$emocaoHoje = false;
$diariosPorData = [];
$diariosPorAnoMes = [];
$emocoesGlobaisLista = ['Irritado' => 0, 'Ansioso' => 0, 'Feliz' => 0, 'Calmo' => 0, 'Triste' => 0, 'Amoroso' => 0];
$anosDisponiveis = [date('Y')];

try {
    $stmtAll = $pdo->prepare("SELECT id, TO_CHAR(criado_em, 'YYYY-MM-DD') as data_reg, TO_CHAR(criado_em, 'HH24:MI') as hora_reg, texto_diario, emocao_selecionada FROM diario WHERE usuario_id = ? ORDER BY criado_em ASC");
    $stmtAll->execute([$id]);
    $allDiarios = $stmtAll->fetchAll();

    foreach ($allDiarios as $reg) {
        $totalDiarios++;
        $data = $reg['data_reg'];
        $ano = substr($data, 0, 4);
        $mes = (int) substr($data, 5, 2);

        if (!in_array($ano, $anosDisponiveis))
            $anosDisponiveis[] = $ano;
        if (!isset($diariosPorData[$data]))
            $diariosPorData[$data] = [];

        $diariosPorData[$data][] = [
            'id' => $reg['id'],
            'texto' => htmlspecialchars((string) $reg['texto_diario']),
            'emocao' => htmlspecialchars((string) $reg['emocao_selecionada']),
            'hora' => $reg['hora_reg']
        ];

        if (!isset($diariosPorAnoMes[$ano]))
            $diariosPorAnoMes[$ano] = array_fill(1, 12, 0);
        $diariosPorAnoMes[$ano][$mes]++;

        $emFormatada = ucfirst(strtolower(trim((string) $reg['emocao_selecionada'])));
        if (!empty($emFormatada) && $emFormatada != 'Null') {
            $totalEmocoes++;
            if (isset($emocoesGlobaisLista[$emFormatada]))
                $emocoesGlobaisLista[$emFormatada]++;
        }

        if ($data === $hoje) {
            $diarioHoje = true;
            if (!empty($emFormatada) && $emFormatada != 'Null')
                $emocaoHoje = true;
        }
    }
    sort($anosDisponiveis);
} catch (PDOException $e) {
}

// Estatísticas diárias adicionais para metas
$diariosHojeCount = isset($diariosPorData[$hoje]) ? count($diariosPorData[$hoje]) : 0;
$multiplosDiariosHoje = ($diariosHojeCount >= 2);

$postouHoje = false;
try {
    $stmtPostHoje = $pdo->prepare("SELECT COUNT(*) FROM posts_comunidade WHERE usuario_id = ? AND TO_CHAR(criado_em, 'YYYY-MM-DD') = ?");
    $stmtPostHoje->execute([$id, $hoje]);
    $postouHoje = ($stmtPostHoje->fetchColumn() > 0);
} catch (Exception $e) {}

$reagiuHoje = false;
try {
    $stmtReacaoHoje = $pdo->prepare("SELECT COUNT(*) FROM reacoes_comunidade WHERE usuario_id = ?");
    $stmtReacaoHoje->execute([$id]);
    $reagiuHoje = ($stmtReacaoHoje->fetchColumn() > 0);
} catch (Exception $e) {}

// Banco de Metas (App & Autocuidado / Bem-estar)
$poolMetas = [
    'diario_vitoria' => [
        'id' => 'diario_vitoria',
        'texto' => 'Anotar uma pequena vitória de hoje no Diário (por menor que seja).',
        'auto' => $diarioHoje
    ],
    'helpy_conversa' => [
        'id' => 'helpy_conversa',
        'texto' => "Dar um 'oi' para o Helpy e desabafar por 2 minutinhos.",
        'auto' => $usouChatHoje
    ],
    'diario_emocao' => [
        'id' => 'diario_emocao',
        'texto' => 'Registrar a emoção que estou sentindo agora no meu Diário.',
        'auto' => $emocaoHoje
    ],
    'video_respiracao' => [
        'id' => 'video_respiracao',
        'texto' => 'Tirar 1 minutinho para assistir a um vídeo de respiração na aba Adicionais.',
        'auto' => $assistiuVideoHoje
    ],
    'comunidade_post' => [
        'id' => 'comunidade_post',
        'texto' => 'Compartilhar uma mensagem positiva ou pensamento na Comunidade.',
        'auto' => $postouHoje
    ],
    'comunidade_apoio' => [
        'id' => 'comunidade_apoio',
        'texto' => 'Deixar uma reação ou palavra de apoio para alguém na Comunidade.',
        'auto' => $reagiuHoje
    ],
    'diario_gratidao' => [
        'id' => 'diario_gratidao',
        'texto' => 'Escrever no Diário pelo menos 1 motivo de gratidão pelo dia de hoje.',
        'auto' => $diarioHoje
    ],
    'autocuidado_agua' => [
        'id' => 'autocuidado_agua',
        'texto' => 'Beber um bom copo de água e fazer 3 respirações lentas e profundas.',
        'auto' => false
    ],
    'autocuidado_pausa' => [
        'id' => 'autocuidado_pausa',
        'texto' => 'Fazer uma pausa de 5 minutos longe de telas para descansar a mente.',
        'auto' => false
    ],
    'autocuidado_alongamento' => [
        'id' => 'autocuidado_alongamento',
        'texto' => 'Alongar o pescoço e os ombros para aliviar a tensão do corpo.',
        'auto' => false
    ],
    'helpy_dica' => [
        'id' => 'helpy_dica',
        'texto' => 'Pedir ao Helpy uma sugestão de reflexão ou conselho para o dia.',
        'auto' => $usouChatHoje
    ],
    'diario_duplo' => [
        'id' => 'diario_duplo',
        'texto' => 'Registrar mais de um momento do seu dia no Diário para reflexão.',
        'auto' => $multiplosDiariosHoje
    ],
    'musica_relax' => [
        'id' => 'musica_relax',
        'texto' => 'Ouvir uma música relaxante ou som suave na aba Adicionais.',
        'auto' => false
    ],
    'autocuidado_gentileza' => [
        'id' => 'autocuidado_gentileza',
        'texto' => 'Praticar a gentileza consigo mesmo(a) e reconhecer um ponto positivo seu.',
        'auto' => false
    ],
    'espaco_zen' => [
        'id' => 'espaco_zen',
        'texto' => 'Organizar um cantinho do seu espaço ao redor para clarear os pensamentos.',
        'auto' => false
    ],
    'artigo_leitura' => [
        'id' => 'artigo_leitura',
        'texto' => 'Ler um artigo ou curiosidade de bem-estar na aba Adicionais.',
        'auto' => false
    ],
];

// Sorteio determinístico diário de 4 metas aleatórias (muda automaticamente a cada novo dia)
$chavesMetas = array_keys($poolMetas);
usort($chavesMetas, function($a, $b) use ($hoje, $id) {
    return strcmp(md5($hoje . '_' . $id . '_' . $a), md5($hoje . '_' . $id . '_' . $b));
});
$chavesSorteadas = array_slice($chavesMetas, 0, 4);
$metasDoDia = [];
foreach ($chavesSorteadas as $chave) {
    $metasDoDia[] = $poolMetas[$chave];
}

$jsonDiariosData = json_encode($diariosPorData);
$jsonGraficoAno = json_encode($diariosPorAnoMes);
$maxEmocao = max(1, max($emocoesGlobaisLista));
$coresEmocoes = ['Irritado' => '#eab8b8', 'Ansioso' => '#ffcc99', 'Feliz' => '#fff1a0', 'Calmo' => '#b5ff99', 'Triste' => '#9bd3ff', 'Amoroso' => '#ff99e6'];
$abrevEmocoes = ['Irritado' => 'Irri.', 'Ansioso' => 'Ansi.', 'Feliz' => 'Feli.', 'Calmo' => 'Calm.', 'Triste' => 'Tris.', 'Amoroso' => 'Amor.'];

$totalSessoesAtivas = 1;
try {
    $stmtSessCount = $pdo->prepare("SELECT COUNT(*) FROM sessoes_usuario WHERE usuario_id = ? AND ativo = TRUE");
    $stmtSessCount->execute([$id]);
    $totalSessoesAtivas = max(1, (int)$stmtSessCount->fetchColumn());
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HelpFull - Meu Perfil</title>
    <link rel="icon" type="image/png" href="assets/logoHelpFull.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap');

        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            max-width: 100vw;
            overflow-x: hidden;
            background-color: #F3F3F3;
            font-family: 'Montserrat', sans-serif;
            color: #1a1a1a;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* MODO EDIÇÃO - FOCO E BLUR */
        body.modo-edicao::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            z-index: 1500;
            animation: fadeInBlur 0.5s ease forwards;
            pointer-events: none;
        }

        #cardSuaConta {
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body.modo-edicao {
            overflow-y: auto;
        }

        body.modo-edicao .conteudo-site {
            position: relative;
            z-index: 2100;
            padding-top: 32px !important; /* Sobe o painel para caber na tela sem rolagem */
            padding-bottom: 35px !important;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .overlay-editar {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1550;
            cursor: pointer;
        }

        body.modo-edicao .overlay-editar {
            display: block;
        }

        body.modo-edicao #cardSuaConta {
            position: relative;
            z-index: 2105;
            background: #ffffff !important;
            transform: scale(1.02);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            margin: 0 auto;
            width: 100%;
        }

        /* Oculta os outros cards para não ocuparem espaço vertical na página */
        body.modo-edicao .conteudo-site > *:not(#cardSuaConta):not(#balao2faContainer) {
            display: none !important;
        }

        /* BALÃO FLUTUANTE 2FA (DESIGN PAINEL DE CONFIGURAÇÃO) */
        #balao2faContainer {
            display: none;
            position: relative;
            z-index: 2100;
            width: 100%;
            max-width: 1000px;
            margin: 18px auto 0 auto;
            background: #ffffff;
            border-radius: 28px;
            padding: 24px 30px;
            box-shadow: 0 16px 45px rgba(0, 0, 0, 0.16);
            border: 1.5px solid rgba(43, 122, 140, 0.22);
            box-sizing: border-box;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body.modo-edicao #balao2faContainer {
            display: block;
            position: relative;
            z-index: 2104;
            animation: balao2faSurgir 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        @keyframes balao2faSurgir {
            from {
                opacity: 0;
                transform: translateY(18px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .balao-2fa-pointer {
            position: absolute;
            top: -10px;
            left: 54px;
            width: 18px;
            height: 18px;
            background: #ffffff;
            border-top: 1.5px solid rgba(43, 122, 140, 0.22);
            border-left: 1.5px solid rgba(43, 122, 140, 0.22);
            transform: rotate(45deg);
            border-top-left-radius: 4px;
        }

        .balao-2fa-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .balao-2fa-info {
            flex: 1;
            min-width: 260px;
        }

        .balao-2fa-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 5px 12px;
            border-radius: 20px;
            background: rgba(43, 122, 140, 0.1);
            color: #2b7a8c;
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .balao-2fa-titulo {
            margin: 0 0 6px 0;
            font-size: 1.2rem;
            font-weight: 800;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .balao-2fa-desc {
            margin: 0;
            font-size: 0.88rem;
            color: #64748b;
            line-height: 1.5;
            max-width: 620px;
        }

        .balao-2fa-switch-box {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-shrink: 0;
        }

        .balao-2fa-status-pill {
            font-size: 0.82rem;
            font-weight: 800;
            padding: 6px 14px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .balao-2fa-status-pill.ativo {
            background: #e6f6f9;
            color: #2b7a8c;
            border: 1px solid rgba(43, 122, 140, 0.25);
        }

        .balao-2fa-status-pill.inativo {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }

        .balao-2fa-email-aviso {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px dashed rgba(43, 122, 140, 0.2);
            font-size: 0.82rem;
            color: #64748b;
        }

        .balao-2fa-email-aviso strong {
            color: #2b7a8c;
        }

        /* Suporte ao Modo Escuro */
        body.acessibilidade-escuro #balao2faContainer {
            background: var(--tema-superficie) !important;
            border-color: var(--tema-borda) !important;
            color: var(--tema-texto) !important;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5) !important;
        }

        body.acessibilidade-escuro .balao-2fa-pointer {
            background: var(--tema-superficie) !important;
            border-color: var(--tema-borda) !important;
        }

        body.acessibilidade-escuro .balao-2fa-titulo {
            color: var(--tema-texto) !important;
        }

        body.acessibilidade-escuro .balao-2fa-desc,
        body.acessibilidade-escuro .balao-2fa-email-aviso {
            color: var(--tema-texto-secundario) !important;
            border-color: var(--tema-borda) !important;
        }

        body.acessibilidade-escuro .balao-2fa-status-pill.ativo {
            background: rgba(43, 122, 140, 0.25) !important;
            color: #7dd3fc !important;
            border-color: rgba(43, 122, 140, 0.4) !important;
        }

        body.acessibilidade-escuro .balao-2fa-status-pill.inativo {
            background: rgba(255, 255, 255, 0.06) !important;
            color: #94a3b8 !important;
            border-color: var(--tema-borda) !important;
        }

        body.acessibilidade-escuro .balao-2fa-badge {
            background: rgba(43, 122, 140, 0.25) !important;
            color: #7dd3fc !important;
        }

        body.acessibilidade-escuro.modo-edicao #cardSuaConta,
        body.acessibilidade-escuro.modo-edicao #balao2faContainer {
            background: var(--tema-superficie) !important;
        }

        /* === TOAST FEEDBACK MODERNO COM VIDRO FOSCO (GLASSMORPHISM) === */
        .toast-feedback-2fa {
            position: fixed;
            bottom: 32px;
            left: 50%;
            transform: translateX(-50%) translateY(30px) scale(0.95);
            padding: 10px 22px 10px 14px;
            border-radius: 50px;
            font-size: 0.92rem;
            font-weight: 700;
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: 0.2px;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            
            /* Vidro Fosco no Modo Claro */
            background: rgba(255, 255, 255, 0.82);
            -webkit-backdrop-filter: blur(18px) saturate(180%);
            backdrop-filter: blur(18px) saturate(180%);
            color: #133842;
            border: 1px solid rgba(43, 122, 140, 0.24);
            box-shadow: 0 16px 36px rgba(27, 61, 69, 0.16), 0 2px 8px rgba(0, 0, 0, 0.04), inset 0 1px 0 rgba(255, 255, 255, 0.9);
        }

        .toast-feedback-2fa.mostrar {
            opacity: 1;
            transform: translateX(-50%) translateY(0) scale(1);
        }

        .toast-feedback-2fa .toast-icone-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            flex-shrink: 0;
            background: rgba(43, 122, 140, 0.12);
            color: #2b7a8c;
            transition: all 0.25s ease;
        }

        .toast-feedback-2fa .toast-icone-wrap svg {
            width: 17px;
            height: 17px;
            stroke-width: 2.2;
            display: block;
        }

        .toast-feedback-2fa.toast-sucesso .toast-icone-wrap {
            background: rgba(16, 185, 129, 0.15);
            color: #059669;
        }

        .toast-feedback-2fa.toast-desativado .toast-icone-wrap {
            background: rgba(43, 122, 140, 0.12);
            color: #2b7a8c;
        }

        .toast-feedback-2fa.toast-erro .toast-icone-wrap {
            background: rgba(239, 68, 68, 0.15);
            color: #dc2626;
        }

        /* Vidro Fosco no Modo Escuro */
        body.acessibilidade-escuro .toast-feedback-2fa {
            background: rgba(22, 30, 34, 0.82) !important;
            -webkit-backdrop-filter: blur(18px) saturate(180%) !important;
            backdrop-filter: blur(18px) saturate(180%) !important;
            color: #f1f5f9 !important;
            border-color: rgba(146, 208, 222, 0.22) !important;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.58), 0 4px 12px rgba(0, 0, 0, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.12) !important;
        }

        body.acessibilidade-escuro .toast-feedback-2fa .toast-icone-wrap {
            background: rgba(43, 122, 140, 0.32) !important;
            color: #7dd3fc !important;
        }

        body.acessibilidade-escuro .toast-feedback-2fa.toast-sucesso .toast-icone-wrap {
            background: rgba(16, 185, 129, 0.26) !important;
            color: #34d399 !important;
        }

        body.acessibilidade-escuro .toast-feedback-2fa.toast-desativado .toast-icone-wrap {
            background: rgba(43, 122, 140, 0.32) !important;
            color: #92d0de !important;
        }

        body.acessibilidade-escuro .toast-feedback-2fa.toast-erro .toast-icone-wrap {
            background: rgba(239, 68, 68, 0.28) !important;
            color: #f87171 !important;
        }

        /* Ajuste na Navbar durante edição para ficar estritamente ATRÁS do painel */
        body.modo-edicao .nav-container-global {
            z-index: 1000 !important; /* Menor que o painel (2100) e que o backdrop (1500) */
            opacity: 0.35;
            pointer-events: none;
            filter: blur(5px);
            transition: opacity 0.3s ease, filter 0.3s ease;
        }

        @keyframes fadeInBlur {
            from { opacity: 0; backdrop-filter: blur(0px); }
            to { opacity: 1; backdrop-filter: blur(15px); }
        }

        .conteudo-site {
            width: 100%;
            max-width: 950px;
        }

        .conteudo-site {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 950px;
            margin: 0 auto;
            padding: 110px 20px 60px 20px;
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        /* SISTEMA DE NOTIFICAÇÃO E NAVBAR (UNIVERSAL) */
        .nav-container-global {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            gap: 15px;
            z-index: 2000;
        }

        .navbar-topo {
            display: flex;
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            padding: 12px 30px;
            border-radius: 50px;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.5);
            align-items: center;
            gap: 30px;
            position: relative;
        }

        .nav-logo {
            font-weight: 900;
            font-size: 1.05rem;
            letter-spacing: -0.5px;
            text-decoration: none;
            color: inherit;
        }

        .nav-links {
            display: flex;
            gap: 25px;
            list-style: none;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: #1a1a1a;
            font-weight: 700;
            font-size: 0.95rem;
            transition: color 0.3s;
        }

        .nav-links a.ativo {
            color: #2b7a8c;
        }

        .nav-links a:hover {
            opacity: 0.7;
        }

        .perfil-capsula {
            width: 36px;
            height: 36px;
            background: #333;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            flex-shrink: 0;
            background-size: cover;
            background-position: center;
            position: relative;
            box-shadow: 0 0 0 3px #2b7a8c;
        }

        .sininho-notificacao {
            position: absolute;
            bottom: -4px;
            right: -4px;
            width: 18px;
            height: 18px;
            background: #2b7a8c;
            border: 2px solid #fff;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .sininho-notificacao svg {
            width: 10px;
            height: 10px;
            color: white;
            fill: white;
        }

        .perfil-capsula:hover .sininho-notificacao {
            opacity: 0 !important;
            transform: scale(0.5);
            pointer-events: none;
        }

        .btn-notificacao-separado {
            position: absolute;
            right: -60px;
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            cursor: pointer;
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            padding: 0;
            color: #1a1a1a;
            opacity: 0;
            transform: scale(0) rotate(-45deg);
            pointer-events: none;
        }

        .btn-notificacao-separado.visivel {
            opacity: 1;
            transform: scale(1) rotate(0deg);
            pointer-events: auto;
        }

        .btn-notificacao-separado:hover {
            transform: scale(1.1);
            background: rgba(255, 255, 255, 0.6);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .btn-notificacao-separado svg {
            width: 20px;
            height: 20px;
            fill: #1a1a1a;
        }

        .painel-notificacoes {
            position: absolute;
            top: 65px;
            right: -60px;
            width: 320px;
            background: rgba(200, 200, 200, 0.85);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border-radius: 25px;
            padding: 20px;
            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.4);
            display: flex;
            flex-direction: column;
            gap: 15px;
            z-index: 2100;
            transform-origin: 90% 0%;
            transform: translateX(40px) scale(0.8);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .painel-notificacoes.aberto {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: translateX(0) scale(1);
        }

        .painel-header-top {
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .painel-header-top h3 {
            font-size: 1.1rem;
            font-weight: 800;
            color: #1a1a1a;
            margin: 0;
        }

        .btn-fechar-painel {
            position: absolute;
            right: 0;
            background: transparent;
            border: none;
            font-size: 1.5rem;
            line-height: 1;
            color: #555;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-fechar-painel:hover {
            color: #ff4d4d;
            transform: scale(1.1);
        }

        .painel-actions {
            display: flex;
            justify-content: flex-end;
            margin-bottom: -5px;
        }

        .btn-limpar-pill {
            background: #fff;
            color: #333;
            font-weight: 800;
            font-size: 0.75rem;
            padding: 4px 15px;
            border-radius: 15px;
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: 0.2s;
        }

        .btn-limpar-pill:hover {
            transform: scale(1.05);
            background: #ffe6e6;
            color: #cc0000;
        }

        .lista-notificacoes {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .item-notificacao {
            background: rgba(255, 255, 255, 0.6);
            border-radius: 15px;
            padding: 15px 15px 15px 25px;
            position: relative;
            display: flex;
            align-items: center;
            gap: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .item-notificacao:hover {
            background: rgba(255, 255, 255, 0.8);
            transform: translateX(-5px);
        }

        .item-notificacao .barra-intensidade {
            position: absolute;
            left: 10px;
            top: 15px;
            bottom: 15px;
            width: 5px;
            border-radius: 5px;
        }

        .item-notificacao.alta .barra-intensidade {
            background: #eab8b8;
        }

        .item-notificacao.media .barra-intensidade {
            background: #fff1a0;
        }

        .item-notificacao.baixa .barra-intensidade {
            background: #b5ff99;
        }

        .notif-content {
            flex: 1;
        }

        .notif-content strong {
            display: block;
            font-size: 0.85rem;
            color: #1a1a1a;
            margin-bottom: 2px;
        }

        .notif-content p {
            margin: 0;
            font-size: 0.75rem;
            color: #444;
            line-height: 1.3;
        }

        .toast-notificacao {
            position: fixed;
            top: 90px;
            left: 50%;
            transform: translateX(-50%) translateY(-20px);
            background: rgba(220, 220, 220, 0.85);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            border-radius: 20px;
            padding: 20px 20px 20px 30px;
            width: 380px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .toast-notificacao.mostrar {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
            pointer-events: auto;
        }

        .toast-barra {
            position: absolute;
            left: 12px;
            top: 20px;
            bottom: 20px;
            width: 5px;
            border-radius: 5px;
        }

        .toast-notificacao.alta .toast-barra {
            background: #eab8b8;
        }

        .toast-notificacao.media .toast-barra {
            background: #fff1a0;
        }

        .toast-notificacao.baixa .toast-barra {
            background: #b5ff99;
        }

        .toast-conteudo p {
            margin: 0;
            font-size: 0.95rem;
            color: #333;
            font-weight: 600;
            line-height: 1.4;
        }

        .toast-btn-container {
            display: flex;
            justify-content: flex-end;
            width: 100%;
        }

        .toast-btn {
            background: #ffffff;
            color: #1a1a1a;
            padding: 8px 20px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 800;
            transition: 0.2s;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .toast-btn:hover {
            transform: scale(1.05);
        }

        /* ESPECÍFICOS PERFIL */
        .card-perfil {
            background: #eaeaea;
            border-radius: 30px;
            padding: 35px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.03);
            width: 100%;
        }

        .titulo-secao {
            font-size: 1.6rem;
            font-weight: 900;
            color: #333;
            margin-bottom: 25px;
            letter-spacing: -0.5px;
        }

        .conta-grid {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 40px;
        }

        .conta-form {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 100%;
            box-sizing: border-box;
        }

        .conta-form label {
            font-size: 0.9rem;
            font-weight: 800;
            color: #444;
            margin-top: 5px;
        }

        .conta-input {
            background: #d6d6d6;
            border: none;
            padding: 12px 20px;
            border-radius: 20px;
            font-size: 1rem;
            font-weight: 600;
            color: #333;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            outline: none;
        }

        .senha-row {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .senha-campo-container {
            position: relative;
            display: inline-flex;
            align-items: center;
            max-width: 180px;
            width: 100%;
        }

        .senha-campo-container .conta-input {
            width: 100%;
            max-width: 100%;
            padding-right: 15px;
            transition: all 0.25s ease;
        }

        .btn-lapis-senha {
            display: none;
            position: absolute;
            right: 8px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.18);
            cursor: pointer;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
            padding: 0;
            z-index: 5;
        }

        .btn-lapis-senha svg {
            width: 16px;
            height: 16px;
            color: #444;
            display: block;
        }

        .btn-lapis-senha:hover {
            transform: scale(1.12);
            background: #f0fdf4;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        .btn-lapis-senha:active {
            transform: scale(0.95);
        }

        body.modo-edicao .btn-lapis-senha {
            display: flex;
        }

        body.modo-edicao .senha-campo-container {
            cursor: pointer;
        }

        body.modo-edicao .senha-campo-container .conta-input {
            padding-right: 46px;
            cursor: pointer;
        }

        body.modo-edicao .senha-campo-container:hover .conta-input {
            border-color: #7dd3fc;
            box-shadow: 0 0 0 2px rgba(125, 211, 252, 0.25);
        }

        body.acessibilidade-escuro .btn-lapis-senha {
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
        }

        body.acessibilidade-escuro .btn-lapis-senha svg {
            color: #222;
        }

        body.acessibilidade-escuro .btn-lapis-senha:hover {
            background: #e2f1f5;
        }

        .btn-apagar,
        .btn-editar {
            border: none;
            padding: 12px 20px;
            border-radius: 20px;
            font-size: 0.95rem;
            font-weight: 800;
            color: #444;
            cursor: pointer;
            transition: opacity 0.3s;
        }

        .btn-apagar:hover,
        .btn-editar:hover {
            opacity: 0.8;
        }

        .btn-apagar {
            background: #eab8b8;
        }

        .btn-editar {
            background: #bce0e6;
        }

        .btn-salvar {
            display: none;
            background: #9bd3ff;
        }

        .btn-sair {
            background: #d6d6d6;
            color: #444;
        }

        body.acessibilidade-escuro .btn-editar {
            background: #1c3642 !important;
            color: #7dd3fc !important;
            border: 1px solid rgba(125, 211, 252, 0.25) !important;
        }

        body.acessibilidade-escuro .btn-editar:hover {
            background: #254454 !important;
        }

        body.acessibilidade-escuro .btn-sair {
            background: #252d32 !important;
            color: #cbd5e1 !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        body.acessibilidade-escuro .btn-sair:hover {
            background: #2f383e !important;
            color: #f1f5f9 !important;
        }

        body.acessibilidade-escuro .btn-salvar {
            background: #2b7a8c !important;
            color: #ffffff !important;
            border: 1px solid rgba(125, 211, 252, 0.4) !important;
        }

        body.acessibilidade-escuro .btn-salvar:hover {
            background: #226473 !important;
        }

        body.acessibilidade-escuro .btn-apagar {
            background: rgba(239, 68, 68, 0.2) !important;
            color: #fca5a5 !important;
            border: 1px solid rgba(239, 68, 68, 0.35) !important;
        }

        body.acessibilidade-escuro .btn-apagar:hover {
            background: rgba(239, 68, 68, 0.3) !important;
        }

        .conta-imagem-placeholder {
            width: 200px;
            height: 200px;
            min-width: 200px;
            background-color: #d6d6d6;
            background-size: cover;
            background-position: center;
            border-radius: 35px;
            flex-shrink: 0;
            position: relative;
            border: 4px solid rgba(255, 255, 255, 0.5);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: inset 0 0 15px rgba(0, 0, 0, 0.05);
        }

        .conta-imagem-placeholder.tem-foto {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: #fff;
        }

        .placeholder-icon {
            opacity: 0.6;
            transition: opacity 0.3s;
        }

        .conta-imagem-placeholder:hover .placeholder-icon {
            opacity: 0.9;
        }

        .stats-container {
            display: flex;
            justify-content: space-between;
            padding: 25px 15px;
        }

        .stat-item {
            flex: 1;
            text-align: center;
            border-right: 2px solid #d6d6d6;
            padding: 0 15px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .stat-item:last-child {
            border-right: none;
        }

        .stat-label {
            font-size: 0.8rem;
            font-weight: 800;
            color: #555;
            line-height: 1.3;
            margin-bottom: 10px;
        }

        .stat-valor {
            font-size: 2.2rem;
            font-weight: 900;
            color: #222;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 18px;
            cursor: pointer;
            user-select: none;
            transition: transform 0.15s ease, opacity 0.2s ease;
        }

        .meta-item:hover {
            transform: translateX(3px);
        }

        .meta-item:last-child {
            margin-bottom: 0;
        }

        .meta-checkbox {
            width: 26px;
            height: 26px;
            border: 2px solid #888;
            border-radius: 8px;
            background: #d6d6d6;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.2s ease;
        }

        .meta-item:hover .meta-checkbox {
            border-color: #2b7a8c;
        }

        .check-icon {
            display: none;
        }

        .meta-item.concluida .check-icon {
            display: block;
            width: 30px;
            height: 30px;
            stroke: #66cc33;
            stroke-width: 4;
            stroke-linecap: round;
            stroke-linejoin: round;
            fill: none;
        }

        .meta-texto {
            font-weight: 800;
            color: #444;
            font-size: 0.95rem;
            transition: color 0.2s ease, text-decoration 0.2s ease;
        }

        .meta-item.concluida .meta-texto {
            text-decoration: line-through;
            text-decoration-thickness: 2px;
            color: #666;
        }

        .grid-cal-detalhes {
            display: grid;
            grid-template-columns: 0.9fr 1.1fr;
            gap: 25px;
            width: 100%;
        }

        .titulo-central {
            text-align: center;
            font-size: 1.4rem;
            font-weight: 900;
            color: #333;
            margin-bottom: 25px;
            letter-spacing: -0.5px;
        }

        .cal-header-bloco {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 30px;
            margin-bottom: 20px;
        }

        .cal-header-textos {
            display: flex;
            flex-direction: column;
            align-items: center;
            line-height: 1.1;
        }

        .cal-header-mes {
            font-weight: 900;
            font-size: 1.2rem;
            color: #222;
        }

        .cal-header-ano {
            font-weight: 800;
            font-size: 0.9rem;
            color: #666;
        }

        .cal-seta {
            font-size: 0.8rem;
            cursor: pointer;
            color: #888;
            padding: 5px 15px;
            border-radius: 15px;
            background: #dcdcdc;
            transition: 0.2s;
            border: none;
            font-weight: 900;
        }

        .cal-seta:hover {
            background: #bce0e6;
            color: #1a1a1a;
        }

        .cal-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            text-align: center;
        }

        .cal-dia-semana {
            font-weight: 900;
            color: #1a1a1a;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }

        .cal-dia {
            padding: 8px 0;
            background: #f0f0f0;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.95rem;
            color: #222;
            position: relative;
            cursor: pointer;
            transition: 0.2s;
        }

        .cal-dia:hover {
            background: #e0e0e0;
        }

        .cal-dia.vazio {
            background: transparent;
            cursor: default;
        }

        .cal-dia.ativo {
            background: #c6eef2;
        }

        .cal-dia.tem-registro::after {
            content: '';
            display: block;
            width: 16px;
            height: 3px;
            background: #2b7a8c;
            border-radius: 2px;
            position: absolute;
            bottom: 4px;
            left: 50%;
            transform: translateX(-50%);
        }

        .detalhes-scroll {
            max-height: 400px;
            overflow-y: auto;
            padding: 20px 10px;
            /* Custom Scrollbar */
            scrollbar-width: thin;
            scrollbar-color: rgba(0, 0, 0, 0.2) transparent;
        }

        .detalhes-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .detalhes-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .detalhes-scroll::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        .detalhes-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 0, 0, 0.2);
        }

        /* Efeito de Desfoque (Fade) */
        .detalhes-wrapper {
            position: relative;
            margin-top: 10px;
            background: #eaeaea;
            border-radius: 20px;
            overflow: hidden;
        }

        .detalhes-wrapper::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; height: 40px;
            background: linear-gradient(to bottom, #eaeaea 0%, transparent 100%);
            z-index: 2; pointer-events: none;
        }

        .detalhes-wrapper::after {
            content: "";
            position: absolute;
            bottom: 0; left: 0; right: 0; height: 40px;
            background: linear-gradient(to top, #eaeaea 0%, transparent 100%);
            z-index: 2; pointer-events: none;
        }

        .detalhe-item {
            background: #e0e0e0;
            border-radius: 20px;
            padding: 20px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 15px;
            position: relative;
            opacity: 0;
        }

        .detalhe-cor {
            width: 12px;
            height: 40px;
            border-radius: 6px;
            flex-shrink: 0;
        }

        .detalhe-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
            flex: 1;
        }

        .detalhe-nome {
            font-weight: 800;
            color: #333;
            font-size: 1.05rem;
        }

        .detalhe-pontos {
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .detalhe-hora {
            font-size: 0.8rem;
            font-weight: 800;
            color: #888;
            margin-top: 2px;
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.9);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .cal-dia.animar {
            animation: fadeInScale 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        .detalhe-item.animar {
            animation: slideInRight 0.5s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        .graficos-container {
            background-color: #F3F3F3;
            border-radius: 35px;
            padding: 30px;
            position: relative;
            margin-top: 10px;
            overflow: hidden;
        }

        .graficos-container > *:not(.fundo-animado-canvas) {
            position: relative;
            z-index: 2;
        }

        .fundo-animado-canvas.fundo-animado-bloco {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 35px;
            z-index: 0;
            pointer-events: none;
            display: block;
            filter: blur(28px);
            -webkit-filter: blur(28px);
            transform: scale(1.08);
            transform-origin: center center;
        }

        .graficos-badge {
            background: #eaeaea;
            color: #1a1a1a;
            font-weight: 900;
            font-size: 1.3rem;
            padding: 12px 25px;
            border-radius: 30px;
            display: inline-block;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .grid-duplo {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            width: 100%;
        }

        .grafico-card {
            background: rgba(234, 234, 234, 0.85);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 25px;
            padding: 25px 20px;
            border: 1px solid rgba(255, 255, 255, 0.7);
            height: 250px;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .grafico-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: auto;
        }

        .grafico-titulo {
            font-weight: 800;
            color: #333;
            font-size: 1.15rem;
        }

        .seletor-ano-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .grafico-wrapper {
            display: flex;
            align-items: stretch;
            gap: 12px;
            height: 160px;
            margin-top: 20px;
        }

        .grafico-y-axis {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 140px;
            padding-bottom: 25px;
            font-size: 0.75rem;
            font-weight: 800;
            color: #888;
            text-align: right;
            min-width: 25px;
        }

        .grafico-barras {
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            height: 100%;
            flex: 1;
            gap: 5px;
        }

        .barra-coluna {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            height: 100%;
            flex: 1;
        }

        .barra {
            width: 100%;
            max-width: 32px;
            border-radius: 6px;
            margin-bottom: 12px;
            transition: height 0.6s ease-out;
        }

        .barra-label {
            font-size: 0.65rem;
            font-weight: 800;
            color: #333;
        }

        /* Card e Botão de Relatório em PDF */
        .relatorio-acao-container {
            margin-top: 25px;
            margin-bottom: 25px;
            background: rgba(234, 234, 234, 0.85);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 35px;
            padding: 24px 32px;
            border: 1px solid rgba(255, 255, 255, 0.7);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .relatorio-bloco-info {
            display: flex;
            align-items: center;
            gap: 18px;
            flex: 1;
            background: transparent !important;
            box-shadow: none !important;
            border: none !important;
        }

        .relatorio-icone-circ {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: #2b7a8c;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 4px 14px rgba(43, 122, 140, 0.25);
        }

        .relatorio-textos {
            display: flex;
            flex-direction: column;
            gap: 4px;
            background: transparent !important;
            box-shadow: none !important;
            border: none !important;
        }

        .relatorio-titulo-texto {
            font-size: 1.12rem;
            font-weight: 800;
            color: #222;
            background: transparent !important;
            box-shadow: none !important;
            border: none !important;
        }

        .relatorio-subtitulo-texto {
            font-size: 0.86rem;
            color: #555;
            line-height: 1.45;
            font-weight: 500;
            background: transparent !important;
            box-shadow: none !important;
            border: none !important;
        }

        .btn-gerar-relatorio {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #2b7a8c;
            color: #ffffff !important;
            font-weight: 800;
            font-size: 0.95rem;
            padding: 14px 26px;
            border-radius: 30px;
            text-decoration: none;
            white-space: nowrap;
            box-shadow: 0 4px 14px rgba(43, 122, 140, 0.3);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            flex-shrink: 0;
        }

        .btn-gerar-relatorio span {
            color: #ffffff !important;
            background: transparent !important;
            box-shadow: none !important;
            border: none !important;
        }

        .btn-gerar-relatorio svg {
            stroke: #ffffff !important;
            color: #ffffff !important;
            fill: none !important;
        }

        .btn-gerar-relatorio:hover {
            transform: translateY(-2px);
            background: #236877;
            box-shadow: 0 6px 18px rgba(43, 122, 140, 0.4);
        }

        @media (max-width: 768px) {
            .relatorio-acao-container {
                flex-direction: column;
                align-items: flex-start;
                padding: 20px;
                gap: 16px;
            }

            .btn-gerar-relatorio {
                width: 100%;
                justify-content: center;
            }
        }

        .overlay-editar {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            z-index: 1500;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: all 0.4s ease;
        }

        body.modo-edicao .overlay-editar {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        body.modo-edicao {
            overflow-y: auto !important;
        }

        body.modo-edicao .navbar-topo {
            z-index: 2110;
            position: relative;
        }

        body.modo-edicao .conteudo-site {
            z-index: 2100;
            position: relative;
        }

        body.modo-edicao #cardSuaConta {
            z-index: 2105;
            position: relative;
            transform: scale(1.02);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        #cardSuaConta {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .acoes-imagem {
            position: absolute;
            bottom: 15px;
            right: 15px;
            display: none;
            gap: 10px;
        }

        .acao-imagem-btn {
            background: #fff;
            border-radius: 50%;
            padding: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .acao-imagem-btn svg {
            width: 18px;
            height: 18px;
            color: #555;
            display: block;
        }

        body.modo-edicao .acoes-imagem {
            display: flex;
        }

        .secao-branca {
            background-color: #ffffff;
            width: 100%;
            position: relative;
            z-index: 10;
            margin-top: auto;
            border-top: 1px solid #e0e0e0;
        }

        .secao-branca-conteudo {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .rodape-simples {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 20px;
            padding: 0 10px;
        }

        .rodape-simples strong {
            font-size: 1.2rem;
            letter-spacing: -1px;
            font-weight: 900;
        }

        .contato-info {
            line-height: 1.6;
            font-size: 0.85rem;
            text-align: left;
            font-weight: 600;
            color: #222;
        }

        /* === MOBILE DROPDOWN MENU === */
        .nav-dropdown-mobile {
            display: none;
            position: absolute;
            top: calc(100% + 10px);
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-radius: 25px;
            padding: 15px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.5);
            flex-direction: column;
            gap: 5px;
            z-index: 2100;
            animation: fadeInDropdown 0.3s ease;
        }
        .nav-dropdown-mobile.aberto { display: flex; }
        .nav-dropdown-mobile a {
            text-decoration: none;
            color: #1a1a1a;
            font-weight: 700;
            font-size: 0.95rem;
            padding: 12px 20px;
            border-radius: 15px;
            transition: background 0.2s;
        }
        .nav-dropdown-mobile a:hover { background: rgba(0,0,0,0.05); }
        .nav-dropdown-mobile a.ativo { color: #2b7a8c; background: rgba(43,122,140,0.08); }
        @keyframes fadeInDropdown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (min-width: 769px) {
            .nav-dropdown-mobile { display: none !important; }
        }

        @media (max-width: 768px) {
            .nav-container-global { width: 100%; }
            .nav-links {
                display: none;
            }

            .navbar-topo {
                padding: 12px 25px;
                width: calc(100% - 40px);
                margin: 0 auto;
                justify-content: space-between;
                overflow: visible !important;
            }
            .nav-logo { cursor: pointer; }

            .conteudo-site {
                padding-top: 145px;
            }

            .card-perfil {
                padding: 24px 16px;
                border-radius: 26px;
                box-sizing: border-box;
                width: 100%;
            }

            .conta-grid {
                flex-direction: column-reverse;
                gap: 20px;
                width: 100%;
                box-sizing: border-box;
            }

            .conta-form {
                width: 100%;
                box-sizing: border-box;
            }

            .senha-row {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                align-items: center;
                width: 100%;
                box-sizing: border-box;
            }

            .senha-row .senha-campo-container {
                flex: 1 1 100%;
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
            }

            .senha-row .senha-campo-container .conta-input {
                flex: 1 1 100%;
                width: 100%;
                max-width: 100%;
                height: 44px;
                padding: 0 16px;
                box-sizing: border-box;
            }

            .senha-row .btn-editar,
            .senha-row .btn-sair,
            .senha-row .btn-salvar,
            .senha-row .btn-apagar {
                height: 42px;
                padding: 0 14px;
                white-space: nowrap;
                font-size: 0.85rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 18px;
                flex: 1 1 auto;
                min-width: 0;
                box-sizing: border-box;
            }

            .senha-row .btn-salvar,
            .senha-row .btn-apagar {
                display: none;
            }

            /* Ajustes Mobile para Modo Edição e Painel 2FA */
            body.modo-edicao .conteudo-site {
                padding-top: 85px !important;
                padding-bottom: 60px !important;
                padding-left: 14px !important;
                padding-right: 14px !important;
                gap: 14px !important;
            }

            body.modo-edicao #cardSuaConta {
                transform: none !important;
                padding: 22px 18px !important;
                border-radius: 24px !important;
                box-sizing: border-box !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            body.modo-edicao .senha-row {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 10px !important;
                margin-top: 8px !important;
            }

            body.modo-edicao .senha-row .senha-campo-container {
                grid-column: span 2 !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            body.modo-edicao .senha-row .conta-input {
                width: 100% !important;
            }

            body.modo-edicao .senha-row .btn-sair {
                display: none !important;
            }

            body.modo-edicao .senha-row #btnEditar {
                grid-column: 1 !important;
                width: 100% !important;
                height: 44px !important;
                border-radius: 16px !important;
                font-size: 0.88rem !important;
                font-weight: 700 !important;
            }

            body.modo-edicao .senha-row #btnSalvar {
                grid-column: 2 !important;
                display: inline-flex !important;
                width: 100% !important;
                height: 44px !important;
                border-radius: 16px !important;
                font-size: 0.88rem !important;
                font-weight: 700 !important;
            }

            body.modo-edicao .senha-row #btnApagar {
                grid-column: span 2 !important;
                display: inline-flex !important;
                width: 100% !important;
                height: 42px !important;
                border-radius: 16px !important;
                font-size: 0.84rem !important;
                font-weight: 600 !important;
                margin-top: 4px !important;
            }

            /* Balão e Card 2FA Responsivo no Mobile */
            #balao2faContainer {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 auto !important;
                padding: 20px 18px !important;
                border-radius: 22px !important;
                box-sizing: border-box !important;
            }

            .balao-2fa-pointer {
                display: none !important;
            }

            .balao-2fa-header {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 12px !important;
            }

            .balao-2fa-info {
                min-width: 0 !important;
                width: 100% !important;
            }

            .balao-2fa-titulo {
                font-size: 1.1rem !important;
                margin: 0 0 6px 0 !important;
            }

            .balao-2fa-desc {
                font-size: 0.84rem !important;
                line-height: 1.45 !important;
                max-width: 100% !important;
            }

            .balao-2fa-switch-box {
                width: 100% !important;
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                padding: 10px 14px !important;
                background: rgba(43, 122, 140, 0.08) !important;
                border-radius: 14px !important;
                box-sizing: border-box !important;
                margin-top: 4px !important;
            }

            body.acessibilidade-escuro .balao-2fa-switch-box {
                background: rgba(255, 255, 255, 0.06) !important;
            }

            .balao-2fa-email-aviso {
                font-size: 0.78rem !important;
                padding-top: 10px !important;
                margin-top: 12px !important;
                word-break: break-all !important;
                line-height: 1.4 !important;
            }

            /* Toast Feedback 2FA no Mobile */
            .toast-feedback-2fa {
                bottom: 24px !important;
                left: 50% !important;
                width: calc(100% - 32px) !important;
                max-width: 380px !important;
                border-radius: 18px !important;
                padding: 12px 16px !important;
                font-size: 0.85rem !important;
                box-sizing: border-box !important;
            }

            .grid-duplo {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .grid-cal-detalhes {
                display: flex !important;
                flex-direction: column !important;
                gap: 20px !important;
                width: 100% !important;
            }

            .grid-cal-detalhes > .card-perfil {
                width: 100% !important;
                box-sizing: border-box !important;
            }

            .detalhes-scroll {
                max-height: 320px;
                padding: 10px 0;
            }

            .stats-container {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
                padding: 15px 5px;
            }

            .stats-container .stat-item:last-child {
                grid-column: span 2;
            }

            .stat-item {
                border-right: none;
                background: rgba(0, 0, 0, 0.04);
                padding: 15px 10px;
                border-radius: 20px;
            }

            .stat-valor {
                font-size: 1.8rem;
            }

            .conta-imagem-placeholder {
                width: 140px;
                height: 140px;
                min-width: 140px;
                margin: 5px auto 10px auto;
            }

            .graficos-container {
                padding: 16px 12px;
                border-radius: 24px;
                box-sizing: border-box;
                width: 100%;
            }

            .graficos-badge {
                font-size: 1rem;
                padding: 6px 16px;
                margin-bottom: 12px;
            }

            .grafico-card {
                padding: 14px 12px;
                min-height: unset;
                height: auto;
                border-radius: 18px;
                box-sizing: border-box;
                width: 100%;
            }

            .grafico-titulo {
                font-size: 1rem;
            }

            .grafico-wrapper {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                gap: 6px;
                height: 110px;
                margin-top: 10px;
                padding-bottom: 2px;
            }

            .grafico-y-axis {
                min-width: 14px;
                font-size: 0.65rem;
                height: 90px;
                padding-bottom: 16px;
                justify-content: space-between;
            }

            .grafico-barras {
                min-width: 260px;
                gap: 2px;
                height: 100%;
            }

            .barra {
                max-width: 14px;
                margin-bottom: 4px;
                border-radius: 4px;
            }

            .barra-label {
                font-size: 0.58rem;
                font-weight: 800;
                line-height: 1;
            }

            .rodape-simples {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .btn-notificacao-separado { right: 10px; }
            .painel-notificacoes { right: 10px; width: calc(100vw - 40px); max-width: 320px; }
            .toast-notificacao { width: 92%; max-width: 380px; }
        }

        /* ===== ESTILOS DO MODAL DE ALTERAÇÃO DE SENHA ===== */
        .btn-trocar-senha {
            background: #cae8f2;
            color: #174a58;
            border: 1px solid rgba(43, 122, 140, 0.22);
            font-weight: 700;
        }

        .btn-trocar-senha:hover {
            background: #b5e0ee;
        }

        body.acessibilidade-escuro .btn-trocar-senha {
            background: #1c3642;
            color: #7dd3fc;
            border-color: rgba(125, 211, 252, 0.25);
        }

        body.acessibilidade-escuro .btn-trocar-senha:hover {
            background: #254454;
        }

        /* TRAVAMENTO DO FUNDO QUANDO MODAL ESTIVER ABERTO */
        html.modal-aberto-travar,
        body.modal-aberto-travar {
            overflow: hidden !important;
            overscroll-behavior: none !important;
            touch-action: none !important;
        }

        body.modal-aberto-travar {
            position: fixed !important;
            width: 100% !important;
            left: 0 !important;
            right: 0 !important;
        }

        .modal-alterar-senha-overlay {
            position: fixed;
            inset: 0;
            z-index: 99999;
            background: rgba(8, 14, 18, 0.65);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.28s ease;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            touch-action: pan-y;
        }

        .modal-alterar-senha-overlay.aberto {
            display: flex;
            opacity: 1;
        }

        .modal-alterar-senha-card {
            background: #ffffff;
            width: 100%;
            max-width: 480px;
            max-height: min(88vh, 88dvh);
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            touch-action: pan-y;
            border-radius: 32px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.22);
            border: 1px solid rgba(43, 122, 140, 0.15);
            padding: 34px 32px 30px 32px;
            position: relative;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif !important;
            transform: scale(0.92);
            transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
            scrollbar-width: thin;
            scrollbar-color: rgba(43, 122, 140, 0.35) transparent;
        }

        .modal-alterar-senha-card::-webkit-scrollbar {
            width: 6px;
        }

        .modal-alterar-senha-card::-webkit-scrollbar-thumb {
            background: rgba(43, 122, 140, 0.3);
            border-radius: 10px;
        }

        .modal-alterar-senha-overlay.aberto .modal-alterar-senha-card {
            transform: scale(1);
        }

        body.acessibilidade-escuro .modal-alterar-senha-card {
            background: var(--tema-superficie, #1e2428) !important;
            border: 1px solid var(--tema-borda, #2f383e) !important;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.75) !important;
            color: var(--tema-texto, #f1f5f9) !important;
            scrollbar-color: rgba(125, 211, 252, 0.3) transparent;
        }

        /* BOTÃO DE FECHAR SEM ANIMAÇÃO */
        .modal-fechar-btn {
            position: absolute;
            top: 22px;
            right: 22px;
            width: 36px;
            height: 36px;
            background: rgba(0, 0, 0, 0.05);
            border: none;
            color: #555;
            cursor: pointer;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: none !important;
            transform: none !important;
            animation: none !important;
        }

        .modal-fechar-btn:hover,
        .modal-fechar-btn:active,
        .modal-fechar-btn:focus {
            background: rgba(0, 0, 0, 0.1);
            color: #111;
            transform: none !important;
            transition: none !important;
            animation: none !important;
        }

        body.acessibilidade-escuro .modal-fechar-btn {
            background: rgba(255, 255, 255, 0.08);
            color: #cbd5e1;
            transition: none !important;
            transform: none !important;
            animation: none !important;
        }

        body.acessibilidade-escuro .modal-fechar-btn:hover,
        body.acessibilidade-escuro .modal-fechar-btn:active,
        body.acessibilidade-escuro .modal-fechar-btn:focus {
            background: rgba(255, 255, 255, 0.16);
            color: #ffffff;
            transform: none !important;
            transition: none !important;
            animation: none !important;
        }

        @media (max-width: 600px) {
            .modal-alterar-senha-overlay {
                padding: 12px 10px;
                align-items: center;
            }

            .modal-alterar-senha-card {
                max-height: calc(100dvh - 24px);
                padding: 26px 18px 22px 18px;
                border-radius: 24px;
            }

            .modal-fechar-btn {
                top: 16px;
                right: 16px;
            }

            .modal-senha-header {
                padding-right: 32px;
                margin-bottom: 16px;
            }
        }

        .modal-senha-header {
            margin-bottom: 22px;
            padding-right: 36px;
        }

        .modal-senha-header-topo {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .modal-senha-titulo {
            font-size: 1.5rem !important;
            font-weight: 900 !important;
            color: #333 !important;
            margin: 0 0 6px 0 !important;
            letter-spacing: -0.5px !important;
        }

        body.acessibilidade-escuro .modal-senha-titulo {
            color: var(--tema-texto, #ffffff) !important;
        }

        .modal-senha-subtitulo {
            margin: 0;
            font-size: 0.88rem;
            color: #64748b;
            line-height: 1.45;
            font-weight: 500;
        }

        body.acessibilidade-escuro .modal-senha-subtitulo {
            color: var(--tema-texto-secundario, #94a3b8) !important;
        }

        .modal-alerta-info {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background: rgba(43, 122, 140, 0.08);
            border: 1px solid rgba(43, 122, 140, 0.2);
            border-radius: 20px;
            padding: 14px 16px;
            font-size: 0.86rem;
            color: #204953;
            line-height: 1.45;
            margin-bottom: 18px;
        }

        .modal-alerta-info svg {
            flex-shrink: 0;
            margin-top: 2px;
            stroke: #204953;
        }

        body.acessibilidade-escuro .modal-alerta-info {
            background: rgba(125, 211, 252, 0.08) !important;
            border-color: rgba(125, 211, 252, 0.2) !important;
            color: #7dd3fc !important;
        }

        body.acessibilidade-escuro .modal-alerta-info svg {
            stroke: #7dd3fc !important;
        }

        .campo-grupo-modal {
            margin-bottom: 18px;
        }

        .modal-label {
            display: block;
            font-size: 0.88rem;
            font-weight: 800;
            color: #444;
            margin-bottom: 7px;
            margin-left: 2px;
        }

        body.acessibilidade-escuro .modal-label {
            color: var(--tema-texto, #f1f5f9) !important;
        }

        .btn-reenviar-codigo-modal {
            background: none;
            border: none;
            color: #204953;
            font-size: 0.82rem;
            font-weight: 800;
            font-family: 'Montserrat', sans-serif !important;
            cursor: pointer;
            padding: 0;
            text-decoration: underline;
            transition: color 0.2s;
        }

        .btn-reenviar-codigo-modal:hover:not(:disabled) {
            color: #2b7a8c;
        }

        .btn-reenviar-codigo-modal:disabled {
            opacity: 0.55;
            cursor: not-allowed;
            text-decoration: none;
        }

        body.acessibilidade-escuro .btn-reenviar-codigo-modal {
            color: #7dd3fc !important;
        }

        .input-com-icone-modal {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }

        .modal-input {
            width: 100%;
            height: 48px;
            border-radius: 20px;
            border: 1.5px solid transparent;
            background: #e6ebed;
            padding: 0 46px 0 18px;
            font-size: 0.95rem;
            font-weight: 600;
            font-family: 'Montserrat', sans-serif !important;
            color: #333;
            box-sizing: border-box;
            outline: none;
            transition: all 0.25s ease;
        }

        .modal-input:focus {
            background: #ffffff;
            border-color: #8ed6e4;
            box-shadow: 0 0 0 3px rgba(142, 214, 228, 0.35);
        }

        body.acessibilidade-escuro .modal-input {
            background: #252d32 !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            color: #f1f5f9 !important;
        }

        body.acessibilidade-escuro .modal-input:focus {
            background: #182228 !important;
            border-color: #7dd3fc !important;
            box-shadow: 0 0 0 3px rgba(125, 211, 252, 0.25) !important;
        }

        .input-codigo-destaque {
            letter-spacing: 6px;
            font-size: 1.3rem;
            font-weight: 800;
            text-align: center;
            font-family: 'Montserrat', monospace !important;
            padding-right: 18px !important;
        }

        .modal-hint {
            display: block;
            font-size: 0.78rem;
            color: #64748b;
            margin-top: 5px;
            margin-left: 4px;
        }

        body.acessibilidade-escuro .modal-hint {
            color: var(--tema-texto-secundario, #94a3b8) !important;
        }

        .btn-olho-toggle {
            position: absolute;
            right: 12px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: transparent;
            border: none;
            color: #64748b;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s, background 0.2s;
        }

        .btn-olho-toggle:hover {
            color: #204953;
            background: rgba(0, 0, 0, 0.04);
        }

        body.acessibilidade-escuro .btn-olho-toggle {
            color: #94a3b8;
        }

        body.acessibilidade-escuro .btn-olho-toggle:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.08);
        }

        .modal-msg-alerta {
            border-radius: 16px;
            padding: 12px 16px;
            font-size: 0.88rem;
            font-weight: 600;
            margin: 14px 0;
            line-height: 1.4;
            display: none;
        }

        .modal-msg-alerta.erro {
            display: block;
            background: #ffe6e6;
            border: 1px solid #ffb3b3;
            color: #cc0000;
        }

        body.acessibilidade-escuro .modal-msg-alerta.erro {
            background: rgba(239, 68, 68, 0.15) !important;
            border-color: rgba(248, 113, 113, 0.3) !important;
            color: #fca5a5 !important;
        }

        .modal-msg-alerta.sucesso {
            display: block;
            background: #e6f6f9;
            border: 1px solid rgba(43, 122, 140, 0.25);
            color: #2b7a8c;
        }

        body.acessibilidade-escuro .modal-msg-alerta.sucesso {
            background: rgba(34, 197, 94, 0.15) !important;
            border-color: rgba(74, 222, 128, 0.3) !important;
            color: #86efac !important;
        }

        .modal-senha-acoes {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .modal-btn {
            height: 48px;
            border-radius: 20px;
            font-size: 0.95rem;
            font-weight: 800;
            font-family: 'Montserrat', sans-serif !important;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            flex: 1;
            transition: all 0.25s ease;
            box-sizing: border-box;
            border: none;
        }

        .modal-btn-cancelar {
            background: #bce0e6;
            color: #444;
        }

        .modal-btn-cancelar:hover {
            background: #a8d5dd;
            transform: scale(1.02);
        }

        body.acessibilidade-escuro .modal-btn-cancelar {
            background: #252d32 !important;
            color: #cbd5e1 !important;
            border: 1px solid var(--tema-borda, #2f383e) !important;
        }

        body.acessibilidade-escuro .modal-btn-cancelar:hover {
            background: #2f383e !important;
            color: #ffffff !important;
            transform: scale(1.02);
        }

        .modal-btn-salvar {
            background: #2b7a8c;
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(43, 122, 140, 0.28);
        }

        .modal-btn-salvar:hover:not(:disabled) {
            background: #236877;
            transform: scale(1.02);
            box-shadow: 0 6px 18px rgba(43, 122, 140, 0.38);
        }

        body.acessibilidade-escuro .modal-btn-salvar {
            background: #2b7a8c !important;
            color: #ffffff !important;
            border: 1px solid rgba(125, 211, 252, 0.25) !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4) !important;
        }

        body.acessibilidade-escuro .modal-btn-salvar:hover:not(:disabled) {
            background: #236877 !important;
            transform: scale(1.02);
        }

        .modal-btn-salvar:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        .modal-btn-salvar .btn-spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spinIt 0.7s linear infinite;
        }

        .balao-2fa-badge-perigo {
            background: rgba(220, 38, 38, 0.1) !important;
            color: #dc2626 !important;
            border: 1px solid rgba(220, 38, 38, 0.25) !important;
        }

        body.acessibilidade-escuro .balao-2fa-badge-perigo {
            background: rgba(239, 68, 68, 0.16) !important;
            color: #fca5a5 !important;
            border-color: rgba(248, 113, 113, 0.3) !important;
        }

        .modal-alerta-perigo {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background: #fff5f5;
            border: 1px solid #fed7d7;
            border-radius: 20px;
            padding: 14px 16px;
            font-size: 0.86rem;
            color: #991b1b;
            line-height: 1.45;
            margin-bottom: 18px;
        }

        .modal-alerta-perigo svg {
            flex-shrink: 0;
            margin-top: 2px;
            stroke: #dc2626;
        }

        body.acessibilidade-escuro .modal-alerta-perigo {
            background: rgba(239, 68, 68, 0.12) !important;
            border-color: rgba(248, 113, 113, 0.25) !important;
            color: #fca5a5 !important;
        }

        body.acessibilidade-escuro .modal-alerta-perigo svg {
            stroke: #fca5a5 !important;
        }

        .modal-btn-apagar-confirmar {
            background: #dc2626;
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.28);
        }

        .modal-btn-apagar-confirmar:hover:not(:disabled) {
            background: #b91c1c;
            transform: scale(1.02);
            box-shadow: 0 6px 18px rgba(220, 38, 38, 0.38);
        }

        .modal-btn-apagar-confirmar:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        body.acessibilidade-escuro .modal-btn-apagar-confirmar {
            background: #b91c1c !important;
            color: #ffffff !important;
            border: 1px solid rgba(248, 113, 113, 0.3) !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4) !important;
        }

        body.acessibilidade-escuro .modal-btn-apagar-confirmar:hover:not(:disabled) {
            background: #991b1b !important;
            transform: scale(1.02);
        }

        .modal-btn-apagar-confirmar .spinner-apagar {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spinIt 0.7s linear infinite;
        }

        /* CARD DISPOSITIVOS CONECTADOS NO PERFIL */
        .card-dispositivos-preview {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 24px 35px;
            background: #eaeaea;
            border-radius: 30px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.6);
            margin-bottom: 25px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card-dispositivos-preview:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06);
        }

        .disp-preview-info {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .disp-preview-tag {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            background: #d6d6d6;
            color: #2b7a8c;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.6);
            flex-shrink: 0;
        }

        .disp-preview-desc {
            font-size: 0.88rem;
            color: #555;
            margin: 4px 0 0 0;
            font-weight: 500;
        }

        .btn-gerenciar-dispositivos {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 18px;
            background: #2b7a8c;
            color: #ffffff;
            font-size: 0.88rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
            white-space: nowrap;
            box-shadow: 0 4px 14px rgba(43, 122, 140, 0.2);
        }

        .btn-gerenciar-dispositivos:hover {
            background: #236877;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(43, 122, 140, 0.3);
        }

        body.acessibilidade-escuro .card-dispositivos-preview {
            background: var(--tema-superficie, #1a2227) !important;
            border-color: var(--tema-borda, #2f383e) !important;
            color: var(--tema-texto, #f1f5f9) !important;
        }

        body.acessibilidade-escuro .disp-preview-tag {
            background: rgba(43, 122, 140, 0.25) !important;
            color: #7dd3fc !important;
            border-color: rgba(125, 211, 252, 0.2) !important;
        }

        body.acessibilidade-escuro .disp-preview-desc {
            color: var(--tema-texto-secundario, #94a3b8) !important;
        }

        body.acessibilidade-escuro .btn-gerenciar-dispositivos {
            background: #2b7a8c !important;
            color: #ffffff !important;
            border: 1px solid rgba(125, 211, 252, 0.25) !important;
        }

        @media (max-width: 768px) {
            .card-dispositivos-preview {
                flex-direction: column;
                align-items: flex-start;
                padding: 20px 18px;
                gap: 16px;
            }
            .btn-gerenciar-dispositivos {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
    <link rel="stylesheet" href="assets/acessibilidade.css?v=20260927-v3">
    <link rel="stylesheet" href="assets/transicao.css?v=20260926-v1">
</head>

<body class="pagina-perfil">
    <div class="overlay-editar" id="overlayEditar" onclick="toggleEdicao(false)"></div>

    <div class="nav-container-global" id="nav-container-global">
        <nav class="navbar-topo">
            <a href="inicio.php" class="nav-logo" id="navLogoBtn" style="text-decoration: none; color: inherit; display: flex; align-items: center;">HELPFULL <span class="nav-seta-dropdown"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg></span></a>
            <ul class="nav-links">
                <li><a href="Diario.php">Diário</a></li>
                <li><a href="Comunidade.php">Comunidade</a></li>
                <li><a href="ChatBOT.php">Helpy</a></li>
                <li><a href="Atividades.php">Adicionais</a></li>
            </ul>
            <div class="nav-dropdown-mobile" id="navDropdownMobile">
                <a href="inicio.php">Início</a>
                <a href="Diario.php">Diário</a>
                <a href="Comunidade.php">Comunidade</a>
                <a href="ChatBOT.php">Helpy</a>
                <a href="Atividades.php">Adicionais</a>
                <a href="Perfil.php" class="ativo">Perfil</a>
                <a href="dispositivos.php">Dispositivos Conectados</a>
                <a href="javascript:void(0)" class="btn-abrir-acessibilidade" onclick="abrirPainelAcessibilidadeMobile(event);">Configurações</a>
            </div>
            <a href="Perfil.php" class="nav-perfil atual" style="text-decoration: none;">
                <div class="perfil-capsula" <?= !empty($fotoPerfilDb) ? "style='background-image: url($fotoPerfilDb);'" : '' ?>>
                    <?php if (empty($fotoPerfilDb)): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    <?php endif; ?>
                    <div class="sininho-notificacao" id="sininhoNavbar">
                        <svg viewBox="0 0 24 24">
                            <path
                                d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z" />
                        </svg>
                    </div>
                </div>
            </a>
        </nav>

        <div class="acessibilidade-anchor">
            <button type="button" class="btn-acessibilidade" id="btnAcessibilidade" onclick="togglePainelAcessibilidade(event)" aria-label="Abrir configurações e acessibilidade" aria-expanded="false" title="Configurações">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
            </button>
        </div>
    </div>

    <div class="conteudo-site">

        <div class="card-perfil" id="cardSuaConta">
            <h2 class="titulo-secao">Sua Conta:</h2>
            <form class="conta-grid" method="POST" id="formConta">
                <input type="hidden" name="acao" id="acaoConta" value="editar_conta">
                <input type="hidden" name="foto_base64" id="fotoBase64Input" value="<?= $fotoPerfilDb ?>">
                <input type="hidden" name="dois_fatores_ativo" id="inputDoisFatores" value="<?= !empty($usuarioLogado['dois_fatores_ativo']) ? '1' : '0' ?>">

                <div class="conta-form">
                    <label>Nome:</label>
                    <input type="text" name="nome" class="conta-input campo-editavel"
                        value="<?= htmlspecialchars($usuarioLogado['nome']) ?>" readonly required>

                    <label>Email:</label>
                    <input type="email" name="email" class="conta-input campo-editavel"
                        value="<?= htmlspecialchars($usuarioLogado['email']) ?>" readonly required>

                    <label>Senha:</label>
                    <div class="senha-row">
                        <div class="senha-campo-container" id="senhaCampoContainer"
                            onclick="if(document.body.classList.contains('modo-edicao')) abrirModalAlterarSenha();">
                            <input type="password" class="conta-input senha-campo-input" id="campoSenhaPerfil" value="******" readonly disabled>
                            <button type="button" class="btn-lapis-senha" id="btnLapisSenha"
                                onclick="abrirModalAlterarSenha(); event.stopPropagation();" title="Alterar senha" aria-label="Alterar senha">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                </svg>
                            </button>
                        </div>
                        <button type="button" class="btn-editar btn-sair" id="btnSair"
                            onclick="location.href='Sair.php'">Sair da conta</button>
                        <button type="button" class="btn-editar" id="btnEditar"
                            onclick="toggleEdicao(true)">Editar</button>
                        <button type="submit" class="btn-editar btn-salvar" id="btnSalvar" style="display: none;">Salvar</button>
                        <button type="button" class="btn-apagar" id="btnApagar" style="display: none;"
                            onclick="confirmarApagar()">Apagar Conta</button>
                    </div>
                </div>

                <div class="conta-imagem-placeholder <?= !empty($fotoPerfilDb) ? 'tem-foto' : '' ?>"
                    id="placeholderFoto" <?= !empty($fotoPerfilDb) ? "style='background-image: url($fotoPerfilDb);'" : '' ?>>
                    <div class="placeholder-icon" id="placeholderIcon"
                        style="<?= !empty($fotoPerfilDb) ? 'display: none;' : '' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none"
                            stroke="#888" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="acoes-imagem">
                        <div class="acao-imagem-btn" id="iconeLixoEdicao" onclick="removerFotoPerfil()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path
                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                                </path>
                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                <line x1="14" y1="11" x2="14" y2="17"></line>
                            </svg>
                        </div>
                        <div class="acao-imagem-btn" id="iconeLapisEdicao"
                            onclick="document.getElementById('uploadImagemPerfil').click()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                            </svg>
                        </div>
                        <input type="file" id="uploadImagemPerfil" style="display:none;" accept="image/*">
                    </div>
                </div>
            </form>
        </div>

        <!-- BALÃO FLUTUANTE DE CONFIGURAÇÃO DE 2FA (ESTILO PAINEL DE CONFIGURAÇÃO) -->
        <?php $ativo2fa = !empty($usuarioLogado['dois_fatores_ativo']); ?>
        <div class="card-perfil balao-2fa-container" id="balao2faContainer" role="region" aria-label="Configuração de Verificação em Duas Etapas">
            <div class="balao-2fa-pointer" aria-hidden="true"></div>
            <div class="balao-2fa-header">
                <div class="balao-2fa-info">
                    <div class="balao-2fa-badge">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                        <span>Segurança da Conta</span>
                    </div>
                    <h3 class="balao-2fa-titulo">
                        Verificação em Duas Etapas (2FA)
                    </h3>
                    <p class="balao-2fa-desc">
                        Exige um código de segurança de 6 dígitos enviado ao seu e-mail a cada login para proteger sua conta contra acessos não autorizados.
                    </p>
                </div>

                <div class="balao-2fa-switch-box">
                    <span class="balao-2fa-status-pill <?= $ativo2fa ? 'ativo' : 'inativo' ?>" id="statusPill2FA">
                        <?= $ativo2fa ? 'Ativado' : 'Desativado' ?>
                    </span>
                    <div class="onboarding-opt-row painel-opt-row <?= $ativo2fa ? 'ativo' : '' ?>" id="optRow2FA" onclick="alternar2FA()" role="switch" aria-checked="<?= $ativo2fa ? 'true' : 'false' ?>" tabindex="0" title="Alternar verificação em duas etapas" style="margin: 0; cursor: pointer;">
                        <div class="onboarding-switch" aria-hidden="true"></div>
                    </div>
                </div>
            </div>

            <div class="balao-2fa-email-aviso">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                </svg>
                <span>Os códigos serão enviados para: <strong id="emailDestinoTag"><?= htmlspecialchars($usuarioLogado['email']) ?></strong></span>
            </div>
        </div>

        <!-- CARD DISPOSITIVOS CONECTADOS NO PERFIL -->
        <div class="card-perfil card-dispositivos-preview" id="cardDispositivosPerfil">
            <div class="disp-preview-info">
                <div class="disp-preview-tag">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                </div>
                <div>
                    <h2 class="titulo-secao" style="margin: 0; font-size: 1.15rem;">Dispositivos Conectados</h2>
                    <p class="disp-preview-desc">
                        <?= $totalSessoesAtivas ?> <?= $totalSessoesAtivas === 1 ? 'dispositivo conectado a esta conta' : 'dispositivos conectados a esta conta' ?>.
                    </p>
                </div>
            </div>
            <a href="dispositivos.php" class="btn-gerenciar-dispositivos" title="Ver e desconectar aparelhos">
                <span>Gerenciar e Desconectar</span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </a>
        </div>

        <div class="card-perfil card-notificacoes-bloco" id="cardNotificacoesPerfil">
            <div class="notificacoes-bloco-header">
                <div class="notificacoes-bloco-titulo">
                    <span class="notificacoes-icone-tag">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                    </span>
                    <h2 class="titulo-secao" style="margin: 0;" id="tituloNotificacoesPerfil">Notificações</h2>
                </div>
                <div class="painel-actions" style="margin: 0;">
                    <button type="button" class="btn-limpar-pill" id="btnLimparNotificacoesPerfil" onclick="limparNotificacoes()">Limpar</button>
                </div>
            </div>
            <div class="lista-notificacoes" id="containerListaNotificacoesPerfil">
                <p style="font-size: 0.88rem; text-align: left; opacity: 0.6; margin: 8px 0;" class="nenhuma-notif-texto">Nenhuma notificação nova.</p>
            </div>
        </div>

        <div class="card-perfil stats-container">
            <div class="stat-item">
                <div class="stat-label">Total de diários<br>registrados</div>
                <div class="stat-valor"><?= $totalDiarios ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Total de emoções<br>registradas</div>
                <div class="stat-valor"><?= $totalEmocoes ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Total de conversas<br>com o Helpy</div>
                <div class="stat-valor"><?= $totalChats ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Total de posts na<br>comunidade</div>
                <div class="stat-valor"><?= $totalPosts ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Total de curtidas<br>recebidas</div>
                <div class="stat-valor"><?= $totalLikesRecebidos ?></div>
            </div>
        </div>

        <div class="card-perfil">
            <h2 class="titulo-secao">Metas</h2>
            <?php foreach ($metasDoDia as $meta): ?>
                <div class="meta-item <?= $meta['auto'] ? 'concluida' : '' ?>"
                     data-id="<?= htmlspecialchars($meta['id']) ?>"
                     data-auto="<?= $meta['auto'] ? '1' : '0' ?>"
                     onclick="toggleMeta(this)">
                    <div class="meta-checkbox">
                        <svg class="check-icon" viewBox="0 0 24 24">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </div>
                    <div class="meta-texto"><?= htmlspecialchars($meta['texto']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="grid-cal-detalhes">
            <div class="card-perfil">
                <h2 class="titulo-central">Calendário</h2>
                <div class="cal-header-bloco">
                    <button class="cal-seta" onclick="mudarMes(-1)">&#9664;</button>
                    <div class="cal-header-textos"><span class="cal-header-mes" id="mesCalendario">Mês</span><span
                            class="cal-header-ano" id="anoCalendario">Ano</span></div>
                    <button class="cal-seta" onclick="mudarMes(1)">&#9654;</button>
                </div>
                <div class="cal-grid" id="gridCalendario"></div>
            </div>

            <div class="card-perfil" style="position: relative;">
                <h2 class="titulo-central">Detalhes do dia</h2>
                <div id="contadorDiariosDia"
                    style="position: absolute; top: 35px; right: 35px; background: #2b7a8c; color: #fff; padding: 6px 14px; border-radius: 20px; font-weight: 800; font-size: 0.8rem; display: none; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border: 1px solid rgba(255,255,255,0.2);">
                    0</div>
                <div class="detalhes-wrapper">
                    <div class="detalhes-scroll" id="painelDetalhes"></div>
                </div>
            </div>
        </div>

        <div class="graficos-container">
            <canvas class="fundo-animado-canvas fundo-animado-bloco sem-mouse" data-mouse="false"></canvas>
            <div class="graficos-badge">Gráficos</div>
            <div class="grid-duplo">
                <div class="grafico-card">
                    <div class="grafico-header-row">
                        <h3 class="grafico-titulo">Diários</h3>
                        <div class="seletor-ano-wrapper">
                            <button class="cal-seta" style="padding: 4px 12px;"
                                onclick="mudarAnoGrafico(-1)">&#9664;</button>
                            <span id="anoGraficoTexto"
                                style="font-weight: 800; margin: 0 10px; font-size: 0.95rem; color: #444;">2026</span>
                            <button class="cal-seta" style="padding: 4px 12px;"
                                onclick="mudarAnoGrafico(1)">&#9654;</button>
                        </div>
                    </div>
                    <div class="grafico-wrapper">
                        <div class="grafico-y-axis" id="yAxisDiarios"><span>0</span><span>0</span><span>0</span></div>
                        <div class="grafico-barras" id="graficoBarrasDiario"></div>
                    </div>
                </div>

                <div class="grafico-card">
                    <div class="grafico-header-row">
                        <h3 class="grafico-titulo">Emoções do Mês</h3>
                    </div>
                    <div class="grafico-wrapper">
                        <div class="grafico-y-axis" id="yAxisEmocoes">
                            <span>0</span><span>0</span><span>0</span>
                        </div>
                        <div class="grafico-barras" id="graficoBarrasEmocoes">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ÁREA DO RELATÓRIO EM PDF (FORA DO BLOCO DE GRÁFICOS) -->
        <div class="relatorio-acao-container">
            <div class="relatorio-bloco-info">
                <div class="relatorio-icone-circ">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                </div>
                <div class="relatorio-textos">
                    <h4 class="relatorio-titulo-texto">Relatório Completo de Uso e Bem-Estar</h4>
                    <p class="relatorio-subtitulo-texto">
                        Gere um documento em PDF com suas reações emocionais, gráficos do mês, publicações da comunidade e conteúdos assistidos.
                        <strong>100% confidencial: nenhum texto escrito do seu diário é exposto.</strong>
                    </p>
                </div>
            </div>
            <a href="relatorio_pdf.php" target="_blank" class="btn-gerar-relatorio" id="btnGerarRelatorioPdf">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                <span style="color: #ffffff !important;">Criar Relatório em PDF</span>
            </a>
        </div>
    </div>

    <div class="secao-branca">
        <div class="secao-branca-conteudo">
            <footer class="rodape-simples">
                <strong>HELPFULL</strong>
                <div class="contato-info">
                    Entre em contato:<br><br>
                    Email: <a href="mailto:contatohelpfull@gmail.com" style="color: inherit; text-decoration: none;">contatohelpfull@gmail.com</a>
                </div>
            </footer>
        </div>
    </div>

    <!-- MODAL ALTERAR SENHA -->
    <div class="modal-alterar-senha-overlay" id="modalAlterarSenha" role="dialog" aria-modal="true" aria-labelledby="modalSenhaTitulo" onclick="if(event.target===this) fecharModalAlterarSenha();">
        <div class="modal-alterar-senha-card">
            <button type="button" class="modal-fechar-btn" onclick="fecharModalAlterarSenha()" title="Fechar janela">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>

            <div class="modal-senha-header">
                <div class="modal-senha-header-topo">
                    <div class="balao-2fa-badge" style="margin-bottom: 0;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <span>Segurança da Conta</span>
                    </div>
                </div>
                <h3 id="modalSenhaTitulo" class="titulo-secao modal-senha-titulo">Alterar Senha</h3>
                <p id="modalSenhaSubtitulo" class="modal-senha-subtitulo">Atualize sua senha de acesso com segurança.</p>
            </div>

            <form id="formAlterarSenha" onsubmit="submeterAlterarSenha(event)" novalidate>
                <!-- ÁREA QUANDO 2FA ESTÁ ATIVO (EXIGE APENAS O CÓDIGO DO E-MAIL) -->
                <div id="blocoSenha2FA" style="display: none;">
                    <div class="modal-alerta-info">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                        <div>
                            <strong>Verificação em Duas Etapas ativa:</strong> Não é necessário digitar a senha antiga. Enviamos um código de segurança de 6 dígitos para o seu e-mail para validar a troca.
                        </div>
                    </div>

                    <div class="campo-grupo-modal">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <label class="modal-label" for="codigo2faSenha">Código de Verificação (6 dígitos):</label>
                            <button type="button" class="btn-reenviar-codigo-modal" id="btnReenviarCodigoSenha" onclick="solicitarCodigoAlterarSenha()">
                                Reenviar código
                            </button>
                        </div>
                        <div class="input-com-icone-modal">
                            <input type="text" id="codigo2faSenha" class="modal-input input-codigo-destaque" maxlength="6" inputmode="numeric" placeholder="000000" autocomplete="one-time-code">
                        </div>
                        <span class="modal-hint" id="hintEmailCodigo">Os códigos são enviados para <?= htmlspecialchars($usuarioLogado['email']) ?></span>
                    </div>
                </div>

                <!-- ÁREA QUANDO 2FA ESTÁ DESATIVADO (EXIGE CONFIRMAÇÃO DA SENHA ANTIGA) -->
                <div id="blocoSenhaSem2FA" style="display: none;">
                    <div class="campo-grupo-modal">
                        <label class="modal-label" for="senhaAntigaInput">Senha Antiga (Atual):</label>
                        <div class="input-com-icone-modal">
                            <input type="password" id="senhaAntigaInput" class="modal-input" placeholder="Digite sua senha atual" autocomplete="current-password">
                            <button type="button" class="btn-olho-toggle" onclick="toggleMostrarSenha('senhaAntigaInput', this)" title="Mostrar / Ocultar">
                                <svg class="olho-aberto" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <svg class="olho-fechado" style="display:none;" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- CAMPOS DA NOVA SENHA (COMUM AOS DOIS FLUXOS) -->
                <div class="campo-grupo-modal">
                    <label class="modal-label" for="novaSenhaInput">Nova Senha:</label>
                    <div class="input-com-icone-modal">
                        <input type="password" id="novaSenhaInput" class="modal-input" placeholder="Mínimo 6 caracteres" minlength="6" autocomplete="new-password" required>
                        <button type="button" class="btn-olho-toggle" onclick="toggleMostrarSenha('novaSenhaInput', this)" title="Mostrar / Ocultar">
                            <svg class="olho-aberto" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            <svg class="olho-fechado" style="display:none;" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                        </button>
                    </div>
                </div>

                <div class="campo-grupo-modal">
                    <label class="modal-label" for="confirmarNovaSenhaInput">Confirmar Nova Senha:</label>
                    <div class="input-com-icone-modal">
                        <input type="password" id="confirmarNovaSenhaInput" class="modal-input" placeholder="Repita a nova senha" minlength="6" autocomplete="new-password" required>
                        <button type="button" class="btn-olho-toggle" onclick="toggleMostrarSenha('confirmarNovaSenhaInput', this)" title="Mostrar / Ocultar">
                            <svg class="olho-aberto" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            <svg class="olho-fechado" style="display:none;" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                        </button>
                    </div>
                </div>

                <!-- MENSAGENS DE FEEDBACK -->
                <div id="msgAlertaSenha" class="modal-msg-alerta" style="display: none;"></div>

                <div class="modal-senha-acoes">
                    <button type="button" class="modal-btn modal-btn-cancelar" onclick="fecharModalAlterarSenha()">Cancelar</button>
                    <button type="submit" class="modal-btn modal-btn-salvar" id="btnSalvarNovaSenha">
                        <span class="btn-spinner" id="spinnerSalvarSenha" style="display: none;"></span>
                        <span class="btn-texto">Salvar Senha</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL CONFIRMAR APAGAR CONTA -->
    <div class="modal-alterar-senha-overlay" id="modalConfirmarApagarConta" role="dialog" aria-modal="true" aria-labelledby="modalApagarTitulo" onclick="if(event.target===this) fecharModalApagarConta();">
        <div class="modal-alterar-senha-card">
            <button type="button" class="modal-fechar-btn" onclick="fecharModalApagarConta()" title="Fechar janela">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>

            <div class="modal-senha-header">
                <div class="modal-senha-header-topo">
                    <div class="balao-2fa-badge balao-2fa-badge-perigo" style="margin-bottom: 0;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                        <span>Aviso Importante</span>
                    </div>
                </div>
                <h3 id="modalApagarTitulo" class="titulo-secao modal-senha-titulo">Apagar Conta</h3>
                <p id="modalApagarSubtitulo" class="modal-senha-subtitulo">Esta ação é permanente e não poderá ser desfeita.</p>
            </div>

            <div class="modal-alerta-perigo">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <div>
                    <strong>Atenção:</strong> Todo o seu histórico no HelpFull, incluindo registros do diário emocional, conversas do chat e preferências serão excluídos definitivamente.
                </div>
            </div>

            <form id="formApagarConta" onsubmit="submeterApagarConta(event)" novalidate>
                <!-- ÁREA QUANDO 2FA ESTÁ ATIVO (EXIGE O CÓDIGO DO E-MAIL) -->
                <div id="blocoApagar2FA" style="display: none;">
                    <div class="modal-alerta-info">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                        <div>
                            <strong>Verificação em Duas Etapas ativa:</strong> Enviamos um código de segurança de 6 dígitos para seu e-mail para validar a exclusão da conta.
                        </div>
                    </div>

                    <div class="campo-grupo-modal">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <label class="modal-label" for="codigo2faApagar">Código de Verificação (6 dígitos):</label>
                            <button type="button" class="btn-reenviar-codigo-modal" id="btnReenviarCodigoApagar" onclick="solicitarCodigoApagarConta()">
                                Reenviar código
                            </button>
                        </div>
                        <div class="input-com-icone-modal">
                            <input type="text" id="codigo2faApagar" class="modal-input input-codigo-destaque" maxlength="6" inputmode="numeric" placeholder="000000" autocomplete="one-time-code">
                        </div>
                        <span class="modal-hint" id="hintEmailCodigoApagar">Os códigos são enviados para <?= htmlspecialchars(mascararEmail($usuarioLogado['email'])) ?></span>
                    </div>
                </div>

                <!-- ÁREA QUANDO 2FA ESTÁ DESATIVADO (EXIGE A SENHA DO USUÁRIO) -->
                <div id="blocoApagarSem2FA" style="display: none;">
                    <div class="campo-grupo-modal">
                        <label class="modal-label" for="senhaApagarInput">Confirme sua senha para continuar:</label>
                        <div class="input-com-icone-modal">
                            <input type="password" id="senhaApagarInput" class="modal-input" placeholder="Digite sua senha" autocomplete="current-password">
                            <button type="button" class="btn-olho-toggle" onclick="toggleMostrarSenha('senhaApagarInput', this)" title="Mostrar / Ocultar">
                                <svg class="olho-aberto" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <svg class="olho-fechado" style="display:none;" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            </button>
                        </div>
                        <span class="modal-hint">A confirmação da senha é necessária para validar esta ação.</span>
                    </div>
                </div>

                <!-- MENSAGENS DE FEEDBACK -->
                <div id="msgAlertaApagar" class="modal-msg-alerta" style="display: none;"></div>

                <div class="modal-senha-acoes">
                    <button type="button" class="modal-btn modal-btn-cancelar" onclick="fecharModalApagarConta()">Cancelar</button>
                    <button type="submit" class="modal-btn modal-btn-apagar-confirmar" id="btnConfirmarExclusaoConta">
                        <span class="spinner-apagar" id="spinnerApagarConta" style="display: none;"></span>
                        <span class="btn-texto">Excluir Conta</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- TOAST CENTRALIZADO -->
    <div id="notificacaoHelpFull" class="toast-notificacao">
        <div class="toast-barra" id="toastBarra"></div>
        <div class="toast-conteudo">
            <p id="textoNotificacao"></p>
        </div>
        <div class="toast-btn-container"><a id="linkNotificacao" href="#" class="toast-btn"></a></div>
    </div>

    <script>
        const detalhesDoBanco = <?= $jsonDiariosData ?: '{}' ?>;
        const graficoAnoBanco = <?= $jsonGraficoAno ?: '{}' ?>;
        const anosDisponiveisBanco = <?= json_encode($anosDisponiveis) ?>;
        let anoGraficoAtual = new Date().getFullYear();

        const mapaCores = { 'Irritado': '#eab8b8', 'Ansioso': '#ffcc99', 'Feliz': '#fff1a0', 'Calmo': '#b5ff99', 'Triste': '#9bd3ff', 'Amoroso': '#ff99e6' };
        const abrevEmocoes = { 'Irritado': 'Irri.', 'Ansioso': 'Ansi.', 'Feliz': 'Feli.', 'Calmo': 'Calm.', 'Triste': 'Tris.', 'Amoroso': 'Amor.' };
        const mesesNomes = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        const mesesAbrev = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

        let dataNavegacao = new Date();
        const dataHoje = new Date();
        const hojeString = `${dataHoje.getFullYear()}-${String(dataHoje.getMonth() + 1).padStart(2, '0')}-${String(dataHoje.getDate()).padStart(2, '0')}`;

        function desenharCalendario() {
            const mes = dataNavegacao.getMonth(); const ano = dataNavegacao.getFullYear();
            document.getElementById('mesCalendario').innerText = mesesNomes[mes]; document.getElementById('anoCalendario').innerText = ano;
            const primeiroDia = new Date(ano, mes, 1).getDay(); const diasNoMes = new Date(ano, mes + 1, 0).getDate();
            let html = ''; const diasSemana = ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'];
            diasSemana.forEach(d => html += `<div class="cal-dia-semana">${d}</div>`);
            for (let i = 0; i < primeiroDia; i++) html += `<div class="cal-dia vazio"></div>`;
            for (let d = 1; d <= diasNoMes; d++) {
                const mesStr = String(mes + 1).padStart(2, '0'); const diaStr = String(d).padStart(2, '0'); const dataString = `${ano}-${mesStr}-${diaStr}`;
                let classes = 'cal-dia animar';
                if (detalhesDoBanco[dataString]) classes += ' tem-registro';
                if (dataString === hojeString) classes += ' ativo';
                html += `<div class="${classes}" id="cal-btn-${dataString}" onclick="abrirDetalhes('${dataString}')" style="animation-delay: ${d * 0.02}s;">${d}</div>`;
            }
            document.getElementById('gridCalendario').innerHTML = html;
            atualizarGraficoEmocoes(ano, mes);
        }

        function atualizarGraficoEmocoes(ano, mes) {
            const prefix = `${ano}-${String(mes + 1).padStart(2, '0')}-`;
            let emocoesCount = { 'Irritado': 0, 'Ansioso': 0, 'Feliz': 0, 'Calmo': 0, 'Triste': 0, 'Amoroso': 0 };
            
            for (let data in detalhesDoBanco) {
                if (data.startsWith(prefix)) {
                    detalhesDoBanco[data].forEach(registro => {
                        if (registro.emocao && registro.emocao !== 'Null') {
                            const em = registro.emocao.trim().charAt(0).toUpperCase() + registro.emocao.trim().slice(1).toLowerCase();
                            if (emocoesCount[em] !== undefined) {
                                emocoesCount[em]++;
                            }
                        }
                    });
                }
            }
            
            let maxCount = 0;
            for (let e in emocoesCount) {
                if (emocoesCount[e] > maxCount) maxCount = emocoesCount[e];
            }
            
            const maxEmocao = Math.max(1, maxCount);
            
            const yAxis = document.getElementById('yAxisEmocoes');
            if (yAxis) yAxis.innerHTML = `<span>${maxEmocao}</span><span>${maxEmocao > 1 ? Math.round(maxEmocao / 2) : ''}</span><span>0</span>`;
            
            const grafico = document.getElementById('graficoBarrasEmocoes');
            if (!grafico) return;
            
            let html = '';
            for (let emNome in emocoesCount) {
                const qtd = emocoesCount[emNome];
                let altura = (qtd / maxEmocao) * 85;
                if (altura === 0) altura = 5;
                const corHex = mapaCores[emNome] || '#ccc';
                const labelCurto = abrevEmocoes[emNome] || emNome;
                
                html += `
                    <div class="barra-coluna">
                        <div class="barra" style="height: ${altura}%; background: ${corHex};"
                            title="${qtd} vezes (${emNome})"></div>
                        <div class="barra-label">${labelCurto}</div>
                    </div>
                `;
            }
            grafico.innerHTML = html;
        }

        function mudarMes(offset) { dataNavegacao.setMonth(dataNavegacao.getMonth() + offset); desenharCalendario(); document.getElementById('painelDetalhes').innerHTML = '<div style="text-align: center; color: #888; margin-top: 20px;">Selecione um dia.</div>'; }

        function abrirDetalhes(dataString) {
            const painel = document.getElementById('painelDetalhes'); const contador = document.getElementById('contadorDiariosDia');
            document.querySelectorAll('.cal-dia').forEach(el => el.classList.remove('ativo'));
            const btn = document.getElementById(`cal-btn-${dataString}`); if (btn) btn.classList.add('ativo');
 
            if (!detalhesDoBanco[dataString] || detalhesDoBanco[dataString].length === 0) {
                painel.innerHTML = '<div style="text-align: center; color: #888; margin-top: 20px;">Nenhum registro encontrado neste dia.</div>';
                if (contador) contador.style.display = 'none'; return;
            }
 
            const total = detalhesDoBanco[dataString].length;
            if (contador) { contador.innerText = total; contador.style.display = 'block'; }
 
            let html = '';
            const registros = [...detalhesDoBanco[dataString]].reverse();
            registros.forEach((registro, index) => {
                const emocaoNome = registro.emocao && registro.emocao !== 'Null' ? registro.emocao : 'Sem Emoção';
                const cor = mapaCores[registro.emocao] || '#ccc';
                html += `
                    <div class="detalhe-item animar" style="animation-delay: ${index * 0.1}s;">
                        <div class="detalhe-cor" style="background-color: ${cor};"></div>
                        <div class="detalhe-info">
                            <div class="detalhe-nome">${emocaoNome}</div>
                            <div class="detalhe-pontos">${registro.texto || '...'}</div>
                        </div>
                        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px; flex-shrink: 0; min-width: 70px;">
                            <div class="detalhe-hora">${registro.hora}</div>
                            <button onclick="apagarDiario('${registro.id}')" style="background: rgba(255, 77, 77, 0.1); border: none; cursor: pointer; color: #ff4d4d; width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; transition: 0.2s;" onmouseover="this.style.background='rgba(255, 77, 77, 0.2)'; this.style.transform='scale(1.1)'" onmouseout="this.style.background='rgba(255, 77, 77, 0.1)'; this.style.transform='scale(1)'">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            </button>
                        </div>
                    </div>`;
            });
            painel.innerHTML = html;
        }

        function apagarDiario(id) {
            if (confirm("Deseja realmente apagar este registro do diário?")) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="acao" value="apagar_diario"><input type="hidden" name="diario_id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function mudarAnoGrafico(offset) {
            let index = anosDisponiveisBanco.indexOf(anoGraficoAtual.toString());
            if (index === -1) index = anosDisponiveisBanco.length - 1;
            let novoIndex = index + offset;
            if (novoIndex >= 0 && novoIndex < anosDisponiveisBanco.length) { anoGraficoAtual = parseInt(anosDisponiveisBanco[novoIndex]); document.getElementById('anoGraficoTexto').innerText = anoGraficoAtual; renderGraficoDiarios(); }
        }

        function renderGraficoDiarios() {
            const ano = anoGraficoAtual.toString(); document.getElementById('anoGraficoTexto').innerText = ano;
            const container = document.getElementById('graficoBarrasDiario'); const yAxis = document.getElementById('yAxisDiarios');
            let dadosAno = graficoAnoBanco[ano] || {}; let maxDiarios = 1;
            for (let i = 1; i <= 12; i++) { if ((dadosAno[i] || 0) > maxDiarios) maxDiarios = dadosAno[i]; }
            let midValue = Math.round(maxDiarios / 2); let midLabel = (midValue === maxDiarios || midValue === 0) ? '' : midValue;
            yAxis.innerHTML = `<span>${maxDiarios}</span><span>${midLabel}</span><span>0</span>`;
            let html = '';
            for (let i = 1; i <= 12; i++) {
                const qtd = dadosAno[i] || 0; let altura = (qtd / maxDiarios) * 85; if (altura === 0) altura = 5;
                html += `<div class="barra-coluna"><div class="barra" style="height: ${altura}%; background: #9bd3ff;" title="${qtd} registros"></div><div class="barra-label">${mesesAbrev[i - 1]}</div></div>`;
            }
            container.innerHTML = html;
        }

        function toggleEdicao(ativar) {
            const body = document.body;
            const btnEditar = document.getElementById('btnEditar');
            const btnSalvar = document.getElementById('btnSalvar');
            const btnApagar = document.getElementById('btnApagar');
            const btnSair = document.getElementById('btnSair');
            const btnLapisSenha = document.getElementById('btnLapisSenha');
            const inputs = document.querySelectorAll('.campo-editavel');
            const card = document.getElementById('cardSuaConta');

            if (ativar) {
                window.scrollTo({ top: 0, behavior: 'instant' });
                body.classList.add('modo-edicao');
                if (btnSair) btnSair.style.display = 'none';
                if (btnLapisSenha) btnLapisSenha.style.display = 'flex';
                if (btnEditar) {
                    btnEditar.textContent = 'Cancelar';
                    btnEditar.style.background = '#64748b';
                    btnEditar.style.color = '#fff';
                    btnEditar.onclick = () => {
                        const placeholder = document.getElementById('placeholderFoto');
                        const initialFoto = <?= json_encode($fotoPerfilDb) ?>;
                        if (placeholder) {
                            placeholder.style.backgroundImage = initialFoto ? `url(${initialFoto})` : 'none';
                            if (initialFoto) {
                                placeholder.classList.add('tem-foto');
                                const pIcon = document.getElementById('placeholderIcon');
                                if (pIcon) pIcon.style.display = 'none';
                            } else {
                                placeholder.classList.remove('tem-foto');
                                const pIcon = document.getElementById('placeholderIcon');
                                if (pIcon) pIcon.style.display = 'block';
                            }
                        }
                        const fotoInput = document.getElementById('fotoBase64Input');
                        if (fotoInput) fotoInput.value = initialFoto || '';

                        // Reverte estado visual de 2FA para o inicial caso tenha cancelado sem salvar
                        if (typeof reverterEstado2FA === 'function') reverterEstado2FA();
                        toggleEdicao(false);
                    };
                }
                if (btnSalvar) btnSalvar.style.display = 'inline-flex';
                if (btnApagar) btnApagar.style.display = 'inline-flex';
                const isDark = body.classList.contains('acessibilidade-escuro');
                inputs.forEach(input => {
                    input.removeAttribute('readonly');
                    input.style.background = isDark ? '#252d32' : '#fff';
                    input.style.color = isDark ? '#f1f5f9' : '#333';
                    input.style.boxShadow = 'inset 0 2px 5px rgba(0,0,0,0.05)';
                });
            } else {
                body.classList.remove('modo-edicao');
                if (btnSair) btnSair.style.display = 'inline-flex';
                if (btnLapisSenha) btnLapisSenha.style.display = 'none';
                if (btnEditar) {
                    btnEditar.textContent = 'Editar';
                    btnEditar.style.background = '';
                    btnEditar.style.color = '';
                    btnEditar.onclick = () => toggleEdicao(true);
                }
                if (btnSalvar) btnSalvar.style.display = 'none';
                if (btnApagar) btnApagar.style.display = 'none';
                inputs.forEach(input => {
                    input.setAttribute('readonly', true);
                    input.style.background = '';
                    input.style.color = '';
                    input.style.boxShadow = '';
                });
            }
        }
        window.toggleEdicao = toggleEdicao;

        // === CONTROLE DE 2FA (VERIFICAÇÃO EM DUAS ETAPAS) ===
        let initial2FA = <?= $ativo2fa ? 'true' : 'false' ?>;
        let estado2FAAtual = initial2FA;

        function reverterEstado2FA() {
            estado2FAAtual = initial2FA;
            const optRow = document.getElementById('optRow2FA');
            const pill = document.getElementById('statusPill2FA');
            const inputHidden = document.getElementById('inputDoisFatores');
            if (optRow) {
                optRow.classList.toggle('ativo', initial2FA);
                optRow.setAttribute('aria-checked', initial2FA ? 'true' : 'false');
            }
            if (pill) {
                pill.textContent = initial2FA ? 'Ativado' : 'Desativado';
                pill.className = 'balao-2fa-status-pill ' + (initial2FA ? 'ativo' : 'inativo');
            }
            if (inputHidden) {
                inputHidden.value = initial2FA ? '1' : '0';
            }
            // Sincroniza também no servidor para manter paridade
            fetch('atualizar_2fa.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ativo: initial2FA })
            }).catch(() => {});
        }

        async function alternar2FA() {
            const novoEstado = !estado2FAAtual;
            const optRow = document.getElementById('optRow2FA');
            const pill = document.getElementById('statusPill2FA');
            const inputHidden = document.getElementById('inputDoisFatores');

            estado2FAAtual = novoEstado;
            if (optRow) {
                optRow.classList.toggle('ativo', novoEstado);
                optRow.setAttribute('aria-checked', novoEstado ? 'true' : 'false');
            }
            if (pill) {
                pill.textContent = novoEstado ? 'Ativado' : 'Desativado';
                pill.className = 'balao-2fa-status-pill ' + (novoEstado ? 'ativo' : 'inativo');
            }
            if (inputHidden) {
                inputHidden.value = novoEstado ? '1' : '0';
            }

            try {
                const resp = await fetch('atualizar_2fa.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ativo: novoEstado })
                });
                const data = await resp.json();
                if (data.sucesso) {
                    initial2FA = novoEstado; // Atualiza o estado salvo
                    mostrarToastFeedback(data.mensagem || (novoEstado ? '2FA ativado com sucesso!' : '2FA desativado.'), 'sucesso');
                } else {
                    // Reverte se falhou
                    estado2FAAtual = !novoEstado;
                    if (optRow) {
                        optRow.classList.toggle('ativo', estado2FAAtual);
                        optRow.setAttribute('aria-checked', estado2FAAtual ? 'true' : 'false');
                    }
                    if (pill) {
                        pill.textContent = estado2FAAtual ? 'Ativado' : 'Desativado';
                        pill.className = 'balao-2fa-status-pill ' + (estado2FAAtual ? 'ativo' : 'inativo');
                    }
                    if (inputHidden) {
                        inputHidden.value = estado2FAAtual ? '1' : '0';
                    }
                    mostrarToastFeedback(data.mensagem || 'Erro ao alterar 2FA.', 'erro');
                }
            } catch (e) {
                mostrarToastFeedback('Erro de conexão ao salvar 2FA.', 'erro');
            }
        }

        function mostrarToastFeedback(msg, tipo = 'info') {
            let t = document.getElementById('toast2FAFeedback');
            if (!t) {
                t = document.createElement('div');
                t.id = 'toast2FAFeedback';
                t.className = 'toast-feedback-2fa';
                document.body.appendChild(t);
            }

            const msgLower = (msg || '').toLowerCase();
            const isDesativado = tipo === 'desativado' || msgLower.includes('desativad');
            const isSucesso = tipo === 'sucesso' || msgLower.includes('ativad') || msgLower.includes('sucesso');
            const isErro = tipo === 'erro' || msgLower.includes('erro') || msgLower.includes('falh');

            let classeTipo = 'toast-info';
            let svgIcon = '';

            if (isErro) {
                classeTipo = 'toast-erro';
                svgIcon = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;
            } else if (isDesativado) {
                classeTipo = 'toast-desativado';
                svgIcon = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="8" y1="12" x2="16" y2="12"/></svg>`;
            } else if (isSucesso) {
                classeTipo = 'toast-sucesso';
                svgIcon = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>`;
            } else {
                classeTipo = 'toast-info';
                svgIcon = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>`;
            }

            t.className = `toast-feedback-2fa ${classeTipo}`;
            t.innerHTML = `<span class="toast-icone-wrap">${svgIcon}</span><span>${msg}</span>`;

            // Força o navegador a calcular o layout antes de animar
            void t.offsetWidth;
            t.classList.add('mostrar');

            clearTimeout(window.__toast2FATimer);
            window.__toast2FATimer = setTimeout(() => {
                t.classList.remove('mostrar');
            }, 3600);
        }

        // Sincroniza o e-mail no aviso do balão se o usuário editar o campo
        const inputEmailPerfil = document.querySelector('input[name="email"]');
        if (inputEmailPerfil) {
            inputEmailPerfil.addEventListener('input', function () {
                const tag = document.getElementById('emailDestinoTag');
                if (tag) tag.textContent = this.value || 'seu e-mail';
            });
        }

        // === CONTROLE DO MODAL DE ALTERAÇÃO DE SENHA ===
        let timerCooldownSenha = null;

        function abrirModalAlterarSenha() {
            const modal = document.getElementById('modalAlterarSenha');
            const bloco2FA = document.getElementById('blocoSenha2FA');
            const blocoSem2FA = document.getElementById('blocoSenhaSem2FA');
            const alerta = document.getElementById('msgAlertaSenha');
            const subtitulo = document.getElementById('modalSenhaSubtitulo');

            if (alerta) {
                alerta.style.display = 'none';
                alerta.className = 'modal-msg-alerta';
                alerta.textContent = '';
            }
            const form = document.getElementById('formAlterarSenha');
            if (form) form.reset();

            // Reseta ícones dos olhos para estado oculto
            document.querySelectorAll('#formAlterarSenha .btn-olho-toggle').forEach(btn => {
                const olhoAberto = btn.querySelector('.olho-aberto');
                const olhoFechado = btn.querySelector('.olho-fechado');
                if (olhoAberto) olhoAberto.style.display = 'block';
                if (olhoFechado) olhoFechado.style.display = 'none';
            });
            ['novaSenhaInput', 'confirmarNovaSenhaInput', 'senhaAntigaInput'].forEach(id => {
                const inp = document.getElementById(id);
                if (inp) inp.type = 'password';
            });

            // Verifica o estado atual de 2FA
            const tem2FA = !!estado2FAAtual;
            if (tem2FA) {
                bloco2FA.style.display = 'block';
                blocoSem2FA.style.display = 'none';
                subtitulo.textContent = 'Verificação em Duas Etapas ativa. Enviamos um código para seu e-mail.';
                solicitarCodigoAlterarSenha();
                setTimeout(() => {
                    const inpCod = document.getElementById('codigo2faSenha');
                    if (inpCod) inpCod.focus();
                }, 180);
            } else {
                bloco2FA.style.display = 'none';
                blocoSem2FA.style.display = 'block';
                subtitulo.textContent = 'Confirme sua senha antiga para cadastrar uma nova senha.';
                setTimeout(() => {
                    const inpAnt = document.getElementById('senhaAntigaInput');
                    if (inpAnt) inpAnt.focus();
                }, 180);
            }

            modal.classList.add('aberto');
            travarScrollFundo();
        }

        let scrollPosBloqueioModal = 0;

        function travarScrollFundo() {
            scrollPosBloqueioModal = window.pageYOffset || document.documentElement.scrollTop || 0;
            document.body.style.top = `-${scrollPosBloqueioModal}px`;
            document.body.classList.add('modal-aberto-travar');
            document.documentElement.classList.add('modal-aberto-travar');
        }

        function destravarScrollFundo() {
            document.body.classList.remove('modal-aberto-travar');
            document.documentElement.classList.remove('modal-aberto-travar');
            document.body.style.top = '';
            window.scrollTo(0, scrollPosBloqueioModal);
        }

        function fecharModalAlterarSenha() {
            const modal = document.getElementById('modalAlterarSenha');
            if (modal) modal.classList.remove('aberto');
            destravarScrollFundo();
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                fecharModalAlterarSenha();
                fecharModalApagarConta();
            }
        });

        function toggleMostrarSenha(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const olhoAberto = btn.querySelector('.olho-aberto');
            const olhoFechado = btn.querySelector('.olho-fechado');

            if (input.type === 'password') {
                input.type = 'text';
                if (olhoAberto) olhoAberto.style.display = 'none';
                if (olhoFechado) olhoFechado.style.display = 'block';
            } else {
                input.type = 'password';
                if (olhoAberto) olhoAberto.style.display = 'block';
                if (olhoFechado) olhoFechado.style.display = 'none';
            }
        }

        async function solicitarCodigoAlterarSenha() {
            const btnReenviar = document.getElementById('btnReenviarCodigoSenha');
            const alerta = document.getElementById('msgAlertaSenha');
            if (btnReenviar) btnReenviar.disabled = true;

            try {
                const resp = await fetch('alterar_senha.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ acao: 'solicitar_codigo' })
                });
                const data = await resp.json();

                if (data.sucesso) {
                    if (alerta) {
                        alerta.className = 'modal-msg-alerta sucesso';
                        alerta.textContent = data.mensagem;
                        alerta.style.display = 'block';
                    }
                    iniciarCooldownReenvioSenha(20);
                } else {
                    if (alerta) {
                        alerta.className = 'modal-msg-alerta erro';
                        alerta.textContent = data.mensagem || 'Erro ao enviar código.';
                        alerta.style.display = 'block';
                    }
                    if (btnReenviar) btnReenviar.disabled = false;
                }
            } catch (err) {
                if (btnReenviar) btnReenviar.disabled = false;
            }
        }

        function iniciarCooldownReenvioSenha(segundos) {
            const btnReenviar = document.getElementById('btnReenviarCodigoSenha');
            if (!btnReenviar) return;
            clearInterval(timerCooldownSenha);
            let restante = segundos;
            btnReenviar.disabled = true;
            btnReenviar.textContent = `Reenviar (${restante}s)`;

            timerCooldownSenha = setInterval(() => {
                restante--;
                if (restante <= 0) {
                    clearInterval(timerCooldownSenha);
                    btnReenviar.disabled = false;
                    btnReenviar.textContent = 'Reenviar código';
                } else {
                    btnReenviar.textContent = `Reenviar (${restante}s)`;
                }
            }, 1000);
        }

        async function submeterAlterarSenha(e) {
            e.preventDefault();
            const alerta = document.getElementById('msgAlertaSenha');
            const btnSalvar = document.getElementById('btnSalvarNovaSenha');
            const spinner = document.getElementById('spinnerSalvarSenha');
            const btnTexto = btnSalvar.querySelector('.btn-texto');

            const novaSenha = document.getElementById('novaSenhaInput').value.trim();
            const confirmarSenha = document.getElementById('confirmarNovaSenhaInput').value.trim();
            const tem2FA = !!estado2FAAtual;
            const codigo2fa = tem2FA ? document.getElementById('codigo2faSenha').value.trim() : '';
            const senhaAntiga = !tem2FA ? document.getElementById('senhaAntigaInput').value : '';

            // Validações no cliente
            if (novaSenha.length < 6) {
                alerta.className = 'modal-msg-alerta erro';
                alerta.textContent = 'A nova senha deve ter no mínimo 6 caracteres.';
                alerta.style.display = 'block';
                return;
            }

            if (novaSenha !== confirmarSenha) {
                alerta.className = 'modal-msg-alerta erro';
                alerta.textContent = 'A confirmação de senha não confere com a nova senha.';
                alerta.style.display = 'block';
                return;
            }

            if (tem2FA && (!codigo2fa || codigo2fa.length !== 6)) {
                alerta.className = 'modal-msg-alerta erro';
                alerta.textContent = 'Por favor, digite o código de 6 dígitos enviado ao seu e-mail.';
                alerta.style.display = 'block';
                return;
            }

            if (!tem2FA && !senhaAntiga) {
                alerta.className = 'modal-msg-alerta erro';
                alerta.textContent = 'Por favor, informe sua senha atual para confirmação.';
                alerta.style.display = 'block';
                return;
            }

            // Envio para o servidor
            alerta.style.display = 'none';
            btnSalvar.disabled = true;
            if (spinner) spinner.style.display = 'inline-block';
            if (btnTexto) btnTexto.textContent = 'Salvando...';

            try {
                const resp = await fetch('alterar_senha.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        acao: 'salvar_senha',
                        nova_senha: novaSenha,
                        confirmar_senha: confirmarSenha,
                        senha_antiga: senhaAntiga,
                        codigo_2fa: codigo2fa
                    })
                });

                const data = await resp.json();

                if (data.sucesso) {
                    alerta.className = 'modal-msg-alerta sucesso';
                    alerta.textContent = '✓ ' + (data.mensagem || 'Senha alterada com sucesso!');
                    alerta.style.display = 'block';
                    if (typeof mostrarToastFeedback === 'function') {
                        mostrarToastFeedback('Senha alterada com sucesso!', 'sucesso');
                    }
                    setTimeout(() => {
                        fecharModalAlterarSenha();
                    }, 1700);
                } else {
                    alerta.className = 'modal-msg-alerta erro';
                    alerta.textContent = data.mensagem || 'Erro ao alterar senha. Tente novamente.';
                    alerta.style.display = 'block';
                }
            } catch (err) {
                alerta.className = 'modal-msg-alerta erro';
                alerta.textContent = 'Erro de comunicação com o servidor: ' + err.message;
                alerta.style.display = 'block';
            } finally {
                btnSalvar.disabled = false;
                if (spinner) spinner.style.display = 'none';
                if (btnTexto) btnTexto.textContent = 'Salvar Senha';
            }
        }

        // === CONTROLE DO MODAL DE EXCLUSÃO DE CONTA ===
        let timerCooldownApagar = null;

        function confirmarApagar() {
            abrirModalApagarConta();
        }

        function abrirModalApagarConta() {
            const modal = document.getElementById('modalConfirmarApagarConta');
            const bloco2FA = document.getElementById('blocoApagar2FA');
            const blocoSem2FA = document.getElementById('blocoApagarSem2FA');
            const alerta = document.getElementById('msgAlertaApagar');
            const subtitulo = document.getElementById('modalApagarSubtitulo');

            if (alerta) {
                alerta.style.display = 'none';
                alerta.className = 'modal-msg-alerta';
                alerta.textContent = '';
            }
            const form = document.getElementById('formApagarConta');
            if (form) form.reset();

            // Reseta icones dos olhos para estado oculto
            document.querySelectorAll('#formApagarConta .btn-olho-toggle').forEach(btn => {
                const olhoAberto = btn.querySelector('.olho-aberto');
                const olhoFechado = btn.querySelector('.olho-fechado');
                if (olhoAberto) olhoAberto.style.display = 'block';
                if (olhoFechado) olhoFechado.style.display = 'none';
            });
            const inpSenha = document.getElementById('senhaApagarInput');
            if (inpSenha) inpSenha.type = 'password';

            const tem2FA = !!estado2FAAtual;
            if (tem2FA) {
                bloco2FA.style.display = 'block';
                blocoSem2FA.style.display = 'none';
                subtitulo.textContent = 'Verificação em Duas Etapas ativa. Enviamos um código para seu e-mail.';
                solicitarCodigoApagarConta();
                setTimeout(() => {
                    const inpCod = document.getElementById('codigo2faApagar');
                    if (inpCod) inpCod.focus();
                }, 180);
            } else {
                bloco2FA.style.display = 'none';
                blocoSem2FA.style.display = 'block';
                subtitulo.textContent = 'Confirme sua senha para validar a exclusão da conta.';
                setTimeout(() => {
                    if (inpSenha) inpSenha.focus();
                }, 180);
            }

            modal.classList.add('aberto');
            travarScrollFundo();
        }

        function fecharModalApagarConta() {
            const modal = document.getElementById('modalConfirmarApagarConta');
            if (modal) modal.classList.remove('aberto');
            destravarScrollFundo();
        }

        async function solicitarCodigoApagarConta() {
            const btnReenviar = document.getElementById('btnReenviarCodigoApagar');
            const alerta = document.getElementById('msgAlertaApagar');
            if (!btnReenviar) return;

            btnReenviar.disabled = true;
            btnReenviar.textContent = 'Enviando código...';

            try {
                const resp = await fetch('apagar_conta.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ acao: 'solicitar_codigo' })
                });

                const data = await resp.json();

                if (data.sucesso) {
                    alerta.className = 'modal-msg-alerta sucesso';
                    alerta.textContent = data.mensagem || 'Código enviado com sucesso.';
                    alerta.style.display = 'block';

                    iniciarCooldownApagar(20);
                } else {
                    alerta.className = 'modal-msg-alerta erro';
                    alerta.textContent = data.mensagem || 'Erro ao enviar código de verificação.';
                    alerta.style.display = 'block';
                    btnReenviar.disabled = false;
                    btnReenviar.textContent = 'Reenviar código';
                }
            } catch (err) {
                alerta.className = 'modal-msg-alerta erro';
                alerta.textContent = 'Erro ao conectar ao servidor para envio do código.';
                alerta.style.display = 'block';
                btnReenviar.disabled = false;
                btnReenviar.textContent = 'Reenviar código';
            }
        }

        function iniciarCooldownApagar(segundos) {
            const btnReenviar = document.getElementById('btnReenviarCodigoApagar');
            if (!btnReenviar) return;

            if (timerCooldownApagar) clearInterval(timerCooldownApagar);

            let restante = segundos;
            btnReenviar.disabled = true;
            btnReenviar.textContent = `Reenviar (${restante}s)`;

            timerCooldownApagar = setInterval(() => {
                restante--;
                if (restante <= 0) {
                    clearInterval(timerCooldownApagar);
                    btnReenviar.disabled = false;
                    btnReenviar.textContent = 'Reenviar código';
                } else {
                    btnReenviar.textContent = `Reenviar (${restante}s)`;
                }
            }, 1000);
        }

        async function submeterApagarConta(e) {
            if (e) e.preventDefault();
            const alerta = document.getElementById('msgAlertaApagar');
            const btnConfirmar = document.getElementById('btnConfirmarExclusaoConta');
            const spinner = document.getElementById('spinnerApagarConta');
            const btnTexto = btnConfirmar ? btnConfirmar.querySelector('.btn-texto') : null;

            const tem2FA = !!estado2FAAtual;
            const codigo2fa = tem2FA ? document.getElementById('codigo2faApagar').value.trim() : '';
            const senha = !tem2FA ? document.getElementById('senhaApagarInput').value : '';

            if (tem2FA && (!codigo2fa || codigo2fa.length !== 6)) {
                alerta.className = 'modal-msg-alerta erro';
                alerta.textContent = 'Por favor, digite o código de 6 dígitos enviado ao seu e-mail.';
                alerta.style.display = 'block';
                return;
            }

            if (!tem2FA && !senha) {
                alerta.className = 'modal-msg-alerta erro';
                alerta.textContent = 'Por favor, digite sua senha para confirmar a exclusão da conta.';
                alerta.style.display = 'block';
                return;
            }

            alerta.style.display = 'none';
            if (btnConfirmar) btnConfirmar.disabled = true;
            if (spinner) spinner.style.display = 'inline-block';
            if (btnTexto) btnTexto.textContent = 'Excluindo...';

            try {
                const resp = await fetch('apagar_conta.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        acao: 'confirmar_apagar',
                        codigo_2fa: codigo2fa,
                        senha: senha
                    })
                });

                const data = await resp.json();

                if (data.sucesso) {
                    alerta.className = 'modal-msg-alerta sucesso';
                    alerta.textContent = data.mensagem || 'Conta excluída com sucesso. Redirecionando...';
                    alerta.style.display = 'block';

                    setTimeout(() => {
                        window.location.href = data.redirecionar || 'Comeco.php';
                    }, 1200);
                } else {
                    alerta.className = 'modal-msg-alerta erro';
                    alerta.textContent = data.mensagem || 'Não foi possível apagar a conta. Verifique os dados.';
                    alerta.style.display = 'block';
                    if (btnConfirmar) btnConfirmar.disabled = false;
                    if (spinner) spinner.style.display = 'none';
                    if (btnTexto) btnTexto.textContent = 'Excluir Conta';
                }
            } catch (err) {
                alerta.className = 'modal-msg-alerta erro';
                alerta.textContent = 'Erro de comunicação com o servidor: ' + err.message;
                alerta.style.display = 'block';
                if (btnConfirmar) btnConfirmar.disabled = false;
                if (spinner) spinner.style.display = 'none';
                if (btnTexto) btnTexto.textContent = 'Excluir Conta';
            }
        }

        function removerFotoPerfil() {
            const placeholder = document.getElementById('placeholderFoto'); const icon = document.getElementById('placeholderIcon');
            placeholder.style.backgroundImage = 'none'; placeholder.classList.remove('tem-foto'); if (icon) icon.style.display = 'block';
            document.getElementById('uploadImagemPerfil').value = ''; document.getElementById('fotoBase64Input').value = '';
        }

        document.getElementById('uploadImagemPerfil').addEventListener('change', function (event) {
            if (event.target.files && event.target.files[0]) {
                const file = event.target.files[0];
                const reader = new FileReader();
                reader.onload = function (e) {
                    const img = new Image();
                    img.onload = function () {
                        const canvas = document.createElement('canvas');
                        const maxDim = 250;
                        let w = img.width;
                        let h = img.height;
                        if (w > h) {
                            if (w > maxDim) {
                                h = Math.round((h * maxDim) / w);
                                w = maxDim;
                            }
                        } else {
                            if (h > maxDim) {
                                w = Math.round((w * maxDim) / h);
                                h = maxDim;
                            }
                        }
                        canvas.width = w;
                        canvas.height = h;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, w, h);
                        const stringBase64 = canvas.toDataURL('image/jpeg', 0.82);

                        const placeholder = document.getElementById('placeholderFoto');
                        const icon = document.getElementById('placeholderIcon');
                        placeholder.style.backgroundImage = `url(${stringBase64})`;
                        placeholder.classList.add('tem-foto');
                        if (icon) icon.style.display = 'none';
                        document.getElementById('fotoBase64Input').value = stringBase64;
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // === METAS DIÁRIAS (SORTEIO E PERSISTÊNCIA) ===
        const CHAVE_METAS_STORAGE = 'helpfull_metas_<?= $hoje ?>_<?= $id ?>';

        function carregarMetasStorage() {
            try {
                // Remove histórico de metas de datas anteriores para manter limpo
                Object.keys(localStorage).forEach(k => {
                    if (k.startsWith('helpfull_metas_') && !k.startsWith('helpfull_metas_<?= $hoje ?>')) {
                        localStorage.removeItem(k);
                    }
                });

                const estadoSalvo = JSON.parse(localStorage.getItem(CHAVE_METAS_STORAGE) || '{}');
                document.querySelectorAll('.meta-item').forEach(item => {
                    const metaId = item.getAttribute('data-id');
                    const autoConcluida = item.getAttribute('data-auto') === '1';

                    if (estadoSalvo.hasOwnProperty(metaId)) {
                        if (estadoSalvo[metaId]) {
                            item.classList.add('concluida');
                        } else {
                            item.classList.remove('concluida');
                        }
                    } else if (autoConcluida) {
                        item.classList.add('concluida');
                    }
                });
            } catch (e) {
                console.error('Erro ao ler metas:', e);
            }
        }

        function salvarMetasStorage() {
            try {
                const estado = {};
                document.querySelectorAll('.meta-item').forEach(item => {
                    const metaId = item.getAttribute('data-id');
                    estado[metaId] = item.classList.contains('concluida');
                });
                localStorage.setItem(CHAVE_METAS_STORAGE, JSON.stringify(estado));
            } catch (e) {
                console.error('Erro ao salvar metas:', e);
            }
        }

        function toggleMeta(el) {
            el.classList.toggle('concluida');
            salvarMetasStorage();
        }

        window.onload = () => {
            desenharCalendario(); renderGraficoDiarios(); carregarMetasStorage();
            if (detalhesDoBanco[hojeString]) abrirDetalhes(hojeString); else document.getElementById('painelDetalhes').innerHTML = '<div style="text-align: center; color: #888; margin-top: 20px;">Nenhum registro para hoje. Que tal escrever algo?</div>';
        };

        // === MOBILE DROPDOWN TOGGLE ===
        const navLogo = document.querySelector('.nav-logo');
        const navDropdown = document.getElementById('navDropdownMobile');
        if (navLogo && navDropdown) {
            navLogo.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    e.preventDefault();
                    e.stopPropagation();
                    const aberto = navDropdown.classList.toggle('aberto');
                    navLogo.classList.toggle('aberto', aberto);
                }
            });
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.navbar-topo') && !e.target.closest('.nav-dropdown-mobile')) {
                    navDropdown.classList.remove('aberto');
                    navLogo.classList.remove('aberto');
                }
            });
        }
    </script>
    <script>
        function togglePainelAcessibilidade(e) {
            if (e && e.stopPropagation) e.stopPropagation();
            if (window.togglePainelAcessibilidade && window.togglePainelAcessibilidade !== togglePainelAcessibilidade) {
                window.togglePainelAcessibilidade(e);
            }
        }
        function abrirPainelAcessibilidade(e) {
            if (e && e.stopPropagation) e.stopPropagation();
            if (window.togglePainelAcessibilidade && window.togglePainelAcessibilidade !== togglePainelAcessibilidade) {
                window.togglePainelAcessibilidade(e);
            }
        }
    </script>
    <script src="assets/fundo-animado.js?v=20260925-v4"></script>
    <script src="notificacoes.js?v=20260927-v2" onerror="if(!window.togglePainelAcessibilidade||window.togglePainelAcessibilidade===togglePainelAcessibilidade){var s=document.createElement('script');s.src='assets/notificacoes.js?v=20260927-v2';document.body.appendChild(s);}"></script>
    <script src="assets/transicao.js?v=20260926-v1"></script>
</body>
</html>