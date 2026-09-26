<?php
// Endpoint AJAX para buscar notícias e artigos de saúde em portais reais
if (isset($_GET['acao']) && $_GET['acao'] === 'buscar_noticias') {
    header('Content-Type: application/json; charset=utf-8');
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    if (empty($query)) {
        echo json_encode([]);
        exit;
    }

    $url = 'https://news.google.com/rss/search?q=' . urlencode($query . ' saúde mental') . '&hl=pt-BR&gl=BR&ceid=BR:pt-419';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $items = [];
    if ($response && $httpCode === 200) {
        $xml = @simplexml_load_string($response);
        if ($xml && isset($xml->channel->item)) {
            foreach ($xml->channel->item as $item) {
                $title = (string)$item->title;
                $source = (string)$item->source;
                $link = (string)$item->link;
                
                if (!empty($source)) {
                    $title = preg_replace('/\s*-\s*' . preg_quote($source, '/') . '$/i', '', $title);
                }

                $items[] = [
                    'titulo' => trim($title),
                    'link' => trim($link),
                    'fonte' => trim($source) ?: 'Portal de Saúde',
                ];
                if (count($items) >= 4) break;
            }
        }
    }
    echo json_encode($items, JSON_UNESCAPED_UNICODE);
    exit;
}

// Endpoint AJAX para buscar vídeos no YouTube
if (isset($_GET['acao']) && $_GET['acao'] === 'buscar_videos_yt') {
    header('Content-Type: application/json; charset=utf-8');
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    if (empty($query)) {
        echo json_encode([]);
        exit;
    }

    $url = 'https://www.youtube.com/results?search_query=' . urlencode($query . ' saúde mental autocuidado');

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    $html = curl_exec($ch);
    curl_close($ch);

    $vids = [];
    if ($html && preg_match('/var ytInitialData = ({.*?});<\/script>/s', $html, $m)) {
        $data = json_decode($m[1], true);
        $sections = $data['contents']['twoColumnSearchResultsRenderer']['primaryContents']['sectionListRenderer']['contents'] ?? [];
        foreach ($sections as $section) {
            $contents = $section['itemSectionRenderer']['contents'] ?? [];
            foreach ($contents as $item) {
                if (isset($item['videoRenderer'])) {
                    $v = $item['videoRenderer'];
                    $videoId = $v['videoId'] ?? '';
                    $title = $v['title']['runs'][0]['text'] ?? '';
                    $channel = $v['ownerText']['runs'][0]['text'] ?? '';
                    if ($videoId && $title) {
                        $vids[] = [
                            'id' => $videoId,
                            'titulo' => $title,
                            'canal' => $channel,
                            'thumb' => "https://img.youtube.com/vi/{$videoId}/mqdefault.jpg",
                            'plataforma' => 'youtube',
                            'fonte' => 'YouTube'
                        ];
                    }
                    if (count($vids) >= 4) break 2;
                }
            }
        }
    }
    echo json_encode($vids, JSON_UNESCAPED_UNICODE);
    exit;
}

require_once 'conexao.php';

// Suporte para registrar conclusão de vídeo quando chamado via Atividades.php
if (isset($_POST['acao']) && $_POST['acao'] === 'marcar_video_visto') {
    header('Content-Type: application/json; charset=utf-8');
    if (!isset($_SESSION['usuario_id'])) {
        echo json_encode(['sucesso' => false, 'erro' => 'Não autenticado']);
        exit;
    }
    $id = $_SESSION['usuario_id'];
    $hoje = date('Y-m-d');
    try {
        $stmtCheck = $pdo->prepare("SELECT ultimo_video_data FROM usuarios WHERE id = ?");
        $stmtCheck->execute([$id]);
        $ultimo = $stmtCheck->fetchColumn();
        $jaConcluiu = ($ultimo === $hoje);

        $stmt = $pdo->prepare("UPDATE usuarios SET videos_assistidos = COALESCE(videos_assistidos, 0) + 1, ultimo_video_data = ? WHERE id = ?");
        $stmt->execute([$hoje, $id]);
        
        echo json_encode([
            'sucesso' => true, 
            'meta_video_concluida' => !$jaConcluiu
        ]);
    } catch (PDOException $e) {
        echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
    }
    exit;
}

