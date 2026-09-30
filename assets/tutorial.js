/**
 * HELPFULL - SISTEMA DE TUTORIAL E TOUR GUIADO INTERATIVO
 * Proporciona um onboarding acolhedor e intuitivo para o primeiro uso do usuário.
 */
(function () {
    'use strict';

    var passoAtual = 0;
    var overlayEl = null;
    var cardEl = null;
    var glowEl = null;
    var svgMaskEl = null;
    var cutoutRectEl = null;
    var redimensionando = false;

    var passos = [
        {
            seletor: '#heroCarrosselCard',
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
            seletorFallback: '#nav-container-global',
            titulo: 'Navegação e Acessibilidade',
            icone: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>',
            categoria: 'TUDO DO SEU JEITO',
            desc: 'Acesse seu Perfil com estatísticas, altere temas (claro/escuro), use o Tradutor de Libras oficial e configure o site para o seu conforto.',
            dica: 'Você pode rever este tour interativo a qualquer momento clicando no botão do rodapé da página!',
            posicaoPreferida: 'bottom'
        }
    ];

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
                    <span class="tutorial-badge" id="tutorialBadge">✦ PASSO 1 DE 6</span>
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
        var el = document.querySelector(passo.seletor);
        if (!el && passo.seletorFallback) {
            el = document.querySelector(passo.seletorFallback);
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
        var passo = passos[index];
        if (!passo) return;

        var alvo = obterElementoAlvo(passo);
        var padding = 10;
        var rect;

        if (alvo) {
            rect = alvo.getBoundingClientRect();
        } else {
            // Se o elemento não existir, centraliza na tela
            var vw = window.innerWidth;
            var vh = window.innerHeight;
            rect = {
                top: vh / 2 - 50,
                bottom: vh / 2 + 50,
                left: vw / 2 - 150,
                right: vw / 2 + 150,
                width: 300,
                height: 100
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
                // No mobile, o card fica ancorado confortavelmente na parte inferior
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
        if (index < 0 || index >= passos.length) return;
        passoAtual = index;

        var passo = passos[index];
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
        document.getElementById('tutorialBadge').innerHTML = `✦ PASSO ${index + 1} DE ${passos.length} • ${passo.categoria}`;
        document.getElementById('tutorialStepIcon').innerHTML = passo.icone;
        document.getElementById('tutorialTitulo').textContent = passo.titulo;
        document.getElementById('tutorialDesc').textContent = passo.desc;
        document.getElementById('tutorialDicaTexto').textContent = passo.dica;

        // Barra de progresso
        var pct = Math.round(((index + 1) / passos.length) * 100);
        document.getElementById('tutorialProgressFill').style.width = pct + '%';

        // Dots
        renderizarDots(passos.length, index);

        // Botões
        var btnVoltar = document.getElementById('tutorialBtnVoltar');
        var btnProximo = document.getElementById('tutorialBtnProximo');

        btnVoltar.disabled = index === 0;

        if (index === passos.length - 1) {
            btnProximo.innerHTML = 'Concluir Tour ✨';
            btnProximo.style.background = 'linear-gradient(135deg, #10b981, #059669)';
        } else {
            btnProximo.innerHTML = 'Próximo →';
            btnProximo.style.background = '';
        }

        // Posiciona visualmente com pequeno delay para acomodar scroll suave
        setTimeout(function () {
            atualizarPosicoesPasso(index, true);
        }, 120);
    }

    function proximoPasso() {
        if (passoAtual < passos.length - 1) {
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

    function iniciarTutorial(forcar) {
        criarEstruturaDOM();

        // Se for forçado (ex: clique no botão do rodapé), ignora flag de concluído
        if (!forcar) {
            var uid = window._helpfullUsuarioId || '';
            var concluido = localStorage.getItem('helpfull_tutorial_concluido') === 'true' ||
                            (uid && localStorage.getItem('helpfull_tutorial_concluido_' + uid) === 'true');
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

        // Marca como concluído no localStorage
        var uid = window._helpfullUsuarioId || '';
        localStorage.setItem('helpfull_tutorial_concluido', 'true');
        if (uid) {
            localStorage.setItem('helpfull_tutorial_concluido_' + uid, 'true');
            localStorage.setItem('helpfull_onboarding_' + uid, 'true');
        }

        // Registra conclusão no banco de dados
        try {
            fetch('concluir_onboarding.php', { method: 'POST' }).catch(function () {});
        } catch (e) {}

        // Exibe toast de sucesso
        if (window.mostrarNotificacaoAtiva) {
            window.mostrarNotificacaoAtiva('Tour concluído! Agora você conhece todas as ferramentas do HelpFull. Bom autocuidado! ✨', 'inicio.php');
        }
    }

    // Exportações globais
    window.iniciarTutorial = iniciarTutorial;
    window.finalizarTutorial = finalizarTutorial;
    window.proximoPassoTutorial = proximoPasso;
    window.passoAnteriorTutorial = passoAnterior;

    // Inicialização automática para primeiro uso
    document.addEventListener('DOMContentLoaded', function () {
        var uid = window._helpfullUsuarioId || '';
        var jaViu = localStorage.getItem('helpfull_tutorial_concluido') === 'true' ||
                    (uid && localStorage.getItem('helpfull_tutorial_concluido_' + uid) === 'true');

        // Se for primeiro acesso e não houver onboarding modal bloqueando
        if (window._helpfullPrimeiraVez && !jaViu) {
            // Se o modal-onboarding existir, ele terá a opção de iniciar o tour
            var modalOnboarding = document.getElementById('modal-onboarding');
            if (!modalOnboarding) {
                setTimeout(function () {
                    iniciarTutorial(false);
                }, 900);
            }
        }
    });
})();
