/**
 * HelpFull - Fundo Animado Interativo
 * Recria o conceito orgânico suave do vídeo e das fotos de fundo (HELPFULL.png)
 * com fitas/ondas fluidas de degradê azul/teal (#2b7a8c, #48adc2, #7ecfdb)
 * e um brilho azul luminoso que segue o movimento do cursor suavemente.
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

        // Fitas e ondas orgânicas inspiradas no design HELPFULL.png
        // Paleta oficial: #1a5b6a, #2b7a8c (teal principal), #48adc2, #7ecfdb (azul céu), #c2eaf1
        const ribbons = [
            {
                baseY: 0.22,
                amp: 70,
                freq: 0.0018,
                speed: 0.00045,
                color1: 'rgba(43, 122, 140, 0.45)', // Teal escuro característico
                color2: 'rgba(72, 173, 194, 0.16)',
                phase: 0,
                thickness: 260
            },
            {
                baseY: 0.38,
                amp: 90,
                freq: 0.0014,
                speed: 0.00035,
                color1: 'rgba(72, 173, 194, 0.52)', // Azul turquesa vivo
                color2: 'rgba(126, 207, 219, 0.18)',
                phase: 2.1,
                thickness: 310
            },
            {
                baseY: 0.56,
                amp: 85,
                freq: 0.0016,
                speed: 0.00042,
                color1: 'rgba(126, 207, 219, 0.55)', // Azul piscina claro
                color2: 'rgba(194, 234, 241, 0.14)',
                phase: 4.3,
                thickness: 330
            },
            {
                baseY: 0.72,
                amp: 95,
                freq: 0.0011,
                speed: 0.00030,
                color1: 'rgba(43, 122, 140, 0.35)', // Fita de base para profundidade
                color2: 'rgba(72, 173, 194, 0.08)',
                phase: 1.2,
                thickness: 350
            },
            {
                baseY: 0.42,
                amp: 110,
                freq: 0.0009,
                speed: 0.00025,
                color1: 'rgba(26, 91, 106, 0.25)', // Toque suave de contraste teal
                color2: 'rgba(43, 122, 140, 0.05)',
                phase: 3.5,
                thickness: 390
            }
        ];

        let startTime = performance.now();

        function animar(timestamp) {
            const time = timestamp - startTime;

            // Velocidade e rastro suave do mouse
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

            // 1. Fundo Gradiente suave
            if (isDark) {
                const bgGrad = ctx.createLinearGradient(0, 0, width * 0.8, height);
                bgGrad.addColorStop(0, '#0f1418');
                bgGrad.addColorStop(0.5, '#151e24');
                bgGrad.addColorStop(1, '#0c1114');
                ctx.fillStyle = bgGrad;
            } else if (isContrast) {
                ctx.fillStyle = '#080a0c';
            } else {
                const bgGrad = ctx.createLinearGradient(0, 0, width * 0.8, height);
                bgGrad.addColorStop(0, '#e5eff2');
                bgGrad.addColorStop(0.4, '#edf5f7');
                bgGrad.addColorStop(1, '#f7fafb');
                ctx.fillStyle = bgGrad;
            }
            ctx.fillRect(0, 0, width, height);

            // 2. Renderização das fitas/ondas orgânicas
            ribbons.forEach((ribbon, idx) => {
                ctx.save();

                const yBase = height * ribbon.baseY;
                const waveTime = time * ribbon.speed + ribbon.phase;

                const grad = ctx.createLinearGradient(0, yBase - ribbon.amp, width, yBase + ribbon.thickness);
                if (isDark) {
                    grad.addColorStop(0, ribbon.color1.replace(/[\d\.]+\)$/, '0.32)'));
                    grad.addColorStop(1, ribbon.color2.replace(/[\d\.]+\)$/, '0.04)'));
                } else {
                    grad.addColorStop(0, ribbon.color1);
                    grad.addColorStop(1, ribbon.color2);
                }

                ctx.fillStyle = grad;
                ctx.beginPath();
                ctx.moveTo(-120, height + 120);
                ctx.lineTo(-120, yBase);

                const step = 40;
                for (let x = -120; x <= width + 120; x += step) {
                    const h1 = Math.sin(x * ribbon.freq + waveTime) * ribbon.amp;
                    const h2 = Math.cos(x * ribbon.freq * 1.6 - waveTime * 0.8) * (ribbon.amp * 0.38);

                    // Reação ao mouse: as ondas se movem suavemente com a proximidade do cursor
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

            // 3. Brilho azul que segue o mouse suavemente ("brilho azul segundo as cores da foto")
            ctx.save();

            // Expansão dinâmica baseada no movimento do cursor
            const speedBoost = Math.min(mouseSpeed * 0.006, 0.22);
            const baseRadius = Math.max(width, height) * 0.35;
            const glowRadius = baseRadius * (1 + speedBoost);

            const glowGrad = ctx.createRadialGradient(
                currentMouseX, currentMouseY, 0,
                currentMouseX, currentMouseY, glowRadius
            );

            if (isDark) {
                glowGrad.addColorStop(0, 'rgba(72, 196, 222, 0.42)'); // Ciano luminoso
                glowGrad.addColorStop(0.3, 'rgba(43, 122, 140, 0.25)'); // Teal HelpFull
                glowGrad.addColorStop(0.65, 'rgba(31, 100, 117, 0.10)');
                glowGrad.addColorStop(1, 'rgba(43, 122, 140, 0)');
            } else {
                glowGrad.addColorStop(0, 'rgba(88, 208, 230, 0.55)'); // Ciano/turquesa vivo
                glowGrad.addColorStop(0.28, 'rgba(43, 122, 140, 0.32)'); // Tom teal central
                glowGrad.addColorStop(0.65, 'rgba(126, 207, 219, 0.15)'); // Azul suave
                glowGrad.addColorStop(1, 'rgba(43, 122, 140, 0)');
            }

            ctx.fillStyle = glowGrad;
            ctx.beginPath();
            ctx.arc(currentMouseX, currentMouseY, glowRadius, 0, Math.PI * 2);
            ctx.fill();

            // Ponto de luz interno concentrado no cursor
            const coreRadius = 150 * (1 + speedBoost);
            const coreGlow = ctx.createRadialGradient(
                currentMouseX, currentMouseY, 0,
                currentMouseX, currentMouseY, coreRadius
            );
            coreGlow.addColorStop(0, isDark ? 'rgba(162, 238, 252, 0.45)' : 'rgba(255, 255, 255, 0.50)');
            coreGlow.addColorStop(0.45, 'rgba(72, 196, 222, 0.26)');
            coreGlow.addColorStop(1, 'rgba(72, 196, 222, 0)');

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
