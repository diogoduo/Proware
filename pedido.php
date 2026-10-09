<?php
require __DIR__ . '/app/bootstrap.php';

$codigo = get('codigo');
$usuario = exigir_login('pedido.php?codigo=' . urlencode($codigo));
$pedido = pedido_do_usuario($codigo, $usuario['id']);
if (!$pedido) {
    require __DIR__ . '/404.php';
    exit;
}

$novo = get('novo') === '1';
$titulo = 'Pedido ' . $pedido['codigo'];
$pagina = 'conta';
$pagamento = FORMAS_PAGAMENTO[$pedido['pagamento']];
$aprovado = $pedido['status'] === 'Pagamento aprovado';
$etapas = ['Pedido recebido', 'Pagamento aprovado', 'Montagem e testes', 'Enviado', 'Entregue'];
$etapaAtual = $aprovado ? 1 : 0;
$endereco = $pedido['endereco'];

require APP . '/views/topo.php';
?>

<section class="secao secao--compacta">
    <div class="container estreito">
        <?php if ($novo): ?>
            <div class="confirmacao" data-limpar-carrinho>
                <span class="confirmacao__icone"><?= icone('check') ?></span>
                <h1>Pedido confirmado!</h1>
                <p>Obrigado pela compra, <?= e(primeiro_nome($usuario['nome'])) ?>. Você acompanha cada etapa em <a href="conta.php">Minha conta</a>.</p>
            </div>
        <?php else: ?>
            <nav class="migalhas migalhas--claro" aria-label="Você está em">
                <ol>
                    <li><a href="index.php">Início</a></li>
                    <li><a href="conta.php">Minha conta</a></li>
                    <li aria-current="page">Pedido <?= e($pedido['codigo']) ?></li>
                </ol>
            </nav>
            <h1 class="titulo-pagina">Pedido <?= e($pedido['codigo']) ?></h1>
        <?php endif; ?>

        <div class="cartao detalhe-pedido">
            <div class="detalhe-pedido__topo">
                <div>
                    <small>Número do pedido</small>
                    <strong><?= e($pedido['codigo']) ?></strong>
                </div>
                <div>
                    <small>Feito em</small>
                    <strong><?= e(data_br($pedido['criado_em'])) ?></strong>
                </div>
                <span class="etiqueta<?= $aprovado ? ' etiqueta--sucesso' : ' etiqueta--aviso' ?>"><?= e($pedido['status']) ?></span>
            </div>

            <ol class="linha-tempo" aria-label="Andamento do pedido">
                <?php foreach ($etapas as $i => $etapa): ?>
                    <li class="<?= $i <= $etapaAtual ? 'is-feita' : '' ?><?= $i === $etapaAtual ? ' is-atual' : '' ?>"<?= $i === $etapaAtual ? ' aria-current="step"' : '' ?>>
                        <span class="linha-tempo__ponto"><?= $i <= $etapaAtual ? icone('check') : '' ?></span>
                        <span><?= e($etapa) ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>

            <?php if (!$aprovado): ?>
                <div class="alerta alerta--info">
                    <?= icone($pagamento['icone']) ?>
                    <p>
                        <?php if ($pedido['pagamento'] === 'pix'): ?>
                            <strong>Pague com Pix para liberar a montagem.</strong> Em uma loja real, o QR Code e o código copia e cola apareceriam aqui. Nesta demonstração nenhum valor é cobrado.
                        <?php else: ?>
                            <strong>O boleto vence em 3 dias úteis.</strong> Em uma loja real, o link do boleto apareceria aqui. Nesta demonstração nenhum valor é cobrado.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>

            <h2 class="detalhe-pedido__subtitulo">Itens</h2>
            <ul class="itens-pedido">
                <?php foreach ($pedido['itens'] as $item): ?>
                    <li>
                        <img src="assets/img/produtos/<?= e($item['imagem']) ?>" alt="" width="64" height="64">
                        <div>
                            <strong><?= e($item['nome']) ?></strong>
                            <span><?= e($item['descricao']) ?></span>
                            <?php if (!empty($item['pecas'])): ?>
                                <details class="itens-pedido__pecas">
                                    <summary>Ver as 8 peças</summary>
                                    <ul>
                                        <?php foreach ($item['pecas'] as $categoria => $nome): ?>
                                            <li><span><?= e(categorias()[$categoria]['nome'] ?? $categoria) ?>:</span> <?= e($nome) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </details>
                            <?php endif; ?>
                        </div>
                        <span class="itens-pedido__qtd"><?= (int) $item['qtd'] ?>×</span>
                        <strong class="itens-pedido__preco"><?= brl($item['preco'] * $item['qtd']) ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="detalhe-pedido__colunas">
                <div>
                    <h2 class="detalhe-pedido__subtitulo">Entrega</h2>
                    <address>
                        <?= e($endereco['rua']) ?>, <?= e($endereco['numero']) ?><?= $endereco['complemento'] ? ' — ' . e($endereco['complemento']) : '' ?><br>
                        <?= e($endereco['bairro']) ?> · <?= e($endereco['cidade']) ?>/<?= e($endereco['uf']) ?><br>
                        CEP <?= e(formatar_cep($endereco['cep'])) ?>
                    </address>
                    <p class="texto-suave">Pagamento: <?= e($pagamento['nome']) ?></p>
                </div>
                <dl class="linhas-resumo">
                    <div><dt>Subtotal</dt><dd><?= brl((float) $pedido['subtotal']) ?></dd></div>
                    <?php if ($pedido['desconto'] > 0): ?>
                        <div><dt>Desconto Pix</dt><dd class="texto-sucesso">− <?= brl((float) $pedido['desconto']) ?></dd></div>
                    <?php endif; ?>
                    <div><dt>Frete</dt><dd class="texto-sucesso">Grátis</dd></div>
                    <div class="linhas-resumo__total"><dt>Total</dt><dd><?= brl((float) $pedido['total']) ?></dd></div>
                </dl>
            </div>
        </div>

        <div class="estado-vazio__acoes">
            <a class="botao botao--primario" href="conta.php">Ver meus pedidos</a>
            <a class="botao botao--contorno" href="index.php">Voltar para a loja</a>
        </div>
    </div>
</section>

<?php require APP . '/views/rodape.php'; ?>
