/**
 * HelpFull - Fundo Animado Interativo com Ondas Fluidas e Rastro do Cursor
 * 
 * 1. Paleta de Cores Fiel à Imagem de Referência:
 *    - Tons de azul oceano profundo (#0f3856, #144b6f, #195880)
 *    - Ciano vibrante e turquesa elétrico (#2488bc, #38b9dc, #48cae4)
 *    - Azul celeste suave (#7ad8ee, #a8e9f7)
 *    - Cristas luminosas em branco puro (#ffffff) desenhando as curvas das ondas
 * 
 * 2. Linhas com Ondas que cruzam a tela ("tipo linhas com ondas", sem ser só na base):
 *    - Fitas ondulantes superiores, diagonais no centro e arcos na base.
 * 
 * 3. Interação do Mouse:
 *    - Ao movimentar o mouse, as outras ondas se acalmam e diminuem suavemente (ficam menores e mais calmas).
 *    - Quando o mouse para, as ondas voltam a respirar e fluir normalmente.
 * 
 * 4. Rastro Fluido do Mouse ("rastro do mouse quando mexer"):
 *    - Rastro contínuo em fita luminosa azul degradê com gotas e partículas de luz.
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

        // Posição e rastreamento do mouse
        let mouseX = window.innerWidth * 0.5;
        let mouseY = window.innerHeight * 0.35;
        let currentMouseX = mouseX;
        let currentMouseY = mouseY;
        let prevMouseX = mouseX;
        let prevMouseY = mouseY;
        let mouseSpeed = 0;
        let isMouseActive = false;
        let lastMoveTimestamp = 0;

        // Fator de acalmar as ondas: 0 = normal (cheias e ativas), 1 = calmas e pequeninas
        let calmIntensity = 0;

        // Histórico de pontos para o rastro do mouse
        const trailPoints = [];
        const MAX_TRAIL_POINTS = 30;

        // Partículas brilhantes soltas no rastro
        const trailParticles = [];
        const MAX_PARTICLES = 35;

        function redimensionar() {
            width = window.innerWidth;
            height = window.innerHeight;
            canvas.width = Math.floor(width * dpr);
            canvas.height = Math.floor(height * dpr);
            ctx.scale(dpr, dpr);
        }

        window.addEventListener('resize', redimensionar, { passive: true });
        redimensionar();

        // Adiciona ponto ao rastro
        function registrarPontoRastro(x, y, speed) {
            const now = performance.now();
            lastMoveTimestamp = now;

            trailPoints.unshift({
                x: x,
                y: y,
                time: now,
                speed: speed,
                width: Math.min(18 + speed * 0.8, 38)
            });

            if (trailPoints.length > MAX_TRAIL_POINTS) {
                trailPoints.pop();
            }

            // Gera pequenas partículas luminosas flutuantes no rastro se estiver em movimento
            if (speed > 3 && trailParticles.length < MAX_PARTICLES && Math.random() < 0.65) {
                trailParticles.push({
                    x: x + (Math.random() - 0.5) * 20,
                    y: y + (Math.random() - 0.5) * 20,
                    vx: (Math.random() - 0.5) * 1.5,
                    vy: (Math.random() - 0.5) * 1.5 - 0.5,
                    radius: Math.random() * 8 + 4,
                    alpha: 0.85,
                    decay: Math.random() * 0.025 + 0.02
                });
            }
        }

        // Eventos de mouse
        window.addEventListener('mousemove', function (e) {
            mouseX = e.clientX;
            mouseY = e.clientY;
            isMouseActive = true;
            registrarPontoRastro(mouseX, mouseY, mouseSpeed);
        }, { passive: true });

        // Eventos de toque no celular/tablet
        window.addEventListener('touchmove', function (e) {
            if (e.touches && e.touches.length > 0) {
                mouseX = e.touches[0].clientX;
                mouseY = e.touches[0].clientY;
                isMouseActive = true;
                registrarPontoRastro(mouseX, mouseY, mouseSpeed);
            }
        }, { passive: true });

        /**
         * Ondas e fitas inspiradas diretamente na foto de referência:
         * 1. Fita superior arqueada (curva suave no topo esquerdo)
         * 2. Grande fita diagonal que corta o centro com tons azuis ricos e crista branca
         * 3. Onda inferior central ondulante
         * 4. Arco ascendente circular no canto inferior direito
         * 5. Fita translúcida secundária que entrelaça o meio
         */
        const waveRibbons = [
            // 1. Fita ondulante superior (topo e esquerda)
            {
                p0: { rx: -0.15, ry: 0.22 },
                p1: { rx: 0.22, ry: 0.04 },
                p2: { rx: 0.55, ry: 0.35 },
                p3: { rx: 1.15, ry: 0.12 },
                baseAmp: 85,
                freq: 0.0015,
                speed: 0.00045,
                phase: 0.2,
                baseLineWidth: 190,
                colorStart: '#16547e', // Azul oceano profundo
                colorMid: '#2488bc',   // Azul cerúleo
                colorEnd: '#48cae4',   // Ciano vivo
                crestColor: 'rgba(255, 255, 255, 0.92)'
            },
            // 2. Fita diagonal principal (corta o centro da tela como na foto)
            {
                p0: { rx: -0.2, ry: 0.52 },
                p1: { rx: 0.32, ry: 0.25 },
                p2: { rx: 0.68, ry: 0.70 },
                p3: { rx: 1.2, ry: 0.40 },
                baseAmp: 110,
                freq: 0.0011,
                speed: 0.00035,
                phase: 2.1,
                baseLineWidth: 240,
                colorStart: '#0f3856', // Azul marinho escuro
                colorMid: '#1b6fa5',   // Azul intenso
                colorEnd: '#38b9dc',   // Ciano vibrante
                crestColor: 'rgba(255, 255, 255, 0.95)'
            },
            // 3. Fita ondulada inferior suave
            {
                p0: { rx: -0.1, ry: 0.85 },
                p1: { rx: 0.38, ry: 0.68 },
                p2: { rx: 0.72, ry: 0.94 },
                p3: { rx: 1.15, ry: 0.78 },
                baseAmp: 90,
                freq: 0.0014,
                speed: 0.00040,
                phase: 4.3,
                baseLineWidth: 220,
                colorStart: '#144b6f',
                colorMid: '#2898c0',
                colorEnd: '#7ad8ee',
                crestColor: 'rgba(235, 250, 255, 0.88)'
            },
            // 4. Arco circular dinâmico no canto inferior direito (idêntico à foto)
            {
                p0: { rx: 0.48, ry: 1.2 },
                p1: { rx: 0.65, ry: 0.62 },
                p2: { rx: 0.88, ry: 0.70 },
                p3: { rx: 1.18, ry: 0.98 },
                baseAmp: 75,
                freq: 0.0019,
                speed: 0.00050,
                phase: 1.2,
                baseLineWidth: 175,
                colorStart: '#0c2f48',
                colorMid: '#1e73a8',
                colorEnd: '#48cae4',
                crestColor: 'rgba(255, 255, 255, 0.90)'
            },
            // 5. Linha de apoio sutil translúcida cruzando a tela
            {
                p0: { rx: -0.1, ry: 0.40 },
                p1: { rx: 0.45, ry: 0.55 },
                p2: { rx: 0.75, ry: 0.22 },
                p3: { rx: 1.15, ry: 0.50 },
                baseAmp: 70,
                freq: 0.0013,
                speed: 0.00030,
                phase: 3.5,
                baseLineWidth: 140,
                colorStart: '#207cae',
                colorMid: '#38b9dc',
                colorEnd: '#a8e9f7',
                crestColor: 'rgba(255, 255, 255, 0.70)'
            }
        ];

        let startTime = performance.now();

        function animar(timestamp) {
            const time = timestamp - startTime;

            // 1. Calcula a velocidade instantânea do mouse
            const dist = Math.hypot(mouseX - prevMouseX, mouseY - prevMouseY);
            mouseSpeed += (dist - mouseSpeed) * 0.2;
            prevMouseX = mouseX;
            prevMouseY = mouseY;

            // Interpolação suave para o foco do mouse
            currentMouseX += (mouseX - currentMouseX) * 0.09;
            currentMouseY += (mouseY - currentMouseY) * 0.09;

            // 2. Comportamento Interativo:
            // "quando movimentar o mouse as outras ondas ficam menorezinhas e mais calmas"
            const isMoving = mouseSpeed > 1.0;
            if (isMoving) {
                // Ao mexer o mouse, calmIntensity sobe rapidamente em direção a 1.0
                calmIntensity += (1.0 - calmIntensity) * 0.12;
            } else {
                // Quando o mouse para, calmIntensity retorna suavemente a 0.0
                calmIntensity += (0.0 - calmIntensity) * 0.035;
            }

            // Movimento suave autônomo se o usuário não mexer
            if (!isMouseActive) {
                mouseX = width * 0.5 + Math.cos(time * 0.0006) * (width * 0.24);
                mouseY = height * 0.38 + Math.sin(time * 0.0008) * (height * 0.15);
            }

            const isDark = document.body.classList.contains('acessibilidade-escuro');
            const isContrast = document.body.classList.contains('acessibilidade-contraste');

            ctx.clearRect(0, 0, width, height);

            // 3. Fundo base: gradiente pérola claro como na foto de referência
            if (isDark) {
                const bg = ctx.createLinearGradient(0, 0, width, height);
                bg.addColorStop(0, '#0a1015');
                bg.addColorStop(0.5, '#101820');
                bg.addColorStop(1, '#080d11');
                ctx.fillStyle = bg;
            } else if (isContrast) {
                ctx.fillStyle = '#050709';
            } else {
                const bg = ctx.createLinearGradient(0, 0, width, height);
                bg.addColorStop(0, '#edf3f6');
                bg.addColorStop(0.35, '#f1f6f8');
                bg.addColorStop(0.7, '#f7fafc');
                bg.addColorStop(1, '#f9fcfe');
                ctx.fillStyle = bg;
            }
            ctx.fillRect(0, 0, width, height);

            // 4. Modulação das ondas:
            // Quando calmIntensity = 1 (mouse em movimento):
            // - A amplitude diminui para 32% (ficam bem pequenininhas)
            // - A velocidade e frequência diminuem para 28% (ficam bem calmas)
            // - A espessura da linha também afina suavemente
            const ampFactor = 1.0 - (calmIntensity * 0.68);
            const speedFactor = 1.0 - (calmIntensity * 0.72);
            const widthFactor = 1.0 - (calmIntensity * 0.25);

            // 5. Renderização das LINHAS COM ONDAS
            waveRibbons.forEach((ribbon) => {
                ctx.save();

                const waveTime = time * (ribbon.speed * speedFactor) + ribbon.phase;
                const dynamicAmp = ribbon.baseAmp * ampFactor;

                // Pontos da curva Bezier com deslocamento ondulatório
                const startX = ribbon.p0.rx * width;
                const startY = ribbon.p0.ry * height + Math.sin(waveTime) * (dynamicAmp * 0.7);

                const cp1X = ribbon.p1.rx * width + Math.cos(waveTime * 0.8) * (dynamicAmp * 0.6);
                const cp1Y = ribbon.p1.ry * height + Math.sin(waveTime * 1.1) * dynamicAmp;

                const cp2X = ribbon.p2.rx * width + Math.sin(waveTime * 0.9) * (dynamicAmp * 0.7);
                const cp2Y = ribbon.p2.ry * height + Math.cos(waveTime * 1.15) * dynamicAmp;

                const endX = ribbon.p3.rx * width;
                const endY = ribbon.p3.ry * height + Math.sin(waveTime * 0.75) * (dynamicAmp * 0.8);

                // Gradiente linear ao longo da trajetória da onda
                const grad = ctx.createLinearGradient(startX, startY, endX, endY);
                if (isDark) {
                    grad.addColorStop(0, ribbon.colorStart + '88');
                    grad.addColorStop(0.5, ribbon.colorMid + '99');
                    grad.addColorStop(1, ribbon.colorEnd + '66');
                } else {
                    grad.addColorStop(0, ribbon.colorStart + 'd9');
                    grad.addColorStop(0.48, ribbon.colorMid + 'f0');
                    grad.addColorStop(1, ribbon.colorEnd + 'aa');
                }

                ctx.strokeStyle = grad;
                ctx.lineWidth = ribbon.baseLineWidth * widthFactor;
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';

                ctx.beginPath();
                ctx.moveTo(startX, startY);
                ctx.bezierCurveTo(cp1X, cp1Y, cp2X, cp2Y, endX, endY);
                ctx.stroke();

                // CRISTA BRANCA / LUZ LUMINOSA (linha branca que define a onda como na foto)
                if (!isDark && !isContrast) {
                    ctx.save();
                    const crestOffset = (ribbon.baseLineWidth * widthFactor) * 0.28;
                    const crestGrad = ctx.createLinearGradient(startX, startY - crestOffset, endX, endY - crestOffset);
                    crestGrad.addColorStop(0, 'rgba(255, 255, 255, 0)');
                    crestGrad.addColorStop(0.25, ribbon.crestColor);
                    crestGrad.addColorStop(0.55, 'rgba(255, 255, 255, 0.98)');
                    crestGrad.addColorStop(0.8, 'rgba(168, 233, 247, 0.85)');
                    crestGrad.addColorStop(1, 'rgba(255, 255, 255, 0)');

                    ctx.strokeStyle = crestGrad;
                    ctx.lineWidth = (ribbon.baseLineWidth * widthFactor) * 0.30;
                    ctx.beginPath();
                    ctx.moveTo(startX, startY - crestOffset);
                    ctx.bezierCurveTo(
                        cp1X, cp1Y - crestOffset,
                        cp2X, cp2Y - crestOffset,
                        endX, endY - crestOffset
                    );
                    ctx.stroke();
                    ctx.restore();
                }

                ctx.restore();
            });

            // 6. RASTRO DO MOUSE ("e faz o rastro do mouse quando mexer")
            // A) Fita contínua conectada com interpolação suave
            const now = performance.now();
            // Remove pontos muito antigos
            for (let i = trailPoints.length - 1; i >= 0; i--) {
                if (now - trailPoints[i].time > 650) {
                    trailPoints.splice(i, 1);
                }
            }

            if (trailPoints.length > 2) {
                // Desenha a fita do rastro em camadas para brilho intenso
                for (let layer = 0; layer < 2; layer++) {
                    ctx.save();
                    for (let i = 0; i < trailPoints.length - 1; i++) {
                        const p0 = trailPoints[i];
                        const p1 = trailPoints[i + 1];
                        const ageP0 = (now - p0.time) / 650;
                        const ageP1 = (now - p1.time) / 650;
                        const life = Math.max(0, 1 - (ageP0 + ageP1) * 0.5);

                        const midX = (p0.x + p1.x) * 0.5;
                        const midY = (p0.y + p1.y) * 0.5;

                        ctx.beginPath();
                        ctx.moveTo(p0.x, p0.y);
                        ctx.lineTo(p1.x, p1.y);

                        if (layer === 0) {
                            // Camada externa: halo azul/ciano brilhante
                            ctx.strokeStyle = isDark
                                ? `rgba(56, 185, 220, ${life * 0.55})`
                                : `rgba(36, 136, 188, ${life * 0.65})`;
                            ctx.lineWidth = (p0.width + p1.width) * 0.5 * life;
                        } else {
                            // Camada interna: núcleo ciano claro / branco
                            ctx.strokeStyle = isDark
                                ? `rgba(168, 233, 247, ${life * 0.75})`
                                : `rgba(255, 255, 255, ${life * 0.85})`;
                            ctx.lineWidth = (p0.width + p1.width) * 0.22 * life;
                        }
                        ctx.lineCap = 'round';
                        ctx.stroke();
                    }
                    ctx.restore();
                }
            }

            // B) Partículas e gotas luminosas flutuando no rastro
            for (let i = trailParticles.length - 1; i >= 0; i--) {
                const pt = trailParticles[i];
                pt.x += pt.vx;
                pt.y += pt.vy;
                pt.alpha -= pt.decay;
                pt.radius *= 0.97;

                if (pt.alpha <= 0 || pt.radius <= 0.5) {
                    trailParticles.splice(i, 1);
                    continue;
                }

                ctx.save();
                const pGrad = ctx.createRadialGradient(pt.x, pt.y, 0, pt.x, pt.y, pt.radius * 2);
                pGrad.addColorStop(0, `rgba(255, 255, 255, ${pt.alpha * 0.95})`);
                pGrad.addColorStop(0.4, `rgba(56, 185, 220, ${pt.alpha * 0.8})`);
                pGrad.addColorStop(1, `rgba(24, 136, 188, 0)`);
                ctx.fillStyle = pGrad;
                ctx.beginPath();
                ctx.arc(pt.x, pt.y, pt.radius * 2, 0, Math.PI * 2);
                ctx.fill();
                ctx.restore();
            }

            // 7. BRILHO AZUL NO CURSOR (Ponto principal com degradê azul/ciano da foto)
            ctx.save();
            const mainGlowRadius = 240 + Math.min(mouseSpeed * 3, 90);
            const mainGlow = ctx.createRadialGradient(
                currentMouseX, currentMouseY, 0,
                currentMouseX, currentMouseY, mainGlowRadius
            );

            if (isDark) {
                mainGlow.addColorStop(0, 'rgba(122, 216, 238, 0.48)');
                mainGlow.addColorStop(0.35, 'rgba(30, 115, 168, 0.25)');
                mainGlow.addColorStop(0.7, 'rgba(15, 56, 86, 0.10)');
                mainGlow.addColorStop(1, 'rgba(15, 56, 86, 0)');
            } else {
                mainGlow.addColorStop(0, 'rgba(56, 185, 220, 0.60)');  // Ciano vibrante
                mainGlow.addColorStop(0.3, 'rgba(30, 115, 168, 0.35)'); // Azul oceano
                mainGlow.addColorStop(0.65, 'rgba(122, 216, 238, 0.16)');
                mainGlow.addColorStop(1, 'rgba(30, 115, 168, 0)');
            }

            ctx.fillStyle = mainGlow;
            ctx.beginPath();
            ctx.arc(currentMouseX, currentMouseY, mainGlowRadius, 0, Math.PI * 2);
            ctx.fill();

            // Ponto de luz central no cursor (brilho branco + ciano)
            const coreRadius = 55 + Math.min(mouseSpeed * 0.6, 20);
            const coreGlow = ctx.createRadialGradient(
                currentMouseX, currentMouseY, 0,
                currentMouseX, currentMouseY, coreRadius
            );
            coreGlow.addColorStop(0, isDark ? 'rgba(215, 248, 255, 0.85)' : 'rgba(255, 255, 255, 0.95)');
            coreGlow.addColorStop(0.45, 'rgba(56, 185, 220, 0.45)');
            coreGlow.addColorStop(1, 'rgba(56, 185, 220, 0)');
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