$usuarioLogado = null;
if (isset($_SESSION['usuario_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuarioLogado = $stmt->fetch();
    if (!$usuarioLogado) {
        session_destroy();
        $usuarioLogado = null;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HelpFull - Atividades</title>
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

        .conteudo-site {
            width: 100%;
            max-width: 1050px;
            box-sizing: border-box;
        }

        .cabecalho-imagem {
            background-color: #F3F3F3;
            border-radius: 30px;
            padding: 40px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .cabecalho-imagem > *:not(.fundo-animado-canvas) {
            position: relative;
            z-index: 2;
        }

        .fundo-animado-canvas.fundo-animado-bloco {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 30px;
            z-index: 0;
            pointer-events: none;
            display: block;
            filter: blur(28px);
            -webkit-filter: blur(28px);
            transform: scale(1.08);
            transform-origin: center center;
        }

        .sobre-content {
            display: none;
            animation: fadeIn 0.4s ease forwards;
        }

        .sobre-content.ativo {
            display: block;
        }

        .conteudo-site {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            padding: 100px 20px 60px 20px;
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        /* SISTEMA DE NOTIFICAÇÃO E NAVBAR (UNIVERSAL) */
        .nav-wrapper-global {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2000;
            display: flex;
            align-items: center;
            gap: 25px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .navbar-topo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 30px;
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            padding: 12px 30px;
            border-radius: 50px;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: all 0.4s ease;
        }

        .navbar-topo.scrolled {
            background: rgba(255, 255, 255, 0.65);
            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.12);
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

        .nav-entrar-btn {
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            text-decoration: none;
            color: #2b7a8c;
            font-size: 0.95rem;
            white-space: nowrap;
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
            right: -100px;
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
            right: -100px;
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

        /* ESPECÍFICOS ATIVIDADES */
        .nav-tabs-side {
            width: 0;
            opacity: 0;
            transform: scale(0.8) translateX(-20px);
            pointer-events: none;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
        }

        .nav-tabs-side.show {
            width: 220px;
            opacity: 1;
            transform: scale(1) translateX(0);
            pointer-events: auto;
        }

        #tabs-original-container {
            margin-bottom: 25px;
            transition: opacity 0.4s ease, visibility 0.4s;
            display: inline-block;
        }

        #tabs-original-container.hiding {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .tabs-capsula {
            display: inline-flex;
            position: relative;
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            padding: 6px;
            border-radius: 50px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.5);
            z-index: 1;
            white-space: nowrap;
        }

        .tab-slider {
            position: absolute;
            top: 6px;
            left: 6px;
            height: calc(100% - 12px);
            width: 100px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55), width 0.3s ease;
            z-index: -1;
        }

        .tab-btn {
            padding: 10px 25px;
            border-radius: 50px;
            border: none;
            background: transparent;
            font-weight: 800;
            font-size: 1rem;
            color: #444;
            cursor: pointer;
            transition: color 0.3s ease;
            font-family: inherit;
            position: relative;
            z-index: 2;
        }

        .tab-btn.ativo {
            color: #1a1a1a;
        }

        .tab-content {
            display: none;
            flex-direction: column;
            gap: 25px;
            animation: fadeIn 0.4s ease forwards;
        }

        .tab-content.ativo {
            display: flex;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-sobre,
        .card-artigo,
        .secao-video-header {
            background: rgba(240, 240, 240, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.9);
        }

        .card-sobre {
            padding: 30px 40px;
        }

        .card-sobre h3 {
            font-size: 1.2rem;
            font-weight: 800;
            margin-bottom: 10px;
            color: #1a1a1a;
        }

        .card-sobre p {
            font-size: 1.05rem;
            line-height: 1.6;
            color: #333;
            font-weight: 500;
        }

        .card-artigo {
            padding: 40px;
        }

        .card-artigo h3 {
            font-size: 1.4rem;
            font-weight: 800;
            margin-bottom: 15px;
            color: #1a1a1a;
        }

        .card-artigo p {
            font-size: 1.05rem;
            line-height: 1.7;
            color: #444;
        }

        .secao-video {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .secao-video-header {
            padding: 30px 40px;
        }

        .secao-video-header h3 {
            font-size: 1.4rem;
            font-weight: 800;
            margin-bottom: 10px;
            color: #1a1a1a;
        }

        .secao-video-header p {
            font-size: 1.05rem;
            line-height: 1.6;
            color: #444;
        }

        .video-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .video-card {
            background-color: #dcdcdc;
            background-size: cover;
            background-position: center;
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 20px;
            aspect-ratio: 16/9;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            overflow: hidden;
        }

        .video-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.2);
            transition: background 0.3s;
        }

        .video-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .video-card:hover::before {
            background: rgba(0, 0, 0, 0.1);
        }

        .play-btn {
            position: relative;
            z-index: 2;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, background 0.3s;
        }

        .video-card:hover .play-btn {
            transform: scale(1.1);
            background: rgba(255, 255, 255, 1);
        }

        .play-btn svg {
            margin-left: 4px;
            fill: #1a1a1a;
        }

        /* LINK PARA O VÍDEO RELACIONADO (ESTILO HERO INÍCIO) */
        .link-card-video {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 18px;
            font-weight: 800;
            font-size: 1.05rem;
            text-decoration: none;
            color: #2b7a8c;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .link-card-video:hover {
            color: #1e5966;
            transform: translateX(4px);
        }

        .link-card-video .seta {
            display: inline-block;
            transition: transform 0.25s ease;
        }

        .link-card-video:hover .seta {
            transform: translateX(4px);
        }

        /* ANIMAÇÃO DE DESTAQUE NO VÍDEO DESTINO */
        @keyframes destaqueVideoPulse {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(43, 122, 140, 0.85);
            }
            35% {
                transform: scale(1.045);
                box-shadow: 0 0 0 16px rgba(43, 122, 140, 0.4), 0 12px 35px rgba(43, 122, 140, 0.5);
            }
            70% {
                transform: scale(1.02);
                box-shadow: 0 0 0 8px rgba(43, 122, 140, 0.25), 0 8px 25px rgba(43, 122, 140, 0.35);
            }
            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(43, 122, 140, 0);
            }
        }

        .video-card.video-card-destaque {
            animation: destaqueVideoPulse 1.8s cubic-bezier(0.25, 1, 0.5, 1) 2 !important;
            z-index: 10;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(15px);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.4s ease;
        }

        .modal-overlay.ativo {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-content {
            position: relative;
            width: 85%;
            max-width: 800px;
            aspect-ratio: 16/9;
            background: #000;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            transform: scale(0.95);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .modal-overlay.ativo .modal-content {
            transform: scale(1);
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            z-index: 10000;
            color: #fff;
        }

        .modal-close:hover {
            background: white;
            transform: scale(1.1);
        }

        .secao-branca {
            background-color: #ffffff;
            width: 100%;
            position: relative;
            z-index: 10;
            margin-top: auto;
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
            font-weight: 500;
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
            .nav-links {
                display: none;
            }

            .nav-wrapper-global {
                width: 100%;
            }

            .navbar-topo {
                padding: 12px 25px;
                width: calc(100% - 40px);
                margin: 0 auto;
                justify-content: space-between;
            }
            .nav-logo { cursor: pointer; }

            .nav-tabs-side {
                display: none;
            }

            .conteudo-site {
                padding-top: 110px;
            }

            .video-grid {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 95%;
                border-radius: 15px;
            }

            .card-sobre,
            .card-artigo,
            .secao-video-header {
                padding: 25px;
            }

            .modal-close {
                top: -45px;
                right: 5px;
            }

            #tabs-original-container {
                max-width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 5px;
            }

            .btn-notificacao-separado { right: 10px; }
            .painel-notificacoes { right: 10px; width: calc(100vw - 40px); max-width: 320px; }
            .toast-notificacao { width: 92%; max-width: 380px; }
        }

        @media (max-width: 480px) {
            .tab-btn { padding: 8px 16px; font-size: 0.9rem; }
            .cabecalho-imagem { padding: 25px 20px; border-radius: 20px; }
            .card-sobre, .card-artigo, .secao-video-header { padding: 20px; border-radius: 20px; }
        }

        /* === BARRA DE BUSCA === */
        .busca-wrapper {
            display: flex;
            flex-direction: column;
            gap: 15px;
            width: 100%;
        }

        .busca-row {
            display: flex;
            background: rgba(255,255,255,0.65);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255,255,255,0.85);
            border-radius: 50px;
            padding: 5px 6px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.06);
            gap: 6px;
            align-items: center;
            box-sizing: border-box;
            width: 100%;
            transition: box-shadow 0.3s;
        }

        .busca-row:focus-within {
            box-shadow: 0 8px 30px rgba(43,122,140,0.15);
            border-color: rgba(43,122,140,0.35);
        }

        .busca-icone {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-left: 6px;
            color: #888;
        }

        .busca-input {
            flex: 1 1 0;
            min-width: 0;
            width: 100%;
            border: none;
            background: transparent;
            font-family: inherit;
            font-size: 0.92rem;
            font-weight: 600;
            color: #333;
            outline: none;
            padding: 8px 4px;
        }

        .busca-input::placeholder { color: #aaa; }

        .busca-btn {
            flex-shrink: 0;
            height: 38px;
            padding: 0 18px;
            border-radius: 38px;
            border: none;
            background: #2b7a8c;
            color: #fff;
            font-weight: 800;
            font-size: 0.88rem;
            cursor: pointer;
            white-space: nowrap;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.25s;
            box-shadow: 0 4px 12px rgba(43,122,140,0.25);
        }

        .busca-btn:hover {
            background: #236878;
            transform: scale(1.04);
        }

        .busca-resultados {
            display: none;
            flex-direction: column;
            gap: 14px;
            animation: fadeIn 0.35s ease forwards;
        }

        .busca-resultados.visivel { display: flex; }

        /* Resultados de texto (Wikipedia) */
        .busca-resultado-texto {
            background: #ffffff;
            border-radius: 20px;
            padding: 22px 28px;
            border: 1px solid #ebebeb;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
            text-decoration: none;
            color: inherit;
            display: block;
            transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
        }

        .busca-resultado-texto:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 28px rgba(0,0,0,0.08);
            border-color: rgba(43,122,140,0.2);
        }

        .busca-resultado-texto h4 {
            font-size: 1.05rem;
            font-weight: 800;
            color: #1a1a1a;
            margin-bottom: 8px;
        }

        .busca-resultado-texto p {
            font-size: 0.9rem;
            line-height: 1.55;
            color: #555;
            font-weight: 500;
        }

        .busca-resultado-fonte {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.72rem;
            font-weight: 700;
            color: #fff;
            margin-top: 12px;
            letter-spacing: 0.4px;
            padding: 4px 12px;
            border-radius: 20px;
            background: #607d8b;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }

        .busca-resultado-fonte.wiki { background: #1a5276; }
        .busca-resultado-fonte.portal { background: #00897b; }
        .busca-resultado-fonte.artigo { background: #6a1b9a; }
        .busca-resultado-fonte.academico { background: #1565c0; }
        .busca-resultado-fonte.ddg  { background: #de5833; }
        .busca-resultado-fonte.quote { background: #5c6bc0; }

        .busca-secao-titulo {
            font-size: 0.75rem;
            font-weight: 800;
            color: #777;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 0 4px;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Subtítulo "Textos separados por nós" */
        .divisor-curado {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 25px 0 12px 0;
            padding: 0 5px;
        }

        .divisor-curado-linha {
            flex: 1;
            height: 2px;
            background: linear-gradient(to right, transparent, rgba(43, 122, 140, 0.25), transparent);
            border-radius: 2px;
        }

        .divisor-curado-texto {
            font-size: 0.82rem;
            font-weight: 800;
            color: #2b7a8c;
            text-transform: uppercase;
            letter-spacing: 1px;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(43, 122, 140, 0.08);
            padding: 6px 16px;
            border-radius: 30px;
            border: 1px solid rgba(43, 122, 140, 0.15);
        }

        .divisor-curado-texto svg {
            color: #2b7a8c;
            flex-shrink: 0;
        }

        /* Resultados de vídeo (YouTube) */
        .video-busca-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .video-busca-card {
            border-radius: 20px;
            overflow: hidden;
            background: #dcdcdc;
            cursor: pointer;
            position: relative;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        }

        .video-busca-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.12);
        }

        .video-busca-thumb {
            width: 100%;
            aspect-ratio: 16/9;
            object-fit: cover;
            display: block;
        }

        .video-busca-info {
            background: #fff;
            padding: 12px 14px;
            flex: 1;
        }

        .video-busca-titulo {
            font-size: 0.82rem;
            font-weight: 800;
            color: #1a1a1a;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .video-busca-canal {
            font-size: 0.72rem;
            font-weight: 600;
            color: #888;
        }

        .video-busca-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
        }

        .video-busca-badge {
            font-size: 0.65rem;
            font-weight: 800;
            color: #fff;
            padding: 3px 8px;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .video-busca-badge.youtube { background: #cc0000; }
        .video-busca-badge.dailymotion { background: #0066dc; }
        .video-busca-badge.vimeo { background: #1ab7ea; }

        .video-busca-play {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -60%);
            width: 48px;
            height: 48px;
            background: rgba(255,255,255,0.92);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(0,0,0,0.15);
            transition: transform 0.3s, background 0.3s;
            pointer-events: none;
        }

        .video-busca-card:hover .video-busca-play {
            transform: translate(-50%, -60%) scale(1.12);
            background: #fff;
        }

        .busca-loader {
            display: none;
            justify-content: center;
            align-items: center;
            padding: 30px;
            gap: 10px;
            font-weight: 700;
            color: #888;
            font-size: 0.9rem;
        }

        .busca-loader.visivel { display: flex; }

        .busca-spinner {
            width: 22px;
            height: 22px;
            border: 3px solid rgba(0,0,0,0.08);
            border-top-color: #2b7a8c;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        .busca-vazio {
            text-align: center;
            padding: 30px;
            font-weight: 700;
            color: #aaa;
            font-size: 0.9rem;
        }

        .busca-label {
            font-size: 0.78rem;
            font-weight: 800;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 0 5px;
        }

        @media (max-width: 600px) {
            .video-busca-grid { grid-template-columns: 1fr; }
        }
    </style>
    <link rel="stylesheet" href="assets/acessibilidade.css?v=20260925-v20">
</head>

<body>

    <div class="nav-wrapper-global">
        <nav class="navbar-topo" id="mainNavbar">
            <a href="inicio.php" class="nav-logo" style="display: flex; align-items: center;">HELPFULL <span class="nav-seta-dropdown"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg></span></a>
            <ul class="nav-links">
                <li><a href="Diario.php">Diário</a></li>
                <li><a href="Comunidade.php">Comunidade</a></li>
                <li><a href="ChatBOT.php">Helpy</a></li>
                <li><a href="Atividades.php" class="ativo">Adicionais</a></li>
            </ul>
            <div class="nav-dropdown-mobile" id="navDropdownMobile">
                <a href="inicio.php">Início</a>
                <a href="Diario.php">Diário</a>
                <a href="Comunidade.php">Comunidade</a>
                <a href="ChatBOT.php">Helpy</a>
                <a href="Atividades.php" class="ativo">Adicionais</a>
                <a href="Perfil.php">Perfil</a>
                <a href="javascript:void(0)" class="btn-abrir-acessibilidade" onclick="abrirPainelAcessibilidadeMobile(event);">Configurações</a>
            </div>
            <?php if ($usuarioLogado): ?>
                <?php $fotoPerfilDb = !empty($usuarioLogado['foto_perfil']) ? $usuarioLogado['foto_perfil'] : ''; ?>
                <a href="Perfil.php" style="text-decoration: none;">
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
            <?php else: ?>
                <a href="Comeco.php" class="nav-entrar-btn">Entrar</a>
            <?php endif; ?>
        </nav>

        <button class="btn-notificacao-separado" id="btnNotificacaoDetached" onclick="togglePainelNotificacoes()">
            <svg viewBox="0 0 24 24">
                <path
                    d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z" />
            </svg>
        </button>

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

        <div class="nav-tabs-side" id="navTabsSide"></div>
    </div>

    <div class="conteudo-site">
        <div class="cabecalho-imagem" id="cabecalhoFundo">
            <canvas class="fundo-animado-canvas fundo-animado-bloco sem-mouse" data-mouse="false"></canvas>
            <div id="tabs-original-container">
                <div class="tabs-capsula" id="tabsElement">
                    <div class="tab-slider" id="tabSlider"></div>
                    <button class="tab-btn ativo" data-tab="textos" onclick="showTab('textos', this)">Textos</button>
                    <button class="tab-btn" data-tab="videos" onclick="showTab('videos', this)">Videos</button>
                </div>
            </div>

            <div id="sobre-textos" class="sobre-content ativo">
                <div class="card-sobre">
                    <h3>Sobre essa aba:</h3>
                    <p>Aqui reunimos materiais para apoiar sua jornada de autoconhecimento. Explore artigos rápidos,
                        técnicas de relaxamento e reflexões selecionadas para ajudar a trazer mais clareza, calma e
                        leveza ao seu dia a dia.</p>
                </div>
            </div>
            <div id="sobre-videos" class="sobre-content">
                <div class="card-sobre">
                    <h3>Sobre essa aba:</h3>
                    <p>Aqui reunimos conteúdos visuais para apoiar sua jornada de bem-estar. Assista a vídeos curtos com
                        técnicas de respiração, exercícios de foco e reflexões guiadas para ajudar a acalmar a mente e
                        trazer mais leveza ao seu dia.</p>
                </div>
            </div>
        </div>

        <div id="tab-textos" class="tab-content ativo">
            <!-- Barra de busca textos -->
            <div class="busca-wrapper">
                <div class="busca-label">🔍 Pesquisar conteúdos</div>
                <div class="busca-row">
                    <div class="busca-icone">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </div>
                    <input type="text" class="busca-input" id="busca-texto-input"
                        placeholder="Ex: ansiedade, meditação, autocuidado..."
                        onkeydown="if(event.key==='Enter') buscarTextos()">
                    <button class="busca-btn" onclick="buscarTextos()">Buscar</button>
                </div>
                <div class="busca-loader" id="busca-texto-loader">
                    <div class="busca-spinner"></div> Buscando artigos...
                </div>
                <div class="busca-resultados" id="busca-texto-resultados"></div>
            </div>

            <!-- Subtítulo "Textos separados por nós" antes dos textos prontos da plataforma -->
            <div class="divisor-curado" id="divisor-textos-curados">
                <div class="divisor-curado-linha"></div>
                <div class="divisor-curado-texto">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
                    </svg>
                    Textos separados por nós
                </div>
                <div class="divisor-curado-linha"></div>
            </div>

            <div class="card-artigo">
                <h3>A Técnica 4-7-8 para Alívio Imediato</h3>
                <p>Quando a mente acelera, sua respiração é sua maior aliada. A técnica 4-7-8 atua como um calmante
                    natural para o sistema nervoso. Funciona assim: inspire silenciosamente pelo nariz contando até 4;
                    prenda a respiração contando até 7; e expire completamente pela boca contando até 8. Repita esse
                    ciclo quatro vezes. Essa prática simples ajuda a desacelerar os batimentos cardíacos e traz a mente
                    de volta para o momento presente, sendo excelente para praticar em momentos de tensão ou antes de
                    dormir.</p>
                <a href="javascript:void(0)" onclick="irParaVideo('video-fmBRuuQ0Gs8')" class="link-card-video">
                    Praticar respiração guiada com o vídeo <span class="seta">→</span>
                </a>
            </div>
            <div class="card-artigo">
                <h3>O poder das pausas e da autocompaixão</h3>
                <p>É muito comum nos cobrarmos excessivamente quando as coisas não saem como o planejado. A
                    autocompaixão não é ter pena de si mesmo, mas sim se tratar com a mesma gentileza que você trataria
                    um amigo em dificuldade. Se o dia foi pesado, não se culpe por não ter tido o rendimento que
                    gostaria. Reconheça seu esforço, permita-se pausar e lembre-se de que o cuidado com a mente envolve,
                    acima de tudo, aceitar nossos momentos de descanso. Um dia difícil não define sua jornada.</p>
                <a href="javascript:void(0)" onclick="irParaVideo('video-LsgpZ6IbGx0')" class="link-card-video">
                    Assistir vídeo de pausa e presença mental <span class="seta">→</span>
                </a>
            </div>
            <div class="card-artigo">
                <h3>Como o estresse atua no corpo (e como desativá-lo)</h3>
                <p>Quando nos sentimos sob pressão ou ameaçados, nosso cérebro aciona o modo de "luta ou fuga", liberando hormônios como cortisol e adrenalina. Isso acelera os batimentos, tensiona os músculos e bloqueia a capacidade de pensar com clareza. Para enviar ao cérebro o sinal de que o perigo já passou, pequenas práticas corporais são poderosas: relaxar os ombros, soltar o maxilar e realizar exalações longas e audíveis. Ao acalmar o corpo físico primeiro, a mente gradualmente compreende que está segura, permitindo que a racionalidade e a serenidade voltem a liderar.</p>
                <a href="javascript:void(0)" onclick="irParaVideo('video-xI_oUWoofJ0')" class="link-card-video">
                    Assistir ao vídeo sobre estresse e mente <span class="seta">→</span>
                </a>
            </div>
            <div class="card-artigo">
                <h3>Quebrando o ciclo do excesso de pensamentos</h3>
                <p>Ficar repassando a mesma preocupação mentalmente dá a falsa impressão de estarmos resolvendo um problema, mas na verdade apenas desgasta nossa energia emocional. Quando você perceber que está preso num turbilhão de pensamentos, use a regra da ancoragem: nomeie mentalmente três coisas que você consegue ver ao seu redor, dois sons que consegue ouvir e uma textura que consegue tocar com as mãos. Trazer a atenção de volta para os sentidos físicos interrompe o piloto automático da mente e impede que medos futuros sequestrem a sua paz no presente.</p>
                <a href="javascript:void(0)" onclick="irParaVideo('video--4igBhtIlhk')" class="link-card-video">
                    Assistir ao vídeo sobre excesso de pensamentos <span class="seta">→</span>
                </a>
            </div>
            <div class="card-artigo">
                <h3>Acolhendo o que você sente sem julgamentos</h3>
                <p>Sentir tristeza, cansaço, medo ou irritação não significa que você esteja falhando. As emoções são mensagens biológicas que pedem atenção, e tentar suprimi-las à força costuma apenas torná-las mais intensas. Quando uma sensação incômoda surgir, experimente dar um nome a ela sem se criticar: "estou sentindo frustração agora". Observe essa sensação no corpo como quem assiste a uma onda no oceano — ela cresce, atinge seu pico e, se você não lutar contra ela, naturalmente se dissipa. Aprender a conviver com o desconforto é um dos passos mais libertadores da saúde emocional.</p>
                <a href="javascript:void(0)" onclick="irParaVideo('video-ySLhZfsagDA')" class="link-card-video">
                    Assistir ao vídeo sobre emoções e bem-estar <span class="seta">→</span>
                </a>
            </div>
        </div>

        <div id="tab-videos" class="tab-content">
            <!-- Barra de busca vídeos -->
            <div class="busca-wrapper">
                <div class="busca-label">🎬 Pesquisar vídeos e práticas</div>
                <div class="busca-row">
                    <div class="busca-icone">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="#2b7a8c">
                            <polygon points="5 3 19 12 5 21 5 3"></polygon>
                        </svg>
                    </div>
                    <input type="text" class="busca-input" id="busca-video-input"
                        placeholder="Ex: meditação guiada, técnicas de ansiedade..."
                        onkeydown="if(event.key==='Enter') buscarVideos()">
                    <button class="busca-btn" onclick="buscarVideos()">Buscar</button>
                </div>
                <div class="busca-loader" id="busca-video-loader">
                    <div class="busca-spinner"></div> Buscando vídeos...
                </div>
                <div class="busca-resultados" id="busca-video-resultados"></div>
            </div>

            <!-- Subtítulo "Vídeos separados por nós" antes dos vídeos prontos da plataforma -->
            <div class="divisor-curado" id="divisor-videos-curados">
                <div class="divisor-curado-linha"></div>
                <div class="divisor-curado-texto">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                    </svg>
                    Vídeos separados por nós
                </div>
                <div class="divisor-curado-linha"></div>
            </div>

            <div class="secao-video">
                <div class="secao-video-header">
                    <h3>Práticas de Respiração e Foco</h3>
                    <p>Técnicas de respiração guiada para reduzir a ansiedade e ancorar no momento presente. Práticas
                        curtas focadas apenas no controle do ar e relaxamento mental, ideais para acalmar os
                        pensamentos.</p>
                </div>
                <div class="video-grid">
                    <div class="video-card" id="video-aNXKjGFUlMs"
                        style="background-image: url('https://img.youtube.com/vi/aNXKjGFUlMs/maxresdefault.jpg');"
                        onclick="openVideo('aNXKjGFUlMs')">
                        <div class="play-btn"><svg viewBox="0 0 24 24" width="24" height="24">
                                <path d="M8 5v14l11-7z" />
                            </svg></div>
                    </div>
                    <div class="video-card" id="video-fmBRuuQ0Gs8"
                        style="background-image: url('https://img.youtube.com/vi/fmBRuuQ0Gs8/maxresdefault.jpg');"
                        onclick="openVideo('fmBRuuQ0Gs8')">
                        <div class="play-btn"><svg viewBox="0 0 24 24" width="24" height="24">
                                <path d="M8 5v14l11-7z" />
                            </svg></div>
                    </div>
                </div>
            </div>
            <div class="secao-video" id="secao-mente">
                <div class="secao-video-header">
                    <h3>Entendendo a Mente</h3>
                    <p>Vídeos educativos sobre como nossa mente processa emoções e a importância do descanso mental.
                        Perfeito para entender melhor seus próprios sentimentos de forma leve e didática.</p>
                </div>
                <div class="video-grid">
                    <div class="video-card" id="video-xI_oUWoofJ0"
                        style="background-image: url('https://img.youtube.com/vi/xI_oUWoofJ0/maxresdefault.jpg');"
                        onclick="openVideo('xI_oUWoofJ0')">
                        <div class="play-btn"><svg viewBox="0 0 24 24" width="24" height="24">
                                <path d="M8 5v14l11-7z" />
                            </svg></div>
                    </div>
                    <div class="video-card" id="video-LsgpZ6IbGx0"
                        style="background-image: url('https://img.youtube.com/vi/LsgpZ6IbGx0/maxresdefault.jpg');"
                        onclick="openVideo('LsgpZ6IbGx0')">
                        <div class="play-btn"><svg viewBox="0 0 24 24" width="24" height="24">
                                <path d="M8 5v14l11-7z" />
                            </svg></div>
                    </div>
                    <div class="video-card" id="video--4igBhtIlhk"
                        style="background-image: url('https://img.youtube.com/vi/-4igBhtIlhk/maxresdefault.jpg');"
                        onclick="openVideo('-4igBhtIlhk')">
                        <div class="play-btn"><svg viewBox="0 0 24 24" width="24" height="24">
                                <path d="M8 5v14l11-7z" />
                            </svg></div>
                    </div>
                    <div class="video-card" id="video-ySLhZfsagDA"
                        style="background-image: url('https://img.youtube.com/vi/ySLhZfsagDA/maxresdefault.jpg');"
                        onclick="openVideo('ySLhZfsagDA')">
                        <div class="play-btn"><svg viewBox="0 0 24 24" width="24" height="24">
                                <path d="M8 5v14l11-7z" />
                            </svg></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="videoModal" onclick="closeModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <button class="modal-close" onclick="closeModal(event)"><svg viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" width="24" height="24">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg></button>
            <div id="video-player-container" style="width: 100%; height: 100%;"></div>
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
        const wrapperGlobal = document.querySelector('.nav-wrapper-global');
        const mainNavbar = document.getElementById('mainNavbar');
        const cabecalhoFundo = document.getElementById('cabecalhoFundo');
        const containerOriginal = document.getElementById('tabs-original-container');
        const containerNav = document.getElementById('navTabsSide');
        const capsula = document.getElementById('tabsElement');
        let currentTabId = 'textos';

        window.addEventListener('scroll', () => {
            const rect = cabecalhoFundo.getBoundingClientRect();
            if (rect.bottom < 150) {
                if (!containerNav.classList.contains('show')) {
                    mainNavbar.classList.add('scrolled');
                    containerOriginal.classList.add('hiding');
                    containerNav.classList.add('show');
                    containerNav.appendChild(capsula);
                    setTimeout(() => reposicionarSlider(currentTabId), 50);
                }
            } else {
                if (containerNav.classList.contains('show')) {
                    mainNavbar.classList.remove('scrolled');
                    containerOriginal.classList.remove('hiding');
                    containerNav.classList.remove('show');
                    containerOriginal.appendChild(capsula);
                    setTimeout(() => reposicionarSlider(currentTabId), 50);
                }
            }
        });

        function reposicionarSlider(tabId) {
            const btnAtivo = document.querySelector(`.tab-btn[data-tab="${tabId}"]`);
            if (btnAtivo) {
                const slider = document.getElementById('tabSlider');
                slider.style.width = btnAtivo.offsetWidth + 'px';
                slider.style.transform = `translateX(${btnAtivo.offsetLeft - 6}px)`;
            }
        }

        function showTab(tabId, btnClicado) {
            currentTabId = tabId;
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('ativo'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('ativo'));
            document.querySelectorAll('.sobre-content').forEach(content => content.classList.remove('ativo'));
            if (!btnClicado) {
                btnClicado = document.querySelector(`.tab-btn[data-tab="${tabId}"]`);
            }
            if (btnClicado) btnClicado.classList.add('ativo');
            reposicionarSlider(tabId);
            const tabContent = document.getElementById('tab-' + tabId);
            if (tabContent) tabContent.classList.add('ativo');
            const sobreContent = document.getElementById('sobre-' + tabId);
            if (sobreContent) sobreContent.classList.add('ativo');
        }

        function irParaVideo(targetId) {
            const btnVideos = document.querySelector('.tab-btn[data-tab="videos"]');
            showTab('videos', btnVideos);

            setTimeout(() => {
                const elemento = document.getElementById(targetId);
                if (elemento) {
                    elemento.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    elemento.classList.remove('video-card-destaque');
                    void elemento.offsetWidth;
                    elemento.classList.add('video-card-destaque');

                    setTimeout(() => {
                        elemento.classList.remove('video-card-destaque');
                    }, 3600);
                }
            }, 120);
        }

        window.addEventListener('DOMContentLoaded', () => { setTimeout(() => { reposicionarSlider('textos'); }, 50); });

        function openVideo(videoId, plataforma = 'youtube') {
            const modal = document.getElementById('videoModal');
            const playerContainer = document.getElementById('video-player-container');
            let embedSrc = `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0`;
            if (plataforma === 'dailymotion') {
                embedSrc = `https://www.dailymotion.com/embed/video/${videoId}?autoplay=1`;
            }
            playerContainer.innerHTML = `<iframe width="100%" height="100%" src="${embedSrc}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
            modal.classList.add('ativo');
            
            function marcarVideoVisto(videoId) {
                const formData = new FormData();
                formData.append('acao', 'marcar_video_visto');

                fetch('Atividades.php', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.sucesso && data.meta_video_concluida) {
                            mostrarNotificacaoAtiva({
                                mensagem: "Meta concluída! Você assistiu a um vídeo hoje. 🍿",
                                intensidade: "baixa",
                                link: "Perfil.php",
                                textoBotao: "Ver Perfil"
                            });
                        }
                    })
                    .catch(error => console.error("Erro ao marcar vídeo:", error));
            }
            marcarVideoVisto(videoId);
        }

        function closeModal(event) {
            const modal = document.getElementById('videoModal');
            const playerContainer = document.getElementById('video-player-container');
            modal.classList.remove('ativo');
            setTimeout(() => { playerContainer.innerHTML = ''; }, 300);
        }

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

        // === BUSCA TEXTOS (Múltiplas fontes em paralelo: Portais de Saúde, Artigos Científicos, Acadêmicos e Wikipedia) ===
        async function buscarTextos() {
            const query = document.getElementById('busca-texto-input').value.trim();
            if (!query) return;

            const loader = document.getElementById('busca-texto-loader');
            const container = document.getElementById('busca-texto-resultados');

            loader.classList.add('visivel');
            container.classList.remove('visivel');
            container.innerHTML = '';

            const queryEnriquecida = query + ' saúde mental bem-estar';

            // Helper para criar card de resultado
            function criarCard(titulo, snippet, url, fonte, classeFonte) {
                const card = document.createElement('a');
                card.href = url;
                card.target = '_blank';
                card.rel = 'noopener noreferrer';
                card.className = 'busca-resultado-texto';
                card.innerHTML = `
                    <h4>${titulo}</h4>
                    <p>${snippet}</p>
                    <span class="busca-resultado-fonte ${classeFonte}">${fonte}</span>
                `;
                return card;
            }

            // Helper para reconstruir abstract do OpenAlex
            function reconstruirAbstract(invertedIndex) {
                if (!invertedIndex) return '';
                const words = [];
                for (const [word, positions] of Object.entries(invertedIndex)) {
                    for (const pos of positions) {
                        words[pos] = word;
                    }
                }
                return words.join(' ');
            }

            // Busca em paralelo nas 4 fontes distintas
            const [noticiasResult, crossrefResult, openAlexResult, wikiResult] = await Promise.allSettled([
                // 1. Portais de Notícias e Saúde brasileiros (G1, Veja Saúde, O Globo, VivaBem, Einstein, etc.)
                fetch(`Atividades.php?acao=buscar_noticias&q=${encodeURIComponent(query)}`)
                    .then(r => r.json()),

                // 2. Artigos Científicos e Médicos (Crossref: SciELO, Revistas de Psiquiatria e Medicina)
                fetch(`https://api.crossref.org/works?query=${encodeURIComponent(query + ' saúde mental')}&rows=3`)
                    .then(r => r.json()),

                // 3. Pesquisas e Repositórios Acadêmicos (OpenAlex em Português)
                fetch(`https://api.openalex.org/works?search=${encodeURIComponent(query)}&filter=language:pt&per_page=3`)
                    .then(r => r.json()),

                // 4. Wikipedia PT-BR (Conceitos e guias enciclopédicos)
                fetch(`https://pt.wikipedia.org/w/api.php?action=query&list=search&srsearch=${encodeURIComponent(queryEnriquecida)}&srlimit=2&srnamespace=0&format=json&origin=*`)
                    .then(r => r.json())
            ]);

            loader.classList.remove('visivel');

            let totalResultados = 0;

            // --- 1. Processa Portais de Notícias / Saúde ---
            if (noticiasResult.status === 'fulfilled' && Array.isArray(noticiasResult.value)) {
                noticiasResult.value.forEach(item => {
                    if (item.titulo && item.link) {
                        const fonteNome = item.fonte || 'Portal de Saúde';
                        container.appendChild(criarCard(
                            item.titulo,
                            `Matéria e artigo informativo publicado por ${fonteNome}. Clique para ler o conteúdo completo no portal.`,
                            item.link,
                            `📰 ${fonteNome}`,
                            'portal'
                        ));
                        totalResultados++;
                    }
                });
            }

            // --- 2. Processa Artigos Científicos (Crossref / SciELO / Revistas de Saúde) ---
            if (crossrefResult.status === 'fulfilled') {
                const items = crossrefResult.value?.message?.items || [];
                items.forEach(item => {
                    const titulo = Array.isArray(item.title) ? item.title[0] : (item.title || '');
                    if (!titulo) return;

                    let snippet = '';
                    if (item.abstract) {
                        snippet = item.abstract.replace(/<[^>]*>/g, '').trim();
                        if (snippet.length > 220) snippet = snippet.substring(0, 220) + '...';
                    } else if (item.subtitle && item.subtitle.length > 0) {
                        snippet = Array.isArray(item.subtitle) ? item.subtitle[0] : item.subtitle;
                    } else {
                        snippet = 'Estudo científico e publicação médica sobre saúde mental e comportamento humano.';
                    }

                    const url = item.URL || (item.DOI ? `https://doi.org/${item.DOI}` : '#');
                    const revista = (item['container-title'] && item['container-title'][0]) 
                        ? item['container-title'][0] 
                        : (item.publisher || 'Periódico Científico');

                    container.appendChild(criarCard(
                        titulo,
                        snippet,
                        url,
                        `🔬 Artigo Científico • ${revista}`,
                        'artigo'
                    ));
                    totalResultados++;
                });
            }

            // --- 3. Processa Pesquisas Acadêmicas (OpenAlex) ---
            if (openAlexResult.status === 'fulfilled') {
                const results = openAlexResult.value?.results || [];
                results.forEach(item => {
                    const titulo = item.title || item.display_name;
                    if (!titulo) return;

                    let snippet = reconstruirAbstract(item.abstract_inverted_index);
                    if (snippet) {
                        if (snippet.length > 220) snippet = snippet.substring(0, 220) + '...';
                    } else {
                        snippet = 'Trabalho de pesquisa acadêmica em saúde mental e psicologia com acesso aberto.';
                    }

                    const url = item.doi || item.primary_location?.landing_page_url || (item.id ? item.id : '#');
                    const fonte = item.primary_location?.source?.display_name || 'Repositório Acadêmico';

                    container.appendChild(criarCard(
                        titulo,
                        snippet,
                        url,
                        `🎓 Pesquisa Acadêmica • ${fonte}`,
                        'academico'
                    ));
                    totalResultados++;
                });
            }

            // --- 4. Processa Wikipedia PT ---
            if (wikiResult.status === 'fulfilled') {
                const resultados = wikiResult.value?.query?.search || [];
                resultados.slice(0, 2).forEach(item => {
                    const snippet = item.snippet.replace(/<[^>]*>/g, '');
                    const url = `https://pt.wikipedia.org/wiki/${encodeURIComponent(item.title.replace(/ /g, '_'))}`;
                    container.appendChild(criarCard(
                        item.title,
                        snippet + '...',
                        url,
                        '📚 Wikipedia',
                        'wiki'
                    ));
                    totalResultados++;
                });
            }

            // Se nenhuma fonte retornou nada
            if (totalResultados === 0) {
                container.innerHTML = `<div class="busca-vazio">Nenhum resultado encontrado para "${query}".<br>Tente palavras como: ansiedade, meditação, autocuidado, respiração...</div>`;
            }

            container.classList.add('visivel');
        }

        // === BUSCA VÍDEOS (Múltiplas plataformas: YouTube e Dailymotion) ===
        async function buscarVideos() {
            const query = document.getElementById('busca-video-input').value.trim();
            if (!query) return;

            const loader = document.getElementById('busca-video-loader');
            const container = document.getElementById('busca-video-resultados');

            loader.classList.add('visivel');
            container.classList.remove('visivel');
            container.innerHTML = '';

            const queryEnriquecida = query + ' saúde mental autocuidado';

            try {
                // Busca em paralelo no YouTube e no Dailymotion
                const [ytResult, dmResult] = await Promise.allSettled([
                    // 1. YouTube via endpoint local
                    fetch(`Atividades.php?acao=buscar_videos_yt&q=${encodeURIComponent(query)}`)
                        .then(r => r.json()),

                    // 2. Dailymotion API pública
                    fetch(`https://api.dailymotion.com/videos?search=${encodeURIComponent(query + ' saude mental')}&fields=id,title,owner.screenname,thumbnail_240_url,url&limit=4`)
                        .then(r => r.json())
                ]);

                loader.classList.remove('visivel');

                const videosEncontrados = [];

                // Processa resultados do YouTube
                if (ytResult.status === 'fulfilled' && Array.isArray(ytResult.value) && ytResult.value.length > 0) {
                    ytResult.value.slice(0, 4).forEach(v => {
                        videosEncontrados.push({
                            id: v.id,
                            titulo: v.titulo,
                            canal: v.canal || 'YouTube',
                            thumb: v.thumb || `https://img.youtube.com/vi/${v.id}/mqdefault.jpg`,
                            plataforma: 'youtube',
                            badge: '▶️ YouTube'
                        });
                    });
                }

                // Processa resultados do Dailymotion
                if (dmResult.status === 'fulfilled' && dmResult.value?.list) {
                    dmResult.value.list.slice(0, 3).forEach(v => {
                        videosEncontrados.push({
                            id: v.id,
                            titulo: v.title,
                            canal: v['owner.screenname'] || 'Dailymotion',
                            thumb: v.thumbnail_240_url,
                            plataforma: 'dailymotion',
                            badge: '🎬 Dailymotion'
                        });
                    });
                }

                // Se nenhuma das fontes diretas retornou resultados, tenta Invidious como fallback
                if (videosEncontrados.length === 0) {
                    try {
                        const resInv = await fetch(`https://inv.nadeko.net/api/v1/search?q=${encodeURIComponent(queryEnriquecida)}&type=video&page=1`);
                        const dataInv = await resInv.json();
                        if (Array.isArray(dataInv)) {
                            dataInv.filter(v => v.type === 'video').slice(0, 4).forEach(v => {
                                videosEncontrados.push({
                                    id: v.videoId,
                                    titulo: v.title,
                                    canal: v.author || 'YouTube',
                                    thumb: `https://img.youtube.com/vi/${v.videoId}/mqdefault.jpg`,
                                    plataforma: 'youtube',
                                    badge: '▶️ YouTube'
                                });
                            });
                        }
                    } catch (e) {
                        console.warn("Fallback Invidious offline:", e);
                    }
                }

                if (videosEncontrados.length === 0) {
                    // Fallback visual com links diretos
                    container.innerHTML = `
                        <div class="busca-vazio" style="display:flex;flex-direction:column;gap:15px;align-items:center;">
                            <span>Nenhum vídeo embutido encontrado no momento.</span>
                            <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center;">
                                <a href="https://www.youtube.com/results?search_query=${encodeURIComponent(queryEnriquecida)}" 
                                   target="_blank" rel="noopener noreferrer"
                                   style="display:inline-flex;align-items:center;gap:8px;background:#cc0000;color:#fff;padding:10px 22px;border-radius:30px;font-weight:800;font-size:0.88rem;text-decoration:none;box-shadow:0 4px 12px rgba(204,0,0,0.3);">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="white"><path d="M21.543 6.498C22 8.28 22 12 22 12s0 3.72-.457 5.502c-.254.985-.997 1.76-1.938 2.022C17.896 20 12 20 12 20s-5.893 0-7.605-.476c-.945-.266-1.687-1.04-1.938-2.022C2 15.72 2 12 2 12s0-3.72.457-5.502c.254-.985.997-1.76 1.938-2.022C6.107 4 12 4 12 4s5.896 0 7.605.476c.945.266 1.687 1.04 1.938 2.022zM10 15.5l6-3.5-6-3.5v7z"/></svg>
                                    Buscar no YouTube
                                </a>
                                <a href="https://www.dailymotion.com/search/${encodeURIComponent(queryEnriquecida)}" 
                                   target="_blank" rel="noopener noreferrer"
                                   style="display:inline-flex;align-items:center;gap:8px;background:#0066dc;color:#fff;padding:10px 22px;border-radius:30px;font-weight:800;font-size:0.88rem;text-decoration:none;box-shadow:0 4px 12px rgba(0,102,220,0.3);">
                                    Buscar no Dailymotion
                                </a>
                            </div>
                        </div>`;
                    container.classList.add('visivel');
                    return;
                }

                const grid = document.createElement('div');
                grid.className = 'video-busca-grid';

                videosEncontrados.forEach(v => {
                    const card = document.createElement('div');
                    card.className = 'video-busca-card';
                    card.innerHTML = `
                        <img class="video-busca-thumb" src="${v.thumb}" alt="${v.titulo}"
                             onerror="this.src='https://via.placeholder.com/320x180?text=Vídeo'">
                        <div class="video-busca-play">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="#1a1a1a"><polygon points="8 5 19 12 8 19 8 5"></polygon></svg>
                        </div>
                        <div class="video-busca-info">
                            <div class="video-busca-titulo">${v.titulo}</div>
                            <div class="video-busca-meta">
                                <span class="video-busca-canal">${v.canal}</span>
                                <span class="video-busca-badge ${v.plataforma}">${v.badge}</span>
                            </div>
                        </div>
                    `;
                    card.onclick = () => openVideo(v.id, v.plataforma);
                    grid.appendChild(card);
                });

                // Ações para explorar mais nas plataformas
                const linksContainer = document.createElement('div');
                linksContainer.style.cssText = 'display:flex;gap:10px;flex-wrap:wrap;margin-top:6px;';

                const verMaisYt = document.createElement('a');
                verMaisYt.href = `https://www.youtube.com/results?search_query=${encodeURIComponent(queryEnriquecida)}`;
                verMaisYt.target = '_blank';
                verMaisYt.rel = 'noopener noreferrer';
                verMaisYt.style.cssText = 'display:inline-flex;align-items:center;gap:8px;background:#cc0000;color:#fff;padding:8px 18px;border-radius:30px;font-weight:800;font-size:0.8rem;text-decoration:none;box-shadow:0 4px 12px rgba(204,0,0,0.25);transition:transform 0.2s;';
                verMaisYt.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="white"><path d="M21.543 6.498C22 8.28 22 12 22 12s0 3.72-.457 5.502c-.254.985-.997 1.76-1.938 2.022C17.896 20 12 20 12 20s-5.893 0-7.605-.476c-.945-.266-1.687-1.04-1.938-2.022C2 15.72 2 12 2 12s0-3.72.457-5.502c.254-.985.997-1.76 1.938-2.022C6.107 4 12 4 12 4s5.896 0 7.605.476c.945.266 1.687 1.04 1.938 2.022zM10 15.5l6-3.5-6-3.5v7z"/></svg> Mais no YouTube`;
                verMaisYt.onmouseover = () => verMaisYt.style.transform = 'scale(1.04)';
                verMaisYt.onmouseout = () => verMaisYt.style.transform = 'scale(1)';

                const verMaisDm = document.createElement('a');
                verMaisDm.href = `https://www.dailymotion.com/search/${encodeURIComponent(queryEnriquecida)}`;
                verMaisDm.target = '_blank';
                verMaisDm.rel = 'noopener noreferrer';
                verMaisDm.style.cssText = 'display:inline-flex;align-items:center;gap:8px;background:#0066dc;color:#fff;padding:8px 18px;border-radius:30px;font-weight:800;font-size:0.8rem;text-decoration:none;box-shadow:0 4px 12px rgba(0,102,220,0.25);transition:transform 0.2s;';
                verMaisDm.innerHTML = `Mais no Dailymotion`;
                verMaisDm.onmouseover = () => verMaisDm.style.transform = 'scale(1.04)';
                verMaisDm.onmouseout = () => verMaisDm.style.transform = 'scale(1)';

                linksContainer.appendChild(verMaisYt);
                linksContainer.appendChild(verMaisDm);

                container.appendChild(grid);
                container.appendChild(linksContainer);
                container.classList.add('visivel');

            } catch(e) {
                loader.classList.remove('visivel');
                const queryEnriquecida = query + ' saúde mental autocuidado';
                container.innerHTML = `
                    <div class="busca-vazio" style="display:flex;flex-direction:column;gap:15px;align-items:center;">
                        <span>Não foi possível carregar os resultados aqui.</span>
                        <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center;">
                            <a href="https://www.youtube.com/results?search_query=${encodeURIComponent(queryEnriquecida)}" 
                               target="_blank" rel="noopener noreferrer"
                               style="display:inline-flex;align-items:center;gap:8px;background:#cc0000;color:#fff;padding:10px 22px;border-radius:30px;font-weight:800;font-size:0.88rem;text-decoration:none;box-shadow:0 4px 12px rgba(204,0,0,0.3);">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="white"><path d="M21.543 6.498C22 8.28 22 12 22 12s0 3.72-.457 5.502c-.254.985-.997 1.76-1.938 2.022C17.896 20 12 20 12 20s-5.893 0-7.605-.476c-.945-.266-1.687-1.04-1.938-2.022C2 15.72 2 12 2 12s0-3.72.457-5.502c.254-.985.997-1.76 1.938-2.022C6.107 4 12 4 12 4s5.896 0 7.605.476c.945.266 1.687 1.04 1.938 2.022zM10 15.5l6-3.5-6-3.5v7z"/></svg>
                                Buscar no YouTube
                            </a>
                            <a href="https://www.dailymotion.com/search/${encodeURIComponent(queryEnriquecida)}" 
                               target="_blank" rel="noopener noreferrer"
                               style="display:inline-flex;align-items:center;gap:8px;background:#0066dc;color:#fff;padding:10px 22px;border-radius:30px;font-weight:800;font-size:0.88rem;text-decoration:none;box-shadow:0 4px 12px rgba(0,102,220,0.3);">
                                Buscar no Dailymotion
                            </a>
                        </div>
                    </div>`;
                container.classList.add('visivel');
            }
        }
    </script>
    <script src="assets/fundo-animado.js?v=20260925-v4"></script>
    <script src="notificacoes.js?v=20260924-v7" onerror="if(!window.togglePainelAcessibilidade){var s=document.createElement('script');s.src='assets/notificacoes.js?v=20260924-v7';document.body.appendChild(s);}"></script>
</body>

</html>