/**
 * HelpFull - Sistema de Transição Rápida e Suave entre Telas
 * 1. Pré-carregamento especulativo (Speculation Rules API para renderização instantânea)
 * 2. Pré-busca preditiva ao passar o mouse ou encostar o dedo (Hover/Touch Prefetch)
 * 3. Barra de transição luminosa com feedback tátil e visual imediato
 * 4. Transições de tela suaves sem travamentos ou tela branca
 */
(function () {
    'use strict';

    // 1. Injetar Regras Especulativas (Speculation Rules API) para navegadores modernos (Chrome/Edge/Opera)
    function habilitarEspeculacao() {
        if (HTMLScriptElement.supports && HTMLScriptElement.supports('speculationrules')) {
            if (!document.querySelector('script[type="speculationrules"]')) {
                const specScript = document.createElement('script');
                specScript.type = 'speculationrules';
                specScript.textContent = JSON.stringify({
                    prerender: [
                        {
                            source: 'list',
                            urls: [
                                'inicio.php',
                                'Comunidade.php',
                                'Diario.php',
                                'ChatBOT.php',
                                'Atividades.php',
                                'Perfil.php'
                            ],
                            eagerness: 'moderate'
                        }
                    ],
                    prefetch: [
                        {
                            source: 'list',
                            urls: [
                                'inicio.php',
                                'Comunidade.php',
                                'Diario.php',
                                'ChatBOT.php',
                                'Atividades.php',
                                'Perfil.php'
                            ],
                            eagerness: 'moderate'
                        }
                    ]
                });
                document.head.appendChild(specScript);
            }
        }
    }

    // 2. Pré-busca sob demanda ao passar o mouse / toque (Hover & Touch Prefetch)
    const linksPreCarregados = new Set();

    function preCarregarUrl(url) {
        if (!url || linksPreCarregados.has(url)) return;
        try {
            const parsed = new URL(url, window.location.href);
            // Somente mesma origem e páginas PHP/internas
            if (parsed.origin !== window.location.origin) return;
            if (parsed.pathname === window.location.pathname && parsed.search === window.location.search) return;
            const pathLower = parsed.pathname.toLowerCase();
            if (pathLower.includes('logout') || pathLower.includes('sair') || pathLower.includes('comeco') || pathLower.includes('login') || pathLower.includes('cadastro')) return;

            linksPreCarregados.add(url);

            const prefetchLink = document.createElement('link');
            prefetchLink.rel = 'prefetch';
            prefetchLink.href = parsed.href;
            prefetchLink.as = 'document';
            document.head.appendChild(prefetchLink);
        } catch (e) {}
    }

    function inicializarPreCarregamentoAoHover() {
        const tratarElemento = (el) => {
            const link = el.closest ? el.closest('a') : null;
            if (!link || !link.href) return;
            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
            if (link.target && link.target !== '_self') return;
            preCarregarUrl(link.href);
        };

        document.addEventListener('pointerenter', (e) => {
            tratarElemento(e.target);
        }, { passive: true, capture: true });

        document.addEventListener('touchstart', (e) => {
            tratarElemento(e.target);
        }, { passive: true, capture: true });
    }

    // 3. Barra de Progresso Luminosa no Topo
    let barraProgresso = null;
    let timerProgresso = null;

    function obterOuCriarBarra() {
        if (!barraProgresso) {
            barraProgresso = document.getElementById('helpfull-transicao-barra');
            if (!barraProgresso) {
                barraProgresso = document.createElement('div');
                barraProgresso.id = 'helpfull-transicao-barra';
                document.body.prepend(barraProgresso);
            }
        }
        return barraProgresso;
    }

    function iniciarBarraProgresso() {
        const barra = obterOuCriarBarra();
        if (!barra) return;

        clearTimeout(timerProgresso);
        barra.className = 'ativo';
        barra.style.width = '25%';

        timerProgresso = setTimeout(() => {
            barra.style.width = '65%';
            timerProgresso = setTimeout(() => {
                barra.style.width = '85%';
            }, 180);
        }, 120);
    }

    function concluirBarraProgresso() {
        const barra = obterOuCriarBarra();
        if (!barra) return;

        clearTimeout(timerProgresso);
        barra.style.width = '100%';
        barra.className = 'ativo finalizado';

        setTimeout(() => {
            barra.className = '';
            barra.style.width = '0%';
        }, 300);
    }

    // 4. Interceptação de clique para transição suave de saída
    function inicializarTransicaoNavegacao() {
        document.addEventListener('click', (e) => {
            // Ignorar se clicou com botões modificadores (abrir em nova aba, etc.)
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;

            const link = e.target.closest('a');
            if (!link || !link.href) return;

            const hrefAttr = link.getAttribute('href');
            if (!hrefAttr || hrefAttr.startsWith('#') || hrefAttr.startsWith('javascript:')) return;
            if (link.target && link.target !== '_self') return;
            if (link.hasAttribute('download')) return;

            // Se for link externo
            try {
                const targetUrl = new URL(link.href, window.location.href);
                if (targetUrl.origin !== window.location.origin) return;

                // Se for a mesma página exata com hash
                if (targetUrl.pathname === window.location.pathname && targetUrl.search === window.location.search && targetUrl.hash) {
                    return;
                }

                // Inicia barra de progresso imediata
                iniciarBarraProgresso();

                // Se o navegador não suportar View Transitions nativas, aplica fade suave
                if (!document.startViewTransition) {
                    document.body.classList.add('helpfull-saindo');
                }
            } catch (err) {}
        }, { capture: true });
    }

    // 5. Inicialização no carregamento
    function inicializar() {
        habilitarEspeculacao();
        inicializarPreCarregamentoAoHover();
        inicializarTransicaoNavegacao();

        // Animação de entrada suave
        document.body.classList.add('helpfull-entrando');
        concluirBarraProgresso();

        // Suporte a bfcache (navegação Voltar/Avançar no histórico)
        window.addEventListener('pageshow', (event) => {
            document.body.classList.remove('helpfull-saindo');
            concluirBarraProgresso();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializar);
    } else {
        inicializar();
    }
})();
