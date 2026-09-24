<?php
require_once 'conexao.php';

$usuarioLogado = null;
if (isset($_SESSION['usuario_id'])) {
    $stmt = $pdo->prepare("SELECT id, nome, email, foto_perfil FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuarioLogado = $stmt->fetch();
    if (!$usuarioLogado) {
        session_destroy();
        $usuarioLogado = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: Comeco.php");
        exit;
    }
    if (isset($_POST['acao']) && $_POST['acao'] === 'criar_post') {
        $conteudo = $_POST['conteudo'] ?? '';
        $tagImpacto = $_POST['tag_impacto'] ?? 'Bom';
        $capaUrl = $_POST['capa_url'] ?? '';

        if (!empty($conteudo) || !empty($capaUrl)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO posts_comunidade (usuario_id, conteudo_post, tag_impacto, capa_url) VALUES (?, ?, ?, ?)");
                $stmt->execute([$_SESSION['usuario_id'], $conteudo, $tagImpacto, $capaUrl]);

                if (isset($_POST['ajax'])) {
                    echo json_encode(['success' => true]);
                    exit;
                }
            } catch (PDOException $e) {
                if (isset($_POST['ajax'])) {
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    exit;
                }
            }
        }
        header("Location: Comunidade.php");
        exit;
    }
    if (isset($_POST['acao']) && $_POST['acao'] === 'reagir') {
        $post_id = $_POST['post_id'];
        $tipo_reacao = $_POST['tipo_reacao'];

        try {
            $stmtDel = $pdo->prepare("DELETE FROM reacoes_comunidade WHERE post_id = ? AND usuario_id = ?");
            $stmtDel->execute([$post_id, $usuarioLogado['id']]);
            $stmt = $pdo->prepare("INSERT INTO reacoes_comunidade (post_id, usuario_id, tipo_reacao) VALUES (?, ?, ?)");
            $stmt->execute([$post_id, $usuarioLogado['id'], $tipo_reacao]);

            // Notificação Social
            $stmt = $pdo->prepare("SELECT usuario_id FROM posts_comunidade WHERE id = ?");
            $stmt->execute([$post_id]);
            $autor = $stmt->fetch();
            if ($autor && $autor['usuario_id'] != $usuarioLogado['id']) {
                $stmt = $pdo->prepare("INSERT INTO notificacoes_sociais (usuario_destino_id, usuario_origem_id, post_id, tipo, detalhe) VALUES (?, ?, ?, 'reacao', ?)");
                $stmt->execute([$autor['usuario_id'], $usuarioLogado['id'], $post_id, $tipo_reacao]);
            }

            header("Location: Comunidade.php");
            exit;
        } catch (PDOException $e) {
        }
    }

    if (isset($_POST['acao']) && $_POST['acao'] === 'curtir') {
        $post_id = $_POST['post_id'];
        $usuario_id = $usuarioLogado['id'];
        try {
            $stmt = $pdo->prepare("SELECT id FROM curtidas_comunidade WHERE post_id = ? AND usuario_id = ?");
            $stmt->execute([$post_id, $usuario_id]);
            $existente = $stmt->fetch();

            if ($existente) {
                $stmt = $pdo->prepare("DELETE FROM curtidas_comunidade WHERE post_id = ? AND usuario_id = ?");
                $stmt->execute([$post_id, $usuario_id]);
                $curtiu = false;
            } else {
                $stmt = $pdo->prepare("INSERT INTO curtidas_comunidade (post_id, usuario_id) VALUES (?, ?)");
                $stmt->execute([$post_id, $usuario_id]);
                $curtiu = true;

                $stmt = $pdo->prepare("SELECT usuario_id FROM posts_comunidade WHERE id = ?");
                $stmt->execute([$post_id]);
                $autor = $stmt->fetch();
                if ($autor && $autor['usuario_id'] != $usuario_id) {
                    $stmt = $pdo->prepare("INSERT INTO notificacoes_sociais (usuario_destino_id, usuario_origem_id, post_id, tipo) VALUES (?, ?, ?, 'curtida')");
                    $stmt->execute([$autor['usuario_id'], $usuario_id, $post_id]);
                }
            }

            if (isset($_POST['ajax'])) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM curtidas_comunidade WHERE post_id = ?");
                $stmt->execute([$post_id]);
                $total = $stmt->fetchColumn();
                echo json_encode(['success' => true, 'curtiu' => $curtiu, 'total' => $total]);
                exit;
            }
        } catch (PDOException $e) {
            if (isset($_POST['ajax'])) {
                echo json_encode(['success' => false]);
                exit;
            }
        }
        header("Location: Comunidade.php");
        exit;
    }

    if (isset($_POST['acao']) && $_POST['acao'] === 'deletar_post') {
        $post_id = $_POST['post_id'] ?? '';
        if (!empty($post_id)) {
            try {
                $stmt = $pdo->prepare("DELETE FROM curtidas_comunidade WHERE post_id = ?");
                $stmt->execute([$post_id]);
                $stmt = $pdo->prepare("DELETE FROM reacoes_comunidade WHERE post_id = ?");
                $stmt->execute([$post_id]);
                $stmt = $pdo->prepare("DELETE FROM notificacoes_sociais WHERE post_id = ?");
                $stmt->execute([$post_id]);

                $stmt = $pdo->prepare("DELETE FROM posts_comunidade WHERE id = ? AND usuario_id = ?");
                $stmt->execute([$post_id, $_SESSION['usuario_id']]);
            } catch (PDOException $e) {
            }
        }
        header("Location: Comunidade.php");
        exit;
    }

    if (isset($_POST['acao']) && $_POST['acao'] === 'editar_post') {
        $post_id = $_POST['post_id'] ?? '';
        $novo_conteudo = trim($_POST['conteudo'] ?? '');
        if (!empty($post_id) && !empty($novo_conteudo)) {
            try {
                $stmt = $pdo->prepare("UPDATE posts_comunidade SET conteudo_post = ? WHERE id = ? AND usuario_id = ?");
                $stmt->execute([$novo_conteudo, $post_id, $_SESSION['usuario_id']]);
            } catch (PDOException $e) {
            }
        }
        header("Location: Comunidade.php");
        exit;
    }
}

// Open Library API (gratuita, sem chave necessária)

try {
    $current_user_id = $usuarioLogado['id'] ?? 0;
    $query = "SELECT p.*, u.nome as autor_nome, u.foto_perfil as autor_foto,
              COALESCE(counts.total_curtidas, 0) as total_curtidas,
              CASE WHEN user_liked.id IS NOT NULL THEN 1 ELSE 0 END as usuario_curtiu
              FROM posts_comunidade p 
              JOIN usuarios u ON p.usuario_id = u.id 
              LEFT JOIN (SELECT post_id, COUNT(*) as total_curtidas FROM curtidas_comunidade GROUP BY post_id) counts ON counts.post_id = p.id
              LEFT JOIN (SELECT id, post_id FROM curtidas_comunidade WHERE usuario_id = ?) user_liked ON user_liked.post_id = p.id
              ORDER BY p.criado_em DESC
              LIMIT 50";
    $stmtPosts = $pdo->prepare($query);
    $stmtPosts->execute([$current_user_id]);
    $posts = $stmtPosts->fetchAll();
} catch (PDOException $e) {
    $posts = [];
}

$usuarioNovoSemPosts = false;
if (!empty($usuarioLogado['id'])) {
    try {
        $stmtCheckPosts = $pdo->prepare("SELECT COUNT(*) FROM posts_comunidade WHERE usuario_id = ?");
        $stmtCheckPosts->execute([$usuarioLogado['id']]);
        $usuarioNovoSemPosts = ((int)$stmtCheckPosts->fetchColumn() === 0);
    } catch (PDOException $e) {
        $usuarioNovoSemPosts = false;
    }
} else {
    $usuarioNovoSemPosts = true;
}

$coresTags = [
    'Pode dar gatilho' => 'alerta',
    'Me deixou ansioso' => 'alerta',
    'Achei pesado' => 'alerta',
    'Cura a alma' => 'seguranca',
    'É leve' => 'seguranca',
    'Me trouxe paz' => 'seguranca',
    'Relaxante' => 'seguranca',
    'Me fez refletir' => 'process',
    'História profunda' => 'process',
    'Me emocionou' => 'process',
    'Me deixou pensativo' => 'process'
];

