<?php
require_once 'conexao.php';

if (isset($_SESSION['usuario_id'])) {
    header("Location: inicio.php");
    exit();
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

        /* === FUNDO DE VÍDEO E FAIXA === */
        .video-fundo {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 70vh;
            object-fit: cover;
            z-index: 0;
            pointer-events: none;
        }

        .faixa-inferior {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 30vh;
            background: #f0f0f0;
            z-index: 0;
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
            .imagem-fundo-mobile { display: block !important; }
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
    </style>
</head>

<body>

    <video class="video-fundo" autoplay loop muted playsinline poster="assets/HELPFULL.png">
        <source src="assets/HelpFullVideoFundo.mp4" type="video/mp4">
    </video>
    <img class="imagem-fundo-mobile" src="assets/HELPFULL.png" alt="Plano de Fundo HelpFull">
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
                    window.location.href = 'inicio.php';
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
        document.getElementById('formLogin').addEventListener('submit', async (e) => {
            e.preventDefault();
            limparErro('erroLogin');

            const btn = document.getElementById('btnEntrar');
            const textoOriginal = btn.innerText;
            btn.innerHTML = loadingHTML;
            btn.disabled = true;

            const email = document.getElementById('loginEmail').value;
            const senha = document.getElementById('loginSenha').value;

            try {
                const response = await fetch('login_proc.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, senha })
                });
                const data = await response.json();
                if (data.sucesso) {
                    window.location.href = 'inicio.php';
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
</body>

</html>