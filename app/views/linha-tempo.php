<?php
/** Andamento de um pedido, com a data de cada etapa. Recebe $pedido. */
$datas = datas_das_etapas($pedido);
?>
<ol class="linha-tempo" aria-label="Andamento do pedido">
    <?php foreach (FLUXO_PEDIDO as $status): ?>
        <?php
        $feita = isset($datas[$status]);
        $atual = $status === $pedido['status'];
        ?>
        <li class="<?= $feita ? 'is-feita' : '' ?><?= $atual ? ' is-atual' : '' ?>"<?= $atual ? ' aria-current="step"' : '' ?>>
            <span class="linha-tempo__ponto"><?= $feita ? icone('check') : '' ?></span>
            <span><?= e(STATUS_PEDIDO[$status]['etapa']) ?></span>
            <?php if ($feita): ?>
                <small><?= e(date_create($datas[$status])->format('d/m H:i')) ?></small>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ol>
