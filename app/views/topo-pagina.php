<?php
/** Faixa de título das páginas internas. Recebe $titulo, $subtitulo e $migalhas (rótulo => link). */
$migalhas = $migalhas ?? [];
?>
<section class="topo-pagina">
    <div class="container">
        <nav class="migalhas" aria-label="Você está em">
            <ol>
                <li><a href="index.php">Início</a></li>
                <?php foreach ($migalhas as $rotulo => $link): ?>
                    <li><a href="<?= e($link) ?>"><?= e($rotulo) ?></a></li>
                <?php endforeach; ?>
                <li aria-current="page"><?= e($titulo) ?></li>
            </ol>
        </nav>
        <h1><?= e($titulo) ?></h1>
        <?php if (!empty($subtitulo)): ?>
            <p><?= e($subtitulo) ?></p>
        <?php endif; ?>
    </div>
</section>
