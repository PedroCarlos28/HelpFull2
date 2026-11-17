<?php
// =======================================================
// HELPFULL - PAINEL DE MODERAÇÃO DA COMUNIDADE
// Design exclusivo com a mesma identidade visual de Dispositivos Conectados
// =======================================================

require_once __DIR__ . '/conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: Comeco.php');
    exit;
}

$usuarioLogado = obterUsuarioLogado($pdo);
$isAdmin       = !empty($usuarioLogado['is_admin']);
$adminEmail    = 'contatohelpfull@gmail.com';

if (!$isAdmin && ($usuarioLogado['email'] ?? '') !== $adminEmail) {
    header('Location: Comunidade.php');
    exit;
}

$fotoPerfilDb = !empty($usuarioLogado['foto_perfil']) ? $usuarioLogado['foto_perfil'] : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HelpFull - Moderação da Comunidade</title>
    <link rel="icon" type="image/png" href="assets/logoHelpFull.png">

    <style>
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
            background-color: #F3F3F3;
            font-family: 'Montserrat', sans-serif;
            color: #1a1a1a;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* NAVBAR GLOBAL (UNIVERSAL HELPFULL) */
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

        .nav-seta-dropdown {
            display: none;
        }

        .nav-links {
            display: flex;
            gap: 25px;
            list-style: none;
            align-items: center;
            margin: 0;
            padding: 0;
        }

        .nav-links a {
            text-decoration: none;
            color: #1a1a1a;
            font-weight: 700;
            font-size: 0.95rem;
            transition: color 0.3s;
        }

        .nav-links a:hover,
        .nav-links a.ativo {
            opacity: 0.7;
            color: #2b7a8c;
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
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .perfil-capsula:hover {
            transform: scale(1.05);
            box-shadow: 0 0 0 3px #2b7a8c, 0 4px 12px rgba(43, 122, 140, 0.2);
        }

        .acessibilidade-anchor {
            position: absolute;
            right: -60px;
            pointer-events: auto;
        }

        .btn-acessibilidade {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #1a1a1a;
            transition: all 0.3s;
        }

        .btn-acessibilidade:hover {
            transform: scale(1.05);
            background: rgba(255, 255, 255, 0.6);
            color: #2b7a8c;
        }

        .nav-dropdown-mobile {
            display: none;
        }

        /* CONTAINER PRINCIPAL */
        .conteudo-site {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            padding: 120px 20px 80px 20px;
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        /* BARRA SUPERIOR DE NAVEGAÇÃO INTERNA */
        .topo-voltar-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 10px;
            margin-bottom: 4px;
        }

        .link-voltar {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #2b7a8c;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 700;
            padding: 8px 18px;
            background: #eaeaea;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            transition: all 0.2s ease;
        }

        .link-voltar:hover {
            background: #e0e0e0;
            transform: translateX(-3px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
        }

        .link-voltar svg {
            stroke: currentColor;
            width: 16px;
            height: 16px;
        }

        /* CARD HEADER PRINCIPAL */
        .card-dispositivos-header {
            background: #eaeaea;
            border-radius: 30px;
            padding: 35px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.6);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
            flex-wrap: wrap;
        }

        .header-textos {
            flex: 1;
            min-width: 280px;
        }

        .badge-seguranca {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 14px;
            border-radius: 20px;
            background: #d6d6d6;
            color: #2b7a8c;
            border: 1px solid rgba(255, 255, 255, 0.6);
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .badge-seguranca svg {
            stroke: currentColor;
            width: 14px;
            height: 14px;
        }

        .titulo-pagina {
            font-size: 1.7rem;
            font-weight: 900;
            color: #1a1a1a;
            margin: 0 0 8px 0;
            letter-spacing: -0.5px;
        }

        .desc-pagina {
            font-size: 0.92rem;
            color: #555;
            line-height: 1.55;
            margin: 0;
            max-width: 600px;
        }

        .header-acoes {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 12px;
            flex-shrink: 0;
        }

        .pill-contador-ativo {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 16px;
            border-radius: 20px;
            background: #d6d6d6;
            color: #2b7a8c;
            font-size: 0.82rem;
            font-weight: 700;
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        .ponto-pulse {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 0 rgba(16, 185, 129, 0.4);
            animation: pulseDot 2s infinite;
        }

        @keyframes pulseDot {
            0% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            }
            70% {
                box-shadow: 0 0 0 8px rgba(16, 185, 129, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }
        }

        /* GRID DE ESTATÍSTICAS */
        .stats-grid-dispositivos {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 16px;
        }

        .card-stat-box {
            background: #eaeaea;
            border-radius: 24px;
            padding: 22px 24px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.6);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card-stat-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
        }

        .stat-icone-box {
            width: 48px;
            height: 48px;
            border-radius: 18px;
            background: #d6d6d6;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #2b7a8c;
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        .stat-icone-box svg {
            width: 22px;
            height: 22px;
            stroke: currentColor;
        }

        .card-stat-box.n1 .stat-icone-box {
            color: #d97706;
        }

        .card-stat-box.n2 .stat-icone-box {
            color: #ea580c;
        }

        .card-stat-box.n3 .stat-icone-box {
            color: #dc2626;
        }

        .stat-textos .num {
            font-size: 1.75rem;
            font-weight: 900;
            color: #1a1a1a;
            line-height: 1.1;
        }

        .stat-textos .label {
            font-size: 0.74rem;
            font-weight: 800;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 3px;
        }

        /* CARD DE DICAS / REGRAS DE MODERAÇÃO */
        .card-dicas-seguranca {
            background: #eaeaea;
            border-radius: 30px;
            padding: 26px 35px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.03);
            display: flex;
            align-items: flex-start;
            gap: 18px;
        }

        .dicas-icone {
            width: 44px;
            height: 44px;
            border-radius: 16px;
            background: #d6d6d6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2b7a8c;
            border: 1px solid rgba(255, 255, 255, 0.6);
            flex-shrink: 0;
        }

        .dicas-icone svg {
            width: 22px;
            height: 22px;
            stroke: currentColor;
        }

        .dicas-conteudo {
            flex: 1;
        }

        .dicas-titulo {
            font-size: 0.98rem;
            font-weight: 800;
            color: #2b7a8c;
            margin: 0 0 8px 0;
        }

        .dicas-texto-lista {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 0.85rem;
            color: #555;
            line-height: 1.5;
        }

        .dicas-regra-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dicas-regra-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .dicas-regra-dot.n1 { background: #d97706; }
        .dicas-regra-dot.n2 { background: #ea580c; }
        .dicas-regra-dot.n3 { background: #dc2626; }

        /* SEÇÃO: MEMBROS COM INFRAÇÃO */
        .secao-titulo-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 10px;
            margin-bottom: 2px;
            padding: 0 6px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .secao-subtitulo {
            font-size: 1.05rem;
            font-weight: 800;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .secao-subtitulo svg {
            stroke: currentColor;
            width: 18px;
            height: 18px;
            color: #2b7a8c;
        }

        /* BARRA DE BUSCA E FILTROS */
        .card-barra-filtros {
            background: #eaeaea;
            border-radius: 24px;
            padding: 16px 24px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.03);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .busca-input-box {
            position: relative;
            flex: 1;
            min-width: 250px;
        }

        .busca-input-box svg {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            stroke: #777;
            width: 16px;
            height: 16px;
            pointer-events: none;
        }

        .input-busca {
            width: 100%;
            height: 44px;
            border-radius: 20px;
            border: 1.5px solid rgba(255, 255, 255, 0.6);
            background: #d6d6d6;
            padding: 0 18px 0 44px;
            font-size: 0.88rem;
            font-weight: 600;
            font-family: inherit;
            color: #1a1a1a;
            outline: none;
            transition: all 0.2s ease;
        }

        .input-busca:focus {
            background: #ffffff;
            border-color: #2b7a8c;
            box-shadow: 0 0 0 3px rgba(43, 122, 140, 0.2);
        }

        .filtros-pills-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-filtro-pill {
            padding: 8px 16px;
            border-radius: 20px;
            background: #d6d6d6;
            color: #2b7a8c;
            border: 1px solid rgba(255, 255, 255, 0.6);
            font-size: 0.78rem;
            font-weight: 800;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-filtro-pill:hover,
        .btn-filtro-pill.ativo {
            background: #2b7a8c;
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(43, 122, 140, 0.25);
        }

        /* LISTA DE MEMBROS RESTRITOS */
        .lista-membros-moderacao {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .card-membro-item {
            background: #eaeaea;
            border-radius: 30px;
            padding: 24px 35px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.6);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card-membro-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06);
        }

        .membro-esquerda {
            display: flex;
            align-items: center;
            gap: 20px;
            flex: 1;
            min-width: 0;
        }

        .membro-avatar-box {
            width: 56px;
            height: 56px;
            border-radius: 20px;
            background: #d6d6d6;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #2b7a8c;
            font-size: 1.15rem;
            font-weight: 900;
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        .membro-info-textos {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 0;
        }

        .membro-topo-nome {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .membro-nome {
            font-size: 1.1rem;
            font-weight: 800;
            color: #1a1a1a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .badge-nivel-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }

        .badge-nivel-pill.n1 {
            background: rgba(245, 158, 11, 0.18);
            color: #b45309;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .badge-nivel-pill.n2 {
            background: rgba(234, 88, 12, 0.18);
            color: #c2410c;
            border: 1px solid rgba(234, 88, 12, 0.3);
        }

        .badge-nivel-pill.n3 {
            background: rgba(220, 38, 38, 0.18);
            color: #b91c1c;
            border: 1px solid rgba(220, 38, 38, 0.3);
        }

        .membro-detalhes-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            font-size: 0.84rem;
            color: #555;
        }

        .membro-meta-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .membro-meta-item svg {
            width: 14px;
            height: 14px;
            stroke: #777;
            flex-shrink: 0;
        }

        .membro-motivo-box {
            font-size: 0.83rem;
            color: #475569;
            background: #d6d6d6;
            padding: 6px 12px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            max-width: fit-content;
            cursor: pointer;
        }

        .membro-direita-acao {
            flex-shrink: 0;
        }

        .btn-liberar-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 18px;
            background: #d6d6d6;
            color: #047857;
            border: 1px solid rgba(16, 185, 129, 0.35);
            font-size: 0.86rem;
            font-weight: 800;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-liberar-item:hover {
            background: #10b981;
            color: #ffffff;
            border-color: #10b981;
            transform: scale(1.02);
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.25);
        }

        .btn-liberar-item svg {
            stroke: currentColor;
            width: 16px;
            height: 16px;
        }

        /* ESTADO VAZIO (SEM MEMBROS RESTRITOS) */
        .card-estado-vazio {
            background: #eaeaea;
            border-radius: 30px;
            padding: 45px 30px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.03);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 14px;
        }

        .vazio-icone-wrapper {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            background: #d6d6d6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2b7a8c;
            margin-bottom: 4px;
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        .vazio-icone-wrapper svg {
            width: 32px;
            height: 32px;
            stroke: currentColor;
        }

        .vazio-titulo {
            font-size: 1.15rem;
            font-weight: 800;
            color: #1a1a1a;
            margin: 0;
        }

        .vazio-desc {
            font-size: 0.88rem;
            color: #555;
            margin: 0;
            max-width: 480px;
            line-height: 1.5;
        }

        /* HISTÓRICO RECENTE */
        .lista-historico-items {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .card-historico-item {
            background: #eaeaea;
            border-radius: 24px;
            padding: 18px 28px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.6);
            display: flex;
            align-items: flex-start;
            gap: 16px;
            transition: all 0.2s ease;
        }

        .card-historico-item:hover {
            transform: translateX(3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.05);
        }

        .hist-acao-tag {
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .hist-acao-tag.aviso {
            background: #d6d6d6;
            color: #b45309;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .hist-acao-tag.banimento {
            background: #d6d6d6;
            color: #b91c1c;
            border: 1px solid rgba(220, 38, 38, 0.3);
        }

        .hist-acao-tag.liberacao {
            background: #d6d6d6;
            color: #047857;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .hist-corpo {
            flex: 1;
            min-width: 0;
        }

        .hist-autor-linha {
            font-size: 0.9rem;
            font-weight: 800;
            color: #1a1a1a;
        }

        .hist-email-suave {
            font-weight: 500;
            color: #666;
            font-size: 0.8rem;
        }

        .hist-motivo-texto {
            font-size: 0.84rem;
            color: #475569;
            margin-top: 4px;
            line-height: 1.45;
        }

        .hist-data-rodape {
            font-size: 0.76rem;
            color: #777;
            margin-top: 6px;
        }

        .spinner {
            display: inline-block;
            width: 22px;
            height: 22px;
            border: 2.5px solid rgba(43, 122, 140, 0.25);
            border-top-color: #2b7a8c;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* SUPORTE A TEMA ESCURO (Acessibilidade) */
        html.acessibilidade-escuro,
        body.acessibilidade-escuro {
            background-color: var(--tema-fundo, #121619) !important;
            background: var(--tema-fundo, #121619) !important;
            color: var(--tema-texto, #f1f5f9) !important;
        }

        body.acessibilidade-escuro .navbar-topo {
            background: rgba(26, 33, 38, 0.75) !important;
            border-color: var(--tema-borda, #2f383e) !important;
        }

        body.acessibilidade-escuro .nav-logo,
        body.acessibilidade-escuro .nav-links a {
            color: var(--tema-texto, #f1f5f9) !important;
        }

        body.acessibilidade-escuro .link-voltar {
            background: var(--tema-superficie, #1a2227) !important;
            color: #7dd3fc !important;
            border-color: rgba(125, 211, 252, 0.25) !important;
        }

        body.acessibilidade-escuro .card-dispositivos-header,
        body.acessibilidade-escuro .card-stat-box,
        body.acessibilidade-escuro .card-dicas-seguranca,
        body.acessibilidade-escuro .card-barra-filtros,
        body.acessibilidade-escuro .card-membro-item,
        body.acessibilidade-escuro .card-estado-vazio,
        body.acessibilidade-escuro .card-historico-item {
            background: var(--tema-superficie, #1a2227) !important;
            border-color: var(--tema-borda, #2f383e) !important;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.4) !important;
            color: var(--tema-texto, #f1f5f9) !important;
        }

        body.acessibilidade-escuro .titulo-pagina,
        body.acessibilidade-escuro .stat-textos .num,
        body.acessibilidade-escuro .membro-nome,
        body.acessibilidade-escuro .vazio-titulo,
        body.acessibilidade-escuro .secao-subtitulo,
        body.acessibilidade-escuro .hist-autor-linha {
            color: var(--tema-texto, #ffffff) !important;
        }

        body.acessibilidade-escuro .desc-pagina,
        body.acessibilidade-escuro .dicas-texto-lista,
        body.acessibilidade-escuro .vazio-desc,
        body.acessibilidade-escuro .membro-detalhes-meta {
            color: var(--tema-texto-secundario, #94a3b8) !important;
        }

        body.acessibilidade-escuro .badge-seguranca,
        body.acessibilidade-escuro .pill-contador-ativo,
        body.acessibilidade-escuro .stat-icone-box,
        body.acessibilidade-escuro .dicas-icone,
        body.acessibilidade-escuro .membro-avatar-box,
        body.acessibilidade-escuro .vazio-icone-wrapper,
        body.acessibilidade-escuro .btn-filtro-pill {
            background: rgba(43, 122, 140, 0.2) !important;
            color: #7dd3fc !important;
            border-color: rgba(125, 211, 252, 0.2) !important;
        }

        body.acessibilidade-escuro .input-busca {
            background: #252d32 !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            color: #f1f5f9 !important;
        }

        /* RESPONSIVIDADE (Mobile) */
        @media (max-width: 768px) {
            .nav-container-global {
                width: 100%;
                left: 0;
                transform: none;
                top: 15px;
            }

            .nav-links {
                display: none;
            }

            .nav-seta-dropdown {
                display: inline-block;
                margin-left: 6px;
                vertical-align: middle;
            }

            .navbar-topo {
                padding: 12px 25px;
                width: calc(100% - 40px);
                margin: 0 auto;
                justify-content: space-between;
                overflow: visible !important;
            }

            .nav-dropdown-mobile {
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(25px);
                -webkit-backdrop-filter: blur(25px);
                border-radius: 20px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.8);
                display: none;
                flex-direction: column;
                padding: 10px;
                gap: 5px;
                margin-top: 10px;
                z-index: 1000;
            }

            .nav-dropdown-mobile.ativo {
                display: flex;
            }

            .nav-dropdown-mobile a {
                padding: 10px 15px;
                text-decoration: none;
                color: #1a1a1a;
                font-weight: 600;
                font-size: 0.9rem;
                border-radius: 12px;
                transition: background 0.2s;
            }

            .nav-dropdown-mobile a:hover,
            .nav-dropdown-mobile a.ativo {
                background: rgba(43, 122, 140, 0.1);
                color: #2b7a8c;
            }

            .conteudo-site {
                padding-top: 135px !important;
                padding-left: 16px !important;
                padding-right: 16px !important;
            }

            .card-dispositivos-header {
                padding: 24px 20px;
                flex-direction: column;
                align-items: flex-start;
            }

            .header-acoes {
                align-items: flex-start;
            }

            .card-membro-item {
                flex-direction: column;
                align-items: flex-start;
                padding: 20px;
            }

            .membro-direita-acao {
                width: 100%;
            }

            .btn-liberar-item {
                width: 100%;
                justify-content: center;
            }

            .card-dicas-seguranca {
                flex-direction: column;
                padding: 20px 18px;
            }
        }
    </style>
    <link rel="stylesheet" href="assets/acessibilidade.css?v=20261112-notif-v4">
    <link rel="stylesheet" href="assets/transicao.css?v=20260926-v1">
    <script>
        (function() {
            try {
                var modo = localStorage.getItem('helpfull_tema_modo');
                var escuro = localStorage.getItem('helpfull_escuro') === 'true';
                if (modo === 'escuro' || (!modo && escuro) || (modo === 'sistema' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('acessibilidade-escuro');
                    if (document.body) document.body.classList.add('acessibilidade-escuro');
                }
            } catch(e) {}
        })();
    </script>
</head>

<body>

    <!-- NAVBAR TOPO GLOBAL -->
    <div class="nav-container-global">
        <nav class="navbar-topo">
            <a href="inicio.php" class="nav-logo" id="navLogoBtn">
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
            </ul>

            <div class="nav-dropdown-mobile" id="navDropdownMobile">
                <a href="inicio.php">Início</a>
                <a href="Diario.php">Diário</a>
                <a href="Comunidade.php">Comunidade</a>
                <a href="ChatBOT.php">Helpy</a>
                <a href="Atividades.php">Adicionais</a>
                <a href="Perfil.php">Perfil</a>
            </div>

            <a href="Perfil.php" style="text-decoration: none;">
                <div class="perfil-capsula" <?= !empty($fotoPerfilDb) ? "style='background-image: url($fotoPerfilDb);'" : '' ?> title="Meu Perfil">
                    <?php if (empty($fotoPerfilDb)): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    <?php endif; ?>
                </div>
            </a>
        </nav>
    </div>

    <!-- CONTEÚDO PRINCIPAL -->
    <main class="conteudo-site">

        <!-- BOTÃO DE VOLTAR À COMUNIDADE -->
        <div class="topo-voltar-row">
            <a href="Comunidade.php" class="link-voltar">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                <span>Voltar para Comunidade</span>
            </a>
        </div>

        <!-- CARD HEADER PRINCIPAL -->
        <section class="card-dispositivos-header">
            <div class="header-textos">
                <div class="badge-seguranca">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                    <span>Painel Administrativo</span>
                </div>
                <h1 class="titulo-pagina">Moderação da Comunidade</h1>
                <p class="desc-pagina">Supervisão de infrações, membros sob aviso disciplinar e histórico de ações de moderação da plataforma HelpFull.</p>
            </div>

            <div class="header-acoes">
                <div class="pill-contador-ativo">
                    <span class="ponto-pulse"></span>
                    <span id="statContadorPill">Moderação Ativa</span>
                </div>
            </div>
        </section>

        <!-- GRID DE ESTATÍSTICAS -->
        <section class="stats-grid-dispositivos">
            <div class="card-stat-box">
                <div class="stat-icone-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div class="stat-textos">
                    <div class="num" id="statTotal">—</div>
                    <div class="label">Total sob restrição</div>
                </div>
            </div>

            <div class="card-stat-box n1">
                <div class="stat-icone-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <div class="stat-textos">
                    <div class="num" id="statN1">—</div>
                    <div class="label">1º Aviso (1/3)</div>
                </div>
            </div>

            <div class="card-stat-box n2">
                <div class="stat-icone-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </div>
                <div class="stat-textos">
                    <div class="num" id="statN2">—</div>
                    <div class="label">2º Aviso (2/3)</div>
                </div>
            </div>

            <div class="card-stat-box n3">
                <div class="stat-icone-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                    </svg>
                </div>
                <div class="stat-textos">
                    <div class="num" id="statN3">—</div>
                    <div class="label">Banidos (3/3)</div>
                </div>
            </div>
        </section>

        <!-- CARD DE DIRETRIZES E REGRAS -->
        <section class="card-dicas-seguranca">
            <div class="dicas-icone">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
            </div>
            <div class="dicas-conteudo">
                <h3 class="dicas-titulo">Regras e Sistema de Infrações</h3>
                <div class="dicas-texto-lista">
                    <div class="dicas-regra-item">
                        <div class="dicas-regra-dot n1"></div>
                        <span><strong>1º Aviso (1/3):</strong> Primeiro post removido por violação. Expira automaticamente após 6 meses sem reincidências.</span>
                    </div>
                    <div class="dicas-regra-item">
                        <div class="dicas-regra-dot n2"></div>
                        <span><strong>2º Aviso (2/3):</strong> Notificação de alerta reforçado. Mais uma publicação removida causará banimento definitivo.</span>
                    </div>
                    <div class="dicas-regra-item">
                        <div class="dicas-regra-dot n3"></div>
                        <span><strong>Banimento (3/3):</strong> Suspensão da conta na comunidade. Fica bloqueada a criação de posts e reações.</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- SEÇÃO: USUÁRIOS COM INFRAÇÃO -->
        <div class="secao-titulo-row">
            <h2 class="secao-subtitulo">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
                <span>Membros com Infrações Registradas</span>
            </h2>
        </div>

        <!-- BARRA DE PESQUISA E FILTROS -->
        <div class="card-barra-filtros">
            <div class="busca-input-box">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="inputBusca" class="input-busca" placeholder="Filtrar por nome, e-mail ou motivo..." oninput="filtrarPorBusca()">
            </div>
            <div class="filtros-pills-row">
                <button type="button" class="btn-filtro-pill ativo" onclick="filtrarNivel(0, this)">Todos</button>
                <button type="button" class="btn-filtro-pill" onclick="filtrarNivel(1, this)">1º Aviso</button>
                <button type="button" class="btn-filtro-pill" onclick="filtrarNivel(2, this)">2º Aviso</button>
                <button type="button" class="btn-filtro-pill" onclick="filtrarNivel(3, this)">Banidos</button>
            </div>
        </div>

        <!-- LISTA DINÂMICA DE USUÁRIOS -->
        <section id="containerMembros" class="lista-membros-moderacao">
            <div class="card-estado-vazio">
                <div class="spinner"></div>
                <p class="vazio-desc">Carregando dados da moderação...</p>
            </div>
        </section>

        <!-- SEÇÃO: HISTÓRICO RECENTE -->
        <div class="secao-titulo-row" style="margin-top: 20px;">
            <h2 class="secao-subtitulo">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span>Histórico Recente de Moderações</span>
            </h2>
        </div>

        <section id="containerHistorico" class="lista-historico-items">
            <div class="card-estado-vazio">
                <div class="spinner"></div>
                <p class="vazio-desc">Carregando histórico disciplinar...</p>
            </div>
        </section>

    </main>

    <!-- SCRIPTS -->
    <script src="assets/transicao.js?v=20260926-v1"></script>
    <script>
    let todosUsuarios = [];
    let filtroAtual = 0;
    let termoBusca = '';

    // Menu Mobile Navbar
    const navLogoBtn = document.getElementById('navLogoBtn');
    const navDropdownMobile = document.getElementById('navDropdownMobile');
    const navSeta = document.querySelector('.nav-seta-dropdown');

    if (navLogoBtn && navDropdownMobile) {
        navLogoBtn.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                navDropdownMobile.classList.toggle('ativo');
                if (navSeta) {
                    navSeta.style.transform = navDropdownMobile.classList.contains('ativo') ? 'rotate(180deg)' : 'rotate(0deg)';
                }
            }
        });
    }

    async function carregarDados() {
        try {
            const r = await fetch('comunidade_moderacao_proc.php?acao=listar_banidos');
            const d = await r.json();
            if (!d.success) throw new Error(d.error || 'Erro ao consultar');
            todosUsuarios = d.usuarios || [];
            atualizarStats();
            aplicarFiltros();
        } catch (e) {
            document.getElementById('containerMembros').innerHTML = `
                <div class="card-estado-vazio">
                    <h3 class="vazio-titulo">Erro ao carregar dados</h3>
                    <p class="vazio-desc">${escHtml(e.message)}</p>
                </div>
            `;
        }

        carregarHistorico();
    }

    function atualizarStats() {
        const total = todosUsuarios.length;
        const n1 = todosUsuarios.filter(u => u.nivel == 1).length;
        const n2 = todosUsuarios.filter(u => u.nivel == 2).length;
        const n3 = todosUsuarios.filter(u => u.nivel == 3).length;

        document.getElementById('statTotal').textContent = total;
        document.getElementById('statN1').textContent = n1;
        document.getElementById('statN2').textContent = n2;
        document.getElementById('statN3').textContent = n3;

        const pill = document.getElementById('statContadorPill');
        if (pill) {
            pill.textContent = total > 0 ? `${total} membro${total > 1 ? 's' : ''} sob restrição` : 'Comunidade sem infrações';
        }
    }

    function filtrarNivel(nivel, btn) {
        filtroAtual = nivel;
        document.querySelectorAll('.btn-filtro-pill').forEach(b => b.classList.remove('ativo'));
        if (btn) btn.classList.add('ativo');
        aplicarFiltros();
    }

    function filtrarPorBusca() {
        termoBusca = (document.getElementById('inputBusca').value || '').trim().toLowerCase();
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
        renderMembros(filtrados);
    }

    function renderMembros(usuarios) {
        const container = document.getElementById('containerMembros');
        if (!usuarios.length) {
            container.innerHTML = `
                <div class="card-estado-vazio">
                    <div class="vazio-icone-wrapper">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            <polyline points="9 12 11 14 15 10"></polyline>
                        </svg>
                    </div>
                    <h3 class="vazio-titulo">Nenhum membro encontrado</h3>
                    <p class="vazio-desc">Não há infrações registradas para o filtro selecionado no momento.</p>
                </div>
            `;
            return;
        }

        const labels = { 1: '1º Aviso (1/3)', 2: '2º Aviso (2/3)', 3: 'Banido da Comunidade (3/3)' };
        const classes = { 1: 'n1', 2: 'n2', 3: 'n3' };

        const html = usuarios.map(u => {
            const criado = new Date(u.criado_em).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
            const expira = u.expira_avisos_em
                ? `Expira em: ${new Date(u.expira_avisos_em).toLocaleDateString('pt-BR')}`
                : (u.nivel < 3 ? 'Aviso ativo' : 'Permanente');

            const inicial = (u.nome || 'U').charAt(0).toUpperCase();

            return `
                <div class="card-membro-item" id="membroCard_${escHtml(u.usuario_id)}">
                    <div class="membro-esquerda">
                        <div class="membro-avatar-box">${escHtml(inicial)}</div>
                        <div class="membro-info-textos">
                            <div class="membro-topo-nome">
                                <span class="membro-nome">${escHtml(u.nome)}</span>
                                <span class="badge-nivel-pill ${classes[u.nivel]}">${labels[u.nivel]}</span>
                            </div>
                            <div class="membro-detalhes-meta">
                                <span class="membro-meta-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                        <polyline points="22,6 12,13 2,6"></polyline>
                                    </svg>
                                    ${escHtml(u.email)}
                                </span>
                                <span class="membro-meta-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    ${criado} (${expira})
                                </span>
                                <span class="membro-meta-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 20h9"></path>
                                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                                    </svg>
                                    ${u.total_punicoes ?? 1} ocorrência${(u.total_punicoes ?? 1) > 1 ? 's' : ''}
                                </span>
                            </div>
                            <div class="membro-motivo-box" title="Clique para ver o motivo completo" onclick="alert('Motivo registrado:\\n\\n' + ${JSON.stringify(u.motivo)})">
                                <strong>Motivo:</strong> ${escHtml((u.motivo || '').substring(0, 100))}${(u.motivo || '').length > 100 ? '...' : ''}
                            </div>
                        </div>
                    </div>
                    <div class="membro-direita-acao">
                        <button type="button" class="btn-liberar-item" onclick="liberarUsuario('${escHtml(u.usuario_id)}', this)">
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span>Liberar Membro</span>
                        </button>
                    </div>
                </div>
            `;
        }).join('');

        container.innerHTML = html;
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
                btn.innerHTML = `
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span>Liberar Membro</span>
                `;
            }
        } catch (e) {
            alert('Erro de rede ao tentar liberar usuário.');
            btn.disabled = false;
            btn.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Liberar Membro</span>
            `;
        }
    }

    async function carregarHistorico() {
        const container = document.getElementById('containerHistorico');
        try {
            const r = await fetch('comunidade_moderacao_proc.php?acao=listar_historico');
            const d = await r.json();
            if (!d.success || !d.historico.length) {
                container.innerHTML = `
                    <div class="card-estado-vazio" style="padding: 30px;">
                        <p class="vazio-desc">Nenhuma ação de moderação registrada no histórico recente.</p>
                    </div>
                `;
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

                return `
                    <div class="card-historico-item">
                        <span class="hist-acao-tag ${cls}">${escHtml(lbl)}</span>
                        <div class="hist-corpo">
                            <div class="hist-autor-linha">
                                ${escHtml(h.usuario_nome)} <span class="hist-email-suave">(${escHtml(h.usuario_email)})</span>
                            </div>
                            <div class="hist-motivo-texto">${escHtml(h.motivo || 'Sem observação adicional')}</div>
                            <div class="hist-data-rodape">Registrado em ${data} · Aplicado por ${escHtml(h.admin_nome || 'Sistema')}</div>
                        </div>
                    </div>
                `;
            }).join('');

            container.innerHTML = html;
        } catch (e) {
            container.innerHTML = `
                <div class="card-estado-vazio" style="padding: 30px;">
                    <p class="vazio-desc">Erro ao carregar histórico.</p>
                </div>
            `;
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
