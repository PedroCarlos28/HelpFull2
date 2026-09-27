<?php
require_once 'conexao.php';

if (isset($_SESSION['usuario_id'])) {
    if (isset($_GET['trocar']) || isset($_GET['logout'])) {
        limparSessaoUsuario();
    } else {
        try {
            $stmtVal = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
            $stmtVal->execute([$_SESSION['usuario_id']]);
            if ($stmtVal->fetch()) {
                header("Location: inicio.php");
                exit();
            }
        } catch (Exception $e) {
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HelpFull - Entrar</title>
    <link rel="icon" type="image/png" href="assets/logoHelpFull.png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #f0f0f0;
            overflow-x: hidden;
            overflow-y: auto;
            position: relative;
            padding: 20px 0;
        }

        /* === FUNDO ANIMADO INTERATIVO COM BRILHO QUE SEGUE O MOUSE === */
        .fundo-animado-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            z-index: 0;
            pointer-events: none;
            display: block;
            filter: blur(32px);
            -webkit-filter: blur(32px);
            transform: scale(1.08);
            transform-origin: center center;
        }

        .faixa-inferior {
            display: none !important;
        }

        /* === NAVBAR PADRÃO === */
        .navbar-topo {
            display: flex;
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 620px;
            max-width: 95%;
            z-index: 1000;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            padding: 12px 30px;
            border-radius: 50px;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .nav-col-esq { flex: 1; display: flex; justify-content: flex-start; }
        .nav-col-dir { flex: 1; display: flex; justify-content: flex-end; }

        .nav-logo {
            font-weight: 900;
            font-size: 1.05rem;
            color: #1a1a1a;
            letter-spacing: -0.5px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .titulo-central {
            font-weight: 900;
            font-size: 1.15rem;
            color: #1a1a1a;
            letter-spacing: -0.5px;
            text-align: center;
            white-space: nowrap;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #mainTitle { display: inline-block; }

        .blur-fade {
            animation: iosBlurFade 0.4s cubic-bezier(0.25, 0.1, 0.25, 1);
        }

        @keyframes iosBlurFade {
            0%   { opacity: 0; filter: blur(8px); transform: scale(0.95); }
            100% { opacity: 1; filter: blur(0px); transform: scale(1); }
        }

        .btn-voltar-inicio {
            background: #ffffff;
            color: #1a1a1a;
            text-decoration: none;
            padding: 8px 18px;
            border-radius: 30px;
            font-weight: 800;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            white-space: nowrap;
            transition: 0.3s;
        }

        .btn-voltar-inicio:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }

        /* === CONTAINER PRINCIPAL DO LOGIN === */
        .caixa-acesso {
            display: flex;
            flex-direction: column;
            width: 620px;
            max-width: 95%;
            position: relative;
            z-index: 10;
            margin-top: 60px;
            gap: 16px;
        }

        /* === CARD DE FORMULÁRIO === */
        .card-acesso {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(40px);
            -webkit-backdrop-filter: blur(40px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 45px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            position: relative;
            width: 100%;
        }

        .forms-slider {
            display: flex;
            width: 200%;
            transition: transform 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .form-view {
            width: 50%;
            padding: 40px 60px 45px 60px;
            display: flex;
            flex-direction: column;
        }

        .inputs-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 18px;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .input-group label {
            font-size: 1rem;
            font-weight: 900;
            color: #1a1a1a;
            margin-left: 5px;
        }

        .input-group input {
            width: 100%;
            height: 55px;
            background: #ffffff;
            border: 2px solid transparent;
            border-radius: 30px;
            padding: 0 25px;
            font-size: 1.1rem;
            font-weight: 700;
            color: #333;
            outline: none;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.03);
            transition: 0.3s;
        }

        .input-group input:focus {
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
            transform: translateY(-1px);
        }

        .input-group input.input-erro {
            border-color: #ff6b6b;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.15);
        }

        .input-group input.input-ok {
            border-color: #4caf7d;
            box-shadow: 0 0 0 3px rgba(76, 175, 125, 0.12);
        }

        /* === INDICADOR DE FORÇA DE SENHA === */
        .senha-feedback {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-top: -6px;
            margin-left: 5px;
        }

        .senha-barras {
            display: flex;
            gap: 5px;
        }

        .senha-barra {
            flex: 1;
            height: 4px;
            border-radius: 4px;
            background: #e0e0e0;
            transition: background 0.3s;
        }

        .senha-barra.ativa-fraca  { background: #ff6b6b; }
        .senha-barra.ativa-media  { background: #ffd93d; }
        .senha-barra.ativa-forte  { background: #4caf7d; }

        .senha-hint {
            font-size: 0.75rem;
            font-weight: 700;
            color: #888;
            transition: color 0.3s;
            min-height: 14px;
        }

        .senha-hint.fraca  { color: #ff6b6b; }
        .senha-hint.media  { color: #c9961b; }
        .senha-hint.forte  { color: #4caf7d; }

        /* === MENSAGEM ERRO INLINE === */
        .msg-erro-inline {
            font-size: 0.78rem;
            font-weight: 700;
            color: #ff6b6b;
            margin-left: 5px;
            min-height: 14px;
            display: none;
        }

        .msg-erro-inline.visivel { display: block; }

        /* === FOOTER BOTÕES === */
        .footer-botoes {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding-top: 20px;
            flex-shrink: 0;
        }

        .footer-botoes-direita {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .btn-form {
            height: 52px;
            padding: 0 40px;
            border-radius: 26px;
            border: none;
            font-weight: 900;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 140px;
        }

        .btn-secundario { background: #9cb4b8; color: white; }
        .btn-primario   { background: #2b7a8c; color: white; }

        .btn-form:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        /* === LOADING DOTS === */
        .loading-dots {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
        }

        .loading-dots div {
            width: 8px;
            height: 8px;
            background-color: #fff;
            border-radius: 50%;
            animation: bounceDots 0.5s infinite alternate;
        }

        .loading-dots div:nth-child(1) { animation-delay: 0s; }
        .loading-dots div:nth-child(2) { animation-delay: 0.15s; }
        .loading-dots div:nth-child(3) { animation-delay: 0.3s; }

        @keyframes bounceDots {
            from { transform: translateY(0); }
            to   { transform: translateY(-6px); }
        }

        /* Slide */
        .card-acesso.cad-mode .forms-slider {
            transform: translateX(-50%);
        }

        /* === TELA 2FA NO CARD DE ACESSO === */
        .card-acesso.modo-2fa .forms-slider {
            display: none !important;
        }

        .card-acesso.modo-2fa .view-2fa {
            display: flex !important;
            flex-direction: column;
            padding: 40px 60px 45px 60px;
            animation: fadeIn2FA 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        @keyframes fadeIn2FA {
            from {
                opacity: 0;
                transform: scale(0.96) translateY(10px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .badge-2fa-seguranca {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            align-self: flex-start;
            padding: 6px 14px;
            border-radius: 20px;
            background: rgba(43, 122, 140, 0.12);
            color: #2b7a8c;
            font-size: 0.8rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .texto-2fa-info {
            margin-bottom: 20px;
        }

        .titulo-2fa {
            font-size: 1.45rem;
            font-weight: 900;
            color: #1a1a1a;
            margin: 0 0 6px 0;
        }

        .desc-2fa {
            font-size: 0.95rem;
            color: #555;
            margin: 0;
            line-height: 1.5;
        }

        .desc-2fa strong {
            color: #2b7a8c;
            word-break: break-all;
        }

        .input-group-2fa {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 6px;
        }

        .input-group-2fa label {
            font-size: 0.95rem;
            font-weight: 800;
            color: #1a1a1a;
            margin-left: 5px;
        }

        .input-group-2fa input {
            width: 100%;
            height: 60px;
            background: #ffffff;
            border: 2px solid transparent;
            border-radius: 30px;
            padding: 0 20px;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: 12px;
            text-align: center;
            font-family: 'Courier New', Courier, monospace;
            color: #1b3d45;
            outline: none;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.04);
            transition: all 0.3s;
            box-sizing: border-box;
        }

        .input-group-2fa input:focus {
            border-color: #2b7a8c;
            box-shadow: 0 5px 25px rgba(43, 122, 140, 0.2);
        }

        .reenviar-2fa-box {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 18px;
            font-size: 0.88rem;
            color: #666;
            flex-wrap: wrap;
        }

        .btn-link-reenviar {
            background: none;
            border: none;
            color: #2b7a8c;
            font-weight: 800;
            cursor: pointer;
            padding: 0;
            text-decoration: underline;
            font-size: 0.88rem;
            transition: color 0.2s;
        }

        .btn-link-reenviar:hover {
            color: #1b3d45;
        }

        .btn-link-reenviar:disabled {
            color: #94a3b8;
            cursor: not-allowed;
            text-decoration: none;
        }

        .timer-reenviar {
            font-weight: 700;
            color: #888;
            font-size: 0.82rem;
        }

        @media (max-width: 520px) {
            .card-acesso.modo-2fa .view-2fa {
                padding: 30px 24px 35px 24px;
            }
            .input-group-2fa input {
                font-size: 1.6rem;
                letter-spacing: 8px;
                height: 52px;
            }
        }

        /* === BOTÃO BOLINHA GOOGLE === */
        .btn-google-circle {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            border: none;
            background: #ffffff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: transform 0.25s, box-shadow 0.25s;
            flex-shrink: 0;
            position: relative;
        }

        .btn-google-circle:hover {
            transform: translateY(-2px) scale(1.07);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.13);
        }

        .btn-google-circle:active { transform: scale(0.95); }

        .btn-google-circle .google-icon {
            width: 22px;
            height: 22px;
        }

        /* Spinner dentro da bolinha */
        .btn-google-circle .social-spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(0,0,0,0.12);
            border-top-color: #4285F4;
            border-radius: 50%;
            animation: spinIt 0.7s linear infinite;
        }

        .btn-google-circle.carregando .social-spinner { display: block; }
        .btn-google-circle.carregando .google-icon   { display: none; }

        @keyframes spinIt {
            to { transform: rotate(360deg); }
        }

        /* MOBILE */
        @media (max-width: 650px) {
            .caixa-acesso {
                width: 95%;
                margin-top: 75px;
                margin-bottom: 20px;
            }

            .form-view { padding: 30px 20px; }

            .navbar-topo {
                padding: 10px 15px;
                width: calc(100% - 30px);
            }

            .btn-form {
                padding: 0 20px;
                min-width: 110px;
                font-size: 0.9rem;
            }

            .card-social { padding: 20px 20px; }

            .botoes-sociais { grid-template-columns: 1fr; }
            .btn-social.full-width { grid-column: auto; }
        }

        @media (max-height: 600px) {
            body { justify-content: flex-start; padding-top: 100px; }
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

        @media (max-width: 768px) {
            .video-fundo, .faixa-inferior { display: none !important; }
            .imagem-fundo, .imagem-fundo-mobile { display: none !important; }
        }

        @media (max-width: 480px) {
            .caixa-acesso {
                width: 94%;
                margin-top: 75px;
                margin-bottom: 20px;
            }
            .form-view {
                padding: 25px 18px;
            }
            .footer-botoes {
                gap: 10px;
            }
            .footer-botoes-direita {
                gap: 8px;
            }
            .footer-botoes-direita .btn-form {
                min-width: 95px;
                padding: 0 14px;
                font-size: 0.88rem;
                height: 48px;
            }
            .btn-google-circle {
                width: 48px;
                height: 48px;
            }
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
    </style>
    <link rel="stylesheet" href="assets/acessibilidade.css?v=20260927-v3">
    <link rel="stylesheet" href="assets/transicao.css?v=20260926-v1">
</head>

<body>

    <!-- FUNDO ANIMADO INTERATIVO COM BRILHO QUE SEGUE O MOUSE -->
    <canvas id="fundoAnimadoCanvas" class="fundo-animado-canvas"></canvas>
    <div class="faixa-inferior"></div>

    <nav class="navbar-topo">
        <div class="nav-col-esq">
            <div class="nav-logo">HELPFULL✦</div>
        </div>
        <div class="titulo-central">
            <span id="mainTitle">Entrar</span>
        </div>
        <div class="nav-col-dir">
            <a href="inicio.php" class="btn-voltar-inicio">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
                    stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                Início
            </a>
        </div>
    </nav>

    <div class="caixa-acesso">

        <!-- CARD FORMULÁRIOS (email + senha) -->
        <div class="card-acesso" id="mainCard">
            <div class="forms-slider">

                <!-- LOGIN -->
                <form id="formLogin" class="form-view">
                    <div class="inputs-container">
                        <div class="input-group">
                            <label for="loginEmail">Email:</label>
                            <input type="email" id="loginEmail" autocomplete="email" required>
                        </div>
                        <div class="input-group">
                            <label for="loginSenha">Senha:</label>
                            <input type="password" id="loginSenha" autocomplete="current-password" required>
                        </div>
                        <span id="erroLogin" class="msg-erro-inline"></span>
                    </div>
                    <div class="footer-botoes">
                        <button type="button" class="btn-google-circle" id="btnGoogleLogin" onclick="loginSocial('google')" title="Entrar com Google">
                            <div class="social-spinner"></div>
                            <svg class="google-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                            </svg>
                        </button>
                        <div class="footer-botoes-direita">
                            <button type="button" class="btn-form btn-secundario" onclick="toggleMode()">Criar Conta</button>
                            <button type="submit" class="btn-form btn-primario" id="btnEntrar">Entrar</button>
                        </div>
                    </div>
                </form>

                <!-- CADASTRO -->
                <form id="formCadastro" class="form-view">
                    <div class="inputs-container">
                        <div class="input-group">
                            <label for="cadNome">Nome:</label>
                            <input type="text" id="cadNome" autocomplete="name" required>
                        </div>
                        <div class="input-group">
                            <label for="cadEmail">Email:</label>
                            <input type="email" id="cadEmail" autocomplete="email" required>
                        </div>
                        <div class="input-group">
                            <label for="cadSenha">Senha:</label>
                            <input type="password" id="cadSenha" minlength="6" autocomplete="new-password" required>
                            <div class="senha-feedback" id="senhaFeedback" style="display:none;">
                                <div class="senha-barras">
                                    <div class="senha-barra" id="barra1"></div>
                                    <div class="senha-barra" id="barra2"></div>
                                    <div class="senha-barra" id="barra3"></div>
                                </div>
                                <span class="senha-hint" id="senhaHint"></span>
                            </div>
                        </div>
                        <span id="erroCadastro" class="msg-erro-inline"></span>
                    </div>
                    <div class="footer-botoes">
                        <button type="button" class="btn-google-circle" id="btnGoogleCad" onclick="loginSocial('google')" title="Cadastrar com Google">
                            <div class="social-spinner"></div>
                            <svg class="google-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                            </svg>
                        </button>
                        <div class="footer-botoes-direita">
                            <button type="button" class="btn-form btn-secundario" onclick="toggleMode()">Entrar</button>
                            <button type="submit" class="btn-form btn-primario" id="btnCadastrar">Finalizar</button>
                        </div>
                    </div>
                </form>

            </div>

            <!-- TELA DE VERIFICAÇÃO EM DUAS ETAPAS (2FA) -->
            <div class="view-2fa" id="view2FA" style="display: none;">
                <div class="badge-2fa-seguranca">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                    <span>Segurança da Conta</span>
                </div>

                <div class="texto-2fa-info">
                    <h3 class="titulo-2fa">Código de Verificação</h3>
                    <p class="desc-2fa">
                        Enviamos um código de 6 dígitos para o e-mail:<br>
                        <strong id="emailDestino2FA">seu-email@dominio.com</strong>
                    </p>
                </div>

                <form id="form2FA" class="form-2fa-inner" onsubmit="event.preventDefault(); verificarCodigo2FA();">
                    <div class="input-group-2fa">
                        <label for="inputCodigo2FA">Digite o código:</label>
                        <input type="text" id="inputCodigo2FA" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code" placeholder="------" required>
                    </div>
                    <span id="erro2FA" class="msg-erro-inline"></span>

                    <div class="footer-botoes" style="margin-top: 18px;">
                        <button type="button" class="btn-form btn-secundario" id="btnVoltarLogin" onclick="voltarAoLogin()">Voltar</button>
                        <button type="submit" class="btn-form btn-primario" id="btnConfirmar2FA">Confirmar</button>
                    </div>

                    <div class="reenviar-2fa-box">
                        <span id="textoReenviar">Não recebeu o código?</span>
                        <button type="button" id="btnReenviar2FA" onclick="reenviarCodigo2FA()" class="btn-link-reenviar">Reenviar código</button>
                        <span id="timerReenviar2FA" class="timer-reenviar" style="display: none;"></span>
                    </div>
                </form>
            </div>

        </div>


    </div><!-- /caixa-acesso -->

    <!-- Firebase SDK -->
    <script type="module">
        import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-app.js';
        import {
            getAuth,
            GoogleAuthProvider,
            signInWithPopup,
            signInWithRedirect,
            getRedirectResult
        } from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-auth.js';

        // ============================================================
        // ⚙️  CONFIGURE AQUI AS SUAS CREDENCIAIS DO FIREBASE
        //     1. Acesse https://console.firebase.google.com/
        //     2. Crie um projeto (ou use um existente)
        //     3. Vá em "Configurações do Projeto" > "Seus apps" > SDK Web
        //     4. Cole os valores abaixo
        // ============================================================
        const firebaseConfig = {
            apiKey:            "AIzaSyCm9BVm73imctbpFxFWV9YKX30zm8QB27I",
            authDomain:        "helpfull-e4aae.firebaseapp.com",
            projectId:         "helpfull-e4aae",
            storageBucket:     "helpfull-e4aae.firebasestorage.app",
            messagingSenderId: "737958838062",
            appId:             "1:737958838062:web:4d23292baf7bd6fdcb04bf",
            measurementId:     "G-5GE5SNZX81"
        };
        // ============================================================

        const FIREBASE_CONFIGURADO = firebaseConfig.apiKey !== "COLE_AQUI_SUA_API_KEY";

        let app, auth;

        // Função compartilhada para enviar dados ao PHP e autenticar no sistema
        async function finalizarLoginOAuth(user) {
            const btnLogin = document.getElementById('btnGoogleLogin');
            const btnCad   = document.getElementById('btnGoogleCad');
            if (btnLogin) { btnLogin.classList.add('carregando'); btnLogin.disabled = true; }
            if (btnCad)   { btnCad.classList.add('carregando');   btnCad.disabled   = true; }

            try {
                const idToken = await user.getIdToken();
                const response = await fetch('oauth_firebase.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        idToken: idToken,
                        email:   user.email,
                        nome:    user.displayName,
                        foto:    user.photoURL
                    })
                });

                const rawText = await response.text();
                let data = null;
                try {
                    data = JSON.parse(rawText);
                } catch (jsonErr) {
                    console.error('Resposta bruta recebida:', rawText);
                    const match = rawText.match(/\{[\s\S]*\}/);
                    if (match) {
                        try { data = JSON.parse(match[0]); } catch (e2) {}
                    }
                }

                if (data && data.sucesso) {
                    if (data.requer_2fa) {
                        if (btnLogin) { btnLogin.classList.remove('carregando'); btnLogin.disabled = false; }
                        if (btnCad)   { btnCad.classList.remove('carregando');   btnCad.disabled   = false; }
                        emailPendente2FA = data.email || user.email;
                        abrirModo2FA(data.email_mascarado || emailPendente2FA, data.debug_codigo);
                    } else {
                        window.location.href = 'inicio.php';
                    }
                } else if (data) {
                    alert('Erro no login: ' + (data.mensagem || 'Tente novamente.'));
                    if (btnLogin) { btnLogin.classList.remove('carregando'); btnLogin.disabled = false; }
                    if (btnCad)   { btnCad.classList.remove('carregando');   btnCad.disabled   = false; }
                } else {
                    const msgLimpa = rawText.replace(/<[^>]*>?/gm, ' ').replace(/\s+/g, ' ').trim();
                    alert('Erro no servidor: ' + (msgLimpa || 'Resposta inválida do servidor.'));
                    if (btnLogin) { btnLogin.classList.remove('carregando'); btnLogin.disabled = false; }
                    if (btnCad)   { btnCad.classList.remove('carregando');   btnCad.disabled   = false; }
                }
            } catch (err) {
                console.error('Erro ao autenticar com o servidor:', err);
                alert('Erro de comunicação com o servidor: ' + (err.message || err));
                if (btnLogin) { btnLogin.classList.remove('carregando'); btnLogin.disabled = false; }
                if (btnCad)   { btnCad.classList.remove('carregando');   btnCad.disabled   = false; }
            }
        }

        if (FIREBASE_CONFIGURADO) {
            app  = initializeApp(firebaseConfig);
            auth = getAuth(app);

            // Processa o resultado caso venha de um redirecionamento
            getRedirectResult(auth).then(async (result) => {
                if (result && result.user) {
                    await finalizarLoginOAuth(result.user);
                }
            }).catch((err) => {
                console.error('Redirect error:', err);
            });
        }

        // Expõe a função para o onclick inline dos botões Google
        window.loginSocial = async function() {
            if (!FIREBASE_CONFIGURADO) {
                mostrarInstrucoesFirebase();
                return;
            }

            const isCad = document.getElementById('mainCard').classList.contains('cad-mode');
            const btn   = document.getElementById(isCad ? 'btnGoogleCad' : 'btnGoogleLogin');
            if (btn) { btn.classList.add('carregando'); btn.disabled = true; }

            const provider = new GoogleAuthProvider();
            provider.setCustomParameters({ prompt: 'select_account' });

            try {
                // Tenta abrir em Popup primeiro (muito mais rápido e confiável no localhost)
                const result = await signInWithPopup(auth, provider);
                if (result && result.user) {
                    await finalizarLoginOAuth(result.user);
                }
            } catch (err) {
                console.warn('Popup falhou ou bloqueado:', err);
                // Se o popup for bloqueado pelo navegador, tenta via Redirect como fallback
                if (err.code === 'auth/popup-blocked' || err.code === 'auth/cancelled-popup-request') {
                    try {
                        await signInWithRedirect(auth, provider);
                    } catch (redirectErr) {
                        alert('Erro ao redirecionar: ' + redirectErr.message);
                        if (btn) { btn.classList.remove('carregando'); btn.disabled = false; }
                    }
                } else if (err.code !== 'auth/popup-closed-by-user') {
                    alert('Erro ao conectar com Google: ' + (err.message || err.code));
                    if (btn) { btn.classList.remove('carregando'); btn.disabled = false; }
                } else {
                    // Usuário apenas fechou a janela
                    if (btn) { btn.classList.remove('carregando'); btn.disabled = false; }
                }
            }
        };
    </script>

    <script>
        // === TOGGLE MODO LOGIN / CADASTRO ===
        function toggleMode() {
            const card = document.getElementById('mainCard');
            const title = document.getElementById('mainTitle');

            card.classList.toggle('cad-mode');

            title.classList.remove('blur-fade');
            void title.offsetWidth;

            title.innerText = card.classList.contains('cad-mode') ? 'Cadastrar' : 'Entrar';
            title.classList.add('blur-fade');

            // Limpa erros ao trocar
            document.getElementById('erroLogin').classList.remove('visivel');
            document.getElementById('erroCadastro').classList.remove('visivel');
        }

        // === INDICADOR DE FORÇA DE SENHA ===
        const cadSenha = document.getElementById('cadSenha');

        cadSenha.addEventListener('input', function() {
            const val = this.value;
            const feedback = document.getElementById('senhaFeedback');
            const hint     = document.getElementById('senhaHint');
            const b1 = document.getElementById('barra1');
            const b2 = document.getElementById('barra2');
            const b3 = document.getElementById('barra3');

            if (val.length === 0) {
                feedback.style.display = 'none';
                this.classList.remove('input-erro', 'input-ok');
                return;
            }

            feedback.style.display = 'flex';
            b1.className = 'senha-barra';
            b2.className = 'senha-barra';
            b3.className = 'senha-barra';
            hint.className = 'senha-hint';

            if (val.length < 6) {
                b1.classList.add('ativa-fraca');
                hint.textContent = 'Muito curta — mínimo 6 caracteres';
                hint.classList.add('fraca');
                this.classList.add('input-erro');
                this.classList.remove('input-ok');
            } else if (val.length < 10 || !/[0-9]/.test(val) || !/[A-Z]/.test(val)) {
                b1.classList.add('ativa-media');
                b2.classList.add('ativa-media');
                hint.textContent = 'Senha razoável';
                hint.classList.add('media');
                this.classList.remove('input-erro');
                this.classList.add('input-ok');
            } else {
                b1.classList.add('ativa-forte');
                b2.classList.add('ativa-forte');
                b3.classList.add('ativa-forte');
                hint.textContent = 'Senha forte 💪';
                hint.classList.add('forte');
                this.classList.remove('input-erro');
                this.classList.add('input-ok');
            }
        });

        // === LOADING DOTS HTML ===
        const loadingHTML = '<div class="loading-dots"><div></div><div></div><div></div></div>';

        // === MOSTRAR ERRO INLINE ===
        function mostrarErro(elId, msg) {
            const el = document.getElementById(elId);
            el.textContent = msg;
            el.classList.add('visivel');
        }

        function limparErro(elId) {
            const el = document.getElementById(elId);
            el.textContent = '';
            el.classList.remove('visivel');
        }

        // === LOGIN ===
        let emailPendente2FA = '';
        let timerCooldown2FA = null;

        document.getElementById('formLogin').addEventListener('submit', async (e) => {
            e.preventDefault();
            limparErro('erroLogin');

            const btn = document.getElementById('btnEntrar');
            const textoOriginal = btn.innerText;
            btn.innerHTML = loadingHTML;
            btn.disabled = true;

            const email = document.getElementById('loginEmail').value.trim();
            const senha = document.getElementById('loginSenha').value;

            try {
                const response = await fetch('login_proc.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, senha })
                });
                const data = await response.json();
                if (data.sucesso) {
                    if (data.requer_2fa) {
                        // Ativa a tela de verificação em duas etapas
                        emailPendente2FA = email;
                        abrirModo2FA(data.email_mascarado || email, data.debug_codigo);
                    } else {
                        window.location.href = 'inicio.php';
                    }
                } else {
                    mostrarErro('erroLogin', data.mensagem || 'Email ou senha incorretos.');
                }
            } catch (error) {
                mostrarErro('erroLogin', 'Erro ao conectar. Tente novamente.');
            } finally {
                btn.innerText = textoOriginal;
                btn.disabled = false;
            }
        });

        // === FUNÇÕES DE 2FA ===
        function abrirModo2FA(emailMascarado, debugCodigo = null) {
            const card = document.getElementById('mainCard');
            const mainTitle = document.getElementById('mainTitle');
            const emailDest = document.getElementById('emailDestino2FA');
            const inputCodigo = document.getElementById('inputCodigo2FA');

            card.classList.add('modo-2fa');
            if (mainTitle) {
                mainTitle.innerText = 'Verificação';
                mainTitle.classList.remove('blur-fade');
                void mainTitle.offsetWidth;
                mainTitle.classList.add('blur-fade');
            }
            if (emailDest) emailDest.innerText = emailMascarado;
            limparErro('erro2FA');
            if (inputCodigo) {
                inputCodigo.value = '';
                setTimeout(() => inputCodigo.focus(), 300);
            }

            iniciarCooldownReenviar(25);

            if (debugCodigo) {
                mostrarToastLocal(`Código de teste gerado: ${debugCodigo}`);
            }
        }

        function voltarAoLogin() {
            const card = document.getElementById('mainCard');
            const mainTitle = document.getElementById('mainTitle');
            card.classList.remove('modo-2fa');
            if (mainTitle) {
                mainTitle.innerText = 'Entrar';
                mainTitle.classList.remove('blur-fade');
                void mainTitle.offsetWidth;
                mainTitle.classList.add('blur-fade');
            }
            limparErro('erro2FA');
            if (timerCooldown2FA) clearInterval(timerCooldown2FA);
        }

        async function verificarCodigo2FA() {
            limparErro('erro2FA');
            const btn = document.getElementById('btnConfirmar2FA');
            const inputCodigo = document.getElementById('inputCodigo2FA');
            const codigo = inputCodigo.value.trim().replace(/\D/g, '');

            if (!codigo || codigo.length !== 6) {
                mostrarErro('erro2FA', 'Digite o código de 6 dígitos.');
                inputCodigo.focus();
                return;
            }

            const textoOriginal = btn.innerText;
            btn.innerHTML = loadingHTML;
            btn.disabled = true;

            try {
                const resp = await fetch('verificar_2fa.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        acao: 'verificar',
                        email: emailPendente2FA,
                        codigo: codigo
                    })
                });
                const data = await resp.json();
                if (data.sucesso) {
                    window.location.href = 'inicio.php';
                } else {
                    mostrarErro('erro2FA', data.mensagem || 'Código incorreto.');
                    inputCodigo.focus();
                    inputCodigo.select();
                }
            } catch (e) {
                mostrarErro('erro2FA', 'Erro de conexão. Tente novamente.');
            } finally {
                btn.innerText = textoOriginal;
                btn.disabled = false;
            }
        }

        async function reenviarCodigo2FA() {
            limparErro('erro2FA');
            const btnReenviar = document.getElementById('btnReenviar2FA');
            btnReenviar.disabled = true;
            btnReenviar.textContent = 'Enviando...';

            try {
                const resp = await fetch('verificar_2fa.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        acao: 'reenviar',
                        email: emailPendente2FA
                    })
                });
                const data = await resp.json();
                if (data.sucesso) {
                    mostrarToastLocal(data.mensagem || 'Código reenviado com sucesso!');
                    if (data.debug_codigo) {
                        setTimeout(() => mostrarToastLocal(`Código de teste gerado: ${data.debug_codigo}`), 1000);
                    }
                    iniciarCooldownReenviar(25);
                } else {
                    mostrarErro('erro2FA', data.mensagem || 'Erro ao reenviar código.');
                    btnReenviar.disabled = false;
                    btnReenviar.textContent = 'Reenviar código';
                }
            } catch (e) {
                mostrarErro('erro2FA', 'Erro de conexão ao reenviar código.');
                btnReenviar.disabled = false;
                btnReenviar.textContent = 'Reenviar código';
            }
        }

        function iniciarCooldownReenviar(segundos) {
            const btnReenviar = document.getElementById('btnReenviar2FA');
            const timer = document.getElementById('timerReenviar2FA');
            if (!btnReenviar || !timer) return;

            if (timerCooldown2FA) clearInterval(timerCooldown2FA);

            let restante = segundos;
            btnReenviar.style.display = 'none';
            timer.style.display = 'inline';
            timer.textContent = `(${restante}s)`;

            timerCooldown2FA = setInterval(() => {
                restante--;
                if (restante <= 0) {
                    clearInterval(timerCooldown2FA);
                    timer.style.display = 'none';
                    btnReenviar.style.display = 'inline';
                    btnReenviar.disabled = false;
                    btnReenviar.textContent = 'Reenviar código';
                } else {
                    timer.textContent = `(${restante}s)`;
                }
            }, 1000);
        }

        // Auto-envio ao preencher os 6 dígitos
        document.getElementById('inputCodigo2FA')?.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
            if (this.value.length === 6) {
                verificarCodigo2FA();
            }
        });

        function mostrarToastLocal(msg, tipo = 'info') {
            let toast = document.getElementById('toastLocal2FA');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'toastLocal2FA';
                toast.className = 'toast-feedback-2fa';
                document.body.appendChild(toast);
            }

            const msgLower = (msg || '').toLowerCase();
            const isDesativado = tipo === 'desativado' || msgLower.includes('desativad');
            const isSucesso = tipo === 'sucesso' || msgLower.includes('ativad') || msgLower.includes('sucesso') || msgLower.includes('gerado');
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

            toast.className = `toast-feedback-2fa ${classeTipo}`;
            toast.innerHTML = `<span class="toast-icone-wrap">${svgIcon}</span><span>${msg}</span>`;

            void toast.offsetWidth;
            toast.classList.add('mostrar');

            clearTimeout(window.__toastLocalTimer);
            window.__toastLocalTimer = setTimeout(() => {
                toast.classList.remove('mostrar');
            }, 4000);
        }

        // === CADASTRO ===
        document.getElementById('formCadastro').addEventListener('submit', async (e) => {
            e.preventDefault();
            limparErro('erroCadastro');

            const senha = document.getElementById('cadSenha').value;

            // Validação frontend mínimo 6 chars
            if (senha.length < 6) {
                mostrarErro('erroCadastro', 'A senha deve ter pelo menos 6 caracteres.');
                document.getElementById('cadSenha').classList.add('input-erro');
                return;
            }

            const btn = document.getElementById('btnCadastrar');
            const textoOriginal = btn.innerText;
            btn.innerHTML = loadingHTML;
            btn.disabled = true;

            const nome  = document.getElementById('cadNome').value;
            const email = document.getElementById('cadEmail').value;

            try {
                const response = await fetch('cadastro_proc.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ nome, email, senha })
                });
                const data = await response.json();
                if (data.sucesso) {
                    window.location.href = 'inicio.php';
                } else {
                    mostrarErro('erroCadastro', data.mensagem || 'Erro ao cadastrar.');
                }
            } catch (error) {
                mostrarErro('erroCadastro', 'Erro ao conectar. Tente novamente.');
            } finally {
                btn.innerText = textoOriginal;
                btn.disabled = false;
            }
        });

        // === INSTRUÇÕES FIREBASE ===
        function mostrarInstrucoesFirebase() {
            alert(
                '🔧 Como configurar o Firebase:\n\n' +
                '1. Acesse https://console.firebase.google.com/\n' +
                '2. Crie um projeto novo\n' +
                '3. Clique em "Adicionar app" > Web (</> )\n' +
                '4. Copie as credenciais firebaseConfig\n' +
                '5. Cole no arquivo Comeco.php (onde está COLE_AQUI...)\n' +
                '6. No Firebase Console: Authentication > Sign-in method\n' +
                '   Ative: Google, GitHub, Facebook, Apple, Microsoft\n\n' +
                'Após isso, os botões sociais funcionarão automaticamente.'
            );
        }

        window.mostrarInstrucoesFirebase = mostrarInstrucoesFirebase;
    </script>
    <script src="assets/fundo-animado.js?v=20260925-v4"></script>
    <script src="assets/transicao.js?v=20260926-v1"></script>
</body>

</html>