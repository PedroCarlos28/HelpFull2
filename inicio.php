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

        /* FUNDO */
        .video-fundo {
            position: fixed;
            top: 50%;
            left: 50%;
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            object-fit: cover;
            z-index: 0;
            pointer-events: none;
            transform: translate(-50%, -50%);
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
            background: #fff;
        }

        .quadrado-grafico img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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
    <link rel="stylesheet" href="assets/acessibilidade.css?v=20260925-v17">
</head>

<body>

    <video class="video-fundo" autoplay loop muted playsinline poster="assets/HELPFULL.png">
        <source src="assets/HelpFullVideoFundo.mp4" type="video/mp4">
    </video>
    <img class="imagem-fundo-mobile" src="assets/HELPFULL.png" alt="Plano de Fundo HelpFull">

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
        <div class="caixa-vidro">
            <p class="texto-intro">
                O bem-estar impulsiona a motivação e os relacionamentos,
                enquanto dificuldades emocionais prejudicam o humor e a tomada de decisões.
            </p>
            <div class="quadrado-grafico">
                <img src="assets/HELPFULL.png" alt="Imagem HelpFull">
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
    </script>
    <script src="notificacoes.js?v=20260924-v7" onerror="if(!window.togglePainelAcessibilidade){var s=document.createElement('script');s.src='assets/notificacoes.js?v=20260924-v7';document.body.appendChild(s);}"></script>

</body>

</html>