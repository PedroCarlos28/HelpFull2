/**
 * HELPFULL - SISTEMA DE TUTORIAL E TOUR GUIADO INTERATIVO
 *
 * Dois modos:
 *  1. TOUR COMPLETO (global): começa no Início e navega junto com o usuário
 *     pelas telas principais (Diário → Comunidade → Helpy → Atividades → Perfil),
 *     explicando as funções de cada uma. O progresso é salvo no sessionStorage
 *     e retomado automaticamente quando a próxima página carrega.
 *  2. TOUR DA PÁGINA: explica apenas a tela atual (botão "✦ Tour da Página").
 */
(function () {
    'use strict';

    // =====================================================================
    // CONFIGURAÇÃO GERAL
    // =====================================================================
    var STORAGE_TOUR_GLOBAL = 'helpfull_tour_global';
    var EXPIRACAO_TOUR_MS = 20 * 60 * 1000; // 20 minutos

    // Ordem das paradas do tour completo
    var ROTEIRO = ['inicio', 'diario', 'comunidade', 'chatbot', 'atividades', 'perfil'];

    var URLS = {
        inicio: 'inicio.php',
        diario: 'Diario.php',
        comunidade: 'Comunidade.php',
        chatbot: 'ChatBOT.php',
        atividades: 'Atividades.php',
        perfil: 'Perfil.php',
        dispositivos: 'dispositivos.php'
    };

    var NOMES = {
        inicio: 'Início',
        diario: 'Diário',
        comunidade: 'Comunidade',
        chatbot: 'Helpy',
        atividades: 'Atividades',
        perfil: 'Perfil',
        dispositivos: 'Dispositivos'
    };

    function svg(conteudo) {
        return '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + conteudo + '</svg>';
    }

    var ICONES = {
        estrela: svg('<path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"></path>'),
        mapa: svg('<polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon><line x1="8" y1="2" x2="8" y2="18"></line><line x1="16" y1="6" x2="16" y2="22"></line>'),
        livro: svg('<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>'),
        livroAberto: svg('<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>'),
        chat: svg('<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>'),
        pessoas: svg('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>'),
        vento: svg('<path d="M9.59 4.59A2 2 0 1 1 11 8H2m10.59 11.41A2 2 0 1 0 14 16H2m15.73-8.27A2.5 2.5 0 1 1 19.5 12H2"></path>'),
        ajustes: svg('<line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line>'),
        lapis: svg('<path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>'),
        rosto: svg('<circle cx="12" cy="12" r="10"></circle><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line>'),
        salvar: svg('<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline>'),
        arquivo: svg('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line>'),
        relogio: svg('<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline>'),
        coracao: svg('<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>'),
        grade: svg('<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line>'),
        play: svg('<circle cx="12" cy="12" r="10"></circle><polygon points="10 8 16 12 10 16 10 8"></polygon>'),
        enviar: svg('<line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>'),
        editar: svg('<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>'),
        usuario: svg('<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>'),
        escudo: svg('<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>'),
        monitor: svg('<rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line>'),
        barras: svg('<line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line>'),
        calendario: svg('<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>'),
        power: svg('<path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line>'),
        lupa: svg('<circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>'),
        download: svg('<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line>'),
        trofeu: svg('<path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path><path d="M4 22h16"></path><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"></path><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"></path><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"></path>')
    };

    // Helpers para ações que preparam a tela antes de um passo
    function mostrarAbaAtividades(aba) {
        return function () {
            if (typeof window.showTab === 'function') {
                try { window.showTab(aba); } catch (e) {}
            } else {
                var btn = document.querySelector('.tab-btn[data-tab="' + aba + '"]');
                if (btn) btn.click();
            }
        };
    }

    // =====================================================================
    // TOUR COMPLETO (NAVEGA ENTRE AS PÁGINAS)
    // seletores: lista em ordem de prioridade; o primeiro elemento visível é usado.
    // Passos sem seletores aparecem centralizados (introdução/conclusão).
    // =====================================================================
    var tourCompleto = {
        inicio: [
            {
                seletores: [],
                icone: ICONES.estrela,
                categoria: 'BOAS-VINDAS',
                titulo: 'Vamos conhecer o HelpFull juntos?',
                desc: 'Neste tour eu vou te acompanhar pelas telas principais do site: Diário, Comunidade, Helpy, Atividades e Perfil. Em cada uma, mostro para que ela serve e como usar.',
                dica: 'Leva uns 3 minutos. Você pode sair a qualquer momento no × (ou Esc) e navegar com as setas ← → do teclado.'
            },
            {
                seletores: ['#menu', '.links-capsula'],
                icone: ICONES.mapa,
                categoria: 'NAVEGAÇÃO',
                titulo: 'O mapa do site',
                desc: 'Este menu leva a todas as áreas do HelpFull. Ao rolar a página, uma barra flutuante aparece no topo com os mesmos atalhos, seu avatar (que abre o Perfil) e o sininho de notificações.',
                dica: 'Clique em "Ir para Diário" e eu te levo até lá agora mesmo.',
                posicaoPreferida: 'bottom'
            }
        ],
        diario: [
            {
                seletores: ['#campoTexto', '.textarea-diario'],
                icone: ICONES.lapis,
                categoria: 'ESCRITA LIVRE',
                titulo: 'Escreva sobre o seu dia',
                desc: 'Este é o seu diário. Escreva livremente o que aconteceu, o que sentiu ou o que está passando pela sua cabeça. Só você tem acesso a esses textos.',
                dica: 'Sem ideia de como começar? Tente: "Hoje eu me senti… porque…".',
                posicaoPreferida: 'bottom'
            },
            {
                seletores: ['.secao-emocoes-interna', '.container-emocoes'],
                icone: ICONES.rosto,
                categoria: 'HUMOR DO DIA',
                titulo: 'Marque como você está',
                desc: 'Escolha a emoção que mais combina com seu momento: Irritado, Ansioso, Feliz, Calmo, Triste ou Amoroso. Ela fica ligada ao registro de hoje e alimenta os gráficos de humor do seu Perfil.',
                dica: 'Num dia corrido, dá para salvar só a emoção, mesmo sem escrever nada.',
                posicaoPreferida: 'top'
            },
            {
                seletores: ['.container-acoes', '.btn-salvar'],
                icone: ICONES.salvar,
                categoria: 'REGISTRO',
                titulo: 'Salvar ou recomeçar',
                desc: '"Salvar" guarda o registro do dia. "Apagar tudo" limpa o campo para você começar de novo.',
                dica: 'Escrever todos os dias forma uma sequência de autocuidado que aparece depois no seu Perfil.',
                posicaoPreferida: 'top'
            }
        ],
        comunidade: [
            {
                seletores: ['#formCriarPost', '#cardPlaceholder'],
                icone: ICONES.pessoas,
                categoria: 'ESPAÇO SEGURO',
                titulo: 'Compartilhe com a comunidade',
                desc: 'Aqui você publica desabafos, conquistas ou mensagens de apoio para outras pessoas que também estão cuidando da saúde mental. Escreva no campo e envie pelo botão de avião de papel.',
                dica: 'O espaço é moderado e sem julgamentos: respeito em primeiro lugar.',
                posicaoPreferida: 'bottom'
            },
            {
                seletores: ['#btnToggleImpacto', '#btnToggleImpactoNav'],
                icone: ICONES.rosto,
                categoria: 'TOM DO POST',
                titulo: 'Dia bom ou dia difícil?',
                desc: 'Este botão alterna a etiqueta da sua publicação entre "Bom" e "Ruim". Assim, quem lê entende como você está e pode responder com o cuidado certo.',
                dica: 'Toque no botão para alternar antes de enviar o post.',
                posicaoPreferida: 'bottom'
            },
            {
                seletores: ['#formCriarPost .btn-acao-circ', '.nav-btn-circ'],
                icone: ICONES.lupa,
                categoria: 'IMAGEM DE CAPA',
                titulo: 'Deixe seu post mais bonito',
                desc: 'A lupa abre uma busca de imagens para usar como capa da sua publicação. Escolha uma que represente o que você quer transmitir.',
                dica: 'A imagem é opcional: um post só com texto também é muito bem-vindo.',
                posicaoPreferida: 'bottom'
            },
            {
                seletores: ['.post-card'],
                icone: ICONES.coracao,
                categoria: 'EMPATIA',
                titulo: 'Curta, reaja e apoie',
                desc: 'Cada publicação pode receber curtidas, reações e comentários. Uma mensagem gentil pode fazer toda a diferença no dia de alguém.',
                dica: 'Nos seus próprios posts, use o menu de três pontinhos para editar ou excluir.',
                posicaoPreferida: 'top'
            }
        ],
        chatbot: [
            {
                seletores: ['.caixa-chat', '#area-mensagens'],
                icone: ICONES.chat,
                categoria: 'ASSISTENTE COM IA',
                titulo: 'Converse com o Helpy',
                desc: 'O Helpy é um assistente com inteligência artificial que conversa com empatia. Use para desabafar, organizar ideias ou pedir técnicas para se acalmar, a qualquer hora.',
                dica: 'O Helpy apoia, mas não substitui um profissional. Em uma emergência, ligue 188 (CVV) — é gratuito e funciona 24h.',
                posicaoPreferida: 'bottom'
            },
            {
                seletores: ['.area-input-chat', '#inputChat'],
                icone: ICONES.enviar,
                categoria: 'SUA MENSAGEM',
                titulo: 'Digite e envie',
                desc: 'Escreva aqui o que quiser conversar e pressione Enter ou o botão de enviar. O Helpy responde em poucos segundos.',
                dica: 'Experimente: "estou ansioso, me ajuda a respirar".',
                posicaoPreferida: 'top'
            },
            {
                seletores: ['.sidebar-historico', '.btn-toggle-historico-mobile', '.btn-novo-chat'],
                icone: ICONES.editar,
                categoria: 'HISTÓRICO',
                titulo: 'Suas conversas ficam salvas',
                desc: 'As conversas são organizadas por dia. Clique em um dia para reabrir o que foi conversado, ou no ícone de lápis para começar um assunto novo do zero.',
                dica: 'No celular, o histórico abre pelo botão "Histórico" no topo da tela.',
                posicaoPreferida: 'bottom'
            }
        ],
        atividades: [
            {
                seletores: ['#tabsElement', '#tabs-original-container'],
                icone: ICONES.grade,
                categoria: 'CONTEÚDOS',
                titulo: 'Textos ou vídeos',
                desc: 'A área de Atividades reúne materiais práticos para o seu bem-estar. Use estas abas para alternar entre artigos curtos e vídeos guiados.',
                dica: 'Escolha o formato que combina com o seu momento: ler ou assistir.',
                posicaoPreferida: 'bottom',
                antes: mostrarAbaAtividades('textos')
            },
            {
                seletores: ['#tab-textos .busca-wrapper', '.busca-wrapper'],
                icone: ICONES.lupa,
                categoria: 'PESQUISA',
                titulo: 'Busque um tema',
                desc: 'Digite um assunto — ansiedade, sono, meditação, autocuidado — e clique em "Buscar" para encontrar artigos sobre ele.',
                dica: 'A aba de vídeos tem uma busca igual a esta.',
                posicaoPreferida: 'bottom',
                antes: mostrarAbaAtividades('textos')
            },
            {
                seletores: ['#tab-textos .card-artigo', '.card-artigo'],
                icone: ICONES.livroAberto,
                categoria: 'LEITURAS',
                titulo: 'Textos escolhidos por nós',
                desc: 'Abaixo da busca ficam artigos selecionados pela equipe, como a técnica de respiração 4-7-8. São leituras de poucos minutos para momentos de tensão.',
                dica: 'Que tal ler um texto curto pela manhã para começar o dia com mais calma?',
                posicaoPreferida: 'top',
                antes: mostrarAbaAtividades('textos')
            },
            {
                seletores: ['#tab-videos .secao-video', '#sobre-videos', '.tab-btn[data-tab="videos"]'],
                icone: ICONES.play,
                categoria: 'VÍDEOS',
                titulo: 'Respire e relaxe com vídeos',
                desc: 'Na aba Vídeos você encontra respirações guiadas, exercícios de foco e conteúdos que explicam como a mente funciona. É só clicar em um vídeo para assistir.',
                dica: 'Use fones de ouvido para uma experiência mais imersiva.',
                posicaoPreferida: 'top',
                antes: mostrarAbaAtividades('videos')
            }
        ],
        perfil: [
            {
                seletores: ['#cardSuaConta', '.card-perfil'],
                icone: ICONES.usuario,
                categoria: 'SUA CONTA',
                titulo: 'Seus dados',
                desc: 'Aqui ficam seu nome, e-mail e foto de perfil. Você pode editar suas informações e trocar a senha quando quiser.',
                dica: 'Uma foto deixa suas interações na Comunidade mais pessoais.',
                posicaoPreferida: 'bottom'
            },
            {
                seletores: ['.stats-container'],
                icone: ICONES.trofeu,
                categoria: 'SUA EVOLUÇÃO',
                titulo: 'Seus números',
                desc: 'Veja quantos diários você escreveu, quantas emoções registrou, quantas conversas teve com o Helpy e quanto apoio recebeu da Comunidade.',
                dica: 'Cada número é um passo na sua jornada de autocuidado.',
                posicaoPreferida: 'top'
            },
            {
                seletores: ['.grid-cal-detalhes'],
                icone: ICONES.calendario,
                categoria: 'CALENDÁRIO',
                titulo: 'Releia qualquer dia',
                desc: 'Os dias em que você fez um registro ficam marcados no calendário. Clique em um deles para reler o diário e ver a emoção escolhida.',
                dica: 'Use as setas do calendário para navegar entre os meses.',
                posicaoPreferida: 'top'
            },
            {
                seletores: ['.graficos-container'],
                icone: ICONES.barras,
                categoria: 'GRÁFICOS',
                titulo: 'Entenda seus padrões',
                desc: 'Os gráficos mostram a frequência dos seus diários e quais emoções apareceram mais no mês. Ótimo para perceber o que tem afetado seu humor.',
                dica: 'Quanto mais você registra no Diário, mais completos ficam os gráficos.',
                posicaoPreferida: 'top'
            },
            {
                seletores: ['#btnGerarRelatorioPdf', '.btn-gerar-relatorio'],
                icone: ICONES.download,
                categoria: 'RELATÓRIO',
                titulo: 'Relatório em PDF',
                desc: 'Gera um relatório com seus registros e gráficos para acompanhar sua evolução ou levar a uma consulta com psicólogo(a).',
                dica: 'O relatório abre em uma nova aba, pronto para salvar ou imprimir.',
                posicaoPreferida: 'top'
            },
            {
                seletores: ['#balao2faContainer', '#cardDispositivosPerfil'],
                icone: ICONES.escudo,
                categoria: 'SEGURANÇA',
                titulo: 'Proteja sua conta',
                desc: 'Ative a verificação em duas etapas para receber um código no e-mail a cada login. Logo abaixo, em "Dispositivos Conectados", você vê e desconecta aparelhos que acessaram sua conta.',
                dica: 'Usou um computador público? Desconecte-o pela central de dispositivos.',
                posicaoPreferida: 'top'
            },
            {
                seletores: [],
                icone: ICONES.estrela,
                categoria: 'TUDO PRONTO',
                titulo: 'Agora você conhece o HelpFull ✨',
                desc: 'Você passou pelo Diário, Comunidade, Helpy, Atividades e Perfil. Explore no seu ritmo — este espaço é seu.',
                dica: 'Para rever, use "✦ Tour pelo Site" no menu do Início, ou "✦ Tour da Página" em qualquer tela. Em "Configurações" você ativa o tema escuro, ajusta a fonte e liga o tradutor de Libras.'
            }
        ]
    };

    // =====================================================================
    // TOURS INDIVIDUAIS (APENAS A PÁGINA ATUAL)
    // =====================================================================
    var colecaoPassos = {
        inicio: [
            {
                seletores: ['#heroCarrosselCard', '.hero-carrossel'],
                titulo: 'Bem-vindo ao seu espaço seguro',
                icone: ICONES.estrela,
                categoria: 'SEU REFÚGIO',
                desc: 'O HelpFull é o seu espaço diário para cuidar da saúde mental, organizar seus sentimentos e encontrar ferramentas acolhedoras para uma vida mais leve.',
                dica: 'Faça login para fazer o tour completo, que passa por todas as telas do site.',
                posicaoPreferida: 'bottom'
            },
            {
                seletores: ['#menu', '.links-capsula'],
                titulo: 'Navegação',
                icone: ICONES.mapa,
                categoria: 'MAPA DO SITE',
                desc: 'Por este menu você acessa o Diário, a Comunidade, o Helpy (assistente com IA) e as Atividades de relaxamento.',
                dica: 'Ao rolar a página, uma barra flutuante aparece no topo com os mesmos atalhos.',
                posicaoPreferida: 'bottom'
            },
            {
                seletores: ['#secaoTutorialHelpy', 'a[href="ChatBOT.php"]'],
                titulo: 'Converse com o Helpy a qualquer hora',
                icone: ICONES.chat,
                categoria: 'APOIO 24 HORAS',
                desc: 'Precisa desabafar na madrugada ou organizar ideias confusas? O Helpy é a nossa IA empática, treinada para te escutar com carinho e sigilo.',
                dica: 'O Helpy oferece exercícios imediatos de calma e reflexões gentis sempre que você precisar.',
                posicaoPreferida: 'top'
            }
        ],
        diario: tourCompleto.diario,
        comunidade: tourCompleto.comunidade,
        chatbot: tourCompleto.chatbot,
        atividades: tourCompleto.atividades,
        perfil: tourCompleto.perfil.slice(0, tourCompleto.perfil.length - 1),
        dispositivos: [
            {
                seletores: ['.card-dispositivos-header', '.conteudo-site'],
                titulo: 'Central de Dispositivos',
                icone: ICONES.escudo,
                categoria: 'SEGURANÇA',
                desc: 'Aqui você tem controle total sobre quais computadores e celulares têm acesso permitido à sua conta HelpFull.',
                dica: 'Se você usou um computador público ou de terceiros, encerre o acesso por aqui com tranquilidade.',
                posicaoPreferida: 'bottom'
            },
            {
                seletores: ['.card-sessao-atual', '.sessao-item.atual'],
                titulo: 'Sua Conexão Atual',
                icone: ICONES.relogio,
                categoria: 'ESTE APARELHO',
                desc: 'Identifica o navegador, sistema operacional e horário do seu login atual. Esta conexão é marcada em destaque como ativa.',
                dica: 'Essa sessão permanece segura através de chaves exclusivas de autenticação.',
                posicaoPreferida: 'bottom'
            },
            {
                seletores: ['.card-outros-dispositivos', '.lista-dispositivos'],
                titulo: 'Outros Aparelhos e Desconexão',
                icone: ICONES.power,
                categoria: 'CONTROLE TOTAL',
                desc: 'Lista os outros dispositivos conectados. Você pode encerrar sessões individualmente ou desconectar todos os outros aparelhos de uma só vez.',
                dica: 'Muito útil caso você perca um celular ou queira garantir que ninguém mais esteja conectado.',
                posicaoPreferida: 'top'
            }
        ]
    };

    // =====================================================================
    // ESTADO
    // =====================================================================
    var modoAtual = 'pagina';   // 'pagina' | 'global'
    var paginaAtual = 'inicio';
    var passosAtivos = [];
    var passoAtual = 0;
    var navegando = false;

    var overlayEl = null;
    var cardEl = null;
    var glowEl = null;
    var cutoutRectEl = null;
    var rafPendente = false;

    // =====================================================================
    // UTILITÁRIOS
    // =====================================================================
    function detectarPaginaAtual() {
        var path = window.location.pathname.toLowerCase();
        if (path.indexOf('diario') !== -1) return 'diario';
        if (path.indexOf('comunidade') !== -1) return 'comunidade';
        if (path.indexOf('atividades') !== -1) return 'atividades';
        if (path.indexOf('chatbot') !== -1) return 'chatbot';
        if (path.indexOf('dispositivos') !== -1) return 'dispositivos';
        if (path.indexOf('perfil') !== -1) return 'perfil';
        return 'inicio';
    }

    function usuarioEstaLogado() {
        return !!window._helpfullUsuarioId || !!document.querySelector('.perfil-capsula');
    }

    function elementoVisivel(el) {
        if (!el) return false;
        var r = el.getBoundingClientRect();
        if (r.width < 2 || r.height < 2) return false;
        // Fora da tela na horizontal (ex.: menu lateral escondido no celular)
        if (r.right <= 0 || r.left >= window.innerWidth) return false;
        var estilo = window.getComputedStyle(el);
        if (estilo.visibility === 'hidden' || estilo.display === 'none' || parseFloat(estilo.opacity) === 0) return false;
        return true;
    }

    function obterElementoAlvo(passo) {
        var lista = passo.seletores || [];
        for (var i = 0; i < lista.length; i++) {
            var candidatos;
            try { candidatos = document.querySelectorAll(lista[i]); } catch (e) { continue; }
            for (var j = 0; j < candidatos.length; j++) {
                if (elementoVisivel(candidatos[j])) return candidatos[j];
            }
        }
        return null;
    }

    function salvarEstadoGlobal(pagina, passo) {
        try {
            sessionStorage.setItem(STORAGE_TOUR_GLOBAL, JSON.stringify({ pagina: pagina, passo: passo, ts: Date.now() }));
        } catch (e) {}
    }

    function lerEstadoGlobal() {
        try {
            var bruto = sessionStorage.getItem(STORAGE_TOUR_GLOBAL);
            if (!bruto) return null;
            var dados = JSON.parse(bruto);
            if (!dados || !dados.pagina || (Date.now() - (dados.ts || 0)) > EXPIRACAO_TOUR_MS) {
                limparEstadoGlobal();
                return null;
            }
            return dados;
        } catch (e) {
            return null;
        }
    }

    function limparEstadoGlobal() {
        try { sessionStorage.removeItem(STORAGE_TOUR_GLOBAL); } catch (e) {}
    }

    function indiceNoRoteiro(pagina) {
        return ROTEIRO.indexOf(pagina);
    }

    function totalPassosGlobais() {
        var total = 0;
        for (var i = 0; i < ROTEIRO.length; i++) total += tourCompleto[ROTEIRO[i]].length;
        return total;
    }

    function offsetGlobal(pagina) {
        var soma = 0;
        for (var i = 0; i < ROTEIRO.length && ROTEIRO[i] !== pagina; i++) soma += tourCompleto[ROTEIRO[i]].length;
        return soma;
    }

    // =====================================================================
    // ESTRUTURA DO DOM
    // =====================================================================
    function criarEstruturaDOM() {
        if (overlayEl) return;

        overlayEl = document.createElement('div');
        overlayEl.id = 'tutorial-overlay';
        overlayEl.innerHTML =
            '<svg class="tutorial-spotlight-svg" preserveAspectRatio="none">' +
            '  <defs>' +
            '    <mask id="tutorial-spotlight-mask" x="0" y="0" width="100%" height="100%">' +
            '      <rect x="0" y="0" width="100%" height="100%" fill="#ffffff" />' +
            '      <rect class="tutorial-cutout-rect" id="tutorial-cutout" x="0" y="0" width="0" height="0" rx="18" ry="18" fill="#000000" />' +
            '    </mask>' +
            '  </defs>' +
            '  <rect x="0" y="0" width="100%" height="100%" fill="rgba(8, 14, 22, 0.78)" mask="url(#tutorial-spotlight-mask)" />' +
            '</svg>' +
            '<div id="tutorial-target-glow" style="display: none;"></div>';
        document.body.appendChild(overlayEl);

        cutoutRectEl = document.getElementById('tutorial-cutout');
        glowEl = document.getElementById('tutorial-target-glow');

        cardEl = document.createElement('div');
        cardEl.id = 'tutorial-card';
        cardEl.setAttribute('role', 'dialog');
        cardEl.setAttribute('aria-modal', 'true');
        cardEl.setAttribute('aria-live', 'polite');
        cardEl.setAttribute('aria-label', 'Tutorial interativo do HelpFull');
        cardEl.innerHTML =
            '<div class="tutorial-progress-bar"><div class="tutorial-progress-fill" id="tutorialProgressFill"></div></div>' +
            '<div class="tutorial-card-body">' +
            '  <div class="tutorial-card-header">' +
            '    <span class="tutorial-badge" id="tutorialBadge">✦ PASSO 1</span>' +
            '    <button type="button" class="tutorial-btn-fechar" id="tutorialBtnFechar" aria-label="Fechar tutorial" title="Sair do tour">×</button>' +
            '  </div>' +
            '  <div class="tutorial-etapas" id="tutorialEtapas" hidden></div>' +
            '  <div class="tutorial-title-block">' +
            '    <div class="tutorial-step-icon" id="tutorialStepIcon"></div>' +
            '    <div><h3 id="tutorialTitulo">Carregando...</h3></div>' +
            '  </div>' +
            '  <p class="tutorial-desc" id="tutorialDesc"></p>' +
            '  <div class="tutorial-dica-box" id="tutorialDicaBox">' +
            '    <span class="tutorial-dica-icone"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6"></path><path d="M10 22h4"></path><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5.76.76 1.23 1.52 1.41 2.5"></path></svg></span>' +
            '    <span id="tutorialDicaTexto"></span>' +
            '  </div>' +
            '  <div class="tutorial-card-footer">' +
            '    <div class="tutorial-dots" id="tutorialDots"></div>' +
            '    <div class="tutorial-nav-botoes">' +
            '      <button type="button" class="tutorial-btn-voltar" id="tutorialBtnVoltar">Voltar</button>' +
            '      <button type="button" class="tutorial-btn-proximo" id="tutorialBtnProximo">Próximo →</button>' +
            '    </div>' +
            '  </div>' +
            '</div>';
        document.body.appendChild(cardEl);

        document.getElementById('tutorialBtnFechar').addEventListener('click', function () { finalizarTutorial(false); });
        document.getElementById('tutorialBtnVoltar').addEventListener('click', passoAnterior);
        document.getElementById('tutorialBtnProximo').addEventListener('click', proximoPasso);
        document.getElementById('tutorialEtapas').addEventListener('click', function (e) {
            var alvo = e.target.closest ? e.target.closest('[data-etapa]') : null;
            if (!alvo || navegando) return;
            var pagina = alvo.getAttribute('data-etapa');
            if (pagina === paginaAtual) { exibirPasso(0); return; }
            navegarPara(pagina, 0);
        });

        document.addEventListener('keydown', onKeyDownTutorial);
        window.addEventListener('resize', agendarReposicionamento);
        window.addEventListener('scroll', agendarReposicionamento, { passive: true });
    }

    function tutorialAtivo() {
        return overlayEl && overlayEl.classList.contains('ativo');
    }

    function onKeyDownTutorial(e) {
        if (!tutorialAtivo() || navegando) return;
        var focoEmBotao = e.target && e.target.tagName === 'BUTTON' && cardEl.contains(e.target);
        if (e.key === 'Escape') {
            e.preventDefault();
            finalizarTutorial(false);
        } else if (e.key === 'ArrowRight' || (e.key === 'Enter' && !focoEmBotao)) {
            e.preventDefault();
            proximoPasso();
        } else if (e.key === 'ArrowLeft') {
            e.preventDefault();
            passoAnterior();
        }
    }

    function agendarReposicionamento() {
        if (!tutorialAtivo() || rafPendente) return;
        rafPendente = true;
        requestAnimationFrame(function () {
            rafPendente = false;
            atualizarPosicoesPasso();
        });
    }

    // =====================================================================
    // POSICIONAMENTO DO DESTAQUE E DO CARD
    // =====================================================================
    function atualizarPosicoesPasso() {
        var passo = passosAtivos[passoAtual];
        if (!passo || !cardEl) return;

        var alvo = obterElementoAlvo(passo);
        var vw = window.innerWidth;
        var vh = window.innerHeight;
        var isMobile = vw <= 768;
        var cardW = Math.min(410, vw - 32);
        var cardH = cardEl.offsetHeight || 300;

        if (!alvo) {
            // Passo centralizado (introdução, conclusão ou elemento inexistente)
            cutoutRectEl.setAttribute('width', 0);
            cutoutRectEl.setAttribute('height', 0);
            cutoutRectEl.setAttribute('x', vw / 2);
            cutoutRectEl.setAttribute('y', vh / 2);
            glowEl.style.display = 'none';
            cardEl.classList.add('tutorial-card-centro');
            if (!isMobile) {
                cardEl.style.bottom = '';
                cardEl.style.right = '';
                cardEl.style.left = Math.round((vw - cardW) / 2) + 'px';
                cardEl.style.top = Math.round(Math.max(20, (vh - cardH) / 2)) + 'px';
            }
            return;
        }

        cardEl.classList.remove('tutorial-card-centro');

        var rect = alvo.getBoundingClientRect();
        var padding = 12;
        var x = Math.max(8, rect.left - padding);
        var y = Math.max(8, rect.top - padding);
        var w = Math.min(vw - 8 - x, rect.right + padding - x);
        var h = Math.min(vh - 8 - y, rect.bottom + padding - y);
        if (h < 0) h = 0;

        cutoutRectEl.setAttribute('x', x);
        cutoutRectEl.setAttribute('y', y);
        cutoutRectEl.setAttribute('width', w);
        cutoutRectEl.setAttribute('height', h);

        glowEl.style.display = h > 0 ? 'block' : 'none';
        glowEl.style.left = x + 'px';
        glowEl.style.top = y + 'px';
        glowEl.style.width = w + 'px';
        glowEl.style.height = h + 'px';

        if (isMobile) {
            cardEl.style.top = '';
            cardEl.style.left = '';
            cardEl.style.right = '';
            cardEl.style.bottom = '16px';
            return;
        }

        var margem = 18;
        var cardX = x + w / 2 - cardW / 2;
        cardX = Math.max(20, Math.min(cardX, vw - cardW - 20));

        var espacoAbaixo = vh - (y + h) - margem - 20;
        var espacoAcima = y - margem - 20;
        var cabeAbaixo = espacoAbaixo >= cardH;
        var cabeAcima = espacoAcima >= cardH;
        var cardY;

        if (passo.posicaoPreferida === 'top' && cabeAcima) {
            cardY = y - cardH - margem;
        } else if (cabeAbaixo) {
            cardY = y + h + margem;
        } else if (cabeAcima) {
            cardY = y - cardH - margem;
        } else {
            // Elemento muito grande: tenta colocar ao lado, senão fixa no canto inferior
            var espacoDireita = vw - (x + w) - margem - 20;
            var espacoEsquerda = x - margem - 20;
            if (espacoDireita >= cardW) {
                cardX = x + w + margem;
            } else if (espacoEsquerda >= cardW) {
                cardX = x - cardW - margem;
            } else {
                cardX = vw - cardW - 24;
            }
            cardY = vh - cardH - 24;
        }

        cardY = Math.max(20, Math.min(cardY, vh - cardH - 20));

        cardEl.style.bottom = '';
        cardEl.style.right = '';
        cardEl.style.left = Math.round(cardX) + 'px';
        cardEl.style.top = Math.round(cardY) + 'px';
    }

    // =====================================================================
    // RENDERIZAÇÃO DO CARD
    // =====================================================================
    function renderizarDots(total, atual) {
        var container = document.getElementById('tutorialDots');
        var html = '';
        for (var i = 0; i < total; i++) {
            html += '<span class="tutorial-dot' + (i === atual ? ' ativo' : '') + '"></span>';
        }
        container.innerHTML = html;
    }

    function renderizarEtapas() {
        var container = document.getElementById('tutorialEtapas');
        if (modoAtual !== 'global') {
            container.hidden = true;
            container.innerHTML = '';
            return;
        }
        var idx = indiceNoRoteiro(paginaAtual);
        var trilha = '';
        for (var i = 0; i < ROTEIRO.length; i++) {
            var classe = i < idx ? 'feito' : (i === idx ? 'atual' : '');
            trilha += '<button type="button" class="tutorial-etapa ' + classe + '" data-etapa="' + ROTEIRO[i] + '" ' +
                'title="' + NOMES[ROTEIRO[i]] + '" aria-label="Ir para a etapa ' + NOMES[ROTEIRO[i]] + '">' +
                '<span class="tutorial-etapa-nome">' + NOMES[ROTEIRO[i]] + '</span></button>';
        }
        container.innerHTML = '<div class="tutorial-etapas-trilha">' + trilha + '</div>';
        container.hidden = false;
    }

    function exibirPasso(index) {
        if (index < 0 || index >= passosAtivos.length) return;
        passoAtual = index;
        var passo = passosAtivos[index];

        if (modoAtual === 'global') salvarEstadoGlobal(paginaAtual, index);

        if (typeof passo.antes === 'function') {
            try { passo.antes(); } catch (e) {}
        }

        // Atualiza conteúdo
        var badge;
        if (modoAtual === 'global') {
            badge = '✦ ' + NOMES[paginaAtual].toUpperCase() + ' • ' + (passo.categoria || '');
        } else {
            badge = '✦ PASSO ' + (index + 1) + ' DE ' + passosAtivos.length + ' • ' + (passo.categoria || NOMES[paginaAtual].toUpperCase());
        }
        document.getElementById('tutorialBadge').textContent = badge;
        document.getElementById('tutorialStepIcon').innerHTML = passo.icone || ICONES.estrela;
        document.getElementById('tutorialTitulo').textContent = passo.titulo;
        document.getElementById('tutorialDesc').textContent = passo.desc;
        document.getElementById('tutorialDicaTexto').textContent = passo.dica || '';
        document.getElementById('tutorialDicaBox').style.display = passo.dica ? '' : 'none';

        // Progresso
        var pct;
        if (modoAtual === 'global') {
            pct = ((offsetGlobal(paginaAtual) + index + 1) / totalPassosGlobais()) * 100;
        } else {
            pct = ((index + 1) / passosAtivos.length) * 100;
        }
        document.getElementById('tutorialProgressFill').style.width = Math.round(pct) + '%';

        renderizarDots(passosAtivos.length, index);
        renderizarEtapas();
        atualizarBotoes();

        // Rola até o elemento (se necessário) e posiciona
        var alvo = obterElementoAlvo(passo);
        var precisouRolar = false;
        if (alvo) {
            var r = alvo.getBoundingClientRect();
            var visivel = r.top >= 70 && r.bottom <= window.innerHeight - 90;
            if (!visivel) {
                precisouRolar = true;
                alvo.scrollIntoView({ behavior: 'smooth', block: r.height > window.innerHeight * 0.6 ? 'start' : 'center' });
            }
        }

        atualizarPosicoesPasso();
        setTimeout(atualizarPosicoesPasso, precisouRolar ? 450 : 80);

        var btnProximo = document.getElementById('tutorialBtnProximo');
        if (btnProximo) {
            try { btnProximo.focus({ preventScroll: true }); } catch (e) {}
        }
    }

    function atualizarBotoes() {
        var btnVoltar = document.getElementById('tutorialBtnVoltar');
        var btnProximo = document.getElementById('tutorialBtnProximo');
        var ultimo = passoAtual === passosAtivos.length - 1;
        var idx = indiceNoRoteiro(paginaAtual);
        var proxPagina = (modoAtual === 'global' && idx > -1 && idx < ROTEIRO.length - 1) ? ROTEIRO[idx + 1] : null;
        var pagAnterior = (modoAtual === 'global' && idx > 0) ? ROTEIRO[idx - 1] : null;

        btnVoltar.disabled = passoAtual === 0 && !pagAnterior;
        btnVoltar.textContent = (passoAtual === 0 && pagAnterior) ? '← ' + NOMES[pagAnterior] : 'Voltar';

        btnProximo.disabled = false;
        btnProximo.classList.remove('tutorial-btn-ir', 'tutorial-btn-concluir');
        if (!ultimo) {
            btnProximo.textContent = 'Próximo →';
        } else if (proxPagina) {
            btnProximo.textContent = 'Ir para ' + NOMES[proxPagina] + ' →';
            btnProximo.classList.add('tutorial-btn-ir');
        } else {
            btnProximo.textContent = 'Concluir tour ✨';
            btnProximo.classList.add('tutorial-btn-concluir');
        }
    }

    // =====================================================================
    // NAVEGAÇÃO ENTRE PASSOS E PÁGINAS
    // =====================================================================
    function navegarPara(pagina, passo) {
        if (!URLS[pagina]) return;
        navegando = true;
        salvarEstadoGlobal(pagina, passo);

        var btnProximo = document.getElementById('tutorialBtnProximo');
        var btnVoltar = document.getElementById('tutorialBtnVoltar');
        if (btnProximo) {
            btnProximo.disabled = true;
            btnProximo.innerHTML = '<span class="tutorial-spinner" aria-hidden="true"></span> Abrindo ' + NOMES[pagina] + '...';
        }
        if (btnVoltar) btnVoltar.disabled = true;
        if (cardEl) cardEl.classList.add('tutorial-card-saindo');

        setTimeout(function () {
            window.location.href = URLS[pagina];
        }, 260);
    }

    function proximoPasso() {
        if (navegando) return;
        if (passoAtual < passosAtivos.length - 1) {
            exibirPasso(passoAtual + 1);
            return;
        }
        if (modoAtual === 'global') {
            var idx = indiceNoRoteiro(paginaAtual);
            if (idx > -1 && idx < ROTEIRO.length - 1) {
                navegarPara(ROTEIRO[idx + 1], 0);
                return;
            }
        }
        finalizarTutorial(true);
    }

    function passoAnterior() {
        if (navegando) return;
        if (passoAtual > 0) {
            exibirPasso(passoAtual - 1);
            return;
        }
        if (modoAtual === 'global') {
            var idx = indiceNoRoteiro(paginaAtual);
            if (idx > 0) navegarPara(ROTEIRO[idx - 1], -1); // -1 = último passo daquela página
        }
    }

    // =====================================================================
    // INÍCIO / FIM
    // =====================================================================
    function abrirOverlay() {
        var modalOnboarding = document.getElementById('modal-onboarding');
        if (modalOnboarding && modalOnboarding.classList.contains('aberto')) {
            modalOnboarding.classList.remove('aberto');
        }
        if (window.fecharPainelAcessibilidade) {
            try { window.fecharPainelAcessibilidade(); } catch (e) {}
        }
        overlayEl.classList.add('ativo');
        cardEl.classList.remove('tutorial-card-saindo');
        cardEl.classList.add('ativo');
        document.body.style.overflow = 'hidden';
    }

    function iniciarTourGlobalNaPagina(pagina, passo) {
        criarEstruturaDOM();
        modoAtual = 'global';
        paginaAtual = pagina;
        passosAtivos = tourCompleto[pagina];
        navegando = false;
        var inicio = passo < 0 ? passosAtivos.length - 1 : Math.min(passo || 0, passosAtivos.length - 1);
        abrirOverlay();
        exibirPasso(inicio);
    }

    /**
     * Inicia o tour completo que navega pelas telas.
     * Se não estiver no Início, leva o usuário até lá primeiro.
     */
    function iniciarTourCompleto() {
        var pagina = detectarPaginaAtual();
        if (pagina === 'inicio') {
            iniciarTourGlobalNaPagina('inicio', 0);
        } else {
            salvarEstadoGlobal('inicio', 0);
            window.location.href = URLS.inicio;
        }
    }

    /**
     * Compatível com as chamadas existentes: window.iniciarTutorial(true)
     * - No Início (usuário logado): abre o TOUR COMPLETO entre as telas.
     * - Nas demais telas: abre o tour daquela página.
     * tipoPersonalizado: 'global' força o tour completo; nome da página força o tour da página.
     */
    function iniciarTutorial(forcar, tipoPersonalizado) {
        var pagina = detectarPaginaAtual();

        if (tipoPersonalizado === 'global' || (!tipoPersonalizado && pagina === 'inicio' && usuarioEstaLogado())) {
            if (!forcar && localStorage.getItem('helpfull_tutorial_concluido') === 'true') return;
            iniciarTourCompleto();
            return;
        }

        criarEstruturaDOM();
        limparEstadoGlobal();
        modoAtual = 'pagina';
        paginaAtual = tipoPersonalizado || pagina;
        passosAtivos = colecaoPassos[paginaAtual] || colecaoPassos.inicio;

        if (!forcar) {
            var uid = window._helpfullUsuarioId || '';
            var concluido = localStorage.getItem('helpfull_tutorial_' + paginaAtual + '_concluido') === 'true' ||
                (uid && localStorage.getItem('helpfull_tutorial_' + paginaAtual + '_concluido_' + uid) === 'true');
            if (concluido) return;
        }

        abrirOverlay();
        exibirPasso(0);
    }

    function marcarConcluido(pagina) {
        var uid = window._helpfullUsuarioId || '';
        localStorage.setItem('helpfull_tutorial_' + pagina + '_concluido', 'true');
        if (uid) localStorage.setItem('helpfull_tutorial_' + pagina + '_concluido_' + uid, 'true');
    }

    function marcarOnboardingConcluido() {
        var uid = window._helpfullUsuarioId || '';
        localStorage.setItem('helpfull_tutorial_concluido', 'true');
        if (uid) {
            localStorage.setItem('helpfull_onboarding_' + uid, 'true');
            localStorage.setItem('helpfull_tutorial_concluido_' + uid, 'true');
        }
        try { fetch('concluir_onboarding.php', { method: 'POST' }).catch(function () {}); } catch (e) {}
    }

    /**
     * @param {boolean} concluiu true quando o usuário chegou ao fim; false quando fechou/pulou.
     */
    function finalizarTutorial(concluiu) {
        if (overlayEl) overlayEl.classList.remove('ativo');
        if (cardEl) cardEl.classList.remove('ativo');
        if (glowEl) glowEl.style.display = 'none';
        document.body.style.overflow = '';
        navegando = false;

        var eraGlobal = modoAtual === 'global';
        limparEstadoGlobal();

        if (eraGlobal) {
            for (var i = 0; i < ROTEIRO.length; i++) marcarConcluido(ROTEIRO[i]);
            marcarOnboardingConcluido();
        } else {
            marcarConcluido(paginaAtual);
            if (paginaAtual === 'inicio') marcarOnboardingConcluido();
        }

        if (concluiu === true && window.mostrarNotificacaoAtiva) {
            var msg = eraGlobal
                ? 'Tour concluído! Agora você conhece o HelpFull. Aproveite cada espaço com calma. ✨'
                : 'Tour do ' + NOMES[paginaAtual] + ' concluído! Aproveite cada detalhe deste espaço com calma. ✨';
            try { window.mostrarNotificacaoAtiva(msg, window.location.pathname); } catch (e) {}
        }
    }

    // =====================================================================
    // EXPORTAÇÕES GLOBAIS
    // =====================================================================
    window.iniciarTutorial = iniciarTutorial;
    window.iniciarTourCompleto = iniciarTourCompleto;
    window.finalizarTutorial = function () { finalizarTutorial(false); };
    window.proximoPassoTutorial = proximoPasso;
    window.passoAnteriorTutorial = passoAnterior;

    // =====================================================================
    // INICIALIZAÇÃO AUTOMÁTICA
    // =====================================================================
    function aoCarregar() {
        var pagina = detectarPaginaAtual();

        // 1. Retoma o tour completo se ele estiver em andamento
        var estado = lerEstadoGlobal();
        if (estado) {
            if (estado.pagina === pagina && tourCompleto[pagina]) {
                setTimeout(function () {
                    iniciarTourGlobalNaPagina(pagina, estado.passo);
                }, 650);
                return;
            }
            // Usuário saiu do roteiro (ou foi redirecionado): encerra o tour
            limparEstadoGlobal();
        }

        // 2. Primeira visita ao Início: inicia automaticamente
        if (pagina === 'inicio') {
            var uid = window._helpfullUsuarioId || '';
            var jaViu = localStorage.getItem('helpfull_tutorial_inicio_concluido') === 'true' ||
                localStorage.getItem('helpfull_tutorial_concluido') === 'true' ||
                (uid && localStorage.getItem('helpfull_tutorial_concluido_' + uid) === 'true');

            if (window._helpfullPrimeiraVez && !jaViu && !document.getElementById('modal-onboarding')) {
                setTimeout(function () { iniciarTutorial(false); }, 900);
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', aoCarregar);
    } else {
        aoCarregar();
    }
})();
