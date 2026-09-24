<?php
require_once 'conexao.php';

$usuarioLogado = null;
if (isset($_SESSION['usuario_id'])) {
    $stmt = $pdo->prepare("SELECT id, nome, email, foto_perfil, videos_assistidos, ultimo_video_data FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuarioLogado = $stmt->fetch();
    if (!$usuarioLogado) {
        session_destroy();
        $usuarioLogado = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if ($_POST['acao'] === 'editar_conta') {
        $novoNome = trim($_POST['nome']);
        $novoEmail = trim($_POST['email']);
        $novaFoto = $_POST['foto_base64'] ?? null;
        if (!empty($novoNome) && !empty($novoEmail)) {
            // Atualiza sempre a foto, permitindo que ela seja removida (vazia)
            $stmtUp = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, foto_perfil = ? WHERE id = ?");
            $stmtUp->execute([$novoNome, $novoEmail, $novaFoto, $_SESSION['usuario_id']]);
            $_SESSION['usuario_nome'] = $novoNome;
        }
        header("Location: Perfil.php?sucesso=1");
        exit;
    }
    if ($_POST['acao'] === 'apagar_conta') {
        $stmtDel = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmtDel->execute([$_SESSION['usuario_id']]);
        session_destroy();
        header("Location: Comeco.php");
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

$jsonDiariosData = json_encode($diariosPorData);
$jsonGraficoAno = json_encode($diariosPorAnoMes);
$maxEmocao = max(1, max($emocoesGlobaisLista));
$coresEmocoes = ['Irritado' => '#eab8b8', 'Ansioso' => '#ffcc99', 'Feliz' => '#fff1a0', 'Calmo' => '#b5ff99', 'Triste' => '#9bd3ff', 'Amoroso' => '#ff99e6'];
$abrevEmocoes = ['Irritado' => 'Irri.', 'Ansioso' => 'Ansi.', 'Feliz' => 'Feli.', 'Calmo' => 'Calm.', 'Triste' => 'Tris.', 'Amoroso' => 'Amor.'];
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
        }

        #cardSuaConta {
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body.modo-edicao #cardSuaConta {
            position: relative;
            z-index: 2100; /* Acima de tudo, inclusive da navbar */
            transform: scale(1.02);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
        }

        /* Esconde outros cards e elementos quando editando */
        body.modo-edicao .conteudo-site > *:not(#cardSuaConta) {
            opacity: 0;
            pointer-events: none;
            transform: translateY(20px);
        }

        /* Ajuste na Navbar durante edição para não sobrepor o foco */
        body.modo-edicao .nav-container-global {
            opacity: 0.3;
            pointer-events: none;
            filter: blur(5px);
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

        .senha-row .conta-input {
            max-width: 180px;
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
            background-image: url('assets/HELPFULL.png');
            background-size: cover;
            background-position: center;
            border-radius: 35px;
            padding: 30px;
            position: relative;
            margin-top: 10px;
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
            overflow: hidden;
        }

        body.modo-edicao .navbar-topo,
        body.modo-edicao .conteudo-site {
            z-index: 1600;
            position: relative;
        }

        body.modo-edicao #cardSuaConta {
            z-index: 1600;
            position: relative;
            transform: scale(1.02);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            background: #ffffff;
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

            .senha-row .conta-input {
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

            body.modo-edicao .senha-row .btn-sair {
                display: none !important;
            }

            body.modo-edicao .senha-row .btn-salvar,
            body.modo-edicao .senha-row .btn-apagar {
                display: inline-flex !important;
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
    </style>
    <link rel="stylesheet" href="assets/acessibilidade.css?v=20260916-v3">
</head>

<body>
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
            <button type="button" class="btn-acessibilidade" id="btnAcessibilidade" onclick="togglePainelAcessibilidade()" aria-label="Abrir opções de acessibilidade" aria-expanded="false">
                <span class="material-symbols-rounded" aria-hidden="true">settings</span>
            </button>

            <button class="btn-notificacao-separado" id="btnNotificacaoDetached" onclick="togglePainelNotificacoes()">
                <svg viewBox="0 0 24 24">
                    <path
                        d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z" />
                </svg>
            </button>
        </div>

        <div class="painel-notificacoes" id="painelNotificacoes">
            <div class="painel-header-top">
                <h3>Notificações</h3>
                <button class="btn-fechar-painel" onclick="togglePainelNotificacoes()">×</button>
            </div>
            <div class="painel-actions">
                <button class="btn-limpar-pill" onclick="limparNotificacoes()">Limpar</button>
            </div>
            <div class="lista-notificacoes" id="containerListaNotificacoes">
                <p style="font-size: 0.8rem; text-align: left; opacity: 0.6;">Nenhuma notificação nova.</p>
            </div>
        </div>
    </div>

    <div class="conteudo-site">

        <div class="card-perfil" id="cardSuaConta">
            <h2 class="titulo-secao">Sua Conta:</h2>
            <form class="conta-grid" method="POST" id="formConta">
                <input type="hidden" name="acao" id="acaoConta" value="editar_conta">
                <input type="hidden" name="foto_base64" id="fotoBase64Input" value="<?= $fotoPerfilDb ?>">

                <div class="conta-form">
                    <label>Nome:</label>
                    <input type="text" name="nome" class="conta-input campo-editavel"
                        value="<?= htmlspecialchars($usuarioLogado['nome']) ?>" readonly required>

                    <label>Email:</label>
                    <input type="email" name="email" class="conta-input campo-editavel"
                        value="<?= htmlspecialchars($usuarioLogado['email']) ?>" readonly required>

                    <label>Senha:</label>
                    <div class="senha-row">
                        <input type="password" class="conta-input" value="******" readonly disabled>
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
            <div class="meta-item <?= $diarioHoje ? 'concluida' : '' ?>">
                <div class="meta-checkbox"><svg class="check-icon" viewBox="0 0 24 24">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg></div>
                <div class="meta-texto">Anotar uma pequena vitória de hoje no Diário (por menor que seja).</div>
            </div>
            <div class="meta-item <?= $usouChatHoje ? 'concluida' : '' ?>">
                <div class="meta-checkbox"><svg class="check-icon" viewBox="0 0 24 24">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg></div>
                <div class="meta-texto">Dar um 'oi' para o Helpy e desabafar por 2 minutinhos.</div>
            </div>
            <div class="meta-item <?= $emocaoHoje ? 'concluida' : '' ?>">
                <div class="meta-checkbox"><svg class="check-icon" viewBox="0 0 24 24">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg></div>
                <div class="meta-texto">Registrar a emoção que estou sentindo agora no meu Diário.</div>
            </div>
            <div class="meta-item <?= $assistiuVideoHoje ? 'concluida' : '' ?>">
                <div class="meta-checkbox"><svg class="check-icon" viewBox="0 0 24 24">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg></div>
                <div class="meta-texto">Tirar 1 minutinho para assistir a um vídeo de respiração na aba Adicionais</div>
            </div>
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
    </div>

    <div class="secao-branca">
        <div class="secao-branca-conteudo">
            <footer class="rodape-simples">
                <strong>HELPFULL</strong>
                <div class="contato-info">Entre em contato:<br><br>Email:<br>Intagram:<br>WhatsApp:</div>
            </footer>
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
        const detalhesDoBanco = <?= $jsonDiariosData ?>;
        const graficoAnoBanco = <?= $jsonGraficoAno ?>;
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
            const inputs = document.querySelectorAll('.campo-editavel');
            const card = document.getElementById('cardSuaConta');

            if (ativar) {
                window.scrollTo({ top: 0, behavior: 'smooth' });
                body.classList.add('modo-edicao');
                btnSair.style.display = 'none';
                btnEditar.textContent = 'Cancelar';
                btnEditar.style.background = '#888';
                btnEditar.style.color = '#fff';
                btnEditar.onclick = () => {
                    const placeholder = document.getElementById('placeholderFoto');
                    const initialFoto = "<?= $fotoPerfilDb ?>";
                    placeholder.style.backgroundImage = initialFoto !== "" ? `url(${initialFoto})` : 'none';
                    document.getElementById('fotoBase64Input').value = initialFoto;
                    if (initialFoto !== "") {
                        placeholder.classList.add('tem-foto');
                        document.getElementById('placeholderIcon').style.display = 'none';
                    } else {
                        placeholder.classList.remove('tem-foto');
                        document.getElementById('placeholderIcon').style.display = 'block';
                    }
                    toggleEdicao(false);
                };
                btnSalvar.style.display = 'inline-flex';
                btnApagar.style.display = 'inline-flex';
                inputs.forEach(input => {
                    input.removeAttribute('readonly');
                    input.style.background = '#fff';
                    input.style.boxShadow = 'inset 0 2px 5px rgba(0,0,0,0.05)';
                });
            } else {
                body.classList.remove('modo-edicao');
                btnSair.style.display = 'inline-flex';
                btnEditar.textContent = 'Editar';
                btnEditar.style.background = '#bce0e6';
                btnEditar.style.color = '#444';
                btnEditar.onclick = () => toggleEdicao(true);
                btnSalvar.style.display = 'none';
                btnApagar.style.display = 'none';
                inputs.forEach(input => {
                    input.setAttribute('readonly', true);
                    input.style.background = '';
                    input.style.boxShadow = '';
                });
            }
        }

        function confirmarApagar() { if (confirm("Tem certeza absoluta que deseja apagar sua conta? Todo o seu histórico no HelpFull será perdido para sempre.")) { document.getElementById('acaoConta').value = 'apagar_conta'; document.getElementById('formConta').submit(); } }

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

        window.onload = () => {
            desenharCalendario(); renderGraficoDiarios();
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
        function abrirPainelAcessibilidade(e) {
            if (e && e.stopPropagation) e.stopPropagation();
            if (window.togglePainelAcessibilidade) {
                window.togglePainelAcessibilidade(e);
            }
        }
    </script>
    <script src="notificacoes.js?v=20260916-v2"></script>
</body>
</html>