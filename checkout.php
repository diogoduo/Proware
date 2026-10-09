<?php
require __DIR__ . '/app/bootstrap.php';

$usuario = exigir_login('checkout.php');

$titulo = 'Finalizar compra';
$pagina = '';
$scripts = ['assets/js/checkout.js'];

// Sugere o endereço do último pedido, se houver.
$ultimoPedido = pedidos_do_usuario($usuario['id'])[0] ?? null;
$endereco = array_merge(
    ['cep' => '', 'rua' => '', 'numero' => '', 'complemento' => '', 'bairro' => '', 'cidade' => '', 'uf' => ''],
    $ultimoPedido['endereco'] ?? []
);
$endereco['cep'] = formatar_cep($endereco['cep']);
$pagamento = 'pix';
$erros = [];
$errosCarrinho = [];

if (requisicao_post()) {
    foreach (array_keys($endereco) as $campo) {
        $endereco[$campo] = post($campo);
    }
    $endereco['uf'] = strtoupper($endereco['uf']);
    $pagamento = post('pagamento');

    if (!csrf_valido()) {
        $errosCarrinho[] = 'Sua sessão expirou. Confira os dados e tente novamente.';
    }
    $carrinho = montar_itens_do_carrinho(post('itens'));
    $errosCarrinho = array_merge($errosCarrinho, $carrinho['erros']);
    $erros = validar_endereco($endereco);
    if (!isset(FORMAS_PAGAMENTO[$pagamento])) {
        $erros['pagamento'] = 'Escolha a forma de pagamento.';
    }

    if (!$erros && !$errosCarrinho) {
        $pedido = criar_pedido($usuario, $carrinho, $endereco, $pagamento);
        redirecionar('pedido.php?codigo=' . urlencode($pedido['codigo']) . '&novo=1');
    }
}

require APP . '/views/topo.php';
?>

