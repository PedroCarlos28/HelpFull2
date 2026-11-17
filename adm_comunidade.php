<?php
/**
 * adm_comunidade.php
 * Painel administrativo de moderação da Comunidade HelpFull
 * Acesso exclusivo para administradores
 */

require_once 'conexao.php';

$usuarioLogado = obterUsuarioLogado($pdo);
$isAdmin       = !empty($usuarioLogado['is_admin']);
$adminEmail    = 'contatohelpfull@gmail.com';

if (!$isAdmin && ($usuarioLogado['email'] ?? '') !== $adminEmail) {
    header('Location: Comunidade.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HelpFull — Painel de Moderação</title>
    <link rel="icon" type="image/png" href="assets/logoHelpFull.png">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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

        /* FUNDO ANIMADO */
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

        /* NAVBAR UNIVERSAL */
        .nav-container-global {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            display: flex;
            justify-content: center;
            padding-top: 15px;
            z-index: 1000;
            pointer-events: none;
        }

        .navbar-topo {
            position: relative;
            width: calc(100% - 40px);
            max-width: 1000px;
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-radius: 40px;
            padding: 12px 28px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.85);
            pointer-events: auto;
            transition: all 0.3s ease;
        }

        .nav-top-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }

        .nav-logo {
            font-size: 1.15rem;
            font-weight: 800;
            color: #0f172a;
            text-decoration: none;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .nav-seta-dropdown {
            display: none;
            color: #2b7a8c;
            transition: transform 0.3s ease;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 24px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-links a {
            text-decoration: none;
            color: #64748b;
            font-size: 0.92rem;
            font-weight: 600;
            transition: 0.2s;
            position: relative;
        }

        .nav-links a:hover,
        .nav-links a.ativo {
            color: #2b7a8c;
            font-weight: 700;
        }

        .nav-links a.ativo::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 100%;
            height: 2.5px;
            background: #2b7a8c;
            border-radius: 2px;
        }

        .perfil-capsula {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(43, 122, 140, 0.2);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            cursor: pointer;
            transition: 0.2s;
            color: #2b7a8c;
            text-decoration: none;
            width: 40px;
            height: 40px;
            justify-content: center;
            position: relative;
        }

        .perfil-capsula:hover {
            transform: translateY(-2px);
            border-color: rgba(43, 122, 140, 0.4);
            box-shadow: 0 6px 16px rgba(43, 122, 140, 0.15);
        }

        .nav-dropdown-mobile {
            display: none;
            flex-direction: column;
            gap: 10px;
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
        }

        .nav-dropdown-mobile.aberto {
            display: flex;
        }

        .nav-dropdown-mobile a {
            text-decoration: none;
            color: #475569;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 8px 12px;
            border-radius: 12px;
            transition: 0.2s;
        }

        .nav-dropdown-mobile a:hover,
        .nav-dropdown-mobile a.ativo {
            background: rgba(43, 122, 140, 0.08);
            color: #2b7a8c;
            font-weight: 700;
        }

        /* CONTEÚDO PRINCIPAL */
        .conteudo-site {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            padding: 100px 20px 60px 20px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            box-sizing: border-box;
        }

        .caixa-agrupadora {
            position: relative;
            z-index: 1;
            border-radius: 40px;
            padding: 36px 30px;
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 24px;
            box-sizing: border-box;
        }

        .caixa-agrupadora::before {
            content: '';
            position: absolute;
            inset: 0;
            z-index: -3;
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-radius: 50px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08), inset 0 2px 10px rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.9);
            pointer-events: none;
        }

        /* HEADER DO PAINEL */
        .adm-header-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            background: #ffffff;
            border-radius: 32px;
            padding: 24px 28px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.04);
        }

        .adm-header-left {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
        }

        .adm-header-icone {
            width: 54px;
            height: 54px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(43, 122, 140, 0.16), rgba(43, 122, 140, 0.06));
            color: #2b7a8c;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1.5px solid rgba(43, 122, 140, 0.2);
            flex-shrink: 0;
        }

        .adm-header-titulos {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 0;
        }

        .adm-header-titulo-linha {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .adm-header-titulos h1 {
            font-size: 1.25rem;
            font-weight: 800;
            color: #0f172a;
        }

        .badge-adm-pill {
            background: linear-gradient(135deg, #2b7a8c, #1f5d6b);
            color: #ffffff;
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.8px;
            padding: 3px 10px;
            border-radius: 999px;
            text-transform: uppercase;
        }

        .adm-header-titulos p {
            font-size: 0.83rem;
            color: #64748b;
            font-weight: 500;
        }

        .btn-voltar-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 999px;
            background: rgba(43, 122, 140, 0.08);
            color: #2b7a8c;
            text-decoration: none;
            font-size: 0.84rem;
            font-weight: 700;
            transition: all 0.25s ease;
            border: 1.5px solid rgba(43, 122, 140, 0.18);
            white-space: nowrap;
        }

        .btn-voltar-pill:hover {
            background: #2b7a8c;
            color: #ffffff;
            transform: translateX(-3px);
            box-shadow: 0 4px 14px rgba(43, 122, 140, 0.25);
        }

        /* STATS GRID */
        .adm-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .adm-stat-card {
            background: #ffffff;
            border-radius: 28px;
            padding: 20px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(0, 0, 0, 0.04);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.25s ease;
        }

        .adm-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }

        .adm-stat-icone {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .adm-stat-card.total .adm-stat-icone {
            background: rgba(43, 122, 140, 0.12);
            color: #2b7a8c;
        }

        .adm-stat-card.n1 .adm-stat-icone {
            background: rgba(245, 158, 11, 0.15);
            color: #d97706;
        }

        .adm-stat-card.n2 .adm-stat-icone {
            background: rgba(234, 88, 12, 0.15);
            color: #ea580c;
        }

        .adm-stat-card.n3 .adm-stat-icone {
            background: rgba(220, 38, 38, 0.15);
            color: #dc2626;
        }

        .adm-stat-info .num {
            font-size: 1.8rem;
            font-weight: 800;
            line-height: 1.1;
            color: #0f172a;
        }

        .adm-stat-info .label {
            font-size: 0.74rem;
            font-weight: 700;
            color: #64748b;
            margin-top: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* SEÇÃO DE CONTEÚDO */
        .card-adm-secao {
            background: #ffffff;
            border-radius: 36px;
            padding: 28px 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.04);
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .secao-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .secao-header h2 {
            font-size: 1.05rem;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .secao-header-icone {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            background: rgba(43, 122, 140, 0.1);
            color: #2b7a8c;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* BOX DE REGRAS */
        .aviso-regras-banner {
            background: rgba(43, 122, 140, 0.05);
            border: 1px solid rgba(43, 122, 140, 0.15);
            border-radius: 20px;
            padding: 16px 20px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .aviso-regras-icone {
            color: #2b7a8c;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .legenda-niveis-grid {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 0.8rem;
            color: #334155;
            line-height: 1.45;
        }

        .legenda-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legenda-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .legenda-dot.n1 { background: #d97706; }
        .legenda-dot.n2 { background: #ea580c; }
        .legenda-dot.n3 { background: #dc2626; }

        /* CONTROLES / FILTRO E BUSCA */
        .adm-controles-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
        }

        .busca-input-wrapper {
            position: relative;
            flex: 1;
            min-width: 240px;
        }

        .busca-input-wrapper svg {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
        }

        .busca-input-wrapper input {
            width: 100%;
            padding: 11px 16px 11px 44px;
            border-radius: 999px;
            border: 1.5px solid rgba(0, 0, 0, 0.08);
            background: rgba(248, 250, 252, 0.9);
            font-family: inherit;
            font-size: 0.86rem;
            font-weight: 600;
            color: #1a1a1a;
            outline: none;
            transition: all 0.2s ease;
        }

        .busca-input-wrapper input:focus {
            border-color: #2b7a8c;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(43, 122, 140, 0.15);
        }

        .filtros-pills {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-filtro {
            padding: 8px 16px;
            border-radius: 999px;
            border: 1.5px solid rgba(0, 0, 0, 0.08);
            background: rgba(255, 255, 255, 0.9);
            font-family: inherit;
            font-size: 0.8rem;
            font-weight: 700;
            color: #64748b;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-filtro.ativo,
        .btn-filtro:hover {
            background: rgba(43, 122, 140, 0.1);
            border-color: rgba(43, 122, 140, 0.3);
            color: #2b7a8c;
        }

        /* TABELA DE MODERAÇÃO */
        .tabela-wrapper {
            width: 100%;
            overflow-x: auto;
            border-radius: 20px;
        }

        .tabela-moderacao {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
        }

        .tabela-moderacao th {
            text-align: left;
            padding: 8px 14px;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #64748b;
            font-weight: 700;
            border: none;
        }

        .tabela-moderacao tbody tr {
            background: rgba(248, 250, 252, 0.7);
            border-radius: 16px;
            transition: all 0.2s ease;
        }

        .tabela-moderacao tbody tr:hover {
            background: rgba(43, 122, 140, 0.05);
            transform: translateY(-1px);
        }

        .tabela-moderacao td {
            padding: 14px 14px;
            font-size: 0.84rem;
            border: none;
            vertical-align: middle;
        }

        .tabela-moderacao td:first-child {
            border-top-left-radius: 16px;
            border-bottom-left-radius: 16px;
        }

        .tabela-moderacao td:last-child {
            border-top-right-radius: 16px;
            border-bottom-right-radius: 16px;
        }

        .celula-usuario {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .usuario-avatar-circ {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2b7a8c, #1f5d6b);
            color: #ffffff;
            font-size: 0.88rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(43, 122, 140, 0.2);
        }

        .usuario-textos .nome {
            font-weight: 700;
            color: #0f172a;
        }

        .usuario-textos .email {
            font-size: 0.75rem;
            color: #64748b;
            font-weight: 500;
        }

        .badge-nivel {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .badge-nivel.n1 {
            background: rgba(245, 158, 11, 0.15);
            color: #92400e;
        }

        .badge-nivel.n2 {
            background: rgba(234, 88, 12, 0.15);
            color: #9a3412;
        }

        .badge-nivel.n3 {
            background: rgba(220, 38, 38, 0.15);
            color: #991b1b;
        }

        .motivo-tooltip {
            max-width: 240px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #475569;
            font-size: 0.82rem;
            font-weight: 500;
            cursor: pointer;
        }

        .btn-liberar {
            background: rgba(16, 185, 129, 0.12);
            color: #047857;
            border: 1.5px solid rgba(16, 185, 129, 0.3);
            padding: 8px 18px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s ease;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-liberar:hover {
            background: #10b981;
            color: #ffffff;
            border-color: #10b981;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
        }

        .btn-liberar:disabled {
            opacity: 0.5;
            cursor: default;
            transform: none;
        }

        /* HISTÓRICO */
        .historico-lista {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .hist-item {
            display: flex;
            gap: 16px;
            padding: 14px 18px;
            background: rgba(248, 250, 252, 0.85);
            border-radius: 18px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            align-items: flex-start;
            transition: all 0.2s ease;
        }

        .hist-item:hover {
            background: #ffffff;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
            transform: translateX(2px);
        }

        .hist-acao-badge {
            flex-shrink: 0;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .hist-acao-badge.aviso {
            background: rgba(245, 158, 11, 0.15);
            color: #92400e;
        }

        .hist-acao-badge.banimento {
            background: rgba(220, 38, 38, 0.15);
            color: #991b1b;
        }

        .hist-acao-badge.liberacao {
            background: rgba(16, 185, 129, 0.15);
            color: #047857;
        }

        .hist-info {
            flex: 1;
            min-width: 0;
        }

        .hist-usuario {
            font-weight: 700;
            font-size: 0.88rem;
            color: #0f172a;
        }

        .hist-motivo {
            font-size: 0.8rem;
            color: #475569;
            margin-top: 4px;
            line-height: 1.45;
        }

        .hist-data {
            font-size: 0.74rem;
            color: #94a3b8;
            margin-top: 6px;
            font-weight: 500;
        }

        /* ESTADO VAZIO & SPINNER */
        .vazio-estado {
            text-align: center;
            padding: 40px 20px;
            color: #64748b;
            font-size: 0.9rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }

        .vazio-icone-box {
            width: 56px;
            height: 56px;
            border-radius: 20px;
            background: rgba(43, 122, 140, 0.08);
            color: #2b7a8c;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2.5px solid rgba(43, 122, 140, 0.2);
            border-top-color: #2b7a8c;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* RESPONSIVIDADE */
        @media (max-width: 768px) {
            .nav-seta-dropdown {
                display: block;
            }
            .nav-links {
                display: none;
            }
            .conteudo-site {
                padding: 90px 14px 50px 14px;
            }
            .caixa-agrupadora {
                padding: 24px 16px;
                border-radius: 30px;
            }
            .caixa-agrupadora::before {
                border-radius: 35px;
            }
            .card-adm-secao {
                padding: 20px 16px;
                border-radius: 26px;
            }
            .adm-header-banner {
                padding: 18px 16px;
                border-radius: 24px;
            }
            .btn-voltar-pill {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <canvas id="fundoAnimadoCanvas" class="fundo-animado-canvas sem-mouse" data-mouse="false"></canvas>

    <!-- NAVBAR UNIVERSAL -->
    <div class="nav-container-global">
        <nav class="navbar-topo" id="mainNavbar">
            <div class="nav-top-row">
                <a href="inicio.php" class="nav-logo">
                    HELPFULL
                    <span class="nav-seta-dropdown">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </span>
                </a>
                <ul class="nav-links">
                    <li><a href="Diario.php">Diário</a></li>
                    <li><a href="Comunidade.php">Comunidade</a></li>
                    <li><a href="ChatBOT.php">Helpy</a></li>
                    <li><a href="Atividades.php">Adicionais</a></li>
                    <li><a href="adm_comunidade.php" class="ativo">Moderação</a></li>
                </ul>
                <?php if ($usuarioLogado): ?>
                    <?php 
                        $fotoPerfilDb = !empty($usuarioLogado['foto_perfil']) ? $usuarioLogado['foto_perfil'] : ''; 
                        $temFotoNav = !empty($fotoPerfilDb) && (strlen($fotoPerfilDb) <= 150000);
                    ?>
                    <a href="Perfil.php" style="text-decoration: none;">
                        <div class="perfil-capsula" <?= $temFotoNav ? "style='background-image: url($fotoPerfilDb); background-size: cover; background-position: center;'" : '' ?>>
                            <?php if (!$temFotoNav): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php else: ?>
                    <a href="Comeco.php" class="nav-entrar-btn">Entrar</a>
                <?php endif; ?>
            </div>
            <div class="nav-dropdown-mobile" id="navDropdownMobile">
                <a href="inicio.php">Início</a>
                <a href="Diario.php">Diário</a>
                <a href="Comunidade.php">Comunidade</a>
                <a href="ChatBOT.php">Helpy</a>
                <a href="Atividades.php">Adicionais</a>
                <a href="adm_comunidade.php" class="ativo">Painel de Moderação</a>
                <a href="Perfil.php">Perfil</a>
            </div>
        </nav>
    </div>

    <!-- CONTEÚDO PRINCIPAL -->
    <div class="conteudo-site">
        <div class="caixa-agrupadora">

            <!-- HEADER BANNER -->
            <div class="adm-header-banner">
                <div class="adm-header-left">
                    <div class="adm-header-icone">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                    </div>
                    <div class="adm-header-titulos">
                        <div class="adm-header-titulo-linha">
                            <h1>Painel de Moderação</h1>
                            <span class="badge-adm-pill">Admin</span>
                        </div>
                        <p>Gerencie o sistema de advertências, membros suspensos e histórico disciplinar</p>
                    </div>
                </div>
                <a href="Comunidade.php" class="btn-voltar-pill">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Voltar para Comunidade
                </a>
            </div>

            <!-- STATS CARDS -->
            <div class="adm-stats-grid" id="statsRow">
                <div class="adm-stat-card total">
                    <div class="adm-stat-icone">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <div class="adm-stat-info">
                        <div class="num" id="statTotal">—</div>
                        <div class="label">Total com infrações</div>
                    </div>
                </div>

                <div class="adm-stat-card n1">
                    <div class="adm-stat-icone">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <div class="adm-stat-info">
                        <div class="num" id="statN1">—</div>
                        <div class="label">1º Aviso (1/3)</div>
                    </div>
                </div>

                <div class="adm-stat-card n2">
                    <div class="adm-stat-icone">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </div>
                    <div class="adm-stat-info">
                        <div class="num" id="statN2">—</div>
                        <div class="label">2º Aviso (Alerta 2/3)</div>
                    </div>
                </div>

                <div class="adm-stat-card n3">
                    <div class="adm-stat-icone">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                        </svg>
                    </div>
                    <div class="adm-stat-info">
                        <div class="num" id="statN3">—</div>
                        <div class="label">Banidos da Comunidade</div>
                    </div>
                </div>
            </div>

            <!-- SEÇÃO DE USUÁRIOS COM INFRAÇÃO -->
            <div class="card-adm-secao">
                <div class="secao-header">
                    <h2>
                        <span class="secao-header-icone">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                        </span>
                        Membros com Infrações / Suspensão
                    </h2>
                </div>

                <!-- BOX INFORMATIVO DAS REGRAS -->
                <div class="aviso-regras-banner">
                    <div class="aviso-regras-icone">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                    </div>
                    <div class="legenda-niveis-grid">
                        <div class="legenda-item">
                            <div class="legenda-dot n1"></div>
                            <span><strong>Nível 1 (1º Aviso):</strong> 1º post apagado por infração. Expira automaticamente em 6 meses se não houver novos avisos.</span>
                        </div>
                        <div class="legenda-item">
                            <div class="legenda-dot n2"></div>
                            <span><strong>Nível 2 (2º Aviso):</strong> Alerta de reincidência. O próximo post apagado causará banimento definitivo da comunidade.</span>
                        </div>
                        <div class="legenda-item">
                            <div class="legenda-dot n3"></div>
                            <span><strong>Nível 3 (Banido):</strong> 3ª infração atingida. Conta bloqueada para publicações e reações (apenas visualização permitida).</span>
                        </div>
                    </div>
                </div>

                <!-- BARRA DE CONTROLES: BUSCA & FILTROS -->
                <div class="adm-controles-row">
                    <div class="busca-input-wrapper">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input type="text" id="inputBuscaUsuario" placeholder="Buscar por nome ou e-mail..." oninput="filtrarPorBusca()">
                    </div>

                    <div class="filtros-pills">
                        <button class="btn-filtro ativo" onclick="filtrarNivel(0, this)">Todos</button>
                        <button class="btn-filtro" onclick="filtrarNivel(1, this)">1º Aviso</button>
                        <button class="btn-filtro" onclick="filtrarNivel(2, this)">2º Aviso</button>
                        <button class="btn-filtro" onclick="filtrarNivel(3, this)">Banidos</button>
                    </div>
                </div>

                <!-- TABELA -->
                <div id="tabelaWrapper" class="tabela-wrapper">
                    <div class="vazio-estado">
                        <span class="spinner"></span>
                        <span>Carregando dados da moderação...</span>
                    </div>
                </div>
            </div>

            <!-- SEÇÃO DE HISTÓRICO -->
            <div class="card-adm-secao">
                <div class="secao-header">
                    <h2>
                        <span class="secao-header-icone">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </span>
                        Histórico Recente de Moderações
                    </h2>
                </div>
                <div id="historicoWrapper">
                    <div class="vazio-estado">
                        <span class="spinner"></span>
                        <span>Carregando histórico...</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="assets/fundo-animado.js?v=20260925-v4"></script>
    <script src="assets/transicao.js?v=20260926-v1"></script>
    <script>
    let todosUsuarios = [];
    let filtroAtual = 0;
    let termoBusca = '';

    // Menu Mobile Navbar
    const navLogo = document.querySelector('.nav-logo');
    const navDropdownMobile = document.getElementById('navDropdownMobile');
    const navSeta = document.querySelector('.nav-seta-dropdown');

    if (navLogo && navDropdownMobile) {
        navLogo.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                navDropdownMobile.classList.toggle('aberto');
                if (navSeta) {
                    navSeta.style.transform = navDropdownMobile.classList.contains('aberto') ? 'rotate(180deg)' : 'rotate(0deg)';
                }
            }
        });
    }

    async function carregarDados() {
        try {
            const r = await fetch('comunidade_moderacao_proc.php?acao=listar_banidos');
            const d = await r.json();
            if (!d.success) throw new Error(d.error);
            todosUsuarios = d.usuarios || [];
            atualizarStats();
            aplicarFiltros();
        } catch (e) {
            document.getElementById('tabelaWrapper').innerHTML =
                `<div class="vazio-estado">Erro ao carregar dados: ${escHtml(e.message)}</div>`;
        }

        carregarHistorico();
    }

    function atualizarStats() {
        document.getElementById('statTotal').textContent = todosUsuarios.length;
        document.getElementById('statN1').textContent = todosUsuarios.filter(u => u.nivel == 1).length;
        document.getElementById('statN2').textContent = todosUsuarios.filter(u => u.nivel == 2).length;
        document.getElementById('statN3').textContent = todosUsuarios.filter(u => u.nivel == 3).length;
    }

    function filtrarNivel(nivel, btn) {
        filtroAtual = nivel;
        document.querySelectorAll('.btn-filtro').forEach(b => b.classList.remove('ativo'));
        if (btn) btn.classList.add('ativo');
        aplicarFiltros();
    }

    function filtrarPorBusca() {
        termoBusca = (document.getElementById('inputBuscaUsuario').value || '').trim().toLowerCase();
        aplicarFiltros();
    }

    function aplicarFiltros() {
        let filtrados = todosUsuarios;
        if (filtroAtual > 0) {
            filtrados = filtrados.filter(u => u.nivel == filtroAtual);
        }
        if (termoBusca) {
            filtrados = filtrados.filter(u => 
                (u.nome || '').toLowerCase().includes(termoBusca) ||
                (u.email || '').toLowerCase().includes(termoBusca) ||
                (u.motivo || '').toLowerCase().includes(termoBusca)
            );
        }
        renderTabela(filtrados);
    }

    function renderTabela(usuarios) {
        const wrapper = document.getElementById('tabelaWrapper');
        if (!usuarios.length) {
            wrapper.innerHTML = `<div class="vazio-estado">
                <div class="vazio-icone-box">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/>
                        <path d="m9 12 2 2 4-4"/>
                    </svg>
                </div>
                <div><strong>Nenhum usuário encontrado nesta listagem!</strong></div>
                <span style="font-size:0.8rem;color:#94a3b8">A comunidade está saudável e sem infrações ativas.</span>
            </div>`;
            return;
        }

        const labelNivel = { 1: '1º Aviso (1/3)', 2: '2º Aviso (2/3)', 3: 'Banido (3/3)' };
        const classNivel = { 1: 'n1', 2: 'n2', 3: 'n3' };

        const rows = usuarios.map(u => {
            const criado = new Date(u.criado_em).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
            const expira = u.expira_avisos_em
                ? `Expira em: ${new Date(u.expira_avisos_em).toLocaleDateString('pt-BR')}`
                : (u.nivel < 3 ? 'Aviso ativo' : 'Banimento permanente');

            const inicial = (u.nome || 'U').charAt(0).toUpperCase();

            return `<tr>
                <td>
                    <div class="celula-usuario">
                        <div class="usuario-avatar-circ">${escHtml(inicial)}</div>
                        <div class="usuario-textos">
                            <div class="nome">${escHtml(u.nome)}</div>
                            <div class="email">${escHtml(u.email)}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge-nivel ${classNivel[u.nivel]}">
                        <span class="legenda-dot ${classNivel[u.nivel]}"></span>
                        ${labelNivel[u.nivel]}
                    </span>
                </td>
                <td>
                    <div class="motivo-tooltip" title="${escHtml(u.motivo)}" onclick="alert('Motivo completo:\\n\\n' + this.title)">
                        ${escHtml(u.motivo)}
                    </div>
                </td>
                <td style="font-size:0.78rem;color:#475569">
                    <div><strong>${criado}</strong></div>
                    <div style="font-size:0.73rem;color:#94a3b8">${expira}</div>
                </td>
                <td style="font-size:0.85rem;color:#0f172a;text-align:center;font-weight:700">
                    ${u.total_punicoes ?? 0}
                </td>
                <td>
                    <button class="btn-liberar" onclick="liberarUsuario('${u.usuario_id}', this)">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        Liberar
                    </button>
                </td>
            </tr>`;
        }).join('');

        wrapper.innerHTML = `<table class="tabela-moderacao">
            <thead>
                <tr>
                    <th>Membro</th>
                    <th>Nível</th>
                    <th>Motivo do Registro</th>
                    <th>Data / Expiração</th>
                    <th style="text-align:center">Histórico</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>`;
    }

    async function liberarUsuario(usuarioId, btn) {
        if (!confirm('Deseja realmente liberar este usuário e remover todas as restrições da conta dele na comunidade?')) return;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner" style="width:14px;height:14px;border-width:2px;"></span>';
        try {
            const fd = new FormData();
            fd.append('acao', 'liberar_usuario');
            fd.append('usuario_id', usuarioId);
            const r = await fetch('comunidade_moderacao_proc.php', { method: 'POST', body: fd });
            const d = await r.json();
            if (d.success) {
                todosUsuarios = todosUsuarios.filter(u => u.usuario_id !== usuarioId);
                atualizarStats();
                aplicarFiltros();
                carregarHistorico();
            } else {
                alert('Erro ao liberar: ' + (d.error || 'Erro desconhecido'));
                btn.disabled = false;
                btn.innerHTML = '<polyline points="20 6 9 17 4 12"></polyline> Liberar';
            }
        } catch (e) {
            alert('Erro de rede. Tente novamente.');
            btn.disabled = false;
            btn.innerHTML = '<polyline points="20 6 9 17 4 12"></polyline> Liberar';
        }
    }

    async function carregarHistorico() {
        const wrapper = document.getElementById('historicoWrapper');
        try {
            const r = await fetch('comunidade_moderacao_proc.php?acao=listar_historico');
            const d = await r.json();
            if (!d.success || !d.historico.length) {
                wrapper.innerHTML = `<div class="vazio-estado">
                    <span style="opacity:0.7">Nenhuma ação de moderação registrada recentemente.</span>
                </div>`;
                return;
            }
            const acaoLabel = {
                aviso_1: '1º Aviso',
                aviso_2: '2º Aviso',
                banimento: 'Banimento',
                liberacao: 'Liberação',
                expiracao_automatica: 'Expirou'
            };
            const acaoClass = {
                aviso_1: 'aviso',
                aviso_2: 'aviso',
                banimento: 'banimento',
                liberacao: 'liberacao',
                expiracao_automatica: 'liberacao'
            };
            const html = d.historico.map(h => {
                const data = new Date(h.criado_em).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
                const cls = acaoClass[h.acao] || 'aviso';
                const lbl = acaoLabel[h.acao] || h.acao;
                return `<div class="hist-item">
                    <span class="hist-acao-badge ${cls}">${escHtml(lbl)}</span>
                    <div class="hist-info">
                        <div class="hist-usuario">${escHtml(h.usuario_nome)} <span style="color:#94a3b8;font-weight:400;font-size:0.8rem">(${escHtml(h.usuario_email)})</span></div>
                        <div class="hist-motivo">${escHtml(h.motivo || 'Sem observação adicional')}</div>
                        <div class="hist-data">Registrado em ${data} · Aplicado por ${escHtml(h.admin_nome || 'Sistema HelpFull')}</div>
                    </div>
                </div>`;
            }).join('');
            wrapper.innerHTML = `<div class="historico-lista">${html}</div>`;
        } catch (e) {
            wrapper.innerHTML = `<div class="vazio-estado">Erro ao carregar histórico.</div>`;
        }
    }

    function escHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    carregarDados();
    </script>
</body>
</html>
