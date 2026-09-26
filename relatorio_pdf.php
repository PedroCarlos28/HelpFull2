<?php
require_once 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: Comeco.php");
    exit;
}

$id = $_SESSION['usuario_id'];
$hoje = date('Y-m-d');
$mesAtualNumero = date('m');
$anoAtual = date('Y');
$mesesNomes = [
    '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março',
    '04' => 'Abril',   '05' => 'Maio',      '06' => 'Junho',
    '07' => 'Julho',   '08' => 'Agosto',    '09' => 'Setembro',
    '10' => 'Outubro', '11' => 'Novembro',  '12' => 'Dezembro'
];
$nomeMesAtual = $mesesNomes[$mesAtualNumero] ?? 'Mês Atual';

// 1. Dados do Usuário
$stmtU = $pdo->prepare("SELECT id, nome, email, foto_perfil, criado_em, videos_assistidos, ultimo_video_data FROM usuarios WHERE id = ?");
$stmtU->execute([$id]);
$usuario = $stmtU->fetch();
if (!$usuario) {
    header("Location: Comeco.php");
    exit;
}

// 2. Métricas do Diário (SEM SELECIONAR OU EXPOR TEXTO_DIARIO POR PRIVACIDADE)
$totalDiariosTotal = 0;
$totalDiariosMes = 0;
$diasComDiarioMes = 0;
$emocoesMes = ['Feliz' => 0, 'Calmo' => 0, 'Ansioso' => 0, 'Triste' => 0, 'Irritado' => 0, 'Amoroso' => 0];
$emocoesTotal = ['Feliz' => 0, 'Calmo' => 0, 'Ansioso' => 0, 'Triste' => 0, 'Irritado' => 0, 'Amoroso' => 0];
$diariosPorDiaDoMes = [];

