<?php
define('NA_AREA_ADMIN', true);
require dirname(__DIR__) . '/app/bootstrap.php';

$admin = exigir_admin('admin/pedidos.php');
$titulo = 'Pedidos';
$secao = 'pedidos';

$status = get('status');
$status = isset(STATUS_PEDIDO[$status]) ? $status : '';
$busca = mb_substr_seguro(get('busca'), 0, 80);
$contagem = contar_pedidos_por_status();
$pedidos = listar_pedidos($status, $busca);
$subtitulo = array_sum($contagem) . ' pedidos no total';

require APP . '/views/admin/topo.php';
?>

<div class="admin-filtros">
    <nav class="abas" aria-label="Filtrar por situação">
        <a class="aba" href="admin/pedidos.php<?= $busca !== '' ? '?busca=' . e(urlencode($busca)) : '' ?>"<?= $status === '' ? ' aria-current="page"' : '' ?>>
            Todos <span class="aba__contador"><?= array_sum($contagem) ?></span>
        </a>
        <?php foreach (STATUS_PEDIDO as $chave => $info): ?>
            <a class="aba" href="admin/pedidos.php?status=<?= e($chave) ?><?= $busca !== '' ? '&amp;busca=' . e(urlencode($busca)) : '' ?>"<?= $status === $chave ? ' aria-current="page"' : '' ?>>
                <?= e($info['nome']) ?> <span class="aba__contador"><?= $contagem[$chave] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <form class="busca-admin" method="get" action="admin/pedidos.php" role="search">
        <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
        <label class="visualmente-oculto" for="busca">Buscar pedidos</label>
        <?= icone('busca') ?>
        <input id="busca" name="busca" type="search" placeholder="Código, nome ou e-mail" value="<?= e($busca) ?>">
    </form>
</div>

<section class="admin-cartao admin-cartao--tabela">
    <?php if (!$pedidos): ?>
        <div class="estado-vazio">
            <span class="estado-vazio__icone"><?= icone('pacote') ?></span>
            <h2>Nenhum pedido encontrado</h2>
            <p><?= $busca !== '' || $status !== '' ? 'Tente outro filtro ou limpe a busca.' : 'Os pedidos aparecem aqui assim que alguém finalizar uma compra.' ?></p>
        </div>
    <?php else: ?>
        <div class="tabela-rolagem">
            <table class="tabela-admin">
                <thead>
                    <tr>
                        <th scope="col">Pedido</th>
                        <th scope="col">Cliente</th>
                        <th scope="col">Itens</th>
                        <th scope="col">Pagamento</th>
                        <th scope="col">Situação</th>
                        <th scope="col" class="numero">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $pedido): ?>
                        <?php $unidades = array_sum(array_column($pedido['itens'], 'qtd')); ?>
                        <tr>
                            <td>
                                <a class="tabela-admin__link" href="admin/pedido.php?codigo=<?= e($pedido['codigo']) ?>"><?= e($pedido['codigo']) ?></a>
                                <small><?= e(data_br($pedido['criado_em'])) ?></small>
                            </td>
                            <td><?= e($pedido['cliente_nome']) ?><small><?= e($pedido['cliente_email']) ?></small></td>
                            <td><?= $unidades ?> <?= $unidades === 1 ? 'item' : 'itens' ?><small><?= e($pedido['itens'][0]['nome'] ?? '') ?><?= count($pedido['itens']) > 1 ? ' e mais ' . (count($pedido['itens']) - 1) : '' ?></small></td>
                            <td><?= e(FORMAS_PAGAMENTO[$pedido['pagamento']]['nome'] ?? $pedido['pagamento']) ?></td>
                            <td><?= etiqueta_pedido($pedido['status']) ?></td>
                            <td class="numero"><?= brl($pedido['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require APP . '/views/admin/rodape.php'; ?>
