<?php
require __DIR__ . '/app/bootstrap.php';

$titulo = 'Monte seu PC';
$subtitulo = 'Escolha cada peça e acompanhe a compatibilidade, o consumo e o preço em tempo real.';
$descricao = 'Configurador de PC com checagem de compatibilidade: escolha processador, placa-mãe, memória, placa de vídeo e mais. Montagem e testes inclusos.';
$pagina = 'montar';
$scripts = ['assets/js/montar.js'];

// A montagem inicial pode vir de um PC pronto, de um perfil ou de um link compartilhado.
$pcOrigem = produto(get('pc'));
$perfil = perfis()[get('perfil')] ?? null;

if ($pcOrigem && $pcOrigem['tipo'] === 'pc') {
    $selecao = $pcOrigem['pecas'];
} elseif ($perfil) {
    $selecao = $perfil['pecas'];
} else {
    $selecao = [];
    foreach (array_keys(categorias()) as $categoria) {
        $id = get($categoria);
        if ((componente($id)['categoria'] ?? null) === $categoria) {
            $selecao[$categoria] = $id;
        }
    }
}

require APP . '/views/topo.php';
parcial('topo-pagina', compact('titulo', 'subtitulo'));
?>

<section class="secao secao--compacta">
    <div class="container configurador" data-configurador>
        <div class="configurador__principal">
            <div class="perfis-rapidos">
                <p class="perfis-rapidos__titulo">Comece por um perfil ou escolha do zero:</p>
                <div class="filtros">
                    <?php foreach (perfis() as $chave => $p): ?>
                        <button class="filtro" type="button" aria-pressed="false" data-perfil="<?= e(json_encode($p['pecas'])) ?>">
                            <?= icone($p['icone']) ?> <?= e($p['nome']) ?>
                        </button>
                    <?php endforeach; ?>
                    <button class="filtro filtro--limpar" type="button" data-limpar><?= icone('lixeira') ?> Limpar</button>
                </div>
            </div>

            <?php if ($pcOrigem && $pcOrigem['tipo'] === 'pc'): ?>
                <div class="alerta alerta--info">
                    <?= icone('info') ?>
                    <p>Você está personalizando o <strong><?= e($pcOrigem['nome']) ?></strong>.
                        <?php if (!empty($pcOrigem['preco_original'])): ?>
                            O desconto do PC pronto vale só para a configuração original.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>

            <form class="etapas" data-form-montagem>
                <?php $numero = 0; ?>
                <?php foreach (categorias() as $categoria => $info): $numero++; ?>
                    <fieldset class="etapa" id="etapa-<?= e($categoria) ?>" data-etapa="<?= e($categoria) ?>">
                        <legend class="etapa__topo">
                            <span class="etapa__numero" aria-hidden="true"><span><?= $numero ?></span><?= icone('check') ?></span>
                            <span class="etapa__titulo"><?= e($info['nome']) ?></span>
                        </legend>
                        <p class="etapa__dica"><?= icone($info['icone']) ?> <?= e($info['dica']) ?></p>

                        <div class="opcoes<?= $categoria === 'gabinete' ? ' opcoes--imagens' : '' ?>">
                            <?php foreach (componentes_da_categoria($categoria) as $id => $peca): ?>
                                <label class="opcao">
                                    <input type="radio" name="<?= e($categoria) ?>" value="<?= e($id) ?>"<?= ($selecao[$categoria] ?? '') === $id ? ' checked' : '' ?>>
                                    <?php if (!empty($peca['imagem'])): ?>
                                        <img class="opcao__imagem" src="assets/img/produtos/<?= e($peca['imagem']) ?>" alt="" width="120" height="120" loading="lazy">
                                    <?php endif; ?>
                                    <span class="opcao__corpo">
                                        <span class="opcao__nome"><?= e($peca['nome']) ?></span>
                                        <span class="opcao__detalhe"><?= e($peca['detalhe']) ?></span>
                                        <span class="opcao__motivo" data-motivo></span>
                                    </span>
                                    <span class="opcao__preco"><?= $peca['preco'] > 0 ? brl((float) $peca['preco']) : 'Incluso' ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                <?php endforeach; ?>
            </form>
        </div>

        <aside class="resumo-montagem" id="resumo-montagem" aria-labelledby="resumo-titulo">
            <div class="resumo-montagem__caixa">
                <div class="resumo-montagem__topo">
                    <h2 id="resumo-titulo">Sua montagem</h2>
                    <span class="resumo-montagem__progresso" data-progresso-texto>0 de 8</span>
                </div>
                <div class="barra-progresso" aria-hidden="true"><span data-progresso-barra></span></div>

                <ul class="resumo-montagem__lista">
                    <?php foreach (categorias() as $categoria => $info): ?>
                        <li data-resumo="<?= e($categoria) ?>">
                            <a href="#etapa-<?= e($categoria) ?>">
                                <span class="resumo-montagem__icone"><?= icone($info['icone']) ?></span>
                                <span class="resumo-montagem__peca">
                                    <small><?= e($info['nome']) ?></small>
                                    <span data-nome>Não escolhido</span>
                                </span>
                                <span class="resumo-montagem__preco" data-preco></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="consumo">
                    <div class="consumo__topo">
                        <span><?= icone('raio') ?> Consumo estimado</span>
                        <strong data-consumo>—</strong>
                    </div>
                    <div class="barra-progresso barra-progresso--consumo" aria-hidden="true"><span data-consumo-barra></span></div>
                    <p class="consumo__legenda" data-consumo-legenda>Escolha o processador e a placa de vídeo.</p>
                </div>

                <div class="verificacao" data-verificacao></div>

                <dl class="linhas-resumo resumo-montagem__total">
                    <div><dt>Total</dt><dd data-total>R$ 0,00</dd></div>
                    <div class="linhas-resumo__destaque"><dt>No Pix (5% off)</dt><dd data-total-pix>R$ 0,00</dd></div>
                </dl>
                <p class="resumo-montagem__parcelas" data-parcelas></p>

                <button class="botao botao--primario botao--bloco botao--grande" type="button" data-adicionar-montagem disabled>
                    <?= icone('carrinho') ?> Adicionar ao carrinho
                </button>
                <button class="botao botao--texto botao--bloco" type="button" data-copiar-link>Copiar link desta montagem</button>
                <p class="resumo-montagem__nota"><?= icone('ferramenta') ?> Montagem, 24h de testes e frete grátis inclusos.</p>
            </div>
        </aside>
    </div>

    <div class="barra-montagem" data-barra-montagem>
        <div>
            <small data-barra-progresso>0 de 8 peças</small>
            <strong data-barra-total>R$ 0,00</strong>
        </div>
        <a class="botao botao--primario" href="#resumo-montagem">Ver resumo</a>
    </div>
</section>

<?php require APP . '/views/rodape.php'; ?>
