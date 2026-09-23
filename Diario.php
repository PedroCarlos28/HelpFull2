<?php
require_once 'conexao.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: Comeco.php");
    exit;
}

// ==========================================
// PROCESSAR SALVAMENTO DO DIÁRIO (VIA AJAX)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'salvar_diario') {
    header('Content-Type: application/json');

    $conteudo = trim($_POST['conteudo'] ?? '');
    $emocao = trim($_POST['emocao'] ?? '');
    if (empty($emocao))
        $emocao = null; // Permite salvar sem emoção

    if (empty($conteudo) && empty($emocao)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Preencha o diário ou escolha uma emoção.']);
        exit;
    }

    try {
        $hoje = date('Y-m-d');
        
        // Verifica se é o primeiro texto de diário do dia
        $stmtCheckD = $pdo->prepare("SELECT COUNT(*) FROM diario WHERE usuario_id = ? AND DATE(criado_em) = ? AND texto_diario != '' AND texto_diario IS NOT NULL");
        $stmtCheckD->execute([$_SESSION['usuario_id'], $hoje]);
        $primeiroDiario = ($stmtCheckD->fetchColumn() == 0 && !empty($conteudo));

        // Verifica se é a primeira emoção do dia
        $stmtCheckE = $pdo->prepare("SELECT COUNT(*) FROM diario WHERE usuario_id = ? AND DATE(criado_em) = ? AND emocao_selecionada IS NOT NULL");
        $stmtCheckE->execute([$_SESSION['usuario_id'], $hoje]);
        $primeiraEmocao = ($stmtCheckE->fetchColumn() == 0 && !empty($emocao));

        $stmt = $pdo->prepare("INSERT INTO diario (usuario_id, texto_diario, emocao_selecionada, criado_em) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$_SESSION['usuario_id'], $conteudo, $emocao]);
        
        echo json_encode([
            'sucesso' => true,
            'meta_diario_concluida' => $primeiroDiario,
            'meta_emocao_concluida' => $primeiraEmocao
        ]);
    } catch (PDOException $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
    }
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuarioLogado = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HelpFull - Diário</title>
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
            padding: 95px 20px 0 20px;
        }

        /* NAVBAR */
        .nav-container-global {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            gap: 25px;
            z-index: 2000;
            transition: all 0.4s ease;
        }

        .navbar-topo {
            display: flex;
            justify-content: space-between;
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

        .item-notificacao.alta .barra-intensidade { background: #eab8b8; }
        .item-notificacao.media .barra-intensidade { background: #fff1a0; }
        .item-notificacao.baixa .barra-intensidade { background: #b5ff99; }

        .notif-content { flex: 1; }
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

        .toast-notificacao.alta .toast-barra { background: #eab8b8; }
        .toast-notificacao.media .toast-barra { background: #fff1a0; }
        .toast-notificacao.baixa .toast-barra { background: #b5ff99; }

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

        /* CAIXA AGRUPADORA PRINCIPAL */
        .caixa-agrupadora {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-radius: 40px;
            padding: 50px 60px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05), inset 0 2px 5px rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.6);
            margin-bottom: 30px;
        }

        .texto-intro-diario {
            font-size: 1.05rem;
            line-height: 1.5;
            margin-bottom: 40px;
            font-weight: 700;
            color: #1a1a1a;
        }

        .textarea-container {
            background: #ececec;
            border-radius: 25px;
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.05), 0 5px 15px rgba(0, 0, 0, 0.03);
            border: 1px solid #dfdfdf;
        }

        .textarea-diario {
            width: 100%;
            min-height: 200px;
            background: transparent;
            border: none;
            resize: vertical;
            font-size: 1.15rem;
            font-family: inherit;
            color: #1a1a1a;
            line-height: 1.5;
            font-weight: 700;
            outline: none;
        }

        .textarea-diario::placeholder {
            color: #888;
            font-weight: 700;
        }

        .titulo-emocoes {
            font-size: 1.25rem;
            font-weight: 800;
            margin-bottom: 25px;
        }

        .container-emocoes {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: space-between;
        }

        .btn-emocao {
            flex: 1;
            min-width: 100px;
            padding: 12px 0;
            border-radius: 50px;
            border: none;
            font-weight: 800;
            font-size: 0.95rem;
            color: #1a1a1a;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .btn-emocao:hover { transform: scale(1.05); }

        .btn-emocao.selecionada {
            border: 2px solid rgba(0, 0, 0, 0.2);
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .emocao-irritado { background-color: #ff9b9b; }
        .emocao-ansioso { background-color: #ffce99; }
        .emocao-feliz { background-color: #fffa99; }
        .emocao-calmo { background-color: #bfff99; }
        .emocao-triste { background-color: #99d6ff; }
        .emocao-amoroso { background-color: #ff99eb; }

        .container-acoes {
            display: flex;
            justify-content: flex-end;
            gap: 20px;
            margin-bottom: 60px;
        }

        .btn-acao {
            padding: 15px 40px;
            border-radius: 50px;
            border: none;
            font-size: 1.15rem;
            font-weight: 800;
            color: #ffffff;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-acao:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .btn-apagar { background-color: #b57a7b; }
        .btn-salvar { background-color: #6b8a95; }

        .loading-dots {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-left: 8px;
            vertical-align: middle;
        }
        .loading-dots div {
            width: 4px;
            height: 4px;
            background-color: #fff;
            border-radius: 50%;
            animation: bounceDots 0.5s infinite alternate;
        }
        .loading-dots div:nth-child(2) { animation-delay: 0.15s; }
        .loading-dots div:nth-child(3) { animation-delay: 0.3s; }
        @keyframes bounceDots {
            from { transform: translateY(2px); }
            to { transform: translateY(-4px); }
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

        /* RESPONSIVIDADE MAX 1024px */
        @media (max-width: 1024px) {
            .navbar-topo {
                width: auto;
                max-width: none;
            }
            .nav-links { gap: 25px; }
            .nav-links a { font-size: 0.95rem; }
        }

        /* TABLET E MOBILE (max-width: 768px) */
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
            .conteudo-site { padding-top: 110px; }
            
            .texto-intro-diario { font-size: 1rem; }

            .caixa-agrupadora { padding: 30px; border-radius: 25px; margin-bottom: 20px; }
            .textarea-container { padding: 20px; margin-bottom: 30px; }
            
            .titulo-emocoes { font-size: 1.15rem; text-align: left; }
            .container-emocoes { gap: 10px; justify-content: flex-start; }
            .btn-emocao { 
                flex: 1 1 calc(33.333% - 10px); 
                width: auto; 
                min-width: 100px;
                font-size: 0.9rem;
                padding: 10px 0;
            }

            .container-acoes { 
                flex-direction: column; 
                gap: 15px; 
                margin-bottom: 40px; 
            }
            .btn-acao { 
                width: 100%; 
                text-align: center; 
                padding: 15px;
            }

            .btn-notificacao-separado { right: 10px; }
            .painel-notificacoes { right: 10px; width: calc(100vw - 40px); max-width: 320px; }
            .toast-notificacao { width: 92%; max-width: 380px; }
        }

        /* MOBILE PEQUENO (max-width: 480px) */
        @media (max-width: 480px) {
            .navbar-topo { width: calc(100% - 30px); padding: 12px 20px; }
            .conteudo-site { padding: 100px 15px 0 15px; }

            .texto-intro-diario { font-size: 0.95rem; text-align: left; }
            
            .caixa-agrupadora { padding: 20px; border-radius: 20px; }
            .textarea-container { padding: 15px; margin-bottom: 25px; }
            .textarea-diario { min-height: 200px; font-size: 1.05rem; }
            
            .btn-emocao { flex: 1 1 calc(50% - 10px); min-width: auto; font-size: 0.85rem; padding: 10px 5px; }
        }
    </style>
    <link rel="stylesheet" href="assets/acessibilidade.css?v=20260916-v3">
</head>

<body>

    <img class="imagem-fundo" src="assets/HELPFULL.png" alt="Plano de Fundo HelpFull">

    <div class="nav-container-global">
        <nav class="navbar-topo">
            <a href="inicio.php" class="nav-logo" style="text-decoration: none; color: inherit;">HELPFULL</a>
            <ul class="nav-links">
                <li><a href="Diario.php" class="ativo">Diário</a></li>
                <li><a href="Comunidade.php">Comunidade</a></li>
                <li><a href="ChatBOT.php">ChatBOT</a></li>
                <li><a href="Atividades.php">Adicionais</a></li>
            </ul>
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
            <div class="nav-dropdown-mobile" id="navDropdownMobile">
                <a href="inicio.php">Início</a>
                <a href="Diario.php" class="ativo">Diário</a>
                <a href="Comunidade.php">Comunidade</a>
                <a href="ChatBOT.php">ChatBOT</a>
                <a href="Atividades.php">Adicionais</a>
                <a href="Perfil.php">Perfil</a>
            </div>
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
        <div class="caixa-agrupadora">
            <p class="texto-intro-diario">
                Escrever um diário pode ajudar você a entender melhor seus pensamentos e sentimentos.
                Reserve alguns minutos para escrever sobre o seu dia, concentrando-se no que correu bem, no
                que o desafiou e como você se sentiu ao longo do dia. Você também pode selecionar as
                emoções que melhor descrevem seu humor hoje.
            </p>

            <div class="textarea-container">
                <textarea class="textarea-diario" id="campoTexto"
                    placeholder="Comece seu diario digitando aqui..."></textarea>
            </div>

            <div class="secao-emocoes-interna">
                <h3 class="titulo-emocoes">Atribua uma emoção ao seu diario:</h3>
                <div class="container-emocoes">
                    <button class="btn-emocao emocao-irritado" onclick="selecionarEmocao(this)">Irritado</button>
                    <button class="btn-emocao emocao-ansioso" onclick="selecionarEmocao(this)">Ansioso</button>
                    <button class="btn-emocao emocao-feliz" onclick="selecionarEmocao(this)">Feliz</button>
                    <button class="btn-emocao emocao-calmo" onclick="selecionarEmocao(this)">Calmo</button>
                    <button class="btn-emocao emocao-triste" onclick="selecionarEmocao(this)">Triste</button>
                    <button class="btn-emocao emocao-amoroso" onclick="selecionarEmocao(this)">Amoroso</button>
                </div>
            </div>
        </div>

        <div class="container-acoes">
            <button class="btn-acao btn-apagar" onclick="apagarTudo()">Apagar tudo</button>
            <button class="btn-acao btn-salvar" onclick="salvarDiario(this)">Salvar</button>
        </div>
    </div>

    <div class="secao-branca">
        <div class="secao-branca-conteudo">
            <footer class="rodape-simples">
                <strong>HELPFULL</strong>
                <div class="contato-info">
                    Entre em contato:<br><br>Email:<br>Intagram:<br>WhatsApp:
                </div>
            </footer>
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
        // === TEMPLATES DE NOTIFICAÇÕES ===
        const NOTIF_TEMPLATES = {
            diario_salvo: {
                mensagem: "Diário salvo com sucesso! Confira no seu perfil. ✨",
                intensidade: "baixa", link: "Perfil.php", textoBotao: "Ver Perfil"
            },
            meta_diario: {
                mensagem: "Meta concluída! Você registrou seu dia no Diário. 📖",
                intensidade: "baixa", link: "Perfil.php", textoBotao: "Ver Perfil"
            },
            meta_emocao: {
                mensagem: "Meta concluída! Você registrou como está se sentindo. 💛",
                intensidade: "baixa", link: "Perfil.php", textoBotao: "Ver Perfil"
            }
        };

        function selecionarEmocao(botaoClicado) {
            const botoes = document.querySelectorAll('.btn-emocao');
            botoes.forEach(botao => botao.classList.remove('selecionada'));
            botaoClicado.classList.add('selecionada');
        }

        function apagarTudo() {
            document.getElementById('campoTexto').value = '';
            const botoes = document.querySelectorAll('.btn-emocao');
            botoes.forEach(botao => botao.classList.remove('selecionada'));
        }

        async function salvarDiario(botao) {
            const texto = document.getElementById('campoTexto').value.trim();
            const btnSelecionado = document.querySelector('.btn-emocao.selecionada');
            const emocao = btnSelecionado ? btnSelecionado.innerText.trim() : '';

            if (texto === "" && emocao === "") {
                alert("Por favor, escreva como foi o seu dia ou selecione uma emoção!");
                return;
            }

            const originalText = botao.innerHTML;
            botao.innerHTML = 'Salvando <div class="loading-dots"><div></div><div></div><div></div></div>';
            botao.disabled = true;

            const formData = new FormData();
            formData.append('acao', 'salvar_diario');
            formData.append('conteudo', texto);
            formData.append('emocao', emocao);

            try {
                const response = await fetch('Diario.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.sucesso) {
                    apagarTudo();
                    mostrarNotificacaoAtiva(NOTIF_TEMPLATES.diario_salvo);

                    if (result.meta_diario_concluida) {
                        setTimeout(() => mostrarNotificacaoAtiva(NOTIF_TEMPLATES.meta_diario), 1000);
                    }
                    if (result.meta_emocao_concluida) {
                        setTimeout(() => mostrarNotificacaoAtiva(NOTIF_TEMPLATES.meta_emocao), 2000);
                    }
                } else {
                    alert("Erro ao salvar: " + result.mensagem);
                }
            } catch (error) {
                alert("Erro de conexão ao salvar.");
            } finally {
                botao.innerHTML = originalText;
                botao.disabled = false;
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
                }
            });
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.navbar-topo') && !e.target.closest('.nav-dropdown-mobile')) {
                    navDropdown.classList.remove('aberto');
                }
            });
        }
    </script>
    <script src="notificacoes.js?v=20260916-v2"></script>
</body>

</html>