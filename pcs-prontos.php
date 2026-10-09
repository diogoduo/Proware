<?php
require __DIR__ . '/app/bootstrap.php';

$titulo = 'PCs Prontos';
$subtitulo = 'Configurações equilibradas, montadas e testadas pela nossa equipe. É só tirar da caixa e ligar.';
$descricao = 'PCs gamer, workstations e computadores para estudo montados e testados, com até 20% de desconto e frete grátis.';
$pagina = 'pcs';

$pcs = produtos_do_tipo('pc');

require APP . '/views/topo.php';
parcial('topo-pagina', compact('titulo', 'subtitulo'));
?>

<section class="secao secao--compacta">
    <div class="container">
        <div class="barra-filtros" data-filtros>
            <div class="filtros" role="group" aria-label="Filtrar por uso">
                <button class="filtro" type="button" data-filtro="todos" aria-pressed="true">Todos</button>
                <?php foreach (usos() as $chave => $uso): ?>
                    <button class="filtro" type="button" data-filtro="<?= e($chave) ?>" aria-pressed="false">
                        <?= icone($uso['icone']) ?> <?= e($uso['nome']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <label class="ordenar">
                <span>Ordenar por</span>
                <select data-ordenar>
                    <option value="padrao">Relevância</option>
                    <option value="menor">Menor preço</option>
                    <option value="maior">Maior preço</option>
                </select>
            </label>
        </div>

        <p class="contagem-resultados" data-contagem aria-live="polite"><?= count($pcs) ?> computadores</p>

        <div class="grade-produtos" data-lista-produtos>
            <?php foreach ($pcs as $produto): ?>
                <?php parcial('card-produto', ['produto' => $produto]); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="secao secao--compacta">
    <div class="container">
        <div class="faixa-chamada">
            <span class="faixa-chamada__icone"><?= icone('ferramenta') ?></span>
            <div>
                <h2>Quer trocar alguma peça?</h2>
                <p>Clique em <strong>Personalizar</strong> em qualquer PC para abri-lo no configurador e ajustar do seu jeito.</p>
            </div>
            <a class="botao botao--primario" href="montar.php">Monte seu PC <?= icone('seta') ?></a>
        </div>
    </div>
</section>

<?php parcial('beneficios'); ?>

<?php require APP . '/views/rodape.php'; ?>