$tmdbKey = '1482bdfd51f8e2ab38fe49ac49546d17';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HelpFull - Comunidade</title>
    <link rel="icon" type="image/png" href="assets/logoHelpFull.png">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap');

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
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            padding: 110px 20px 60px 20px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            box-sizing: border-box;
        }

        /* SISTEMA DE NOTIFICAÇÃO E NAVBAR (UNIVERSAL) */
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
            flex-direction: column;
            width: 620px;
            max-width: 95%;
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            padding: 12px 30px;
            border-radius: 50px;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            gap: 0;
            overflow: visible;
            position: relative;
        }

        .navbar-topo.scrolled {
            width: 850px;
            padding: 18px 35px;
            border-radius: 40px;
            gap: 15px;
            background: rgba(255, 255, 255, 0.65);
            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.12);
        }

        .nav-top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            gap: 20px;
        }

        .nav-logo {
            font-weight: 900;
            font-size: 1.05rem;
            letter-spacing: -0.5px;
            text-decoration: none;
            color: #1a1a1a;
            text-transform: uppercase;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            list-style: none;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: #1a1a1a;
            font-weight: 700;
            font-size: 0.9rem;
            transition: opacity 0.3s;
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

        /* ESPECÍFICOS COMUNIDADE */
        .nav-publish-row {
            width: 100%;
            overflow: hidden;
            max-height: 0;
            opacity: 0;
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .navbar-topo.scrolled .nav-publish-row {
            max-height: 80px;
            opacity: 1;
            margin-top: 5px;
        }

        .nav-input-container {
            background: rgba(255, 255, 255, 0.8);
            border-radius: 40px;
            padding: 8px 10px 8px 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid rgba(255, 255, 255, 1);
        }

        .nav-input-texto {
            flex: 1;
            min-width: 0;
            border: none;
            background: transparent;
            font-size: 1rem;
            font-weight: 600;
            color: #333;
            outline: none;
        }

        .nav-acoes {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .nav-pill-bom {
            background: #a4f49c;
            color: #2b6a2b;
            font-weight: 800;
            padding: 10px 22px;
            border-radius: 25px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            white-space: nowrap;
            transition: 0.3s;
        }

        .nav-pill-bom.ruim {
            background: #fce4e4;
            color: #cc4444;
        }

        .nav-btn-circ,
        .nav-btn-send {
            width: 42px;
            height: 42px;
            background: #e8e8e8;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            color: #555;
            flex-shrink: 0;
            transition: 0.3s;
        }

        .nav-pill-bom:hover,
        .nav-btn-circ:hover,
        .nav-btn-send:hover {
            transform: scale(1.05);
            background: #ddd;
        }

        .nav-pill-bom:hover {
            background: #94e48c;
        }

        #formCriarPost.hiding {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: translateY(-20px) scale(0.95);
        }

        #formCriarPost {
            transition: all 0.4s ease;
        }

        .caixa-agrupadora {
            position: relative;
            z-index: 1;
            border-radius: 40px;
            padding: 40px 30px;
            width: 100%;
            max-width: 1000px;
            margin: 0 auto 30px auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
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
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1), inset 0 2px 10px rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.9);
            pointer-events: none;
        }

        .card-postagem {
            background: #ffffff;
            border-radius: 40px;
            padding: 20px 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: none;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .input-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .input-texto-post {
            flex: 1;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            color: #333;
            outline: none;
            background: transparent;
        }

        .input-texto-post::placeholder {
            color: #999;
        }

        .acoes-criar-post {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pill-toggle-bom {
            background: #a4f49c;
            color: #2b6a2b;
            font-weight: 800;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
            border: none;
            cursor: pointer;
            transition: 0.2s;
        }

        .pill-toggle-bom.ruim {
            background: #fce4e4;
            color: #cc4444;
        }

        .btn-acao-circ {
            width: 35px;
            height: 35px;
            background: #dcdcdc;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            color: #555;
            font-weight: 900;
        }

        .post-card {
            background: #ffffff;
            border-radius: 30px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            border: 1px solid #e5e5e5;
            position: relative;
        }

        .post-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .post-avatar {
            width: 32px;
            height: 32px;
            background: #2b7a8c;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .post-nome {
            font-weight: 800;
            font-size: 1.1rem;
            color: #1a1a1a;
            text-transform: capitalize;
        }

        .post-data {
            font-size: 0.75rem;
            font-weight: 700;
            color: #888;
        }

        .post-texto {
            font-size: 1.25rem;
            font-weight: 700;
            color: #222;
            line-height: 1.4;
            margin-bottom: 20px;
        }

        .post-botoes-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: auto;
        }

        .pill-cinza {
            background: #dadada;
            color: #444;
            font-weight: 800;
            padding: 6px 15px;
            border-radius: 15px;
            font-size: 0.8rem;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .pill-cinza:hover {
            background: #cecece;
            transform: scale(1.05);
        }

        .pill-cinza.curtido {
            background: #ff4d4d !important;
            color: white !important;
            border-color: #ff4d4d !important;
            animation: likePop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            transform: scale(1.08);
        }

        @keyframes likePop {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.3);
            }

            100% {
                transform: scale(1.08);
            }
        }

        .pill-avaliacao {
            font-weight: 800;
            font-size: 0.85rem;
            padding: 6px 15px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .pill-avaliacao.bom {
            background: #e4fce0;
            color: #55a845;
            border: 1px solid #a4f49c;
        }

        .pill-avaliacao.ruim {
            background: #fce4e4;
            color: #cc4444;
            border: 1px solid #f49c9c;
        }

        /* Modais, Animações e Reações */
        @keyframes fadeInPopup {
            from {
                opacity: 0;
                transform: translateY(10px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes fadeOutPopup {
            from {
                opacity: 1;
                transform: translateY(0) scale(1);
            }

            to {
                opacity: 0;
                transform: translateY(10px) scale(0.95);
            }
        }

        .reacoes-expandidas {
            /* display: none; */
            visibility: hidden;
            opacity: 0;
            position: absolute;
            bottom: 80px;
            left: 30px;
            z-index: 20000;
            gap: 15px;
            padding: 12px 15px;
            align-items: center;
            transform: translateY(10px) scale(0.95);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            pointer-events: none;
        }

        .reacoes-expandidas.ativo {
            visibility: visible;
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }

        .reacoes-expandidas::before {
            content: '';
            position: absolute;
            inset: 0;
            z-index: -2;
            background: rgba(255, 255, 255, 0.80);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border-radius: 40px;
            pointer-events: none;
            transition: background 0.3s ease, border-color 0.3s ease;
        }

        .reacoes-expandidas.has-active::before {
            background: rgba(255, 255, 255, 0.98);
            border-color: rgba(255, 255, 255, 1);
        }

        .reacoes-expandidas.has-active .coluna-reacao:not(.active) .pill-titulo {
            filter: blur(3px);
            opacity: 0.5;
            transform: scale(0.95);
        }

        .coluna-reacao {
            position: relative;
            display: flex;
            flex-direction: column;
            z-index: 101;
        }

        .pill-titulo {
            font-size: 0.95rem;
            font-weight: 800;
            padding: 10px 18px;
            border-radius: 25px;
            color: #fff;
            cursor: pointer;
            display: flex;
            gap: 8px;
            border: none;
            transition: 0.3s;
            position: relative;
            z-index: 2;
        }

        .pill-titulo.alerta {
            background: #db8484;
        }

        .pill-titulo.seguranca {
            background: #9be381;
        }

        .pill-titulo.process {
            background: #81a8e3;
        }

        .itens-reacao {
            position: absolute;
            bottom: -12px;
            left: 50%;
            transform: translateX(-50%) translateY(10px);
            width: max-content;
            padding: 18px 18px 65px 18px;
            background: rgba(255, 255, 255, 0.80);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            border-radius: 35px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            opacity: 0;
            visibility: hidden;
            transition: 0.3s;
            pointer-events: none;
            z-index: 1;
        }

        .itens-reacao.aberto {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(0);
            pointer-events: auto;
        }

        .tag-reacao-btn {
            font-size: 0.85rem;
            font-weight: 700;
            padding: 8px 15px;
            border-radius: 20px;
            border: none;
            cursor: pointer;
            text-align: left;
            transition: transform 0.1s;
        }

        .tag-reacao-btn:hover {
            transform: scale(1.05);
        }

        .tag-reacao-btn.alerta-item {
            background: #f2e1e1;
            color: #3a2525;
        }

        .tag-reacao-btn.seguranca-item {
            background: #e6f2e1;
            color: #2a3a25;
        }

        .tag-reacao-btn.process-item {
            background: #e1e9f2;
            color: #252e3a;
        }

        .lista-comentarios {
            margin-top: 25px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .comentario-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .comentario-nome {
            font-size: 0.8rem;
            font-weight: 800;
            color: #333;
            text-transform: capitalize;
        }

        .tag-solida {
            font-size: 0.8rem;
            font-weight: 800;
            padding: 6px 15px;
            border-radius: 15px;
            color: #fff;
            width: fit-content;
        }

        .tag-solida.alerta {
            background: #db8484;
        }

        .tag-solida.seguranca {
            background: #9be381;
        }

        .tag-solida.process {
            background: #81a8e3;
        }

        /* Modal de Pesquisa Ajustado ao Figma */
        #modal-pesquisa {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.25);
            z-index: 10000;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 20px;
            justify-content: center;
            align-items: center;
        }

        #modal-content {
            background: rgba(220, 220, 220, 0.85);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-radius: 45px;
            padding: 50px 35px 40px 35px;
            width: 100%;
            max-width: 500px;
            display: flex;
            flex-direction: column;
            position: relative;
            box-shadow: 0 30px 100px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.5);
            animation: fadeInPopup 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            transition: all 0.4s ease;
        }

        .loader-mini {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-top: 2px solid #2b7a8c;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .loader {
            width: 45px;
            height: 45px;
            border: 5px solid rgba(0, 0, 0, 0.05);
            border-top-color: #607d8b;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* A grid só aparece se tiver conteúdo e estica o modal naturalmente */
        .grid-resultados {
            display: none;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            max-height: 55vh;
            overflow-y: auto !important;
            padding: 15px 5px;
            margin-top: 10px;
            scrollbar-width: none;
            -ms-overflow-style: none;
            mask-image: linear-gradient(to bottom, transparent, black 8%, black 92%, transparent);
            -webkit-mask-image: linear-gradient(to bottom, transparent, black 8%, black 92%, transparent);
        }

        .grid-resultados::-webkit-scrollbar {
            display: none;
        }

        .grid-resultados::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 10px;
        }

        .item-resultado {
            display: flex;
            flex-direction: column;
            gap: 0;
            position: relative;
            background: #fff;
            border-radius: 20px;
            padding: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .item-resultado:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .badget-tipo {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            color: #fff;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 0.6rem;
            font-weight: 900;
            text-transform: uppercase;
            z-index: 5;
            letter-spacing: 0.5px;
        }

        .item-capa-wrapper {
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            height: 120px;
            background: #f5f5f5;
        }

        .item-titulo {
            font-size: 0.7rem;
            font-weight: 800;
            color: #555;
            text-align: center;
            padding: 10px 5px 5px 5px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.2;
            min-height: 2.4rem;
        }

        #modal-compact-header {
            display: none;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 35px;
            padding: 5px;
            align-items: center;
            gap: 15px;
            width: 100%;
            margin-bottom: 20px;
        }

        .compact-pill {
            background: #607d8b;
            color: #fff;
            font-weight: 800;
            padding: 8px 25px;
            border-radius: 25px;
            font-size: 0.9rem;
            box-shadow: 0 4px 10px rgba(96, 125, 139, 0.3);
        }

        .compact-query {
            font-weight: 700;
            color: #666;
            font-size: 0.9rem;
            flex: 1;
        }


        .btn-filtro {
            padding: 8px 22px;
            border-radius: 25px;
            border: none;
            background: #e5e5e5;
            font-weight: 700;
            font-size: 0.9rem;
            padding: 10px 22px;
            border-radius: 25px;
            border: 1px solid rgba(0, 0, 0, 0.08);
            background: rgba(255, 255, 255, 0.6);
            color: #555;
            font-weight: 800;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            white-space: nowrap;
            backdrop-filter: blur(10px);
        }

        .btn-filtro:hover:not(.active) {
            background: rgba(255, 255, 255, 0.9);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .btn-filtro.active {
            background: #2b7a8c;
            color: #fff;
            box-shadow: 0 8px 20px rgba(43, 122, 140, 0.25);
            border-color: #2b7a8c;
            transform: scale(1.05);
        }

        .attachment-pill {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 18px;
            padding: 6px 12px 6px 6px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid rgba(0, 0, 0, 0.03);
            position: relative;
            min-width: 180px;
            max-width: 240px;
        }

        .attachment-img {
            width: 45px;
            height: 55px;
            border-radius: 12px;
            object-fit: cover;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .attachment-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
            flex: 1;
            min-width: 0;
        }

        .attachment-name {
            font-size: 0.8rem;
            font-weight: 800;
            color: #333;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .attachment-type {
            font-size: 0.65rem;
            font-weight: 700;
            color: #777;
            background: rgba(0, 0, 0, 0.05);
            padding: 2px 8px;
            border-radius: 10px;
            width: fit-content;
        }

        .attachment-remove {
            background: #ff4d4d;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 0.85rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            position: absolute;
            top: -8px;
            right: -8px;
            z-index: 5;
            transition: 0.2s;
        }

        .attachment-remove:hover {
            transform: scale(1.1);
            background: #ff1a1a;
        }

        .attachment-pill.removing {
            animation: slideOutPill 0.3s ease forwards;
            pointer-events: none;
        }

        @keyframes slideOutPill {
            to {
                opacity: 0;
                transform: scale(0.8) translateX(-20px);
            }
        }

        /* Ações do Post */
        .acoes-post-proprio {
            position: relative;
            margin-left: auto;
            display: flex;
            align-items: center;
            z-index: 20;
        }

        .btn-dots {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(0, 0, 0, 0.08);
            cursor: pointer;
            color: #1a1a1a;
            opacity: 0.75;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            transition: 0.2s;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            flex-shrink: 0;
        }

        .btn-dots:hover {
            background: #fff;
            opacity: 1;
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .menu-opcoes-post {
            visibility: hidden;
            opacity: 0;
            position: absolute;
            top: 38px;
            right: 0;
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-radius: 20px;
            padding: 8px;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.15) !important;
            border: 1px solid rgba(0, 0, 0, 0.08) !important;
            z-index: 30000 !important;
            flex-direction: column;
            align-items: stretch;
            gap: 2px;
            min-width: 140px;
            transform: translateY(-8px) scale(0.95);
            transition: all 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            pointer-events: none;
        }

        .menu-opcoes-post.ativo {
            visibility: visible;
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }

        .btn-menu-opcao {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border: none;
            background: transparent;
            font-family: inherit;
            font-weight: 700;
            font-size: 1rem;
            color: #333;
            cursor: pointer;
            transition: 0.2s;
            white-space: nowrap;
            border-radius: 15px;
        }

        .btn-menu-opcao:hover {
            background: rgba(0, 0, 0, 0.04);
            transform: translateX(3px);
        }

        .btn-menu-opcao.excluir {
            color: #ef7d7d;
        }

        .btn-menu-opcao.excluir svg {
            color: #ef7d7d;
        }

        .divisor-menu {
            width: 100%;
            height: 1px;
            background: rgba(0, 0, 0, 0.05);
            margin: 4px 0;
        }

        .post-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            position: relative;
        }

        /* Modal de Edição */
        #modal-edicao {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            z-index: 20000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-edicao-content {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 35px;
            padding: 35px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.6);
            animation: fadeInPopup 0.3s ease;
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

        .post-card-inner {
            display: flex;
            gap: 20px;
            justify-content: space-between;
        }

        .post-midias-container {
            display: flex;
            flex-direction: row;
            align-items: center;
            flex-shrink: 0;
            padding-left: 25px;
        }

        .post-midias-container > div {
            position: relative;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
            border-radius: 12px;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .post-midias-container > div:hover {
            transform: translateY(-6px) scale(1.06);
            z-index: 25 !important;
            box-shadow: 0 10px 22px rgba(0, 0, 0, 0.22);
        }

        .banner-boas-vindas-comunidade {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(43, 122, 140, 0.28);
            border-radius: 22px;
            padding: 16px 20px;
            margin: 15px 0 10px 0;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 8px 30px rgba(43, 122, 140, 0.08);
            position: relative;
            animation: fadeInDropdown 0.35s ease;
        }

        .bv-comunidade-icone {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: rgba(43, 122, 140, 0.12);
            color: #2b7a8c;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .bv-comunidade-info {
            flex: 1;
        }

        .bv-comunidade-info h4 {
            font-size: 0.95rem;
            font-weight: 800;
            color: #2b7a8c;
            margin-bottom: 3px;
        }

        .bv-comunidade-info p {
            font-size: 0.82rem;
            line-height: 1.45;
            color: #444;
            font-weight: 500;
        }

        .btn-fechar-bv {
            background: rgba(0, 0, 0, 0.05);
            border: none;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.15rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            margin-left: auto;
            flex-shrink: 0;
            transition: all 0.2s ease;
        }

        .btn-fechar-bv:hover {
            background: rgba(0, 0, 0, 0.1);
            color: #1a1a1a;
            transform: scale(1.08);
        }

        body.acessibilidade-escuro .banner-boas-vindas-comunidade {
            background: rgba(24, 24, 27, 0.9);
            border-color: rgba(43, 122, 140, 0.4);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
        }

        body.acessibilidade-escuro .bv-comunidade-info p {
            color: #d4d4d8;
        }

        body.acessibilidade-escuro .btn-fechar-bv {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .nav-container-global {
                width: 100%;
                left: 0;
                right: 0;
                transform: none;
                padding: 0 15px;
                box-sizing: border-box;
            }

            .navbar-topo,
            .navbar-topo.scrolled {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 auto;
                box-sizing: border-box;
            }

            .nav-publish-row {
                display: none !important;
            }

            #formCriarPost.hiding {
                opacity: 1 !important;
                visibility: visible !important;
                pointer-events: auto !important;
                transform: none !important;
            }

            .nav-logo {
                cursor: pointer;
            }

            .conteudo-site {
                padding-top: 95px;
                padding-left: 12px;
                padding-right: 12px;
            }

            .caixa-agrupadora {
                padding: 20px 12px;
                border-radius: 25px;
                width: 100%;
            }

            .caixa-agrupadora::before {
                border-radius: 25px;
            }

            .card-postagem {
                border-radius: 22px;
                padding: 15px;
            }

            .post-card {
                border-radius: 22px;
                padding: 18px 15px;
            }

            .btn-notificacao-separado { right: 10px; }
            .painel-notificacoes { right: 10px; width: calc(100vw - 40px); max-width: 320px; }
            .toast-notificacao { width: 92%; max-width: 380px; }
        }

        @media (max-width: 600px) {
            .input-row {
                flex-wrap: wrap;
                gap: 10px;
            }

            .input-texto-post {
                width: 100%;
                flex: 1 1 100%;
                font-size: 0.95rem;
                padding: 4px 0;
            }

            .acoes-criar-post {
                width: 100%;
                justify-content: flex-end;
                gap: 8px;
            }

            .post-card-inner {
                flex-direction: column-reverse;
                gap: 15px;
            }

            .post-midias-container {
                display: flex;
                flex-direction: row;
                align-items: center;
                justify-content: center;
                margin: 5px 0 10px 0;
                padding-left: 25px;
                align-self: center;
            }

            .post-botoes-row {
                flex-wrap: wrap;
                gap: 8px;
            }

            .grid-resultados { grid-template-columns: repeat(2, 1fr); }
            .reacoes-expandidas { left: 10px; right: 10px; width: auto; flex-wrap: wrap; justify-content: center; }
            .pill-titulo { font-size: 0.85rem; padding: 8px 14px; }
        }

        @media (max-width: 450px) {
            .grid-resultados { grid-template-columns: 1fr; }
            .post-texto { font-size: 1.05rem; }
        }
    </style>
    <link rel="stylesheet" href="assets/acessibilidade.css?v=20260916-v3">
</head>

<body>

    <img class="imagem-fundo" src="assets/HELPFULL.png" alt="Plano de Fundo HelpFull">

    <div class="nav-container-global">
        <nav class="navbar-topo" id="mainNavbar">
            <div class="nav-top-row">
                <a href="inicio.php" class="nav-logo" style="display: flex; align-items: center;">HELPFULL <span class="nav-seta-dropdown"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg></span></a>
                <ul class="nav-links">
                    <li><a href="Diario.php">Diário</a></li>
                    <li><a href="Comunidade.php" class="ativo">Comunidade</a></li>
                    <li><a href="ChatBOT.php">Helpy</a></li>
                    <li><a href="Atividades.php">Adicionais</a></li>
                </ul>
                <?php if ($usuarioLogado): ?>
                    <?php 
                        $fotoPerfilDb = !empty($usuarioLogado['foto_perfil']) ? $usuarioLogado['foto_perfil'] : ''; 
                        $temFotoNav = !empty($fotoPerfilDb) && (strlen($fotoPerfilDb) <= 150000);
                    ?>
                    <a href="Perfil.php" style="text-decoration: none;">
                        <div class="perfil-capsula" <?= $temFotoNav ? "style='background-image: url($fotoPerfilDb);'" : '' ?>>
                            <?php if (!$temFotoNav): ?>
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
            </div>
            <div class="nav-dropdown-mobile" id="navDropdownMobile">
                <a href="inicio.php">Início</a>
                <a href="Diario.php">Diário</a>
                <a href="Comunidade.php" class="ativo">Comunidade</a>
                <a href="ChatBOT.php">Helpy</a>
                <a href="Atividades.php">Adicionais</a>
                <a href="Perfil.php">Perfil</a>
                <a href="javascript:void(0)" class="btn-abrir-acessibilidade" onclick="abrirPainelAcessibilidadeMobile(event);">Configurações</a>
            </div>

            <div class="nav-publish-row">
                <form method="POST" id="formCriarPostNav">
                    <input type="hidden" name="acao" value="criar_post">
                    <input type="hidden" name="tag_impacto" id="inputTagImpactoNav" value="Bom">
                    <input type="hidden" name="capa_url" id="url_imagem_selecionada_nav">

                    <div class="nav-input-container">
                        <input type="text" name="conteudo" class="nav-input-texto" id="navInputPost"
                            placeholder="Comente sua experiência aqui...">
                        <div class="nav-acoes">
                            <button type="button" class="nav-pill-bom" id="btnToggleImpactoNav"
                                onclick="toggleImpactoNav()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2.5">
                                    <circle cx="12" cy="12" r="10" />
                                    <path d="M8 14s1.5 2 4 2 4-2 4-2" />
                                    <line x1="9" x2="9.01" y1="9" y2="9" />
                                    <line x1="15" x2="15.01" y1="9" y2="9" />
                                </svg>
                                <span id="textoToggleImpactoNav">Bom</span>
                            </button>
                            <button type="button" class="nav-btn-circ" onclick="abrirModal()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2.5">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                            </button>
                            <button type="submit" class="nav-btn-send">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5">
                                    <path d="m22 2-7 20-4-9-9-4Z" />
                                    <path d="M22 2 11 13" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </form>
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
            <div id="cardPlaceholder">
                <form method="POST" class="card-postagem" id="formCriarPost">
                    <input type="hidden" name="acao" value="criar_post">
                    <input type="hidden" name="tag_impacto" id="inputTagImpacto" value="Bom">
                    <input type="hidden" name="capa_url" id="url_imagem_selecionada">
                    <div class="input-row">
                        <input type="text" name="conteudo" class="input-texto-post" id="mainInputPost"
                            placeholder="Comente sua experiência aqui..." required>
                        <div class="acoes-criar-post">
                            <button type="button" class="pill-toggle-bom" id="btnToggleImpacto"
                                onclick="toggleImpacto()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2.5">
                                    <circle cx="12" cy="12" r="10" />
                                    <path d="M8 14s1.5 2 4 2 4-2 4-2" />
                                    <line x1="9" x2="9.01" y1="9" y2="9" />
                                    <line x1="15" x2="15.01" y1="9" y2="9" />
                                </svg>
                                <span id="textoToggleImpacto">Bom</span>
                            </button>
                            <button type="button" class="btn-acao-circ" onclick="abrirModal()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2.5">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                            </button>
                            <button type="submit" class="btn-acao-circ">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5">
                                    <path d="m22 2-7 20-4-9-9-4Z" />
                                    <path d="M22 2 11 13" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div id="preview-capa-container"
                        style="display: none; flex-wrap: wrap; gap: 10px; margin-top: 10px;"></div>
                </form>
            </div>

            <?php if ($usuarioNovoSemPosts): ?>
                <div class="banner-boas-vindas-comunidade" id="bannerBoasVindasComunidade">
                    <div class="bv-comunidade-icone">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </div>
                    <div class="bv-comunidade-info">
                        <h4>Bem-vindo(a) à Comunidade! ✦</h4>
                        <p>Este é o seu espaço seguro para compartilhar como você está se sentindo, desabafar e acolher os outros. Você pode criar sua primeira publicação no campo acima ou reagir às postagens com empatia!</p>
                    </div>
                    <button type="button" class="btn-fechar-bv" onclick="document.getElementById('bannerBoasVindasComunidade').style.display='none';" aria-label="Fechar dica">×</button>
                </div>
            <?php endif; ?>

            <?php foreach ($posts as $post):
                $dataCriacao = date('d/m/Y', strtotime($post['criado_em']));
                $stmtReacoes = $pdo->prepare("SELECT r.*, u.nome as autor_reacao FROM reacoes_comunidade r JOIN usuarios u ON r.usuario_id = u.id WHERE r.post_id = ? ORDER BY r.criado_em ASC");
                $stmtReacoes->execute([$post['id']]);
                $reacoes = $stmtReacoes->fetchAll();
                ?>
                <div class="post-card">
                    <div class="post-card-inner">
                        <div style="flex: 1; display: flex; flex-direction: column;">
                            <div class="post-header">
                                <?php 
                                    $autorFoto = $post['autor_foto'] ?? '';
                                    $temFotoAutor = !empty($autorFoto) && (strlen($autorFoto) <= 150000);
                                ?>
                                <div class="post-avatar" <?= $temFotoAutor ? "style='background-image: url(" . $autorFoto . "); background-size: cover; background-position: center;'" : '' ?>>
                                    <?php if (!$temFotoAutor): ?>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <div class="post-nome"><?= htmlspecialchars($post['autor_nome']) ?></div>
                                <div class="post-data"><?= $dataCriacao ?></div>

                                <?php if (isset($_SESSION['usuario_id']) && $post['usuario_id'] == $_SESSION['usuario_id']): ?>
                                    <div class="acoes-post-proprio">
                                        <button type="button" class="btn-dots" onclick="toggleMenuOpcoes(this)" aria-label="Opções do post">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="1"></circle>
                                                <circle cx="19" cy="12" r="1"></circle>
                                                <circle cx="5" cy="12" r="1"></circle>
                                            </svg>
                                        </button>
                                        <div class="menu-opcoes-post">
                                            <button type="button" class="btn-menu-opcao excluir" onclick="deletarPost('<?= $post['id'] ?>')">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2.5">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path
                                                        d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                                                    </path>
                                                </svg>
                                                Apagar
                                            </button>
                                            <div class="divisor-menu"></div>
                                            <button type="button" class="btn-menu-opcao"
                                                onclick="abrirEdicaoPost('<?= $post['id'] ?>', this)">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2.5">
                                                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                                </svg>
                                                Editar
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="post-texto"><?= htmlspecialchars($post['conteudo_post']) ?></div>

                            <form method="POST" class="reacoes-expandidas" id="menuReacoes_<?= $post['id'] ?>">
                                <input type="hidden" name="acao" value="reagir"><input type="hidden" name="post_id"
                                    value="<?= $post['id'] ?>">
                                <div class="coluna-reacao">
                                    <div class="pill-titulo alerta" onclick="toggleSubReacoes(this)">Alerta <svg width="14"
                                            height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="3">
                                            <path d="m6 9 6 6 6-6" />
                                        </svg></div>
                                    <div class="itens-reacao">
                                        <button type="submit" name="tipo_reacao" value="Pode dar gatilho"
                                            class="tag-reacao-btn alerta-item">Pode dar gatilho</button>
                                        <button type="submit" name="tipo_reacao" value="Me deixou ansioso"
                                            class="tag-reacao-btn alerta-item">Me deixou ansioso</button>
                                        <button type="submit" name="tipo_reacao" value="Achei pesado"
                                            class="tag-reacao-btn alerta-item">Achei pesado</button>
                                    </div>
                                </div>
                                <div class="coluna-reacao">
                                    <div class="pill-titulo seguranca" onclick="toggleSubReacoes(this)">Segurança <svg
                                            width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="3">
                                            <path d="m6 9 6 6 6-6" />
                                        </svg></div>
                                    <div class="itens-reacao">
                                        <button type="submit" name="tipo_reacao" value="Cura a alma"
                                            class="tag-reacao-btn seguranca-item">Cura a alma</button>
                                        <button type="submit" name="tipo_reacao" value="É leve"
                                            class="tag-reacao-btn seguranca-item">É leve</button>
                                        <button type="submit" name="tipo_reacao" value="Me trouxe paz"
                                            class="tag-reacao-btn seguranca-item">Me trouxe paz</button>
                                        <button type="submit" name="tipo_reacao" value="Relaxante"
                                            class="tag-reacao-btn seguranca-item">Relaxante</button>
                                    </div>
                                </div>
                                <div class="coluna-reacao">
                                    <div class="pill-titulo process" onclick="toggleSubReacoes(this)">Processamento <svg
                                            width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="3">
                                            <path d="m6 9 6 6 6-6" />
                                        </svg></div>
                                    <div class="itens-reacao">
                                        <button type="submit" name="tipo_reacao" value="Me fez refletir"
                                            class="tag-reacao-btn process-item">Me fez refletir</button>
                                        <button type="submit" name="tipo_reacao" value="História profunda"
                                            class="tag-reacao-btn process-item">História profunda</button>
                                        <button type="submit" name="tipo_reacao" value="Me emocionou"
                                            class="tag-reacao-btn process-item">Me emocionou</button>
                                        <button type="submit" name="tipo_reacao" value="Me deixou pensativo"
                                            class="tag-reacao-btn process-item">Me deixou pensativo</button>
                                    </div>
                                </div>
                            </form>

                            <div class="post-botoes-row">
                                <button type="button"
                                    class="pill-cinza <?php echo ($post['usuario_curtiu'] > 0) ? 'curtido' : ''; ?>"
                                    onclick="curtirPost(this, '<?php echo $post['id']; ?>')">
                                    ♡ <?php echo $post['total_curtidas'] ?? 0; ?>
                                </button>
                                <button class="pill-cinza" onclick="toggleMenu('menuReacoes_<?= $post['id'] ?>')">💬
                                    Reagir</button>
                                <?php if ($post['tag_impacto'] === 'Ruim'): ?>
                                    <div class="pill-avaliacao ruim">Ruim</div>
                                <?php else: ?>
                                    <div class="pill-avaliacao bom">Bom</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($post['capa_url'])):
                            $midias = json_decode($post['capa_url'], true);
                            if (!$midias) {
                                $midias = [['capa' => $post['capa_url']]];
                            } ?>
                            <div class="post-midias-container">
                                <?php foreach (array_slice($midias, 0, 3) as $index => $midia): ?>
                                    <div
                                        style="width: <?= count($midias) > 1 ? '70px' : '110px' ?>; flex-shrink: 0; transition: 0.3s; margin-left: <?= ($index > 0) ? '-25px' : '0' ?>; z-index: <?= 10 - $index ?>;">
                                        <img src="<?= htmlspecialchars($midia['capa']) ?>"
                                            style="width: 100%; height: <?= count($midias) > 1 ? '110px' : '160px' ?>; object-fit: cover; border-radius: 12px; border: 2px solid #fff; opacity:0; transition:opacity 0.3s;"
                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                            onload="this.style.opacity='1';">
                                        <div style="display:none; width:100%; height:<?= count($midias) > 1 ? '110px' : '160px' ?>; border-radius:12px; background:#e8e8e8; align-items:center; justify-content:center; font-size:0.65rem; font-weight:700; color:#999; text-align:center; padding:5px; border:2px solid #fff;"><?= htmlspecialchars(substr($midia['titulo'] ?? 'Mídia', 0, 30)) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (count($reacoes) > 0): ?>
                        <div class="lista-comentarios">
                            <?php foreach ($reacoes as $reacao):
                                $corBg = isset($coresTags[$reacao['tipo_reacao']]) ? $coresTags[$reacao['tipo_reacao']] : 'blue'; ?>
                                <div class="comentario-item">
                                    <span class="comentario-nome"><?= htmlspecialchars($reacao['autor_reacao']) ?></span>
                                    <div class="tag-solida <?= $corBg ?>"><?= htmlspecialchars($reacao['tipo_reacao']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- TOAST E MODAL -->
    <div id="notificacaoHelpFull" class="toast-notificacao">
        <div class="toast-barra" id="toastBarra"></div>
        <div class="toast-conteudo">
            <p id="textoNotificacao"></p>
        </div>
        <div class="toast-btn-container"><a id="linkNotificacao" href="#" class="toast-btn"></a></div>
    </div>

    <!-- MODAL DE PESQUISA (Ajustado ao Figma) -->
    <div id="modal-pesquisa">
        <div id="modal-content">
            <button onclick="fecharModal()"
                style="position: absolute; top: 15px; right: 15px; background: rgba(0,0,0,0.06); width: 30px; height: 30px; border-radius: 50%; border: none; font-size: 1.2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #333; z-index: 20; transition: 0.2s;"
                onmouseover="this.style.background='rgba(0,0,0,0.1)'"
                onmouseout="this.style.background='rgba(0,0,0,0.06)'">&times;</button>

            <!-- Header Padrão -->
            <div id="modal-header-container">
                <div style="text-align: center; margin-bottom: 15px;">
                    <h2 style="color: #333; font-weight: 900; margin-bottom: 5px; font-size: 1.5rem;">Mídias e
                        Atividades</h2>
                    <p style="color: #555; font-weight: 700; font-size: 1rem; margin-top: 0; opacity: 0.8;">O que você
                        consumiu?</p>
                </div>

                <div
                    style="display: flex; background: rgba(255, 255, 255, 0.4); backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px); padding: 7px; border-radius: 35px; margin-bottom: 25px; width: 100%; border: 1px solid rgba(255, 255, 255, 0.6); box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                    <input type="text" id="input-busca-api" placeholder="Escreva aqui o nome..."
                        style="flex: 1; padding: 10px 20px; border-radius: 25px; border: none; background: transparent; outline: none; font-weight: 700; color: #333; font-size: 0.95rem; width: 100%;">
                    <button onclick="buscarNaAPI()"
                        style="padding: 0 25px; border-radius: 30px; border: none; background: #607d8b; color: #fff; font-weight: 800; cursor: pointer; font-size: 0.9rem; box-shadow: 0 4px 10px rgba(96, 125, 139, 0.3); white-space: nowrap; transition: 0.2s; backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);"
                        onmouseover="this.style.transform='scale(1.03)'; this.style.background='#546e7a'"
                        onmouseout="this.style.transform='scale(1)'; this.style.background='#607d8b'">Buscar</button>
                </div>

                <div style="display: flex; gap: 15px; justify-content: center; width: 100%; margin: 0 auto;">
                    <button class="btn-filtro active" id="btn-filtro-tudo"
                        onclick="mudarFiltroPesquisa('tudo', this)">Tudo</button>
                    <button class="btn-filtro" onclick="mudarFiltroPesquisa('video', this)">Filmes/Séries</button>
                    <button class="btn-filtro" onclick="mudarFiltroPesquisa('book', this)">Livros</button>
                </div>
            </div>

            <!-- Header Compacto (Aparece após busca) -->
            <div id="modal-compact-header">
                <div class="compact-pill" id="compact-filter-name">Tudo</div>
                <div class="compact-query" id="compact-search-query">Pesquisa aqui...</div>
            </div>

            <!-- Grid de Resultados -->
            <div id="resultados-api" class="grid-resultados" style="display: none; justify-content: center;"></div>
        </div>
    </div>

    <!-- Modal de Edição de Post -->
    <div id="modal-edicao"
        style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px); z-index: 999999; align-items: center; justify-content: center; padding: 20px;">
        <div class="modal-edicao-content"
            style="background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(20px); border-radius: 35px; padding: 35px; width: 100%; max-width: 500px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); border: 1px solid rgba(255, 255, 255, 0.6); animation: fadeInPopup 0.3s ease;">
            <h2 style="font-weight: 900; margin-bottom: 20px; font-size: 1.3rem; color: #1a1a1a;">Editar Publicação</h2>
            <form method="POST">
                <input type="hidden" name="acao" value="editar_post">
                <input type="hidden" name="post_id" id="edit-post-id">
                <textarea name="conteudo" id="edit-post-texto"
                    style="width: 100%; min-height: 120px; border-radius: 20px; border: 1px solid #ddd; padding: 15px; font-family: inherit; font-weight: 600; outline: none; margin-bottom: 20px; resize: vertical;"></textarea>
                <div style="display: flex; gap: 15px; justify-content: flex-end;">
                    <button type="button" onclick="fecharEdicaoPost()"
                        style="padding: 10px 25px; border-radius: 25px; border: none; background: #eee; font-weight: 800; cursor: pointer; transition: 0.2s;"
                        onmouseover="this.style.background='#e0e0e0'"
                        onmouseout="this.style.background='#eee'">Cancelar</button>
                    <button type="submit"
                        style="padding: 10px 25px; border-radius: 25px; border: none; background: #2b7a8c; color: #fff; font-weight: 800; cursor: pointer; transition: 0.2s; box-shadow: 0 5px 15px rgba(43, 122, 140, 0.3);"
                        onmouseover="this.style.transform='scale(1.05)'"
                        onmouseout="this.style.transform='scale(1)'">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const isLoggedIn = <?= isset($_SESSION['usuario_id']) ? 'true' : 'false' ?>;
        function checkLogin(e) {
            if (!isLoggedIn) {
                if (e) e.preventDefault();
                alert("Para fazer uma postagem, você precisa estar logado!");
                window.location.href = "Comeco.php";
                return false;
            }
            return true;
        }

        document.getElementById('mainInputPost')?.addEventListener('click', checkLogin);

        const mainNavbar = document.getElementById('mainNavbar');
        const mainForm = document.getElementById('formCriarPost');
        const navForm = document.getElementById('formCriarPostNav');
        const mainInput = document.getElementById('mainInputPost');
        const navInput = document.getElementById('navInputPost');

        function checkNavbarScroll() {
            if (!mainNavbar || !mainForm) return;
            if (window.innerWidth <= 768) {
                mainNavbar.classList.remove('scrolled');
                if (mainForm) mainForm.classList.remove('hiding');
                return;
            }
            const rect = mainForm.getBoundingClientRect();
            if (rect.top < 20 || window.scrollY > 150) {
                mainNavbar.classList.add('scrolled');
                if (mainForm) mainForm.classList.add('hiding');
            } else {
                mainNavbar.classList.remove('scrolled');
                if (mainForm) mainForm.classList.remove('hiding');
            }
        }

        window.addEventListener('scroll', checkNavbarScroll);
        window.addEventListener('resize', checkNavbarScroll);

        if (mainInput && navInput) {
            mainInput.addEventListener('input', (e) => navInput.value = e.target.value);
            navInput.addEventListener('input', (e) => mainInput.value = e.target.value);
        }

        function abrirModal() {
            document.getElementById('modal-pesquisa').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function fecharModal() {
            document.getElementById('modal-pesquisa').style.display = 'none';
            document.body.style.overflow = '';

            const container = document.getElementById('resultados-api');
            container.style.display = 'none';
            container.innerHTML = '';

            document.getElementById('input-busca-api').value = '';

            // Resetar header
            document.getElementById('modal-header-container').style.display = 'block';
            document.getElementById('modal-compact-header').style.display = 'none';

            const btnTudo = document.getElementById('btn-filtro-tudo');
            mudarFiltroPesquisa('tudo', btnTudo, true);
        }

        let tipoBuscaAtual = 'tudo';

        function mudarFiltroPesquisa(tipo, botao, noRefresh = false) {
            if (!botao) return;
            tipoBuscaAtual = tipo;
            document.querySelectorAll('.btn-filtro').forEach(btn => btn.classList.remove('active'));
            botao.classList.add('active');

            if (!noRefresh && document.getElementById('input-busca-api').value.trim() !== '') {
                buscarNaAPI();
            }
        }

        document.getElementById('input-busca-api')?.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarNaAPI();
            }
        });

        async function buscarNaAPI() {
            const query = document.getElementById('input-busca-api').value.trim();
            if (!query) return;

            const container = document.getElementById('resultados-api');
            container.style.display = 'grid';
            container.innerHTML = '<div class="loader-container"><div class="loader"></div></div>';

            // Alternar para header compacto
            document.getElementById('modal-header-container').style.display = 'none';
            document.getElementById('modal-compact-header').style.display = 'flex';
            document.getElementById('compact-search-query').innerText = query;
            const activeFilterText = document.querySelector('.btn-filtro.active').innerText;
            document.getElementById('compact-filter-name').innerText = activeFilterText;

            try {
                let livros = [];
                let videos = [];
                const searchPromises = [];
                const tmdbKey = "<?php echo $tmdbKey; ?>";

                // API de Livros - Open Library (gratuita, sem chave)
                if (tipoBuscaAtual === 'book' || tipoBuscaAtual === 'tudo') {
                    searchPromises.push(
                        fetch(`https://openlibrary.org/search.json?q=${encodeURIComponent(query)}&fields=key,title,author_name,cover_i,isbn&limit=20`)
                            .then(res => res.json())
                            .then(data => {
                                if (data.docs) {
                                    livros = data.docs
                                        .filter(item => item.cover_i)
                                        .map(item => ({
                                            capa: `https://covers.openlibrary.org/b/id/${item.cover_i}-M.jpg`,
                                            titulo: item.title,
                                            tipo: 'Livro'
                                        }));
                                }
                            })
                            .catch(e => console.error('Erro Open Library:', e))
                    );
                }

                // API de Filmes/Séries
                if (tipoBuscaAtual === 'video' || tipoBuscaAtual === 'tudo') {
                    searchPromises.push(
                        fetch(`https://api.themoviedb.org/3/search/multi?api_key=${tmdbKey}&language=pt-BR&query=${encodeURIComponent(query)}&include_adult=false`)
                            .then(res => res.json())
                            .then(data => {
                                if (data.results) {
                                    videos = data.results
                                        .filter(item => (item.media_type === 'movie' || item.media_type === 'tv') && item.poster_path)
                                        .map(item => ({
                                            capa: 'https://image.tmdb.org/t/p/w500' + item.poster_path,
                                            titulo: item.title || item.name,
                                            tipo: item.media_type === 'movie' ? 'Filme' : 'Série'
                                        }));
                                }
                            })
                            .catch(e => console.error('Erro TMDB:', e))
                    );
                }

                // Aguarda ambas as APIs (Paralelo)
                await Promise.all(searchPromises);

                let itensEncontrados = [];
                if (tipoBuscaAtual === 'tudo') {
                    const max = Math.max(livros.length, videos.length);
                    for (let i = 0; i < max; i++) {
                        if (videos[i]) itensEncontrados.push(videos[i]);
                        if (livros[i]) itensEncontrados.push(livros[i]);
                    }
                } else {
                    itensEncontrados = [...videos, ...livros];
                }

                itensEncontrados = itensEncontrados.slice(0, 16);
                container.innerHTML = '';

                if (itensEncontrados.length > 0) {
                    itensEncontrados.forEach(item => {
                        const capaSegura = item.capa.replace(/^http:\/\//i, 'https://');

                        const div = document.createElement('div');
                        div.className = 'item-resultado';
                        div.style.cursor = 'pointer';
                        div.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                        div.onmouseover = () => {
                            div.style.transform = 'translateY(-3px)';
                            div.style.filter = 'brightness(1.1)';
                        };
                        div.onmouseout = () => {
                            div.style.transform = 'translateY(0)';
                            div.style.filter = 'brightness(1)';
                        };
                        div.innerHTML = `
                            <div class="badget-tipo">${item.tipo}</div>
                            <div class="item-capa-wrapper">
                                <img src="${capaSegura}" style="width: 100%; height: 100%; object-fit: cover; display: block; opacity: 0; transition: opacity 0.3s;" 
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" 
                                     onload="this.style.opacity='1';">
                                <div style="display:none; width:100%; height:100%; align-items:center; justify-content:center; flex-direction:column; gap:5px; padding:10px; text-align:center;">
                                    <svg width='28' height='28' viewBox='0 0 24 24' fill='none' stroke='#bbb' stroke-width='1.5'><rect x='3' y='3' width='18' height='18' rx='2'/><circle cx='8.5' cy='8.5' r='1.5'/><polyline points='21 15 16 10 5 21'/></svg>
                                    <span style="font-size:0.65rem;color:#bbb;font-weight:700;">${item.titulo.substring(0,25)}</span>
                                </div>
                            </div>
                            <div class="item-titulo">${item.titulo}</div>
                        `;
                        div.onclick = () => selecionarCapa({ capa: capaSegura, titulo: item.titulo, tipo: item.tipo });
                        container.appendChild(div);
                    });
                } else {
                    container.innerHTML = '<p style="grid-column: 1/-1; text-align: center; font-weight: 700; opacity: 0.5; padding: 20px;">Nenhum resultado encontrado.</p>';
                }
            } catch (error) {
                console.error('Erro geral na busca:', error);
                container.innerHTML = '<p style="grid-column: 1/-1; text-align: center; font-weight: 800; color: #cc4444; padding: 20px;">Erro ao buscar mídias.</p>';
            }
        }

        let selectedMedia = [];
        function selecionarCapa(item) {
            if (selectedMedia.length >= 3) {
                alert("Você pode adicionar no máximo 3 anexos por postagem.");
                return;
            }
            selectedMedia.push(item);
            renderPreviews();
            fecharModal();
        }

        function renderPreviews() {
            const previewMain = document.getElementById('preview-capa-container');
            const hiddenInput = document.getElementById('url_imagem_selecionada');
            const hiddenInputNav = document.getElementById('url_imagem_selecionada_nav');

            if (selectedMedia.length === 0) {
                previewMain.style.display = 'none';
                previewMain.innerHTML = '';
                hiddenInput.value = '';
                hiddenInputNav.value = '';
                return;
            }

            previewMain.style.display = 'flex';
            previewMain.style.flexWrap = 'wrap';
            previewMain.style.gap = '10px';
            previewMain.innerHTML = selectedMedia.map((item, index) => `
                <div class="attachment-pill">
                    <img src="${item.capa}" class="attachment-img">
                    <div class="attachment-info">
                        <div class="attachment-name">${item.titulo}</div>
                        <div class="attachment-type">${item.tipo}</div>
                    </div>
                    <button type="button" class="attachment-remove" onclick="removerMedia(${index})">&times;</button>
                </div>
            `).join('');

            const jsonStr = JSON.stringify(selectedMedia);
            hiddenInput.value = jsonStr;
            hiddenInputNav.value = jsonStr;
        }

        function removerMedia(index) {
            const pills = document.querySelectorAll('.attachment-pill');
            if (pills[index]) {
                pills[index].classList.add('removing');
                setTimeout(() => {
                    selectedMedia.splice(index, 1);
                    renderPreviews();
                }, 300);
            }
        }

        function removerCapa() {
            selectedMedia = [];
            renderPreviews();
        }

        function toggleImpactoNav() {
            const btn = document.getElementById('btnToggleImpactoNav');
            const texto = document.getElementById('textoToggleImpactoNav');
            const input = document.getElementById('inputTagImpactoNav');
            const mainInputTag = document.getElementById('inputTagImpacto');
            const mainBtn = document.getElementById('btnToggleImpacto');
            const mainTexto = document.getElementById('textoToggleImpacto');

            if (input.value === 'Bom') {
                input.value = 'Ruim'; texto.textContent = 'Ruim'; btn.classList.add('ruim');
                mainInputTag.value = 'Ruim'; mainTexto.textContent = 'Ruim'; mainBtn.classList.add('ruim');
            } else {
                input.value = 'Bom'; texto.textContent = 'Bom'; btn.classList.remove('ruim');
                mainInputTag.value = 'Bom'; mainTexto.textContent = 'Bom'; mainBtn.classList.remove('ruim');
            }
        }

        function toggleImpacto() {
            const btn = document.getElementById('btnToggleImpacto');
            const texto = document.getElementById('textoToggleImpacto');
            const input = document.getElementById('inputTagImpacto');
            if (input.value === 'Bom') {
                input.value = 'Ruim'; texto.textContent = 'Ruim'; btn.classList.add('ruim');
            } else {
                input.value = 'Bom'; texto.textContent = 'Bom'; btn.classList.remove('ruim');
            }
        }



        async function curtirPost(btn, postId) {
            // Resposta Otimista (Instantânea)
            let num = parseInt(btn.innerText.replace('♡ ', ''));
            const jaCurtiu = btn.classList.contains('curtido');

            if (jaCurtiu) {
                num--; btn.classList.remove('curtido');
            } else {
                num++; btn.classList.add('curtido');
            }
            btn.innerText = '♡ ' + num;

            const formData = new FormData();
            formData.append('acao', 'curtir');
            formData.append('post_id', postId);
            formData.append('ajax', '1');

            try {
                const response = await fetch('Comunidade.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    // Sincroniza com o valor real (caso mude enquanto processava)
                    btn.innerText = '♡ ' + result.total;
                } else { throw new Error(); }
            } catch (error) {
                // Reverte se falhar
                if (jaCurtiu) {
                    num++; btn.classList.add('curtido');
                } else {
                    num--; btn.classList.remove('curtido');
                }
                btn.innerText = '♡ ' + num;
                console.error('Erro ao curtir:', error);
            }
        }

        function toggleSubReacoes(elemento) {
            const colunaAtiva = elemento.closest('.coluna-reacao');
            const formPai = elemento.closest('.reacoes-expandidas');
            const itensClicado = elemento.nextElementSibling;
            const svgClicado = elemento.querySelector('svg');
            const estaAberto = itensClicado.classList.contains('aberto');

            formPai.querySelectorAll('.itens-reacao').forEach(itens => itens.classList.remove('aberto'));
            formPai.querySelectorAll('.pill-titulo svg').forEach(svg => svg.style.transform = 'rotate(0deg)');
            formPai.querySelectorAll('.coluna-reacao').forEach(col => col.classList.remove('active'));

            if (!estaAberto) {
                itensClicado.classList.add('aberto');
                svgClicado.style.transform = 'rotate(180deg)';
                colunaAtiva.classList.add('active');
                formPai.classList.add('has-active');
            } else {
                formPai.classList.remove('has-active');
            }
        }

        // === MOBILE DROPDOWN TOGGLE ===
        const navLogoCom = document.querySelector('.nav-logo');
        const navDropdownCom = document.getElementById('navDropdownMobile');
        if (navLogoCom && navDropdownCom) {
            navLogoCom.addEventListener('click', function (e) {
                if (window.innerWidth <= 768) {
                    e.preventDefault();
                    e.stopPropagation();
                    const aberto = navDropdownCom.classList.toggle('aberto');
                    navLogoCom.classList.toggle('aberto', aberto);
                }
            });
            document.addEventListener('click', function (e) {
                if (!e.target.closest('.navbar-topo') && !e.target.closest('.nav-dropdown-mobile')) {
                    navDropdownCom.classList.remove('aberto');
                    navLogoCom.classList.remove('aberto');
                }
            });
        }
        // Gerenciador de Postagens
        async function handlePostSubmission(event) {
            event.preventDefault();
            const form = event.target;
            const btn = form.querySelector('button[type="submit"]');

            // Bloqueia todos os botões de envio do site para evitar confusão
            const todosBotoes = document.querySelectorAll('button[type="submit"], .btn-acao-circ');
            todosBotoes.forEach(b => {
                b.disabled = true;
                b.style.pointerEvents = 'none';
                b.style.opacity = '0.6';
            });

            const originalContent = btn.innerHTML;
            // Centraliza o loader no botão circular
            btn.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;"><div class="loader-mini"></div></div>';

            const formData = new FormData(form);
            formData.append('ajax', '1');

            try {
                const response = await fetch('Comunidade.php', {
                    method: 'POST',
                    body: formData
                });

                // Sucesso: Recarrega a página (isso libera os botões naturalmente ao recarregar)
                window.scrollTo({ top: 0, behavior: 'smooth' });
                setTimeout(() => window.location.reload(), 300);
            } catch (error) {
                console.error('Erro ao publicar:', error);
                // Em caso de erro real, libera para tentar novamente
                todosBotoes.forEach(b => {
                    b.disabled = false;
                    b.style.pointerEvents = 'auto';
                    b.style.opacity = '1';
                });
                btn.innerHTML = originalContent;
                alert("Houve um erro ao publicar. Verifique sua conexão e tente novamente.");
            }
        }

        if (mainForm) mainForm.addEventListener('submit', handlePostSubmission);
        if (navForm) navForm.addEventListener('submit', handlePostSubmission);
        function deletarPost(id) {
            if (!id) return;
            if (confirm("Tem certeza que deseja apagar esta publicação?")) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'Comunidade.php';
                form.innerHTML = `
                    <input type="hidden" name="acao" value="deletar_post">
                    <input type="hidden" name="post_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function abrirEdicaoPost(id, btn) {
            const card = btn ? btn.closest('.post-card') : null;
            const postTextoEl = card ? card.querySelector('.post-texto') : null;
            const texto = postTextoEl ? postTextoEl.innerText.trim() : '';

            const inputId = document.getElementById('edit-post-id');
            const inputTexto = document.getElementById('edit-post-texto');
            const modal = document.getElementById('modal-edicao');

            if (inputId && inputTexto && modal) {
                inputId.value = id;
                inputTexto.value = texto;
                modal.style.display = 'flex';
                setTimeout(() => {
                    inputTexto.focus();
                }, 50);
            } else {
                alert('Erro ao abrir o editor. Elementos faltando no site.');
            }
        }

        function fecharEdicaoPost() {
            const modal = document.getElementById('modal-edicao');
            if (modal) modal.style.display = 'none';
        }

        // Handler para o formulário de edição
        const editForm = document.querySelector('#modal-edicao form');
        if (editForm) {
            editForm.addEventListener('submit', function (e) {
                const btn = this.querySelector('button[type="submit"]');
                if (btn) {
                    btn.disabled = true;
                    btn.innerText = "Salvando...";
                }
            });
        }

        function toggleMenu(id) {
            const menu = document.getElementById(id);
            if (!menu) return;
            const isOpen = menu.classList.contains('ativo');
            document.querySelectorAll('.menu-opcoes-post.ativo, .reacoes-expandidas.ativo').forEach(m => m.classList.remove('ativo'));
            if (!isOpen) menu.classList.add('ativo');
        }

        function toggleMenuOpcoes(btn) {
            const menu = btn.parentElement.querySelector('.menu-opcoes-post');
            if (!menu) return;

            const isOpen = menu.classList.contains('ativo');

            // Fecha outros
            document.querySelectorAll('.menu-opcoes-post.ativo, .reacoes-expandidas.ativo').forEach(m => {
                m.classList.remove('ativo');
            });

            if (!isOpen) {
                menu.classList.add('ativo');
            }
        }

        // Fecha todos os menus ao clicar fora deles
        document.addEventListener('click', function (e) {
            const isInsideMenu = e.target.closest('.acoes-post-proprio') ||
                                 e.target.closest('.pill-cinza') ||
                                 e.target.closest('.reacoes-expandidas') ||
                                 e.target.closest('.menu-opcoes-post');
            if (!isInsideMenu) {
                document.querySelectorAll('.menu-opcoes-post.ativo, .reacoes-expandidas.ativo').forEach(m => m.classList.remove('ativo'));
            }
        });
    </script>
    <script src="notificacoes.js?v=20260916-v2"></script>
</body>

</html>