try {
    // Total Geral de diários
    $stmtTotD = $pdo->prepare("SELECT COUNT(*) FROM diario WHERE usuario_id = ?");
    $stmtTotD->execute([$id]);
    $totalDiariosTotal = (int)$stmtTotD->fetchColumn();

    // Diários e Emoções do Mês
    $stmtMesD = $pdo->prepare("
        SELECT TO_CHAR(criado_em, 'YYYY-MM-DD') as dia_reg, emocao_selecionada, COUNT(*) as qtd
        FROM diario 
        WHERE usuario_id = ? 
          AND criado_em >= DATE_TRUNC('month', CURRENT_DATE)
        GROUP BY TO_CHAR(criado_em, 'YYYY-MM-DD'), emocao_selecionada
        ORDER BY dia_reg ASC
    ");
    $stmtMesD->execute([$id]);
    $rowsMes = $stmtMesD->fetchAll();

    $diasUnicos = [];
    foreach ($rowsMes as $r) {
        $totalDiariosMes += (int)$r['qtd'];
        $diasUnicos[$r['dia_reg']] = true;
        
        $emFormatada = ucfirst(strtolower(trim((string)$r['emocao_selecionada'])));
        if (!empty($emFormatada) && $emFormatada !== 'Null') {
            if (!isset($emocoesMes[$emFormatada])) $emocoesMes[$emFormatada] = 0;
            $emocoesMes[$emFormatada] += (int)$r['qtd'];
        }
    }
    $diasComDiarioMes = count($diasUnicos);

    // Emoções no histórico completo
    $stmtTotEm = $pdo->prepare("
        SELECT emocao_selecionada, COUNT(*) as qtd
        FROM diario
        WHERE usuario_id = ? AND emocao_selecionada IS NOT NULL AND emocao_selecionada != '' AND emocao_selecionada != 'Null'
        GROUP BY emocao_selecionada
    ");
    $stmtTotEm->execute([$id]);
    while ($r = $stmtTotEm->fetch()) {
        $emFormatada = ucfirst(strtolower(trim((string)$r['emocao_selecionada'])));
        if (isset($emocoesTotal[$emFormatada])) {
            $emocoesTotal[$emFormatada] = (int)$r['qtd'];
        } else {
            $emocoesTotal[$emFormatada] = (int)$r['qtd'];
        }
    }
} catch (Exception $e) {}

// 3. Métricas da Comunidade
$totalPosts = 0;
$postsBons = 0;
$postsRuins = 0;
$curtidasRecebidas = 0;
$curtidasDadas = 0;
$reacoesDadas = [];

try {
    // Total de posts
    $stmtPosts = $pdo->prepare("SELECT COUNT(*) FROM posts_comunidade WHERE usuario_id = ?");
    $stmtPosts->execute([$id]);
    $totalPosts = (int)$stmtPosts->fetchColumn();

    // Posts Bons (Positivos/Leves)
    $stmtBons = $pdo->prepare("SELECT COUNT(*) FROM posts_comunidade WHERE usuario_id = ? AND tag_impacto = 'Bom'");
    $stmtBons->execute([$id]);
    $postsBons = (int)$stmtBons->fetchColumn();

    // Posts Ruins (Desabafos/Atenção)
    $stmtRuins = $pdo->prepare("SELECT COUNT(*) FROM posts_comunidade WHERE usuario_id = ? AND tag_impacto = 'Ruim'");
    $stmtRuins->execute([$id]);
    $postsRuins = (int)$stmtRuins->fetchColumn();

    // Curtidas recebidas de outros
    $stmtCurtRec = $pdo->prepare("
        SELECT COUNT(*) FROM curtidas_comunidade c 
        JOIN posts_comunidade p ON c.post_id = p.id 
        WHERE p.usuario_id = ?
    ");
    $stmtCurtRec->execute([$id]);
    $curtidasRecebidas = (int)$stmtCurtRec->fetchColumn();

    // Curtidas dadas pelo usuário
    $stmtCurtDadas = $pdo->prepare("SELECT COUNT(*) FROM curtidas_comunidade WHERE usuario_id = ?");
    $stmtCurtDadas->execute([$id]);
    $curtidasDadas = (int)$stmtCurtDadas->fetchColumn();

    // Reações dadas pelo usuário na comunidade
    $stmtReacoes = $pdo->prepare("
        SELECT tipo_reacao, COUNT(*) as qtd 
        FROM reacoes_comunidade 
        WHERE usuario_id = ? 
        GROUP BY tipo_reacao 
        ORDER BY qtd DESC
    ");
    $stmtReacoes->execute([$id]);
    $reacoesDadas = $stmtReacoes->fetchAll();
} catch (Exception $e) {}

// 4. Métricas do ChatBOT Helpy
$totalConversasHelpy = 0;
$totalMensagensHelpy = 0;
$primeiraInteracaoHelpy = null;
$ultimaInteracaoHelpy = null;

try {
    $stmtChatSes = $pdo->prepare("SELECT COUNT(DISTINCT sessao_token) FROM historico_chat WHERE usuario_id = ?");
    $stmtChatSes->execute([(string)$id]);
    $totalConversasHelpy = (int)$stmtChatSes->fetchColumn();

    $stmtChatMsg = $pdo->prepare("SELECT COUNT(*), MIN(criado_em) as pri, MAX(criado_em) as ult FROM historico_chat WHERE usuario_id = ?");
    $stmtChatMsg->execute([(string)$id]);
    $chatStats = $stmtChatMsg->fetch();
    if ($chatStats) {
        $totalMensagensHelpy = (int)$chatStats['count'];
        $primeiraInteracaoHelpy = $chatStats['pri'] ? date('d/m/Y', strtotime($chatStats['pri'])) : null;
        $ultimaInteracaoHelpy = $chatStats['ult'] ? date('d/m/Y H:i', strtotime($chatStats['ult'])) : null;
    }
} catch (Exception $e) {}

// 5. Pesquisas de Livros, Filmes e Séries na Comunidade
$pesquisasComunidade = [];
try {
    $stmtPCom = $pdo->prepare("
        SELECT termo_ou_titulo, categoria, TO_CHAR(criado_em, 'DD/MM/YYYY HH24:MI') as data_formatada 
        FROM registro_atividades_usuario 
        WHERE usuario_id = ? AND tipo = 'pesquisa_comunidade'
        ORDER BY criado_em DESC LIMIT 25
    ");
    $stmtPCom->execute([$id]);
    $pesquisasComunidade = $stmtPCom->fetchAll();
} catch (Exception $e) {}

// 6. Pesquisas de Textos e Vídeos nas Atividades
$pesquisasTextos = [];
$pesquisasVideos = [];
try {
    $stmtPTxt = $pdo->prepare("
        SELECT termo_ou_titulo, categoria, TO_CHAR(criado_em, 'DD/MM/YYYY HH24:MI') as data_formatada 
        FROM registro_atividades_usuario 
        WHERE usuario_id = ? AND tipo = 'pesquisa_texto'
        ORDER BY criado_em DESC LIMIT 25
    ");
    $stmtPTxt->execute([$id]);
    $pesquisasTextos = $stmtPTxt->fetchAll();

    $stmtPVid = $pdo->prepare("
        SELECT termo_ou_titulo, categoria, TO_CHAR(criado_em, 'DD/MM/YYYY HH24:MI') as data_formatada 
        FROM registro_atividades_usuario 
        WHERE usuario_id = ? AND tipo = 'pesquisa_video'
        ORDER BY criado_em DESC LIMIT 25
    ");
    $stmtPVid->execute([$id]);
    $pesquisasVideos = $stmtPVid->fetchAll();
} catch (Exception $e) {}

// 7. Vídeos Selecionados da Plataforma Assistidos
$videosAssistidosLista = [];
$totalVideosRegistrados = (int)($usuario['videos_assistidos'] ?? 0);
$ultimoVideoData = $usuario['ultimo_video_data'] ? date('d/m/Y', strtotime($usuario['ultimo_video_data'])) : 'Nenhum';

try {
    $stmtVids = $pdo->prepare("
        SELECT termo_ou_titulo, categoria, TO_CHAR(criado_em, 'DD/MM/YYYY HH24:MI') as data_formatada 
        FROM registro_atividades_usuario 
        WHERE usuario_id = ? AND tipo = 'video_assistido'
        ORDER BY criado_em DESC LIMIT 25
    ");
    $stmtVids->execute([$id]);
    $videosAssistidosLista = $stmtVids->fetchAll();
} catch (Exception $e) {}

// Paleta visual de emoções
$coresEmocoes = [
    'Irritado' => ['cor' => '#eab8b8', 'dark' => '#b35353'],
    'Ansioso'  => ['cor' => '#ffcc99', 'dark' => '#c2772b'],
    'Feliz'    => ['cor' => '#fff1a0', 'dark' => '#b89d00'],
    'Calmo'    => ['cor' => '#b5ff99', 'dark' => '#459924'],
    'Triste'   => ['cor' => '#9bd3ff', 'dark' => '#2b78b8'],
    'Amoroso'  => ['cor' => '#ff99e6', 'dark' => '#b82b98']
];

$maxEmocaoMes = max(1, max($emocoesMes));
$totalRegistrosEmocoesMes = array_sum($emocoesMes);

$codigoRelatorio = strtoupper(substr(md5($id . $hoje . 'HelpFullReport2026'), 0, 10));
$dataHoraEmissao = date('d/m/Y \à\s H:i');
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Bem-Estar - <?= htmlspecialchars($usuario['nome']) ?> - HelpFull</title>
    <link rel="icon" type="image/png" href="assets/logoHelpFull.png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            background-color: #f0f4f7;
            color: #2c3e50;
            line-height: 1.5;
            padding: 30px 15px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Barra de Ações Superior (Oculta na Impressão/PDF) */
        .barra-acoes-topo {
            max-width: 900px;
            margin: 0 auto 25px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 16px 24px;
            border-radius: 18px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        }

        .btn-voltar {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: #2b7a8c;
            font-weight: 700;
            font-size: 0.92rem;
            padding: 10px 18px;
            border-radius: 12px;
            background: rgba(43, 122, 140, 0.08);
            transition: all 0.2s ease;
        }

        .btn-voltar:hover {
            background: rgba(43, 122, 140, 0.16);
            transform: translateX(-2px);
        }

        .btn-imprimir-pdf {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: none;
            background: linear-gradient(135deg, #2b7a8c 0%, #1e5a67 100%);
            color: #ffffff;
            font-weight: 800;
            font-size: 0.95rem;
            padding: 12px 24px;
            border-radius: 14px;
            cursor: pointer;
            box-shadow: 0 6px 18px rgba(43, 122, 140, 0.35);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-imprimir-pdf:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(43, 122, 140, 0.45);
        }

        /* Folha do Relatório A4 */
        .folha-relatorio {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 24px;
            padding: 40px 48px;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.07);
            position: relative;
        }

        /* Cabeçalho do Relatório */
        .relatorio-cabecalho {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 24px;
            border-bottom: 2px solid #eef2f5;
            margin-bottom: 26px;
        }

        .logo-marca {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-marca img {
            width: 44px;
            height: 44px;
            object-fit: contain;
        }

        .logo-marca h1 {
            font-size: 1.6rem;
            font-weight: 900;
            color: #1a2a3a;
            letter-spacing: -0.5px;
        }

        .logo-marca span {
            color: #2b7a8c;
        }

        .logo-slogan {
            font-size: 0.8rem;
            color: #718096;
            font-weight: 600;
        }

        .meta-documento {
            text-align: right;
        }

        .badge-confidencial {
            display: inline-block;
            background: #e6f6f9;
            color: #1e5a67;
            font-size: 0.72rem;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 6px;
        }

        .doc-info-item {
            font-size: 0.78rem;
            color: #718096;
            margin-bottom: 2px;
        }

        /* Banner de Privacidade Garantida */
        .aviso-privacidade {
            background: linear-gradient(135deg, #e8f8fa 0%, #f0fafd 100%);
            border: 1px solid #bce6ee;
            border-left: 5px solid #2b7a8c;
            border-radius: 12px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 28px;
        }

        .aviso-privacidade svg {
            flex-shrink: 0;
            color: #2b7a8c;
        }

        .aviso-privacidade p {
            font-size: 0.82rem;
            color: #214e59;
            font-weight: 600;
            line-height: 1.45;
        }

        /* Card de Identificação do Usuário */
        .perfil-resumo-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
            border: 1px solid #edf2f7;
            border-radius: 16px;
            padding: 18px 24px;
            margin-bottom: 30px;
        }

        .perfil-usuario-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .perfil-avatar {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            background: #2b7a8c;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            font-weight: 900;
            background-size: cover;
            background-position: center;
            border: 2px solid #ffffff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .perfil-nome {
            font-size: 1.15rem;
            font-weight: 800;
            color: #1a202c;
        }

        .perfil-email {
            font-size: 0.84rem;
            color: #718096;
            font-weight: 500;
        }

        .perfil-datas {
            text-align: right;
            font-size: 0.8rem;
            color: #4a5568;
            font-weight: 600;
        }

        /* Seções do Relatório */
        .secao-relatorio {
            margin-bottom: 32px;
            page-break-inside: avoid;
        }

        .secao-titulo-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid #edf2f7;
        }

        .secao-icone {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: #e6f6f9;
            color: #2b7a8c;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .secao-titulo {
            font-size: 1.05rem;
            font-weight: 800;
            color: #1a202c;
        }

        /* Grid de Cards Estatísticos */
        .grid-estatisticas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .card-stat {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 14px 18px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .stat-rotulo {
            font-size: 0.74rem;
            text-transform: uppercase;
            font-weight: 700;
            color: #718096;
            letter-spacing: 0.5px;
        }

        .stat-valor {
            font-size: 1.45rem;
            font-weight: 900;
            color: #1a202c;
        }

        .stat-subtexto {
            font-size: 0.72rem;
            color: #a0aec0;
            font-weight: 600;
        }

        /* Gráfico de Barras de Emoções */
        .grafico-emocoes-container {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px;
            margin-top: 14px;
        }

        .grafico-emocoes-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 12px;
            align-items: flex-end;
            height: 140px;
            padding: 10px 0 25px 0;
            position: relative;
            border-bottom: 1px solid #e2e8f0;
        }

        .coluna-emocao {
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100%;
            justify-content: flex-end;
            position: relative;
        }

        .barra-visual {
            width: 100%;
            max-width: 44px;
            border-radius: 8px 8px 3px 3px;
            transition: height 0.3s ease;
            position: relative;
            min-height: 8px;
        }

        .barra-topo-valor {
            position: absolute;
            top: -20px;
            font-size: 0.75rem;
            font-weight: 800;
            color: #4a5568;
        }

        .label-emocao {
            position: absolute;
            bottom: -24px;
            font-size: 0.72rem;
            font-weight: 700;
            color: #4a5568;
            white-space: nowrap;
        }

        /* Lista de Itens Registrados (Pesquisas, Vídeos, Tags) */
        .tabela-itens {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
            margin-top: 10px;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #edf2f7;
        }

        .tabela-itens th {
            background: #f8fafc;
            color: #4a5568;
            font-weight: 800;
            text-align: left;
            padding: 10px 16px;
            border-bottom: 1px solid #e2e8f0;
        }

        .tabela-itens td {
            padding: 10px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #2d3748;
            font-weight: 500;
        }

        .tabela-itens tr:last-child td {
            border-bottom: none;
        }

        .badge-tipo {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            background: #edf2f7;
            color: #4a5568;
        }

        .badge-tipo.bom { background: #e6fffa; color: #234e52; }
        .badge-tipo.ruim { background: #fff5f5; color: #742a2a; }
        .badge-tipo.livro { background: #fefcbf; color: #744210; }
        .badge-tipo.midia { background: #e9d8fd; color: #553c9a; }
        .badge-tipo.video { background: #fed7d7; color: #9b2c2c; }

        .lista-vazia-aviso {
            font-size: 0.82rem;
            color: #a0aec0;
            font-style: italic;
            padding: 12px 16px;
            text-align: center;
            background: #f8fafc;
            border-radius: 8px;
        }

        /* Rodapé Oficial */
        .relatorio-rodape {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #edf2f7;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            color: #718096;
        }

        .rodape-msg {
            font-weight: 600;
            color: #2b7a8c;
        }

        /* Regras Especiais de Impressão */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }

            .barra-acoes-topo {
                display: none !important;
            }

            .folha-relatorio {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                max-width: 100% !important;
            }

            .secao-relatorio {
                page-break-inside: avoid;
            }

            @page {
                size: A4 portrait;
                margin: 15mm 12mm 15mm 12mm;
            }
        }

        @media (max-width: 650px) {
            .folha-relatorio {
                padding: 24px 18px;
            }

            .relatorio-cabecalho {
                flex-direction: column;
                gap: 16px;
            }

            .meta-documento {
                text-align: left;
            }

            .perfil-resumo-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 14px;
            }

            .perfil-datas {
                text-align: left;
            }

            .grafico-emocoes-grid {
                gap: 6px;
            }

            .label-emocao {
                font-size: 0.65rem;
            }
        }
    </style>
</head>

<body>

    <!-- BARRA SUPERIOR DE AÇÕES (IMPRESSÃO / VOLTAR) -->
    <div class="barra-acoes-topo">
        <a href="Perfil.php" class="btn-voltar">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            Voltar ao Perfil
        </a>

        <button type="button" class="btn-imprimir-pdf" onclick="window.print()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            Imprimir / Salvar em PDF
        </button>
    </div>

    <!-- DOCUMENTO A4 DO RELATÓRIO -->
    <div class="folha-relatorio" id="documentoRelatorio">

        <!-- CABEÇALHO -->
        <header class="relatorio-cabecalho">
            <div class="logo-marca">
                <img src="assets/logoHelpFull.png" alt="HelpFull" onerror="this.style.display='none'">
                <div>
                    <h1>HELPFULL<span>✦</span></h1>
                    <div class="logo-slogan">Relatório Pessoal de Bem-Estar e Saúde Mental</div>
                </div>
            </div>

            <div class="meta-documento">
                <span class="badge-confidencial">Documento Confidencial</span>
                <div class="doc-info-item"><strong>Cód:</strong> <?= $codigoRelatorio ?></div>
                <div class="doc-info-item"><strong>Emissão:</strong> <?= $dataHoraEmissao ?></div>
                <div class="doc-info-item"><strong>Período:</strong> <?= $nomeMesAtual ?> / <?= $anoAtual ?></div>
            </div>
        </header>

        <!-- AVISO DE PRIVACIDADE E SIGILO -->
        <div class="aviso-privacidade">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
            <p>
                <strong>Garantia Estrita de Privacidade:</strong> Este relatório consolida apenas métricas quantitativas, frequência de uso e reações emocionais. Por respeito irrestrito à sua confidencialidade pessoal, <strong>nenhum texto, palavra ou conteúdo escrito do seu Diário foi incluído ou acessado</strong> neste documento.
            </p>
        </div>

        <!-- RESUMO DO USUÁRIO -->
        <div class="perfil-resumo-card">
            <div class="perfil-usuario-info">
                <?php 
                    $foto = !empty($usuario['foto_perfil']) && strlen($usuario['foto_perfil']) < 200000 ? $usuario['foto_perfil'] : '';
                    $iniciais = strtoupper(substr($usuario['nome'], 0, 2));
                ?>
                <div class="perfil-avatar" <?= $foto ? "style='background-image: url($foto);'" : '' ?>>
                    <?= !$foto ? $iniciais : '' ?>
                </div>
                <div>
                    <div class="perfil-nome"><?= htmlspecialchars($usuario['nome']) ?></div>
                    <div class="perfil-email"><?= htmlspecialchars($usuario['email']) ?></div>
                </div>
            </div>
            <div class="perfil-datas">
                <div>Membro desde: <?= $usuario['criado_em'] ? date('d/m/Y', strtotime($usuario['criado_em'])) : '2026' ?></div>
                <div>Status: Usuário Ativo</div>
            </div>
        </div>

        <!-- 1. JORNADA DO DIÁRIO & EMOÇÕES -->
        <section class="secao-relatorio">
            <div class="secao-titulo-row">
                <div class="secao-icone">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                    </svg>
                </div>
                <h2 class="secao-titulo">1. Diário Pessoal & Registro Emocional</h2>
            </div>

            <div class="grid-estatisticas">
                <div class="card-stat">
                    <span class="stat-rotulo">Total de Registros</span>
                    <span class="stat-valor"><?= $totalDiariosTotal ?></span>
                    <span class="stat-subtexto">Histórico vitalício</span>
                </div>
                <div class="card-stat">
                    <span class="stat-rotulo">Registros neste Mês</span>
                    <span class="stat-valor"><?= $totalDiariosMes ?></span>
                    <span class="stat-subtexto"><?= $nomeMesAtual ?> de <?= $anoAtual ?></span>
                </div>
                <div class="card-stat">
                    <span class="stat-rotulo">Dias com Diário</span>
                    <span class="stat-valor"><?= $diasComDiarioMes ?></span>
                    <span class="stat-subtexto">Consistência mensal</span>
                </div>
                <div class="card-stat">
                    <span class="stat-rotulo">Total de Emoções</span>
                    <span class="stat-valor"><?= $totalRegistrosEmocoesMes ?></span>
                    <span class="stat-subtexto">Sentimentos classificados</span>
                </div>
            </div>

            <!-- Gráfico Visual de Emoções do Mês -->
            <div class="grafico-emocoes-container">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
                    <strong style="font-size:0.88rem;color:#1a202c;">Distribuição das Emoções no Mês (<?= $nomeMesAtual ?>)</strong>
                    <span style="font-size:0.75rem;color:#718096;">Frequência por sentimento</span>
                </div>

                <div class="grafico-emocoes-grid">
                    <?php foreach ($emocoesMes as $emNome => $qtd): 
                        $corInfo = $coresEmocoes[$emNome] ?? ['cor' => '#cbd5e0', 'dark' => '#4a5568'];
                        $alturaPct = $maxEmocaoMes > 0 ? max(8, round(($qtd / $maxEmocaoMes) * 100)) : 8;
                        if ($qtd === 0) $alturaPct = 6;
                    ?>
                        <div class="coluna-emocao">
                            <span class="barra-topo-valor"><?= $qtd ?></span>
                            <div class="barra-visual" style="height: <?= $alturaPct ?>%; background: <?= $corInfo['cor'] ?>; border: 1px solid <?= $corInfo['dark'] ?>;"></div>
                            <span class="label-emocao"><?= $emNome ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- 2. USO DA COMUNIDADE & PARTICIPAÇÃO SOCIAL -->
        <section class="secao-relatorio">
            <div class="secao-titulo-row">
                <div class="secao-icone">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <h2 class="secao-titulo">2. Interação na Comunidade</h2>
            </div>

            <div class="grid-estatisticas">
                <div class="card-stat">
                    <span class="stat-rotulo">Total de Publicações</span>
                    <span class="stat-valor"><?= $totalPosts ?></span>
                    <span class="stat-subtexto">Posts compartilhados</span>
                </div>
                <div class="card-stat" style="border-left: 4px solid #38a169;">
                    <span class="stat-rotulo">Publicações Boas</span>
                    <span class="stat-valor" style="color: #276749;"><?= $postsBons ?></span>
                    <span class="stat-subtexto">Mensagens positivas</span>
                </div>
                <div class="card-stat" style="border-left: 4px solid #e53e3e;">
                    <span class="stat-rotulo">Publicações Ruins</span>
                    <span class="stat-valor" style="color: #9b2c2c;"><?= $postsRuins ?></span>
                    <span class="stat-subtexto">Desabafos compartilhados</span>
                </div>
                <div class="card-stat">
                    <span class="stat-rotulo">Curtidas Recebidas</span>
                    <span class="stat-valor"><?= $curtidasRecebidas ?></span>
                    <span class="stat-subtexto">Apoio recebido</span>
                </div>
            </div>

            <?php if (!empty($reacoesDadas)): ?>
                <div style="margin-top: 14px;">
                    <strong style="font-size: 0.82rem; color: #4a5568; display: block; margin-bottom: 8px;">Reações enviadas para outros membros:</strong>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <?php foreach ($reacoesDadas as $re): ?>
                            <span style="background: #edf2f7; border: 1px solid #e2e8f0; padding: 4px 12px; border-radius: 20px; font-size: 0.78rem; font-weight: 700; color: #2d3748;">
                                <?= htmlspecialchars($re['tipo_reacao']) ?>: <strong><?= $re['qtd'] ?></strong>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <!-- 3. INTERAÇÕES COM O CHATBOT (HELPY) -->
        <section class="secao-relatorio">
            <div class="secao-titulo-row">
                <div class="secao-icone">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                </div>
                <h2 class="secao-titulo">3. Apoio Emocional com o Helpy (ChatBOT)</h2>
            </div>

            <div class="grid-estatisticas">
                <div class="card-stat">
                    <span class="stat-rotulo">Conversas Iniciadas</span>
                    <span class="stat-valor"><?= $totalConversasHelpy ?></span>
                    <span class="stat-subtexto">Sessões de escuta e acolhimento</span>
                </div>
                <div class="card-stat">
                    <span class="stat-rotulo">Mensagens Trocadas</span>
                    <span class="stat-valor"><?= $totalMensagensHelpy ?></span>
                    <span class="stat-subtexto">Interações registradas</span>
                </div>
                <div class="card-stat">
                    <span class="stat-rotulo">Último Desabafo</span>
                    <span class="stat-valor" style="font-size: 1.05rem;"><?= $ultimaInteracaoHelpy ?: 'Sem registros' ?></span>
                    <span class="stat-subtexto">Momento do contato</span>
                </div>
            </div>
        </section>

        <!-- 4. PESQUISAS DE LIVROS, FILMES E SÉRIES NA COMUNIDADE -->
        <section class="secao-relatorio">
            <div class="secao-titulo-row">
                <div class="secao-icone">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </div>
                <h2 class="secao-titulo">4. Pesquisas de Livros, Filmes e Séries na Comunidade</h2>
            </div>

            <div id="containerPesquisasComunidade">
                <?php if (!empty($pesquisasComunidade)): ?>
                    <table class="tabela-itens">
                        <thead>
                            <tr>
                                <th>Termo Pesquisado</th>
                                <th>Categoria</th>
                                <th>Data / Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pesquisasComunidade as $p): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($p['termo_ou_titulo']) ?></strong></td>
                                    <td><span class="badge-tipo <?= strpos(strtolower($p['categoria']), 'livro') !== false ? 'livro' : 'midia' ?>"><?= htmlspecialchars($p['categoria']) ?></span></td>
                                    <td><?= $p['data_formatada'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="lista-vazia-aviso" id="avisoVazioPesquisasComunidade">
                        Nenhuma pesquisa de livros ou mídias salva no banco até o momento. As próximas buscas feitas na Comunidade serão listadas aqui automaticamente.
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- 5. PESQUISAS NAS ATIVIDADES (TEXTOS E VÍDEOS) -->
        <section class="secao-relatorio">
            <div class="secao-titulo-row">
                <div class="secao-icone">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                    </svg>
                </div>
                <h2 class="secao-titulo">5. Pesquisas nas Atividades (Textos & Vídeos)</h2>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <!-- Coluna Textos -->
                <div>
                    <strong style="font-size: 0.84rem; color: #2b7a8c; display: block; margin-bottom: 8px;">📄 Artigos e Textos Pesquisados:</strong>
                    <div id="containerPesquisasTextos">
                        <?php if (!empty($pesquisasTextos)): ?>
                            <table class="tabela-itens">
                                <thead>
                                    <tr>
                                        <th>Termo</th>
                                        <th>Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pesquisasTextos as $pt): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($pt['termo_ou_titulo']) ?></strong></td>
                                            <td><?= $pt['data_formatada'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="lista-vazia-aviso" id="avisoVazioTextos">Nenhuma busca de textos registrada ainda.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Coluna Vídeos -->
                <div>
                    <strong style="font-size: 0.84rem; color: #2b7a8c; display: block; margin-bottom: 8px;">🎬 Vídeos e Exercícios Pesquisados:</strong>
                    <div id="containerPesquisasVideos">
                        <?php if (!empty($pesquisasVideos)): ?>
                            <table class="tabela-itens">
                                <thead>
                                    <tr>
                                        <th>Termo</th>
                                        <th>Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pesquisasVideos as $pv): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($pv['termo_ou_titulo']) ?></strong></td>
                                            <td><?= $pv['data_formatada'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="lista-vazia-aviso" id="avisoVazioVideos">Nenhuma busca de vídeos registrada ainda.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- 6. VÍDEOS SELECIONADOS DA PLATAFORMA ASSISTIDOS -->
        <section class="secao-relatorio">
            <div class="secao-titulo-row">
                <div class="secao-icone">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polygon points="10 8 16 12 10 16 10 8"></polygon>
                    </svg>
                </div>
                <h2 class="secao-titulo">6. Conteúdos em Vídeo Assistidos na Plataforma</h2>
            </div>

            <div class="grid-estatisticas" style="margin-bottom: 14px;">
                <div class="card-stat">
                    <span class="stat-rotulo">Total Assistidos</span>
                    <span class="stat-valor"><?= $totalVideosRegistrados ?></span>
                    <span class="stat-subtexto">Contador de visualizações</span>
                </div>
                <div class="card-stat">
                    <span class="stat-rotulo">Último Vídeo Visto</span>
                    <span class="stat-valor" style="font-size: 1.1rem;"><?= $ultimoVideoData ?></span>
                    <span class="stat-subtexto">Data de conclusão</span>
                </div>
            </div>

            <div id="containerVideosAssistidos">
                <?php if (!empty($videosAssistidosLista)): ?>
                    <table class="tabela-itens">
                        <thead>
                            <tr>
                                <th>Vídeo / Prática Assistida</th>
                                <th>Canal / Categoria</th>
                                <th>Data de Visualização</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($videosAssistidosLista as $va): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($va['termo_ou_titulo']) ?></strong></td>
                                    <td><span class="badge-tipo video"><?= htmlspecialchars($va['categoria']) ?></span></td>
                                    <td><?= $va['data_formatada'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="lista-vazia-aviso" id="avisoVazioVideosAssistidos">
                        Vídeos selecionados da plataforma (técnicas de respiração 4-7-8, foco e bem-estar mental) que você assistir na aba Adicionais aparecerão detalhados aqui.
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- RODAPÉ OFICIAL -->
        <footer class="relatorio-rodape">
            <div>
                <strong>HelpFull</strong> — Plataforma de Apoio e Saúde Mental &copy; <?= date('Y') ?>
            </div>
            <div class="rodape-msg">
                Cuidando do que realmente importa: Você. ✦
            </div>
        </footer>

    </div>

    <!-- Sincronização inteligente com localStorage (caso haja itens salvos no navegador antes do banco) -->
    <script>
        (function () {
            try {
                // 1. Pesquisas Comunidade
                const pCom = JSON.parse(localStorage.getItem('helpfull_pesquisas_comunidade') || '[]');
                const containerCom = document.getElementById('containerPesquisasComunidade');
                const avisoCom = document.getElementById('avisoVazioPesquisasComunidade');
                if (pCom.length > 0 && avisoCom) {
                    let html = `
                        <table class="tabela-itens">
                            <thead>
                                <tr>
                                    <th>Termo Pesquisado</th>
                                    <th>Categoria</th>
                                    <th>Data / Hora</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
                    pCom.slice(0, 15).forEach(item => {
                        const d = item.data ? new Date(item.data).toLocaleString('pt-BR') : 'Hoje';
                        const cat = item.categoria || 'Geral';
                        const badgeClasse = cat.toLowerCase().includes('livro') ? 'livro' : 'midia';
                        html += `
                            <tr>
                                <td><strong>${item.termo}</strong></td>
                                <td><span class="badge-tipo ${badgeClasse}">${cat}</span></td>
                                <td>${d}</td>
                            </tr>
                        `;
                    });
                    html += `</tbody></table>`;
                    containerCom.innerHTML = html;
                }

                // 2. Pesquisas Textos
                const pTxt = JSON.parse(localStorage.getItem('helpfull_pesquisas_textos') || '[]');
                const containerTxt = document.getElementById('containerPesquisasTextos');
                const avisoTxt = document.getElementById('avisoVazioTextos');
                if (pTxt.length > 0 && avisoTxt) {
                    let html = `<table class="tabela-itens"><thead><tr><th>Termo</th><th>Data</th></tr></thead><tbody>`;
                    pTxt.slice(0, 10).forEach(item => {
                        const d = item.data ? new Date(item.data).toLocaleString('pt-BR') : 'Hoje';
                        html += `<tr><td><strong>${item.termo}</strong></td><td>${d}</td></tr>`;
                    });
                    html += `</tbody></table>`;
                    containerTxt.innerHTML = html;
                }

                // 3. Pesquisas Vídeos
                const pVid = JSON.parse(localStorage.getItem('helpfull_pesquisas_videos') || '[]');
                const containerVid = document.getElementById('containerPesquisasVideos');
                const avisoVid = document.getElementById('avisoVazioVideos');
                if (pVid.length > 0 && avisoVid) {
                    let html = `<table class="tabela-itens"><thead><tr><th>Termo</th><th>Data</th></tr></thead><tbody>`;
                    pVid.slice(0, 10).forEach(item => {
                        const d = item.data ? new Date(item.data).toLocaleString('pt-BR') : 'Hoje';
                        html += `<tr><td><strong>${item.termo}</strong></td><td>${d}</td></tr>`;
                    });
                    html += `</tbody></table>`;
                    containerVid.innerHTML = html;
                }

                // 4. Vídeos Assistidos
                const vAssist = JSON.parse(localStorage.getItem('helpfull_videos_assistidos_lista') || '[]');
                const containerVAssist = document.getElementById('containerVideosAssistidos');
                const avisoVAssist = document.getElementById('avisoVazioVideosAssistidos');
                if (vAssist.length > 0 && avisoVAssist) {
                    let html = `
                        <table class="tabela-itens">
                            <thead>
                                <tr>
                                    <th>Vídeo / Prática Assistida</th>
                                    <th>Canal / Categoria</th>
                                    <th>Data de Visualização</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
                    vAssist.slice(0, 15).forEach(item => {
                        const d = item.data ? new Date(item.data).toLocaleString('pt-BR') : 'Hoje';
                        html += `
                            <tr>
                                <td><strong>${item.titulo}</strong></td>
                                <td><span class="badge-tipo video">${item.plataforma === 'dailymotion' ? 'Dailymotion' : 'Plataforma HelpFull'}</span></td>
                                <td>${d}</td>
                            </tr>
                        `;
                    });
                    html += `</tbody></table>`;
                    containerVAssist.innerHTML = html;
                }
            } catch (e) {}
        })();
    </script>
</body>

</html>
