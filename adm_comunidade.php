<?php
/**
 * adm_comunidade.php
 * Painel administrativo de moderação da Comunidade HelpFull
 * Acesso exclusivo para contatohelpfull@gmail.com
 */

require_once 'conexao.php';

$usuarioLogado = obterUsuarioLogado($pdo);
$isAdmin       = !empty($usuarioLogado['is_admin']);
$adminEmail    = 'contatohelpfull@gmail.com';

if (!$isAdmin || ($usuarioLogado['email'] ?? '') !== $adminEmail) {
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Montserrat', sans-serif;
            background: #dbe7eb;
            min-height: 100vh;
            color: #1a1a1a;
        }
        .topo-adm {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(43,122,140,0.15);
            padding: 16px 32px;
            display: flex;
            align-items: center;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .topo-adm a {
            color: #2b7a8c;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
        }
        .topo-adm h1 {
            font-size: 1.1rem;
            font-weight: 800;
            color: #0f172a;
        }
        .badge-adm {
            background: linear-gradient(135deg, #2b7a8c, #1d5f70);
            color: #fff;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
            letter-spacing: 0.5px;
        }
        .container-adm {
            max-width: 1100px;
            margin: 0 auto;
            padding: 32px 20px 60px;
        }
        .card-adm {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid rgba(0,0,0,0.07);
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            padding: 24px 28px;
            margin-bottom: 24px;
        }
        .card-adm h2 {
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-adm h2 .icone-h2 {
            width: 32px; height: 32px;
            background: rgba(43,122,140,0.1);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
        }
        /* Stats */
        .stats-row {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }
        .stat-box {
            flex: 1;
            min-width: 140px;
            background: rgba(255,255,255,0.9);
            border-radius: 16px;
            border: 1px solid rgba(0,0,0,0.07);
            padding: 18px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            text-align: center;
        }
        .stat-box .num { font-size: 2rem; font-weight: 800; color: #2b7a8c; }
        .stat-box .label { font-size: 0.75rem; color: #64748b; font-weight: 600; margin-top: 4px; }
        .stat-box.vermelho .num { color: #dc2626; }
        .stat-box.laranja .num { color: #ea580c; }
        .stat-box.amarelo .num { color: #d97706; }
        /* Tabela */
        .tabela-moderacao {
            width: 100%;
            border-collapse: collapse;
        }
        .tabela-moderacao th {
            text-align: left;
            padding: 10px 14px;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            font-weight: 700;
            border-bottom: 2px solid rgba(0,0,0,0.06);
        }
        .tabela-moderacao td {
            padding: 12px 14px;
            font-size: 0.85rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            vertical-align: middle;
        }
        .tabela-moderacao tr:last-child td { border-bottom: none; }
        .tabela-moderacao tr:hover td { background: rgba(43,122,140,0.03); }
        .badge-nivel {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .badge-nivel.n1 { background: rgba(234,179,8,0.15); color: #854d0e; }
        .badge-nivel.n2 { background: rgba(234,88,12,0.15); color: #9a3412; }
        .badge-nivel.n3 { background: rgba(220,38,38,0.15); color: #991b1b; }
        .btn-liberar {
            background: rgba(16,185,129,0.1);
            color: #065f46;
            border: 1px solid rgba(16,185,129,0.25);
            padding: 7px 16px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            transition: 0.2s;
        }
        .btn-liberar:hover {
            background: rgba(16,185,129,0.2);
            transform: translateY(-1px);
        }
        .btn-liberar:disabled { opacity: 0.5; cursor: default; transform: none; }
        .motivo-curto {
            max-width: 220px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #475569;
            font-size: 0.8rem;
        }
        .vazio-estado {
            text-align: center;
            padding: 40px 20px;
            color: #94a3b8;
            font-size: 0.88rem;
        }
        .vazio-estado svg { opacity: 0.4; margin-bottom: 10px; }
        /* Legenda de níveis */
        .legenda-niveis {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }
        .legenda-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
            color: #475569;
        }
        .legenda-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
        }
        .legenda-dot.n1 { background: #eab308; }
        .legenda-dot.n2 { background: #ea580c; }
        .legenda-dot.n3 { background: #dc2626; }
        /* Histórico */
        .historico-lista {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .hist-item {
            display: flex;
            gap: 14px;
            padding: 12px 16px;
            background: rgba(248,250,252,0.8);
            border-radius: 12px;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .hist-acao-badge {
            flex-shrink: 0;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.68rem;
            font-weight: 700;
            height: fit-content;
            margin-top: 2px;
        }
        .hist-acao-badge.aviso { background: rgba(234,179,8,0.15); color: #854d0e; }
        .hist-acao-badge.banimento { background: rgba(220,38,38,0.15); color: #991b1b; }
        .hist-acao-badge.liberacao { background: rgba(16,185,129,0.15); color: #065f46; }
        .hist-info { flex: 1; min-width: 0; }
        .hist-usuario { font-weight: 700; font-size: 0.85rem; color: #0f172a; }
        .hist-motivo { font-size: 0.78rem; color: #64748b; margin-top: 2px; }
        .hist-data { font-size: 0.72rem; color: #94a3b8; margin-top: 4px; }
        /* Filtro */
        .filtros-row {
            display: flex;
            gap: 10px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }
        .btn-filtro {
            padding: 8px 18px;
            border-radius: 20px;
            border: 1.5px solid rgba(0,0,0,0.1);
            background: rgba(255,255,255,0.8);
            font-family: inherit;
            font-size: 0.8rem;
            font-weight: 700;
            color: #64748b;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-filtro.ativo, .btn-filtro:hover {
            background: rgba(43,122,140,0.1);
            border-color: rgba(43,122,140,0.3);
            color: #2b7a8c;
        }
        /* Loading */
        .spinner {
            display: inline-block;
            width: 18px; height: 18px;
            border: 2px solid rgba(43,122,140,0.2);
            border-top-color: #2b7a8c;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            vertical-align: middle;
            margin-right: 6px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        @media (max-width: 600px) {
            .topo-adm { padding: 14px 16px; }
            .container-adm { padding: 20px 12px 60px; }
            .card-adm { padding: 18px 16px; }
            .tabela-moderacao th, .tabela-moderacao td { padding: 10px 8px; }
            .motivo-curto { max-width: 120px; }
        }
    </style>
</head>
<body>
    <div class="topo-adm">
        <a href="Comunidade.php">← Voltar</a>
        <h1>Painel de Moderação</h1>
        <span class="badge-adm">ADMIN</span>
    </div>

    <div class="container-adm">
        <!-- Stats -->
        <div class="stats-row" id="statsRow">
            <div class="stat-box"><div class="num" id="statTotal">—</div><div class="label">Total com infrações</div></div>
            <div class="stat-box amarelo"><div class="num" id="statN1">—</div><div class="label">Nível 1 — 1º Aviso</div></div>
            <div class="stat-box laranja"><div class="num" id="statN2">—</div><div class="label">Nível 2 — 2º Aviso (Alerta)</div></div>
            <div class="stat-box vermelho"><div class="num" id="statN3">—</div><div class="label">Nível 3 — Banidos</div></div>
        </div>

        <!-- Usuários com restrição -->
        <div class="card-adm">
            <h2>
                <span class="icone-h2">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2b7a8c" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </span>
                Usuários com Infrações / Banimento
            </h2>

            <div class="legenda-niveis">
                <div class="legenda-item"><div class="legenda-dot n1"></div> Nível 1: 1º Aviso (1º post apagado — expira em 6 meses sem novo aviso)</div>
                <div class="legenda-item"><div class="legenda-dot n2"></div> Nível 2: 2º Aviso (alerta de que mais 1 post causará banimento — expira em 6 meses)</div>
                <div class="legenda-item"><div class="legenda-dot n3"></div> Nível 3: Banido (3º post apagado — proibido de postar e interagir, somente visualiza)</div>
            </div>

            <div class="filtros-row">
                <button class="btn-filtro ativo" onclick="filtrarNivel(0, this)">Todos</button>
                <button class="btn-filtro" onclick="filtrarNivel(1, this)">1º Aviso</button>
                <button class="btn-filtro" onclick="filtrarNivel(2, this)">2º Aviso</button>
                <button class="btn-filtro" onclick="filtrarNivel(3, this)">Banidos</button>
            </div>

            <div id="tabelaWrapper">
                <div class="vazio-estado"><span class="spinner"></span> Carregando...</div>
            </div>
        </div>

        <!-- Histórico recente -->
        <div class="card-adm">
            <h2>
                <span class="icone-h2">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2b7a8c" stroke-width="2.5"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
                </span>
                Histórico Recente de Moderação
            </h2>
            <div id="historicoWrapper">
                <div class="vazio-estado"><span class="spinner"></span> Carregando...</div>
            </div>
        </div>
    </div>

    <script>
    let todosUsuarios = [];
    let filtroAtual = 0;

    async function carregarDados() {
        try {
            const r = await fetch('comunidade_moderacao_proc.php?acao=listar_banidos');
            const d = await r.json();
            if (!d.success) throw new Error(d.error);
            todosUsuarios = d.usuarios || [];
            atualizarStats();
            renderTabela(todosUsuarios);
        } catch (e) {
            document.getElementById('tabelaWrapper').innerHTML =
                `<div class="vazio-estado">Erro ao carregar dados: ${e.message}</div>`;
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
        btn.classList.add('ativo');
        const filtrados = nivel === 0 ? todosUsuarios : todosUsuarios.filter(u => u.nivel == nivel);
        renderTabela(filtrados);
    }

    function renderTabela(usuarios) {
        const wrapper = document.getElementById('tabelaWrapper');
        if (!usuarios.length) {
            wrapper.innerHTML = `<div class="vazio-estado">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg>
                <div>Nenhum usuário com restrição!</div>
            </div>`;
            return;
        }

        const labelNivel = { 1: '1º Aviso', 2: '2º Aviso (Alerta)', 3: 'Banido' };
        const classNivel = { 1: 'n1', 2: 'n2', 3: 'n3' };

        const rows = usuarios.map(u => {
            const criado = new Date(u.criado_em).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
            const expira = u.expira_avisos_em
                ? `Expira: ${new Date(u.expira_avisos_em).toLocaleDateString('pt-BR')}`
                : (u.nivel < 3 ? '' : 'Permanente');
            return `<tr>
                <td><strong>${escHtml(u.nome)}</strong><br><span style="font-size:0.75rem;color:#94a3b8">${escHtml(u.email)}</span></td>
                <td><span class="badge-nivel ${classNivel[u.nivel]}">${labelNivel[u.nivel]}</span></td>
                <td class="motivo-curto" title="${escHtml(u.motivo)}">${escHtml(u.motivo)}</td>
                <td style="font-size:0.78rem;color:#64748b">${criado}<br>${expira}</td>
                <td style="font-size:0.78rem;color:#64748b;text-align:center">${u.total_punicoes ?? 0}</td>
                <td><button class="btn-liberar" onclick="liberarUsuario('${u.usuario_id}', this)">Liberar</button></td>
            </tr>`;
        }).join('');

        wrapper.innerHTML = `<table class="tabela-moderacao">
            <thead><tr>
                <th>Usuário</th>
                <th>Nível</th>
                <th>Motivo</th>
                <th>Data / Expiração</th>
                <th style="text-align:center">Punicões</th>
                <th>Ação</th>
            </tr></thead>
            <tbody>${rows}</tbody>
        </table>`;
    }

    async function liberarUsuario(usuarioId, btn) {
        if (!confirm('Liberar este usuário e remover todas as restrições?')) return;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span>';
        try {
            const fd = new FormData();
            fd.append('acao', 'liberar_usuario');
            fd.append('usuario_id', usuarioId);
            const r = await fetch('comunidade_moderacao_proc.php', { method: 'POST', body: fd });
            const d = await r.json();
            if (d.success) {
                todosUsuarios = todosUsuarios.filter(u => u.usuario_id !== usuarioId);
                atualizarStats();
                const filtrados = filtroAtual === 0 ? todosUsuarios : todosUsuarios.filter(u => u.nivel == filtroAtual);
                renderTabela(filtrados);
                carregarHistorico();
            } else {
                alert('Erro ao liberar: ' + (d.error || 'Erro desconhecido'));
                btn.disabled = false;
                btn.textContent = 'Liberar';
            }
        } catch (e) {
            alert('Erro de rede. Tente novamente.');
            btn.disabled = false;
            btn.textContent = 'Liberar';
        }
    }

    async function carregarHistorico() {
        const wrapper = document.getElementById('historicoWrapper');
        try {
            const r = await fetch('comunidade_moderacao_proc.php?acao=listar_historico');
            const d = await r.json();
            if (!d.success || !d.historico.length) {
                wrapper.innerHTML = `<div class="vazio-estado">Nenhuma ação de moderação registrada ainda.</div>`;
                return;
            }
            const acaoLabel = {
                aviso_1: 'Aviso 1',
                aviso_2: 'Aviso 2',
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
                        <div class="hist-usuario">${escHtml(h.usuario_nome)} <span style="color:#94a3b8;font-weight:400">&lt;${escHtml(h.usuario_email)}&gt;</span></div>
                        <div class="hist-motivo">${escHtml((h.motivo || '').substring(0, 120))}${(h.motivo||'').length > 120 ? '...' : ''}</div>
                        <div class="hist-data">em ${data} · por ${escHtml(h.admin_nome || 'Sistema')}</div>
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
