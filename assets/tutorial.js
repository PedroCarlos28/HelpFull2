/**
 * HELPFULL - SISTEMA DE TUTORIAL E TOUR GUIADO INTERATIVO UNIVERSAL
 * Proporciona um onboarding e tour acolhedor em TODAS as telas do HelpFull:
 * Início, Diário, Comunidade, Atividades, Helpy (ChatBOT), Perfil e Dispositivos.
 */
(function () {
    'use strict';

    var passoAtual = 0;
    var passosAtivos = [];
    var paginaAtual = 'inicio';
    var overlayEl = null;
    var cardEl = null;
    var glowEl = null;
    var cutoutRectEl = null;
    var redimensionando = false;

    // Coleção completa de tours especializados para todas as telas do sistema
    var colecaoPassos = {
        inicio: [
            {
                seletor: '#heroCarrosselCard',
                seletorFallback: '.hero-carrossel',
                titulo: 'Bem-vindo ao seu espaço seguro',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"></path></svg>',
                categoria: 'SEU REFÚGIO',
                desc: 'O HelpFull é o seu espaço diário para cuidar da saúde mental, organizar seus sentimentos e encontrar ferramentas acolhedoras para uma vida mais leve.',
                dica: 'Acompanhe as frases, reflexões e conteúdos inspiradores que se renovam no topo do site.',
                posicaoPreferida: 'bottom'
            },
            {
                seletor: '#cardTutorialDiario',
                seletorFallback: 'a[href="Diario.php"]',
                titulo: 'Seu Diário Emocional',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>',
                categoria: 'AUTOCUIDADO',
                desc: 'Registre como foi seu dia com emojis de humor e textos livres. Escrever alivia a mente e permite entender seus padrões emocionais ao longo das semanas.',
                dica: 'Você pode exportar relatórios completos em PDF com gráficos para acompanhar seu progresso ou levar à terapia.',
                posicaoPreferida: 'top'
            },
            {
                seletor: '#secaoTutorialHelpy',
                seletorFallback: 'a[href="ChatBOT.php"]',
                titulo: 'Converse com o Helpy a qualquer hora',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>',
                categoria: 'APOIO 24 HORAS',
                desc: 'Precisa desabafar na madrugada ou organizar ideias confusas? O Helpy é a nossa IA empática treinada para te escutar com carinho, paciência e sigilo total.',
                dica: 'O Helpy oferece exercícios imediatos de calma e reflexões gentis sempre que você precisar.',
                posicaoPreferida: 'top'
            },
            {
                seletor: '#cardTutorialComunidade',
                seletorFallback: 'a[href="Comunidade.php"]',
                titulo: 'Comunidade Acolhedora',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
                categoria: 'CONEXÃO E APOIO',
                desc: 'Compartilhe suas vitórias, desabafos e mensagens de apoio em um ambiente seguro, moderado e sem qualquer julgamento. Você nunca está sozinho.',
                dica: 'Envie apoio e carinho às histórias de outros membros da comunidade.',
                posicaoPreferida: 'top'
            },
            {
                seletor: '#cardTutorialAtividades',
                seletorFallback: 'a[href="Atividades.php"]',
                titulo: 'Alívio Rápido e Respiração',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.59 4.59A2 2 0 1 1 11 8H2m10.59 11.41A2 2 0 1 0 14 16H2m15.73-8.27A2.5 2.5 0 1 1 19.5 12H2"></path></svg>',
                categoria: 'CRISES E ANSIEDADE',
                desc: 'Exercícios guiados comprovados como a respiração 4-7-8, respiração quadrada, vídeos calmos e sons da natureza para aliviar a ansiedade imediatamente.',
                dica: 'Faça uma pausa de 3 minutos de respiração antes de reuniões importantes ou ao se deitar.',
                posicaoPreferida: 'top'
            },
            {
                seletor: '#menu',
                seletorFallback: '.navbar-topo',
                titulo: 'Navegação e Acessibilidade',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>',
                categoria: 'TUDO DO SEU JEITO',
                desc: 'Acesse seu Perfil com estatísticas, altere temas (claro/escuro), use o Tradutor de Libras oficial e configure o site para o seu conforto.',
                dica: 'Você pode rever este tour interativo a qualquer momento clicando no menu ou rodapé!',
                posicaoPreferida: 'bottom'
            }
        ],
        diario: [
            {
                seletor: '#bannerBoasVindasDiario',
                seletorFallback: '.caixa-agrupadora',
                titulo: 'Seu Diário Emocional',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>',
                categoria: 'BEM-ESTAR',
                desc: 'Este é o seu refúgio pessoal de escrita terapêutica. Escrever sobre seu dia alivia a ansiedade e organiza seus pensamentos com total sigilo.',
                dica: 'Seus registros são criptografados e apenas você tem acesso a eles.',
                posicaoPreferida: 'bottom'
            },
            {
                seletor: '#campoTexto',
                seletorFallback: '.textarea-diario',
                titulo: 'Área de Escrita Livre',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>',
                categoria: 'DESABAFO',
                desc: 'Digite aqui seus pensamentos, conquistas do dia ou sentimentos desafiadores. Não se preocupe com regras de escrita — coloque tudo para fora!',
                dica: 'Tente escrever pelo menos 3 linhas sobre algo bom ou marcante que aconteceu hoje.',
                posicaoPreferida: 'bottom'
            },
            {
                seletor: '.secao-emocoes-interna',
                seletorFallback: '.container-emocoes',
                titulo: 'Atribua seu Humor de Hoje',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>',
                categoria: 'AUTOAVALIAÇÃO',
                desc: 'Escolha a emoção que melhor representa seu estado neste momento. O HelpFull acompanha seus humores para montar gráficos no seu perfil.',
                dica: 'Reconhecer e nomear o que sentimos é um passo fundamental para o equilíbrio emocional.',
                posicaoPreferida: 'top'
            },
            {
                seletor: '.container-acoes',
                seletorFallback: '.btn-salvar',
                titulo: 'Salvar e Concluir Meta',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>',
                categoria: 'REGISTRO',
                desc: 'Ao clicar em Salvar, seu diário é guardado e você completa a meta diária de escrita, acumulando dias seguidos de autocuidado!',
                dica: 'Você também pode usar "Apagar tudo" se desejar recomeçar seu texto do zero.',
                posicaoPreferida: 'top'
            },
            {
                seletor: '.perfil-capsula',
                seletorFallback: '.nav-links a[href="Perfil.php"]',
                titulo: 'Consulte seu Histórico no Perfil',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>',
                categoria: 'RELATÓRIOS',
                desc: 'No seu Perfil você pode ver o calendário com todos os dias em que escreveu e exportar um relatório emocional em PDF para levar à terapia.',
                dica: 'Acesse seu perfil a qualquer momento clicando no seu avatar no menu!',
                posicaoPreferida: 'bottom'
            }
        ],
        comunidade: [
            {
                seletor: '#formCriarPost',
                seletorFallback: '#cardPlaceholder',
                titulo: 'Espaço de Apoio Comunitário',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
                categoria: 'ACOLHIMENTO',
                desc: 'A Comunidade é um local seguro e respeitoso para compartilhar vivências, pedir conselhos ou enviar mensagens de ânimo para quem precisa.',
                dica: 'Tolerância zero para ofensas: aqui todos estão unidos pelo mesmo propósito de bem-estar.',
                posicaoPreferida: 'bottom'
            },
            {
                seletor: '#mainInputPost',
                seletorFallback: '.input-texto-post',
                titulo: 'Crie uma Publicação',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>',
                categoria: 'COMPARTILHAR',
                desc: 'Digite sua experiência ou reflexão. Você pode também anexar imagens ou capas inspiradoras clicando no botão circular com imagem.',
                dica: 'Desabafar em comunidade ajuda a perceber que você nunca está sozinho(a).',
                posicaoPreferida: 'bottom'
            },
            {
                seletor: '#btnToggleImpacto',
                seletorFallback: '#btnToggleImpactoNav',
                titulo: 'Classifique o Impacto',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>',
                categoria: 'TAG EMOCIONAL',
                desc: 'Alterne a tag do seu post (ex: Bom, Desabafo, Superação) para que a comunidade entenda o contexto e ofereça o acolhimento adequado.',
                dica: 'Clique no botão para alternar rapidamente a classificação do seu post.',
                posicaoPreferida: 'bottom'
            },
            {
                seletor: '.post-card',
                seletorFallback: '.postagem-card',
                titulo: 'Reaja e Comente com Carinho',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>',
                categoria: 'EMPATIA',
                desc: 'Deixe reações acolhedoras e comentários generosos nos relatos dos colegas. Uma mensagem de apoio pode iluminar o dia de alguém.',
                dica: 'Você pode excluir ou gerenciar seus próprios posts através do botão de três pontinhos.',
                posicaoPreferida: 'top'
            }
        ],
        atividades: [
            {
                seletor: '#tabsElement',
                seletorFallback: '#tabs-original-container',
                titulo: 'Abas de Conteúdo Prático',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>',
                categoria: 'EXPLORAR',
                desc: 'Alterne facilmente entre a aba de Textos (artigos e técnicas) e a aba de Vídeos (sons da natureza, meditações e respiração guiada).',
                dica: 'Cada atividade concluída gera pontos e contabiliza tempo no seu progresso pessoal.',
                posicaoPreferida: 'bottom'
            },
            {
                seletor: '#sobre-textos',
                seletorFallback: '.sobre-content',
                titulo: 'Artigos e Guias Rápidos',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>',
                categoria: 'CONHECIMENTO',
                desc: 'Acesse artigos selecionados sobre manejo de crises, ansiedade, rotina do sono e autocompaixão, escritos de forma simples e direta.',
                dica: 'Pratique a leitura de um artigo curto pela manhã para inspirar um dia mais calmo.',
                posicaoPreferida: 'bottom'
            },
            {
                seletor: '.tab-btn[data-tab="videos"]',
                seletorFallback: '#tabsElement button:nth-child(3)',
                titulo: 'Vídeos Relaxantes e Sons',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polygon points="10 8 16 12 10 16 10 8"></polygon></svg>',
                categoria: 'ÁUDIO E VÍDEO',
                desc: 'Na aba de Vídeos você encontra paisagens naturais, chuva suave e músicas relaxantes em alta definição para desacelerar o ritmo.',
                dica: 'Use fones de ouvido para uma experiência de imersão e relaxamento ainda mais profunda.',
                posicaoPreferida: 'bottom'
            }
        ],
        chatbot: [
            {
                seletor: '.caixa-chat',
                seletorFallback: '#area-mensagens',
                titulo: 'Conheça o Helpy',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>',
                categoria: 'ASSISTENTE VIRTUAL',
                desc: 'O Helpy é o seu companheiro virtual de acolhimento. Treinado com empatia e escuta ativa, ele está disponível a qualquer hora do dia ou da noite.',
                dica: 'O Helpy é um apoio carinhoso e confidencial. Suas conversas não são compartilhadas com ninguém.',
                posicaoPreferida: 'bottom'
            },
            {
                seletor: '.area-input-chat',
                seletorFallback: '#inputChat',
                titulo: 'Campo de Conversa',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>',
                categoria: 'DESABAFE LIVREMENTE',
                desc: 'Digite como você está se sentindo, peça sugestões de técnicas de relaxamento ou apenas converse. Pressione Enter para enviar.',
                dica: 'Você pode pedir exercícios de respiração imediata digitando "estou ansioso".',
                posicaoPreferida: 'top'
            },
            {
                seletor: '.sidebar-historico',
                seletorFallback: '.btn-novo-chat',
                titulo: 'Histórico e Novo Chat',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>',
                categoria: 'ORGANIZAÇÃO',
                desc: 'Suas conversas passadas ficam salvas na barra lateral por data. Para começar um novo assunto do zero, clique em "Novo Chat".',
                dica: 'No celular, você pode abrir o histórico pelo botão superior de "Histórico".',
                posicaoPreferida: 'bottom'
            }
        ],
        perfil: [
            {
                seletor: '#cardSuaConta',
                seletorFallback: '.card-perfil',
                titulo: 'Seu Perfil e Dados',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
                categoria: 'CONTA',
                desc: 'Aqui você visualiza seus dados cadastrais, altera seu nome, foto de perfil e senha com total praticidade.',
                dica: 'Mantenha sua foto atualizada para interagir de forma personalizada na comunidade.',
                posicaoPreferida: 'bottom'
            },
            {
                seletor: '#balao2faContainer',
                seletorFallback: '.balao-2fa-container',
                titulo: 'Segurança em Duas Etapas (2FA)',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>',
                categoria: 'SEGURANÇA MÁXIMA',
                desc: 'Ative a verificação em duas etapas para exigir um código de 6 dígitos enviado ao seu e-mail a cada login. Seus diários ficam duplamente blindados!',
                dica: 'Basta um toque na chave para ativar ou desativar com facilidade.',
                posicaoPreferida: 'top'
            },
            {
                seletor: '#cardDispositivosPerfil',
                seletorFallback: '.card-dispositivos-preview',
                titulo: 'Dispositivos Conectados',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>',
                categoria: 'SESSÕES ATIVAS',
                desc: 'Acompanhe quantos navegadores e aparelhos estão autorizados na sua conta. Você pode desconectar aparelhos antigos a qualquer momento.',
                dica: 'Clique em "Gerenciar e Desconectar" para acessar a central completa de dispositivos.',
                posicaoPreferida: 'top'
            },
            {
                seletor: '.stats-container',
                seletorFallback: '.card-perfil.stats-container',
                titulo: 'Estatísticas de Autocuidado',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>',
                categoria: 'SUA EVOLUÇÃO',
                desc: 'Veja o total de diários escritos, emoções registradas, conversas com o Helpy e curtidas carinhosas recebidas da comunidade.',
                dica: 'Cada número representa um passo a mais na sua jornada de autoconhecimento.',
                posicaoPreferida: 'top'
            },
            {
                seletor: '.grid-cal-detalhes',
                seletorFallback: '.graficos-container',
                titulo: 'Calendário e Gráficos de Humor',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
                categoria: 'VISÃO TEMPORAL',
                desc: 'Navegue pelos dias do mês para reler o que você escreveu e acompanhe os gráficos de evolução das suas emoções.',
                dica: 'Clique em qualquer dia com registro no calendário para ver os detalhes daquele dia.',
                posicaoPreferida: 'top'
            }
        ],
        dispositivos: [
            {
                seletor: '.card-dispositivos-header',
                seletorFallback: '.conteudo-site',
                titulo: 'Central de Dispositivos',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>',
                categoria: 'SEGURANÇA',
                desc: 'Aqui você tem controle total sobre quais computadores e celulares têm acesso permitido à sua conta HelpFull.',
                dica: 'Se você usou um computador público ou de terceiros, encerre o acesso por aqui com tranquilidade.',
                posicaoPreferida: 'bottom'
            },
            {
                seletor: '.card-sessao-atual',
                seletorFallback: '.sessao-item.atual',
                titulo: 'Sua Conexão Atual',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>',
                categoria: 'ESTE APARELHO',
                desc: 'Identifica o navegador, sistema operacional e horário do seu login atual. Esta conexão é marcada em destaque como ativa.',
                dica: 'Essa sessão permanece segura através de chaves exclusivas de autenticação.',
                posicaoPreferida: 'bottom'
            },
            {
                seletor: '.card-outros-dispositivos',
                seletorFallback: '.lista-dispositivos',
                titulo: 'Outros Aparelhos e Desconexão',
                icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line></svg>',
                categoria: 'CONTROLE TOTAL',
                desc: 'Lista os outros dispositivos conectados. Você pode encerrar sessões individualmente ou desconectar todos os outros aparelhos de uma só vez.',
                dica: 'Muito útil caso você perca um celular ou queira garantir que ninguém mais esteja conectado.',
                posicaoPreferida: 'top'
            }
        ]
    };

    function detectarPaginaAtual() {
        var path = window.location.pathname.toLowerCase();
        if (path.includes('diario')) return 'diario';
        if (path.includes('comunidade')) return 'comunidade';
        if (path.includes('atividades')) return 'atividades';
        if (path.includes('chatbot') || path.includes('chat')) return 'chatbot';
        if (path.includes('dispositivos')) return 'dispositivos';
        if (path.includes('perfil')) return 'perfil';
        return 'inicio';
    }

    function obterNomePaginaAmigavel(chave) {
        switch (chave) {
            case 'diario': return 'Diário';
            case 'comunidade': return 'Comunidade';
            case 'atividades': return 'Atividades';
            case 'chatbot': return 'Helpy Chat';
            case 'perfil': return 'Perfil';
            case 'dispositivos': return 'Dispositivos';
            default: return 'Início';
        }
    }

    function criarEstruturaDOM() {
        if (overlayEl) return;

        // Overlay com SVG Mask
        overlayEl = document.createElement('div');
        overlayEl.id = 'tutorial-overlay';
        overlayEl.innerHTML = `
            <svg class="tutorial-spotlight-svg" preserveAspectRatio="none">
                <defs>
                    <mask id="tutorial-spotlight-mask" x="0" y="0" width="100%" height="100%">
                        <rect x="0" y="0" width="100%" height="100%" fill="#ffffff" />
                        <rect class="tutorial-cutout-rect" id="tutorial-cutout" x="0" y="0" width="0" height="0" rx="18" ry="18" fill="#000000" />
                    </mask>
                </defs>
                <rect x="0" y="0" width="100%" height="100%" fill="rgba(8, 14, 22, 0.78)" mask="url(#tutorial-spotlight-mask)" />
            </svg>
            <div id="tutorial-target-glow" style="display: none;"></div>
        `;
        document.body.appendChild(overlayEl);

        cutoutRectEl = document.getElementById('tutorial-cutout');
        glowEl = document.getElementById('tutorial-target-glow');

        // Card flutuante
        cardEl = document.createElement('div');
        cardEl.id = 'tutorial-card';
        cardEl.setAttribute('role', 'dialog');
        cardEl.setAttribute('aria-modal', 'true');
        cardEl.setAttribute('aria-label', 'Tutorial interativo do HelpFull');
        cardEl.innerHTML = `
            <div class="tutorial-progress-bar">
                <div class="tutorial-progress-fill" id="tutorialProgressFill"></div>
            </div>
            <div class="tutorial-card-body">
                <div class="tutorial-card-header">
                    <span class="tutorial-badge" id="tutorialBadge">✦ PASSO 1 DE 5</span>
                    <button type="button" class="tutorial-btn-fechar" id="tutorialBtnFechar" aria-label="Fechar tutorial" title="Pular tutorial">×</button>
                </div>
                <div class="tutorial-title-block">
                    <div class="tutorial-step-icon" id="tutorialStepIcon"></div>
                    <div>
                        <h3 id="tutorialTitulo">Carregando...</h3>
                    </div>
                </div>
                <p class="tutorial-desc" id="tutorialDesc"></p>
                <div class="tutorial-dica-box" id="tutorialDicaBox">
                    <span class="tutorial-dica-icone">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6"></path><path d="M10 22h4"></path><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5.76.76 1.23 1.52 1.41 2.5"></path></svg>
                    </span>
                    <span id="tutorialDicaTexto"></span>
                </div>
                <div class="tutorial-card-footer">
                    <div class="tutorial-dots" id="tutorialDots"></div>
                    <div class="tutorial-nav-botoes">
                        <button type="button" class="tutorial-btn-voltar" id="tutorialBtnVoltar">Voltar</button>
                        <button type="button" class="tutorial-btn-proximo" id="tutorialBtnProximo">Próximo →</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(cardEl);

        // Eventos dos botões do card
        document.getElementById('tutorialBtnFechar').addEventListener('click', finalizarTutorial);
        document.getElementById('tutorialBtnVoltar').addEventListener('click', passoAnterior);
        document.getElementById('tutorialBtnProximo').addEventListener('click', proximoPasso);

        // Suporte ao teclado (Escape para fechar, setas para navegar)
        document.addEventListener('keydown', onKeyDownTutorial);
        window.addEventListener('resize', onResizeTutorial);
        window.addEventListener('scroll', onScrollTutorial, { passive: true });
    }

    function onKeyDownTutorial(e) {
        if (!overlayEl || !overlayEl.classList.contains('ativo')) return;
        if (e.key === 'Escape') {
            e.preventDefault();
            finalizarTutorial();
        } else if (e.key === 'ArrowRight' || e.key === 'Enter') {
            e.preventDefault();
            proximoPasso();
        } else if (e.key === 'ArrowLeft') {
            e.preventDefault();
            passoAnterior();
        }
    }

    function onResizeTutorial() {
        if (!overlayEl || !overlayEl.classList.contains('ativo')) return;
        if (redimensionando) return;
        redimensionando = true;
        requestAnimationFrame(function () {
            atualizarPosicoesPasso(passoAtual, false);
            redimensionando = false;
        });
    }

    function onScrollTutorial() {
        if (!overlayEl || !overlayEl.classList.contains('ativo')) return;
        requestAnimationFrame(function () {
            atualizarPosicoesPasso(passoAtual, false);
        });
    }

    function obterElementoAlvo(passo) {
        var el = null;
        if (passo.seletor) {
            try { el = document.querySelector(passo.seletor); } catch (e) {}
        }
        if (!el && passo.seletorFallback) {
            try { el = document.querySelector(passo.seletorFallback); } catch (e) {}
        }
        return el;
    }

    function renderizarDots(total, atual) {
        var container = document.getElementById('tutorialDots');
        if (!container) return;
        var html = '';
        for (var i = 0; i < total; i++) {
            html += `<span class="tutorial-dot ${i === atual ? 'ativo' : ''}" data-step="${i}"></span>`;
        }
        container.innerHTML = html;
    }

    function atualizarPosicoesPasso(index, suave) {
        var passo = passosAtivos[index];
        if (!passo) return;

        var alvo = obterElementoAlvo(passo);
        var padding = 12;
        var rect;

        if (alvo) {
            rect = alvo.getBoundingClientRect();
        } else {
            // Se o elemento não existir nesta resolução, centraliza na tela com visual de destaque
            var vw = window.innerWidth;
            var vh = window.innerHeight;
            rect = {
                top: vh / 2 - 60,
                bottom: vh / 2 + 60,
                left: vw / 2 - 160,
                right: vw / 2 + 160,
                width: 320,
                height: 120
            };
        }

        var x = Math.max(8, rect.left - padding);
        var y = Math.max(8, rect.top - padding);
        var w = Math.min(window.innerWidth - 16, rect.width + padding * 2);
        var h = Math.min(window.innerHeight - 16, rect.height + padding * 2);

        // Atualiza o SVG Cutout
        if (cutoutRectEl) {
            cutoutRectEl.setAttribute('x', x);
            cutoutRectEl.setAttribute('y', y);
            cutoutRectEl.setAttribute('width', w);
            cutoutRectEl.setAttribute('height', h);
            cutoutRectEl.setAttribute('rx', 18);
            cutoutRectEl.setAttribute('ry', 18);
        }

        // Atualiza o anel pulsante
        if (glowEl) {
            glowEl.style.display = 'block';
            glowEl.style.left = x + 'px';
            glowEl.style.top = y + 'px';
            glowEl.style.width = w + 'px';
            glowEl.style.height = h + 'px';
        }

        // Posiciona o Card flutuante
        if (cardEl) {
            var isMobile = window.innerWidth <= 768;
            if (isMobile) {
                cardEl.style.top = '';
                cardEl.style.left = '';
                cardEl.style.right = '';
                cardEl.style.bottom = '16px';
            } else {
                var cardW = 410;
                var cardH = cardEl.offsetHeight || 280;
                var cardX = x + w / 2 - cardW / 2;
                var cardY = y + h + 18; // Padrão: abaixo do elemento

                // Ajusta se sair pelos lados
                if (cardX < 20) cardX = 20;
                if (cardX + cardW > window.innerWidth - 20) cardX = window.innerWidth - cardW - 20;

                // Se não couber embaixo ou a posição preferida for top:
                if (passo.posicaoPreferida === 'top' || (cardY + cardH > window.innerHeight - 20 && y - cardH - 18 > 20)) {
                    cardY = y - cardH - 18;
                }

                // Protege contra borda superior
                if (cardY < 20) cardY = 20;

                cardEl.style.bottom = '';
                cardEl.style.right = '';
                cardEl.style.left = Math.round(cardX) + 'px';
                cardEl.style.top = Math.round(cardY) + 'px';
            }
        }
    }

    function exibirPasso(index) {
        if (index < 0 || index >= passosAtivos.length) return;
        passoAtual = index;

        var passo = passosAtivos[index];
        var alvo = obterElementoAlvo(passo);

        // Se o elemento estiver fora do viewport, rola suavemente até ele
        if (alvo) {
            var r = alvo.getBoundingClientRect();
            var isInView = r.top >= 60 && r.bottom <= window.innerHeight - 100;
            if (!isInView) {
                alvo.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        // Atualiza textos e conteúdo do card
        var nomePag = obterNomePaginaAmigavel(paginaAtual).toUpperCase();
        document.getElementById('tutorialBadge').innerHTML = `✦ PASSO ${index + 1} DE ${passosAtivos.length} • ${passo.categoria || nomePag}`;
        document.getElementById('tutorialStepIcon').innerHTML = passo.icone;
        document.getElementById('tutorialTitulo').textContent = passo.titulo;
        document.getElementById('tutorialDesc').textContent = passo.desc;
        document.getElementById('tutorialDicaTexto').textContent = passo.dica;

        // Barra de progresso
        var pct = Math.round(((index + 1) / passosAtivos.length) * 100);
        document.getElementById('tutorialProgressFill').style.width = pct + '%';

        // Dots
        renderizarDots(passosAtivos.length, index);

        // Botões
        var btnVoltar = document.getElementById('tutorialBtnVoltar');
        var btnProximo = document.getElementById('tutorialBtnProximo');

        btnVoltar.disabled = index === 0;

        if (index === passosAtivos.length - 1) {
            btnProximo.innerHTML = 'Concluir Tour ✨';
            btnProximo.style.background = 'linear-gradient(135deg, #10b981, #059669)';
        } else {
            btnProximo.innerHTML = 'Próximo →';
            btnProximo.style.background = '';
        }

        // Posiciona visualmente com pequeno delay para acomodar scroll suave
        setTimeout(function () {
            atualizarPosicoesPasso(index, true);
        }, 130);
    }

    function proximoPasso() {
        if (passoAtual < passosAtivos.length - 1) {
            exibirPasso(passoAtual + 1);
        } else {
            finalizarTutorial();
        }
    }

    function passoAnterior() {
        if (passoAtual > 0) {
            exibirPasso(passoAtual - 1);
        }
    }

    function iniciarTutorial(forcar, tipoPersonalizado) {
        criarEstruturaDOM();

        paginaAtual = tipoPersonalizado || detectarPaginaAtual();
        passosAtivos = colecaoPassos[paginaAtual] || colecaoPassos['inicio'];

        // Se forçado (ex: clique no botão), ignora verificação de concluído
        if (!forcar) {
            var uid = window._helpfullUsuarioId || '';
            var concluido = localStorage.getItem('helpfull_tutorial_' + paginaAtual + '_concluido') === 'true' ||
                            (uid && localStorage.getItem('helpfull_tutorial_' + paginaAtual + '_concluido_' + uid) === 'true');
            if (concluido) return;
        }

        // Fecha outros modais se estiverem abertos
        var modalOnboarding = document.getElementById('modal-onboarding');
        if (modalOnboarding && modalOnboarding.classList.contains('aberto')) {
            modalOnboarding.classList.remove('aberto');
        }
        if (window.fecharPainelAcessibilidade) {
            window.fecharPainelAcessibilidade();
        }

        overlayEl.classList.add('ativo');
        cardEl.classList.add('ativo');
        document.body.style.overflow = 'hidden';

        exibirPasso(0);
    }

    function finalizarTutorial() {
        if (overlayEl) overlayEl.classList.remove('ativo');
        if (cardEl) cardEl.classList.remove('ativo');
        if (glowEl) glowEl.style.display = 'none';

        document.body.style.overflow = '';

        // Marca como concluído no localStorage para esta página
        var uid = window._helpfullUsuarioId || '';
        localStorage.setItem('helpfull_tutorial_' + paginaAtual + '_concluido', 'true');
        if (uid) {
            localStorage.setItem('helpfull_tutorial_' + paginaAtual + '_concluido_' + uid, 'true');
        }

        if (paginaAtual === 'inicio') {
            localStorage.setItem('helpfull_tutorial_concluido', 'true');
            if (uid) {
                localStorage.setItem('helpfull_onboarding_' + uid, 'true');
            }
            try {
                fetch('concluir_onboarding.php', { method: 'POST' }).catch(function () {});
            } catch (e) {}
        }

        // Notificação acolhedora de sucesso
        var nomeAmigavel = obterNomePaginaAmigavel(paginaAtual);
        if (window.mostrarNotificacaoAtiva) {
            window.mostrarNotificacaoAtiva(`Tour do ${nomeAmigavel} concluído! Aproveite cada detalhe deste espaço com calma. ✨`, window.location.pathname);
        }
    }

    // Exportações globais
    window.iniciarTutorial = iniciarTutorial;
    window.finalizarTutorial = finalizarTutorial;
    window.proximoPassoTutorial = proximoPasso;
    window.passoAnteriorTutorial = passoAnterior;

    // Inicialização automática apenas para primeira vez no Início
    document.addEventListener('DOMContentLoaded', function () {
        var pag = detectarPaginaAtual();
        if (pag === 'inicio') {
            var uid = window._helpfullUsuarioId || '';
            var jaViu = localStorage.getItem('helpfull_tutorial_inicio_concluido') === 'true' ||
                        localStorage.getItem('helpfull_tutorial_concluido') === 'true' ||
                        (uid && localStorage.getItem('helpfull_tutorial_concluido_' + uid) === 'true');

            if (window._helpfullPrimeiraVez && !jaViu) {
                var modalOnboarding = document.getElementById('modal-onboarding');
                if (!modalOnboarding) {
                    setTimeout(function () {
                        iniciarTutorial(false, 'inicio');
                    }, 900);
                }
            }
        }
    });
})();
