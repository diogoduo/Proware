<?php
define('NA_AREA_ADMIN', true);
require dirname(__DIR__) . '/app/bootstrap.php';

$admin = exigir_admin('admin/index.php');
$titulo = 'Visão geral';
$subtitulo = 'Olá, ' . primeiro_nome($admin['nome']) . '. Este é o resumo da loja hoje, ' . date('d/m/Y') . '.';
$secao = 'inicio';

$m = metricas_da_loja(14);
$recentes = listar_pedidos('', '', 6);
$mensagensNovas = contar_mensagens_por_status()['nova'];

// Escala do gráfico: arredonda o maior valor para cima em um número "redondo".
$maiorDia = max($m['por_dia']);
$passo = $maiorDia > 0 ? 10 ** floor(log10($maiorDia / 4)) : 250;
foreach ([1, 2, 2.5, 5, 10] as $multiplo) {
    if ($passo * $multiplo * 4 >= $maiorDia) {
        $passo *= $multiplo;
        break;
    }
}
$topoEscala = $passo * 4;
$totalPeriodo = array_sum($m['por_dia']);

// [singular, plural] de cada pendência
$pendencias = [
    ['status' => 'recebido', 'texto' => ['pedido aguardando pagamento', 'pedidos aguardando pagamento'], 'icone' => 'relogio'],
    ['status' => 'pago', 'texto' => ['pedido pago, pronto para montar', 'pedidos pagos, prontos para montar'], 'icone' => 'ferramenta'],
    ['status' => 'montagem', 'texto' => ['pedido em montagem e testes', 'pedidos em montagem e testes'], 'icone' => 'cpu'],
];

require APP . '/views/admin/topo.php';
?>

<section class="indicadores" aria-label="Indicadores">
    <div class="indicador indicador--destaque">
        <p class="indicador__rotulo">Faturamento total</p>
        <p class="indicador__valor"><?= brl($m['faturamento']) ?></p>
        <p class="indicador__nota">Pedidos não cancelados</p>
    </div>
    <div class="indicador">
        <p class="indicador__rotulo">Pedidos</p>
        <p class="indicador__valor"><?= number_format($m['pedidos'], 0, ',', '.') ?></p>
        <p class="indicador__nota"><?= $m['por_status']['cancelado'] ?> cancelado(s) fora da conta</p>
    </div>
    <div class="indicador">
        <p class="indicador__rotulo">Ticket médio</p>
        <p class="indicador__valor"><?= brl($m['ticket_medio']) ?></p>
        <p class="indicador__nota">Valor médio por pedido</p>
    </div>
    <div class="indicador">
        <p class="indicador__rotulo">Clientes cadastrados</p>
        <p class="indicador__valor"><?= number_format($m['clientes'], 0, ',', '.') ?></p>
        <p class="indicador__nota"><a href="admin/clientes.php">Ver clientes</a></p>
    </div>
</section>

