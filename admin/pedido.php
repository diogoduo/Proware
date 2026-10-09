<?php
define('NA_AREA_ADMIN', true);
require dirname(__DIR__) . '/app/bootstrap.php';

$codigo = get('codigo') ?: post('codigo');
$admin = exigir_admin('admin/pedido.php?codigo=' . urlencode($codigo));
$pedido = pedido_por_codigo($codigo);
if (!$pedido) {
    flash('erro', 'Pedido não encontrado.');
    redirecionar('pedidos.php');
}

$erroAcao = '';
if (requisicao_post()) {
    exigir_edicao_no_painel($admin, 'pedido.php?codigo=' . urlencode($codigo));
    if (!csrf_valido()) {
        $erroAcao = 'Sua sessão expirou. Tente novamente.';
    } else {
        try {
            $novo = post('status');
            alterar_status_pedido($codigo, $novo, post('rastreio'), post('observacao'));
            flash('sucesso', 'Pedido atualizado para "' . STATUS_PEDIDO[$novo]['nome'] . '".');
            redirecionar('pedido.php?codigo=' . urlencode($codigo));
        } catch (InvalidArgumentException $erro) {
            $erroAcao = $erro->getMessage();
        }
    }
}

$titulo = 'Pedido ' . $pedido['codigo'];
$subtitulo = 'Feito em ' . data_br($pedido['criado_em']) . ' por ' . $pedido['cliente_nome'];
$secao = 'pedidos';
$proximo = proximo_status($pedido['status']);
$cliente = usuario_por_id($pedido['usuario_id']);
$endereco = $pedido['endereco'];
$acoesTopo = '<a class="botao botao--contorno" href="admin/pedidos.php">' . icone('voltar') . ' Todos os pedidos</a>';

require APP . '/views/admin/topo.php';
?>

