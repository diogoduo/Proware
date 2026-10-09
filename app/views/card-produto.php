<?php
/** Card de produto usado nas vitrines. Recebe $produto. */
$link = 'produto.php?id=' . $produto['id'];
$desconto = porcentagem_desconto($produto);
$ehPc = $produto['tipo'] === 'pc';
$icones = ['cpu' => 'cpu', 'gpu' => 'gpu', 'memoria' => 'memoria', 'ssd' => 'ssd'];
?>
<article class="card-produto" data-uso="<?= e($produto['uso'] ?? '') ?>" data-preco="<?= e($produto['preco']) ?>">
    <a class="card-produto__imagem" href="<?= e($link) ?>" tabindex="-1" aria-hidden="true">
        <img src="assets/img/produtos/<?= e($produto['imagem']) ?>" alt="" loading="lazy" width="320" height="320">
    </a>
    <?php if ($desconto): ?>
        <span class="selo selo--oferta"><?= $desconto ?>% OFF</span>
    <?php endif; ?>

    <div class="card-produto__corpo">
        <p class="card-produto__categoria"><?= e($produto['categoria']) ?></p>
        <h3 class="card-produto__nome"><a href="<?= e($link) ?>"><?= e($produto['nome']) ?></a></h3>

        <?php if ($ehPc): ?>
            <ul class="card-produto__specs">
                <?php foreach ($icones as $categoria => $icone): ?>
                    <li><?= icone($icone) ?> <?= e(componente($produto['pecas'][$categoria])['nome']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="card-produto__resumo"><?= e($produto['resumo']) ?></p>
        <?php endif; ?>

        <div class="precos">
            <?php if (!empty($produto['preco_original'])): ?>
                <s class="precos__de">de <?= brl((float) $produto['preco_original']) ?></s>
            <?php endif; ?>
            <p class="precos__pix"><strong><?= brl(preco_pix((float) $produto['preco'])) ?></strong> no Pix</p>
            <p class="precos__parcelado">ou <?= brl((float) $produto['preco']) ?> em <?= PARCELAS ?>x de <?= brl(valor_parcela((float) $produto['preco'])) ?></p>
        </div>

        <div class="card-produto__acoes">
            <button class="botao botao--primario" type="button" data-adicionar="<?= e($produto['id']) ?>">
                <?= icone('carrinho') ?> Comprar
            </button>
            <?php if ($ehPc): ?>
                <a class="botao botao--contorno" href="montar.php?pc=<?= e($produto['id']) ?>" title="Abrir no Monte seu PC">Personalizar</a>
            <?php else: ?>
                <a class="botao botao--contorno" href="<?= e($link) ?>">Detalhes</a>
            <?php endif; ?>
        </div>
    </div>
</article>
