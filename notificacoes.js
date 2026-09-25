/* =============================================================
   HelpFull — Sistema de Notificações (v2 - 2026-09-16)
   ============================================================= */
(function () {
    'use strict';

    /* ── Timers e estado ──────────────────────────────────── */
    var _hoverTimer   = null;
    var _toastTimer   = null;
    var _toastQueue   = [];
    var _toastRunning = false;
    var _totalNotifs  = 0;

    /* ── Helpers de elementos ─────────────────────────────── */
    function getBtnSino()   { return document.getElementById('btnNotificacaoDetached'); }
    function getToast()     { return document.getElementById('notificacaoHelpFull'); }
    function getSininho()   { return document.getElementById('sininhoNavbar'); }
    function getPainel()    { return document.getElementById('painelNotificacoes'); }
    function getContainer() { return document.getElementById('containerListaNotificacoes'); }

    /* ── Badge sininho ────────────────────────────────────── */
    function atualizarBadge() {
        var s = getSininho();
        if (s) s.style.display = _totalNotifs > 0 ? 'flex' : 'none';
    }

    /* ── Botão sino flutuante ─────────────────────────────── */
    function mostrarBtnSino() {
        var btn = getBtnSino();
        if (!btn) return;
        btn.classList.add('visivel');
        clearTimeout(_hoverTimer);
        _hoverTimer = setTimeout(function () {
            var p = getPainel();
            if (!p || !p.classList.contains('aberto')) esconderBtnSino();
        }, 4000);
    }

    function esconderBtnSino() {
        var btn = getBtnSino();
        if (btn) btn.classList.remove('visivel');
    }

    /* ── Painel ───────────────────────────────────────────── */
    window.togglePainelNotificacoes = function () {
        var painel = getPainel();
        if (!painel) return;
        var abrindo = !painel.classList.contains('aberto');
        painel.classList.toggle('aberto');
        if (abrindo) {
            clearTimeout(_hoverTimer);
            mostrarBtnSino();
            fetch('notificacao_ia.php?acao=marcar_lidas').catch(function () {});
            fetch('social_notificacoes_proc.php?acao=marcar_lidas').catch(function () {});
        } else {
            _hoverTimer = setTimeout(esconderBtnSino, 1500);
        }
    };

    window.limparNotificacoes = function () {
        if (!confirm('Deseja limpar as notificações?')) return;
        fetch('notificacao_ia.php?acao=limpar').catch(function () {});
        fetch('social_notificacoes_proc.php?acao=limpar').catch(function () {});
        _totalNotifs = 0;
        atualizarBadge();
        esconderBtnSino();
        var p = getPainel();
        if (p) p.classList.remove('aberto');
        var c = getContainer();
        if (c) c.innerHTML = '<p style="font-size:0.8rem;text-align:left;opacity:0.6;">Nenhuma notificação nova.</p>';
    };

    /* ── Popular painel ───────────────────────────────────── */
    function popularPainel(data, append) {
        var c = getContainer();
        if (!c || !data) return;
        if (!append) {
            c.innerHTML = '';
        } else {
            var ph = c.querySelector('p');
            if (ph) ph.remove();
        }
        var item = document.createElement('div');
        item.className = 'item-notificacao ' + (data.intensidade || 'baixa');
        var href = data.link || '#';
        var btnHtml = (href !== '#')
            ? '<a href="' + _esc(href) + '" class="notif-link-btn">' + _esc(data.textoBotao || 'Ver') + '</a>'
            : '';
        item.innerHTML =
            '<div class="barra-intensidade"></div>' +
            '<div class="notif-content">' +
            '<strong>' + _esc(data.titulo || 'Notificação') + '</strong>' +
            '<p>' + _esc(data.mensagem || '') + '</p>' +
            '</div>' + btnHtml;
        if (href !== '#') {
            item.style.cursor = 'pointer';
            item.addEventListener('click', function (e) {
                if (!e.target.classList.contains('notif-link-btn'))
                    window.location.href = href;
            });
        }
        c.appendChild(item);
        _totalNotifs++;
        atualizarBadge();
    }

    function _esc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    /* ── Toast (com fila) ─────────────────────────────────── */
    function mostrarToast(data) {
        if (!data) return;
        _toastQueue.push(data);
        if (!_toastRunning) _nextToast();
    }

    function _nextToast() {
        if (!_toastQueue.length) { _toastRunning = false; return; }
        _toastRunning = true;
        var data = _toastQueue.shift();
        var toast = getToast();
        if (!toast) { _nextToast(); return; }
        toast.className = 'toast-notificacao';
        if (data.intensidade) toast.classList.add(data.intensidade);
        var txt  = document.getElementById('textoNotificacao');
        var link = document.getElementById('linkNotificacao');
        if (txt)  txt.innerHTML  = _esc(data.mensagem || '');
        if (link) {
            link.href      = data.link      || '#';
            link.innerText = data.textoBotao || 'Ver';
            link.style.display = (data.link && data.link !== '#') ? '' : 'none';
        }
        toast.classList.add('mostrar');
        clearTimeout(_toastTimer);
        _toastTimer = setTimeout(function () {
            toast.classList.remove('mostrar');
            setTimeout(_nextToast, 600);
        }, 5000);
    }

    function fecharToast() {
        var t = getToast();
        if (t) t.classList.remove('mostrar');
        clearTimeout(_toastTimer);
    }

    /* Expõe globalmente para Diario.php e outras páginas */
    window.mostrarNotificacaoAtiva = mostrarToast;
    window.popularPainel           = popularPainel;
    window.fecharNotificacao       = fecharToast;

    /* ── Hover no perfil — SEMPRE mostra o sino ───────────── */
    function vincularHoverPerfil() {
        var cap = document.querySelector('.perfil-capsula');
        if (!cap) return;
        var target = cap.closest('a') || cap;
        target.addEventListener('mouseenter', function () { mostrarBtnSino(); });
        target.addEventListener('mouseleave', function () {
            var p = getPainel();
            if (!p || !p.classList.contains('aberto')) {
                clearTimeout(_hoverTimer);
                _hoverTimer = setTimeout(esconderBtnSino, 2000);
            }
        });
    }

    /* ── Carregar notificações do servidor ────────────────── */
    async function carregarNotificacoes() {
        // 1) IA — emoções negativas no diário
        try {
            var r    = await fetch('notificacao_ia.php');
            var data = await r.json();
            if (data && data.mostrar) {
                popularPainel(data, false);
                if (data.nova) mostrarToast(data);
            }
        } catch(e) {}

        // 2) Social — likes, reações
        try {
            var rs  = await fetch('social_notificacoes_proc.php');
            var soc = await rs.json();
            if (Array.isArray(soc) && soc.length > 0) {
                soc.forEach(function (n) { popularPainel(n, true); });
            }
        } catch(e) {}

        // garante placeholder se sem notificações
        var c = getContainer();
        if (c && c.children.length === 0) {
            c.innerHTML = '<p style="font-size:0.8rem;text-align:left;opacity:0.6;">Nenhuma notificação nova.</p>';
        }
    }

    /* ── Acessibilidade ───────────────────────────────────── */
    var prefAcess = {
        escuro:       'acessibilidade-escuro',
        contraste:    'acessibilidade-contraste',
        textoGrande:  'acessibilidade-texto-grande',
        sublinhar:    'acessibilidade-sublinhar',
        semAnimacao:  'acessibilidade-sem-animacao'
    };

    var tradInterface = {
        'pt-BR': {
            idioma:'Idioma', acessibilidade:'Acessibilidade',
            preferencias:'Preferências aplicadas em todo o site',
            fechar:'Fechar acessibilidade',
            escuro:['Modo noturno','Fundo preto e superfícies cinza escuro'],
            contraste:['Mais contraste','Realça bordas, textos e links'],
            textoGrande:['Texto maior','Aumenta a leitura sem trocar de página'],
            sublinhar:['Sublinhar links','Facilita localizar elementos clicáveis'],
            semAnimacao:['Reduzir animações','Diminui movimentos e transições'],
            restaurar:'Restaurar padrão',
            nav:{'Diario.php':'Diário','Comunidade.php':'Comunidade','ChatBOT.php':'Helpy','Atividades.php':'Atividades','Perfil.php':'Perfil','inicio.php':'Início'}
        },
        en: {
            idioma:'Language', acessibilidade:'Accessibility',
            preferencias:'Preferences applied across the site',
            fechar:'Close accessibility options',
            escuro:['Dark mode','Dark background and gray surfaces'],
            contraste:['High contrast','Highlights borders, text and links'],
            textoGrande:['Larger text','Improves readability without changing pages'],
            sublinhar:['Underline links','Makes clickable elements easier to find'],
            semAnimacao:['Reduce motion','Reduces movement and transitions'],
            restaurar:'Restore defaults',
            nav:{'Diario.php':'Journal','Comunidade.php':'Community','ChatBOT.php':'Helpy','Atividades.php':'Activities','Perfil.php':'Profile','inicio.php':'Home'}
        }
    };

    var dicionarioEN = {
        // Navegação e cabeçalho
        'Início': 'Home',
        'Diário': 'Journal',
        'Comunidade': 'Community',
        'Helpy': 'Helpy',
        'Atividades': 'Activities',
        'Adicionais': 'Activities',
        'Perfil': 'Profile',
        'Configurações': 'Settings',
        'Entrar': 'Sign in',
        'Sair': 'Log out',
        'Sair da conta': 'Log out',
        'Notificações': 'Notifications',
        'Limpar': 'Clear',
        'Nenhuma notificação nova.': 'No new notifications.',
        'Ver Perfil': 'View Profile',

        // Acessibilidade e Preferências
        'Idioma': 'Language',
        'Acessibilidade': 'Accessibility',
        'Preferências aplicadas em todo o site': 'Preferences applied across the site',
        'Fechar acessibilidade': 'Close accessibility',
        'Modo noturno': 'Dark mode',
        'Fundo preto e superfícies cinza escuro': 'Dark background and deep gray surfaces',
        'Mais contraste': 'High contrast',
        'Realça bordas, textos e links': 'Highlights borders, text and links',
        'Texto maior': 'Larger text',
        'Aumenta a leitura sem trocar de página': 'Enhances readability without changing pages',
        'Sublinhar links': 'Underline links',
        'Facilita localizar elementos clicáveis': 'Makes clickable elements easier to locate',
        'Reduzir animações': 'Reduce motion',
        'Diminui movimentos e transições': 'Reduces movement and transitions',
        'Restaurar padrão': 'Restore defaults',

        // Atividades - Geral e Abas
        'Textos': 'Texts',
        'Videos': 'Videos',
        'Vídeos': 'Videos',
        'Sobre essa aba:': 'About this section:',
        'Aqui reunimos materiais para apoiar sua jornada de autoconhecimento. Explore artigos rápidos, técnicas de relaxamento e reflexões selecionadas para ajudar a trazer mais clareza, calma e leveza ao seu dia a dia.':
            'Here we have gathered materials to support your journey of self-discovery. Explore quick articles, relaxation techniques, and selected reflections to bring more clarity, calm, and lightness to your daily life.',
        'Aqui reunimos conteúdos visuais para apoiar sua jornada de bem-estar. Assista a vídeos curtos com técnicas de respiração, exercícios de foco e reflexões guiadas para ajudar a acalmar a mente e trazer mais leveza ao seu dia.':
            'Here we gather visual content to support your wellness journey. Watch short videos with breathing techniques, focus exercises, and guided reflections to help calm your mind and bring more ease to your day.',
        '🔍 Pesquisar conteúdos': '🔍 Search content',
        '🔍 PESQUISAR CONTEÚDOS': '🔍 SEARCH CONTENT',
        'Pesquisar conteúdos': 'Search content',
        'Ex: ansiedade, meditação, autocuidado...': 'Ex: anxiety, meditation, self-care...',
        'Buscar': 'Search',
        'Buscando artigos...': 'Searching articles...',
        'Textos separados por nós': 'Curated texts',
        'A Técnica 4-7-8 para Alívio Imediato': 'The 4-7-8 Technique for Immediate Relief',
        'Quando a mente acelera, sua respiração é sua maior aliada. A técnica 4-7-8 atua como um calmante natural para o sistema nervoso. Funciona assim: inspire silenciosamente pelo nariz contando até 4; prenda a respiração contando até 7; e expire completamente pela boca contando até 8. Repita esse ciclo quatro vezes. Essa prática simples ajuda a desacelerar os batimentos cardíacos e traz a mente de volta para o momento presente, sendo excelente para praticar em momentos de tensão ou antes de dormir.':
            'When your mind races, your breath is your greatest ally. The 4-7-8 technique acts as a natural calmative for your nervous system. It works like this: inhale quietly through your nose for a count of 4; hold your breath for a count of 7; and exhale completely through your mouth for a count of 8. Repeat this cycle four times. This simple practice helps slow your heart rate and brings you back to the present moment, making it great during stressful times or before sleeping.',
        'O poder das pausas e da autocompaixão': 'The Power of Pauses and Self-Compassion',
        'É muito comum nos cobrarmos excessivamente quando as coisas não saem como o planejado. A autocompaixão não é ter pena de si mesmo, mas sim se tratar com a mesma gentileza que você trataria um amigo em dificuldade. Se o dia foi pesado, não se culpe por não ter tido o rendimento que gostaria. Reconheça seu esforço, permita-se pausar e lembre-se de que o cuidado com a mente envolve, acima de tudo, aceitar nossos momentos de descanso. Um dia difícil não define sua jornada.':
            'It is very common to be overly demanding of ourselves when things do not go as planned. Self-compassion is not feeling sorry for yourself, but rather treating yourself with the same kindness you would offer a struggling friend. If the day was heavy, do not blame yourself for not being as productive as you wanted. Acknowledge your effort, allow yourself to pause, and remember that caring for your mind involves accepting moments of rest. One hard day does not define your journey.',
        '🎬 Pesquisar vídeos e práticas': '🎬 Search videos and practices',
        '🎬 PESQUISAR VÍDEOS E PRÁTICAS': '🎬 SEARCH VIDEOS AND PRACTICES',
        'Pesquisar vídeos e práticas': 'Search videos and practices',
        'Ex: meditação guiada, técnicas de ansiedade...': 'Ex: guided meditation, anxiety techniques...',
        'Buscando vídeos...': 'Searching videos...',
        'Vídeos separados por nós': 'Curated videos',
        'Práticas de Respiração e Foco': 'Breathing and Focus Practices',
        'Técnicas de respiração guiada para reduzir a ansiedade e ancorar no momento presente. Práticas curtas focadas apenas no controle do ar e relaxamento mental, ideais para acalmar os pensamentos.':
            'Guided breathing techniques to reduce anxiety and anchor yourself in the present moment. Short practices focused on airflow control and mental relaxation, ideal for calming your mind.',
        'Entendendo a Mente': 'Understanding the Mind',
        'Vídeos educativos sobre como nossa mente processa emoções e a importância do descanso mental. Perfeito para entender melhor seus próprios sentimentos de forma leve e didática.':
            'Educational videos on how our mind processes emotions and the importance of mental rest. Perfect for understanding your feelings in an engaging, easy-to-follow way.',
        'Ler artigo completo': 'Read full article',
        'Fechar artigo': 'Close article',
        'Nenhum artigo encontrado.': 'No articles found.',
        'Nenhum vídeo encontrado.': 'No videos found.',
        'Artigo Completo': 'Full Article',
        'Fonte original': 'Original source',

        // Perfil
        'Sua Conta:': 'Your Account:',
        'Nome:': 'Name:',
        'Email:': 'Email:',
        'Senha:': 'Password:',
        'Editar': 'Edit',
        'Salvar': 'Save',
        'Salvar alterações': 'Save changes',
        'Cancelar': 'Cancel',
        'Apagar Conta': 'Delete Account',
        'Total de diários registrados': 'Total journals registered',
        'Total de diários': 'Total journals',
        'registrados': 'registered',
        'Total de emoções registradas': 'Total emotions logged',
        'Total de emoções': 'Total emotions',
        'registradas': 'logged',
        'Total de conversas com o Helpy': 'Total chats with Helpy',
        'Total de conversas': 'Total chats',
        'com o Helpy': 'with Helpy',
        'Total de posts na comunidade': 'Total posts in community',
        'Total de posts na': 'Total posts in',
        'comunidade': 'community',
        'Total de curtidas recebidas': 'Total likes received',
        'Total de curtidas': 'Total likes',
        'recebidas': 'received',
        'Metas': 'Goals',
        'Anotar uma pequena vitória de hoje no Diário (por menor que seja).': 'Write down a small victory from today in your Journal (no matter how small).',
        "Dar um 'oi' para o Helpy e desabafar por 2 minutinhos.": 'Say hello to Helpy and vent for 2 minutes.',
        'Registrar a emoção que estou sentindo agora no meu Diário.': 'Log the emotion you are feeling right now in your Journal.',
        'Tirar 1 minutinho para assistir a um vídeo de respiração na aba Adicionais': 'Take 1 minute to watch a breathing video in the Activities tab.',
        'Tirar 1 minutinho para assistir a um vídeo de respiração na aba Atividades': 'Take 1 minute to watch a breathing video in the Activities tab.',
        'Calendário': 'Calendar',
        'Detalhes do dia': 'Day details',
        'Gráficos': 'Charts',
        'Diários': 'Journals',
        'Emoções do Mês': 'Monthly Emotions',
        'Mês': 'Month',
        'Ano': 'Year',
        'Nenhum registro encontrado para este mês.': 'No records found for this month.',
        'Selecione um dia para ver os diários.': 'Select a day to view entries.',
        'Nenhum diário registrado neste dia.': 'No journal entries on this day.',

        // Diário
        'Como você está se sentindo hoje?': 'How are you feeling today?',
        'Atribua uma emoção ao seu diario:': 'Assign an emotion to your journal:',
        'Atribua uma emoção ao seu diário:': 'Assign an emotion to your journal:',
        'Irritado': 'Irritated',
        'Ansioso': 'Anxious',
        'Feliz': 'Happy',
        'Calmo': 'Calm',
        'Triste': 'Sad',
        'Amoroso': 'Loving',
        'Título do seu dia...': 'Title of your day...',
        'Escreva sobre seus pensamentos, sentimentos ou acontecimentos...': 'Write about your thoughts, feelings, or events...',
        'Apagar tudo': 'Clear all',
        'Salvar no diário': 'Save to journal',
        'Registros anteriores': 'Previous entries',
        'Filtrar por emoção:': 'Filter by emotion:',
        'Todas as emoções': 'All emotions',
        'Nenhum diário registrado ainda.': 'No journal entries recorded yet.',
        'Escrever em um diário pode ajudar você a entender melhor seus pensamentos e sentimentos.': 'Writing in a journal can help you better understand your thoughts and feelings.',
        'Reserve alguns minutos para escrever sobre o seu dia, concentrando-se no que correu bem, no que o desafiou e como você se sentiu. Você também pode selecionar as emoções que melhor descrevem seu humor hoje.':
            'Take a few minutes to write about your day, focusing on what went well, what challenged you, and how you felt. You can also select the emotions that best describe your mood today.',

        // Comunidade
        'Comunidade HelpFull': 'HelpFull Community',
        'Compartilhe suas experiências, desabafe e encontre apoio mútuo em um espaço seguro e acolhedor.': 'Share your experiences, vent, and find mutual support in a safe and welcoming space.',
        'Criar publicação': 'Create post',
        'O que você gostaria de compartilhar ou desabafar?': 'What would you like to share or vent about?',
        'Publicar': 'Post',
        'Comentários': 'Comments',
        'Comentar': 'Comment',
        'Comente sua experiência aqui...': 'Share your experience here...',
        'Curtir': 'Like',
        'Curtidas': 'Likes',
        'Apoiar': 'Support',
        'Apoios': 'Supports',
        'Denunciar': 'Report',
        'Denunciar publicação': 'Report post',
        'Mais recentes': 'Most recent',
        'Mais curtidos': 'Most liked',
        'Reações': 'Reactions',
        'Reagir': 'React',
        '💬 Reagir': '💬 React',
        '💬  Reagir': '💬 React',
        'Alerta': 'Alert',
        'Segurança': 'Comfort',
        'Processamento': 'Reflection',
        'Pode dar gatilho': 'May be triggering',
        'Me deixou ansioso': 'Made me anxious',
        'Achei pesado': 'I found it heavy',
        'Cura a alma': 'Healing',
        'É leve': 'It is light',
        'Me trouxe paz': 'Brought me peace',
        'Relaxante': 'Relaxing',
        'Me fez refletir': 'Made me reflect',
        'História profunda': 'Deep story',
        'Me emocionou': 'Moved me',
        'Me deixou pensativo': 'Made me thoughtful',
        'Bom': 'Good',
        'Ruim': 'Bad',
        'Nenhum comentário ainda.': 'No comments yet.',
        'Seja o primeiro a comentar!': 'Be the first to comment!',
        'Bem-vindo(a) à Comunidade! ✦': 'Welcome to the Community! ✦',
        'Este é o seu espaço seguro para compartilhar como você está se sentindo, desabafar e acolher os outros. Você pode criar sua primeira publicação no campo acima ou reagir às postagens com empatia!':
            'This is your safe space to share how you are feeling, vent, and support others. You can create your first post in the field above or react to posts with empathy!',
        'Apagar': 'Delete',
        'Editar': 'Edit',
        'Editar Publicação': 'Edit Post',
        'Salvar Alterações': 'Save Changes',
        'Cancelar': 'Cancel',
        'Mídias e Atividades': 'Media & Activities',
        'O que você consumiu?': 'What did you consume?',
        'Escreva aqui o nome...': 'Type the name here...',
        'Buscar': 'Search',
        'Tudo': 'All',
        'Filmes/Séries': 'Movies/Series',
        'Livros': 'Books',
        'Pesquisa aqui...': 'Search here...',

        // ChatBOT (Helpy)
        'Histórico': 'History',
        'Nova Conversa': 'New Conversation',
        'Digite aqui para conversar com o Helpy...': 'Type here to talk to Helpy...',
        'Conversar com o Helpy →': 'Talk to Helpy →',
        'O Helpy está digitando...': 'Helpy is typing...',
        'Limpar histórico': 'Clear history',

        // Início
        'Cuidar de você nunca foi tão simples.': 'Taking care of yourself has never been easier.',
        'O bem-estar impulsiona a motivação e os relacionamentos, enquanto dificuldades emocionais prejudicam o humor e a tomada de decisões.':
            'Well-being boosts motivation and relationships, while emotional struggles harm mood and decision-making.',
        'Todos temos dias difíceis, e você não precisa passar por eles sozinho. Explore um ambiente focado no seu bem-estar, acompanhe seu humor, registre sua jornada e descubra ferramentas desenhadas para trazer mais clareza e tranquilidade para o seu dia a dia.':
            'We all have difficult days, and you don\'t have to go through them alone. Explore an environment focused on your well-being, track your mood, record your journey, and discover tools designed to bring more clarity and calm to your routine.',
        'Registre seus pensamentos diariamente. Escrever alivia a mente e ajuda a entender melhor suas emoções.': 'Record your thoughts daily. Writing clears the mind and helps you better understand your emotions.',
        'Acesse exercícios de respiração e relaxamento para reduzir o estresse e melhorar seu foco no dia a dia.': 'Access breathing and relaxation exercises to reduce stress and improve your focus daily.',
        'Diário Pessoal': 'Personal Journal',
        'Apoio 24 Horas': '24/7 Support',
        'Apoio emocional na palma da sua mão.': 'Emotional support in the palm of your hand.',
        'Entre em contato:': 'Get in touch:',
        'Intagram:': 'Instagram:',
        'WhatsApp:': 'WhatsApp:'
    };

    var dicionarioENLower = {};
    Object.keys(dicionarioEN).forEach(function (k) {
        dicionarioENLower[k.toLowerCase()] = dicionarioEN[k];
    });

    var selConteudo = '.post-texto,.post-nome,.post-data,.comentario-nome,.post-avatar,.post-midias-container,.attachment-name';

    function traduzirPagina() {
        var idioma = localStorage.getItem('helpfull_idioma') || 'pt-BR';
        var walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
        var no;
        while ((no = walker.nextNode())) {
            if (!no || !no.nodeValue) continue;
            var raw = no.nodeValue;
            var trimmed = raw.trim();
            if (!trimmed) continue;
            if (!no.parentElement) continue;
            var tag = no.parentElement.tagName.toLowerCase();
            if (tag === 'script' || tag === 'style' || tag === 'code' || tag === 'pre') continue;
            if (no.parentElement.closest && no.parentElement.closest(selConteudo)) continue;

            if (no._textoOriginal === undefined) {
                no._textoOriginal = raw;
            }

            if (idioma === 'pt-BR') {
                if (no.nodeValue !== no._textoOriginal) {
                    no.nodeValue = no._textoOriginal;
                }
            } else if (idioma === 'en') {
                var originalTrimmed = no._textoOriginal.trim();
                var norm = originalTrimmed.replace(/\s+/g, ' ');
                if (dicionarioEN[originalTrimmed]) {
                    no.nodeValue = no._textoOriginal.replace(originalTrimmed, dicionarioEN[originalTrimmed]);
                } else if (dicionarioEN[norm]) {
                    no.nodeValue = dicionarioEN[norm];
                } else if (dicionarioENLower[originalTrimmed.toLowerCase()]) {
                    no.nodeValue = no._textoOriginal.replace(originalTrimmed, dicionarioENLower[originalTrimmed.toLowerCase()]);
                } else if (dicionarioENLower[norm.toLowerCase()]) {
                    no.nodeValue = dicionarioENLower[norm.toLowerCase()];
                } else if (originalTrimmed.indexOf('Reagir') !== -1) {
                    no.nodeValue = no._textoOriginal.replace('Reagir', 'React');
                }
            }
        }

        // Placeholders
        document.querySelectorAll('[placeholder]').forEach(function (el) {
            if (el.closest && el.closest(selConteudo)) return;
            if (el._origPlaceholder === undefined) {
                el._origPlaceholder = el.getAttribute('placeholder') || '';
            }
            if (idioma === 'pt-BR') {
                el.setAttribute('placeholder', el._origPlaceholder);
            } else if (idioma === 'en') {
                var pTrim = el._origPlaceholder.trim();
                if (dicionarioEN[pTrim]) {
                    el.setAttribute('placeholder', dicionarioEN[pTrim]);
                } else if (dicionarioENLower[pTrim.toLowerCase()]) {
                    el.setAttribute('placeholder', dicionarioENLower[pTrim.toLowerCase()]);
                }
            }
        });

        // Botões submit/button
        document.querySelectorAll('input[type="submit"], input[type="button"]').forEach(function (el) {
            if (el._origVal === undefined) {
                el._origVal = el.value || '';
            }
            if (idioma === 'pt-BR') {
                el.value = el._origVal;
            } else if (idioma === 'en') {
                var vTrim = el._origVal.trim();
                if (dicionarioEN[vTrim]) {
                    el.value = dicionarioEN[vTrim];
                } else if (dicionarioENLower[vTrim.toLowerCase()]) {
                    el.value = dicionarioENLower[vTrim.toLowerCase()];
                }
            }
        });
    }

    window.traduzirPagina = traduzirPagina;

    var _observerTraducao = null;
    function iniciarObservadorTraducao() {
        if (_observerTraducao || typeof MutationObserver === 'undefined') return;
        var timeoutId = null;
        _observerTraducao = new MutationObserver(function (mutations) {
            var temNovosNodes = false;
            for (var i = 0; i < mutations.length; i++) {
                if (mutations[i].addedNodes && mutations[i].addedNodes.length > 0) {
                    temNovosNodes = true;
                    break;
                }
            }
            if (temNovosNodes) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(function () {
                    traduzirPagina();
                }, 100);
            }
        });
        _observerTraducao.observe(document.body, { childList: true, subtree: true });
    }

    function aplicarIdioma() {
        var idioma = localStorage.getItem('helpfull_idioma') || 'pt-BR';
        var t = tradInterface[idioma] || tradInterface['pt-BR'];
        document.documentElement.lang = idioma;
        var sel = document.getElementById('seletorIdioma');
        if (sel) sel.value = idioma;
        var cab = document.querySelector('.acessibilidade-cabecalho h2');
        var des = document.querySelector('.acessibilidade-cabecalho p');
        var fec = document.querySelector('.acessibilidade-fechar');
        var rot = document.querySelector('.acessibilidade-idioma label');
        var res = document.querySelector('.acessibilidade-resetar');
        if (cab) cab.textContent = t.acessibilidade;
        if (des) des.textContent = t.preferencias;
        if (fec) fec.setAttribute('aria-label', t.fechar);
        if (rot) rot.textContent = t.idioma;
        if (res) res.textContent = t.restaurar;
        Object.keys(prefAcess).forEach(function (k) {
            var el = document.querySelector('[data-acessibilidade="' + k + '"]');
            if (!el || !t[k]) return;
            var ti = el.querySelector('strong');
            var di = el.querySelector('small');
            if (ti) ti.textContent = t[k][0];
            if (di) di.textContent = t[k][1];
        });
        document.querySelectorAll('.nav-links a,.links-capsula a,.nav-dropdown-mobile a').forEach(function (a) {
            var url = (a.getAttribute('href') || '').split('?')[0].split('#')[0];
            if (t.nav[url]) a.textContent = t.nav[url];
        });
        traduzirPagina();
        iniciarObservadorTraducao();
    }

    function aplicarAcessibilidade() {
        var tg = localStorage.getItem('helpfull_textoGrande') === 'true';
        document.documentElement.classList.toggle('acessibilidade-texto-grande', tg);
        document.body.classList.toggle('acessibilidade-texto-grande', tg);
        Object.keys(prefAcess).forEach(function (k) {
            var ativo = localStorage.getItem('helpfull_' + k) === 'true';
            var classe = prefAcess[k];
            document.body.classList.toggle(classe, ativo);
            document.documentElement.classList.toggle(classe, ativo);
            var el = document.querySelector('[data-acessibilidade="' + k + '"]');
            if (el) { el.classList.toggle('ativo', ativo); el.setAttribute('aria-pressed', ativo ? 'true' : 'false'); }
        });
    }

    function criarPainelAcessibilidade() {
        var p = document.getElementById('painelAcessibilidade');
        if (!p) {
            var div = document.createElement('div');
            div.innerHTML = `
                <section id="painelAcessibilidade" aria-label="Opções de acessibilidade">
                    <div class="acessibilidade-cabecalho">
                        <div>
                            <h2>Acessibilidade</h2>
                            <p>Preferências aplicadas em todo o site</p>
                        </div>
                        <button type="button" class="acessibilidade-fechar" aria-label="Fechar acessibilidade">×</button>
                    </div>
                    <div class="acessibilidade-opcoes">
                        <div class="acessibilidade-idioma">
                            <label for="seletorIdioma">Idioma</label>
                            <select id="seletorIdioma" aria-label="Selecionar idioma">
                                <option value="pt-BR">Português (Brasil)</option>
                                <option value="en">English</option>
                            </select>
                        </div>
                        <button type="button" class="acessibilidade-opcao" data-acessibilidade="escuro"><span><strong>Modo noturno</strong><small>Fundo preto e superfícies cinza escuro</small></span><span class="acessibilidade-status" aria-hidden="true"></span></button>
                        <button type="button" class="acessibilidade-opcao" data-acessibilidade="contraste"><span><strong>Mais contraste</strong><small>Realça bordas, textos e links</small></span><span class="acessibilidade-status" aria-hidden="true"></span></button>
                        <button type="button" class="acessibilidade-opcao" data-acessibilidade="textoGrande"><span><strong>Texto maior</strong><small>Aumenta a leitura sem trocar de página</small></span><span class="acessibilidade-status" aria-hidden="true"></span></button>
                        <button type="button" class="acessibilidade-opcao" data-acessibilidade="sublinhar"><span><strong>Sublinhar links</strong><small>Facilita localizar elementos clicáveis</small></span><span class="acessibilidade-status" aria-hidden="true"></span></button>
                        <button type="button" class="acessibilidade-opcao" data-acessibilidade="semAnimacao"><span><strong>Reduzir animações</strong><small>Diminui movimentos e transições</small></span><span class="acessibilidade-status" aria-hidden="true"></span></button>
                    </div>
                    <div class="acessibilidade-rodape"><button type="button" class="acessibilidade-resetar">Restaurar padrão</button></div>
                </section>
            `.trim();
            p = div.firstElementChild;
            document.body.appendChild(p);
        }
        var b = document.getElementById('btnAcessibilidade');
        if (b) b.setAttribute('aria-expanded', 'false');
        var f = p.querySelector('.acessibilidade-fechar');
        if (f && f.dataset.ligado !== 'true') {
            f.addEventListener('click', function () {
                p.classList.remove('aberto');
                p.setAttribute('data-aberto', 'false');
                if (b) b.setAttribute('aria-expanded','false');
            });
            f.dataset.ligado = 'true';
        }
        aplicarAcessibilidade();
        aplicarIdioma();
    }

    var _bloqueioFecharPainelAcess = false;
    var _ultimoToggleTempo = 0;

    window.togglePainelAcessibilidade = function (e) {
        if (e) {
            if (e.preventDefault) e.preventDefault();
            if (e.stopPropagation) e.stopPropagation();
        }
        var agora = Date.now();
        if (agora - _ultimoToggleTempo < 250) {
            return;
        }
        _ultimoToggleTempo = agora;

        var p = document.getElementById('painelAcessibilidade');
        var b = document.getElementById('btnAcessibilidade');
        if (!p) {
            criarPainelAcessibilidade();
            conectarControlesAcessibilidade();
            p = document.getElementById('painelAcessibilidade');
        }
        if (!p) return;
        var ab = !p.classList.contains('aberto');
        if (ab) {
            _bloqueioFecharPainelAcess = true;
            setTimeout(function () {
                _bloqueioFecharPainelAcess = false;
            }, 400);
            p.classList.add('aberto');
            p.setAttribute('data-aberto', 'true');
            if (b) b.setAttribute('aria-expanded', 'true');
        } else {
            p.classList.remove('aberto');
            p.setAttribute('data-aberto', 'false');
            if (b) b.setAttribute('aria-expanded', 'false');
        }
    };
    window.abrirPainelAcessibilidade = window.togglePainelAcessibilidade;

    window.abrirPainelAcessibilidadeMobile = function (e) {
        if (e) {
            if (e.preventDefault) e.preventDefault();
            if (e.stopPropagation) e.stopPropagation();
        }
        var dropdown = document.getElementById('navDropdownMobile');
        if (dropdown) dropdown.classList.remove('aberto');
        var logo = document.querySelector('.nav-logo') || document.querySelector('.nav-logo-capsula');
        if (logo) logo.classList.remove('aberto');
        window.togglePainelAcessibilidade(e);
    };

    function garantirBotaoAcessibilidade() {
        var path = (window.location.pathname || '').toLowerCase();
        if (!path.includes('perfil')) return;

        var b = document.getElementById('btnAcessibilidade');
        if (!b) {
            var navContainer = document.querySelector('.nav-container-global');
            if (navContainer) {
                var anchor = document.createElement('div');
                anchor.className = 'acessibilidade-anchor';
                anchor.innerHTML = `
                    <button type="button" class="btn-acessibilidade" id="btnAcessibilidade" onclick="togglePainelAcessibilidade(event)" aria-label="Abrir configurações e acessibilidade" aria-expanded="false" title="Configurações">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                        </svg>
                    </button>
                `;
                navContainer.appendChild(anchor);
            }
        } else {
            // Se o botão já existe mas usa texto ou ícone de fonte que pode falhar, garante o SVG
            if (!b.querySelector('svg')) {
                b.innerHTML = `
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                `;
            }
        }
    }

    function conectarControlesAcessibilidade() {
        var p = document.getElementById('painelAcessibilidade');
        if (!p || p.dataset.controlesConectados === 'true') return;
        p.dataset.controlesConectados = 'true';
        var si = p.querySelector('#seletorIdioma');
        if (si && si.dataset.ligado !== 'true') {
            si.value = localStorage.getItem('helpfull_idioma') || 'pt-BR';
            si.addEventListener('change', function () {
                localStorage.setItem('helpfull_idioma', si.value);
                aplicarIdioma();
            });
            si.dataset.ligado = 'true';
        }
        p.querySelectorAll('[data-acessibilidade]').forEach(function (el) {
            if (el.dataset.ligado === 'true') return;
            el.dataset.ligado = 'true';
            el.addEventListener('click', function (e) {
                e.preventDefault();
                var k = el.getAttribute('data-acessibilidade');
                var ativo = localStorage.getItem('helpfull_' + k) === 'true';
                localStorage.setItem('helpfull_' + k, (!ativo).toString());
                aplicarAcessibilidade();
            });
        });
        var re = p.querySelector('.acessibilidade-resetar');
        if (re && re.dataset.ligado !== 'true') {
            re.dataset.ligado = 'true';
            re.addEventListener('click', function (e) {
                e.preventDefault();
                Object.keys(prefAcess).forEach(function (k) { localStorage.removeItem('helpfull_' + k); });
                localStorage.removeItem('helpfull_textoGrande');
                localStorage.setItem('helpfull_idioma', 'pt-BR');
                if (si) si.value = 'pt-BR';
                aplicarAcessibilidade();
                aplicarIdioma();
            });
        }
        aplicarAcessibilidade();
        aplicarIdioma();
    }

    /* ── Inicialização ────────────────────────────────────── */
    aplicarAcessibilidade();
    aplicarIdioma();

    window.addEventListener('DOMContentLoaded', function () {
        aplicarIdioma();
        aplicarAcessibilidade();
        criarPainelAcessibilidade();
        conectarControlesAcessibilidade();
        garantirBotaoAcessibilidade();
        vincularHoverPerfil();
        carregarNotificacoes();

        // fechar painel ao clicar fora
        document.addEventListener('click', function (e) {
            var painel = getPainel();
            var btn    = getBtnSino();
            if (painel && painel.classList.contains('aberto')) {
                if (!painel.contains(e.target) && (!btn || !btn.contains(e.target))) {
                    painel.classList.remove('aberto');
                    _hoverTimer = setTimeout(esconderBtnSino, 1500);
                }
            }

            if (_bloqueioFecharPainelAcess) return;

            var pa = document.getElementById('painelAcessibilidade');
            var ba = document.getElementById('btnAcessibilidade');
            var ml = e.target.closest('.btn-abrir-acessibilidade, .btn-acessibilidade, [onclick*="togglePainelAcessibilidade"], [onclick*="abrirPainelAcessibilidade"], .nav-dropdown-mobile');
            if (pa && pa.classList.contains('aberto')) {
                if (!pa.contains(e.target) && (!ba || !ba.contains(e.target)) && !ml) {
                    pa.classList.remove('aberto');
                    pa.setAttribute('data-aberto', 'false');
                    if (ba) ba.setAttribute('aria-expanded', 'false');
                }
            }
        });
    });

    if (document.readyState !== 'loading') {
        criarPainelAcessibilidade();
        conectarControlesAcessibilidade();
        garantirBotaoAcessibilidade();
    }

})();
