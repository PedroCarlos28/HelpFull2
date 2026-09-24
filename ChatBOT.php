<?php
require_once 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: Comeco.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuarioLogado = $stmt->fetch();

if (!$usuarioLogado) {
    session_destroy();
    header("Location: Comeco.php");
    exit;
}

// BUSCAR DATAS COM CONVERSAS (Histórico)
$stmtHist = $pdo->prepare("SELECT DISTINCT sessao_token FROM historico_chat WHERE usuario_id = ? ORDER BY sessao_token DESC");
$stmtHist->execute([$usuarioLogado['id']]);
$datasHistorico = $stmtHist->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HelpFull - Helpy</title>
    <link rel="icon" type="image/png" href="assets/logoHelpFull.png">
    <style>
        /* =======================================
           CONFIGURAÇÕES GLOBAIS E FUNDO
           ======================================= */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap');

        body {
            font-family: 'Montserrat', sans-serif;
            color: #1a1a1a;
            overflow: hidden;
            background-color: transparent;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        /* LAYOUT FLUIDO SEM ZOOM */
        .layout-chat-wrapper {
            font-size: 1rem;
        }

        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            max-width: 100vw;
            overflow-x: hidden;
            background-color: #dbe7eb;
            font-family: 'Montserrat', sans-serif;
            color: #1a1a1a;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .imagem-fundo {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
            pointer-events: none;
        }

        .conteudo-site {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            padding: 95px 20px 20px 20px;
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 0;
        }

        /* NAVBAR */
        .nav-container-global {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
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

        /* SISTEMA DE NOTIFICAÇÃO (UNIVERSAL) */
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

        /* LAYOUT CHAT + SIDEBAR */
        .layout-chat-wrapper {
            display: flex;
            gap: 20px;
            width: 100%;
            flex: 1;
            min-height: 0;
            margin: 0 auto;
            margin-bottom: 5px;
        }

        .sidebar-historico {
            background: rgba(235, 235, 235, 0.85);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-radius: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05), inset 0 2px 5px rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.6);
            width: 250px;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            flex-shrink: 0;
        }

        .header-historico {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 85%;
            margin-bottom: 25px;
        }

        .titulo-historico {
            font-size: 1.15rem;
            font-weight: 800;
            color: #333;
            margin: 0;
        }

        .btn-novo-chat {
            background: transparent;
            border: none;
            color: #333;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s, color 0.2s;
            padding: 0;
        }

        .btn-novo-chat:hover {
            color: #2b7a8c;
            transform: scale(1.1);
        }

        .lista-historico {
            display: flex;
            flex-direction: column;
            gap: 15px;
            width: 100%;
            align-items: center;
            overflow-y: auto;
            padding-right: 5px;
            scrollbar-width: thin;
            scrollbar-color: rgba(0, 0, 0, 0.05) transparent;
        }

        .lista-historico::-webkit-scrollbar {
            width: 4px;
        }

        .lista-historico::-webkit-scrollbar-track {
            background: transparent;
        }

        .lista-historico::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 10px;
        }

        .btn-historico {
            background: #e2e2e2;
            border: none;
            border-radius: 25px;
            padding: 12px 25px;
            font-size: 0.95rem;
            font-weight: 700;
            color: #333;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 85%;
        }

        .btn-historico:hover {
            background: #d0d0d0;
            transform: scale(1.03);
        }

        /* JANELA DO CHAT */
        .caixa-chat {
            background: rgba(235, 235, 235, 0.85);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-radius: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05), inset 0 2px 5px rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.6);
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 0;
            overflow: hidden;
            width: 100%;
            position: relative;
        }

        .blur-topo {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 80px;
            z-index: 10;
            pointer-events: none;
            backdrop-filter: blur(15px);
            background: linear-gradient(to bottom, rgba(235, 235, 235, 0.95), transparent);
            -webkit-mask-image: linear-gradient(to bottom, black 20%, transparent 100%);
            mask-image: linear-gradient(to bottom, black 20%, transparent 100%);
        }

        .blur-baixo {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 100px;
            z-index: 5;
            pointer-events: none;
            backdrop-filter: blur(15px);
            background: linear-gradient(to top, rgba(235, 235, 235, 0.95), transparent);
            -webkit-mask-image: linear-gradient(to top, black 20%, transparent 100%);
            mask-image: linear-gradient(to top, black 20%, transparent 100%);
        }

        .area-mensagens {
            flex: 1;
            padding: 60px 40px 120px 40px;
            display: flex;
            flex-direction: column;
            gap: 25px;
            overflow-y: auto;
            scroll-behavior: smooth;
            scrollbar-width: thin;
            scrollbar-color: rgba(0, 0, 0, 0.1) transparent;
        }

        .area-mensagens::-webkit-scrollbar {
            width: 6px;
        }

        .area-mensagens::-webkit-scrollbar-track {
            background: transparent;
        }

        .area-mensagens::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        .area-mensagens::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 0, 0, 0.2);
        }

        .mensagem {
            max-width: 75%;
            padding: 20px 30px;
            font-size: 1.15rem;
            font-weight: 500;
            line-height: 1.5;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            animation: fadeIn 0.3s ease;
        }

        .msg-usuario {
            align-self: flex-end;
            background-color: #badbeb;
            color: #1a1a1a;
            border-radius: 35px 35px 10px 35px;
        }

        .msg-bot {
            align-self: flex-start;
            background-color: #d1d1d1;
            color: #1a1a1a;
            border-radius: 35px 35px 35px 10px;
        }

        .msg-bot-digitando {
            align-self: flex-start;
            background-color: #d1d1d1;
            padding: 20px 25px;
            border-radius: 35px 35px 35px 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 80px;
            height: 60px;
        }

        .loading-dots {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
        }

        .loading-dots div {
            width: 8px;
            height: 8px;
            background-color: #666;
            border-radius: 50%;
            animation: bounceDots 0.5s infinite alternate;
        }

        .loading-dots div:nth-child(1) {
            animation-delay: 0s;
        }

        .loading-dots div:nth-child(2) {
            animation-delay: 0.15s;
        }

        .loading-dots div:nth-child(3) {
            animation-delay: 0.3s;
        }

        @keyframes bounceDots {
            from {
                transform: translateY(0);
            }

            to {
                transform: translateY(-6px);
            }
        }

        /* ÁREA DE DIGITAÇÃO */
        .area-input-chat {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 10;
            padding: 30px 40px 30px 40px;
            display: flex;
            gap: 15px;
            align-items: center;
            background: transparent;
        }

        .input-chat {
            flex: 1;
            padding: 20px 30px;
            border-radius: 35px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            font-size: 1.15rem;
            outline: none;
            color: #1a1a1a;
            font-weight: 700;
            font-family: inherit;
        }

        .input-chat::placeholder {
            color: #888;
            font-weight: 700;
        }

        .btn-enviar-chat {
            width: 63px;
            height: 63px;
            border-radius: 25px;
            background: rgba(186, 219, 235, 0.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, background-color 0.2s;
            flex-shrink: 0;
        }

        .btn-enviar-chat:hover {
            transform: scale(1.05);
            background-color: #a0ccde;
        }

        .icone-enviar {
            width: 28px;
            height: 28px;
            fill: none;
            stroke: #1a1a1a;
            stroke-width: 2.5;
            stroke-linecap: round;
            stroke-linejoin: round;
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
            .nav-links { display: none; }
            .navbar-topo {
                padding: 12px 25px;
                width: calc(100% - 40px);
                margin: 0 auto;
                justify-content: space-between;
            }
            .nav-logo { cursor: pointer; }
            .conteudo-site { padding-top: 85px; }
            .sidebar-historico { display: none; }
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

        .btn-toggle-historico-mobile {
            display: none;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            padding: 8px 18px;
            font-weight: 800;
            font-size: 0.85rem;
            color: #333;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 10px;
            align-self: flex-start;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        @media (max-width: 768px) {
            .btn-toggle-historico-mobile {
                display: inline-flex;
            }

            .sidebar-historico {
                display: none;
            }

            .sidebar-historico.aberto-mobile {
                display: flex !important;
                position: fixed;
                top: 85px;
                left: 20px;
                right: 20px;
                z-index: 2500;
                width: auto;
                max-height: 75vh;
                box-shadow: 0 15px 45px rgba(0,0,0,0.2);
            }

            .layout-chat-wrapper {
                flex-direction: column;
                gap: 10px;
            }

            .caixa-chat {
                border-radius: 25px;
                height: calc(100vh - 210px);
            }

            .area-mensagens {
                padding: 60px 20px 90px 20px;
            }

            .mensagem {
                max-width: 90%;
                font-size: 0.95rem;
                padding: 15px 20px;
            }

            .area-input-chat {
                padding: 15px 20px 20px 20px;
            }

            .input-chat {
                padding: 14px 20px;
                font-size: 0.95rem;
            }

            .btn-enviar-chat {
                width: 50px;
                height: 50px;
                border-radius: 20px;
            }

            .blur-baixo {
                height: 80px;
            }

            .btn-notificacao-separado { right: 10px; }
            .painel-notificacoes { right: 10px; width: calc(100vw - 40px); max-width: 320px; }
            .toast-notificacao { width: 92%; max-width: 380px; }
        }
    </style>
    <link rel="stylesheet" href="assets/acessibilidade.css?v=20260916-v3">
</head>

<body>

    <img class="imagem-fundo" src="assets/HELPFULL.png" alt="Plano de Fundo HelpFull">

    <div class="nav-container-global">
        <nav class="navbar-topo">
            <a href="inicio.php" class="nav-logo" style="display: flex; align-items: center;">HELPFULL <span class="nav-seta-dropdown"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg></span></a>
            <ul class="nav-links">
                <li><a href="Diario.php">Diário</a></li>
                <li><a href="Comunidade.php">Comunidade</a></li>
                <li><a href="ChatBOT.php" class="ativo">Helpy</a></li>
                <li><a href="Atividades.php">Adicionais</a></li>
            </ul>
            <div class="nav-dropdown-mobile" id="navDropdownMobile">
                <a href="inicio.php">Início</a>
                <a href="Diario.php">Diário</a>
                <a href="Comunidade.php">Comunidade</a>
                <a href="ChatBOT.php" class="ativo">Helpy</a>
                <a href="Atividades.php">Adicionais</a>
                <a href="Perfil.php">Perfil</a>
                <a href="javascript:void(0)" onclick="if(window.togglePainelAcessibilidade){togglePainelAcessibilidade();}if(document.getElementById('navDropdownMobile'))document.getElementById('navDropdownMobile').classList.remove('aberto');if(document.querySelector('.nav-logo'))document.querySelector('.nav-logo').classList.remove('aberto');" style="display: flex; align-items: center; gap: 8px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                    Configurações
                </a>
            </div>
            <?php $fotoPerfilDb = !empty($usuarioLogado['foto_perfil']) ? $usuarioLogado['foto_perfil'] : ''; ?>
            <a href="Perfil.php" style="text-decoration: none; display: flex;">
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
    </div>

    <div class="conteudo-site">
        <button class="btn-toggle-historico-mobile" onclick="toggleHistoricoMobile()">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 3"></path><circle cx="12" cy="12" r="9"></circle></svg>
            Histórico
        </button>
        <div class="layout-chat-wrapper">
            <div class="sidebar-historico">
                <div class="header-historico">
                    <h3 class="titulo-historico">Histórico</h3>
                    <button class="btn-novo-chat" onclick="novoChat()" title="Nova Conversa">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </button>
                </div>

                <div class="lista-historico" id="lista-historico-container">
                    <?php if (empty($datasHistorico)): ?>
                        <p id="msg-sem-historico" style="font-size: 0.8rem; color: #888; text-align: center;">Nenhuma
                            conversa ainda.</p>
                    <?php else: ?>
                        <?php foreach ($datasHistorico as $data):
                            $dataFormatada = date('d/m', strtotime($data));
                            ?>
                            <button class="btn-historico" id="btn-hist-<?= $data ?>"
                                onclick="carregarHistorico('<?= $data ?>')">Dia <?= $dataFormatada ?></button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="caixa-chat">
                <div class="blur-topo"></div>
                <div class="blur-baixo"></div>
                <div class="area-mensagens" id="area-mensagens">
                    <div class="mensagem msg-bot">
                        Olá, <?= htmlspecialchars($usuarioLogado['nome']) ?>! Aqui é o seu assistente Helpy. Como
                        posso te apoiar hoje?
                    </div>
                </div>

                <div class="area-input-chat">
                    <input type="text" class="input-chat" id="inputChat"
                        placeholder="Digite aqui para conversar com o Helpy..." onkeypress="verificarEnter(event)">

                    <button class="btn-enviar-chat" onclick="enviarMensagem()">
                        <svg class="icone-enviar" viewBox="0 0 24 24">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- TOAST CENTRALIZADO -->
    <div id="notificacaoHelpFull" class="toast-notificacao">
        <div class="toast-barra" id="toastBarra"></div>
        <div class="toast-conteudo">
            <p id="textoNotificacao"></p>
        </div>
        <div class="toast-btn-container">
            <a id="linkNotificacao" href="#" class="toast-btn"></a>
        </div>
    </div>

    <script>
        function toggleHistoricoMobile() {
            const sidebar = document.querySelector('.sidebar-historico');
            if (sidebar) {
                sidebar.classList.toggle('aberto-mobile');
            }
        }

        function novoChat() {
            const areaMensagens = document.getElementById('area-mensagens');
            areaMensagens.innerHTML = `
                <div class="mensagem msg-bot">
                    Olá, <?= htmlspecialchars($usuarioLogado['nome']) ?>! Aqui é o seu assistente Helpy. Como posso te apoiar hoje?
                </div>
            `;
        }

        async function enviarMensagem() {
            const input = document.getElementById('inputChat');
            const mensagemTexto = input.value.trim();
            const areaMensagens = document.getElementById('area-mensagens');

            if (mensagemTexto !== "") {
                const formatarTexto = (texto) => {
                    return texto
                        .replace(/</g, "&lt;").replace(/>/g, "&gt;")
                        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                        .replace(/\n/g, '<br>');
                };

                const novaMensagemHTML = `
                    <div class="mensagem msg-usuario">
                        ${mensagemTexto.replace(/</g, "&lt;").replace(/>/g, "&gt;")}
                    </div>
                `;
                areaMensagens.insertAdjacentHTML('beforeend', novaMensagemHTML);
                areaMensagens.scrollTop = areaMensagens.scrollHeight;
                input.value = "";

                const hoje = new Date();
                const ano = hoje.getFullYear();
                const mes = String(hoje.getMonth() + 1).padStart(2, '0');
                const dia = String(hoje.getDate()).padStart(2, '0');
                const dataYMD = `${ano}-${mes}-${dia}`;
                const dataDM = `${dia}/${mes}`;

                const btnHoje = document.getElementById(`btn-hist-${dataYMD}`);
                if (!btnHoje) {
                    const containerHist = document.getElementById('lista-historico-container');
                    const msgSemHist = document.getElementById('msg-sem-historico');

                    if (msgSemHist) msgSemHist.remove();

                    const novoBtn = `<button class="btn-historico" id="btn-hist-${dataYMD}" onclick="carregarHistorico('${dataYMD}')">Dia ${dataDM}</button>`;
                    containerHist.insertAdjacentHTML('afterbegin', novoBtn);
                }

                const idDigitando = "digitando-" + Date.now();
                const botDigitandoHTML = `
                    <div class="msg-bot-digitando" id="${idDigitando}">
                        <div class="loading-dots">
                            <div></div><div></div><div></div>
                        </div>
                    </div>
                `;
                areaMensagens.insertAdjacentHTML('beforeend', botDigitandoHTML);
                areaMensagens.scrollTop = areaMensagens.scrollHeight;

                try {
                    const response = await fetch("chatbot_proc.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ message: mensagemTexto })
                    });

                    const data = await response.json();

                    const indicator = document.getElementById(idDigitando);
                    if (indicator) indicator.remove();

                    const botMensagemHTML = `
                        <div class="mensagem msg-bot">
                            ${formatarTexto(data.reply || "Ops, não rolou nenhuma resposta.")}
                        </div>
                    `;
                    areaMensagens.insertAdjacentHTML('beforeend', botMensagemHTML);

                    // Verifica se bateu a meta
                    if (data.meta_concluida) {
                        mostrarNotificacaoAtiva({
                            mensagem: "Meta concluída! Você desabafou um pouco com o Helpy hoje. 💬",
                            intensidade: "baixa",
                            link: "Perfil.php",
                            textoBotao: "Ver Perfil"
                        });
                    }
                } catch (error) {
                    console.error("Erro no chat:", error);
                    const indicator = document.getElementById(idDigitando);
                    if (indicator) indicator.remove();

                    const erroHTML = `
                        <div class="mensagem msg-bot" style="color: #a12b2b;">
                            Erro de conexão com o servidor.
                        </div>
                    `;
                    areaMensagens.insertAdjacentHTML('beforeend', erroHTML);
                }

                areaMensagens.scrollTop = areaMensagens.scrollHeight;
            }
        }

        function verificarEnter(event) {
            if (event.key === "Enter") {
                enviarMensagem();
            }
        }

        async function carregarHistorico(dataSessao) {
            const areaMensagens = document.getElementById('area-mensagens');
            areaMensagens.innerHTML = `
                <div class="msg-bot-digitando">
                    <div class="loading-dots">
                        <div></div><div></div><div></div>
                    </div>
                </div>
            `;

            try {
                const response = await fetch(`get_history.php?sessao=${dataSessao}`);
                const mensagens = await response.json();

                areaMensagens.innerHTML = '';

                if (mensagens.length === 0) {
                    areaMensagens.innerHTML = '<div class="mensagem msg-bot">Nenhuma mensagem encontrada nesta data.</div>';
                    return;
                }

                mensagens.forEach(msg => {
                    areaMensagens.insertAdjacentHTML('beforeend', `
                        <div class="mensagem msg-usuario">${msg.mensagem}</div>
                    `);
                    areaMensagens.insertAdjacentHTML('beforeend', `
                        <div class="mensagem msg-bot">${msg.resposta.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>').replace(/\n/g, '<br>')}</div>
                    `);
                });

                areaMensagens.scrollTop = areaMensagens.scrollHeight;
            } catch (error) {
                console.error("Erro ao carregar histórico:", error);
                areaMensagens.innerHTML = '<div class="mensagem msg-bot" style="color: red;">Erro ao carregar o histórico.</div>';
            }
        }

        // === MOBILE DROPDOWN TOGGLE ===
        const navLogo = document.querySelector('.nav-logo');
        const navDropdown = document.getElementById('navDropdownMobile');
        if (navLogo && navDropdown) {
            navLogo.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    e.preventDefault();
                    navDropdown.classList.toggle('aberto');
                    navLogo.classList.toggle('aberto');
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
    <script src="notificacoes.js?v=20260916-v2"></script>
</body>

</html>