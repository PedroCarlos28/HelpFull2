/**
 * HelpFull - Fundo Animado Interativo com Ondas Orgânicas Suaves
 * 
 * - Animação orgânica de fitas fluidas (suave, relaxante e respirando naturalmente).
 * - Cores fiéis:
 *     * Topo da tela/animação MAIS CLARO: tons pérola e azul celeste suave (#f2f8fa, #def0f6).
 *     * Meio: ciano vibrante (#56b9dc, #48cae4) e azul cerúleo oceânico (#1e70a0).
 *     * Final da tela/animação MAIS ESCURO: azul marinho profundo e meia-noite (#0c2b42, #103e5c, #144b6f).
 * - Interatividade:
 *     * Rastro e brilho do mouse ATIVOS APENAS nas duas telas principais (inicio.php e Comeco.php).
 *     * Nas outras telas e blocos de gráficos/atividades: SEM rastro e SEM brilho do mouse (apenas fluxo orgânico calmo).
 * - Acessibilidade:
 *     * Ao ativar o modo "Reduzir animações" (body.acessibilidade-sem-animacao), a animação do fundo para completamente
 *       e as ondas permanecem estáticas e elegantes, sem nenhum movimento.
 */
(function () {
    'use strict';

    // Paleta de Cores do HelpFull (Compartilhada globalmente)
    window.HelpFullPaletaCores = window.HelpFullPaletaCores || {
        'padrao': {
            id: 'padrao',
            nome: 'Azul HelpFull (Original)',
            tipo: 'claro',
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
        'celeste': {
            id: 'celeste',
            nome: 'Azul Celeste',
            tipo: 'claro',
            corBolinha: '#0284c7',
            primaria: '#0284c7',
            hover: '#0369a1',
            ativa: '#075985',
            clara: '#38bdf8',
            suave: 'rgba(2, 132, 199, 0.12)',
            borda: 'rgba(2, 132, 199, 0.28)',
            sombra: 'rgba(2, 132, 199, 0.35)',
            bgGradClaro: ['#f0f9ff', '#e0f2fe', '#7dd3fc', '#0284c7', '#082f49'],
            bgGradEscuro: ['#081e2b', '#05131c', '#02070a'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(186, 230, 253, 0.12)' },
                { color1: 'rgba(125, 211, 252, 0.48)', color2: 'rgba(186, 230, 253, 0.16)' },
                { color1: 'rgba(56, 189, 248, 0.56)', color2: 'rgba(125, 211, 252, 0.20)' },
                { color1: 'rgba(2, 132, 199, 0.65)', color2: 'rgba(56, 189, 248, 0.22)' },
                { color1: 'rgba(3, 105, 161, 0.78)', color2: 'rgba(2, 132, 199, 0.30)' },
                { color1: 'rgba(8, 47, 73, 0.90)', color2: 'rgba(3, 105, 161, 0.45)' }
            ],
            glow: ['rgba(56, 189, 248, 0.44)', 'rgba(2, 132, 199, 0.25)']
        },
        'esmeralda': {
            id: 'esmeralda',
            nome: 'Verde Esmeralda',
            tipo: 'claro',
            corBolinha: '#10b981',
            primaria: '#059669',
            hover: '#047857',
            ativa: '#065f46',
            clara: '#34d399',
            suave: 'rgba(16, 185, 129, 0.12)',
            borda: 'rgba(16, 185, 129, 0.28)',
            sombra: 'rgba(16, 185, 129, 0.35)',
            bgGradClaro: ['#f0fdf4', '#dcfce7', '#86efac', '#059669', '#064e3b'],
            bgGradEscuro: ['#06231a', '#041812', '#020a07'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(167, 243, 208, 0.12)' },
                { color1: 'rgba(110, 231, 183, 0.48)', color2: 'rgba(167, 243, 208, 0.16)' },
                { color1: 'rgba(52, 211, 153, 0.56)', color2: 'rgba(110, 231, 183, 0.20)' },
                { color1: 'rgba(16, 185, 129, 0.65)', color2: 'rgba(52, 211, 153, 0.22)' },
                { color1: 'rgba(5, 150, 105, 0.78)', color2: 'rgba(16, 185, 129, 0.30)' },
                { color1: 'rgba(6, 78, 59, 0.90)', color2: 'rgba(5, 150, 105, 0.45)' }
            ],
            glow: ['rgba(52, 211, 153, 0.44)', 'rgba(5, 150, 105, 0.25)']
        },
        'menta': {
            id: 'menta',
            nome: 'Verde Menta',
            tipo: 'claro',
            corBolinha: '#22c55e',
            primaria: '#16a34a',
            hover: '#15803d',
            ativa: '#166534',
            clara: '#4ade80',
            suave: 'rgba(34, 197, 94, 0.12)',
            borda: 'rgba(34, 197, 94, 0.28)',
            sombra: 'rgba(34, 197, 94, 0.35)',
            bgGradClaro: ['#f0fdf4', '#dcfce7', '#86efac', '#16a34a', '#14532d'],
            bgGradEscuro: ['#071f11', '#04150b', '#020804'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(187, 247, 208, 0.12)' },
                { color1: 'rgba(134, 239, 172, 0.48)', color2: 'rgba(187, 247, 208, 0.16)' },
                { color1: 'rgba(74, 222, 128, 0.56)', color2: 'rgba(134, 239, 172, 0.20)' },
                { color1: 'rgba(34, 197, 94, 0.65)', color2: 'rgba(74, 222, 128, 0.22)' },
                { color1: 'rgba(22, 163, 74, 0.78)', color2: 'rgba(34, 197, 94, 0.30)' },
                { color1: 'rgba(20, 83, 45, 0.90)', color2: 'rgba(22, 163, 74, 0.45)' }
            ],
            glow: ['rgba(74, 222, 128, 0.44)', 'rgba(22, 163, 74, 0.25)']
        },
        'violeta': {
            id: 'violeta',
            nome: 'Roxo Violeta',
            tipo: 'claro',
            corBolinha: '#8b5cf6',
            primaria: '#7c3aed',
            hover: '#6d28d9',
            ativa: '#5b21b6',
            clara: '#a78bfa',
            suave: 'rgba(139, 92, 246, 0.12)',
            borda: 'rgba(139, 92, 246, 0.28)',
            sombra: 'rgba(139, 92, 246, 0.35)',
            bgGradClaro: ['#faf5ff', '#f3e8ff', '#d8b4fe', '#7c3aed', '#3b0764'],
            bgGradEscuro: ['#180e29', '#10091c', '#06030b'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(233, 213, 255, 0.12)' },
                { color1: 'rgba(216, 180, 254, 0.48)', color2: 'rgba(233, 213, 255, 0.16)' },
                { color1: 'rgba(168, 85, 247, 0.56)', color2: 'rgba(216, 180, 254, 0.20)' },
                { color1: 'rgba(124, 58, 237, 0.65)', color2: 'rgba(168, 85, 247, 0.22)' },
                { color1: 'rgba(91, 33, 182, 0.78)', color2: 'rgba(124, 58, 237, 0.30)' },
                { color1: 'rgba(59, 7, 100, 0.90)', color2: 'rgba(91, 33, 182, 0.45)' }
            ],
            glow: ['rgba(168, 85, 247, 0.44)', 'rgba(124, 58, 237, 0.25)']
        },
        'magenta': {
            id: 'magenta',
            nome: 'Rosa Floral',
            tipo: 'claro',
            corBolinha: '#ec4899',
            primaria: '#db2777',
            hover: '#be185d',
            ativa: '#9d174d',
            clara: '#f472b6',
            suave: 'rgba(236, 72, 153, 0.12)',
            borda: 'rgba(236, 72, 153, 0.28)',
            sombra: 'rgba(236, 72, 153, 0.35)',
            bgGradClaro: ['#fdf2f8', '#fce7f3', '#f472b6', '#db2777', '#500724'],
            bgGradEscuro: ['#240713', '#18040d', '#0a0105'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(251, 207, 232, 0.12)' },
                { color1: 'rgba(244, 114, 182, 0.48)', color2: 'rgba(251, 207, 232, 0.16)' },
                { color1: 'rgba(236, 72, 153, 0.56)', color2: 'rgba(244, 114, 182, 0.20)' },
                { color1: 'rgba(219, 39, 119, 0.65)', color2: 'rgba(236, 72, 153, 0.22)' },
                { color1: 'rgba(157, 23, 77, 0.78)', color2: 'rgba(219, 39, 119, 0.30)' },
                { color1: 'rgba(80, 7, 36, 0.90)', color2: 'rgba(157, 23, 77, 0.45)' }
            ],
            glow: ['rgba(244, 114, 182, 0.44)', 'rgba(219, 39, 119, 0.25)']
        },
        'coral': {
            id: 'coral',
            nome: 'Coral Radiante',
            tipo: 'claro',
            corBolinha: '#f97316',
            primaria: '#ea580c',
            hover: '#c2410c',
            ativa: '#9a3412',
            clara: '#fb923c',
            suave: 'rgba(249, 115, 22, 0.12)',
            borda: 'rgba(249, 115, 22, 0.28)',
            sombra: 'rgba(249, 115, 22, 0.35)',
            bgGradClaro: ['#fff7ed', '#ffedd5', '#fdba74', '#ea580c', '#431407'],
            bgGradEscuro: ['#241006', '#180a03', '#090301'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(254, 215, 170, 0.12)' },
                { color1: 'rgba(253, 186, 116, 0.48)', color2: 'rgba(254, 215, 170, 0.16)' },
                { color1: 'rgba(249, 115, 22, 0.56)', color2: 'rgba(253, 186, 116, 0.20)' },
                { color1: 'rgba(234, 88, 12, 0.65)', color2: 'rgba(249, 115, 22, 0.22)' },
                { color1: 'rgba(154, 52, 18, 0.78)', color2: 'rgba(234, 88, 12, 0.30)' },
                { color1: 'rgba(67, 20, 7, 0.90)', color2: 'rgba(154, 52, 18, 0.45)' }
            ],
            glow: ['rgba(249, 115, 22, 0.44)', 'rgba(234, 88, 12, 0.25)']
        },
        'ambar': {
            id: 'ambar',
            nome: 'Âmbar Dourado',
            tipo: 'claro',
            corBolinha: '#f59e0b',
            primaria: '#d97706',
            hover: '#b45309',
            ativa: '#92400e',
            clara: '#fbbf24',
            suave: 'rgba(245, 158, 11, 0.12)',
            borda: 'rgba(245, 158, 11, 0.28)',
            sombra: 'rgba(245, 158, 11, 0.35)',
            bgGradClaro: ['#fffbeb', '#fef3c7', '#fcd34d', '#d97706', '#451a03'],
            bgGradEscuro: ['#211304', '#150c02', '#090501'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(253, 230, 138, 0.12)' },
                { color1: 'rgba(252, 211, 77, 0.48)', color2: 'rgba(253, 230, 138, 0.16)' },
                { color1: 'rgba(245, 158, 11, 0.56)', color2: 'rgba(252, 211, 77, 0.20)' },
                { color1: 'rgba(217, 119, 6, 0.65)', color2: 'rgba(245, 158, 11, 0.22)' },
                { color1: 'rgba(146, 64, 14, 0.78)', color2: 'rgba(217, 119, 6, 0.30)' },
                { color1: 'rgba(69, 26, 3, 0.90)', color2: 'rgba(146, 64, 14, 0.45)' }
            ],
            glow: ['rgba(245, 158, 11, 0.44)', 'rgba(217, 119, 6, 0.25)']
        },
        'safira': {
            id: 'safira',
            nome: 'Azul Meia-Noite',
            tipo: 'escuro',
            corBolinha: '#1e3a8a',
            primaria: '#1e3a8a',
            hover: '#172554',
            ativa: '#0f172a',
            clara: '#3b82f6',
            suave: 'rgba(30, 58, 138, 0.16)',
            borda: 'rgba(30, 58, 138, 0.32)',
            sombra: 'rgba(30, 58, 138, 0.40)',
            bgGradClaro: ['#eef2ff', '#e0e7ff', '#60a5fa', '#1e3a8a', '#0a1024'],
            bgGradEscuro: ['#0a1126', '#060a17', '#03050d'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(191, 219, 254, 0.12)' },
                { color1: 'rgba(96, 165, 250, 0.48)', color2: 'rgba(191, 219, 254, 0.16)' },
                { color1: 'rgba(59, 130, 246, 0.56)', color2: 'rgba(96, 165, 250, 0.20)' },
                { color1: 'rgba(30, 58, 138, 0.75)', color2: 'rgba(59, 130, 246, 0.25)' },
                { color1: 'rgba(23, 37, 84, 0.85)', color2: 'rgba(30, 58, 138, 0.35)' },
                { color1: 'rgba(10, 16, 36, 0.95)', color2: 'rgba(23, 37, 84, 0.50)' }
            ],
            glow: ['rgba(59, 130, 246, 0.44)', 'rgba(30, 58, 138, 0.30)']
        },
        'floresta': {
            id: 'floresta',
            nome: 'Verde Floresta',
            tipo: 'escuro',
            corBolinha: '#14532d',
            primaria: '#14532d',
            hover: '#052e16',
            ativa: '#022c22',
            clara: '#16a34a',
            suave: 'rgba(20, 83, 45, 0.16)',
            borda: 'rgba(20, 83, 45, 0.32)',
            sombra: 'rgba(20, 83, 45, 0.40)',
            bgGradClaro: ['#f0fdf4', '#dcfce7', '#4ade80', '#14532d', '#052010'],
            bgGradEscuro: ['#071c10', '#04120a', '#020704'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(187, 247, 208, 0.12)' },
                { color1: 'rgba(74, 222, 128, 0.48)', color2: 'rgba(187, 247, 208, 0.16)' },
                { color1: 'rgba(22, 163, 74, 0.58)', color2: 'rgba(74, 222, 128, 0.20)' },
                { color1: 'rgba(20, 83, 45, 0.75)', color2: 'rgba(22, 163, 74, 0.25)' },
                { color1: 'rgba(5, 46, 22, 0.85)', color2: 'rgba(20, 83, 45, 0.35)' },
                { color1: 'rgba(3, 26, 12, 0.95)', color2: 'rgba(5, 46, 22, 0.50)' }
            ],
            glow: ['rgba(34, 197, 94, 0.44)', 'rgba(20, 83, 45, 0.30)']
        },
        'indigo': {
            id: 'indigo',
            nome: 'Índigo Profundo',
            tipo: 'escuro',
            corBolinha: '#312e81',
            primaria: '#312e81',
            hover: '#1e1b4b',
            ativa: '#18181b',
            clara: '#6366f1',
            suave: 'rgba(49, 46, 129, 0.16)',
            borda: 'rgba(49, 46, 129, 0.32)',
            sombra: 'rgba(49, 46, 129, 0.40)',
            bgGradClaro: ['#eef2ff', '#e0e7ff', '#818cf8', '#312e81', '#0d0b26'],
            bgGradEscuro: ['#100d2b', '#0a081a', '#04030d'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(199, 210, 254, 0.12)' },
                { color1: 'rgba(129, 140, 248, 0.48)', color2: 'rgba(199, 210, 254, 0.16)' },
                { color1: 'rgba(99, 102, 241, 0.58)', color2: 'rgba(129, 140, 248, 0.20)' },
                { color1: 'rgba(49, 46, 129, 0.75)', color2: 'rgba(99, 102, 241, 0.25)' },
                { color1: 'rgba(30, 27, 75, 0.85)', color2: 'rgba(49, 46, 129, 0.35)' },
                { color1: 'rgba(13, 11, 38, 0.95)', color2: 'rgba(30, 27, 75, 0.50)' }
            ],
            glow: ['rgba(99, 102, 241, 0.44)', 'rgba(49, 46, 129, 0.30)']
        },
        'ameixa': {
            id: 'ameixa',
            nome: 'Ameixa Imperial',
            tipo: 'escuro',
            corBolinha: '#581c87',
            primaria: '#581c87',
            hover: '#3b0764',
            ativa: '#2e1065',
            clara: '#9333ea',
            suave: 'rgba(88, 28, 135, 0.16)',
            borda: 'rgba(88, 28, 135, 0.32)',
            sombra: 'rgba(88, 28, 135, 0.40)',
            bgGradClaro: ['#faf5ff', '#f3e8ff', '#c084fc', '#581c87', '#1a052e'],
            bgGradEscuro: ['#1b072e', '#11041c', '#07010a'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(233, 213, 255, 0.12)' },
                { color1: 'rgba(192, 132, 252, 0.48)', color2: 'rgba(233, 213, 255, 0.16)' },
                { color1: 'rgba(147, 51, 234, 0.58)', color2: 'rgba(192, 132, 252, 0.20)' },
                { color1: 'rgba(88, 28, 135, 0.75)', color2: 'rgba(147, 51, 234, 0.25)' },
                { color1: 'rgba(59, 7, 100, 0.85)', color2: 'rgba(88, 28, 135, 0.35)' },
                { color1: 'rgba(26, 5, 46, 0.95)', color2: 'rgba(59, 7, 100, 0.50)' }
            ],
            glow: ['rgba(147, 51, 234, 0.44)', 'rgba(88, 28, 135, 0.30)']
        },
        'vinho': {
            id: 'vinho',
            nome: 'Vinho Carmim',
            tipo: 'escuro',
            corBolinha: '#881337',
            primaria: '#881337',
            hover: '#4c0519',
            ativa: '#3b0713',
            clara: '#e11d48',
            suave: 'rgba(136, 19, 55, 0.16)',
            borda: 'rgba(136, 19, 55, 0.32)',
            sombra: 'rgba(136, 19, 55, 0.40)',
            bgGradClaro: ['#fff1f2', '#ffe4e6', '#fb7185', '#881337', '#2e0410'],
            bgGradEscuro: ['#29040e', '#190208', '#0a0104'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(254, 205, 211, 0.12)' },
                { color1: 'rgba(251, 113, 133, 0.48)', color2: 'rgba(254, 205, 211, 0.16)' },
                { color1: 'rgba(225, 29, 72, 0.58)', color2: 'rgba(251, 113, 133, 0.20)' },
                { color1: 'rgba(136, 19, 55, 0.75)', color2: 'rgba(225, 29, 72, 0.25)' },
                { color1: 'rgba(76, 5, 25, 0.85)', color2: 'rgba(136, 19, 55, 0.35)' },
                { color1: 'rgba(46, 4, 16, 0.95)', color2: 'rgba(76, 5, 25, 0.50)' }
            ],
            glow: ['rgba(225, 29, 72, 0.44)', 'rgba(136, 19, 55, 0.30)']
        },
        'petroleo': {
            id: 'petroleo',
            nome: 'Petróleo Oceânico',
            tipo: 'escuro',
            corBolinha: '#134e4a',
            primaria: '#134e4a',
            hover: '#042f2e',
            ativa: '#022c22',
            clara: '#0d9488',
            suave: 'rgba(19, 78, 74, 0.16)',
            borda: 'rgba(19, 78, 74, 0.32)',
            sombra: 'rgba(19, 78, 74, 0.40)',
            bgGradClaro: ['#f0fdfa', '#ccfbf1', '#5eead4', '#134e4a', '#042220'],
            bgGradEscuro: ['#072422', '#041716', '#020a09'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(153, 246, 228, 0.12)' },
                { color1: 'rgba(94, 234, 212, 0.48)', color2: 'rgba(153, 246, 228, 0.16)' },
                { color1: 'rgba(20, 184, 166, 0.58)', color2: 'rgba(94, 234, 212, 0.20)' },
                { color1: 'rgba(19, 78, 74, 0.75)', color2: 'rgba(20, 184, 166, 0.25)' },
                { color1: 'rgba(4, 47, 46, 0.85)', color2: 'rgba(19, 78, 74, 0.35)' },
                { color1: 'rgba(4, 34, 32, 0.95)', color2: 'rgba(4, 47, 46, 0.50)' }
            ],
            glow: ['rgba(20, 184, 166, 0.44)', 'rgba(19, 78, 74, 0.30)']
        },
        'grafite': {
            id: 'grafite',
            nome: 'Grafite Chumbo',
            tipo: 'escuro',
            corBolinha: '#334155',
            primaria: '#334155',
            hover: '#1e293b',
            ativa: '#0f172a',
            clara: '#64748b',
            suave: 'rgba(51, 65, 85, 0.16)',
            borda: 'rgba(51, 65, 85, 0.32)',
            sombra: 'rgba(51, 65, 85, 0.40)',
            bgGradClaro: ['#f8fafc', '#f1f5f9', '#94a3b8', '#334155', '#0f172a'],
            bgGradEscuro: ['#141923', '#0e1118', '#07090d'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(226, 232, 240, 0.12)' },
                { color1: 'rgba(148, 163, 184, 0.48)', color2: 'rgba(226, 232, 240, 0.16)' },
                { color1: 'rgba(100, 116, 139, 0.58)', color2: 'rgba(148, 163, 184, 0.20)' },
                { color1: 'rgba(51, 65, 85, 0.75)', color2: 'rgba(100, 116, 139, 0.25)' },
                { color1: 'rgba(30, 41, 59, 0.85)', color2: 'rgba(51, 65, 85, 0.35)' },
                { color1: 'rgba(15, 23, 42, 0.95)', color2: 'rgba(30, 41, 59, 0.50)' }
            ],
            glow: ['rgba(148, 163, 184, 0.44)', 'rgba(51, 65, 85, 0.30)']
        },
        'bronze': {
            id: 'bronze',
            nome: 'Bronze Terroso',
            tipo: 'escuro',
            corBolinha: '#78350f',
            primaria: '#78350f',
            hover: '#451a03',
            ativa: '#291102',
            clara: '#b45309',
            suave: 'rgba(120, 53, 15, 0.16)',
            borda: 'rgba(120, 53, 15, 0.32)',
            sombra: 'rgba(120, 53, 15, 0.40)',
            bgGradClaro: ['#fffbeb', '#fef3c7', '#fde68a', '#78350f', '#240f04'],
            bgGradEscuro: ['#211105', '#160b03', '#090401'],
            ribbons: [
                { color1: 'rgba(255, 255, 255, 0.45)', color2: 'rgba(254, 243, 199, 0.12)' },
                { color1: 'rgba(253, 230, 138, 0.48)', color2: 'rgba(254, 243, 199, 0.16)' },
                { color1: 'rgba(217, 119, 6, 0.58)', color2: 'rgba(253, 230, 138, 0.20)' },
                { color1: 'rgba(120, 53, 15, 0.75)', color2: 'rgba(217, 119, 6, 0.25)' },
                { color1: 'rgba(69, 26, 3, 0.85)', color2: 'rgba(120, 53, 15, 0.35)' },
                { color1: 'rgba(36, 15, 4, 0.95)', color2: 'rgba(69, 26, 3, 0.50)' }
            ],
            glow: ['rgba(217, 119, 6, 0.44)', 'rgba(120, 15, 4, 0.30)']
        }
    };

    function initFundoAnimadoInstancia(canvas) {
        if (!canvas || canvas._fundoAnimadoInit) return;
        canvas._fundoAnimadoInit = true;

        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        const isBlock = canvas.classList.contains('fundo-animado-bloco') || canvas.getAttribute('data-container') === 'true';

        // O rastro e o brilho do mouse ficam restritos estritamente às duas telas (inicio.php e Comeco.php)
        const pathName = window.location.pathname.toLowerCase();
        const isEligibleScreen = pathName.includes('inicio') || pathName.includes('comeco');
        const explicitlyDisabled = canvas.getAttribute('data-mouse') === 'false' || canvas.classList.contains('sem-mouse');
        const explicitlyEnabled = canvas.getAttribute('data-mouse') === 'true';

        const hasMouse = (isEligibleScreen || explicitlyEnabled) && !explicitlyDisabled && !isBlock;

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

        let animFrameId = null;
        let startTime = performance.now();

        function checarReducaoAnimacao() {
            return document.body.classList.contains('acessibilidade-sem-animacao') ||
                   (document.documentElement && document.documentElement.classList.contains('acessibilidade-sem-animacao')) ||
                   (window.localStorage && window.localStorage.getItem('helpfull_semAnimacao') === 'true') ||
                   (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
        }

        function redimensionar() {
            if (isBlock && canvas.parentElement) {
                const rect = canvas.parentElement.getBoundingClientRect();
                width = Math.max(rect.width, 100);
                height = Math.max(rect.height, 80);
            } else {
                width = window.innerWidth;
                height = window.innerHeight;
            }
            canvas.width = Math.floor(width * dpr);
            canvas.height = Math.floor(height * dpr);
            ctx.scale(dpr, dpr);

            if (checarReducaoAnimacao()) {
                desenharQuadro(1200);
            }
        }

        window.addEventListener('resize', redimensionar, { passive: true });
        if (isBlock && window.ResizeObserver && canvas.parentElement) {
            const ro = new ResizeObserver(redimensionar);
            ro.observe(canvas.parentElement);
        }
        redimensionar();

        // Eventos de movimento (apenas se hasMouse = true)
        if (hasMouse) {
            window.addEventListener('mousemove', function (e) {
                if (checarReducaoAnimacao()) return;
                mouseX = e.clientX;
                mouseY = e.clientY;
                mouseMoved = true;
            }, { passive: true });

            window.addEventListener('touchmove', function (e) {
                if (checarReducaoAnimacao()) return;
                if (e.touches && e.touches.length > 0) {
                    mouseX = e.touches[0].clientX;
                    mouseY = e.touches[0].clientY;
                    mouseMoved = true;
                }
            }, { passive: true });
        }

        /**
         * Fitas e ondas orgânicas com progressão de luminosidade:
         * Topo claro -> Centro vibrante -> Base/final bem escuro
         */
        const ribbons = [
            // 0. Brilho no topo mais alto (claridade máxima no topo)
            {
                baseY: 0.12,
                amp: isBlock ? 35 : 55,
                freq: 0.0019,
                speed: 0.00048,
                color1: 'rgba(255, 255, 255, 0.45)',
                color2: 'rgba(180, 235, 248, 0.12)',
                phase: 0.5,
                thickness: isBlock ? 140 : 220
            },
            // 1. Fita superior suave (azul celeste claro)
            {
                baseY: 0.22,
                amp: isBlock ? 45 : 72,
                freq: 0.0017,
                speed: 0.00042,
                color1: 'rgba(114, 210, 232, 0.48)',
                color2: 'rgba(180, 238, 248, 0.16)',
                phase: 0,
                thickness: isBlock ? 180 : 270
            },
            // 2. Fita superior-média (ciano vibrante)
            {
                baseY: 0.38,
                amp: isBlock ? 55 : 92,
                freq: 0.0014,
                speed: 0.00035,
                color1: 'rgba(56, 185, 220, 0.56)',
                color2: 'rgba(114, 210, 232, 0.20)',
                phase: 2.1,
                thickness: isBlock ? 210 : 315
            },
            // 3. Fita central (azul cerúleo / oceano)
            {
                baseY: 0.54,
                amp: isBlock ? 52 : 88,
                freq: 0.0015,
                speed: 0.00040,
                color1: 'rgba(30, 112, 160, 0.65)',
                color2: 'rgba(56, 185, 220, 0.22)',
                phase: 4.3,
                thickness: isBlock ? 230 : 340
            },
            // 4. Fita inferior (azul marinho escuro)
            {
                baseY: 0.72,
                amp: isBlock ? 58 : 98,
                freq: 0.0011,
                speed: 0.00030,
                color1: 'rgba(20, 75, 111, 0.78)',
                color2: 'rgba(30, 112, 160, 0.30)',
                phase: 1.2,
                thickness: isBlock ? 250 : 370
            },
            // 5. Fita mais escura no final (profundidade máxima na base)
            {
                baseY: 0.86,
                amp: isBlock ? 65 : 110,
                freq: 0.0009,
                speed: 0.00025,
                color1: 'rgba(12, 43, 66, 0.90)',
                color2: 'rgba(20, 75, 111, 0.45)',
                phase: 3.5,
                thickness: isBlock ? 280 : 410
            }
        ];

        function desenharQuadro(time) {
            const isReduced = checarReducaoAnimacao();

            if (hasMouse && !isReduced) {
                const moveDelta = Math.hypot(mouseX - prevX, mouseY - prevY);
                mouseSpeed += (moveDelta - mouseSpeed) * 0.1;
                prevX = mouseX;
                prevY = mouseY;

                currentMouseX += (mouseX - currentMouseX) * 0.06;
                currentMouseY += (mouseY - currentMouseY) * 0.06;

                // Flutuação automática se inativo
                if (!mouseMoved) {
                    mouseX = width * 0.5 + Math.cos(time * 0.0007) * (width * 0.25);
                    mouseY = height * 0.38 + Math.sin(time * 0.0010) * (height * 0.18);
                }
            }

            const isDark = document.body.classList.contains('acessibilidade-escuro');
            const isContrast = document.body.classList.contains('acessibilidade-contraste');

            let corId = 'padrao';
            try {
                corId = window.localStorage ? (window.localStorage.getItem('helpfull_cor_tema') || 'padrao') : 'padrao';
            } catch (e) {}
            const paleta = (window.HelpFullPaletaCores && window.HelpFullPaletaCores[corId]) ? window.HelpFullPaletaCores[corId] : (window.HelpFullPaletaCores ? window.HelpFullPaletaCores['padrao'] : null);

            ctx.clearRect(0, 0, width, height);

            // 1. Fundo Gradiente: MAIS CLARO NO TOPO e MAIS ESCURO NO FINAL DA PÁGINA
            if (isDark) {
                const bgGrad = ctx.createLinearGradient(0, 0, 0, height);
                if (paleta && paleta.bgGradEscuro) {
                    bgGrad.addColorStop(0, paleta.bgGradEscuro[0]);
                    bgGrad.addColorStop(0.5, paleta.bgGradEscuro[1]);
                    bgGrad.addColorStop(1, paleta.bgGradEscuro[2]);
                } else {
                    bgGrad.addColorStop(0, '#15212a');
                    bgGrad.addColorStop(0.5, '#0e1820');
                    bgGrad.addColorStop(1, '#050a0e');
                }
                ctx.fillStyle = bgGrad;
            } else if (isContrast) {
                ctx.fillStyle = '#06080a';
            } else {
                const bgGrad = ctx.createLinearGradient(0, 0, 0, height);
                if (paleta && paleta.bgGradClaro) {
                    bgGrad.addColorStop(0, paleta.bgGradClaro[0]);
                    bgGrad.addColorStop(0.25, paleta.bgGradClaro[1]);
                    bgGrad.addColorStop(0.55, paleta.bgGradClaro[2]);
                    bgGrad.addColorStop(0.80, paleta.bgGradClaro[3]);
                    bgGrad.addColorStop(1, paleta.bgGradClaro[4]);
                } else {
                    bgGrad.addColorStop(0, '#f2f8fa');
                    bgGrad.addColorStop(0.25, '#def0f6');
                    bgGrad.addColorStop(0.55, '#86d1e4');
                    bgGrad.addColorStop(0.80, '#2178a2');
                    bgGrad.addColorStop(1, '#0c2b42');
                }
                ctx.fillStyle = bgGrad;
            }
            ctx.fillRect(0, 0, width, height);

            // 2. Renderização das fitas/ondas orgânicas
            ribbons.forEach((ribbon, rIdx) => {
                ctx.save();

                const yBase = height * ribbon.baseY;
                const waveTime = time * ribbon.speed + ribbon.phase;

                const rColor1 = (paleta && paleta.ribbons && paleta.ribbons[rIdx]) ? paleta.ribbons[rIdx].color1 : ribbon.color1;
                const rColor2 = (paleta && paleta.ribbons && paleta.ribbons[rIdx]) ? paleta.ribbons[rIdx].color2 : ribbon.color2;

                const grad = ctx.createLinearGradient(0, yBase - ribbon.amp, 0, yBase + ribbon.thickness);
                if (isDark) {
                    grad.addColorStop(0, rColor1.replace(/[\d\.]+\)$/, '0.35)'));
                    grad.addColorStop(1, rColor2.replace(/[\d\.]+\)$/, '0.05)'));
                } else {
                    grad.addColorStop(0, rColor1);
                    grad.addColorStop(1, rColor2);
                }

                ctx.fillStyle = grad;
                ctx.beginPath();
                ctx.moveTo(-120, height + 120);
                ctx.lineTo(-120, yBase);

                const step = isBlock ? 25 : 35;
                for (let x = -120; x <= width + 120; x += step) {
                    const h1 = Math.sin(x * ribbon.freq + waveTime) * ribbon.amp;
                    const h2 = Math.cos(x * ribbon.freq * 1.6 - waveTime * 0.8) * (ribbon.amp * 0.38);

                    let mouseDistort = 0;
                    if (hasMouse && !isReduced) {
                        const dx = x - currentMouseX;
                        const distToMouseX = Math.abs(dx);
                        if (distToMouseX < 380) {
                            const factor = 1 - (distToMouseX / 380);
                            const dy = yBase - currentMouseY;
                            mouseDistort = Math.sin(factor * Math.PI) * (dy * 0.16);
                        }
                    }

                    const y = yBase + h1 + h2 + mouseDistort;
                    ctx.lineTo(x, y);
                }

                ctx.lineTo(width + 120, height + 120);
                ctx.closePath();
                ctx.fill();

                ctx.restore();
            });

            // 3. Brilho azul do mouse (APENAS se hasMouse = true e NÃO estiver em modo sem animação)
            if (hasMouse && !isReduced) {
                ctx.save();

                const speedBoost = Math.min(mouseSpeed * 0.006, 0.22);
                const baseRadius = Math.max(width, height) * 0.34;
                const glowRadius = baseRadius * (1 + speedBoost);

                const glowGrad = ctx.createRadialGradient(
                    currentMouseX, currentMouseY, 0,
                    currentMouseX, currentMouseY, glowRadius
                );

                const glowC1 = (paleta && paleta.glow) ? paleta.glow[0] : (isDark ? 'rgba(72, 202, 228, 0.44)' : 'rgba(56, 185, 220, 0.58)');
                const glowC2 = (paleta && paleta.glow) ? paleta.glow[1] : (isDark ? 'rgba(30, 112, 160, 0.25)' : 'rgba(30, 112, 160, 0.34)');

                if (isDark) {
                    glowGrad.addColorStop(0, glowC1);
                    glowGrad.addColorStop(0.3, glowC2);
                    glowGrad.addColorStop(0.65, glowC2.replace(/[\d\.]+\)$/, '0.10)'));
                    glowGrad.addColorStop(1, glowC2.replace(/[\d\.]+\)$/, '0)'));
                } else {
                    glowGrad.addColorStop(0, glowC1);
                    glowGrad.addColorStop(0.28, glowC2);
                    glowGrad.addColorStop(0.65, glowC1.replace(/[\d\.]+\)$/, '0.16)'));
                    glowGrad.addColorStop(1, glowC2.replace(/[\d\.]+\)$/, '0)'));
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
                coreGlow.addColorStop(0, isDark ? 'rgba(255, 255, 255, 0.50)' : 'rgba(255, 255, 255, 0.60)');
                coreGlow.addColorStop(0.45, glowC1.replace(/[\d\.]+\)$/, '0.28)'));
                coreGlow.addColorStop(1, glowC1.replace(/[\d\.]+\)$/, '0)'));

                ctx.fillStyle = coreGlow;
                ctx.beginPath();
                ctx.arc(currentMouseX, currentMouseY, coreRadius, 0, Math.PI * 2);
                ctx.fill();

                ctx.restore();
            }
        }

        function animar(timestamp) {
            if (checarReducaoAnimacao()) {
                // Modo menos animações: renderiza uma única vez a onda estática e para
                desenharQuadro(1200);
                animFrameId = null;
                return;
            }

            const time = timestamp - startTime;
            desenharQuadro(time);
            animFrameId = requestAnimationFrame(animar);
        }

        function verificarModoAnimacao() {
            if (checarReducaoAnimacao()) {
                if (animFrameId) {
                    cancelAnimationFrame(animFrameId);
                    animFrameId = null;
                }
                desenharQuadro(1200); // Fica estático na posição da onda
            } else {
                if (!animFrameId) {
                    startTime = performance.now() - 1200;
                    animFrameId = requestAnimationFrame(animar);
                }
            }
        }

        // Observa alterações no body e html (ex: ao ativar ou desativar o switch de menos animações)
        if (window.MutationObserver) {
            const observer = new MutationObserver(verificarModoAnimacao);
            observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
            if (document.documentElement) {
                observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
            }
        }
        window.addEventListener('storage', function (e) {
            if (!e || e.key === 'helpfull_semAnimacao' || e.key === null) {
                verificarModoAnimacao();
            }
            if (!e || e.key === 'helpfull_cor_tema') {
                if (checarReducaoAnimacao()) {
                    desenharQuadro(1200);
                }
            }
        });
        window.addEventListener('helpfull-acessibilidade-mudou', verificarModoAnimacao);
        window.addEventListener('helpfull-cor-tema-mudou', function () {
            if (checarReducaoAnimacao()) {
                desenharQuadro(1200);
            }
        });
        if (window.matchMedia) {
            const mq = window.matchMedia('(prefers-reduced-motion: reduce)');
            if (mq.addEventListener) {
                mq.addEventListener('change', verificarModoAnimacao);
            }
        }

        verificarModoAnimacao();
    }

    function initFundoAnimado() {
        const canvs = document.querySelectorAll('.fundo-animado-canvas');
        if (canvs.length > 0) {
            canvs.forEach(initFundoAnimadoInstancia);
        } else {
            let canvas = document.getElementById('fundoAnimadoCanvas');
            if (!canvas) {
                canvas = document.createElement('canvas');
                canvas.id = 'fundoAnimadoCanvas';
                canvas.className = 'fundo-animado-canvas';
                document.body.insertBefore(canvas, document.body.firstChild);
            }
            initFundoAnimadoInstancia(canvas);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFundoAnimado);
    } else {
        initFundoAnimado();
    }
})();
