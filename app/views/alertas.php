<?php
$icones = ['sucesso' => 'ok', 'erro' => 'alerta', 'info' => 'info'];
$mensagens = flashes();
if (!$mensagens) {
    return;
}
?>
<div class="container alertas-pagina">
    <?php foreach ($mensagens as $m): ?>
        <div class="alerta alerta--<?= e($m['tipo']) ?>" role="<?= $m['tipo'] === 'erro' ? 'alert' : 'status' ?>">
            <?= icone($icones[$m['tipo']] ?? 'info') ?>
            <p><?= e($m['mensagem']) ?></p>
        </div>
    <?php endforeach; ?>
</div>
