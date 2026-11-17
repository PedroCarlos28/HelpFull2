<?php
/**
 * comunidade_moderacao_proc.php
 * Backend de moderação da comunidade HelpFull
 * Ações disponíveis (POST + JSON):
 *   - aplicar_punicao   : admin aplica aviso/banimento ao excluir post
 *   - liberar_usuario   : admin libera usuário banido ou remove avisos
 *   - aceitar_regras    : usuário aceita as regras da comunidade
 *   - verificar_regras  : verifica se usuário já aceitou regras
 *   - listar_banidos    : lista usuários com restrições (admin)
 *   - listar_mensagens  : lista mensagens prontas (admin)
 *   - status_usuario    : retorna nível de punição do usuário atual
 */

require_once 'conexao.php';
header('Content-Type: application/json; charset=utf-8');

$usuarioLogado = obterUsuarioLogado($pdo);
$isAdmin       = !empty($usuarioLogado['is_admin']);
$adminEmail    = 'contatohelpfull@gmail.com';

// Apenas admin pode usar rotas admin
function exigeAdmin($isAdmin) {
    if (!$isAdmin) {
        echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
        exit;
    }
}

// Expiração automática de avisos nível 1 e 2 (6 meses sem novo aviso)
function verificarExpiracao($pdo) {
    try {
        $pdo->prepare("
            DELETE FROM moderacao_comunidade
            WHERE nivel < 3
            AND expira_avisos_em IS NOT NULL
            AND expira_avisos_em <= NOW()
        ")->execute();
    } catch (Throwable $e) {}
}

verificarExpiracao($pdo);

$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

// ─── aceitar_regras ──────────────────────────────────────────────
if ($acao === 'aceitar_regras') {
    if (empty($usuarioLogado)) {
        echo json_encode(['success' => false]);
        exit;
    }
    try {
        $pdo->prepare("
            INSERT INTO comunidade_regras_aceitas (usuario_id)
            VALUES (?)
            ON CONFLICT (usuario_id) DO NOTHING
        ")->execute([$usuarioLogado['id']]);
        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ─── verificar_regras ────────────────────────────────────────────
if ($acao === 'verificar_regras') {
    if (empty($usuarioLogado)) {
        echo json_encode(['aceito' => false]);
        exit;
    }
    try {
        $stmt = $pdo->prepare("SELECT aceito_em FROM comunidade_regras_aceitas WHERE usuario_id = ?");
        $stmt->execute([$usuarioLogado['id']]);
        $row = $stmt->fetch();
        echo json_encode(['aceito' => (bool)$row]);
    } catch (Throwable $e) {
        echo json_encode(['aceito' => false]);
    }
    exit;
}

// ─── status_usuario ──────────────────────────────────────────────
if ($acao === 'status_usuario') {
    if (empty($usuarioLogado)) {
        echo json_encode(['nivel' => 0]);
        exit;
    }
    try {
        $stmt = $pdo->prepare("
            SELECT nivel, motivo, criado_em, expira_avisos_em
            FROM moderacao_comunidade
            WHERE usuario_id = ?
        ");
        $stmt->execute([$usuarioLogado['id']]);
        $row = $stmt->fetch();
        if ($row) {
            echo json_encode(['nivel' => (int)$row['nivel'], 'motivo' => $row['motivo']]);
        } else {
            echo json_encode(['nivel' => 0]);
        }
    } catch (Throwable $e) {
        echo json_encode(['nivel' => 0]);
    }
    exit;
}

// ─── status_usuario_alvo (admin consulta outro usuário) ──────────
if ($acao === 'status_usuario_alvo') {
    exigeAdmin($isAdmin);
    $uid = $_POST['usuario_id'] ?? $_GET['usuario_id'] ?? '';
    if (empty($uid)) {
        echo json_encode(['nivel' => 0]);
        exit;
    }
    try {
        $stmt = $pdo->prepare("SELECT nivel FROM moderacao_comunidade WHERE usuario_id = ?");
        $stmt->execute([$uid]);
        $row = $stmt->fetch();
        echo json_encode(['nivel' => $row ? (int)$row['nivel'] : 0]);
    } catch (Throwable $e) {
        echo json_encode(['nivel' => 0]);
    }
    exit;
}

// ─── listar_mensagens ────────────────────────────────────────────
if ($acao === 'listar_mensagens') {
    exigeAdmin($isAdmin);
    try {
        $stmt = $pdo->prepare("SELECT id, titulo, mensagem FROM moderacao_mensagens_prontas WHERE ativo = TRUE ORDER BY id");
        $stmt->execute();
        echo json_encode(['success' => true, 'mensagens' => $stmt->fetchAll()]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'mensagens' => []]);
    }
    exit;
}

// ─── listar_banidos ──────────────────────────────────────────────
if ($acao === 'listar_banidos') {
    exigeAdmin($isAdmin);
    try {
        $stmt = $pdo->prepare("
            SELECT mc.id, mc.usuario_id, mc.nivel, mc.motivo, mc.criado_em, mc.expira_avisos_em,
                   u.nome, u.email,
                   (SELECT COUNT(*) FROM moderacao_historico mh WHERE mh.usuario_id = mc.usuario_id) AS total_punicoes
            FROM moderacao_comunidade mc
            JOIN usuarios u ON u.id = mc.usuario_id
            ORDER BY mc.nivel DESC, mc.criado_em DESC
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll();
        echo json_encode(['success' => true, 'usuarios' => $rows]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'usuarios' => [], 'error' => $e->getMessage()]);
    }
    exit;
}

// ─── aplicar_punicao ─────────────────────────────────────────────
if ($acao === 'aplicar_punicao') {
    exigeAdmin($isAdmin);

    $usuario_alvo_id = $_POST['usuario_id']     ?? '';
    $post_id         = $_POST['post_id']         ?? null;
    $mensagem_id     = $_POST['mensagem_id']     ?? null;
    $motivo_custom   = trim($_POST['motivo_custom'] ?? '');

    if (empty($usuario_alvo_id)) {
        echo json_encode(['success' => false, 'error' => 'Usuário não identificado.']);
        exit;
    }

    // Busca mensagem pronta ou usa customizada
    $motivo = $motivo_custom;
    if (!empty($mensagem_id)) {
        try {
            $stmtMsg = $pdo->prepare("SELECT mensagem FROM moderacao_mensagens_prontas WHERE id = ? AND ativo = TRUE");
            $stmtMsg->execute([$mensagem_id]);
            $msg = $stmtMsg->fetch();
            if ($msg) {
                $motivo = $msg['mensagem'];
                if (!empty($motivo_custom)) {
                    $motivo .= "\n\nObservação do administrador: " . $motivo_custom;
                }
            }
        } catch (Throwable $e) {}
    }

    if (empty($motivo)) {
        echo json_encode(['success' => false, 'error' => 'Informe um motivo para a punição.']);
        exit;
    }

    try {
        // Verifica nível atual
        $stmtAtual = $pdo->prepare("SELECT nivel FROM moderacao_comunidade WHERE usuario_id = ?");
        $stmtAtual->execute([$usuario_alvo_id]);
        $atual = $stmtAtual->fetch();
        $nivel_anterior = $atual ? (int)$atual['nivel'] : 0;
        $nivel_novo = min(3, $nivel_anterior + 1);

        // Data de expiração: +6 meses para níveis 1 e 2
        $expira = null;
        if ($nivel_novo < 3) {
            $expira = date('Y-m-d H:i:sP', strtotime('+6 months'));
        }

        if ($nivel_anterior === 0) {
            // Primeiro aviso — inserir
            $pdo->prepare("
                INSERT INTO moderacao_comunidade (usuario_id, nivel, motivo, admin_id, post_id, expira_avisos_em)
                VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([$usuario_alvo_id, $nivel_novo, $motivo, $usuarioLogado['id'], $post_id ?: null, $expira]);
        } else {
            // Aviso existente — escalona
            $pdo->prepare("
                UPDATE moderacao_comunidade
                SET nivel = ?, motivo = ?, admin_id = ?, post_id = ?, criado_em = NOW(), expira_avisos_em = ?
                WHERE usuario_id = ?
            ")->execute([$nivel_novo, $motivo, $usuarioLogado['id'], $post_id ?: null, $expira, $usuario_alvo_id]);
        }

        // Registra no histórico
        $acaoHist = match($nivel_novo) {
            1 => 'aviso_1',
            2 => 'aviso_2',
            3 => 'banimento',
            default => 'aviso'
        };
        $pdo->prepare("
            INSERT INTO moderacao_historico (usuario_id, admin_id, acao, nivel_anterior, nivel_novo, motivo, post_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ")->execute([$usuario_alvo_id, $usuarioLogado['id'], $acaoHist, $nivel_anterior ?: null, $nivel_novo, $motivo, $post_id ?: null]);

        // Salva notificação de sistema para o usuário punido
        try {
            $titulos = [
                1 => '1º Aviso da Comunidade (1/3)',
                2 => '2º Aviso da Comunidade (2/3) — Atenção!',
                3 => 'Banimento da Comunidade (3/3)',
            ];

            $corpoMsg = $motivo;
            if ($nivel_novo === 2) {
                $corpoMsg .= "\n\n⚠️ ATENÇÃO: Mais um post removido por violação das diretrizes resultará no BANIMENTO da sua conta da comunidade (ficando proibido de postar e interagir). Este aviso expira em 6 meses se nenhuma nova infração ocorrer.";
            } elseif ($nivel_novo === 3) {
                $corpoMsg .= "\n\n🚫 Sua conta atingiu o limite de 3 infrações e foi BANIDA da comunidade. Você pode apenas visualizar as publicações, sem poder postar ou interagir.";
            }

            $pdo->prepare("
                INSERT INTO notificacoes_sistema (usuario_id, titulo, mensagem, tipo)
                VALUES (?, ?, ?, 'moderacao')
            ")->execute([$usuario_alvo_id, $titulos[$nivel_novo], $corpoMsg]);
        } catch (Throwable $e) {
            // Tabela pode não ter coluna tipo — tenta sem
            try {
                $pdo->prepare("
                    INSERT INTO notificacoes_sistema (usuario_id, titulo, mensagem)
                    VALUES (?, ?, ?)
                ")->execute([$usuario_alvo_id, $titulos[$nivel_novo] ?? 'Aviso da Comunidade', $corpoMsg ?? $motivo]);
            } catch (Throwable $e2) {}
        }

        // Se post_id foi fornecido, apaga a publicação e suas interações
        if (!empty($post_id)) {
            try {
                $pdo->prepare("DELETE FROM curtidas_comunidade WHERE post_id = ?")->execute([$post_id]);
                $pdo->prepare("DELETE FROM reacoes_comunidade WHERE post_id = ?")->execute([$post_id]);
                $pdo->prepare("DELETE FROM notificacoes_sociais WHERE post_id = ?")->execute([$post_id]);
                $pdo->prepare("DELETE FROM posts_comunidade WHERE id = ?")->execute([$post_id]);
            } catch (Throwable $eDel) {}
        }

        echo json_encode(['success' => true, 'nivel_novo' => $nivel_novo, 'post_id' => $post_id]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ─── liberar_usuario ─────────────────────────────────────────────
if ($acao === 'liberar_usuario') {
    exigeAdmin($isAdmin);

    $usuario_alvo_id = $_POST['usuario_id'] ?? '';
    if (empty($usuario_alvo_id)) {
        echo json_encode(['success' => false, 'error' => 'Usuário não identificado.']);
        exit;
    }

    try {
        // Nível atual para histórico
        $stmtAtual = $pdo->prepare("SELECT nivel FROM moderacao_comunidade WHERE usuario_id = ?");
        $stmtAtual->execute([$usuario_alvo_id]);
        $atual = $stmtAtual->fetch();
        $nivel_anterior = $atual ? (int)$atual['nivel'] : 0;

        // Remove punição
        $pdo->prepare("DELETE FROM moderacao_comunidade WHERE usuario_id = ?")->execute([$usuario_alvo_id]);

        // Histórico
        $pdo->prepare("
            INSERT INTO moderacao_historico (usuario_id, admin_id, acao, nivel_anterior, nivel_novo, motivo)
            VALUES (?, ?, 'liberacao', ?, 0, 'Liberação manual pelo administrador.')
        ")->execute([$usuario_alvo_id, $usuarioLogado['id'], $nivel_anterior]);

        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ─── listar_historico ────────────────────────────────────────────
if ($acao === 'listar_historico') {
    exigeAdmin($isAdmin);
    try {
        $stmt = $pdo->prepare("
            SELECT mh.id, mh.acao, mh.nivel_anterior, mh.nivel_novo, mh.motivo, mh.criado_em,
                   u.nome AS usuario_nome, u.email AS usuario_email,
                   a.nome AS admin_nome
            FROM moderacao_historico mh
            JOIN usuarios u ON u.id = mh.usuario_id
            LEFT JOIN usuarios a ON a.id = mh.admin_id
            ORDER BY mh.criado_em DESC
            LIMIT 50
        ");
        $stmt->execute();
        echo json_encode(['success' => true, 'historico' => $stmt->fetchAll()]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'historico' => []]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Ação desconhecida.']);
