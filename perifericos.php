<?php
require __DIR__ . '/app/bootstrap.php';

$titulo = 'Periféricos';
$subtitulo = 'Monitor, teclado e mouse selecionados pela equipe para completar o seu setup.';
$descricao = 'Monitor gamer ultrawide, teclado mecânico ABNT2 e mouse RGB com preço de oferta e frete grátis.';
$pagina = 'perifericos';

require APP . '/views/topo.php';
parcial('topo-pagina', compact('titulo', 'subtitulo'));
?>

<section class="secao secao--compacta">
    <div class="container">
        <div class="grade-produtos">
            <?php foreach (produtos_do_tipo('periferico') as $produto): ?>
                <?php parcial('card-produto', ['produto' => $produto]); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="secao secao--compacta">
    <div class="container">
        <div class="faixa-chamada">
            <span class="faixa-chamada__icone"><?= icone('gabinete') ?></span>
            <div>
                <h2>Ainda falta o computador?</h2>
                <p>Monte o seu peça por peça ou escolha um dos nossos PCs prontos.</p>
            </div>
            <a class="botao botao--primario" href="montar.php">Monte seu PC <?= icone('seta') ?></a>
        </div>
    </div>
</section>

<?php parcial('beneficios'); ?>

<?php require APP . '/views/rodape.php'; ?>
