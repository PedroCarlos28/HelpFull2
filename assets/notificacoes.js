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
    function getContainers() {
        var arr = [];
        var c1 = document.getElementById('containerListaNotificacoes');
        if (c1) arr.push(c1);
        var c2 = document.getElementById('containerListaNotificacoesPerfil');
        if (c2 && c2 !== c1) arr.push(c2);
        return arr;
    }
    function getContainer() {
        var arr = getContainers();
        return arr.length > 0 ? arr[0] : null;
    }

    /* ── Efeito de desfoque / fade gradual nas extremidades da rolagem ── */
    function atualizarScrollFade(el) {
        if (!el) return;
        var temScroll = el.scrollHeight > el.clientHeight + 4;
        if (!temScroll) {
            el.classList.add('sem-scroll');
            el.classList.remove('scroll-topo', 'scroll-meio', 'scroll-fim');
            return;
        }
        el.classList.remove('sem-scroll');
        var noTopo = el.scrollTop <= 6;
        var noFim = (el.scrollTop + el.clientHeight) >= (el.scrollHeight - 6);

        if (noTopo && !noFim) {
            el.classList.add('scroll-topo');
            el.classList.remove('scroll-meio', 'scroll-fim');
        } else if (noFim && !noTopo) {
            el.classList.add('scroll-fim');
            el.classList.remove('scroll-topo', 'scroll-meio');
        } else if (!noTopo && !noFim) {
            el.classList.add('scroll-meio');
            el.classList.remove('scroll-topo', 'scroll-fim');
        } else {
            el.classList.add('sem-scroll');
            el.classList.remove('scroll-topo', 'scroll-meio', 'scroll-fim');
        }
    }

    function inicializarScrollFade() {
        var targets = getContainers();
        targets.forEach(function (c) {
            if (!c) return;
            if (!c._scrollFadeVinculado) {
                c._scrollFadeVinculado = true;
                c.addEventListener('scroll', function () {
                    atualizarScrollFade(c);
                }, { passive: true });
            }
            atualizarScrollFade(c);
        });
    }

    /* ── Badge sininho ────────────────────────────────────── */
    function atualizarBadge() {
        var s = getSininho();
        if (s) {
            // Se estiver na tela de perfil com o bloco de notificações aberto, oculta o badge no sino
            if (document.getElementById('cardNotificacoesPerfil') || document.body.classList.contains('pagina-perfil')) {
                s.style.display = 'none';
            } else {
                s.style.display = _totalNotifs > 0 ? 'flex' : 'none';
            }
        }
    }

    /* ── Botão sino flutuante ─────────────────────────────── */
    function mostrarBtnSino() {
        // Se estiver na página do perfil, não mostra o botão flutuante pois as notificações estão no bloco
        if (document.body.classList.contains('pagina-perfil') || document.getElementById('cardNotificacoesPerfil')) return;
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
            carregarNotificacoes();
            setTimeout(inicializarScrollFade, 60);
            fetch('notificacao_ia.php?acao=marcar_lidas').catch(function () {});
            fetch('social_notificacoes_proc.php?acao=marcar_lidas').catch(function () {});
        } else {
            _hoverTimer = setTimeout(esconderBtnSino, 1500);
        }
    };

    function getEmptyStateHtml() {
        var isEn = (localStorage.getItem('helpfull_idioma') === 'en');
        var titulo = isEn ? 'All caught up' : 'Tudo limpo por aqui';
        var desc = isEn ? 'No new notifications right now.' : 'Nenhuma notificação nova no momento.';
        return '<div class="notif-vazio-container">' +
            '<div class="notif-vazio-icone">' +
            '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
            '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>' +
            '<path d="M13.73 21a2 2 0 0 1-3.46 0"></path>' +
            '</svg>' +
            '</div>' +
            '<span class="notif-vazio-titulo">' + titulo + '</span>' +
            '<span class="notif-vazio-sub">' + desc + '</span>' +
            '</div>';
    }

    window.limparNotificacoes = function () {
        var msgConfirm = (localStorage.getItem('helpfull_idioma') === 'en')
            ? 'Do you want to clear notifications?'
            : 'Deseja limpar as notificações?';
        if (!confirm(msgConfirm)) return;
        fetch('notificacao_ia.php?acao=limpar').catch(function () {});
        fetch('social_notificacoes_proc.php?acao=limpar').catch(function () {});
        _totalNotifs = 0;
        atualizarBadge();
        esconderBtnSino();
        var p = getPainel();
        if (p) p.classList.remove('aberto');
        var targets = getContainers();
        targets.forEach(function (c) {
            c.innerHTML = getEmptyStateHtml();
        });
        setTimeout(inicializarScrollFade, 30);
    };

    /* ── Popular painel ───────────────────────────────────── */
    function popularPainel(data, append) {
        var targets = getContainers();
        if (!targets.length || !data) return;
        var notifKey = data.chave || (data.id ? ('id_' + data.id) : null) || data.mensagem;

        var href = data.link || '#';
        var txtBtn = data.textoBotao || (localStorage.getItem('helpfull_idioma') === 'en' ? 'View' : 'Ver');
        var btnHtml = (href !== '#')
            ? '<a href="' + _esc(href) + '" class="notif-link-btn">' + _esc(txtBtn) + '</a>'
            : '';

        targets.forEach(function (c) {
            // Evita duplicar a mesma notificação no mesmo container
            if (notifKey && c.querySelector('[data-notif-key="' + _esc(notifKey) + '"]')) {
                return;
            }

            if (!append) {
                c.innerHTML = '';
            } else {
                var ph = c.querySelector('.notif-vazio-container, p.nenhuma-notif-texto, p');
                if (ph && (ph.classList.contains('nenhuma-notif-texto') || ph.classList.contains('notif-vazio-container') || ph.textContent.indexOf('Nenhuma') !== -1 || ph.textContent.indexOf('No new') !== -1 || ph.textContent.indexOf('Tudo limpo') !== -1)) {
                    ph.remove();
                }
            }
            var item = document.createElement('div');
            item.className = 'item-notificacao ' + (data.intensidade || 'baixa');
            if (notifKey) item.setAttribute('data-notif-key', notifKey);
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
        });

        _totalNotifs++;
        atualizarBadge();
        setTimeout(inicializarScrollFade, 30);
    }

    function _esc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    /* ── Controle de Toasts Exibidos (Não repetir entre telas) ── */
    function jaExibiuToast(data) {
        if (!data) return true;
        // Na tela de Perfil, NUNCA exibe o balão flutuante (toast)
        if (document.body.classList.contains('pagina-perfil') || document.getElementById('cardNotificacoesPerfil')) {
            return true;
        }
        var chave = data.chave || (data.id ? ('id_' + data.id) : null) || data.mensagem;
        if (!chave) return false;
        try {
            var vistos = JSON.parse(localStorage.getItem('helpfull_toasts_vistos') || '[]');
            return vistos.indexOf(chave) !== -1;
        } catch (e) {
            return false;
        }
    }

    function marcarToastExibido(data) {
        if (!data) return;
        var chave = data.chave || (data.id ? ('id_' + data.id) : null) || data.mensagem;
        if (!chave) return;
        try {
            var vistos = JSON.parse(localStorage.getItem('helpfull_toasts_vistos') || '[]');
            if (vistos.indexOf(chave) === -1) {
                vistos.push(chave);
                if (vistos.length > 50) vistos = vistos.slice(-50);
                localStorage.setItem('helpfull_toasts_vistos', JSON.stringify(vistos));
            }
        } catch (e) {}

        // Atualiza no backend para que a notificação seja marcada como lida (lida = 1) e não volte como nova
        var idParam = data.id || data.chave;
        if (idParam) {
            fetch('notificacao_ia.php?acao=marcar_toast_visto&id=' + encodeURIComponent(idParam)).catch(function () {});
        } else {
            fetch('notificacao_ia.php?acao=marcar_toast_visto').catch(function () {});
        }
    }

    /* ── Toast (com fila) ─────────────────────────────────── */
    function mostrarToast(data) {
        if (!data) return;
        // Se já foi exibido em qualquer tela ou se estiver no perfil, não exibe o balão
        if (jaExibiuToast(data)) return;
        marcarToastExibido(data);
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
    window.carregarNotificacoes    = carregarNotificacoes;

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
        var isPerfil = document.body.classList.contains('pagina-perfil') || document.getElementById('cardNotificacoesPerfil');

        // 1) IA — emoções negativas no diário
        try {
            var r    = await fetch('notificacao_ia.php');
            var data = await r.json();
            if (data && data.notificacoes && Array.isArray(data.notificacoes)) {
                data.notificacoes.forEach(function (n, idx) {
                    popularPainel(n, idx > 0);
                    if (n.nova && !isPerfil && !jaExibiuToast(n)) mostrarToast(n);
                });
            } else if (Array.isArray(data)) {
                data.forEach(function (n, idx) {
                    popularPainel(n, idx > 0);
                    if (n.nova && !isPerfil && !jaExibiuToast(n)) mostrarToast(n);
                });
            } else if (data && data.mostrar) {
                popularPainel(data, false);
                if (data.nova && !isPerfil && !jaExibiuToast(data)) mostrarToast(data);
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

        // Se estiver na tela de perfil, marca como lidas imediatamente para não exibir toasts
        if (isPerfil) {
            fetch('notificacao_ia.php?acao=marcar_lidas').catch(function () {});
            fetch('social_notificacoes_proc.php?acao=marcar_lidas').catch(function () {});
        }

        // garante placeholder se sem notificações
        var targets = getContainers();
        targets.forEach(function (c) {
            if (c.children.length === 0 || !c.querySelector('.item-notificacao')) {
                c.innerHTML = getEmptyStateHtml();
            }
        });
        setTimeout(inicializarScrollFade, 60);
    }

    /* ── Acessibilidade ───────────────────────────────────── */
    try {
        localStorage.removeItem('helpfull_reduzirTransparencia');
        if (document.documentElement) document.documentElement.classList.remove('acessibilidade-reduzir-transparencia');
        if (document.body) document.body.classList.remove('acessibilidade-reduzir-transparencia');
    } catch (e) {}

    var prefAcess = {
        escuro:       'acessibilidade-escuro',
        contraste:    'acessibilidade-contraste',
        textoGrande:  'acessibilidade-texto-grande',
        sublinhar:    'acessibilidade-sublinhar',
        semAnimacao:  'acessibilidade-sem-animacao',
        libras:       'acessibilidade-libras'
    };

    var tradInterface = {
        'pt-BR': {
            idioma:'Idioma', acessibilidade:'Acessibilidade',
            preferencias:'Preferências aplicadas em todo o site',
            fechar:'Fechar acessibilidade',
            escuro:['Modo noturno','Fundo preto e superfícies cinza escuro'],
            autoTema:['Seguir dispositivo','Sincroniza com o tema do seu aparelho'],
            contraste:['Mais contraste','Realça bordas, textos e links'],
            textoGrande:['Texto maior','Aumenta a leitura sem trocar de página'],
            sublinhar:['Sublinhar links','Facilita localizar elementos clicáveis'],
            semAnimacao:['Reduzir animações','Diminui movimentos e transições'],
            libras:['Tradutor de Libras (Gov.br)','Ativa o avatar VLibras do Governo Federal'],
            restaurar:'Restaurar padrão',
            nav:{'Diario.php':'Diário','Comunidade.php':'Comunidade','ChatBOT.php':'Helpy','Atividades.php':'Atividades','Perfil.php':'Perfil','inicio.php':'Início'}
        },
        en: {
            idioma:'Language', acessibilidade:'Accessibility',
            preferencias:'Preferences applied across the site',
            fechar:'Close accessibility options',
            escuro:['Dark mode','Dark background and gray surfaces'],
            autoTema:['Sync with device','Matches your device appearance'],
            contraste:['High contrast','Highlights borders, text and links'],
            textoGrande:['Larger text','Improves readability without changing pages'],
            sublinhar:['Underline links','Makes clickable elements easier to find'],
            semAnimacao:['Reduce motion','Reduces movement and transitions'],
            libras:['Libras Translator (Gov.br)','Enables Brazilian Sign Language (VLibras) avatar'],
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
        'Como o estresse atua no corpo (e como desativá-lo)': 'How Stress Affects the Body (and How to Deactivate It)',
        'Quando nos sentimos sob pressão ou ameaçados, nosso cérebro aciona o modo de "luta ou fuga", liberando hormônios como cortisol e adrenalina. Isso acelera os batimentos, tensiona os músculos e bloqueia a capacidade de pensar com clareza. Para enviar ao cérebro o sinal de que o perigo já passou, pequenas práticas corporais são poderosas: relaxar os ombros, soltar o maxilar e realizar exalações longas e audíveis. Ao acalmar o corpo físico primeiro, a mente gradualmente compreende que está segura, permitindo que a racionalidade e a serenidade voltem a liderar.':
            'When we feel pressured or threatened, our brain triggers fight-or-flight mode, releasing hormones such as cortisol and adrenaline. This accelerates heart rate, tenses muscles, and clouds clear thinking. To signal to the brain that danger has passed, simple physical practices are remarkably powerful: relaxing your shoulders, releasing your jaw, and taking long, audible exhales. By calming the physical body first, the mind gradually learns that it is safe, allowing rationality and calm to regain control.',
        'Quebrando o ciclo do excesso de pensamentos': 'Breaking the Cycle of Overthinking',
        'Ficar repassando a mesma preocupação mentalmente dá a falsa impressão de estarmos resolvendo um problema, mas na verdade apenas desgasta nossa energia emocional. Quando você perceber que está preso num turbilhão de pensamentos, use a regra da ancoragem: nomeie mentalmente três coisas que você consegue ver ao seu redor, dois sons que consegue ouvir e uma textura que consegue tocar com as mãos. Trazer a atenção de volta para os sentidos físicos interrompe o piloto automático da mente e impede que medos futuros sequestrem a sua paz no presente.':
            'Replaying the same worries over and over in your head gives the false illusion of problem-solving, but in reality it only drains your emotional energy. When you notice yourself trapped in a whirlpool of thoughts, use the grounding technique: mentally identify three things you can see around you, two sounds you can hear, and one texture you can touch with your hands. Bringing your focus back to physical sensations halts the mind\'s autopilot and prevents future fears from hijacking your present peace.',
        'Acolhendo o que você sente sem julgamentos': 'Welcoming What You Feel Without Judgment',
        'Sentir tristeza, cansaço, medo ou irritação não significa que você esteja falhando. As emoções são mensagens biológicas que pedem atenção, e tentar suprimi-las à força costuma apenas torná-las mais intensas. Quando uma sensação incômoda surgir, experimente dar um nome a ela sem se criticar: "estou sentindo frustração agora". Observe essa sensação no corpo como quem assiste a uma onda no oceano — ela cresce, atinge seu pico e, se você não lutar contra ela, naturalmente se dissipa. Aprender a conviver com o desconforto é um dos passos mais libertadores da saúde emocional.':
            'Feeling sadness, fatigue, fear, or frustration does not mean you are failing. Emotions are biological messages asking for attention, and forcing them down usually only makes them stronger. When an uncomfortable feeling arises, try naming it without self-criticism: "I am experiencing frustration right now". Observe that bodily sensation as someone watching an ocean wave — it rises, crests, and naturally subsides if you don\'t fight it. Learning to sit with discomfort is one of the most liberating steps in emotional well-being.',
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
        'Praticar respiração guiada com o vídeo': 'Practice guided breathing with the video',
        'Assistir vídeo de pausa e presença mental': 'Watch mindful pause video',
        'Assistir ao vídeo sobre estresse e mente': 'Watch video on stress and the mind',
        'Assistir ao vídeo sobre excesso de pensamentos': 'Watch video on overthinking',
        'Assistir ao vídeo sobre emoções e bem-estar': 'Watch video on emotions and well-being',
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
        'Anotar uma pequena vitória de hoje no Diário (por menor que seja).': 'Write down a small victory today in your Journal (no matter how small).',
        'Anotar uma pequena vitória de hoje no Diário (por menor que seja)': 'Write down a small victory today in your Journal (no matter how small)',
        "Dar um 'oi' para o Helpy e desabafar por 2 minutinhos.": 'Say hello to Helpy and vent for 2 minutes.',
        "Dar um 'oi' para o Helpy e desabafar por 2 minutinhos": 'Say hello to Helpy and vent for 2 minutes',
        'Registrar a emoção que estou sentindo agora no meu Diário.': 'Log the emotion you are feeling right now in your Journal.',
        'Registrar a emoção que estou sentindo agora no meu Diário': 'Log the emotion you are feeling right now in your Journal',
        'Tirar 1 minutinho para assistir a um vídeo de respiração na aba Adicionais.': 'Take 1 minute to watch a breathing video in the Extras tab.',
        'Tirar 1 minutinho para assistir a um vídeo de respiração na aba Adicionais': 'Take 1 minute to watch a breathing video in the Extras tab',
        'Tirar 1 minutinho para assistir a um vídeo de respiração na aba Atividades.': 'Take 1 minute to watch a breathing video in the Activities tab.',
        'Tirar 1 minutinho para assistir a um vídeo de respiração na aba Atividades': 'Take 1 minute to watch a breathing video in the Activities tab',
        'Compartilhar uma mensagem positiva ou pensamento na Comunidade.': 'Share a positive message or thought in the Community.',
        'Compartilhar uma mensagem positiva ou pensamento na Comunidade': 'Share a positive message or thought in the Community',
        'Deixar uma reação ou palavra de apoio para alguém na Comunidade.': 'Leave a reaction or supportive word for someone in the Community.',
        'Deixar uma reação ou palavra de apoio para alguém na Comunidade': 'Leave a reaction or supportive word for someone in the Community',
        'Escrever no Diário pelo menos 1 motivo de gratidão pelo dia de hoje.': 'Write in your Journal at least 1 reason for gratitude today.',
        'Escrever no Diário pelo menos 1 motivo de gratidão pelo dia de hoje': 'Write in your Journal at least 1 reason for gratitude today',
        'Beber um bom copo de água e fazer 3 respirações lentas e profundas.': 'Drink a good glass of water and take 3 slow, deep breaths.',
        'Beber um bom copo de água e fazer 3 respirações lentas e profundas': 'Drink a good glass of water and take 3 slow, deep breaths',
        'Fazer uma pausa de 5 minutos longe de telas para descansar a mente.': 'Take a 5-minute break away from screens to rest your mind.',
        'Fazer uma pausa de 5 minutos longe de telas para descansar a mente': 'Take a 5-minute break away from screens to rest your mind',
        'Alongar o pescoço e os ombros para aliviar a tensão do corpo.': 'Stretch your neck and shoulders to relieve body tension.',
        'Alongar o pescoço e os ombros para aliviar a tensão do corpo': 'Stretch your neck and shoulders to relieve body tension',
        'Pedir ao Helpy uma sugestão de reflexão ou conselho para o dia.': 'Ask Helpy for a reflection prompt or advice for the day.',
        'Pedir ao Helpy uma sugestão de reflexão ou conselho para o dia': 'Ask Helpy for a reflection prompt or advice for the day',
        'Registrar mais de um momento do seu dia no Diário para reflexão.': 'Record more than one moment of your day in your Journal for reflection.',
        'Registrar mais de um momento do seu dia no Diário para reflexão': 'Record more than one moment of your day in your Journal for reflection',
        'Ouvir uma música relaxante ou som suave na aba Adicionais.': 'Listen to relaxing music or soothing sounds in the Extras tab.',
        'Ouvir uma música relaxante ou som suave na aba Adicionais': 'Listen to relaxing music or soothing sounds in the Extras tab',
        'Praticar a gentileza consigo mesmo(a) e reconhecer um ponto positivo seu.': 'Practice self-kindness and recognize something positive about yourself.',
        'Praticar a gentileza consigo mesmo(a) e reconhecer um ponto positivo seu': 'Practice self-kindness and recognize something positive about yourself',
        'Organizar um cantinho do seu espaço ao redor para clarear os pensamentos.': 'Tidy up a corner of your space to clear your thoughts.',
        'Organizar um cantinho do seu espaço ao redor para clarear os pensamentos': 'Tidy up a corner of your space to clear your thoughts',
        'Ler um artigo ou curiosidade de bem-estar na aba Adicionais.': 'Read an article or wellness tip in the Extras tab.',
        'Ler um artigo ou curiosidade de bem-estar na aba Adicionais': 'Read an article or wellness tip in the Extras tab',
        'Janeiro': 'January', 'Fevereiro': 'February', 'Março': 'March', 'Abril': 'April',
        'Maio': 'May', 'Junho': 'June', 'Julho': 'July', 'Agosto': 'August',
        'Setembro': 'September', 'Outubro': 'October', 'Novembro': 'November', 'Dezembro': 'December',
        'Jan': 'Jan', 'Fev': 'Feb', 'Mar': 'Mar', 'Abr': 'Apr', 'Mai': 'May', 'Jun': 'Jun',
        'Jul': 'Jul', 'Ago': 'Aug', 'Set': 'Sep', 'Out': 'Oct', 'Nov': 'Nov', 'Dez': 'Dec',
        'Dom': 'Sun', 'Seg': 'Mon', 'Ter': 'Tue', 'Qua': 'Wed', 'Qui': 'Thu', 'Sex': 'Fri', 'Sáb': 'Sat',
        'Configurações & Acessibilidade': 'Settings & Accessibility',
        'Personalize sua experiência no HelpFull a qualquer momento.': 'Customize your HelpFull experience at any time.',
        'Aparência e Modo Noturno': 'Appearance & Dark Mode',
        'Acessibilidade e Leitura': 'Accessibility & Reading',
        'Do Dispositivo': 'From Device',
        'Concluir': 'Done',
        'Tudo pronto! Entrar no site': 'All set! Enter site',
        'Personalize tudo agora': 'Customize everything now',
        'Mais Contraste': 'High Contrast',
        'Texto Maior': 'Larger Text',
        'Sublinhar Links': 'Underline Links',
        'Reduzir Animações': 'Reduce Motion',
        'Tradutor de Libras (Gov.br)': 'Libras Translator (Gov.br)',
        'Notificações': 'Notifications',
        'Nenhuma notificação nova.': 'No new notifications.',
        'Limpar': 'Clear',
        'Ver': 'View',
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
        'Escrever um diário pode ajudar você a entender melhor seus pensamentos e sentimentos. Reserve alguns minutos para escrever sobre o seu dia, concentrando-se no que correu bem, no que o desafiou e como você se sentiu ao longo do dia. Você também pode selecionar as emoções que melhor descrevem seu humor hoje.':
            'Writing in a journal can help you better understand your thoughts and feelings. Take a few minutes to write about your day, focusing on what went well, what challenged you, and how you felt throughout the day. You can also select the emotions that best describe your mood today.',
        'Reserve alguns minutos para escrever sobre o seu dia, concentrando-se no que correu bem, no que o desafiou e como você se sentiu ao longo do dia. Você também pode selecionar as emoções que melhor descrevem seu humor hoje.':
            'Take a few minutes to write about your day, focusing on what went well, what challenged you, and how you felt throughout the day. You can also select the emotions that best describe your mood today.',
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
        'Bem-vindo(a) ao seu Diário! ✦': 'Welcome to your Journal! ✦',
        'Escrever um diário ajuda a entender melhor seus pensamentos e sentimentos. Reserve alguns minutos para expressar como foi o seu dia e selecione as emoções que melhor descrevem seu humor!':
            'Writing in a journal helps you better understand your thoughts and feelings. Take a few minutes to express how your day was and select the emotions that best describe your mood!',
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
        '"Cada pequeno passo conta."': '"Every small step counts."',
        'Cada pequeno passo conta.': 'Every small step counts.',
        'Sua jornada de autocuidado começa aqui. Acompanhe seu humor, registre seus pensamentos e descubra ferramentas para dias mais leves.':
            'Your self-care journey begins here. Track your mood, record your thoughts, and discover tools for lighter days.',
        '"Você não está sozinho nessa."': '"You are not alone in this."',
        'Você não está sozinho nessa.': 'You are not alone in this.',
        'Um espaço pensado pra te ouvir, te ajudar a organizar as emoções e caminhar rumo ao seu melhor bem-estar.':
            'A space designed to listen, help you organize your emotions, and guide you toward your best well-being.',
        '"Bem-estar é um hábito, não um destino."': '"Well-being is a habit, not a destination."',
        'Bem-estar é um hábito, não um destino.': 'Well-being is a habit, not a destination.',
        'Construa, dia após dia, uma rotina mais consciente com apoio emocional sempre à mão.':
            'Build, day by day, a more mindful routine with emotional support always within reach.',
        '"Sua mente merece atenção todos os dias."': '"Your mind deserves attention every day."',
        'Sua mente merece atenção todos os dias.': 'Your mind deserves attention every day.',
        'Entenda seus padrões emocionais, celebre suas conquistas e encontre apoio nos momentos mais difíceis.':
            'Understand your emotional patterns, celebrate your wins, and find support in the most difficult moments.',
        '"Um espaço só seu, para respirar e recomeçar."': '"A space of your own, to breathe and restart."',
        'Um espaço só seu, para respirar e recomeçar.': 'A space of your own, to breathe and restart.',
        'Aqui você encontra ferramentas simples para cuidar da sua saúde emocional, no seu tempo e do seu jeito.':
            'Here you will find simple tools to care for your emotional health, in your time and your way.',
        '"Pequenos hábitos, grandes transformações."': '"Small habits, big transformations."',
        'Pequenos hábitos, grandes transformações.': 'Small habits, big transformations.',
        'Registre, reflita e evolua. O HelpFull te acompanha em cada etapa da sua jornada emocional.':
            'Record, reflect, and evolve. HelpFull accompanies you at every stage of your emotional journey.',
        '"Cuidar de você também é produtivo."': '"Taking care of yourself is also productive."',
        'Cuidar de você também é produtivo.': 'Taking care of yourself is also productive.',
        'Organize seus pensamentos, entenda suas emoções e construa mais equilíbrio no seu dia a dia.':
            'Organize your thoughts, understand your emotions, and build more balance in your daily life.',
        '"Sua jornada emocional começa com um gesto simples."': '"Your emotional journey begins with a simple gesture."',
        'Sua jornada emocional começa com um gesto simples.': 'Your emotional journey begins with a simple gesture.',
        'Escrever, compartilhar e se cuidar. Tudo em um só lugar, feito para o seu bem-estar.':
            'Write, share, and take care of yourself. All in one place, made for your well-being.',
        '"Entenda o que você sente, no seu próprio ritmo."': '"Understand what you feel, at your own pace."',
        'Entenda o que você sente, no seu próprio ritmo.': 'Understand what you feel, at your own pace.',
        'Ferramentas pensadas para te ajudar a lidar com as emoções do dia a dia, com leveza e acolhimento.':
            'Tools designed to help you deal with everyday emotions, with lightness and warmth.',
        '"Porque toda emoção merece ser ouvida."': '"Because every emotion deserves to be heard."',
        'Porque toda emoção merece ser ouvida.': 'Because every emotion deserves to be heard.',
        'Um ambiente seguro para desabafar, refletir e crescer emocionalmente, sempre que você precisar.':
            'A safe environment to vent, reflect, and grow emotionally, whenever you need it.',
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

    var selConteudo = '.post-texto,.post-nome,.post-data,.comentario-nome,.post-avatar,.post-midias-container,.attachment-name,[vw],[vw-plugin-wrapper],.vw-plugin-wrapper,#vlibras-access-wrapper,#vlibras-app-root';

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
                var target = mutations[i].target;
                if (target && target.closest && target.closest('[vw], [vw-plugin-wrapper], .vw-plugin-wrapper, #vlibras-access-wrapper, #vlibras-app-root')) {
                    continue;
                }
                if (mutations[i].addedNodes && mutations[i].addedNodes.length > 0) {
                    for (var j = 0; j < mutations[i].addedNodes.length; j++) {
                        var node = mutations[i].addedNodes[j];
                        if (node.nodeType === 1 && (node.matches && (node.matches('[vw], [vw] *, [vw-plugin-wrapper], .vw-plugin-wrapper, #vlibras-access-wrapper, #vlibras-app-root') || (node.closest && node.closest('[vw], [vw-plugin-wrapper]'))))) {
                            continue;
                        }
                        temNovosNodes = true;
                        break;
                    }
                }
            }
            if (temNovosNodes) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(function () {
                    traduzirPagina();
                }, 150);
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

        var badge = document.getElementById('painelAcessBadge');
        var tit = document.getElementById('painelAcessTitulo');
        var sub = document.getElementById('painelAcessSubtitulo');
        var secTema = document.getElementById('secaoTemaTitulo');
        var secAcess = document.getElementById('secaoAcessTitulo');
        var rotIdioma = document.getElementById('rotuloIdiomaPainel');
        var btnConcluir = document.querySelector('.btn-painel-fechar');
        var res = document.querySelector('.acessibilidade-resetar');
        var fec = document.querySelector('.acessibilidade-fechar');

        if (idioma === 'en') {
            if (badge) badge.innerHTML = (typeof svgIconesPainel !== 'undefined' ? svgIconesPainel.gear : '') + ' <span>Preferences</span>';
            if (tit) tit.textContent = 'Settings & Accessibility';
            if (sub) sub.textContent = 'Customize your HelpFull experience at any time.';
            if (secTema) secTema.innerHTML = (typeof svgIconesPainel !== 'undefined' ? svgIconesPainel.palette : '') + ' <span>Appearance & Dark Mode</span>';
            if (secAcess) secAcess.innerHTML = (typeof svgIconesPainel !== 'undefined' ? svgIconesPainel.sliders : '') + ' <span>Accessibility & Reading</span>';
            if (rotIdioma) rotIdioma.innerHTML = (typeof svgIconesPainel !== 'undefined' ? svgIconesPainel.globe : '') + ' <span>Language</span>';
            if (btnConcluir) btnConcluir.textContent = 'Done';
            if (res) res.textContent = 'Restore defaults';
            if (fec) fec.setAttribute('aria-label', 'Close settings');
            document.querySelectorAll('.painel-tema-btn').forEach(function (btn) {
                var tm = btn.getAttribute('data-tema');
                var span = btn.querySelector('.tema-nome');
                if (span) {
                    if (tm === 'claro') span.textContent = 'Light';
                    else if (tm === 'escuro') span.textContent = 'Dark';
                    else if (tm === 'sistema') span.textContent = 'From Device';
                }
            });
            document.querySelectorAll('.painel-opt-row').forEach(function (row) {
                var k = row.getAttribute('data-acess-key');
                var span = row.querySelector('.onboarding-opt-nome');
                if (span) {
                    if (k === 'contraste') span.textContent = 'High Contrast';
                    else if (k === 'textoGrande') span.textContent = 'Larger Text';
                    else if (k === 'sublinhar') span.textContent = 'Underline Links';
                    else if (k === 'semAnimacao') span.textContent = 'Reduce Motion';
                    else if (k === 'libras') span.textContent = 'Libras Translator (Gov.br)';
                }
            });
        } else {
            if (badge) badge.innerHTML = (typeof svgIconesPainel !== 'undefined' ? svgIconesPainel.gear : '') + ' <span>Preferências</span>';
            if (tit) tit.textContent = 'Configurações & Acessibilidade';
            if (sub) sub.textContent = 'Personalize sua experiência no HelpFull a qualquer momento.';
            if (secTema) secTema.innerHTML = (typeof svgIconesPainel !== 'undefined' ? svgIconesPainel.palette : '') + ' <span>Aparência e Modo Noturno</span>';
            if (secAcess) secAcess.innerHTML = (typeof svgIconesPainel !== 'undefined' ? svgIconesPainel.sliders : '') + ' <span>Acessibilidade e Leitura</span>';
            if (rotIdioma) rotIdioma.innerHTML = (typeof svgIconesPainel !== 'undefined' ? svgIconesPainel.globe : '') + ' <span>Idioma / Language</span>';
            if (btnConcluir) btnConcluir.textContent = 'Concluir';
            if (res) res.textContent = 'Restaurar padrão';
            if (fec) fec.setAttribute('aria-label', 'Fechar configurações');
            document.querySelectorAll('.painel-tema-btn').forEach(function (btn) {
                var tm = btn.getAttribute('data-tema');
                var span = btn.querySelector('.tema-nome');
                if (span) {
                    if (tm === 'claro') span.textContent = 'Claro';
                    else if (tm === 'escuro') span.textContent = 'Escuro';
                    else if (tm === 'sistema') span.textContent = 'Do Dispositivo';
                }
            });
            document.querySelectorAll('.painel-opt-row').forEach(function (row) {
                var k = row.getAttribute('data-acess-key');
                var span = row.querySelector('.onboarding-opt-nome');
                if (span) {
                    if (k === 'contraste') span.textContent = 'Mais Contraste';
                    else if (k === 'textoGrande') span.textContent = 'Texto Maior';
                    else if (k === 'sublinhar') span.textContent = 'Sublinhar Links';
                    else if (k === 'semAnimacao') span.textContent = 'Reduzir Animações';
                    else if (k === 'libras') span.textContent = 'Tradutor de Libras (Gov.br)';
                }
            });
        }

        var titNotif = document.getElementById('tituloNotificacoesPerfil');
        var btnLimparNotif = document.getElementById('btnLimparNotificacoesPerfil');
        if (idioma === 'en') {
            if (titNotif) titNotif.textContent = 'Notifications';
            if (btnLimparNotif) btnLimparNotif.textContent = 'Clear';
        } else {
            if (titNotif) titNotif.textContent = 'Notificações';
            if (btnLimparNotif) btnLimparNotif.textContent = 'Limpar';
        }

        document.querySelectorAll('.nav-links a,.links-capsula a,.nav-dropdown-mobile a').forEach(function (a) {
            var url = (a.getAttribute('href') || '').split('?')[0].split('#')[0];
            if (t.nav[url]) a.textContent = t.nav[url];
        });

        if (typeof atualizarPreviewPainel === 'function') {
            atualizarPreviewPainel('padrao');
        }

        traduzirPagina();
        iniciarObservadorTraducao();
    }

    function gerenciarVLibras(ativo) {
        var wrapper = document.getElementById('vlibras-access-wrapper');
        var appRoot = document.getElementById('vlibras-app-root');
        var legacyVw = document.querySelector('[vw]');
        if (legacyVw) {
            try { legacyVw.remove(); } catch (e) { legacyVw.style.display = 'none'; }
        }

        if (ativo) {
            if (wrapper) {
                wrapper.classList.remove('vlibras-oculto');
                wrapper.style.removeProperty('display');
            }
            if (appRoot) {
                appRoot.classList.remove('vlibras-oculto');
                appRoot.style.removeProperty('display');
            }

            var iniciarWidget = function () {
                try {
                    if (window.VLibras && !window._vlibrasIniciado) {
                        window._vlibrasInstance = new window.VLibras.Widget('https://vlibras.gov.br/app');
                        window._vlibrasIniciado = true;
                    }
                    var w = document.getElementById('vlibras-access-wrapper');
                    if (w) {
                        w.classList.remove('vlibras-oculto');
                        w.style.removeProperty('display');
                    }
                } catch (e) {
                    console.warn('Erro ao inicializar VLibras:', e);
                }
            };

            if (typeof window.VLibras === 'undefined') {
                var scriptExistente = document.getElementById('vlibrasGovScript');
                if (!scriptExistente) {
                    var script = document.createElement('script');
                    script.id = 'vlibrasGovScript';
                    script.src = 'https://vlibras.gov.br/app/vlibras-plugin.js';
                    script.async = true;
                    script.onload = iniciarWidget;
                    script.onerror = function () {
                        console.warn('Não foi possível carregar a API do VLibras Gov.');
                    };
                    document.body.appendChild(script);
                } else {
                    scriptExistente.addEventListener('load', iniciarWidget);
                }
            } else {
                iniciarWidget();
            }
        } else {
            // Oculta e desativa todos os elementos do VLibras da tela
            if (wrapper) {
                wrapper.classList.add('vlibras-oculto');
                wrapper.style.setProperty('display', 'none', 'important');
            }
            if (appRoot) {
                appRoot.classList.add('vlibras-oculto');
                appRoot.style.setProperty('display', 'none', 'important');
                if (appRoot.dataset) appRoot.dataset.active = 'false';
            }
            var allVw = document.querySelectorAll('[vw], .vw-plugin-wrapper');
            allVw.forEach(function(el) {
                el.classList.add('vlibras-oculto');
                el.style.setProperty('display', 'none', 'important');
            });
            try {
                if (localStorage.getItem('@vlibras-widget')) {
                    var c = JSON.parse(localStorage.getItem('@vlibras-widget') || '{}');
                    c.isOpen = false;
                    localStorage.setItem('@vlibras-widget', JSON.stringify(c));
                }
            } catch (e) {}
        }
    }

    // ========================================================
    // GERENCIAMENTO DA COR TEMA DO SITE (EXPANSÍVEL E COMPACTO)
    // ========================================================
window.HelpFullPaletaCores = window.HelpFullPaletaCores || {
        'padrao': {
            id: 'padrao',
            nome: 'Azul HelpFull (Original)',
            tipo: 'padrao',
            corBolinha: '#2b7a8c',
            primaria: '#2b7a8c',
            hover: '#236878',
            ativa: '#1a5360',
            clara: '#38a3b8',
            suave: 'rgba(43, 122, 140, 0.12)',
            borda: 'rgba(43, 122, 140, 0.28)',
            sombra: 'rgba(43, 122, 140, 0.35)',
            bgGradClaro: ['#f2f8fa', '#def0f6', '#86d1e4', '#2178a2', '#0c2b42'],
            bgGradEscuro: ['#15212a', '#0e1820', '#050a0e'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(180, 235, 248, 0.12)' },
                { color1: 'rgba(114, 210, 232, 0.48)', color2: 'rgba(180, 238, 248, 0.16)' },
                { color1: 'rgba(56, 185, 220, 0.56)', color2: 'rgba(114, 210, 232, 0.20)' },
                { color1: 'rgba(30, 112, 160, 0.65)', color2: 'rgba(56, 185, 220, 0.22)' },
                { color1: 'rgba(20, 75, 111, 0.78)', color2: 'rgba(30, 112, 160, 0.30)' },
                { color1: 'rgba(12, 43, 66, 0.90)', color2: 'rgba(20, 75, 111, 0.45)' }
            ],
            glow: ['rgba(72, 202, 228, 0.44)', 'rgba(30, 112, 160, 0.25)']
        },
        'c1_turquesa_claro': {
            id: 'c1_turquesa_claro',
            nome: 'Turquesa',
            tipo: 'claro',
            corBolinha: '#1ABC9C',
            primaria: '#1ABC9C',
            hover: '#16A085',
            ativa: '#138770',
            clara: '#5FD0BA',
            suave: 'rgba(26, 188, 156, 0.12)',
            borda: 'rgba(26, 188, 156, 0.28)',
            sombra: 'rgba(26, 188, 156, 0.35)',
            bgGradClaro: ['#ffffff', 'rgba(26, 188, 156, 0.10)', 'rgba(26, 188, 156, 0.28)', '#1ABC9C', '#041C17'],
            bgGradEscuro: ['#041C17', '#020F0C', '#010605'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(26, 188, 156, 0.14)' },
                { color1: 'rgba(26, 188, 156, 0.48)', color2: 'rgba(26, 188, 156, 0.18)' },
                { color1: 'rgba(26, 188, 156, 0.58)', color2: 'rgba(26, 188, 156, 0.24)' },
                { color1: '#1ABC9C', color2: 'rgba(26, 188, 156, 0.30)' },
                { color1: '#16A085', color2: 'rgba(26, 188, 156, 0.40)' },
                { color1: '#041C17', color2: '#138770' }
            ],
            glow: ['rgba(26, 188, 156, 0.45)', '#16A085']
        },
        'c2_verde_claro': {
            id: 'c2_verde_claro',
            nome: 'Verde Esmeralda',
            tipo: 'claro',
            corBolinha: '#2ECC71',
            primaria: '#2ECC71',
            hover: '#27AD60',
            ativa: '#219351',
            clara: '#6DDB9C',
            suave: 'rgba(46, 204, 113, 0.12)',
            borda: 'rgba(46, 204, 113, 0.28)',
            sombra: 'rgba(46, 204, 113, 0.35)',
            bgGradClaro: ['#ffffff', 'rgba(46, 204, 113, 0.10)', 'rgba(46, 204, 113, 0.28)', '#2ECC71', '#071F11'],
            bgGradEscuro: ['#071F11', '#041009', '#010603'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(46, 204, 113, 0.14)' },
                { color1: 'rgba(46, 204, 113, 0.48)', color2: 'rgba(46, 204, 113, 0.18)' },
                { color1: 'rgba(46, 204, 113, 0.58)', color2: 'rgba(46, 204, 113, 0.24)' },
                { color1: '#2ECC71', color2: 'rgba(46, 204, 113, 0.30)' },
                { color1: '#27AD60', color2: 'rgba(46, 204, 113, 0.40)' },
                { color1: '#071F11', color2: '#219351' }
            ],
            glow: ['rgba(46, 204, 113, 0.45)', '#27AD60']
        },
        'c3_azul_claro': {
            id: 'c3_azul_claro',
            nome: 'Azul Celeste',
            tipo: 'claro',
            corBolinha: '#3498DB',
            primaria: '#3498DB',
            hover: '#2C81BA',
            ativa: '#256D9E',
            clara: '#71B7E6',
            suave: 'rgba(52, 152, 219, 0.12)',
            borda: 'rgba(52, 152, 219, 0.28)',
            sombra: 'rgba(52, 152, 219, 0.35)',
            bgGradClaro: ['#ffffff', 'rgba(52, 152, 219, 0.10)', 'rgba(52, 152, 219, 0.28)', '#3498DB', '#081721'],
            bgGradEscuro: ['#081721', '#040C12', '#020507'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(52, 152, 219, 0.14)' },
                { color1: 'rgba(52, 152, 219, 0.48)', color2: 'rgba(52, 152, 219, 0.18)' },
                { color1: 'rgba(52, 152, 219, 0.58)', color2: 'rgba(52, 152, 219, 0.24)' },
                { color1: '#3498DB', color2: 'rgba(52, 152, 219, 0.30)' },
                { color1: '#2C81BA', color2: 'rgba(52, 152, 219, 0.40)' },
                { color1: '#081721', color2: '#256D9E' }
            ],
            glow: ['rgba(52, 152, 219, 0.45)', '#2C81BA']
        },
        'c4_roxo_claro': {
            id: 'c4_roxo_claro',
            nome: 'Roxo Ametista',
            tipo: 'claro',
            corBolinha: '#9B59B6',
            primaria: '#9B59B6',
            hover: '#844C9B',
            ativa: '#704083',
            clara: '#B98BCC',
            suave: 'rgba(155, 89, 182, 0.12)',
            borda: 'rgba(155, 89, 182, 0.28)',
            sombra: 'rgba(155, 89, 182, 0.35)',
            bgGradClaro: ['#ffffff', 'rgba(155, 89, 182, 0.10)', 'rgba(155, 89, 182, 0.28)', '#9B59B6', '#170D1B'],
            bgGradEscuro: ['#170D1B', '#0C070F', '#050305'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(155, 89, 182, 0.14)' },
                { color1: 'rgba(155, 89, 182, 0.48)', color2: 'rgba(155, 89, 182, 0.18)' },
                { color1: 'rgba(155, 89, 182, 0.58)', color2: 'rgba(155, 89, 182, 0.24)' },
                { color1: '#9B59B6', color2: 'rgba(155, 89, 182, 0.30)' },
                { color1: '#844C9B', color2: 'rgba(155, 89, 182, 0.40)' },
                { color1: '#170D1B', color2: '#704083' }
            ],
            glow: ['rgba(155, 89, 182, 0.45)', '#844C9B']
        },
        'c5_rosa_claro': {
            id: 'c5_rosa_claro',
            nome: 'Rosa Pink',
            tipo: 'claro',
            corBolinha: '#E91E63',
            primaria: '#E91E63',
            hover: '#C61A54',
            ativa: '#A81647',
            clara: '#F06292',
            suave: 'rgba(233, 30, 99, 0.12)',
            borda: 'rgba(233, 30, 99, 0.28)',
            sombra: 'rgba(233, 30, 99, 0.35)',
            bgGradClaro: ['#ffffff', 'rgba(233, 30, 99, 0.10)', 'rgba(233, 30, 99, 0.28)', '#E91E63', '#23040F'],
            bgGradEscuro: ['#23040F', '#130208', '#070103'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(233, 30, 99, 0.14)' },
                { color1: 'rgba(233, 30, 99, 0.48)', color2: 'rgba(233, 30, 99, 0.18)' },
                { color1: 'rgba(233, 30, 99, 0.58)', color2: 'rgba(233, 30, 99, 0.24)' },
                { color1: '#E91E63', color2: 'rgba(233, 30, 99, 0.30)' },
                { color1: '#C61A54', color2: 'rgba(233, 30, 99, 0.40)' },
                { color1: '#23040F', color2: '#A81647' }
            ],
            glow: ['rgba(233, 30, 99, 0.45)', '#C61A54']
        },
        'c6_amarelo_claro': {
            id: 'c6_amarelo_claro',
            nome: 'Amarelo Ouro',
            tipo: 'claro',
            corBolinha: '#F1C40F',
            primaria: '#F1C40F',
            hover: '#CDA70D',
            ativa: '#AE8D0B',
            clara: '#F5D657',
            suave: 'rgba(241, 196, 15, 0.12)',
            borda: 'rgba(241, 196, 15, 0.28)',
            sombra: 'rgba(241, 196, 15, 0.35)',
            bgGradClaro: ['#ffffff', 'rgba(241, 196, 15, 0.10)', 'rgba(241, 196, 15, 0.28)', '#F1C40F', '#241D02'],
            bgGradEscuro: ['#241D02', '#131001', '#070600'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(241, 196, 15, 0.14)' },
                { color1: 'rgba(241, 196, 15, 0.48)', color2: 'rgba(241, 196, 15, 0.18)' },
                { color1: 'rgba(241, 196, 15, 0.58)', color2: 'rgba(241, 196, 15, 0.24)' },
                { color1: '#F1C40F', color2: 'rgba(241, 196, 15, 0.30)' },
                { color1: '#CDA70D', color2: 'rgba(241, 196, 15, 0.40)' },
                { color1: '#241D02', color2: '#AE8D0B' }
            ],
            glow: ['rgba(241, 196, 15, 0.45)', '#CDA70D']
        },
        'c7_laranja_claro': {
            id: 'c7_laranja_claro',
            nome: 'Laranja',
            tipo: 'claro',
            corBolinha: '#E67E22',
            primaria: '#E67E22',
            hover: '#C46B1D',
            ativa: '#A65B18',
            clara: '#EEA564',
            suave: 'rgba(230, 126, 34, 0.12)',
            borda: 'rgba(230, 126, 34, 0.28)',
            sombra: 'rgba(230, 126, 34, 0.35)',
            bgGradClaro: ['#ffffff', 'rgba(230, 126, 34, 0.10)', 'rgba(230, 126, 34, 0.28)', '#E67E22', '#221305'],
            bgGradEscuro: ['#221305', '#120A03', '#070401'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(230, 126, 34, 0.14)' },
                { color1: 'rgba(230, 126, 34, 0.48)', color2: 'rgba(230, 126, 34, 0.18)' },
                { color1: 'rgba(230, 126, 34, 0.58)', color2: 'rgba(230, 126, 34, 0.24)' },
                { color1: '#E67E22', color2: 'rgba(230, 126, 34, 0.30)' },
                { color1: '#C46B1D', color2: 'rgba(230, 126, 34, 0.40)' },
                { color1: '#221305', color2: '#A65B18' }
            ],
            glow: ['rgba(230, 126, 34, 0.45)', '#C46B1D']
        },
        'c8_coral_claro': {
            id: 'c8_coral_claro',
            nome: 'Vermelho Coral',
            tipo: 'claro',
            corBolinha: '#E74C3C',
            primaria: '#E74C3C',
            hover: '#C44133',
            ativa: '#A6372B',
            clara: '#EE8276',
            suave: 'rgba(231, 76, 60, 0.12)',
            borda: 'rgba(231, 76, 60, 0.28)',
            sombra: 'rgba(231, 76, 60, 0.35)',
            bgGradClaro: ['#ffffff', 'rgba(231, 76, 60, 0.10)', 'rgba(231, 76, 60, 0.28)', '#E74C3C', '#230B09'],
            bgGradEscuro: ['#230B09', '#120605', '#070202'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(231, 76, 60, 0.14)' },
                { color1: 'rgba(231, 76, 60, 0.48)', color2: 'rgba(231, 76, 60, 0.18)' },
                { color1: 'rgba(231, 76, 60, 0.58)', color2: 'rgba(231, 76, 60, 0.24)' },
                { color1: '#E74C3C', color2: 'rgba(231, 76, 60, 0.30)' },
                { color1: '#C44133', color2: 'rgba(231, 76, 60, 0.40)' },
                { color1: '#230B09', color2: '#A6372B' }
            ],
            glow: ['rgba(231, 76, 60, 0.45)', '#C44133']
        },
        'c9_prata_claro': {
            id: 'c9_prata_claro',
            nome: 'Prata Suave',
            tipo: 'claro',
            corBolinha: '#95A5A6',
            primaria: '#95A5A6',
            hover: '#7F8C8D',
            ativa: '#6B7778',
            clara: '#B5C0C1',
            suave: 'rgba(149, 165, 166, 0.12)',
            borda: 'rgba(149, 165, 166, 0.28)',
            sombra: 'rgba(149, 165, 166, 0.35)',
            bgGradClaro: ['#ffffff', 'rgba(149, 165, 166, 0.10)', 'rgba(149, 165, 166, 0.28)', '#95A5A6', '#161919'],
            bgGradEscuro: ['#161919', '#0C0D0D', '#040505'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(149, 165, 166, 0.14)' },
                { color1: 'rgba(149, 165, 166, 0.48)', color2: 'rgba(149, 165, 166, 0.18)' },
                { color1: 'rgba(149, 165, 166, 0.58)', color2: 'rgba(149, 165, 166, 0.24)' },
                { color1: '#95A5A6', color2: 'rgba(149, 165, 166, 0.30)' },
                { color1: '#7F8C8D', color2: 'rgba(149, 165, 166, 0.40)' },
                { color1: '#161919', color2: '#6B7778' }
            ],
            glow: ['rgba(149, 165, 166, 0.45)', '#7F8C8D']
        },
        'c10_aco_claro': {
            id: 'c10_aco_claro',
            nome: 'Azul Aço',
            tipo: 'claro',
            corBolinha: '#607D8B',
            primaria: '#607D8B',
            hover: '#526A76',
            ativa: '#455A64',
            clara: '#90A4AE',
            suave: 'rgba(96, 125, 139, 0.12)',
            borda: 'rgba(96, 125, 139, 0.28)',
            sombra: 'rgba(96, 125, 139, 0.35)',
            bgGradClaro: ['#ffffff', 'rgba(96, 125, 139, 0.10)', 'rgba(96, 125, 139, 0.28)', '#607D8B', '#0E1315'],
            bgGradEscuro: ['#0E1315', '#080A0B', '#030404'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(96, 125, 139, 0.14)' },
                { color1: 'rgba(96, 125, 139, 0.48)', color2: 'rgba(96, 125, 139, 0.18)' },
                { color1: 'rgba(96, 125, 139, 0.58)', color2: 'rgba(96, 125, 139, 0.24)' },
                { color1: '#607D8B', color2: 'rgba(96, 125, 139, 0.30)' },
                { color1: '#526A76', color2: 'rgba(96, 125, 139, 0.40)' },
                { color1: '#0E1315', color2: '#455A64' }
            ],
            glow: ['rgba(96, 125, 139, 0.45)', '#526A76']
        },
        'c1_turquesa_escuro': {
            id: 'c1_turquesa_escuro',
            nome: 'Turquesa Escuro',
            tipo: 'escuro',
            corBolinha: '#11806A',
            primaria: '#11806A',
            hover: '#0E6D5A',
            ativa: '#0C5C4C',
            clara: '#58A697',
            suave: 'rgba(17, 128, 106, 0.12)',
            borda: 'rgba(17, 128, 106, 0.28)',
            sombra: 'rgba(17, 128, 106, 0.35)',
            bgGradClaro: ['#ffffff', 'rgba(17, 128, 106, 0.10)', 'rgba(17, 128, 106, 0.28)', '#11806A', '#031310'],
            bgGradEscuro: ['#031310', '#010A08', '#010403'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(17, 128, 106, 0.14)' },
                { color1: 'rgba(17, 128, 106, 0.48)', color2: 'rgba(17, 128, 106, 0.18)' },
                { color1: 'rgba(17, 128, 106, 0.58)', color2: 'rgba(17, 128, 106, 0.24)' },
                { color1: '#11806A', color2: 'rgba(17, 128, 106, 0.30)' },
                { color1: '#0E6D5A', color2: 'rgba(17, 128, 106, 0.40)' },
                { color1: '#031310', color2: '#0C5C4C' }
            ],
            glow: ['rgba(17, 128, 106, 0.45)', '#0E6D5A']
        },
        'c2_verde_escuro': {
            id: 'c2_verde_escuro',
            nome: 'Verde Floresta',
            tipo: 'escuro',
            corBolinha: '#1F8B4C',
            primaria: '#1F8B4C',
            hover: '#1A7641',
            ativa: '#166437',
            clara: '#62AE82',
            suave: 'rgba(31, 139, 76, 0.12)',
            borda: 'rgba(31, 139, 76, 0.28)',
            sombra: 'rgba(31, 139, 76, 0.35)',
            bgGradClaro: ['#ffffff', 'rgba(31, 139, 76, 0.10)', 'rgba(31, 139, 76, 0.28)', '#1F8B4C', '#05150B'],
            bgGradEscuro: ['#05150B', '#020B06', '#010402'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(31, 139, 76, 0.14)' },
                { color1: 'rgba(31, 139, 76, 0.48)', color2: 'rgba(31, 139, 76, 0.18)' },
                { color1: 'rgba(31, 139, 76, 0.58)', color2: 'rgba(31, 139, 76, 0.24)' },
                { color1: '#1F8B4C', color2: 'rgba(31, 139, 76, 0.30)' },
                { color1: '#1A7641', color2: 'rgba(31, 139, 76, 0.40)' },
                { color1: '#05150B', color2: '#166437' }
            ],
            glow: ['rgba(31, 139, 76, 0.45)', '#1A7641']
        },
        'c3_azul_escuro': {
            id: 'c3_azul_escuro',
            nome: 'Azul Noturno',
            tipo: 'escuro',
            corBolinha: '#206694',
            primaria: '#206694',
            hover: '#1B577E',
            ativa: '#17496B',
            clara: '#6394B4',
            suave: 'rgba(32, 102, 148, 0.12)',
            borda: 'rgba(32, 102, 148, 0.28)',
            sombra: 'rgba(32, 102, 148, 0.35)',
            bgGradClaro: ['#ffffff', 'rgba(32, 102, 148, 0.10)', 'rgba(32, 102, 148, 0.28)', '#206694', '#050F16'],
            bgGradEscuro: ['#050F16', '#03080C', '#010304'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(32, 102, 148, 0.14)' },
                { color1: 'rgba(32, 102, 148, 0.48)', color2: 'rgba(32, 102, 148, 0.18)' },
                { color1: 'rgba(32, 102, 148, 0.58)', color2: 'rgba(32, 102, 148, 0.24)' },
                { color1: '#206694', color2: 'rgba(32, 102, 148, 0.30)' },
                { color1: '#1B577E', color2: 'rgba(32, 102, 148, 0.40)' },
                { color1: '#050F16', color2: '#17496B' }
            ],
            glow: ['rgba(32, 102, 148, 0.45)', '#1B577E']
        },
        'c4_roxo_escuro': {
            id: 'c4_roxo_escuro',
            nome: 'Roxo Profundo',
            tipo: 'escuro',
            corBolinha: '#71368A',
            primaria: '#71368A',
            hover: '#602E75',
            ativa: '#512763',
            clara: '#9C72AD',
            suave: 'rgba(113, 54, 138, 0.12)',
            borda: 'rgba(113, 54, 138, 0.28)',
            sombra: 'rgba(113, 54, 138, 0.35)',
            bgGradClaro: ['#ffffff', 'rgba(113, 54, 138, 0.10)', 'rgba(113, 54, 138, 0.28)', '#71368A', '#110815'],
            bgGradEscuro: ['#110815', '#09040B', '#030204'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(113, 54, 138, 0.14)' },
                { color1: 'rgba(113, 54, 138, 0.48)', color2: 'rgba(113, 54, 138, 0.18)' },
                { color1: 'rgba(113, 54, 138, 0.58)', color2: 'rgba(113, 54, 138, 0.24)' },
                { color1: '#71368A', color2: 'rgba(113, 54, 138, 0.30)' },
                { color1: '#602E75', color2: 'rgba(113, 54, 138, 0.40)' },
                { color1: '#110815', color2: '#512763' }
            ],
            glow: ['rgba(113, 54, 138, 0.45)', '#602E75']
        },
        'c5_rosa_escuro': {
            id: 'c5_rosa_escuro',
            nome: 'Carmesim Escuro',
            tipo: 'escuro',
            corBolinha: '#AD1457',
            primaria: '#AD1457',
            hover: '#93114A',
            ativa: '#7D0E3F',
            clara: '#C65A89',
            suave: 'rgba(173, 20, 87, 0.12)',
            borda: 'rgba(173, 20, 87, 0.28)',
            sombra: 'rgba(173, 20, 87, 0.35)',
            bgGradClaro: ['#ffffff', 'rgba(173, 20, 87, 0.10)', 'rgba(173, 20, 87, 0.28)', '#AD1457', '#1A030D'],
            bgGradEscuro: ['#1A030D', '#0E0207', '#050103'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(173, 20, 87, 0.14)' },
                { color1: 'rgba(173, 20, 87, 0.48)', color2: 'rgba(173, 20, 87, 0.18)' },
                { color1: 'rgba(173, 20, 87, 0.58)', color2: 'rgba(173, 20, 87, 0.24)' },
                { color1: '#AD1457', color2: 'rgba(173, 20, 87, 0.30)' },
                { color1: '#93114A', color2: 'rgba(173, 20, 87, 0.40)' },
                { color1: '#1A030D', color2: '#7D0E3F' }
            ],
            glow: ['rgba(173, 20, 87, 0.45)', '#93114A']
        },
        'c6_amarelo_escuro': {
            id: 'c6_amarelo_escuro',
            nome: 'Ouro Mostarda',
            tipo: 'escuro',
            corBolinha: '#C27C0E',
            primaria: '#C27C0E',
            hover: '#A5690C',
            ativa: '#8C590A',
            clara: '#D4A356',
            suave: 'rgba(194, 124, 14, 0.12)',
            borda: 'rgba(194, 124, 14, 0.28)',
            sombra: 'rgba(194, 124, 14, 0.35)',
            bgGradClaro: ['#ffffff', 'rgba(194, 124, 14, 0.10)', 'rgba(194, 124, 14, 0.28)', '#C27C0E', '#1D1302'],
            bgGradEscuro: ['#1D1302', '#100A01', '#060400'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(194, 124, 14, 0.14)' },
                { color1: 'rgba(194, 124, 14, 0.48)', color2: 'rgba(194, 124, 14, 0.18)' },
                { color1: 'rgba(194, 124, 14, 0.58)', color2: 'rgba(194, 124, 14, 0.24)' },
                { color1: '#C27C0E', color2: 'rgba(194, 124, 14, 0.30)' },
                { color1: '#A5690C', color2: 'rgba(194, 124, 14, 0.40)' },
                { color1: '#1D1302', color2: '#8C590A' }
            ],
            glow: ['rgba(194, 124, 14, 0.45)', '#A5690C']
        },
        'c7_laranja_escuro': {
            id: 'c7_laranja_escuro',
            nome: 'Terracota Escura',
            tipo: 'escuro',
            corBolinha: '#A84300',
            primaria: '#A84300',
            hover: '#8F3900',
            ativa: '#793000',
            clara: '#C27B4C',
            suave: 'rgba(168, 67, 0, 0.12)',
            borda: 'rgba(168, 67, 0, 0.28)',
            sombra: 'rgba(168, 67, 0, 0.35)',
            bgGradClaro: ['#ffffff', 'rgba(168, 67, 0, 0.10)', 'rgba(168, 67, 0, 0.28)', '#A84300', '#190A00'],
            bgGradEscuro: ['#190A00', '#0D0500', '#050200'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(168, 67, 0, 0.14)' },
                { color1: 'rgba(168, 67, 0, 0.48)', color2: 'rgba(168, 67, 0, 0.18)' },
                { color1: 'rgba(168, 67, 0, 0.58)', color2: 'rgba(168, 67, 0, 0.24)' },
                { color1: '#A84300', color2: 'rgba(168, 67, 0, 0.30)' },
                { color1: '#8F3900', color2: 'rgba(168, 67, 0, 0.40)' },
                { color1: '#190A00', color2: '#793000' }
            ],
            glow: ['rgba(168, 67, 0, 0.45)', '#8F3900']
        },
        'c8_coral_escuro': {
            id: 'c8_coral_escuro',
            nome: 'Vinho Bordô',
            tipo: 'escuro',
            corBolinha: '#992D22',
            primaria: '#992D22',
            hover: '#82261D',
            ativa: '#6E2018',
            clara: '#B86C64',
            suave: 'rgba(153, 45, 34, 0.12)',
            borda: 'rgba(153, 45, 34, 0.28)',
            sombra: 'rgba(153, 45, 34, 0.35)',
            bgGradClaro: ['#ffffff', 'rgba(153, 45, 34, 0.10)', 'rgba(153, 45, 34, 0.28)', '#992D22', '#170705'],
            bgGradEscuro: ['#170705', '#0C0403', '#050101'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(153, 45, 34, 0.14)' },
                { color1: 'rgba(153, 45, 34, 0.48)', color2: 'rgba(153, 45, 34, 0.18)' },
                { color1: 'rgba(153, 45, 34, 0.58)', color2: 'rgba(153, 45, 34, 0.24)' },
                { color1: '#992D22', color2: 'rgba(153, 45, 34, 0.30)' },
                { color1: '#82261D', color2: 'rgba(153, 45, 34, 0.40)' },
                { color1: '#170705', color2: '#6E2018' }
            ],
            glow: ['rgba(153, 45, 34, 0.45)', '#82261D']
        },
        'c9_prata_escuro': {
            id: 'c9_prata_escuro',
            nome: 'Cinza Chumbo',
            tipo: 'escuro',
            corBolinha: '#979C9F',
            primaria: '#979C9F',
            hover: '#808587',
            ativa: '#6D7072',
            clara: '#B6BABC',
            suave: 'rgba(151, 156, 159, 0.12)',
            borda: 'rgba(151, 156, 159, 0.28)',
            sombra: 'rgba(151, 156, 159, 0.35)',
            bgGradClaro: ['#ffffff', 'rgba(151, 156, 159, 0.10)', 'rgba(151, 156, 159, 0.28)', '#979C9F', '#171718'],
            bgGradEscuro: ['#171718', '#0C0C0D', '#050505'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(151, 156, 159, 0.14)' },
                { color1: 'rgba(151, 156, 159, 0.48)', color2: 'rgba(151, 156, 159, 0.18)' },
                { color1: 'rgba(151, 156, 159, 0.58)', color2: 'rgba(151, 156, 159, 0.24)' },
                { color1: '#979C9F', color2: 'rgba(151, 156, 159, 0.30)' },
                { color1: '#808587', color2: 'rgba(151, 156, 159, 0.40)' },
                { color1: '#171718', color2: '#6D7072' }
            ],
            glow: ['rgba(151, 156, 159, 0.45)', '#808587']
        },
        'c10_aco_escuro': {
            id: 'c10_aco_escuro',
            nome: 'Ardósia Petróleo',
            tipo: 'escuro',
            corBolinha: '#546E7A',
            primaria: '#546E7A',
            hover: '#475E68',
            ativa: '#3C4F58',
            clara: '#879AA2',
            suave: 'rgba(84, 110, 122, 0.12)',
            borda: 'rgba(84, 110, 122, 0.28)',
            sombra: 'rgba(84, 110, 122, 0.35)',
            bgGradClaro: ['#ffffff', 'rgba(84, 110, 122, 0.10)', 'rgba(84, 110, 122, 0.28)', '#546E7A', '#0D1012'],
            bgGradEscuro: ['#0D1012', '#07090A', '#030304'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(84, 110, 122, 0.14)' },
                { color1: 'rgba(84, 110, 122, 0.48)', color2: 'rgba(84, 110, 122, 0.18)' },
                { color1: 'rgba(84, 110, 122, 0.58)', color2: 'rgba(84, 110, 122, 0.24)' },
                { color1: '#546E7A', color2: 'rgba(84, 110, 122, 0.30)' },
                { color1: '#475E68', color2: 'rgba(84, 110, 122, 0.40)' },
                { color1: '#0D1012', color2: '#3C4F58' }
            ],
            glow: ['rgba(84, 110, 122, 0.45)', '#475E68']
        },
    };

    function gerarBolinhasCoresHTML(tipo) {
        if (!window.HelpFullPaletaCores) return '';
        var chaves = Object.keys(window.HelpFullPaletaCores);
        var html = '';
        chaves.forEach(function (k) {
            var item = window.HelpFullPaletaCores[k];
            if (item.tipo === tipo) {
                html += '<button type="button" class="tema-cor-bolinha" data-cor-id="' + item.id + '" data-preview-key="tema-cor" title="' + item.nome + '" style="--cor-dot: ' + item.corBolinha + ';" onclick="selecionarCorTema(\'' + item.id + '\')" aria-label="Cor ' + item.nome + '"><span class="tema-cor-check">✓</span></button>';
            }
        });
        return html;
    }

    function atualizarBolinhasTemaVisual(corId) {
        corId = corId || (window.localStorage ? (window.localStorage.getItem('helpfull_cor_tema') || 'padrao') : 'padrao');
        var p = window.HelpFullPaletaCores ? (window.HelpFullPaletaCores[corId] || window.HelpFullPaletaCores['padrao']) : null;
        var nomeCor = p ? p.nome : 'Azul HelpFull (Original)';
        var hexCor = p ? p.corBolinha : '#2b7a8c';

        document.querySelectorAll('.tema-cor-amostra-ativa').forEach(function (el) {
            el.style.backgroundColor = hexCor;
        });
        document.querySelectorAll('.tema-cor-nome-ativa').forEach(function (el) {
            el.textContent = nomeCor;
        });

        document.querySelectorAll('.tema-cor-bolinha').forEach(function (btn) {
            var id = btn.getAttribute('data-cor-id');
            btn.classList.toggle('ativo', id === corId);
        });
    }

    function toggleExpandirCoresTema() {
        var cards = document.querySelectorAll('.tema-cor-trigger-card');
        cards.forEach(function (card) {
            card.classList.toggle('expandido');
            var btn = card.querySelector('.tema-cor-trigger-btn');
            if (btn) {
                var expandido = card.classList.contains('expandido');
                btn.setAttribute('aria-expanded', expandido ? 'true' : 'false');
            }
        });
    }

    function aplicarCorTema(corId) {
        corId = corId || (window.localStorage ? (window.localStorage.getItem('helpfull_cor_tema') || 'padrao') : 'padrao');
        var styleEl = document.getElementById('helpfull-tema-dinamico-css');

        if (corId === 'padrao' || !window.HelpFullPaletaCores || !window.HelpFullPaletaCores[corId]) {
            if (window.localStorage) window.localStorage.setItem('helpfull_cor_tema', 'padrao');
            if (styleEl) styleEl.textContent = '';
            atualizarBolinhasTemaVisual('padrao');
            window.dispatchEvent(new CustomEvent('helpfull-cor-tema-mudou', { detail: { corId: 'padrao' } }));
            return;
        }

        if (window.localStorage) window.localStorage.setItem('helpfull_cor_tema', corId);
        var p = window.HelpFullPaletaCores[corId];

        if (!styleEl) {
            styleEl = document.createElement('style');
            styleEl.id = 'helpfull-tema-dinamico-css';
            document.head.appendChild(styleEl);
        }

styleEl.textContent = `
            :root, body {
                --cor-tema-primaria: ${p.primaria} !important;
                --cor-tema-hover: ${p.hover} !important;
                --cor-tema-ativa: ${p.ativa} !important;
                --cor-tema-clara: ${p.clara} !important;
                --cor-tema-suave: ${p.suave} !important;
                --cor-tema-borda: ${p.borda} !important;
                --cor-tema-sombra: ${p.sombra} !important;
            }

            /* 1. Botões principais (exceto reações, emoções e perigo) */
            .btn-primario, .btn-salvar, .btn-editar, .btn-novo-chat, .btn-enviar-chat,
            .btn-painel-fechar, .btn-onboarding-concluir, .btn-gerenciar-dispositivos,
            .btn-historico, .btn-buscar-api, .busca-btn, .btn-limpar-pill,
            .acessibilidade-btn-tour, .btn-iniciar, .btn-comentar, .btn-publicar,
            .btn-gerar-relatorio, .modal-btn-salvar, .btn-aceitar-regras, .btn-adm-link,
            .compact-pill,
            button.btn-primario, button.btn-salvar, button.btn-editar, button.btn-painel-fechar,
            button.btn-onboarding-concluir, button.modal-btn-salvar, button.btn-aceitar-regras,
            a.btn-gerenciar-dispositivos, a.btn-primario, a.btn-gerar-relatorio, a.btn-adm-link {
                background: linear-gradient(135deg, ${p.primaria}, ${p.hover}) !important;
                border-color: ${p.primaria} !important;
                color: #ffffff !important;
                box-shadow: 0 4px 14px ${p.sombra} !important;
            }

            .btn-primario:hover, .btn-salvar:hover, .btn-editar:hover, .btn-novo-chat:hover,
            .btn-enviar-chat:hover, .btn-painel-fechar:hover, .btn-onboarding-concluir:hover,
            .btn-gerenciar-dispositivos:hover, .btn-historico:hover, .btn-buscar-api:hover,
            .busca-btn:hover, .btn-limpar-pill:hover, .acessibilidade-btn-tour:hover,
            .btn-gerar-relatorio:hover, .modal-btn-salvar:hover:not(:disabled), .btn-aceitar-regras:hover,
            .btn-adm-link:hover,
            button.btn-primario:hover, button.btn-salvar:hover, button.btn-editar:hover,
            button.btn-painel-fechar:hover, button.btn-onboarding-concluir:hover,
            button.modal-btn-salvar:hover:not(:disabled), button.btn-aceitar-regras:hover,
            a.btn-gerenciar-dispositivos:hover, a.btn-primario:hover, a.btn-gerar-relatorio:hover,
            a.btn-adm-link:hover {
                background: linear-gradient(135deg, ${p.hover}, ${p.ativa}) !important;
                border-color: ${p.hover} !important;
                box-shadow: 0 6px 18px ${p.sombra} !important;
                filter: brightness(1.08) !important;
            }

            /* 2. Abas, categorias, filtros e histórico */
            .tab-btn.ativo, .filtro-btn.ativo, .btn-filtro.ativo,
            .categoria-pill.ativo, .filtro-categoria.ativo,
            .onboarding-tema-btn.ativo, .painel-tema-btn.ativo,
            .btn-historico.ativo, .disp-filtro-pill.ativo,
            body.acessibilidade-escuro .onboarding-tema-btn.ativo,
            body.acessibilidade-escuro .painel-tema-btn.ativo,
            body.acessibilidade-escuro button.painel-tema-btn.ativo,
            body.acessibilidade-escuro button.onboarding-tema-btn.ativo {
                background: linear-gradient(135deg, ${p.primaria}, ${p.hover}) !important;
                border-color: ${p.primaria} !important;
                color: #ffffff !important;
                box-shadow: 0 4px 14px ${p.sombra} !important;
            }

            /* 3. Navegação e links de destaque */
            .nav-links a.ativo,
            .nav-dropdown-mobile a.ativo {
                color: ${p.primaria} !important;
            }
            .nav-dropdown-mobile a.ativo {
                background: ${p.suave} !important;
            }
            .nav-entrar-btn,
            .nav-entrar-btn:hover,
            .dicas-link,
            .btn-olho-toggle:hover {
                color: ${p.primaria} !important;
            }

            /* 4. Notificações, sininho, avatares e contadores */
            .sininho-notificacao,
            .post-avatar,
            .card-relatorio-icone,
            #contadorDiariosDia,
            #badgeNotificacoesPerfil,
            .badge-adm-pill {
                background: ${p.primaria} !important;
                color: #ffffff !important;
            }
            .perfil-icone-wrapper,
            .disp-perfil-icone {
                box-shadow: 0 0 0 3px ${p.primaria} !important;
            }
            .perfil-icone-wrapper:hover,
            .disp-perfil-icone:hover {
                box-shadow: 0 0 0 3px ${p.primaria}, 0 4px 12px ${p.sombra} !important;
            }

            /* 5. Carrossel dots */
            .carrossel-dot.ativo,
            body.acessibilidade-escuro .carrossel-dot.ativo {
                background: ${p.primaria} !important;
                box-shadow: 0 0 8px ${p.sombra}, 0 0 16px ${p.suave} !important;
            }

            /* 6. Calendário */
            .cal-dia.selecionado,
            .cal-dia.hoje {
                background: ${p.primaria} !important;
                color: #ffffff !important;
            }
            .cal-dia.ativo {
                background: ${p.suave} !important;
                color: ${p.primaria} !important;
            }
            .cal-dia.tem-registro::after {
                background: ${p.primaria} !important;
            }

            /* 7. Switches e opções ativas */
            .onboarding-opt-row.ativo .onboarding-switch,
            .painel-opt-row.ativo .onboarding-switch,
            .switch.ativo,
            .balao-2fa-switch.ativo,
            .meta-item.concluida .meta-checkbox {
                background: ${p.primaria} !important;
                border-color: ${p.primaria} !important;
            }

            .onboarding-opt-row.ativo,
            .painel-opt-row.ativo {
                border-color: ${p.primaria} !important;
                background: ${p.suave} !important;
            }

            /* 8. Badges, tags e pílulas */
            .onboarding-badge, .notificacoes-icone-tag, .disp-preview-tag,
            .balao-2fa-badge, .onboarding-preview-tag, .tag-solida.azul,
            .tema-cor-badge-expandir, .balao-2fa-status-pill.ativo,
            .bv-diario-icone, .regra-num, .graficos-badge,
            .disp-badge-atual {
                background: ${p.suave} !important;
                color: ${p.primaria} !important;
                border-color: ${p.borda} !important;
            }
            .bv-diario-info h4,
            .balao-2fa-email-aviso strong,
            .divisor-curado-texto,
            .divisor-curado-texto svg {
                color: ${p.primaria} !important;
            }
            .busca-icone svg polygon,
            .busca-icone svg path {
                fill: ${p.primaria} !important;
                color: ${p.primaria} !important;
            }
            .modal-disp-preview svg {
                stroke: ${p.primaria} !important;
            }

            /* 9. Foco e inputs */
            .modal-busca-barra:focus-within,
            .onboarding-opt-row:hover,
            .painel-opt-row:hover,
            .onboarding-tema-btn:hover,
            .painel-tema-btn:hover,
            .tema-cor-trigger-card:hover {
                border-color: ${p.primaria} !important;
            }

            input:focus, textarea:focus, select:focus,
            .balao-2fa-input-codigo:focus, .campo-perfil:focus {
                border-color: ${p.primaria} !important;
                box-shadow: 0 0 0 3px ${p.suave} !important;
            }

            .loading-spinner, .spinner {
                border-top-color: ${p.primaria} !important;
            }

            .onboarding-dica-compacta {
                background: ${p.suave} !important;
                border-color: ${p.borda} !important;
            }
            .onboarding-dica-compacta svg {
                color: ${p.primaria} !important;
            }

            /* 10. Modo Escuro */
            body.acessibilidade-escuro .onboarding-opt-row.ativo,
            body.acessibilidade-escuro .painel-opt-row.ativo {
                background: ${p.suave} !important;
                border-color: ${p.clara} !important;
            }

            body.acessibilidade-escuro .btn-painel-fechar,
            body.acessibilidade-escuro button.btn-painel-fechar,
            body.acessibilidade-escuro .btn-onboarding-concluir,
            body.acessibilidade-escuro button.btn-onboarding-concluir,
            body.acessibilidade-escuro .btn-salvar,
            body.acessibilidade-escuro .modal-btn-salvar,
            body.acessibilidade-escuro .btn-gerenciar-dispositivos,
            body.acessibilidade-escuro .busca-btn {
                background: linear-gradient(135deg, ${p.primaria}, ${p.hover}) !important;
                border-color: ${p.primaria} !important;
                color: #ffffff !important;
                box-shadow: 0 6px 18px ${p.sombra} !important;
            }

            body.acessibilidade-escuro .btn-painel-fechar:hover,
            body.acessibilidade-escuro button.btn-painel-fechar:hover,
            body.acessibilidade-escuro .btn-onboarding-concluir:hover,
            body.acessibilidade-escuro button.btn-onboarding-concluir:hover,
            body.acessibilidade-escuro .btn-salvar:hover,
            body.acessibilidade-escuro .modal-btn-salvar:hover,
            body.acessibilidade-escuro .btn-gerenciar-dispositivos:hover,
            body.acessibilidade-escuro .busca-btn:hover {
                background: linear-gradient(135deg, ${p.hover}, ${p.ativa}) !important;
            }

            body.acessibilidade-escuro .onboarding-preview-icone-badge,
            body.acessibilidade-escuro .onboarding-preview-dica {
                color: ${p.clara} !important;
            }

            body.acessibilidade-escuro .disp-preview-tag {
                background: ${p.suave} !important;
                color: ${p.clara} !important;
                border-color: ${p.borda} !important;
            }
        `;
                --cor-tema-hover: ${p.hover} !important;
                --cor-tema-ativa: ${p.ativa} !important;
                --cor-tema-clara: ${p.clara} !important;
                --cor-tema-suave: ${p.suave} !important;
                --cor-tema-borda: ${p.borda} !important;
                --cor-tema-sombra: ${p.sombra} !important;
            }

            /* Botões principais (exceto reações, emoções e perigo) */
            .btn-primario, .btn-salvar, .btn-editar, .btn-novo-chat, .btn-enviar-chat,
            .btn-painel-fechar, .btn-onboarding-concluir, .btn-gerenciar-dispositivos,
            .btn-historico, .btn-buscar-api, .busca-btn, .btn-limpar-pill,
            .acessibilidade-btn-tour, .btn-iniciar, .btn-comentar, .btn-publicar,
            button.btn-primario, button.btn-salvar, button.btn-editar, button.btn-painel-fechar, button.btn-onboarding-concluir,
            a.btn-gerenciar-dispositivos, a.btn-primario {
                background: linear-gradient(135deg, ${p.primaria}, ${p.hover}) !important;
                border-color: ${p.primaria} !important;
                color: #ffffff !important;
                box-shadow: 0 4px 14px ${p.sombra} !important;
            }

            .btn-primario:hover, .btn-salvar:hover, .btn-editar:hover, .btn-novo-chat:hover,
            .btn-enviar-chat:hover, .btn-painel-fechar:hover, .btn-onboarding-concluir:hover,
            .btn-gerenciar-dispositivos:hover, .btn-historico:hover, .btn-buscar-api:hover,
            .busca-btn:hover, .btn-limpar-pill:hover, .acessibilidade-btn-tour:hover,
            button.btn-primario:hover, button.btn-salvar:hover, button.btn-editar:hover, button.btn-painel-fechar:hover, button.btn-onboarding-concluir:hover,
            a.btn-gerenciar-dispositivos:hover, a.btn-primario:hover {
                background: linear-gradient(135deg, ${p.hover}, ${p.ativa}) !important;
                border-color: ${p.hover} !important;
                box-shadow: 0 6px 18px ${p.sombra} !important;
                filter: brightness(1.08) !important;
            }

            /* Abas e botões de filtro */
            .tab-btn.ativo, .filtro-btn.ativo, .btn-filtro.ativo,
            .onboarding-tema-btn.ativo, .painel-tema-btn.ativo,
            body.acessibilidade-escuro .onboarding-tema-btn.ativo,
            body.acessibilidade-escuro .painel-tema-btn.ativo,
            body.acessibilidade-escuro button.painel-tema-btn.ativo,
            body.acessibilidade-escuro button.onboarding-tema-btn.ativo {
                background: linear-gradient(135deg, ${p.primaria}, ${p.hover}) !important;
                border-color: ${p.primaria} !important;
                color: #ffffff !important;
                box-shadow: 0 4px 14px ${p.sombra} !important;
            }

            /* Switches e opções ativas */
            .onboarding-opt-row.ativo .onboarding-switch,
            .painel-opt-row.ativo .onboarding-switch,
            .switch.ativo {
                background: ${p.primaria} !important;
                border-color: ${p.primaria} !important;
            }

            .onboarding-opt-row.ativo,
            .painel-opt-row.ativo {
                border-color: ${p.primaria} !important;
                background: ${p.suave} !important;
            }

            /* Badges, tags e contadores */
            .onboarding-badge, .notificacoes-icone-tag, .disp-preview-tag,
            .balao-2fa-badge, .onboarding-preview-tag, .tag-solida.azul,
            .tema-cor-badge-expandir {
                background: ${p.suave} !important;
                color: ${p.primaria} !important;
                border-color: ${p.borda} !important;
            }

            #contadorDiariosDia, .graficos-badge {
                background: ${p.primaria} !important;
                color: #ffffff !important;
            }

            .balao-2fa-status-pill.ativo {
                background: ${p.suave} !important;
                color: ${p.primaria} !important;
            }

            .meta-item.concluida .meta-checkbox {
                background: ${p.primaria} !important;
                border-color: ${p.primaria} !important;
            }

            .cal-dia.selecionado, .cal-dia.hoje {
                background: ${p.primaria} !important;
                color: #ffffff !important;
            }

            .onboarding-dica-compacta {
                background: ${p.suave} !important;
                border-color: ${p.borda} !important;
            }
            .onboarding-dica-compacta svg {
                color: ${p.primaria} !important;
            }

            .onboarding-opt-row:hover,
            .painel-opt-row:hover,
            .onboarding-tema-btn:hover,
            .painel-tema-btn:hover,
            .tema-cor-trigger-card:hover {
                border-color: ${p.primaria} !important;
            }

            input:focus, textarea:focus, select:focus {
                border-color: ${p.primaria} !important;
                box-shadow: 0 0 0 3px ${p.suave} !important;
            }

            /* Modo Escuro */
            body.acessibilidade-escuro .onboarding-opt-row.ativo,
            body.acessibilidade-escuro .painel-opt-row.ativo {
                background: ${p.suave} !important;
                border-color: ${p.clara} !important;
            }

            body.acessibilidade-escuro .btn-painel-fechar,
            body.acessibilidade-escuro button.btn-painel-fechar,
            body.acessibilidade-escuro .btn-onboarding-concluir,
            body.acessibilidade-escuro button.btn-onboarding-concluir {
                background: ${p.primaria} !important;
                border: none !important;
                box-shadow: 0 6px 18px ${p.sombra} !important;
            }

            body.acessibilidade-escuro .btn-painel-fechar:hover,
            body.acessibilidade-escuro button.btn-painel-fechar:hover,
            body.acessibilidade-escuro .btn-onboarding-concluir:hover,
            body.acessibilidade-escuro button.btn-onboarding-concluir:hover {
                background: ${p.hover} !important;
            }

            body.acessibilidade-escuro .onboarding-preview-icone-badge,
            body.acessibilidade-escuro .onboarding-preview-dica {
                color: ${p.clara} !important;
            }
        `;

        atualizarBolinhasTemaVisual(corId);
        window.dispatchEvent(new CustomEvent('helpfull-cor-tema-mudou', { detail: { corId: corId, paleta: p } }));
    }

    window.aplicarCorTema = aplicarCorTema;
    window.selecionarCorTema = function(corId) { aplicarCorTema(corId); };
    window.restaurarCorTemaPadrao = function() { aplicarCorTema('padrao'); };
    window.toggleExpandirCoresTema = toggleExpandirCoresTema;
    window.atualizarBolinhasTemaVisual = atualizarBolinhasTemaVisual;
    window.gerarBolinhasCoresHTML = gerarBolinhasCoresHTML;

    function aplicarAcessibilidade() {
        var modoTema = localStorage.getItem('helpfull_tema_modo') || 'manual';
        if (modoTema === 'sistema') {
            var prefereEscuro = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            localStorage.setItem('helpfull_escuro', prefereEscuro ? 'true' : 'false');
        }

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

        var btnAuto = document.querySelector('[data-acessibilidade="autoTema"]');
        if (btnAuto) {
            var autoAtivo = modoTema === 'sistema';
            btnAuto.classList.toggle('ativo', autoAtivo);
            btnAuto.setAttribute('aria-pressed', autoAtivo ? 'true' : 'false');
        }

        var lb = localStorage.getItem('helpfull_libras') === 'true';
        gerenciarVLibras(lb);
        aplicarCorTema();
        sincronizarEstadoVisualPainel();
    }

    if (window.matchMedia) {
        try {
            var mq = window.matchMedia('(prefers-color-scheme: dark)');
            var onPrefChange = function () {
                if (localStorage.getItem('helpfull_tema_modo') === 'sistema') {
                    aplicarAcessibilidade();
                }
            };
            if (mq.addEventListener) {
                mq.addEventListener('change', onPrefChange);
            } else if (mq.addListener) {
                mq.addListener(onPrefChange);
            }
        } catch (e) {}
    }

    var svgIconesPainel = {
        'sparkles': '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"></path></svg>',
        'gear': '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>',
        'palette': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"></circle><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"></circle><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"></circle><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"></circle><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"></path></svg>',
        'sliders': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>',
        'globe': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>',
        'sun': '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>',
        'moon': '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>',
        'device': '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="2" y1="20" x2="22" y2="20"></line></svg>',
        'contrast': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 2a10 10 0 0 1 0 20z" fill="currentColor"></path></svg>',
        'text': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 7 4 4 20 4 20 7"></polyline><line x1="9" y1="20" x2="15" y2="20"></line><line x1="12" y1="4" x2="12" y2="20"></line></svg>',
        'link': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>',
        'zap': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>',
        'libras': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 11V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v0"></path><path d="M14 10V4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v2"></path><path d="M10 10.5V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v8"></path><path d="M18 8a2 2 0 1 1 4 0v6a8 8 0 0 1-8 8h-2c-2.8 0-4.5-.86-5.99-2.34l-3.6-3.6a2 2 0 0 1 2.83-2.82L7 15"></path></svg>',
        'bulb': '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6"></path><path d="M10 22h4"></path><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5.76.76 1.23 1.52 1.41 2.5"></path></svg>'
    };

    var explicativosPainel = {
        'pt-BR': {
            'padrao': {
                icone: svgIconesPainel.sparkles,
                tag: 'CONFIGURAÇÕES',
                titulo: 'Configurações Rápidas',
                desc: 'Passe o mouse ou toque sobre qualquer opção ao lado para conferir sua finalidade e visualizar o efeito em tempo real.',
                dica: 'As alterações são salvas automaticamente em seu perfil e navegador.'
            },
            'idioma': {
                icone: svgIconesPainel.globe,
                tag: 'LINGUAGEM',
                titulo: 'Idioma da Plataforma',
                desc: 'Alterne todo o conteúdo, menus e metas diárias entre Português e Inglês com tradução instantânea.',
                dica: 'A tradução é aplicada imediatamente em todas as páginas.'
            },
            'tema-cor': {
                icone: svgIconesPainel.palette,
                tag: 'COR DO TEMA',
                titulo: 'Cor do Tema do Site',
                desc: 'Altere as cores principais, botões, ícones, elementos ativos e o fundo animado de todo o site com a sua cor favorita!',
                dica: 'Você pode escolher tons claros ou escuros e voltar ao padrão azul a qualquer momento.'
            },
            'tema-claro': {
                icone: svgIconesPainel.sun,
                tag: 'TEMA VISUAL',
                titulo: 'Modo Claro',
                desc: 'Aparência tradicional e luminosa com tons suaves de azul e branco, ideal para ambientes claros e leitura diurna.',
                dica: 'Proporciona visual limpo, refrescante e acolhedor.'
            },
            'tema-escuro': {
                icone: svgIconesPainel.moon,
                tag: 'TEMA VISUAL',
                titulo: 'Modo Noturno',
                desc: 'Substitui fundos claros por superfícies escuras relaxantes, diminuindo o cansaço dos olhos à noite e economizando bateria.',
                dica: 'Recomendado para ambientes com pouca luz e relaxamento visual.'
            },
            'tema-auto': {
                icone: svgIconesPainel.device,
                tag: 'APARÊNCIA INTELIGENTE',
                titulo: 'Seguir o Dispositivo',
                desc: 'Acompanha automaticamente as configurações do seu celular ou PC: claro de dia e escuro quando o sistema ativar o modo noturno.',
                dica: 'O HelpFull sincroniza sozinho sem você precisar trocar manualmente!'
            },
            'contraste': {
                icone: svgIconesPainel.contrast,
                tag: 'ACESSIBILIDADE VISUAL',
                titulo: 'Mais Contraste',
                desc: 'Realça nitidamente as bordas, cartões e tipografia do site, eliminando tons apagados e facilitando a leitura.',
                dica: 'Excelente sob claridade intensa ou para quem tem baixa visão.'
            },
            'textoGrande': {
                icone: svgIconesPainel.text,
                tag: 'LEGIBILIDADE',
                titulo: 'Texto Maior',
                desc: 'Aumenta as letras de títulos, relatos, artigos e botões em toda a plataforma sem deformar o layout.',
                dica: 'Melhora a experiência de leitura sem precisar aproximar a tela.'
            },
            'sublinhar': {
                icone: svgIconesPainel.link,
                tag: 'NAVEGAÇÃO',
                titulo: 'Sublinhar Links',
                desc: 'Destaca de forma inequívoca todos os links, botões e elementos clicáveis com uma linha inferior.',
                dica: 'Facilita identificar rapidamente onde é possível clicar.'
            },
            'semAnimacao': {
                icone: svgIconesPainel.zap,
                tag: 'CONFORTO E FOCO',
                titulo: 'Reduzir Animações',
                desc: 'Diminui movimentos e efeitos de transição, resultando em uma navegação mais direta, estática e suave.',
                dica: 'Recomendado para evitar tonturas visuais ou acelerar aparelhos mais lentos.'
            },
            'libras': {
                icone: svgIconesPainel.libras,
                tag: 'INCLUSÃO OFICIAL',
                titulo: 'Tradutor de Libras (Gov.br)',
                desc: 'Integra o avatar 3D do VLibras do Governo Federal para tradução simultânea de textos para a Língua Brasileira de Sinais.',
                dica: 'O avatar fica disponível no canto da tela para traduzir o que você selecionar.'
            }
        },
        'en': {
            'padrao': {
                icone: svgIconesPainel.sparkles,
                tag: 'SETTINGS',
                titulo: 'Quick Settings',
                desc: 'Hover or tap on any option on the left to see what it does and test the effect in real time.',
                dica: 'Changes are automatically saved to your profile and browser.'
            },
            'idioma': {
                icone: svgIconesPainel.globe,
                tag: 'LANGUAGE',
                titulo: 'Platform Language',
                desc: 'Switch interface, menus, and daily goals between Portuguese and English with instant translation.',
                dica: 'Changes are applied immediately across all sections.'
            },
            'tema-cor': {
                icone: svgIconesPainel.palette,
                tag: 'COR DO TEMA',
                titulo: 'Cor do Tema do Site',
                desc: 'Altere as cores principais, botões, ícones, elementos ativos e o fundo animado de todo o site com a sua cor favorita!',
                dica: 'Você pode escolher tons claros ou escuros e voltar ao padrão azul a qualquer momento.'
            },
            'tema-claro': {
                icone: svgIconesPainel.sun,
                tag: 'THEME',
                titulo: 'Light Mode',
                desc: 'Bright and traditional look with soothing blue and white tones, ideal for daytime and bright rooms.',
                dica: 'Clean, refreshing and friendly visual feel.'
            },
            'tema-escuro': {
                icone: svgIconesPainel.moon,
                tag: 'THEME',
                titulo: 'Dark Mode',
                desc: 'Replaces bright backgrounds with relaxing dark surfaces to ease eye strain and save battery life.',
                dica: 'Great for nighttime and low-light relaxation.'
            },
            'tema-auto': {
                icone: svgIconesPainel.device,
                tag: 'SMART THEME',
                titulo: 'Match Device',
                desc: 'Automatically synchronizes with your device theme: light by day, dark when your system enables dark mode.',
                dica: 'HelpFull stays in sync without manual changes!'
            },
            'contraste': {
                icone: svgIconesPainel.contrast,
                tag: 'VISUAL ACCESSIBILITY',
                titulo: 'High Contrast',
                desc: 'Sharpens card borders, buttons and text for maximal visibility and clarity.',
                dica: 'Helpful in bright sunlight or for low-vision readers.'
            },
            'textoGrande': {
                icone: svgIconesPainel.text,
                tag: 'READABILITY',
                titulo: 'Larger Text',
                desc: 'Increases text size throughout titles, articles and buttons without breaking layouts.',
                dica: 'Easier reading without zooming your screen.'
            },
            'sublinhar': {
                icone: svgIconesPainel.link,
                tag: 'NAVIGATION',
                titulo: 'Underline Links',
                desc: 'Highlights clickable links and buttons with clear underline indicators.',
                dica: 'Makes interactive targets unmistakable.'
            },
            'semAnimacao': {
                icone: svgIconesPainel.zap,
                tag: 'COMFORT & FOCUS',
                titulo: 'Reduce Motion',
                desc: 'Minimizes motion and transitions for a straightforward, quiet experience.',
                dica: 'Prevents visual fatigue and speeds up low-end devices.'
            },
            'libras': {
                icone: svgIconesPainel.libras,
                tag: 'ACCESSIBILITY',
                titulo: 'Libras Translator (Gov.br)',
                desc: 'Integrates the Brazilian Government VLibras 3D avatar for text-to-sign-language translation.',
                dica: 'The avatar rests on screen to translate any selected text.'
            }
        }
    };

    function atualizarPreviewPainel(chave) {
        var idioma = localStorage.getItem('helpfull_idioma') === 'en' ? 'en' : 'pt-BR';
        var mapa = explicativosPainel[idioma] || explicativosPainel['pt-BR'];
        var item = mapa[chave] || mapa['padrao'];
        var tag = document.getElementById('painelPreviewTag');
        var icone = document.getElementById('painelPreviewIcone');
        var titulo = document.getElementById('painelPreviewTitulo');
        var desc = document.getElementById('painelPreviewDesc');
        var dica = document.getElementById('painelPreviewDica');
        if (!tag || !icone || !titulo || !desc || !dica) return;

        tag.textContent = item.tag;
        icone.innerHTML = item.icone;
        titulo.textContent = item.titulo;
        desc.textContent = item.desc;
        dica.innerHTML = svgIconesPainel.bulb + ' <span>' + item.dica + '</span>';
    }

    function sincronizarEstadoVisualPainel() {
        var p = document.getElementById('painelAcessibilidade');
        if (!p) return;
        var modo = localStorage.getItem('helpfull_tema_modo') || (localStorage.getItem('helpfull_escuro') === 'true' ? 'escuro' : 'claro');
        p.querySelectorAll('.painel-tema-btn').forEach(function (btn) {
            var tema = btn.getAttribute('data-tema');
            btn.classList.toggle('ativo', tema === modo);
        });
        p.querySelectorAll('.painel-opt-row').forEach(function (row) {
            var key = row.getAttribute('data-acess-key');
            var ativo = localStorage.getItem('helpfull_' + key) === 'true';
            row.classList.toggle('ativo', ativo);
        });
        var si = p.querySelector('#seletorIdioma');
        if (si) {
            si.value = localStorage.getItem('helpfull_idioma') || 'pt-BR';
        }
        atualizarBolinhasTemaVisual();
    }

    function criarPainelAcessibilidade() {
        var overlay = document.getElementById('painelAcessibilidadeOverlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'painelAcessibilidadeOverlay';
            overlay.className = 'painel-acessibilidade-overlay';
            overlay.setAttribute('role', 'presentation');
            document.body.appendChild(overlay);
        }

        var p = document.getElementById('painelAcessibilidade');
        if (!p) {
            var div = document.createElement('div');
            div.innerHTML = `
                <section id="painelAcessibilidade" aria-label="Configurações e Acessibilidade" role="dialog" aria-modal="true">
                    <div class="acessibilidade-cabecalho">
                        <div>
                            <div class="onboarding-badge" id="painelAcessBadge">
                                ${svgIconesPainel.gear}
                                <span>Preferências</span>
                            </div>
                            <h2 id="painelAcessTitulo">Configurações & Acessibilidade</h2>
                            <p id="painelAcessSubtitulo">Personalize sua experiência no HelpFull a qualquer momento.</p>
                        </div>
                        <button type="button" class="acessibilidade-fechar" aria-label="Fechar configurações">×</button>
                    </div>

                    <div class="onboarding-grid">
                        <!-- Coluna Esquerda: Controles -->
                        <div class="onboarding-col-opcoes">
                            <div class="acessibilidade-idioma">
                                <label for="seletorIdioma" id="rotuloIdiomaPainel">
                                    ${svgIconesPainel.globe}
                                    <span>Idioma / Language</span>
                                </label>
                                <select id="seletorIdioma" aria-label="Selecionar idioma" data-preview-key="idioma">
                                    <option value="pt-BR">Português (Brasil)</option>
                                    <option value="en">English (US)</option>
                                </select>
                            </div>

                            <div>
                                <div class="onboarding-secao-titulo" id="secaoTemaTitulo">
                                    ${svgIconesPainel.palette}
                                    <span>Aparência e Modo Noturno</span>
                                </div>
                                <div class="onboarding-tema-pills">
                                    <button type="button" class="onboarding-tema-btn painel-tema-btn" data-tema="claro" data-preview-key="tema-claro">
                                        <span class="tema-icone">${svgIconesPainel.sun}</span>
                                        <span class="tema-nome">Claro</span>
                                    </button>
                                    <button type="button" class="onboarding-tema-btn painel-tema-btn" data-tema="escuro" data-preview-key="tema-escuro">
                                        <span class="tema-icone">${svgIconesPainel.moon}</span>
                                        <span class="tema-nome">Escuro</span>
                                    </button>
                                    <button type="button" class="onboarding-tema-btn painel-tema-btn" data-tema="sistema" data-preview-key="tema-auto">
                                        <span class="tema-icone">${svgIconesPainel.device}</span>
                                        <span class="tema-nome">Do Dispositivo</span>
                                    </button>
                                </div>
                            </div>

                            <div class="tema-cor-secao">
                                <div class="onboarding-secao-titulo" id="secaoCorTemaTitulo">
                                    ${svgIconesPainel.palette}
                                    <span>Cor do Tema do Site</span>
                                </div>
                                <div class="tema-cor-trigger-card" id="temaCorCard">
                                    <button type="button" class="tema-cor-trigger-btn" onclick="toggleExpandirCoresTema()" aria-expanded="false" title="Clique para expandir e escolher cores">
                                        <div class="tema-cor-trigger-info">
                                            <span class="tema-cor-amostra-ativa" id="temaCorAmostraAtiva"></span>
                                            <span class="tema-cor-nome-ativa" id="temaCorNomeAtiva">Azul HelpFull (Original)</span>
                                        </div>
                                        <div class="tema-cor-trigger-acoes">
                                            <span class="tema-cor-badge-expandir">Escolher cor</span>
                                            <svg class="tema-cor-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="6 9 12 15 18 9"></polyline>
                                            </svg>
                                        </div>
                                    </button>
                                    <div class="tema-cor-expansao" id="temaCorExpansao">
                                        <div class="tema-cor-grupo">
                                            <div class="tema-cor-grupo-rotulo">☀️ Tons Claros & Vivos</div>
                                            <div class="tema-cor-bolinhas-grid" id="gridCoresClarasPainel">
                                                ${gerarBolinhasCoresHTML('claro')}
                                            </div>
                                        </div>
                                        <div class="tema-cor-grupo">
                                            <div class="tema-cor-grupo-rotulo">🌙 Tons Escuros & Intensos</div>
                                            <div class="tema-cor-bolinhas-grid" id="gridCoresEscurasPainel">
                                                ${gerarBolinhasCoresHTML('escuro')}
                                            </div>
                                        </div>
                                        <div class="tema-cor-rodape">
                                            <button type="button" class="btn-restaurar-cor-padrao" onclick="restaurarCorTemaPadrao()">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><path d="M3 3v5h5"></path></svg>
                                                <span>Voltar ao padrão</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div class="onboarding-secao-titulo" id="secaoAcessTitulo">
                                    ${svgIconesPainel.sliders}
                                    <span>Acessibilidade e Leitura</span>
                                </div>
                                <div class="onboarding-lista-opcoes">
                                    <div class="onboarding-opt-row painel-opt-row" data-acess-key="contraste" data-preview-key="contraste">
                                        <div class="onboarding-opt-info">
                                            <span class="onboarding-opt-icone">${svgIconesPainel.contrast}</span>
                                            <span class="onboarding-opt-nome">Mais Contraste</span>
                                        </div>
                                        <div class="onboarding-switch" aria-hidden="true"></div>
                                    </div>

                                    <div class="onboarding-opt-row painel-opt-row" data-acess-key="textoGrande" data-preview-key="textoGrande">
                                        <div class="onboarding-opt-info">
                                            <span class="onboarding-opt-icone">${svgIconesPainel.text}</span>
                                            <span class="onboarding-opt-nome">Texto Maior</span>
                                        </div>
                                        <div class="onboarding-switch" aria-hidden="true"></div>
                                    </div>

                                    <div class="onboarding-opt-row painel-opt-row" data-acess-key="sublinhar" data-preview-key="sublinhar">
                                        <div class="onboarding-opt-info">
                                            <span class="onboarding-opt-icone">${svgIconesPainel.link}</span>
                                            <span class="onboarding-opt-nome">Sublinhar Links</span>
                                        </div>
                                        <div class="onboarding-switch" aria-hidden="true"></div>
                                    </div>

                                    <div class="onboarding-opt-row painel-opt-row" data-acess-key="semAnimacao" data-preview-key="semAnimacao">
                                        <div class="onboarding-opt-info">
                                            <span class="onboarding-opt-icone">${svgIconesPainel.zap}</span>
                                            <span class="onboarding-opt-nome">Reduzir Animações</span>
                                        </div>
                                        <div class="onboarding-switch" aria-hidden="true"></div>
                                    </div>

                                    <div class="onboarding-opt-row painel-opt-row" data-acess-key="libras" data-preview-key="libras">
                                        <div class="onboarding-opt-info">
                                            <span class="onboarding-opt-icone">${svgIconesPainel.libras}</span>
                                            <span class="onboarding-opt-nome">Tradutor de Libras (Gov.br)</span>
                                        </div>
                                        <div class="onboarding-switch" aria-hidden="true"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Coluna Direita: Preview Explicativo Dinâmico -->
                        <div class="onboarding-col-preview">
                            <div class="onboarding-preview-card" id="painelPreviewCard">
                                <div class="onboarding-preview-topo">
                                    <span class="onboarding-preview-tag" id="painelPreviewTag">CONFIGURAÇÕES</span>
                                    <div class="onboarding-preview-icone-badge" id="painelPreviewIcone">${svgIconesPainel.sparkles}</div>
                                    <h3 class="onboarding-preview-titulo" id="painelPreviewTitulo">Configurações Rápidas</h3>
                                    <p class="onboarding-preview-desc" id="painelPreviewDesc">Passe o mouse ou toque sobre qualquer opção ao lado para conferir sua finalidade e visualizar o efeito em tempo real.</p>
                                </div>
                                <div class="onboarding-preview-dica" id="painelPreviewDica">
                                    ${svgIconesPainel.bulb}
                                    <span>As alterações são salvas automaticamente em seu perfil e navegador.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rodapé do painel com botão de tutorial/tour -->
                    <div class="acessibilidade-rodape">
                        <button type="button" class="acessibilidade-btn-tour" onclick="if(window.iniciarTutorial){window.fecharPainelAcessibilidade();window.iniciarTutorial(true);}" title="Iniciar tour interativo guiado desta tela">✦ Tour da Página</button>
                        <button type="button" class="acessibilidade-resetar">Restaurar padrão</button>
                        <button type="button" class="btn-painel-fechar">Concluir</button>
                    </div>
                </section>
            `.trim();
            p = div.firstElementChild;
            document.body.appendChild(p);
        }

        var b = document.getElementById('btnAcessibilidade');
        if (b) b.setAttribute('aria-expanded', 'false');

        conectarControlesAcessibilidade();
        aplicarAcessibilidade();
        aplicarIdioma();
        sincronizarEstadoVisualPainel();
    }

    window.fecharPainelAcessibilidade = function () {
        var p = document.getElementById('painelAcessibilidade');
        var overlay = document.getElementById('painelAcessibilidadeOverlay');
        var b = document.getElementById('btnAcessibilidade');
        if (p) {
            p.classList.remove('aberto');
            p.setAttribute('data-aberto', 'false');
        }
        if (overlay) {
            overlay.classList.remove('aberto');
        }
        if (b) b.setAttribute('aria-expanded', 'false');
    };

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
        var overlay = document.getElementById('painelAcessibilidadeOverlay');
        var b = document.getElementById('btnAcessibilidade');
        if (!p) {
            criarPainelAcessibilidade();
            p = document.getElementById('painelAcessibilidade');
            overlay = document.getElementById('painelAcessibilidadeOverlay');
        }
        if (!p) return;
        var ab = !p.classList.contains('aberto');
        if (ab) {
            _bloqueioFecharPainelAcess = true;
            setTimeout(function () {
                _bloqueioFecharPainelAcess = false;
            }, 350);
            p.classList.add('aberto');
            p.setAttribute('data-aberto', 'true');
            if (overlay) overlay.classList.add('aberto');
            if (b) b.setAttribute('aria-expanded', 'true');
            sincronizarEstadoVisualPainel();
            atualizarPreviewPainel('padrao');
        } else {
            window.fecharPainelAcessibilidade();
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
            if (!b.querySelector('svg')) {
                b.innerHTML = `
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                `;
            }
        }
    }

    function conectarControlesAcessibilidade() {
        var p = document.getElementById('painelAcessibilidade');
        var overlay = document.getElementById('painelAcessibilidadeOverlay');
        if (!p || p.dataset.controlesConectados === 'true') return;
        p.dataset.controlesConectados = 'true';

        if (overlay && overlay.dataset.ligado !== 'true') {
            overlay.addEventListener('click', function () {
                window.fecharPainelAcessibilidade();
            });
            overlay.dataset.ligado = 'true';
        }

        var si = p.querySelector('#seletorIdioma');
        if (si && si.dataset.ligado !== 'true') {
            si.value = localStorage.getItem('helpfull_idioma') || 'pt-BR';
            si.addEventListener('change', function () {
                localStorage.setItem('helpfull_idioma', si.value);
                aplicarIdioma();
                atualizarPreviewPainel('idioma');
            });
            si.dataset.ligado = 'true';
        }

        // Theme pills
        p.querySelectorAll('.painel-tema-btn').forEach(function (btn) {
            if (btn.dataset.ligado === 'true') return;
            btn.dataset.ligado = 'true';
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var modo = btn.getAttribute('data-tema');
                window.definirTemaModo(modo);
                sincronizarEstadoVisualPainel();
                atualizarPreviewPainel('tema-' + (modo === 'sistema' ? 'auto' : modo));
            });
        });

        // Accessibility toggle rows
        p.querySelectorAll('.painel-opt-row').forEach(function (row) {
            if (row.dataset.ligado === 'true') return;
            row.dataset.ligado = 'true';
            row.addEventListener('click', function (e) {
                e.preventDefault();
                var key = row.getAttribute('data-acess-key');
                window.alternarAcessibilidadeOpcao(key);
                sincronizarEstadoVisualPainel();
                atualizarPreviewPainel(key);
            });
        });

        // Hover / focus preview
        var elementosPreview = p.querySelectorAll('[data-preview-key]');
        elementosPreview.forEach(function (el) {
            var chave = el.getAttribute('data-preview-key');
            el.addEventListener('mouseenter', function () {
                atualizarPreviewPainel(chave);
            });
            el.addEventListener('focus', function () {
                atualizarPreviewPainel(chave);
            });
        });

        var gridOpcoes = p.querySelector('.onboarding-grid');
        if (gridOpcoes) {
            gridOpcoes.addEventListener('mouseleave', function () {
                atualizarPreviewPainel('padrao');
            });
        }

        // Close buttons: x and Concluir
        p.querySelectorAll('.acessibilidade-fechar, .btn-painel-fechar').forEach(function (btn) {
            if (btn.dataset.ligado === 'true') return;
            btn.dataset.ligado = 'true';
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                window.fecharPainelAcessibilidade();
            });
        });

        // Reset button
        var re = p.querySelector('.acessibilidade-resetar');
        if (re && re.dataset.ligado !== 'true') {
            re.dataset.ligado = 'true';
            re.addEventListener('click', function (e) {
                e.preventDefault();
                Object.keys(prefAcess).forEach(function (k) { localStorage.removeItem('helpfull_' + k); });
                localStorage.removeItem('helpfull_textoGrande');
                localStorage.removeItem('helpfull_tema_modo');
                localStorage.setItem('helpfull_idioma', 'pt-BR');
                if (si) si.value = 'pt-BR';
                restaurarCorTemaPadrao();
                aplicarAcessibilidade();
                aplicarIdioma();
                sincronizarEstadoVisualPainel();
                atualizarPreviewPainel('padrao');
            });
        }

        // Escape key to close
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && p.classList.contains('aberto')) {
                window.fecharPainelAcessibilidade();
            }
        });

        aplicarAcessibilidade();
        aplicarIdioma();
        sincronizarEstadoVisualPainel();
    }

    window.definirTemaModo = function (modo) {
        if (modo === 'sistema') {
            localStorage.setItem('helpfull_tema_modo', 'sistema');
        } else if (modo === 'escuro') {
            localStorage.setItem('helpfull_tema_modo', 'escuro');
            localStorage.setItem('helpfull_escuro', 'true');
        } else {
            localStorage.setItem('helpfull_tema_modo', 'claro');
            localStorage.setItem('helpfull_escuro', 'false');
        }
        aplicarAcessibilidade();
    };

    window.alternarAcessibilidadeOpcao = function (k) {
        if (k === 'autoTema') {
            var modoAtual = localStorage.getItem('helpfull_tema_modo');
            if (modoAtual === 'sistema') {
                localStorage.setItem('helpfull_tema_modo', 'manual');
            } else {
                localStorage.setItem('helpfull_tema_modo', 'sistema');
            }
            aplicarAcessibilidade();
            return;
        }
        if (k === 'escuro') {
            localStorage.setItem('helpfull_tema_modo', 'manual');
        }
        var ativo = localStorage.getItem('helpfull_' + k) === 'true';
        localStorage.setItem('helpfull_' + k, (!ativo).toString());
        aplicarAcessibilidade();
    };

    window.aplicarAcessibilidade = aplicarAcessibilidade;

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
        setTimeout(inicializarScrollFade, 80);
        window.addEventListener('resize', inicializarScrollFade);

        var sNav = document.getElementById('sininhoNavbar');
        var cardNotif = document.getElementById('cardNotificacoesPerfil');
        if (sNav && cardNotif) {
            sNav.style.cursor = 'pointer';
            sNav.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                cardNotif.scrollIntoView({ behavior: 'smooth', block: 'center' });
                cardNotif.classList.add('destaque-notificacoes');
                setTimeout(function () {
                    cardNotif.classList.remove('destaque-notificacoes');
                }, 1400);
            });
        }

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
                    window.fecharPainelAcessibilidade();
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

