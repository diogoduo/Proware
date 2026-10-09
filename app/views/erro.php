<?php
/**
 * Página mostrada quando algo dá errado no servidor.
 * Não usa o banco nem o cabeçalho da loja, porque o problema pode estar neles.
 * Recebe $detalhe: uma explicação para erros de configuração, ou null.
 */
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Algo deu errado | Proware</title>
    <?php if (defined('NA_AREA_ADMIN')): ?><base href="../"><?php endif; ?>
    <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="<?= asset('assets/css/estilo.css') ?>">
</head>
<body>
<main class="secao">
    <div class="container estado-vazio">
        <span class="estado-vazio__icone"><?= icone('alerta') ?></span>
        <h1>Algo deu errado do nosso lado</h1>
        <?php if (!empty($detalhe)): ?>
            <p><?= e($detalhe) ?></p>
        <?php else: ?>
            <p>Tente de novo em alguns instantes. Se o problema continuar, fale com a gente.</p>
        <?php endif; ?>
        <div class="estado-vazio__acoes">
            <a class="botao botao--primario" href="index.php">Voltar para o início</a>
        </div>
    </div>
</main>
</body>
</html>
