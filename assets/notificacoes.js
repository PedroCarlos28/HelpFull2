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

    var textosPag = {
        'pt-BR': {
            'Início':'Início','Diário':'Diário','Comunidade':'Comunidade','Atividades':'Atividades','Adicionais':'Adicionais','Perfil':'Perfil',
            'Sua Conta:':'Sua Conta:','Metas':'Metas','Calendário':'Calendário','Detalhes do dia':'Detalhes do dia','Gráficos':'Gráficos',
            'Diários':'Diários','Emoções do Mês':'Emoções do Mês','Sobre essa aba:':'Sobre essa aba:','Textos':'Textos','Videos':'Videos',
            'A Técnica 4-7-8 para Alívio Imediato':'A Técnica 4-7-8 para Alívio Imediato','O poder das pausas e da autocompaixão':'O poder das pausas e da autocompaixão',
            'Práticas de Respiração e Foco':'Práticas de Respiração e Foco','Entendendo a Mente':'Entendendo a Mente',
            'Histórico':'Histórico','Nova Conversa':'Nova Conversa','Digite aqui para conversar com o Helpy...':'Digite aqui para conversar com o Helpy...',
            'Atribua uma emoção ao seu diario:':'Atribua uma emoção ao seu diario:','Irritado':'Irritado','Ansioso':'Ansioso','Feliz':'Feliz','Calmo':'Calmo','Triste':'Triste','Amoroso':'Amoroso',
            'Apagar tudo':'Apagar tudo','Salvar':'Salvar','Editar':'Editar','Sair da conta':'Sair da conta','Apagar Conta':'Apagar Conta',
            'Notificações':'Notificações','Limpar':'Limpar','Nenhuma notificação nova.':'Nenhuma notificação nova.','Bom':'Bom','Ruim':'Ruim','Reagir':'Reagir',
            'Entre em contato:':'Entre em contato:','Email:':'Email:','Intagram:':'Intagram:','WhatsApp:':'WhatsApp:','Comente sua experiência aqui...':'Comente sua experiência aqui...'
        },
        en: {
            'Início':'Home','Diário':'Journal','Comunidade':'Community','Atividades':'Activities','Adicionais':'Activities','Perfil':'Profile',
            'Diário Pessoal':'Personal journal','Apoio 24 Horas':'24-hour support','Apoio emocional na palma da sua mão.':'Emotional support in the palm of your hand.','Conversar com o Helpy →':'Talk to Helpy →',
            'Alerta':'Alert','Segurança':'Safety','Processamento':'Processing','Pode dar gatilho':'May be triggering','Me deixou ansioso':'Made me anxious','Achei pesado':'I found it heavy','Cura a alma':'Healing','É leve':'It is light','Me trouxe paz':'Brought me peace','Relaxante':'Relaxing','Me fez refletir':'Made me reflect','História profunda':'Deep story','Me emocionou':'Moved me','Me deixou pensativo':'Made me thoughtful','Apagar':'Delete',
            'Sua Conta:':'Your Account:','Metas':'Goals','Calendário':'Calendar','Detalhes do dia':'Day details','Gráficos':'Charts',
            'Diários':'Journals','Emoções do Mês':'Monthly emotions','Sobre essa aba:':'About this section:','Textos':'Texts','Videos':'Videos',
            'A Técnica 4-7-8 para Alívio Imediato':'The 4-7-8 technique for immediate relief','O poder das pausas e da autocompaixão':'The power of pauses and self-compassion',
            'Práticas de Respiração e Foco':'Breathing and focus practices','Entendendo a Mente':'Understanding the mind',
            'Histórico':'History','Nova Conversa':'New conversation','Digite aqui para conversar com o Helpy...':'Type here to chat with Helpy...',
            'Atribua uma emoção ao seu diario:':'Choose an emotion for your journal:','Irritado':'Irritated','Ansioso':'Anxious','Feliz':'Happy','Calmo':'Calm','Triste':'Sad','Amoroso':'Loving',
            'Apagar tudo':'Clear all','Salvar':'Save','Editar':'Edit','Sair da conta':'Log out','Apagar Conta':'Delete account',
            'Notificações':'Notifications','Limpar':'Clear','Nenhuma notificação nova.':'No new notifications.','Bom':'Good','Ruim':'Bad','Reagir':'React',
            'Entre em contato:':'Contact us:','Email:':'Email:','Intagram:':'Instagram:','WhatsApp:':'WhatsApp:','Comente sua experiência aqui...':'Share your experience here...'
        }
    };

    var selConteudo = '.post-texto,.post-nome,.post-data,.comentario-nome,.tag-solida,.post-avatar,.post-midias-container,.attachment-name';

    function traduzirPagina() {
        var idioma = localStorage.getItem('helpfull_idioma') || 'pt-BR';
        var mapa   = textosPag[idioma] || textosPag['pt-BR'];
        var walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
        var nos = []; var no;
        while ((no = walker.nextNode())) nos.push(no);
        nos.forEach(function (t) {
            if (!t || !t.nodeValue || !t.nodeValue.trim()) return;
            if (!t.parentElement || (t.parentElement.closest && t.parentElement.closest(selConteudo))) return;
            var el = t.parentElement;
            var orig = el.dataset.idiomaOriginal || t.nodeValue.trim();
            el.dataset.idiomaOriginal = orig;
            if (mapa[orig]) t.nodeValue = t.nodeValue.replace(orig, mapa[orig]);
        });
        document.querySelectorAll('[placeholder]').forEach(function (el) {
            if (el.closest(selConteudo)) return;
            var orig = el.dataset.idiomaPlaceholder || el.getAttribute('placeholder');
            el.dataset.idiomaPlaceholder = orig;
            if (mapa[orig]) el.setAttribute('placeholder', mapa[orig]);
        });
        var longos = idioma === 'en' ? {
            '.texto-intro':'Well-being supports motivation and relationships, while emotional difficulties can affect mood and decision-making.',
            '.texto-extra-scrolled':'We all have difficult days, and you do not have to go through them alone. Explore a space focused on your well-being, track your mood, record your journey and discover tools designed to bring more clarity and calm to your daily life.',
            '.bloco-grande p':'We all have difficult days, and you do not have to go through them alone. Explore a space focused on your well-being, track your mood and discover tools designed to bring more clarity and calm to your daily life.',
            '.secao-cartoes a:nth-child(1) p, .secao-cartoes .cartao:nth-child(1) p':'Record your thoughts every day. Writing can ease the mind and help you better understand your emotions.',
            '.secao-cartoes a:nth-child(2) p, .secao-cartoes .cartao:nth-child(2) p':'Share experiences and feelings in a safe space. Find mutual support and welcoming without judgment.',
            '.secao-cartoes a:nth-child(3) p, .secao-cartoes .cartao:nth-child(3) p':'Access breathing and relaxation exercises to reduce stress and improve your focus throughout the day.',
            '.texto-intro-diario':'Writing in a journal can help you better understand your thoughts and feelings. Take a few minutes to write about your day, focusing on what went well, what challenged you and how you felt. You can also select the emotions that best describe your mood today.',
            '.card-sobre p':'Here you will find materials to support your self-awareness journey. Explore short articles, relaxation techniques and selected reflections to bring more clarity, calm and lightness to your day.',
            '.card-artigo:nth-of-type(1) p':'When your mind speeds up, your breathing is your greatest ally. The 4-7-8 technique acts as a natural calming method for the nervous system and helps you return to the present moment.',
            '.card-artigo:nth-of-type(2) p':'It is common to be overly demanding of ourselves when things do not go as planned. Self-compassion means treating yourself with the same kindness you would offer a friend in difficulty.',
            '.secao-video-header p':'Short videos with breathing techniques, focus exercises and guided reflections to help calm the mind and bring more lightness to your day.'
        } : {
            '.texto-intro':'O bem-estar impulsiona a motivação e os relacionamentos, enquanto dificuldades emocionais prejudicam o humor e a tomada de decisões.',
            '.texto-extra-scrolled':'Todos temos dias difíceis, e você não precisa passar por eles sozinho. Explore um ambiente focado no seu bem-estar, acompanhe seu humor, registre sua jornada e descubra ferramentas desenhadas para trazer mais clareza e tranquilidade para o seu dia a dia.',
            '.bloco-grande p':'Todos temos dias difíceis, e você não precisa passar por eles sozinho. Explore um ambiente focado no seu bem-estar, acompanhe seu humor e descubra ferramentas desenhadas para trazer mais clareza e tranquilidade para o seu dia a dia.',
            '.secao-cartoes a:nth-child(1) p, .secao-cartoes .cartao:nth-child(1) p':'Registre seus pensamentos diariamente. Escrever alivia a mente e ajuda a entender melhor suas emoções.',
            '.secao-cartoes a:nth-child(2) p, .secao-cartoes .cartao:nth-child(2) p':'Compartilhe suas experiências, desabafe e encontre apoio mútuo em um espaço seguro e acolhedor.',
            '.secao-cartoes a:nth-child(3) p, .secao-cartoes .cartao:nth-child(3) p':'Acesse exercícios de respiração e relaxamento para reduzir o estresse e melhorar seu foco no dia a dia.',
            '.texto-intro-diario':'Escrever um diário pode ajudar você a entender melhor seus pensamentos e sentimentos. Reserve alguns minutos para escrever sobre o seu dia, concentrando-se no que correu bem, no que o desafiou e como você se sentiu. Você também pode selecionar as emoções que melhor descrevem seu humor hoje.',
            '.card-sobre p':'Aqui reunimos materiais para apoiar sua jornada de autoconhecimento. Explore artigos rápidos, técnicas de relaxamento e reflexões selecionadas para ajudar a trazer mais clareza, calma e leveza ao seu dia a dia.',
            '.card-artigo:nth-of-type(1) p':'Quando a mente acelera, sua respiração é sua maior aliada. A técnica 4-7-8 atua como um calmante natural para o sistema nervoso e ajuda a trazer a mente de volta para o momento presente.',
            '.card-artigo:nth-of-type(2) p':'É muito comum nos cobrarmos excessivamente quando as coisas não saem como o planejado. A autocompaixão é tratar a si mesmo com a mesma gentileza que você ofereceria a um amigo em dificuldade.',
            '.secao-video-header p':'Aqui reunimos conteúdos visuais para apoiar sua jornada de bem-estar. Assista a vídeos curtos com técnicas de respiração, exercícios de foco e reflexões guiadas para ajudar a acalmar a mente e trazer mais leveza ao seu dia.'
        };
        Object.keys(longos).forEach(function (sel) {
            document.querySelectorAll(sel).forEach(function (el) {
                if (el.closest(selConteudo)) return;
                if (!el.dataset.idiomaOriginal) el.dataset.idiomaOriginal = el.textContent.trim();
                el.textContent = idioma === 'pt-BR' ? el.dataset.idiomaOriginal : longos[sel];
            });
        });
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
