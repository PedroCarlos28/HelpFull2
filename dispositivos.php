<?php
// =======================================================
// HELPFULL - PAINEL DE DISPOSITIVOS CONECTADOS
// Design exclusivo HelpFull - Sem emojis
// =======================================================

require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/sessao_helper.php';
require_once __DIR__ . '/email_helper.php';

// Redireciona se não estiver logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: Comeco.php');
    exit;
}

$usuarioId = $_SESSION['usuario_id'];
$tokenAtual = $_SESSION['token_sessao'] ?? null;

// Busca informações do usuário
$usuarioLogado = null;
try {
    $stmtUser = $pdo->prepare("SELECT id, nome, email, foto_perfil, dois_fatores_ativo FROM usuarios WHERE id = ?");
    $stmtUser->execute([$usuarioId]);
    $usuarioLogado = $stmtUser->fetch();
} catch (Exception $e) {
}

if (!$usuarioLogado) {
    header('Location: Comeco.php');
    exit;
}

$doisFatoresAtivo = !empty($usuarioLogado['dois_fatores_ativo']);
$emailMascarado = function_exists('mascararEmail') ? mascararEmail($usuarioLogado['email']) : $usuarioLogado['email'];
$fotoPerfilDb = !empty($usuarioLogado['foto_perfil']) ? $usuarioLogado['foto_perfil'] : '';

// Carrega as sessões ativas
$sessoes = listarSessoesUsuario($pdo, $usuarioId, $tokenAtual);

$sessaoAtual = null;
$outrasSessoes = [];

foreach ($sessoes as $s) {
    if ($s['is_atual']) {
        $sessaoAtual = $s;
    } else {
        $outrasSessoes[] = $s;
    }
}

// Se não encontrou marcada como atual, assume a primeira
if (!$sessaoAtual && !empty($sessoes)) {
    $sessaoAtual = $sessoes[0];
    $sessaoAtual['is_atual'] = true;
    $outrasSessoes = array_slice($sessoes, 1);
}

