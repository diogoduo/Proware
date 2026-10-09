<?php
define('NA_AREA_ADMIN', true);
require dirname(__DIR__) . '/app/bootstrap.php';

$admin = exigir_admin('admin/clientes.php');
$titulo = 'Clientes';
$secao = 'clientes';
$busca = mb_substr_seguro(get('busca'), 0, 80);
$clientes = listar_clientes($busca);
$subtitulo = count($clientes) . ($busca !== '' ? ' encontrados' : ' contas cadastradas');

require APP . '/views/admin/topo.php';
?>

<div class="admin-filtros">
    <form class="busca-admin" method="get" action="admin/clientes.php" role="search">
        <label class="visualmente-oculto" for="busca">Buscar clientes</label>
        <?= icone('busca') ?>
        <input id="busca" name="busca" type="search" placeholder="Nome ou e-mail" value="<?= e($busca) ?>">
    </form>
</div>

<section class="admin-cartao admin-cartao--tabela">
    <?php if (!$clientes): ?>
        <div class="estado-vazio">
            <span class="estado-vazio__icone"><?= icone('usuarios') ?></span>
            <h2>Nenhum cliente encontrado</h2>
            <p><?= $busca !== '' ? 'Tente buscar por outro nome ou e-mail.' : 'As contas criadas na loja aparecem aqui.' ?></p>
        </div>
    <?php else: ?>
        <div class="tabela-rolagem">
            <table class="tabela-admin">
                <thead>
                    <tr>
                        <th scope="col">Cliente</th>
                        <th scope="col">Celular</th>
                        <th scope="col" class="numero">Pedidos</th>
                        <th scope="col" class="numero">Total gasto</th>
                        <th scope="col">Último pedido</th>
                        <th scope="col">Cliente desde</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $c): ?>
                        <tr>
                            <td>
                                <?= e($c['nome']) ?>
                                <?php if ($c['admin']): ?><span class="etiqueta etiqueta--info etiqueta--mini">Admin</span><?php endif; ?>
                                <small><a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a></small>
                            </td>
                            <td><?= $c['telefone'] ? e(formatar_telefone($c['telefone'])) : '—' ?></td>
                            <td class="numero">
                                <?php if ($c['pedidos'] > 0): ?>
                                    <a href="admin/pedidos.php?busca=<?= e(urlencode($c['email'])) ?>"><?= (int) $c['pedidos'] ?></a>
                                <?php else: ?>0<?php endif; ?>
                            </td>
                            <td class="numero"><?= brl(reais((int) $c['gasto_centavos'])) ?></td>
                            <td><?= $c['ultimo_pedido'] ? e(date_create($c['ultimo_pedido'])->format('d/m/Y')) : '—' ?></td>
                            <td><?= e(date_create($c['criado_em'])->format('d/m/Y')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require APP . '/views/admin/rodape.php'; ?>
