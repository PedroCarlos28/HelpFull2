/**
 * HelpFull - Fundo Animado Interativo com Ondas Orgânicas Suaves
 * 
 * - Animação orgânica de fitas fluidas da versão anterior (suave, relaxante e reativa ao cursor).
 * - Cores extraídas da imagem de referência:
 *     * Topo da tela/animação MAIS CLARO: tons pérola, branco luminoso e azul celeste suave (#eef6fa, #72d2e8).
 *     * Centro: ciano vibrante e turquesa elétrico (#38b9dc, #48cae4) e azul oceano (#1e70a0).
 *     * Final da tela/animação MAIS ESCURO: azul marinho profundo e meia-noite (#0c2b42, #103e5c, #144b6f).
 * - O brilho azul segue suavemente o cursor e interage com a ondulação natural das fitas.
 */
(function () {
    'use strict';

    function initFundoAnimado() {
        let canvas = document.getElementById('fundoAnimadoCanvas');
        if (!canvas) {
            canvas = document.createElement('canvas');
            canvas.id = 'fundoAnimadoCanvas';
            canvas.className = 'fundo-animado-canvas';
            document.body.insertBefore(canvas, document.body.firstChild);
        }

        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        let width = 0;
        let height = 0;
        let dpr = Math.min(window.devicePixelRatio || 1, 1.5);

        // Posição do cursor com interpolação contínua (lerp)
        let mouseX = window.innerWidth * 0.5;
        let mouseY = window.innerHeight * 0.35;
        let currentMouseX = mouseX;
        let currentMouseY = mouseY;
        let prevX = mouseX;
        let prevY = mouseY;
        let mouseSpeed = 0;
        let mouseMoved = false;

        function redimensionar() {
            width = window.innerWidth;
            height = window.innerHeight;
            canvas.width = Math.floor(width * dpr);
            canvas.height = Math.floor(height * dpr);
            ctx.scale(dpr, dpr);
        }

        window.addEventListener('resize', redimensionar, { passive: true });
        redimensionar();

        // Eventos de movimento
        window.addEventListener('mousemove', function (e) {
            mouseX = e.clientX;
            mouseY = e.clientY;
            mouseMoved = true;
        }, { passive: true });

        window.addEventListener('touchmove', function (e) {
            if (e.touches && e.touches.length > 0) {
                mouseX = e.touches[0].clientX;
                mouseY = e.touches[0].clientY;
                mouseMoved = true;
            }
        }, { passive: true });

        /**
         * Fitas e ondas orgânicas com progressão de luminosidade:
         * Topo claro -> Centro vibrante -> Base/final bem escuro
         */
        const ribbons = [
            // 0. Brilho sutil no topo mais alto (claridade máxima no topo)
            {
                baseY: 0.12,
                amp: 55,
                freq: 0.0019,
                speed: 0.00048,
                color1: 'rgba(255, 255, 255, 0.45)', // Crista branca luminosa no topo
                color2: 'rgba(180, 235, 248, 0.12)',
                phase: 0.5,
                thickness: 220
            },
            // 1. Fita superior suave (azul celeste claro)
            {
                baseY: 0.22,
                amp: 72,
                freq: 0.0017,
                speed: 0.00042,
                color1: 'rgba(114, 210, 232, 0.48)', // Azul celeste claro
                color2: 'rgba(180, 238, 248, 0.16)',
                phase: 0,
                thickness: 270
            },
            // 2. Fita superior-média (ciano vibrante)
            {
                baseY: 0.38,
                amp: 92,
                freq: 0.0014,
                speed: 0.00035,
                color1: 'rgba(56, 185, 220, 0.56)', // Ciano vibrante
                color2: 'rgba(114, 210, 232, 0.20)',
                phase: 2.1,
                thickness: 315
            },
            // 3. Fita central (azul cerúleo / oceano)
            {
                baseY: 0.54,
                amp: 88,
                freq: 0.0015,
                speed: 0.00040,
                color1: 'rgba(30, 112, 160, 0.65)', // Azul oceano rico
                color2: 'rgba(56, 185, 220, 0.22)',
                phase: 4.3,
                thickness: 340
            },
            // 4. Fita inferior (azul marinho escuro)
            {
                baseY: 0.72,
                amp: 98,
                freq: 0.0011,
                speed: 0.00030,
                color1: 'rgba(20, 75, 111, 0.78)', // Azul escuro da base
                color2: 'rgba(30, 112, 160, 0.30)',
                phase: 1.2,
                thickness: 370
            },
            // 5. Fita mais escura no final da página (profundidade máxima na base)
            {
                baseY: 0.86,
                amp: 110,
                freq: 0.0009,
                speed: 0.00025,
                color1: 'rgba(12, 43, 66, 0.90)', // Azul mais escuro no final
                color2: 'rgba(20, 75, 111, 0.45)',
                phase: 3.5,
                thickness: 410
            }
        ];

        let startTime = performance.now();

        function animar(timestamp) {
            const time = timestamp - startTime;

            // Velocidade e suavização do cursor
            const moveDelta = Math.hypot(mouseX - prevX, mouseY - prevY);
            mouseSpeed += (moveDelta - mouseSpeed) * 0.1;
            prevX = mouseX;
            prevY = mouseY;

            currentMouseX += (mouseX - currentMouseX) * 0.06;
            currentMouseY += (mouseY - currentMouseY) * 0.06;

            // Flutuação automática autônoma quando o mouse estiver parado
            if (!mouseMoved) {
                mouseX = width * 0.5 + Math.cos(time * 0.0007) * (width * 0.25);
                mouseY = height * 0.38 + Math.sin(time * 0.0010) * (height * 0.18);
            }

            const isDark = document.body.classList.contains('acessibilidade-escuro');
            const isContrast = document.body.classList.contains('acessibilidade-contraste');

            ctx.clearRect(0, 0, width, height);

            // 1. Fundo Gradiente: MAIS CLARO NO TOPO e MAIS ESCURO NO FINAL DA PÁGINA
            if (isDark) {
                const bgGrad = ctx.createLinearGradient(0, 0, 0, height);
                bgGrad.addColorStop(0, '#15212a');    // Topo moderado
                bgGrad.addColorStop(0.5, '#0e1820');  // Meio
                bgGrad.addColorStop(1, '#050a0e');    // Final escuro profundo
                ctx.fillStyle = bgGrad;
            } else if (isContrast) {
                ctx.fillStyle = '#06080a';
            } else {
                const bgGrad = ctx.createLinearGradient(0, 0, 0, height);
                bgGrad.addColorStop(0, '#f2f8fa');    // Muito claro e luminoso no topo
                bgGrad.addColorStop(0.25, '#def0f6'); // Azul celeste suave
                bgGrad.addColorStop(0.55, '#86d1e4'); // Ciano intermediário
                bgGrad.addColorStop(0.80, '#2178a2'); // Azul oceano
                bgGrad.addColorStop(1, '#0c2b42');    // Bem escuro no final da tela/página
                ctx.fillStyle = bgGrad;
            }
            ctx.fillRect(0, 0, width, height);

            // 2. Renderização das fitas/ondas orgânicas
            ribbons.forEach((ribbon) => {
                ctx.save();

                const yBase = height * ribbon.baseY;
                const waveTime = time * ribbon.speed + ribbon.phase;

                // Gradiente vertical dentro de cada fita acompanhando o fluxo claro -> escuro
                const grad = ctx.createLinearGradient(0, yBase - ribbon.amp, 0, yBase + ribbon.thickness);
                if (isDark) {
                    grad.addColorStop(0, ribbon.color1.replace(/[\d\.]+\)$/, '0.35)'));
                    grad.addColorStop(1, ribbon.color2.replace(/[\d\.]+\)$/, '0.05)'));
                } else {
                    grad.addColorStop(0, ribbon.color1);
                    grad.addColorStop(1, ribbon.color2);
                }

                ctx.fillStyle = grad;
                ctx.beginPath();
                ctx.moveTo(-120, height + 120);
                ctx.lineTo(-120, yBase);

                const step = 35;
                for (let x = -120; x <= width + 120; x += step) {
                    const h1 = Math.sin(x * ribbon.freq + waveTime) * ribbon.amp;
                    const h2 = Math.cos(x * ribbon.freq * 1.6 - waveTime * 0.8) * (ribbon.amp * 0.38);

                    // Reação suave ao mouse (distorção ondulatória orgânica ao passar perto)
                    const dx = x - currentMouseX;
                    const distToMouseX = Math.abs(dx);
                    let mouseDistort = 0;
                    if (distToMouseX < 380) {
                        const factor = 1 - (distToMouseX / 380);
                        const dy = yBase - currentMouseY;
                        mouseDistort = Math.sin(factor * Math.PI) * (dy * 0.16);
                    }

                    const y = yBase + h1 + h2 + mouseDistort;
                    ctx.lineTo(x, y);
                }

                ctx.lineTo(width + 120, height + 120);
                ctx.closePath();
                ctx.fill();

                ctx.restore();
            });

            // 3. Brilho azul que segue o mouse suavemente
            ctx.save();

            const speedBoost = Math.min(mouseSpeed * 0.006, 0.22);
            const baseRadius = Math.max(width, height) * 0.34;
            const glowRadius = baseRadius * (1 + speedBoost);

            const glowGrad = ctx.createRadialGradient(
                currentMouseX, currentMouseY, 0,
                currentMouseX, currentMouseY, glowRadius
            );

            if (isDark) {
                glowGrad.addColorStop(0, 'rgba(72, 202, 228, 0.44)');
                glowGrad.addColorStop(0.3, 'rgba(30, 112, 160, 0.25)');
                glowGrad.addColorStop(0.65, 'rgba(15, 56, 86, 0.10)');
                glowGrad.addColorStop(1, 'rgba(15, 56, 86, 0)');
            } else {
                glowGrad.addColorStop(0, 'rgba(56, 185, 220, 0.58)'); // Ciano vibrante
                glowGrad.addColorStop(0.28, 'rgba(30, 112, 160, 0.34)'); // Azul oceano
                glowGrad.addColorStop(0.65, 'rgba(114, 210, 232, 0.16)'); // Azul suave
                glowGrad.addColorStop(1, 'rgba(30, 112, 160, 0)');
            }

            ctx.fillStyle = glowGrad;
            ctx.beginPath();
            ctx.arc(currentMouseX, currentMouseY, glowRadius, 0, Math.PI * 2);
            ctx.fill();

            // Ponto de luz interno concentrado no cursor
            const coreRadius = 145 * (1 + speedBoost);
            const coreGlow = ctx.createRadialGradient(
                currentMouseX, currentMouseY, 0,
                currentMouseX, currentMouseY, coreRadius
            );
            coreGlow.addColorStop(0, isDark ? 'rgba(180, 240, 255, 0.50)' : 'rgba(255, 255, 255, 0.60)');
            coreGlow.addColorStop(0.45, 'rgba(72, 202, 228, 0.28)');
            coreGlow.addColorStop(1, 'rgba(72, 202, 228, 0)');

            ctx.fillStyle = coreGlow;
            ctx.beginPath();
            ctx.arc(currentMouseX, currentMouseY, coreRadius, 0, Math.PI * 2);
            ctx.fill();

            ctx.restore();

            requestAnimationFrame(animar);
        }

        requestAnimationFrame(animar);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFundoAnimado);
    } else {
        initFundoAnimado();
    }
})();