<div class="admin-grade admin-grade--detalhe">
    <div class="admin-coluna">
        <section class="admin-cartao" aria-labelledby="andamento-titulo">
            <div class="admin-cartao__topo">
                <h2 id="andamento-titulo">Andamento</h2>
                <?= etiqueta_pedido($pedido['status']) ?>
            </div>
            <?php parcial('linha-tempo', ['pedido' => $pedido]); ?>
            <?php if ($pedido['rastreio']): ?>
                <p class="admin-rastreio"><?= icone('caminhao') ?> Rastreio: <strong class="codigo"><?= e($pedido['rastreio']) ?></strong></p>
            <?php endif; ?>
        </section>

        <section class="admin-cartao" aria-labelledby="itens-titulo">
            <div class="admin-cartao__topo">
                <h2 id="itens-titulo">Itens</h2>
            </div>
            <ul class="itens-pedido">
                <?php foreach ($pedido['itens'] as $item): ?>
                    <li>
                        <img src="assets/img/produtos/<?= e($item['imagem']) ?>" alt="" width="64" height="64">
                        <div>
                            <strong><?= e($item['nome']) ?></strong>
                            <span><?= e($item['descricao']) ?></span>
                            <?php if (!empty($item['pecas'])): ?>
                                <details class="itens-pedido__pecas" open>
                                    <summary>Peças para separar</summary>
                                    <ul>
                                        <?php foreach ($item['pecas'] as $categoria => $nome): ?>
                                            <li><span><?= e(categorias()[$categoria]['nome'] ?? $categoria) ?>:</span> <?= e($nome) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </details>
                            <?php endif; ?>
                        </div>
                        <span class="itens-pedido__qtd"><?= (int) $item['qtd'] ?>× <?= brl($item['preco']) ?></span>
                        <strong class="itens-pedido__preco"><?= brl($item['preco'] * $item['qtd']) ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
            <dl class="linhas-resumo admin-totais">
                <div><dt>Subtotal</dt><dd><?= brl($pedido['subtotal']) ?></dd></div>
                <?php if ($pedido['desconto'] > 0): ?>
                    <div><dt>Desconto Pix</dt><dd class="texto-sucesso">− <?= brl($pedido['desconto']) ?></dd></div>
                <?php endif; ?>
                <div><dt>Frete</dt><dd class="texto-sucesso">Grátis</dd></div>
                <div class="linhas-resumo__total"><dt>Total</dt><dd><?= brl($pedido['total']) ?></dd></div>
            </dl>
        </section>

        <section class="admin-cartao" aria-labelledby="historico-titulo">
            <div class="admin-cartao__topo">
                <h2 id="historico-titulo">Histórico</h2>
            </div>
            <ol class="historico">
                <?php foreach (array_reverse($pedido['eventos']) as $evento): ?>
                    <li>
                        <span class="historico__data"><?= e(data_br($evento['criado_em'])) ?></span>
                        <span class="historico__texto">
                            <?= e(STATUS_PEDIDO[$evento['status']]['nome'] ?? $evento['status']) ?>
                            <?php if ($evento['observacao']): ?><small><?= e($evento['observacao']) ?></small><?php endif; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ol>
        </section>
    </div>

    <div class="admin-coluna">
        <section class="admin-cartao admin-acoes" aria-labelledby="acoes-titulo">
            <h2 id="acoes-titulo">Próximo passo</h2>

            <?php if ($erroAcao): ?>
                <div class="alerta alerta--erro" role="alert"><?= icone('alerta') ?><p><?= e($erroAcao) ?></p></div>
            <?php endif; ?>

            <?php if ($proximo): ?>
                <form class="formulario" method="post" action="admin/pedido.php">
                    <?= campo_csrf() ?>
                    <input type="hidden" name="codigo" value="<?= e($pedido['codigo']) ?>">
                    <input type="hidden" name="status" value="<?= e($proximo) ?>">
                    <?php if ($proximo === 'enviado'): ?>
                        <div class="campo">
                            <label for="rastreio">Código de rastreio</label>
                            <input id="rastreio" name="rastreio" type="text" required minlength="8" maxlength="30" placeholder="Ex.: BR123456789BR" autocomplete="off" value="<?= e(post('rastreio')) ?>">
                        </div>
                    <?php endif; ?>
                    <div class="campo">
                        <label for="observacao">Observação <span class="opcional">(opcional, só a equipe vê)</span></label>
                        <input id="observacao" name="observacao" type="text" maxlength="200" autocomplete="off">
                    </div>
                    <button class="botao botao--primario botao--bloco" type="submit">
                        <?= icone('check') ?> Marcar como "<?= e(STATUS_PEDIDO[$proximo]['nome']) ?>"
                    </button>
                </form>
            <?php elseif ($pedido['status'] === 'entregue'): ?>
                <p class="admin-acoes__fim"><?= icone('ok') ?> Pedido concluído. Nada mais a fazer.</p>
            <?php else: ?>
                <p class="admin-acoes__fim"><?= icone('info') ?> Pedido cancelado.</p>
            <?php endif; ?>

            <?php if (pode_cancelar($pedido['status'])): ?>
                <form method="post" action="admin/pedido.php" data-confirmar="Cancelar o pedido <?= e($pedido['codigo']) ?>? O cliente verá o pedido como cancelado.">
                    <?= campo_csrf() ?>
                    <input type="hidden" name="codigo" value="<?= e($pedido['codigo']) ?>">
                    <input type="hidden" name="status" value="cancelado">
                    <button class="botao botao--perigo botao--bloco" type="submit"><?= icone('fechar') ?> Cancelar pedido</button>
                </form>
            <?php endif; ?>
        </section>

        <section class="admin-cartao" aria-labelledby="cliente-titulo">
            <h2 id="cliente-titulo">Cliente</h2>
            <dl class="dados-lista">
                <div><dt>Nome</dt><dd><?= e($pedido['cliente_nome']) ?></dd></div>
                <div><dt>E-mail</dt><dd><a href="mailto:<?= e($pedido['cliente_email']) ?>"><?= e($pedido['cliente_email']) ?></a></dd></div>
                <?php if ($cliente): ?>
                    <div><dt>Celular</dt><dd><?= e(formatar_telefone($cliente['telefone'])) ?></dd></div>
                    <div><dt>CPF</dt><dd><?= e(formatar_cpf($cliente['cpf'])) ?></dd></div>
                <?php endif; ?>
            </dl>
        </section>

        <section class="admin-cartao" aria-labelledby="entrega-titulo">
            <h2 id="entrega-titulo">Entrega e pagamento</h2>
            <address class="admin-endereco">
                <?= e($endereco['rua']) ?>, <?= e($endereco['numero']) ?><?= $endereco['complemento'] ? ' — ' . e($endereco['complemento']) : '' ?><br>
                <?= e($endereco['bairro']) ?> · <?= e($endereco['cidade']) ?>/<?= e($endereco['uf']) ?><br>
                CEP <?= e(formatar_cep($endereco['cep'])) ?>
            </address>
            <p class="admin-pagamento"><?= icone(FORMAS_PAGAMENTO[$pedido['pagamento']]['icone'] ?? 'cartao') ?> <?= e(FORMAS_PAGAMENTO[$pedido['pagamento']]['nome'] ?? $pedido['pagamento']) ?></p>
        </section>
    </div>
</div>

<?php require APP . '/views/admin/rodape.php'; ?>
