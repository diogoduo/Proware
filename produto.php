<?php
require __DIR__ . '/app/bootstrap.php';

$produto = produto(get('id'));
if (!$produto) {
    require __DIR__ . '/404.php';
    exit;
}

$ehPc = $produto['tipo'] === 'pc';
$titulo = $produto['nome'];
$descricao = $produto['resumo'];
$pagina = $ehPc ? 'pcs' : 'perifericos';
$desconto = porcentagem_desconto($produto);
$preco = (float) $produto['preco'];
$especificacoes = $ehPc ? especificacoes_pc($produto) : $produto['especificacoes'];

$relacionados = array_filter(produtos_do_tipo($produto['tipo']), fn ($p) => $p['id'] !== $produto['id']);
$relacionados = array_slice($relacionados, 0, 3);

require APP . '/views/topo.php';
?>

<section class="secao secao--compacta">
    <div class="container">
        <nav class="migalhas migalhas--claro" aria-label="Você está em">
            <ol>
                <li><a href="index.php">Início</a></li>
                <li><a href="<?= $ehPc ? 'pcs-prontos.php' : 'perifericos.php' ?>"><?= $ehPc ? 'PCs Prontos' : 'Periféricos' ?></a></li>
                <li aria-current="page"><?= e($produto['nome']) ?></li>
            </ol>
        </nav>

        <div class="produto">
            <div class="produto__galeria">
                <img src="assets/img/produtos/<?= e($produto['imagem']) ?>" alt="<?= e($produto['nome']) ?>" width="560" height="560">
                <?php if ($desconto): ?>
                    <span class="selo selo--oferta selo--grande"><?= $desconto ?>% OFF</span>
                <?php endif; ?>
            </div>

            <div class="produto__info">
                <p class="sobretitulo"><?= e($produto['categoria']) ?></p>
                <h1 class="produto__nome"><?= e($produto['nome']) ?></h1>
                <p class="produto__resumo"><?= e($produto['resumo']) ?></p>

                <?php if ($ehPc): ?>
                    <ul class="produto__destaques">
                        <?php foreach (['cpu', 'gpu', 'memoria', 'ssd'] as $categoria): ?>
                            <li>
                                <span class="produto__destaques-icone"><?= icone(categorias()[$categoria]['icone']) ?></span>
                                <span><small><?= e(categorias()[$categoria]['nome']) ?></small><?= e(componente($produto['pecas'][$categoria])['nome']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <div class="caixa-preco">
                    <?php if (!empty($produto['preco_original'])): ?>
                        <s class="precos__de">de <?= brl((float) $produto['preco_original']) ?></s>
                    <?php endif; ?>
                    <p class="caixa-preco__pix"><strong><?= brl(preco_pix($preco)) ?></strong> <span>à vista no Pix</span></p>
                    <p class="caixa-preco__parcelado">ou <strong><?= brl($preco) ?></strong> em até <?= PARCELAS ?>x de <?= brl(valor_parcela($preco)) ?> sem juros</p>

                    <div class="caixa-preco__acoes">
                        <button class="botao botao--primario botao--grande" type="button" data-adicionar="<?= e($produto['id']) ?>" data-abrir-depois>
                            <?= icone('carrinho') ?> Adicionar ao carrinho
                        </button>
                        <?php if ($ehPc): ?>
                            <a class="botao botao--contorno botao--grande" href="montar.php?pc=<?= e($produto['id']) ?>"><?= icone('ferramenta') ?> Personalizar peças</a>
                        <?php endif; ?>
                    </div>

                    <ul class="garantias">
                        <li><?= icone('caminhao') ?> Frete grátis para todo o Brasil</li>
                        <li><?= icone('escudo') ?> 1 ano de garantia Proware</li>
                        <?php if ($ehPc): ?>
                            <li><?= icone('ferramenta') ?> Montado e testado por 24h antes do envio</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div class="produto__detalhes">
            <h2>Especificações técnicas</h2>
            <table class="tabela-specs">
                <tbody>
                    <?php foreach ($especificacoes as $rotulo => $valor): ?>
                        <tr><th scope="row"><?= e($rotulo) ?></th><td><?= e($valor) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if ($ehPc): ?>
                        <tr><th scope="row">Consumo estimado</th><td>até <?= consumo_estimado($produto['pecas']) ?> W</td></tr>
                        <tr><th scope="row">Sistema</th><td>Sem sistema operacional (instalamos o Windows ou Linux sob consulta)</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php if ($relacionados): ?>
    <section class="secao secao--alternada">
        <div class="container">
            <div class="secao__cabecalho">
                <h2 class="secao__titulo">Você também pode gostar</h2>
            </div>
            <div class="grade-produtos">
                <?php foreach ($relacionados as $item): ?>
                    <?php parcial('card-produto', ['produto' => $item]); ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php require APP . '/views/rodape.php'; ?>
