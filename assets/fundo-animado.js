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