$totalConectados = count($sessoes);
$totalOutros = count($outrasSessoes);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HelpFull - Dispositivos Conectados</title>
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

        .nav-links a:hover {
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
            margin-bottom: 12px;
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

        .btn-desconectar-outros {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            background: #d6d6d6;
            color: #dc2626;
            border: 1px solid rgba(220, 38, 38, 0.25);
            padding: 10px 18px;
            border-radius: 18px;
            font-size: 0.86rem;
            font-weight: 800;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-desconectar-outros:hover:not(:disabled) {
            background: #fde8e8;
            border-color: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.15);
        }

        .btn-desconectar-outros:disabled {
            opacity: 0.45;
            cursor: not-allowed;
            border-color: rgba(255, 255, 255, 0.4);
            color: #888;
            background: #d6d6d6;
        }

        .btn-desconectar-outros svg {
            stroke: currentColor;
            width: 16px;
            height: 16px;
        }

        /* SEÇÃO: DISPOSITIVO ATUAL */
        .secao-titulo-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 10px;
            margin-bottom: 2px;
            padding: 0 6px;
        }

        .secao-subtitulo {
            font-size: 1.05rem;
            font-weight: 800;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-dispositivo-atual {
            background: #eaeaea;
            border-radius: 30px;
            padding: 26px 35px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.6);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 22px;
            position: relative;
        }

        .disp-esquerda {
            display: flex;
            align-items: center;
            gap: 20px;
            flex: 1;
            min-width: 0;
        }

        .disp-icone-box {
            width: 56px;
            height: 56px;
            border-radius: 20px;
            background: #d6d6d6;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #2b7a8c;
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        .disp-icone-box svg {
            width: 26px;
            height: 26px;
            stroke: currentColor;
        }

        .disp-info-textos {
            display: flex;
            flex-direction: column;
            gap: 5px;
            min-width: 0;
        }

        .disp-topo-nome {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .disp-nome {
            font-size: 1.12rem;
            font-weight: 800;
            color: #1a1a1a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .disp-pill-atual {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 12px;
            background: #2b7a8c;
            color: #ffffff;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }

        .disp-detalhes-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            font-size: 0.84rem;
            color: #555;
        }

        .disp-meta-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .disp-meta-item svg {
            width: 14px;
            height: 14px;
            stroke: #94a3b8;
            flex-shrink: 0;
        }

        .disp-direita-acao {
            flex-shrink: 0;
        }

        .btn-desconectar-item {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 18px;
            border-radius: 16px;
            background: #d6d6d6;
            color: #dc2626;
            border: 1px solid rgba(220, 38, 38, 0.25);
            font-size: 0.84rem;
            font-weight: 800;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-desconectar-item:hover {
            background: #dc2626;
            color: #ffffff;
            transform: scale(1.02);
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.25);
        }

        .btn-desconectar-item svg {
            width: 15px;
            height: 15px;
            stroke: currentColor;
        }

        /* LISTA DE OUTROS DISPOSITIVOS */
        .lista-outros-dispositivos {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .card-dispositivo-outro {
            background: #eaeaea;
            border-radius: 30px;
            padding: 24px 35px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.6);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.3s ease;
        }

        .card-dispositivo-outro:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06);
        }

        .card-dispositivo-outro.removendo {
            opacity: 0;
            transform: scale(0.95);
        }

        .disp-icone-box.outro {
            background: #d6d6d6;
            color: #475569;
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        /* ESTADO VAZIO (SEM OUTROS DISPOSITIVOS) */
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

        /* CARD DE DICAS DE SEGURANÇA */
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
            margin: 0 0 6px 0;
        }

        .dicas-texto {
            font-size: 0.86rem;
            color: #555;
            line-height: 1.5;
            margin: 0 0 10px 0;
        }

        .dicas-link {
            color: #2b7a8c;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 3px;
            font-size: 0.84rem;
        }

        .dicas-link:hover {
            color: #2b7a8c;
        }

        /* TRAVAMENTO DO FUNDO QUANDO MODAL ESTIVER ABERTO */
        html.modal-aberto-travar,
        body.modal-aberto-travar {
            overflow: hidden !important;
            overscroll-behavior: none !important;
            touch-action: none !important;
        }

        body.modal-aberto-travar {
            position: fixed !important;
            width: 100% !important;
            left: 0 !important;
            right: 0 !important;
        }

        /* MODAL DE CONFIRMAÇÃO */
        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 99999;
            background: rgba(8, 14, 18, 0.65);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.25s ease;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            touch-action: pan-y;
        }

        .modal-overlay.aberto {
            display: flex;
            opacity: 1;
        }

        .modal-card {
            background: #eaeaea;
            width: 100%;
            max-width: 480px;
            max-height: min(88vh, 88dvh);
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            touch-action: pan-y;
            border-radius: 30px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.6);
            padding: 34px 32px 30px 32px;
            position: relative;
            box-sizing: border-box;
            transform: scale(0.94);
            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            scrollbar-width: thin;
            scrollbar-color: rgba(43, 122, 140, 0.35) transparent;
        }

        .modal-card::-webkit-scrollbar {
            width: 6px;
        }

        .modal-card::-webkit-scrollbar-thumb {
            background: rgba(43, 122, 140, 0.3);
            border-radius: 10px;
        }

        .modal-overlay.aberto .modal-card {
            transform: scale(1);
        }

        /* BOTÃO DE FECHAR SEM ANIMAÇÃO */
        .modal-fechar-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 36px;
            height: 36px;
            background: rgba(0, 0, 0, 0.05);
            border: none;
            color: #555;
            cursor: pointer;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: none !important;
            transform: none !important;
            animation: none !important;
        }

        .modal-fechar-btn:hover,
        .modal-fechar-btn:active,
        .modal-fechar-btn:focus {
            background: rgba(0, 0, 0, 0.1);
            color: #111;
            transform: none !important;
            transition: none !important;
            animation: none !important;
        }

        .modal-icone-topo {
            width: 54px;
            height: 54px;
            border-radius: 20px;
            background: #fef2f2;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #dc2626;
            margin-bottom: 18px;
            border: 1px solid rgba(220, 38, 38, 0.2);
        }

        .modal-icone-topo svg {
            width: 26px;
            height: 26px;
            stroke: currentColor;
        }

        .modal-titulo {
            font-size: 1.35rem;
            font-weight: 900;
            color: #1a1a1a;
            margin: 0 0 8px 0;
            letter-spacing: -0.4px;
        }

        .modal-subtitulo {
            font-size: 0.88rem;
            color: #555;
            line-height: 1.5;
            margin: 0 0 18px 0;
        }

        .modal-disp-preview {
            background: #d6d6d6;
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 16px;
            padding: 12px 16px;
            margin-bottom: 22px;
            font-size: 0.86rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-disp-preview svg {
            stroke: #2b7a8c;
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        .modal-disp-preview strong {
            color: #1a1a1a;
        }

        .modal-alerta-info {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background: rgba(43, 122, 140, 0.1);
            border: 1px solid rgba(43, 122, 140, 0.22);
            border-radius: 18px;
            padding: 12px 16px;
            font-size: 0.84rem;
            color: #2b7a8c;
            line-height: 1.45;
            margin-bottom: 16px;
            text-align: left;
        }

        .modal-alerta-info svg {
            flex-shrink: 0;
            margin-top: 2px;
            stroke: #2b7a8c;
        }

        .campo-grupo-modal {
            margin-bottom: 20px;
            width: 100%;
            text-align: left;
        }

        .modal-label {
            display: block;
            font-size: 0.86rem;
            font-weight: 800;
            color: #444;
            margin-bottom: 7px;
            margin-left: 2px;
        }

        .btn-reenviar-codigo-modal {
            background: none;
            border: none;
            color: #2b7a8c;
            font-size: 0.82rem;
            font-weight: 800;
            font-family: 'Montserrat', sans-serif !important;
            cursor: pointer;
            padding: 0;
            text-decoration: underline;
            transition: color 0.2s;
        }

        .btn-reenviar-codigo-modal:hover:not(:disabled) {
            color: #1e5a67;
        }

        .btn-reenviar-codigo-modal:disabled {
            opacity: 0.55;
            cursor: not-allowed;
            text-decoration: none;
        }

        .input-com-icone-modal {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }

        .modal-input {
            width: 100%;
            height: 48px;
            border-radius: 20px;
            border: 1.5px solid rgba(255, 255, 255, 0.6);
            background: #d6d6d6;
            padding: 0 46px 0 18px;
            font-size: 0.95rem;
            font-weight: 600;
            font-family: 'Montserrat', sans-serif !important;
            color: #333;
            box-sizing: border-box;
            outline: none;
            transition: all 0.25s ease;
        }

        .modal-input:focus {
            background: #ffffff;
            border-color: #8ed6e4;
            box-shadow: 0 0 0 3px rgba(142, 214, 228, 0.35);
        }

        .input-codigo-destaque {
            letter-spacing: 6px;
            font-size: 1.3rem;
            font-weight: 800;
            text-align: center;
            font-family: 'Montserrat', monospace !important;
            padding-right: 18px !important;
        }

        .modal-hint {
            display: block;
            font-size: 0.76rem;
            color: #64748b;
            margin-top: 6px;
            margin-left: 4px;
        }

        .btn-olho-toggle {
            position: absolute;
            right: 12px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: transparent;
            border: none;
            color: #64748b;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s, background 0.2s;
        }

        .btn-olho-toggle:hover {
            color: #2b7a8c;
            background: rgba(0, 0, 0, 0.04);
        }

        .modal-msg-alerta {
            border-radius: 16px;
            padding: 11px 15px;
            font-size: 0.84rem;
            font-weight: 600;
            margin-bottom: 16px;
            line-height: 1.4;
            display: none;
            text-align: left;
        }

        .modal-msg-alerta.erro {
            display: block;
            background: #ffe6e6;
            border: 1px solid #ffb3b3;
            color: #cc0000;
        }

        .modal-msg-alerta.sucesso {
            display: block;
            background: #e6f6f9;
            border: 1px solid rgba(43, 122, 140, 0.25);
            color: #2b7a8c;
        }

        .modal-acoes {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
        }

        .modal-btn-cancelar {
            padding: 12px 20px;
            border-radius: 16px;
            background: #d6d6d6;
            color: #2b7a8c;
            border: 1px solid rgba(255, 255, 255, 0.6);
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }

        .modal-btn-cancelar:hover {
            background: #c8c8c8;
            color: #16333a;
        }

        .modal-btn-confirmar {
            padding: 12px 22px;
            border-radius: 16px;
            background: #dc2626;
            color: #ffffff;
            border: none;
            font-size: 0.88rem;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            font-family: inherit;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);
        }

        .modal-btn-confirmar:hover:not(:disabled) {
            background: #b91c1c;
            transform: scale(1.02);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.4);
        }

        .modal-btn-confirmar:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        .modal-btn-spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* TOAST NOTIFICAÇÃO */
        .toast-flutuante {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 100000;
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            color: #ffffff;
            padding: 14px 20px;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.88rem;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.12);
            transform: translateY(30px);
            opacity: 0;
            pointer-events: none;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            max-width: calc(100vw - 40px);
        }

        .toast-flutuante.visivel {
            transform: translateY(0);
            opacity: 1;
            pointer-events: auto;
        }

        .toast-flutuante.sucesso svg {
            stroke: #34d399;
        }

        .toast-flutuante.erro svg {
            stroke: #f87171;
        }

        .toast-flutuante svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        /* =======================================================
           SUPORTE A TEMA ESCURO (Acessibilidade)
           ======================================================= */
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

        body.acessibilidade-escuro .btn-acessibilidade {
            background: rgba(30, 36, 40, 0.8) !important;
            border-color: var(--tema-borda, #2f383e) !important;
            color: #f1f5f9 !important;
        }

        body.acessibilidade-escuro .link-voltar {
            background: var(--tema-superficie, #1a2227) !important;
            color: #7dd3fc !important;
            border-color: rgba(125, 211, 252, 0.25) !important;
        }

        body.acessibilidade-escuro .card-dispositivos-header,
        body.acessibilidade-escuro .card-dispositivo-atual,
        body.acessibilidade-escuro .card-dispositivo-outro,
        body.acessibilidade-escuro .card-estado-vazio {
            background: var(--tema-superficie, #1a2227) !important;
            border-color: var(--tema-borda, #2f383e) !important;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.4) !important;
            color: var(--tema-texto, #f1f5f9) !important;
        }

        body.acessibilidade-escuro .titulo-pagina,
        body.acessibilidade-escuro .disp-nome,
        body.acessibilidade-escuro .vazio-titulo,
        body.acessibilidade-escuro .secao-subtitulo {
            color: var(--tema-texto, #ffffff) !important;
        }

        body.acessibilidade-escuro .desc-pagina,
        body.acessibilidade-escuro .disp-detalhes-meta,
        body.acessibilidade-escuro .vazio-desc {
            color: var(--tema-texto-secundario, #94a3b8) !important;
        }

        body.acessibilidade-escuro .badge-seguranca,
        body.acessibilidade-escuro .pill-contador-ativo,
        body.acessibilidade-escuro .vazio-icone-wrapper {
            background: rgba(43, 122, 140, 0.2) !important;
            color: #7dd3fc !important;
            border-color: rgba(125, 211, 252, 0.2) !important;
        }

        body.acessibilidade-escuro .disp-icone-box {
            background: rgba(43, 122, 140, 0.25) !important;
            color: #7dd3fc !important;
            border-color: rgba(125, 211, 252, 0.2) !important;
        }

        body.acessibilidade-escuro .disp-icone-box.outro {
            background: #252e35 !important;
            color: #94a3b8 !important;
            border-color: #334155 !important;
        }

        body.acessibilidade-escuro .btn-desconectar-outros {
            background: #1a2227 !important;
            color: #f87171 !important;
            border-color: rgba(248, 113, 113, 0.3) !important;
        }

        body.acessibilidade-escuro .btn-desconectar-outros:hover:not(:disabled) {
            background: #2c1a1d !important;
        }

        body.acessibilidade-escuro .btn-desconectar-item {
            background: rgba(239, 68, 68, 0.15) !important;
            color: #fca5a5 !important;
            border-color: rgba(239, 68, 68, 0.3) !important;
        }

        body.acessibilidade-escuro .btn-desconectar-item:hover {
            background: #dc2626 !important;
            color: #ffffff !important;
        }

        body.acessibilidade-escuro .card-dicas-seguranca {
            background: rgba(43, 122, 140, 0.12) !important;
            border-color: rgba(125, 211, 252, 0.2) !important;
        }

        body.acessibilidade-escuro .dicas-icone {
            background: #1a2227 !important;
            color: #7dd3fc !important;
            border-color: rgba(125, 211, 252, 0.2) !important;
        }

        body.acessibilidade-escuro .dicas-titulo,
        body.acessibilidade-escuro .dicas-link {
            color: #7dd3fc !important;
        }

        body.acessibilidade-escuro .dicas-texto {
            color: #cbd5e1 !important;
        }

        body.acessibilidade-escuro .modal-card {
            background: var(--tema-superficie, #1a2227) !important;
            border-color: var(--tema-borda, #2f383e) !important;
            color: var(--tema-texto, #f1f5f9) !important;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.8) !important;
        }

        body.acessibilidade-escuro .modal-titulo {
            color: var(--tema-texto, #ffffff) !important;
        }

        body.acessibilidade-escuro .modal-subtitulo {
            color: var(--tema-texto-secundario, #94a3b8) !important;
        }

        body.acessibilidade-escuro .modal-disp-preview {
            background: #252e35 !important;
            border-color: #334155 !important;
            color: #cbd5e1 !important;
        }

        body.acessibilidade-escuro .modal-disp-preview strong {
            color: #ffffff !important;
        }

        body.acessibilidade-escuro .modal-fechar-btn,
        body.acessibilidade-escuro .modal-btn-cancelar {
            background: rgba(255, 255, 255, 0.08) !important;
            color: #cbd5e1 !important;
        }

        body.acessibilidade-escuro .modal-fechar-btn:hover,
        body.acessibilidade-escuro .modal-btn-cancelar:hover {
            background: rgba(255, 255, 255, 0.16) !important;
            color: #ffffff !important;
            transform: none !important;
            transition: none !important;
            animation: none !important;
        }

        body.acessibilidade-escuro .modal-alerta-info {
            background: rgba(125, 211, 252, 0.08) !important;
            border-color: rgba(125, 211, 252, 0.2) !important;
            color: #7dd3fc !important;
        }

        body.acessibilidade-escuro .modal-alerta-info svg {
            stroke: #7dd3fc !important;
        }

        body.acessibilidade-escuro .modal-label {
            color: var(--tema-texto, #f1f5f9) !important;
        }

        body.acessibilidade-escuro .modal-input {
            background: #252d32 !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            color: #f1f5f9 !important;
        }

        body.acessibilidade-escuro .modal-input:focus {
            background: #182228 !important;
            border-color: #7dd3fc !important;
            box-shadow: 0 0 0 3px rgba(125, 211, 252, 0.25) !important;
        }

        body.acessibilidade-escuro .btn-reenviar-codigo-modal {
            color: #7dd3fc !important;
        }

        body.acessibilidade-escuro .modal-hint {
            color: var(--tema-texto-secundario, #94a3b8) !important;
        }

        body.acessibilidade-escuro .btn-olho-toggle {
            color: #94a3b8 !important;
        }

        body.acessibilidade-escuro .btn-olho-toggle:hover {
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.08) !important;
        }

        body.acessibilidade-escuro .modal-msg-alerta.erro {
            background: rgba(239, 68, 68, 0.15) !important;
            border-color: rgba(248, 113, 113, 0.3) !important;
            color: #fca5a5 !important;
        }

        body.acessibilidade-escuro .modal-msg-alerta.sucesso {
            background: rgba(34, 197, 94, 0.15) !important;
            border-color: rgba(74, 222, 128, 0.3) !important;
            color: #86efac !important;
        }

        /* =======================================================
           RESPONSIVIDADE (Mobile e Tablets)
           ======================================================= */
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

            .nav-logo {
                cursor: pointer;
            }

            .acessibilidade-anchor {
                display: none;
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

            .topo-voltar-row {
                margin-top: 5px;
                margin-bottom: 12px;
            }

            .card-dispositivos-header {
                padding: 24px 20px;
                flex-direction: column;
                align-items: flex-start;
                gap: 18px;
            }

            .header-acoes {
                width: 100%;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
            }

            .btn-desconectar-outros {
                width: 100%;
                justify-content: center;
            }

            .card-dispositivo-atual,
            .card-dispositivo-outro {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
                padding: 20px 18px;
            }

            .disp-esquerda {
                width: 100%;
                gap: 14px;
            }

            .disp-direita-acao {
                width: 100%;
            }

            .btn-desconectar-item {
                width: 100%;
                justify-content: center;
            }

            .card-dicas-seguranca {
                flex-direction: column;
                padding: 20px 18px;
            }

            .toast-flutuante {
                bottom: 20px;
                right: 20px;
                left: 20px;
                width: auto;
                max-width: none;
            }
        }
    </style>
    <link rel="stylesheet" href="assets/acessibilidade.css?v=20260927-v3">
    <link rel="stylesheet" href="assets/transicao.css?v=20260926-v1">
    <link rel="stylesheet" href="assets/tutorial.css?v=20261001-v2">
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
                <a href="dispositivos.php" class="ativo">Dispositivos Conectados</a>
                <a href="javascript:void(0)" onclick="if(window.iniciarTutorial){window.iniciarTutorial(true);}">✦ Tour da Página</a>
                <a href="javascript:void(0)" class="btn-abrir-acessibilidade" onclick="abrirPainelAcessibilidadeMobile(event);">Configurações</a>
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

        <div class="acessibilidade-anchor">
            <button type="button" class="btn-acessibilidade" id="btnAcessibilidade" onclick="togglePainelAcessibilidade(event)" aria-label="Abrir configurações e acessibilidade" title="Configurações">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
            </button>
        </div>
    </div>

    <!-- CONTEÚDO PRINCIPAL -->
    <main class="conteudo-site">

        <!-- BOTÃO DE VOLTAR AO PERFIL -->
        <div class="topo-voltar-row">
            <a href="Perfil.php" class="link-voltar">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                <span>Voltar ao Perfil</span>
            </a>
        </div>

        <!-- CABEÇALHO DO PAINEL -->
        <section class="card-dispositivos-header">
            <div class="header-textos">
                <div class="badge-seguranca">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                    <span>Segurança da Conta</span>
                </div>
                <h1 class="titulo-pagina">Dispositivos Conectados</h1>
                <p class="desc-pagina">
                    Acompanhe e controle os navegadores e dispositivos atualmente autorizados na sua conta HelpFull. Encerre qualquer acesso que você não reconheça.
                </p>
            </div>

            <div class="header-acoes">
                <div class="pill-contador-ativo" id="indicadorSessoesTotais">
                    <span class="ponto-pulse"></span>
                    <span id="textoContadorSessoes"><?= $totalConectados ?> <?= $totalConectados === 1 ? 'sessão ativa' : 'sessões ativas' ?></span>
                </div>

                <button type="button" class="btn-desconectar-outros" id="btnDesconectarOutros" onclick="abrirModalDesconectarOutros()" <?= $totalOutros === 0 ? 'disabled' : '' ?>>
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span>Desconectar outros dispositivos</span>
                </button>
            </div>
        </section>

        <!-- SEÇÃO: DISPOSITIVO ATUAL -->
        <div class="secao-titulo-row">
            <h2 class="secao-subtitulo">
                <span>Sessão Atual</span>
            </h2>
        </div>

        <?php if ($sessaoAtual): ?>
            <div class="card-dispositivo-atual" id="cardSessaoAtual">
                <div class="disp-esquerda">
                    <div class="disp-icone-box">
                        <?php if ($sessaoAtual['tipo_dispositivo'] === 'mobile'): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                                <line x1="12" y1="18" x2="12.01" y2="18"></line>
                            </svg>
                        <?php elseif ($sessaoAtual['tipo_dispositivo'] === 'tablet'): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect>
                                <line x1="12" y1="18" x2="12.01" y2="18"></line>
                            </svg>
                        <?php else: ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                                <line x1="8" y1="21" x2="16" y2="21"></line>
                                <line x1="12" y1="17" x2="12" y2="21"></line>
                            </svg>
                        <?php endif; ?>
                    </div>

                    <div class="disp-info-textos">
                        <div class="disp-topo-nome">
                            <span class="disp-nome"><?= htmlspecialchars($sessaoAtual['dispositivo']) ?></span>
                            <span class="disp-pill-atual">Este Dispositivo</span>
                        </div>
                        <div class="disp-detalhes-meta">
                            <span class="disp-meta-item" title="Endereço de rede">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="2" y1="12" x2="22" y2="12"></line>
                                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                                </svg>
                                <span><?= htmlspecialchars($sessaoAtual['ip_mascarado']) ?></span>
                            </span>
                            <span class="disp-meta-item" title="Data do login">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <span>Conectado em <?= $sessaoAtual['criado_em_formatado'] ?></span>
                            </span>
                            <span class="disp-meta-item" title="Última atividade">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="23 4 23 10 17 10"></polyline>
                                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                                </svg>
                                <span>Ativo agora</span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="disp-direita-acao">
                    <button type="button" class="btn-desconectar-item" onclick="abrirModalDesconectarItem(<?= $sessaoAtual['id'] ?>, '<?= htmlspecialchars(addslashes($sessaoAtual['dispositivo'])) ?>', true)">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        <span>Sair desta conta</span>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- SEÇÃO: OUTROS DISPOSITIVOS -->
        <div class="secao-titulo-row">
            <h2 class="secao-subtitulo">
                <span>Outros Dispositivos Conectados</span>
                <span id="badgeContadorOutros" style="font-size: 0.85rem; font-weight: 700; color: #64748b;">(<?= $totalOutros ?>)</span>
            </h2>
        </div>

        <div class="lista-outros-dispositivos" id="listaOutrosDispositivos">
            <?php if (!empty($outrasSessoes)): ?>
                <?php foreach ($outrasSessoes as $outra): ?>
                    <div class="card-dispositivo-outro" id="sessao-card-<?= $outra['id'] ?>">
                        <div class="disp-esquerda">
                            <div class="disp-icone-box outro">
                                <?php if ($outra['tipo_dispositivo'] === 'mobile'): ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                                        <line x1="12" y1="18" x2="12.01" y2="18"></line>
                                    </svg>
                                <?php elseif ($outra['tipo_dispositivo'] === 'tablet'): ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect>
                                        <line x1="12" y1="18" x2="12.01" y2="18"></line>
                                    </svg>
                                <?php else: ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                                        <line x1="8" y1="21" x2="16" y2="21"></line>
                                        <line x1="12" y1="17" x2="12" y2="21"></line>
                                    </svg>
                                <?php endif; ?>
                            </div>

                            <div class="disp-info-textos">
                                <span class="disp-nome"><?= htmlspecialchars($outra['dispositivo']) ?></span>
                                <div class="disp-detalhes-meta">
                                    <span class="disp-meta-item" title="Endereço de rede">
                                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="2" y1="12" x2="22" y2="12"></line>
                                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                                        </svg>
                                        <span><?= htmlspecialchars($outra['ip_mascarado']) ?></span>
                                    </span>
                                    <span class="disp-meta-item" title="Data de início">
                                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                            <line x1="16" y1="2" x2="16" y2="6"></line>
                                            <line x1="8" y1="2" x2="8" y2="6"></line>
                                            <line x1="3" y1="10" x2="21" y2="10"></line>
                                        </svg>
                                        <span>Iniciado em <?= $outra['criado_em_formatado'] ?></span>
                                    </span>
                                    <span class="disp-meta-item" title="Última atividade">
                                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="23 4 23 10 17 10"></polyline>
                                            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                                        </svg>
                                        <span>Último acesso: <?= $outra['ultimo_acesso_relativo'] ?></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="disp-direita-acao">
                            <button type="button" class="btn-desconectar-item" onclick="abrirModalDesconectarItem(<?= $outra['id'] ?>, '<?= htmlspecialchars(addslashes($outra['dispositivo'])) ?>', false)">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                    <polyline points="16 17 21 12 16 7"></polyline>
                                    <line x1="21" y1="12" x2="9" y2="12"></line>
                                </svg>
                                <span>Desconectar</span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="card-estado-vazio" id="cardEstadoVazio">
                    <div class="vazio-icone-wrapper">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            <polyline points="9 12 11 14 15 10"></polyline>
                        </svg>
                    </div>
                    <h3 class="vazio-titulo">Nenhum outro dispositivo conectado</h3>
                    <p class="vazio-desc">
                        Sua conta HelpFull está conectada apenas neste dispositivo no momento. Nenhuma outra sessão ativa foi encontrada.
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- CARD: DICAS DE SEGURANÇA -->
        <section class="card-dicas-seguranca">
            <div class="dicas-icone">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
            </div>
            <div class="dicas-conteudo">
                <h4 class="dicas-titulo">Proteja sua conta HelpFull</h4>
                <p class="dicas-texto">
                    Se você notar algum dispositivo suspeito ou não reconhecido, desconecte-o imediatamente e altere sua senha de acesso. Recomendamos também ativar a Verificação em Duas Etapas (2FA) para maior proteção.
                </p>
                <a href="Perfil.php" class="dicas-link">Gerenciar segurança e senha no Perfil</a>
            </div>
        </section>

    </main>

    <!-- MODAL DE CONFIRMAÇÃO DE DESCONEXÃO -->
    <div class="modal-overlay" id="modalConfirmacaoDesconectar" onclick="fecharModalConfirmacao(event)">
        <div class="modal-card" onclick="event.stopPropagation()">
            <button type="button" class="modal-fechar-btn" onclick="fecharModalConfirmacao()" aria-label="Fechar modal">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>

            <div class="modal-icone-topo">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
            </div>

            <h3 class="modal-titulo" id="modalTitulo">Desconectar dispositivo?</h3>
            <p class="modal-subtitulo" id="modalSubtitulo">
                A sessão deste aparelho será encerrada imediatamente. Será necessário inserir as credenciais novamente para acessar.
            </p>

            <div class="modal-disp-preview" id="modalDispPreview">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                    <line x1="8" y1="21" x2="16" y2="21"></line>
                    <line x1="12" y1="17" x2="12" y2="21"></line>
                </svg>
                <span id="modalDispNome">Dispositivo selecionado</span>
            </div>

            <!-- MENSAGEM DE ALERTA DO MODAL -->
            <div class="modal-msg-alerta" id="msgAlertaModalDisp"></div>

            <?php if ($doisFatoresAtivo): ?>
            <!-- AUTENTICAÇÃO COM 2FA ATIVO (EXIGE CÓDIGO DO E-MAIL) -->
            <div id="blocoDisp2FA" class="campo-grupo-modal">
                <div class="modal-alerta-info">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <div>
                        Código de segurança enviado para <strong id="emailMascaradoDisp"><?= htmlspecialchars($emailMascarado) ?></strong>
                    </div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 7px;">
                    <label class="modal-label" for="codigo2faDisp" style="margin-bottom: 0;">Código de Verificação (6 dígitos):</label>
                    <button type="button" class="btn-reenviar-codigo-modal" id="btnReenviarCodigoDisp" onclick="solicitarCodigo2faDisp()">Reenviar código</button>
                </div>
                <input type="text" id="codigo2faDisp" class="modal-input input-codigo-destaque" maxlength="6" inputmode="numeric" placeholder="000000" autocomplete="one-time-code">
                <span class="modal-hint">Digite o código de 6 dígitos enviado ao seu e-mail para validar.</span>
            </div>
            <?php else: ?>
            <!-- AUTENTICAÇÃO SEM 2FA (EXIGE SENHA DA CONTA) -->
            <div id="blocoDispSenha" class="campo-grupo-modal">
                <label class="modal-label" for="senhaConfirmacaoDisp">Confirme sua senha para continuar:</label>
                <div class="input-com-icone-modal">
                    <input type="password" id="senhaConfirmacaoDisp" class="modal-input" placeholder="Digite sua senha" autocomplete="current-password">
                    <button type="button" class="btn-olho-toggle" onclick="toggleMostrarSenhaDisp('senhaConfirmacaoDisp', this)" aria-label="Mostrar ou ocultar senha" title="Mostrar senha">
                        <svg class="olho-aberto" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg class="olho-fechado" style="display: none;" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </button>
                </div>
                <span class="modal-hint">A confirmação da senha é necessária para validar esta ação.</span>
            </div>
            <?php endif; ?>

            <div class="modal-acoes">
                <button type="button" class="modal-btn-cancelar" onclick="fecharModalConfirmacao()">Cancelar</button>
                <button type="button" class="modal-btn-confirmar" id="btnConfirmarAcaoModal" onclick="executarAcaoConfirmada()">
                    <span id="textoBtnConfirmarModal">Sim, desconectar</span>
                </button>
            </div>
        </div>
    </div>

    <!-- TOAST DE NOTIFICAÇÃO -->
    <div class="toast-flutuante" id="toastFeedback">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" id="toastIcone">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        <span id="toastMensagem">Operação realizada com sucesso.</span>
    </div>

    <script>
        // Estado da ação pendente no modal
        let acaoPendente = null; // { tipo: 'item'|'outros', id: null, nome: '', isAtual: false }
        let toastTimeout = null;
        let timerCooldownDisp = null;
        const tem2FA = <?= $doisFatoresAtivo ? 'true' : 'false' ?>;

        // Toggle do menu mobile
        const navLogoBtn = document.getElementById('navLogoBtn');
        const navDropdownMobile = document.getElementById('navDropdownMobile');
        if (navLogoBtn && navDropdownMobile) {
            navLogoBtn.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    e.preventDefault();
                    navDropdownMobile.classList.toggle('ativo');
                }
            });

            document.addEventListener('click', function(e) {
                if (!navLogoBtn.contains(e.target) && !navDropdownMobile.contains(e.target)) {
                    navDropdownMobile.classList.remove('ativo');
                }
            });
        }

        function abrirPainelAcessibilidadeMobile(event) {
            if (typeof togglePainelAcessibilidade === 'function') {
                togglePainelAcessibilidade(event);
            }
        }

        // Exibe toast de retorno
        function mostrarToast(mensagem, tipo = 'sucesso') {
            const toast = document.getElementById('toastFeedback');
            const texto = document.getElementById('toastMensagem');
            const icone = document.getElementById('toastIcone');

            if (!toast || !texto) return;

            clearTimeout(toastTimeout);

            texto.textContent = mensagem;
            toast.className = 'toast-flutuante visivel ' + tipo;

            if (tipo === 'sucesso') {
                icone.innerHTML = '<polyline points="20 6 9 17 4 12"></polyline>';
            } else {
                icone.innerHTML = '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>';
            }

            toastTimeout = setTimeout(() => {
                toast.classList.remove('visivel');
            }, 4500);
        }

        // Alterna visualização de senha
        function toggleMostrarSenhaDisp(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const olhoAberto = btn.querySelector('.olho-aberto');
            const olhoFechado = btn.querySelector('.olho-fechado');

            if (input.type === 'password') {
                input.type = 'text';
                if (olhoAberto) olhoAberto.style.display = 'none';
                if (olhoFechado) olhoFechado.style.display = 'block';
            } else {
                input.type = 'password';
                if (olhoAberto) olhoAberto.style.display = 'block';
                if (olhoFechado) olhoFechado.style.display = 'none';
            }
        }

        // Solicita envio de novo código de verificação para o e-mail (quando 2FA está ativo)
        async function solicitarCodigo2faDisp() {
            const btnReenviar = document.getElementById('btnReenviarCodigoDisp');
            const alerta = document.getElementById('msgAlertaModalDisp');
            if (btnReenviar) btnReenviar.disabled = true;

            try {
                const resp = await fetch('dispositivos_proc.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ acao: 'solicitar_codigo' })
                });
                const data = await resp.json();

                if (data.sucesso) {
                    if (alerta) {
                        alerta.className = 'modal-msg-alerta sucesso';
                        alerta.textContent = data.mensagem || 'Código de verificação enviado para seu e-mail.';
                        alerta.style.display = 'block';
                    }
                    if (data.email_mascarado) {
                        const elEmail = document.getElementById('emailMascaradoDisp');
                        if (elEmail) elEmail.textContent = data.email_mascarado;
                    }
                    iniciarCooldownDisp(20);
                } else {
                    if (alerta) {
                        alerta.className = 'modal-msg-alerta erro';
                        alerta.textContent = data.mensagem || 'Erro ao enviar código de segurança.';
                        alerta.style.display = 'block';
                    }
                    if (btnReenviar) btnReenviar.disabled = false;
                }
            } catch (err) {
                if (btnReenviar) btnReenviar.disabled = false;
            }
        }

        function iniciarCooldownDisp(segundos) {
            const btnReenviar = document.getElementById('btnReenviarCodigoDisp');
            if (!btnReenviar) return;
            clearInterval(timerCooldownDisp);
            let restante = segundos;
            btnReenviar.disabled = true;
            btnReenviar.textContent = `Reenviar (${restante}s)`;

            timerCooldownDisp = setInterval(() => {
                restante--;
                if (restante <= 0) {
                    clearInterval(timerCooldownDisp);
                    btnReenviar.disabled = false;
                    btnReenviar.textContent = 'Reenviar código';
                } else {
                    btnReenviar.textContent = `Reenviar (${restante}s)`;
                }
            }, 1000);
        }

        function limparCamposModalDisp() {
            const alerta = document.getElementById('msgAlertaModalDisp');
            if (alerta) {
                alerta.style.display = 'none';
                alerta.textContent = '';
            }

            const inpCod = document.getElementById('codigo2faDisp');
            if (inpCod) inpCod.value = '';

            const inpSenha = document.getElementById('senhaConfirmacaoDisp');
            if (inpSenha) {
                inpSenha.value = '';
                inpSenha.type = 'password';
            }

            const btnConfirmar = document.getElementById('btnConfirmarAcaoModal');
            if (btnConfirmar) btnConfirmar.disabled = false;
        }

        // Abre modal para desconectar item individual
        function abrirModalDesconectarItem(id, nome, isAtual = false) {
            acaoPendente = { tipo: 'item', id: id, nome: nome, isAtual: isAtual };
            limparCamposModalDisp();

            const modal = document.getElementById('modalConfirmacaoDesconectar');
            const titulo = document.getElementById('modalTitulo');
            const subtitulo = document.getElementById('modalSubtitulo');
            const dispNome = document.getElementById('modalDispNome');
            const btnConfirmar = document.getElementById('textoBtnConfirmarModal');

            if (isAtual) {
                titulo.textContent = 'Desconectar este dispositivo?';
                subtitulo.textContent = 'Você será desconectado da sua conta neste navegador e precisará fazer login novamente.';
                btnConfirmar.textContent = 'Sim, sair da conta';
            } else {
                titulo.textContent = 'Desconectar dispositivo?';
                subtitulo.textContent = 'A sessão neste aparelho será revogada imediatamente. O usuário precisará fazer login novamente.';
                btnConfirmar.textContent = 'Sim, desconectar';
            }

            dispNome.textContent = nome;
            modal.classList.add('aberto');
            travarScrollFundo();

            if (tem2FA) {
                solicitarCodigo2faDisp();
                setTimeout(() => {
                    const inp = document.getElementById('codigo2faDisp');
                    if (inp) inp.focus();
                }, 300);
            } else {
                setTimeout(() => {
                    const inp = document.getElementById('senhaConfirmacaoDisp');
                    if (inp) inp.focus();
                }, 300);
            }
        }

        // Abre modal para desconectar todas as outras sessões
        function abrirModalDesconectarOutros() {
            acaoPendente = { tipo: 'outros' };
            limparCamposModalDisp();

            const modal = document.getElementById('modalConfirmacaoDesconectar');
            const titulo = document.getElementById('modalTitulo');
            const subtitulo = document.getElementById('modalSubtitulo');
            const dispNome = document.getElementById('modalDispNome');
            const btnConfirmar = document.getElementById('textoBtnConfirmarModal');

            titulo.textContent = 'Desconectar todos os outros dispositivos?';
            subtitulo.textContent = 'Todas as sessões ativas em outros computadores, celulares e tablets serão encerradas imediatamente. Apenas este aparelho continuará conectado.';
            dispNome.textContent = 'Todos os outros dispositivos ativos';
            btnConfirmar.textContent = 'Desconectar todos';

            modal.classList.add('aberto');
            travarScrollFundo();

            if (tem2FA) {
                solicitarCodigo2faDisp();
                setTimeout(() => {
                    const inp = document.getElementById('codigo2faDisp');
                    if (inp) inp.focus();
                }, 300);
            } else {
                setTimeout(() => {
                    const inp = document.getElementById('senhaConfirmacaoDisp');
                    if (inp) inp.focus();
                }, 300);
            }
        }

        let scrollPosBloqueioModal = 0;

        function travarScrollFundo() {
            scrollPosBloqueioModal = window.pageYOffset || document.documentElement.scrollTop || 0;
            document.body.style.top = `-${scrollPosBloqueioModal}px`;
            document.body.classList.add('modal-aberto-travar');
            document.documentElement.classList.add('modal-aberto-travar');
        }

        function destravarScrollFundo() {
            document.body.classList.remove('modal-aberto-travar');
            document.documentElement.classList.remove('modal-aberto-travar');
            document.body.style.top = '';
            window.scrollTo(0, scrollPosBloqueioModal);
        }

        function fecharModalConfirmacao(e) {
            if (e && e.target !== e.currentTarget && !e.target.classList.contains('modal-fechar-btn')) {
                return;
            }
            const modal = document.getElementById('modalConfirmacaoDesconectar');
            if (modal) {
                modal.classList.remove('aberto');
            }
            destravarScrollFundo();
            limparCamposModalDisp();
            acaoPendente = null;
        }

        // Executa a requisição de desconexão
        async function executarAcaoConfirmada() {
            if (!acaoPendente) return;

            const alerta = document.getElementById('msgAlertaModalDisp');
            let codigo2fa = '';
            let senha = '';

            // Validação de segurança no cliente
            if (tem2FA) {
                const inpCod = document.getElementById('codigo2faDisp');
                codigo2fa = inpCod ? inpCod.value.trim() : '';
                if (!codigo2fa || codigo2fa.length !== 6) {
                    if (alerta) {
                        alerta.className = 'modal-msg-alerta erro';
                        alerta.textContent = 'Por favor, digite o código de 6 dígitos enviado ao seu e-mail.';
                        alerta.style.display = 'block';
                    }
                    if (inpCod) inpCod.focus();
                    return;
                }
            } else {
                const inpSenha = document.getElementById('senhaConfirmacaoDisp');
                senha = inpSenha ? inpSenha.value : '';
                if (!senha) {
                    if (alerta) {
                        alerta.className = 'modal-msg-alerta erro';
                        alerta.textContent = 'Por favor, digite sua senha para confirmar a desconexão.';
                        alerta.style.display = 'block';
                    }
                    if (inpSenha) inpSenha.focus();
                    return;
                }
            }

            if (alerta) alerta.style.display = 'none';

            const acaoExecutada = { ...acaoPendente };
            const btnConfirmar = document.getElementById('btnConfirmarAcaoModal');
            const textoBtn = document.getElementById('textoBtnConfirmarModal');
            const textoOriginal = textoBtn.textContent;

            btnConfirmar.disabled = true;
            textoBtn.innerHTML = '<span class="modal-btn-spinner"></span> Aguarde...';

            try {
                let payload = {};
                if (acaoExecutada.tipo === 'item') {
                    payload = { acao: 'desconectar', id: acaoExecutada.id };
                } else if (acaoExecutada.tipo === 'outros') {
                    payload = { acao: 'desconectar_outros' };
                }

                if (tem2FA) {
                    payload.codigo_2fa = codigo2fa;
                } else {
                    payload.senha = senha;
                }

                const resposta = await fetch('dispositivos_proc.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                let dados;
                try {
                    dados = await resposta.json();
                } catch (eParse) {
                    throw new Error('Resposta inválida do servidor.');
                }

                if (!dados.sucesso) {
                    if (alerta) {
                        alerta.className = 'modal-msg-alerta erro';
                        alerta.textContent = dados.mensagem || 'Não foi possível concluir a ação.';
                        alerta.style.display = 'block';
                    }
                    mostrarToast(dados.mensagem || 'Não foi possível concluir a ação.', 'erro');
                    btnConfirmar.disabled = false;
                    textoBtn.textContent = textoOriginal;
                    return;
                }

                // Se desconectou o dispositivo atual
                if (dados.logout_atual) {
                    fecharModalConfirmacao();
                    mostrarToast('Dispositivo desconectado. Redirecionando...', 'sucesso');
                    setTimeout(() => {
                        window.location.href = dados.redirecionar || 'Comeco.php';
                    }, 1200);
                    return;
                }

                fecharModalConfirmacao();
                mostrarToast(dados.mensagem || 'Dispositivo desconectado com sucesso.', 'sucesso');

                // Atualiza a interface dinamicamente
                if (acaoExecutada.tipo === 'item') {
                    const cardItem = document.getElementById('sessao-card-' + acaoExecutada.id);
                    if (cardItem) {
                        cardItem.classList.add('removendo');
                        setTimeout(() => {
                            cardItem.remove();
                            verificarListaVazia();
                        }, 250);
                    } else {
                        verificarListaVazia();
                    }
                } else if (acaoExecutada.tipo === 'outros') {
                    const lista = document.getElementById('listaOutrosDispositivos');
                    if (lista) {
                        lista.innerHTML = `
                            <div class="card-estado-vazio" id="cardEstadoVazio">
                                <div class="vazio-icone-wrapper">
                                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                        <polyline points="9 12 11 14 15 10"></polyline>
                                    </svg>
                                </div>
                                <h3 class="vazio-titulo">Nenhum outro dispositivo conectado</h3>
                                <p class="vazio-desc">
                                    Todas as outras sessões foram desconectadas. Sua conta HelpFull está ativa somente neste aparelho.
                                </p>
                            </div>
                        `;
                    }
                    atualizarContadores(0);
                }
            } catch (err) {
                console.error('Erro na ação de desconexão:', err);
                if (alerta) {
                    alerta.className = 'modal-msg-alerta erro';
                    alerta.textContent = 'Falha na comunicação com o servidor. Tente novamente.';
                    alerta.style.display = 'block';
                }
                mostrarToast('Falha na comunicação com o servidor. Tente novamente.', 'erro');
                btnConfirmar.disabled = false;
                textoBtn.textContent = textoOriginal;
            }
        }

        function verificarListaVazia() {
            const lista = document.getElementById('listaOutrosDispositivos');
            if (!lista) return;

            const cardsRestantes = lista.querySelectorAll('.card-dispositivo-outro');
            const totalOutros = cardsRestantes.length;

            atualizarContadores(totalOutros);

            if (totalOutros === 0) {
                lista.innerHTML = `
                    <div class="card-estado-vazio" id="cardEstadoVazio">
                        <div class="vazio-icone-wrapper">
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                <polyline points="9 12 11 14 15 10"></polyline>
                            </svg>
                        </div>
                        <h3 class="vazio-titulo">Nenhum outro dispositivo conectado</h3>
                        <p class="vazio-desc">
                            Sua conta HelpFull está conectada apenas neste dispositivo no momento. Nenhuma outra sessão ativa foi encontrada.
                        </p>
                    </div>
                `;
            }
        }

        function atualizarContadores(totalOutros) {
            const btnDesconectarOutros = document.getElementById('btnDesconectarOutros');
            const badgeOutros = document.getElementById('badgeContadorOutros');
            const textoContador = document.getElementById('textoContadorSessoes');

            if (btnDesconectarOutros) {
                btnDesconectarOutros.disabled = (totalOutros === 0);
            }
            if (badgeOutros) {
                badgeOutros.textContent = `(${totalOutros})`;
            }
            if (textoContador) {
                const totalGeral = totalOutros + 1;
                textoContador.textContent = `${totalGeral} ${totalGeral === 1 ? 'sessão ativa' : 'sessões ativas'}`;
            }
        }
    </script>

    <!-- CODIGO DA NOTIFICACAO INTELIGENTE (TOAST CENTRAL) -->
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
        function togglePainelAcessibilidade(e) {
            if (e && e.stopPropagation) e.stopPropagation();
            if (window.togglePainelAcessibilidade && window.togglePainelAcessibilidade !== togglePainelAcessibilidade) {
                window.togglePainelAcessibilidade(e);
            }
        }
        function abrirPainelAcessibilidade(e) {
            if (e && e.stopPropagation) e.stopPropagation();
            if (window.togglePainelAcessibilidade && window.togglePainelAcessibilidade !== togglePainelAcessibilidade) {
                window.togglePainelAcessibilidade(e);
            }
        }
        function abrirPainelAcessibilidadeMobile(e) {
            if (e && e.stopPropagation) e.stopPropagation();
            if (window.togglePainelAcessibilidade) {
                window.togglePainelAcessibilidade(e);
            }
        }
    </script>
    <script src="notificacoes.js?v=20260927-v4" onerror="if(!window.togglePainelAcessibilidade||window.togglePainelAcessibilidade===togglePainelAcessibilidade){var s=document.createElement('script');s.src='assets/notificacoes.js?v=20260927-v4';document.body.appendChild(s);}"></script>
    <script src="assets/transicao.js?v=20260926-v1"></script>
    <script src="assets/tutorial.js?v=20261001-v2"></script>
</body>

</html>
