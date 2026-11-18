<?php
require_once 'conexao.php';
limparSessaoUsuario();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="1;url=Comeco.php?logout=1">
    <title>Saindo do HelpFull...</title>
    <style>
        body {
            margin: 0;
            background: #f0f0f0;
            font-family: sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
    </style>
    <script>
        (function() {
            var chaves = [
                'helpfull_cor_tema',
                'helpfull_idioma',
                'helpfull_tema_modo',
                'helpfull_escuro',
                'helpfull_contraste',
                'helpfull_textoGrande',
                'helpfull_sublinhar',
                'helpfull_semAnimacao',
                'helpfull_libras',
                'helpfull_reduzirTransparencia'
            ];
            chaves.forEach(function(k) {
                try { localStorage.removeItem(k); } catch (e) {}
            });
            try {
                if (document.documentElement) document.documentElement.className = '';
                if (document.body) document.body.className = '';
            } catch (e) {}
            window.location.replace('Comeco.php?logout=1');
        })();
    </script>
</head>
<body>
</body>
</html>
