<?php
require_once 'conexao.php';

// 1. VERIFICA SE ESTÁ LOGADO (Para carregar dados do usuário)
$usuarioLogado = null;
if (isset($_SESSION['usuario_id'])) {
    // BUSCA O USUÁRIO REAL NO BANCO
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
    <title>HelpFull</title>
    <link rel="icon" type="image/png" href="assets/logoHelpFull.png">
    <style>
        /* =======================================
           CONFIGURAÇÕES GLOBAIS
           ======================================= */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap');

        html,
        body {
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

        /* RESPONSIVIDADE E CONTAINERS FLUIDOS */
        .conteudo-site {
            width: 100%;
            max-width: 1100px;
        }

        /* FUNDO ANIMADO INTERATIVO */
        .fundo-animado-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 0;
            pointer-events: none;
            display: block;
            filter: blur(32px);
            -webkit-filter: blur(32px);
            transform: scale(1.08);
            transform-origin: center center;
        }

        .imagem-fundo-mobile {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
            pointer-events: none;
        }

        /* CONTAINER Principal */
        .conteudo-site {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px 0 20px;
        }

        /* LOGO GIGANTE */
        .logo-gigante {
            font-size: clamp(3rem, 10vw, 8rem);
            text-align: center;
            font-weight: 900;
            letter-spacing: -3px;
            margin-bottom: 30px;
        }

        /* CAIXA VIDRO */
        .caixa-vidro {
            display: flex;
            gap: 40px;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 40px 50px;
            border-radius: 35px;
            margin-bottom: 0;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.9);
            position: relative;
            z-index: 20;
        }

        /* FAIXA BRANCA MEIO */
        .faixa-meio-branca {
            background-color: #f7f7f7;
            width: 100%;
            position: relative;
            z-index: 5;
            margin-top: -120px;
            padding-top: 120px;
            padding-bottom: 80px;
        }

        /* CARROSSEL HERO */
        .carrossel-intro-container {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 180px;
            position: relative;
            user-select: none;
        }

        .carrossel-slides {
            position: relative;
            width: 100%;
            min-height: 125px;
            display: flex;
            align-items: center;
        }

        .carrossel-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transform: translateY(8px) scale(0.99);
            transition: opacity 0.45s ease, transform 0.45s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.45s ease;
            pointer-events: none;
        }

        .carrossel-slide.ativo {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }

        .carrossel-slide .slide-titulo {
            font-size: 1.25rem;
            font-weight: 800;
            color: #1a1a1a;
            letter-spacing: -0.3px;
            margin-bottom: 8px;
            display: block;
            line-height: 1.35;
        }

        .carrossel-slide .slide-desc {
            font-size: 1.05rem;
            line-height: 1.55;
            font-weight: 500;
            color: #2c3e50;
            margin: 0;
        }

        .carrossel-dots {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-top: 14px;
            opacity: 0;
            transform: translateY(6px);
            transition: opacity 0.35s ease, transform 0.35s ease;
            pointer-events: none;
            z-index: 5;
        }

        .caixa-vidro:hover .carrossel-dots,
        .carrossel-dots.mostrar-temporario {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        .carrossel-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: rgba(26, 26, 26, 0.22);
            border: none;
            padding: 0;
            cursor: pointer;
            outline: none;
            transition: width 0.35s cubic-bezier(0.34, 1.56, 0.64, 1), background-color 0.3s ease, transform 0.25s ease;
        }

        .carrossel-dot:hover {
            background: rgba(26, 26, 26, 0.55);
            transform: scale(1.25);
        }

        .carrossel-dot.ativo {
            width: 26px;
            background: #2b7a8c;
            border-radius: 999px;
            box-shadow: 0 0 8px rgba(43, 122, 140, 0.8), 0 0 16px rgba(43, 122, 140, 0.45);
            animation: brilhoDot 2.4s ease-in-out infinite;
        }

        @keyframes brilhoDot {
            0%, 100% {
                box-shadow: 0 0 6px rgba(43, 122, 140, 0.75), 0 0 14px rgba(43, 122, 140, 0.35);
            }
            50% {
                box-shadow: 0 0 11px rgba(43, 122, 140, 0.95), 0 0 22px rgba(43, 122, 140, 0.55);
            }
        }

        .texto-intro {
            flex: 1;
            font-size: 1.15rem;
            line-height: 1.5;
            font-weight: 500;
        }

        .quadrado-grafico {
            width: 250px;
            height: 180px;
            border-radius: 20px;
            overflow: hidden;
            flex-shrink: 0;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            background: #eef6f8;
            position: relative;
        }

        .quadrado-grafico::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(43, 122, 140, 0.14) 0%, rgba(0, 180, 216, 0.05) 100%);
            pointer-events: none;
            z-index: 2;
        }

        .quadrado-grafico .carrossel-img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            visibility: hidden;
            transform: scale(1.08);
            transition: opacity 0.6s ease, transform 0.8s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.6s ease;
            pointer-events: none;
            z-index: 1;
        }

        .quadrado-grafico .carrossel-img.ativo {
            opacity: 1;
            visibility: visible;
            transform: scale(1);
        }

        /* MENU ESTÁTICO */
        .menu-estatitco {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin: 50px 0 100px 0;
            flex-wrap: wrap;
        }

        .menu-estatitco a {
            font-weight: 800;
            font-size: 1.15rem;
            text-decoration: none;
            color: #1a1a1a;
            transition: opacity 0.3s;
        }

        .menu-estatitco a:hover {
            opacity: 0.6;
        }

        /* =======================================
           NAVBAR FLUTUANTE
           ======================================= */
        .nav-container-global {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            z-index: 2000;
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .nav-container-global:not(.escondida) {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: translateX(-50%) translateY(0);
        }

        .nav-container-global.escondida {
            transform: translateX(-50%) translateY(-100px);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .navbar-flutuante {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            padding: 12px 30px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 30px;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.5);
            position: relative;
            transition: all 0.3s ease;
        }

        .nav-logo-capsula {
            font-weight: 900;
            font-size: 1.05rem;
            letter-spacing: -0.5px;
        }

        .links-capsula {
            display: flex;
            gap: 25px;
            list-style: none;
        }

        .links-capsula a {
            text-decoration: none;
            color: #1a1a1a;
            font-weight: 700;
            font-size: 0.95rem;
        }

        /* Foto de perfil e Animação do Sininho */
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

        /* FAZ O SININHO SUMIR AO PASSAR O MOUSE NO PERFIL */
        .perfil-capsula:hover .sininho-notificacao {
            opacity: 0 !important;
            transform: scale(0.5);
            pointer-events: none;
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

        /* BOTÃO DE NOTIFICAÇÃO SEPARADO (Hover) */
        .btn-notificacao-separado {
            position: absolute;
            right: -60px;
            /* Fica do lado direito da nav */
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

        /* === TOAST CENTRALIZADO === */
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

        /* === PAINEL DE NOTIFICAÇÕES LATERAL (Animação Saindo para a esquerda) === */
        .painel-notificacoes {
            position: absolute;
            top: 70px;
            right: -60px;
            /* Borda direita alinha com o botão */
            width: 320px;
            background: rgba(200, 200, 200, 0.85);
            /* Vidro Cinza */
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

            /* EFEITO SAINDO PARA A ESQUERDA */
            transform-origin: 90% 0%;
            /* Origem no canto superior direito (próximo ao botão) */
            transform: translateX(40px) scale(0.8);
            /* Começa menor e deslocado para a direita */
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            /* Curva elástica estilo iOS */
        }

        .painel-notificacoes.aberto {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: translateX(0) scale(1);
            /* Desliza para a esquerda até a posição final (0) */
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

        /* Botão Fechar Painel (X) */
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

        /* Botão Limpar em Pílula */
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

        /* CARDS E SEÇÕES INFERIORES */
        .secao-cartoes {
            display: flex;
            align-items: stretch;
            gap: 25px;
            margin-bottom: 80px;
        }

        .secao-cartoes a {
            flex: 1 1 0;
            display: flex;
            text-decoration: none;
            color: inherit;
        }

        .cartao {
            flex: 1;
            display: flex;
            flex-direction: column;
            width: 100%;
            min-height: 200px;
            box-sizing: border-box;
            background: rgba(250, 250, 250, 0.85);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            padding: 35px 25px;
            border-radius: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.9);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .cartao:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .cartao h3 {
            font-size: 1.15rem;
            margin-bottom: 15px;
            font-weight: 800;
        }

        .cartao p {
            font-size: 0.88rem;
            line-height: 1.55;
            color: #333;
            font-weight: 500;
            flex: 1;
        }

        .bloco-grande-container {
            padding: 40px 0 80px 0;
            display: flex;
            justify-content: center;
        }

        .bloco-grande {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(35px);
            -webkit-backdrop-filter: blur(35px);
            padding: 50px 60px;
            border-radius: 35px;
            width: 100%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.6);
            text-align: left;
        }

        .bloco-grande h2 {
            font-size: 1.35rem;
            margin-bottom: 20px;
            font-weight: 800;
        }

        .bloco-grande p {
            font-size: 1.05rem;
            line-height: 1.6;
            color: #1a1a1a;
            font-weight: 500;
            margin: 0;
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
            padding: 80px 20px 40px 20px;
        }

        .texto-extra-container {
            width: 100%;
            padding: 0 60px;
            margin-bottom: 60px;
        }

        .texto-extra-scrolled {
            font-size: 1.15rem;
            line-height: 1.6;
            font-weight: 500;
            color: #1a1a1a;
            margin-bottom: 30px;
            max-width: 900px;
        }

        .link-extra {
            display: inline-block;
            font-weight: 700;
            font-size: 1.15rem;
            text-decoration: none;
            color: #1a1a1a;
            transition: opacity 0.3s;
        }

        .link-extra:hover {
            opacity: 0.7;
        }

        .rodape-simples {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 20px;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            padding: 40px 60px 0 60px;
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
        }

        /* MOBILE */
        @media (max-width: 768px) {
            .video-fundo {
                display: none !important;
            }

            .imagem-fundo-mobile {
                display: block !important;
            }

            .nav-container-global {
                width: 100%;
                padding: 0 15px;
                box-sizing: border-box;
            }

            .caixa-vidro {
                flex-direction: column;
                text-align: center;
                padding: 30px;
            }

            .carrossel-intro-container {
                text-align: center;
                align-items: center;
                min-height: auto;
                width: 100%;
            }

            .carrossel-slides {
                min-height: 155px;
            }

            .carrossel-slide {
                text-align: center;
                align-items: center;
            }

            .carrossel-dots {
                justify-content: center;
                margin-top: 12px;
                margin-bottom: 22px;
            }

            .quadrado-grafico {
                width: 100%;
                height: 180px;
            }

            .menu-estatitco {
                gap: 20px;
                margin: 40px 0 50px 0;
            }

            .secao-cartoes {
                flex-direction: column;
                gap: 15px;
            }

            .secao-cartoes a {
                width: 100%;
            }

            .cartao {
                min-height: auto;
            }

            .navbar-flutuante {
                width: calc(100% - 40px);
                margin: 0 auto;
                justify-content: space-between;
                padding: 10px 25px;
                gap: 15px;
                overflow: visible !important;
            }

            .links-capsula {
                display: none;
            }

            .bloco-grande {
                padding: 30px;
            }

            .secao-branca-conteudo {
                padding: 50px 20px;
            }

            .texto-extra-container {
                padding: 0 10px;
                margin-bottom: 60px;
            }

            .rodape-simples {
                padding: 30px 10px 0 10px;
            }

            .nav-logo-capsula {
                cursor: pointer;
            }

            .btn-notificacao-separado {
                right: 10px;
            }

            .painel-notificacoes {
                right: 10px;
                width: calc(100vw - 40px);
                max-width: 320px;
            }

            .toast-notificacao {
                width: 92%;
                max-width: 380px;
            }
        }

        @media (max-width: 480px) {
            .caixa-vidro {
                padding: 20px;
                border-radius: 25px;
            }

            .bloco-grande {
                padding: 25px 20px;
                border-radius: 25px;
            }

            .logo-gigante {
                font-size: clamp(2.5rem, 12vw, 4.5rem);
            }

            .texto-intro {
                font-size: 1rem;
            }

            .carrossel-slides {
                min-height: 175px;
            }

            .carrossel-slide .slide-titulo {
                font-size: 1.12rem;
            }

            .carrossel-slide .slide-desc {
                font-size: 0.95rem;
                line-height: 1.5;
            }
        }

        /* === TEMA ESCURO E CONTRASTE (ACESSIBILIDADE) PARA O CARROSSEL === */
        body.acessibilidade-escuro .carrossel-slide .slide-titulo {
            color: #ffffff !important;
        }
        body.acessibilidade-escuro .carrossel-slide .slide-desc {
            color: #d1d5db !important;
        }
        body.acessibilidade-escuro .carrossel-dot {
            background: rgba(255, 255, 255, 0.3) !important;
        }
        body.acessibilidade-escuro .carrossel-dot:hover {
            background: rgba(255, 255, 255, 0.6) !important;
        }
        body.acessibilidade-escuro .carrossel-dot.ativo {
            background: var(--tema-link, #7ecfdb) !important;
            box-shadow: 0 0 8px rgba(126, 207, 219, 0.8), 0 0 16px rgba(126, 207, 219, 0.45) !important;
            animation: brilhoDotEscuro 2.4s ease-in-out infinite !important;
        }

        @keyframes brilhoDotEscuro {
            0%, 100% {
                box-shadow: 0 0 6px rgba(126, 207, 219, 0.75), 0 0 14px rgba(126, 207, 219, 0.35);
            }
            50% {
                box-shadow: 0 0 11px rgba(126, 207, 219, 0.95), 0 0 22px rgba(126, 207, 219, 0.55);
            }
        }

        body.acessibilidade-contraste .carrossel-slide .slide-titulo,
        body.acessibilidade-contraste .carrossel-slide .slide-desc {
            color: var(--tema-texto, #000) !important;
        }
        body.acessibilidade-contraste .carrossel-dot {
            background: transparent !important;
            border: 1.5px solid var(--tema-texto, #000) !important;
        }
        body.acessibilidade-contraste .carrossel-dot.ativo {
            background: var(--tema-texto, #000) !important;
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
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.5);
            flex-direction: column;
            gap: 5px;
            z-index: 2100;
            animation: fadeInDropdown 0.3s ease;
        }

        .nav-dropdown-mobile.aberto {
            display: flex;
        }

        .nav-dropdown-mobile a {
            text-decoration: none;
            color: #1a1a1a;
            font-weight: 700;
            font-size: 0.95rem;
            padding: 12px 20px;
            border-radius: 15px;
            transition: background 0.2s;
        }

        .nav-dropdown-mobile a:hover {
            background: rgba(0, 0, 0, 0.05);
        }

        .nav-dropdown-mobile a.ativo {
            color: #2b7a8c;
            background: rgba(43, 122, 140, 0.08);
        }

        @keyframes fadeInDropdown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (min-width: 769px) {
            .nav-dropdown-mobile {
                display: none !important;
            }
        }
    </style>
    <link rel="stylesheet" href="assets/acessibilidade.css?v=20260925-v20">
</head>

<body>

    <!-- FUNDO ANIMADO INTERATIVO COM BRILHO QUE SEGUE O MOUSE -->
    <canvas id="fundoAnimadoCanvas" class="fundo-animado-canvas"></canvas>

    <!-- Navbar Premium -->
    <div class="nav-container-global escondida" id="nav-container-global">
        <nav class="navbar-flutuante">
            <!-- Logo SEM a estrela na navbar -->
            <a href="inicio.php" class="nav-logo-capsula" style="text-decoration: none; color: inherit; display: flex; align-items: center;">HELPFULL <span class="nav-seta-dropdown"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg></span></a>
            <ul class="links-capsula">
                <li><a href="Diario.php">Diário</a></li>
                <li><a href="Comunidade.php">Comunidade</a></li>
                <li><a href="ChatBOT.php">Helpy</a></li>
                <li><a href="Atividades.php">Atividades</a></li>
            </ul>
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
                        <!-- O sininho desaparece no CSS quando hover na perfil-capsula -->
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

            <div class="nav-dropdown-mobile" id="navDropdownMobile">
                <a href="inicio.php" class="ativo">Início</a>
                <a href="Diario.php">Diário</a>
                <a href="Comunidade.php">Comunidade</a>
                <a href="ChatBOT.php">Helpy</a>
                <a href="Atividades.php">Adicionais</a>
                <?php if ($usuarioLogado): ?>
                    <a href="Perfil.php">Perfil</a>
                <?php else: ?>
                    <a href="Comeco.php">Entrar</a>
                <?php endif; ?>
                <a href="javascript:void(0)" class="btn-abrir-acessibilidade" onclick="abrirPainelAcessibilidadeMobile(event);">Configurações</a>
            </div>
        </nav>

        <!-- Botão de Notificação Avulso (Aparece no Hover) -->
        <button class="btn-notificacao-separado" id="btnNotificacaoDetached" onclick="togglePainelNotificacoes()">
            <svg viewBox="0 0 24 24">
                <path
                    d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z" />
            </svg>
        </button>

        <!-- PAINEL DE NOTIFICAÇÕES (Animação iOS Saindo para a esquerda) -->
        <div class="painel-notificacoes" id="painelNotificacoes">
            <div class="painel-header-top">
                <h3>Notificações</h3>
                <button class="btn-fechar-painel" onclick="togglePainelNotificacoes()">×</button>
            </div>
            <div class="painel-actions">
                <button class="btn-limpar-pill" onclick="limparNotificacoes()">Limpar</button>
            </div>

            <div class="lista-notificacoes" id="containerListaNotificacoes">
                <!-- Será populado via JS pela IA -->
                <p style="font-size: 0.8rem; text-align: left; opacity: 0.6;">Nenhuma notificação nova.</p>
            </div>
        </div>
    </div>

    <div class="conteudo-site">

        <!-- Hero Logo COM a estrela no centro da tela -->
        <h1 class="logo-gigante">HELPFULL✦</h1>

        <!-- Hero Content -->
        <div class="caixa-vidro" id="heroCarrosselCard">
            <div class="carrossel-intro-container" id="carrosselContainer">
                <div class="carrossel-slides" id="carrosselSlides">
                    <!-- Slide 1 -->
                    <div class="carrossel-slide ativo" data-index="0">
                        <strong class="slide-titulo">Cada pequeno passo conta.</strong>
                        <p class="slide-desc">Sua jornada de autocuidado começa aqui. Acompanhe seu humor, registre seus pensamentos e descubra ferramentas para dias mais leves.</p>
                    </div>
                    <!-- Slide 2 -->
                    <div class="carrossel-slide" data-index="1">
                        <strong class="slide-titulo">Você não está sozinho nessa.</strong>
                        <p class="slide-desc">Um espaço pensado pra te ouvir, te ajudar a organizar as emoções e caminhar rumo ao seu melhor bem-estar.</p>
                    </div>
                    <!-- Slide 3 -->
                    <div class="carrossel-slide" data-index="2">
                        <strong class="slide-titulo">Bem-estar é um hábito, não um destino.</strong>
                        <p class="slide-desc">Construa, dia após dia, uma rotina mais consciente com apoio emocional sempre à mão.</p>
                    </div>
                    <!-- Slide 4 -->
                    <div class="carrossel-slide" data-index="3">
                        <strong class="slide-titulo">Sua mente merece atenção todos os dias.</strong>
                        <p class="slide-desc">Entenda seus padrões emocionais, celebre suas conquistas e encontre apoio nos momentos mais difíceis.</p>
                    </div>
                    <!-- Slide 5 -->
                    <div class="carrossel-slide" data-index="4">
                        <strong class="slide-titulo">Um espaço só seu, para respirar e recomeçar.</strong>
                        <p class="slide-desc">Aqui você encontra ferramentas simples para cuidar da sua saúde emocional, no seu tempo e do seu jeito.</p>
                    </div>
                    <!-- Slide 6 -->
                    <div class="carrossel-slide" data-index="5">
                        <strong class="slide-titulo">Pequenos hábitos, grandes transformações.</strong>
                        <p class="slide-desc">Registre, reflita e evolua. O HelpFull te acompanha em cada etapa da sua jornada emocional.</p>
                    </div>
                    <!-- Slide 7 -->
                    <div class="carrossel-slide" data-index="6">
                        <strong class="slide-titulo">Cuidar de você também é produtivo.</strong>
                        <p class="slide-desc">Organize seus pensamentos, entenda suas emoções e construa mais equilíbrio no seu dia a dia.</p>
                    </div>
                    <!-- Slide 8 -->
                    <div class="carrossel-slide" data-index="7">
                        <strong class="slide-titulo">Sua jornada emocional começa com um gesto simples.</strong>
                        <p class="slide-desc">Escrever, compartilhar e se cuidar. Tudo em um só lugar, feito para o seu bem-estar.</p>
                    </div>
                    <!-- Slide 9 -->
                    <div class="carrossel-slide" data-index="8">
                        <strong class="slide-titulo">Entenda o que você sente, no seu próprio ritmo.</strong>
                        <p class="slide-desc">Ferramentas pensadas para te ajudar a lidar com as emoções do dia a dia, com leveza e acolhimento.</p>
                    </div>
                    <!-- Slide 10 -->
                    <div class="carrossel-slide" data-index="9">
                        <strong class="slide-titulo">Porque toda emoção merece ser ouvida.</strong>
                        <p class="slide-desc">Um ambiente seguro para desabafar, refletir e crescer emocionalmente, sempre que você precisar.</p>
                    </div>
                </div>

                <!-- Indicadores (bolinhas) -->
                <div class="carrossel-dots" id="carrosselDots" aria-label="Navegação dos textos em destaque">
                    <button type="button" class="carrossel-dot ativo" data-slide="0" aria-label="Texto 1"></button>
                    <button type="button" class="carrossel-dot" data-slide="1" aria-label="Texto 2"></button>
                    <button type="button" class="carrossel-dot" data-slide="2" aria-label="Texto 3"></button>
                    <button type="button" class="carrossel-dot" data-slide="3" aria-label="Texto 4"></button>
                    <button type="button" class="carrossel-dot" data-slide="4" aria-label="Texto 5"></button>
                    <button type="button" class="carrossel-dot" data-slide="5" aria-label="Texto 6"></button>
                    <button type="button" class="carrossel-dot" data-slide="6" aria-label="Texto 7"></button>
                    <button type="button" class="carrossel-dot" data-slide="7" aria-label="Texto 8"></button>
                    <button type="button" class="carrossel-dot" data-slide="8" aria-label="Texto 9"></button>
                    <button type="button" class="carrossel-dot" data-slide="9" aria-label="Texto 10"></button>
                </div>
            </div>

            <div class="quadrado-grafico" id="carrosselImagens">
                <img src="assets/slides/slide_0.jpg" class="carrossel-img ativo" data-index="0" alt="Cada pequeno passo conta">
                <img src="assets/slides/slide_1.jpg" class="carrossel-img" data-index="1" alt="Você não está sozinho nessa">
                <img src="assets/slides/slide_2.jpg" class="carrossel-img" data-index="2" alt="Bem-estar é um hábito">
                <img src="assets/slides/slide_3.jpg" class="carrossel-img" data-index="3" alt="Sua mente merece atenção todos os dias">
                <img src="assets/slides/slide_4.jpg" class="carrossel-img" data-index="4" alt="Um espaço só seu para respirar">
                <img src="assets/slides/slide_5.jpg" class="carrossel-img" data-index="5" alt="Pequenos hábitos grandes transformações">
                <img src="assets/slides/slide_6.jpg" class="carrossel-img" data-index="6" alt="Cuidar de você também é produtivo">
                <img src="assets/slides/slide_7.jpg" class="carrossel-img" data-index="7" alt="Sua jornada emocional começa com um gesto simples">
                <img src="assets/slides/slide_8.jpg" class="carrossel-img" data-index="8" alt="Entenda o que você sente no seu próprio ritmo">
                <img src="assets/slides/slide_9.jpg" class="carrossel-img" data-index="9" alt="Porque toda emoção merece ser ouvida">
            </div>
        </div>
    </div>

    <div class="faixa-meio-branca">
        <div class="conteudo-site" style="padding-top: 0;">
            <div class="menu-estatitco" id="menu">
                <a href="Diario.php">Diário</a>
                <a href="Comunidade.php">Comunidade</a>
                <a href="ChatBOT.php">Helpy</a>
                <a href="Atividades.php">Atividades</a>
                <?php if ($usuarioLogado): ?>
                    <a href="Perfil.php">Perfil</a>
                <?php else: ?>
                    <a href="Comeco.php" style="color: #2b7a8c; font-weight: 800;">Entrar</a>
                <?php endif; ?>
            </div>

            <section class="secao-cartoes" style="margin-bottom: 0;">
                <a href="Diario.php" style="text-decoration: none; color: inherit;">
                    <div class="cartao">
                        <h3>Diário Pessoal</h3>
                        <p>Registre seus pensamentos diariamente. Escrever alivia a mente e ajuda a entender melhor suas
                            emoções.</p>
                    </div>
                </a>

                <a href="Comunidade.php" style="text-decoration: none; color: inherit;">
                    <div class="cartao">
                        <h3>Comunidade</h3>
                        <p>Compartilhe suas experiências, desabafe e encontre apoio mútuo em um espaço seguro e acolhedor.</p>
                    </div>
                </a>

                <a href="Atividades.php" style="text-decoration: none; color: inherit;">
                    <div class="cartao">
                        <h3>Atividades</h3>
                        <p>Acesse exercícios de respiração e relaxamento para reduzir o estresse e melhorar seu foco no
                            dia a dia.</p>
                    </div>
                </a>
            </section>
        </div>
    </div>

    <div class="conteudo-site" style="padding-top: 0;">
        <div class="bloco-grande-container">
            <div class="bloco-grande">
                <h2>Apoio emocional na palma da sua mão.</h2>
                <p>Todos temos dias difíceis, e você não precisa passar por eles sozinho. Explore um ambiente focado no
                    seu bem-estar, onde suas emoções são validadas. Acompanhe seu humor, registre sua jornada e descubra
                    ferramentas desenhadas para trazer mais clareza e tranquilidade para o seu dia a dia.</p>
            </div>
        </div>
    </div>

    <div class="secao-branca">
        <div class="secao-branca-conteudo">
            <div class="texto-extra-container">
                <p class="texto-extra-scrolled">
                    Precisa conversar agora? O Helpy foi treinado para te ouvir com empatia e total privacidade,
                    sem nenhum julgamento. Um espaço seguro para desabafar, organizar as ideias ou simplesmente
                    encontrar conforto a qualquer hora do dia.
                </p>
                <a href="ChatBOT.php" class="link-extra">Conversar com o Helpy →</a>
            </div>
            <footer class="rodape-simples">
                <strong>HELPFULL</strong>
                <div class="contato-info">
                    Entre em contato:<br><br>
                    Email:<br>
                    Intagram:<br>
                    WhatsApp:
                </div>
            </footer>
        </div>
    </div>

    <!-- CÓDIGO DA NOTIFICAÇÃO INTELIGENTE (TOAST CENTRAL) -->
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
        const navbar = document.getElementById('nav-container-global');
        const menuEstatico = document.getElementById('menu');

        function atualizarNavbarScroll() {
            if (menuEstatico) {
                const posicao = menuEstatico.getBoundingClientRect().top;
                if (posicao < 0) {
                    navbar.classList.remove('escondida');
                } else {
                    navbar.classList.add('escondida');
                }
            }
        }

        window.addEventListener('scroll', atualizarNavbarScroll);
        window.addEventListener('resize', atualizarNavbarScroll);
        atualizarNavbarScroll();

        // === MOBILE DROPDOWN TOGGLE ===
        const navLogo = document.querySelector('.nav-logo-capsula');
        const navDropdown = document.getElementById('navDropdownMobile');
        if (navLogo && navDropdown) {
            navLogo.addEventListener('click', function (e) {
                if (window.innerWidth <= 768) {
                    e.preventDefault();
                    e.stopPropagation();
                    const aberto = navDropdown.classList.toggle('aberto');
                    navLogo.classList.toggle('aberto', aberto);
                }
            });
            document.addEventListener('click', function (e) {
                if (!e.target.closest('.navbar-flutuante') && !e.target.closest('.nav-dropdown-mobile')) {
                    navDropdown.classList.remove('aberto');
                    navLogo.classList.remove('aberto');
                }
            });
        }

        // === CARROSSEL DE TEXTOS EM DESTAQUE ===
        (function () {
            const cardHero = document.getElementById('heroCarrosselCard');
            const slides = document.querySelectorAll('.carrossel-slide');
            const imagens = document.querySelectorAll('.carrossel-img');
            const dotsContainer = document.getElementById('carrosselDots');
            const dots = document.querySelectorAll('.carrossel-dot');

            if (!cardHero || !slides.length) return;

            let slideAtual = 0;
            const totalSlides = slides.length;
            let timerCarrossel = null;
            let timeoutDots = null;
            let cooldownScroll = false;

            function exibirDotsTemporariamente() {
                if (!dotsContainer) return;
                dotsContainer.classList.add('mostrar-temporario');
                if (timeoutDots) clearTimeout(timeoutDots);
                timeoutDots = setTimeout(function () {
                    dotsContainer.classList.remove('mostrar-temporario');
                }, 4000);
            }

            function irParaSlide(novoIndex, manual) {
                if (novoIndex < 0) {
                    novoIndex = totalSlides - 1;
                } else if (novoIndex >= totalSlides) {
                    novoIndex = 0;
                }

                if (novoIndex === slideAtual && manual) return;

                slides[slideAtual].classList.remove('ativo');
                if (imagens[slideAtual]) imagens[slideAtual].classList.remove('ativo');
                if (dots[slideAtual]) dots[slideAtual].classList.remove('ativo');

                slideAtual = novoIndex;

                slides[slideAtual].classList.add('ativo');
                if (imagens[slideAtual]) imagens[slideAtual].classList.add('ativo');
                if (dots[slideAtual]) dots[slideAtual].classList.add('ativo');

                // Revela as bolinhas para indicar que o texto trocou e há mais opções
                exibirDotsTemporariamente();

                // Reinicia a contagem de 1 minuto a partir do momento da troca
                reiniciarTimer();
            }

            function proximoSlide() {
                irParaSlide(slideAtual + 1);
            }

            function slideAnterior() {
                irParaSlide(slideAtual - 1);
            }

            function reiniciarTimer() {
                if (timerCarrossel) clearInterval(timerCarrossel);
                timerCarrossel = setInterval(proximoSlide, 60000); // 1 em 1 minuto (60.000 ms)
            }

            // Inicia o cronômetro automático de 1 minuto
            reiniciarTimer();

            // Clique nas bolinhas indicadoras
            dots.forEach(function (dot, index) {
                dot.addEventListener('click', function (e) {
                    e.stopPropagation();
                    irParaSlide(index, true);
                });
            });

            // Scroll do mouse (Roda / Wheel) sobre o card
            cardHero.addEventListener('wheel', function (e) {
                // Ignora micro-movimentos acidentais
                if (Math.abs(e.deltaY) < 12 && Math.abs(e.deltaX) < 12) return;

                e.preventDefault();
                if (cooldownScroll) return;
                cooldownScroll = true;
                setTimeout(function () {
                    cooldownScroll = false;
                }, 380);

                if (e.deltaY > 0 || e.deltaX > 0) {
                    proximoSlide();
                } else {
                    slideAnterior();
                }
            }, { passive: false });

            // Gestos de Touch no Celular (Swipe)
            let touchStartX = 0;
            let touchStartY = 0;
            let touchEndX = 0;
            let touchEndY = 0;
            let isTouchTracking = false;

            cardHero.addEventListener('touchstart', function (e) {
                if (e.touches && e.touches.length === 1) {
                    touchStartX = e.touches[0].clientX;
                    touchStartY = e.touches[0].clientY;
                    touchEndX = touchStartX;
                    touchEndY = touchStartY;
                    isTouchTracking = true;
                    exibirDotsTemporariamente();
                }
            }, { passive: true });

            cardHero.addEventListener('touchmove', function (e) {
                if (!isTouchTracking || !e.touches || e.touches.length !== 1) return;
                touchEndX = e.touches[0].clientX;
                touchEndY = e.touches[0].clientY;
            }, { passive: true });

            cardHero.addEventListener('touchend', function () {
                if (!isTouchTracking) return;
                isTouchTracking = false;
                const diffX = touchEndX - touchStartX;
                const diffY = touchEndY - touchStartY;

                // Se o deslize horizontal foi de ao menos 30px e maior que o vertical
                if (Math.abs(diffX) > 30 && Math.abs(diffX) > Math.abs(diffY)) {
                    if (diffX < 0) {
                        proximoSlide(); // Arrastou para a esquerda -> próximo
                    } else {
                        slideAnterior(); // Arrastou para a direita -> anterior
                    }
                }
            }, { passive: true });
        })();
    </script>
    <script src="assets/fundo-animado.js?v=20260925-v3"></script>
    <script src="notificacoes.js?v=20260924-v7" onerror="if(!window.togglePainelAcessibilidade){var s=document.createElement('script');s.src='assets/notificacoes.js?v=20260924-v7';document.body.appendChild(s);}"></script>

</body>

</html>