<section class="secao secao--compacta">
    <div class="container">
        <nav class="migalhas migalhas--claro" aria-label="Você está em">
            <ol>
                <li><a href="index.php">Início</a></li>
                <li aria-current="page">Finalizar compra</li>
            </ol>
        </nav>
        <h1 class="titulo-pagina">Finalizar compra</h1>

        <?php foreach ($errosCarrinho as $erro): ?>
            <div class="alerta alerta--erro" role="alert"><?= icone('alerta') ?><p><?= e($erro) ?></p></div>
        <?php endforeach; ?>

        <div class="checkout" data-checkout>
            <form class="checkout__formulario" id="form-checkout" method="post" action="checkout.php" novalidate data-validar>
                <?= campo_csrf() ?>
                <input type="hidden" name="itens" value="" data-itens-carrinho>

                <fieldset class="cartao-formulario">
                    <legend class="cartao-formulario__titulo"><span class="etapa__numero"><span>1</span></span> Endereço de entrega</legend>
                    <p class="cartao-formulario__sub">Entregando para <strong><?= e($usuario['nome']) ?></strong> · <?= e(formatar_telefone($usuario['telefone'])) ?></p>

                    <div class="formulario__linha formulario__linha--cep">
                        <div class="campo<?= isset($erros['cep']) ? ' campo--erro' : '' ?>">
                            <label for="cep">CEP</label>
                            <input id="cep" name="cep" type="text" inputmode="numeric" autocomplete="postal-code" required placeholder="00000-000" data-mascara="cep" data-buscar-cep value="<?= e($endereco['cep']) ?>"<?= attr_erro($erros, 'cep') ?>>
                            <?= erro_campo($erros, 'cep') ?>
                        </div>
                        <p class="campo__dica campo__dica--cep" data-status-cep aria-live="polite">Preenchemos o endereço automaticamente pelo CEP.</p>
                    </div>
                    <div class="formulario__linha formulario__linha--rua">
                        <div class="campo<?= isset($erros['rua']) ? ' campo--erro' : '' ?>">
                            <label for="rua">Rua</label>
                            <input id="rua" name="rua" type="text" autocomplete="address-line1" required value="<?= e($endereco['rua']) ?>"<?= attr_erro($erros, 'rua') ?>>
                            <?= erro_campo($erros, 'rua') ?>
                        </div>
                        <div class="campo<?= isset($erros['numero']) ? ' campo--erro' : '' ?>">
                            <label for="numero">Número</label>
                            <input id="numero" name="numero" type="text" inputmode="numeric" required value="<?= e($endereco['numero']) ?>"<?= attr_erro($erros, 'numero') ?>>
                            <?= erro_campo($erros, 'numero') ?>
                        </div>
                    </div>
                    <div class="formulario__linha">
                        <div class="campo">
                            <label for="complemento">Complemento <span class="opcional">(opcional)</span></label>
                            <input id="complemento" name="complemento" type="text" autocomplete="address-line2" value="<?= e($endereco['complemento']) ?>">
                        </div>
                        <div class="campo<?= isset($erros['bairro']) ? ' campo--erro' : '' ?>">
                            <label for="bairro">Bairro</label>
                            <input id="bairro" name="bairro" type="text" required value="<?= e($endereco['bairro']) ?>"<?= attr_erro($erros, 'bairro') ?>>
                            <?= erro_campo($erros, 'bairro') ?>
                        </div>
                    </div>
                    <div class="formulario__linha formulario__linha--cidade">
                        <div class="campo<?= isset($erros['cidade']) ? ' campo--erro' : '' ?>">
                            <label for="cidade">Cidade</label>
                            <input id="cidade" name="cidade" type="text" autocomplete="address-level2" required value="<?= e($endereco['cidade']) ?>"<?= attr_erro($erros, 'cidade') ?>>
                            <?= erro_campo($erros, 'cidade') ?>
                        </div>
                        <div class="campo<?= isset($erros['uf']) ? ' campo--erro' : '' ?>">
                            <label for="uf">Estado</label>
                            <select id="uf" name="uf" autocomplete="address-level1" required<?= attr_erro($erros, 'uf') ?>>
                                <option value="">UF</option>
                                <?php foreach (ESTADOS as $uf): ?>
                                    <option<?= $endereco['uf'] === $uf ? ' selected' : '' ?>><?= $uf ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= erro_campo($erros, 'uf') ?>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="cartao-formulario">
                    <legend class="cartao-formulario__titulo"><span class="etapa__numero"><span>2</span></span> Forma de pagamento</legend>
                    <div class="formas-pagamento">
                        <?php foreach (FORMAS_PAGAMENTO as $chave => $forma): ?>
                            <label class="opcao opcao--pagamento">
                                <input type="radio" name="pagamento" value="<?= e($chave) ?>"<?= $pagamento === $chave ? ' checked' : '' ?> data-forma-pagamento>
                                <span class="opcao__icone"><?= icone($forma['icone']) ?></span>
                                <span class="opcao__corpo">
                                    <span class="opcao__nome"><?= e($forma['nome']) ?></span>
                                    <span class="opcao__detalhe"><?= e($forma['descricao']) ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?= erro_campo($erros, 'pagamento') ?>
                    <div class="alerta alerta--info alerta--compacto">
                        <?= icone('info') ?>
                        <p>Esta é uma loja de demonstração: nenhum valor é cobrado e nenhum dado de cartão é pedido.</p>
                    </div>
                </fieldset>
            </form>

            <aside class="resumo-pedido" aria-labelledby="resumo-pedido-titulo">
                <div class="resumo-pedido__caixa">
                    <h2 id="resumo-pedido-titulo">Resumo do pedido</h2>
                    <ul class="resumo-pedido__itens" data-resumo-itens></ul>
                    <dl class="linhas-resumo">
                        <div><dt>Subtotal</dt><dd data-resumo-subtotal>R$ 0,00</dd></div>
                        <div data-linha-desconto><dt>Desconto Pix (5%)</dt><dd class="texto-sucesso" data-resumo-desconto></dd></div>
                        <div><dt>Frete</dt><dd class="texto-sucesso">Grátis</dd></div>
                        <div class="linhas-resumo__total"><dt>Total</dt><dd data-resumo-total>R$ 0,00</dd></div>
                    </dl>
                    <p class="resumo-pedido__parcelas" data-resumo-parcelas></p>
                    <button class="botao botao--primario botao--bloco botao--grande" type="submit" form="form-checkout" data-confirmar-pedido>
                        <?= icone('cadeado') ?> Confirmar pedido
                    </button>
                    <a class="botao botao--texto botao--bloco" href="#" data-abrir-carrinho>Editar carrinho</a>
                </div>
            </aside>
        </div>

        <div class="estado-vazio" data-checkout-vazio hidden>
            <span class="estado-vazio__icone"><?= icone('carrinho') ?></span>
            <h2>Seu carrinho está vazio</h2>
            <p>Adicione um PC ou periférico para finalizar a compra.</p>
            <div class="estado-vazio__acoes">
                <a class="botao botao--primario" href="montar.php">Monte seu PC</a>
                <a class="botao botao--contorno" href="pcs-prontos.php">Ver PCs prontos</a>
            </div>
        </div>
    </div>
</section>

<?php require APP . '/views/rodape.php'; ?>