<div class="admin-grade">
    <section class="admin-cartao grafico" aria-labelledby="grafico-titulo" data-grafico>
        <div class="admin-cartao__topo">
            <div>
                <h2 id="grafico-titulo">Faturamento nos últimos 14 dias</h2>
                <p class="admin-cartao__sub"><?= brl($totalPeriodo) ?> no período</p>
            </div>
        </div>

        <div class="grafico__area" aria-hidden="true">
            <div class="grafico__eixo">
                <?php for ($i = 4; $i >= 0; $i--): ?>
                    <span class="grafico__linha" data-posicao="<?= $i / 4 ?>"><span><?= e(valor_compacto($passo * $i)) ?></span></span>
                <?php endfor; ?>
            </div>
            <div class="grafico__barras">
                <?php foreach ($m['por_dia'] as $dia => $valor): ?>
                    <?php $rotuloDia = date_create($dia)->format('d/m'); ?>
                    <div class="grafico__coluna" tabindex="0" data-dica="<?= e(brl($valor)) ?>" data-dica-titulo="<?= e(date_create($dia)->format('d/m/Y')) ?>">
                        <span class="grafico__barra<?= $valor === $maiorDia && $valor > 0 ? ' is-maior' : '' ?>" data-proporcao="<?= $topoEscala ? $valor / $topoEscala : 0 ?>">
                            <?php if ($valor === $maiorDia && $valor > 0): ?>
                                <span class="grafico__valor"><?= e(valor_compacto($valor)) ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="grafico__dia"><?= e($rotuloDia) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="grafico__dica" role="presentation" data-dica-caixa hidden>
                <strong data-dica-valor></strong>
                <span data-dica-data></span>
            </div>
        </div>

        <details class="grafico__tabela">
            <summary>Ver os valores em tabela</summary>
            <table class="tabela-admin tabela-admin--compacta">
                <thead><tr><th scope="col">Dia</th><th scope="col" class="numero">Faturamento</th></tr></thead>
                <tbody>
                    <?php foreach ($m['por_dia'] as $dia => $valor): ?>
                        <tr><td><?= e(date_create($dia)->format('d/m/Y')) ?></td><td class="numero"><?= brl($valor) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </details>
    </section>

    <section class="admin-cartao" aria-labelledby="pendencias-titulo">
        <div class="admin-cartao__topo">
            <h2 id="pendencias-titulo">Precisa da sua atenção</h2>
        </div>
        <ul class="pendencias">
            <?php foreach ($pendencias as $p): ?>
                <li>
                    <a href="admin/pedidos.php?status=<?= e($p['status']) ?>">
                        <span class="pendencias__icone"><?= icone($p['icone']) ?></span>
                        <?php $quantidade = $m['por_status'][$p['status']]; ?>
                        <span class="pendencias__texto"><strong><?= $quantidade ?></strong> <?= e($p['texto'][$quantidade === 1 ? 0 : 1]) ?></span>
                        <?= icone('direita', 'pendencias__seta') ?>
                    </a>
                </li>
            <?php endforeach; ?>
            <li>
                <a href="admin/mensagens.php?status=nova">
                    <span class="pendencias__icone"><?= icone('mensagem') ?></span>
                    <span class="pendencias__texto"><strong><?= $mensagensNovas ?></strong> <?= $mensagensNovas === 1 ? 'mensagem nova' : 'mensagens novas' ?></span>
                    <?= icone('direita', 'pendencias__seta') ?>
                </a>
            </li>
        </ul>
    </section>
</div>

<div class="admin-grade">
    <section class="admin-cartao" aria-labelledby="recentes-titulo">
        <div class="admin-cartao__topo">
            <h2 id="recentes-titulo">Últimos pedidos</h2>
            <a class="link-seta" href="admin/pedidos.php">Ver todos <?= icone('seta') ?></a>
        </div>
        <?php if (!$recentes): ?>
            <p class="texto-suave">Nenhum pedido ainda. Eles aparecem aqui assim que alguém finalizar uma compra.</p>
        <?php else: ?>
            <div class="tabela-rolagem">
                <table class="tabela-admin">
                    <thead>
                        <tr><th scope="col">Pedido</th><th scope="col">Cliente</th><th scope="col">Situação</th><th scope="col" class="numero">Total</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentes as $pedido): ?>
                            <tr>
                                <td><a class="tabela-admin__link" href="admin/pedido.php?codigo=<?= e($pedido['codigo']) ?>"><?= e($pedido['codigo']) ?></a><small><?= e(data_br($pedido['criado_em'])) ?></small></td>
                                <td><?= e($pedido['cliente_nome']) ?></td>
                                <td><?= etiqueta_pedido($pedido['status']) ?></td>
                                <td class="numero"><?= brl($pedido['total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-cartao" aria-labelledby="vendidos-titulo">
        <div class="admin-cartao__topo">
            <h2 id="vendidos-titulo">Mais vendidos</h2>
        </div>
        <?php if (!$m['mais_vendidos']): ?>
            <p class="texto-suave">Ainda não há vendas.</p>
        <?php else: ?>
            <ol class="ranking">
                <?php foreach ($m['mais_vendidos'] as $posicao => $item): ?>
                    <li>
                        <span class="ranking__posicao"><?= $posicao + 1 ?></span>
                        <span class="ranking__nome"><?= e($item['nome']) ?><small><?= $item['unidades'] ?> <?= $item['unidades'] === 1 ? 'unidade' : 'unidades' ?></small></span>
                        <strong><?= brl($item['receita']) ?></strong>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </section>
</div>

<?php require APP . '/views/admin/rodape.php'; ?>
