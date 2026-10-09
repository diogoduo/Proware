<?php
define('NA_AREA_ADMIN', true);
require dirname(__DIR__) . '/app/bootstrap.php';

$admin = exigir_admin('admin/mensagens.php');

if (requisicao_post()) {
    if (csrf_valido() && isset(STATUS_MENSAGEM[post('marcar')])) {
        marcar_mensagem((int) post('id'), post('marcar'));
        flash('sucesso', 'Mensagem marcada como ' . mb_lower_seguro(STATUS_MENSAGEM[post('marcar')]['nome']) . '.');
    }
    redirecionar('mensagens.php' . (post('voltar_status') !== '' ? '?status=' . urlencode(post('voltar_status')) : ''));
}

$titulo = 'Mensagens';
$secao = 'mensagens';
$status = get('status');
$status = isset(STATUS_MENSAGEM[$status]) ? $status : '';
$contagem = contar_mensagens_por_status();
$mensagens = listar_mensagens($status);
$subtitulo = 'Recebidas pelo formulário de contato da loja';

require APP . '/views/admin/topo.php';
?>

<div class="admin-filtros">
    <nav class="abas" aria-label="Filtrar mensagens">
        <a class="aba" href="admin/mensagens.php"<?= $status === '' ? ' aria-current="page"' : '' ?>>Todas <span class="aba__contador"><?= array_sum($contagem) ?></span></a>
        <?php foreach (STATUS_MENSAGEM as $chave => $info): ?>
            <a class="aba" href="admin/mensagens.php?status=<?= e($chave) ?>"<?= $status === $chave ? ' aria-current="page"' : '' ?>>
                <?= e($info['plural']) ?>
                <span class="aba__contador"><?= $contagem[$chave] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
</div>

<?php if (!$mensagens): ?>
    <section class="admin-cartao">
        <div class="estado-vazio">
            <span class="estado-vazio__icone"><?= icone('mensagem') ?></span>
            <h2><?= $status === 'nova' ? 'Nenhuma mensagem nova' : 'Nenhuma mensagem aqui' ?></h2>
            <p>As mensagens enviadas pela página de contato aparecem nesta lista.</p>
        </div>
    </section>
<?php else: ?>
    <ul class="lista-mensagens">
        <?php foreach ($mensagens as $m): ?>
            <?php $info = STATUS_MENSAGEM[$m['status']]; ?>
            <li class="admin-cartao mensagem<?= $m['status'] === 'nova' ? ' mensagem--nova' : '' ?>">
                <div class="mensagem__topo">
                    <div>
                        <strong><?= e($m['nome']) ?></strong>
                        <a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a>
                    </div>
                    <div class="mensagem__meta">
                        <?= etiqueta($info['nome'], $info['classe']) ?>
                        <span><?= e(data_br($m['criado_em'])) ?></span>
                    </div>
                </div>
                <p class="mensagem__assunto"><?= e(ASSUNTOS_CONTATO[$m['assunto']] ?? $m['assunto']) ?></p>
                <p class="mensagem__texto"><?= nl2br(e($m['mensagem'])) ?></p>
                <div class="mensagem__acoes">
                    <a class="botao botao--primario" href="mailto:<?= e($m['email']) ?>?subject=<?= e(rawurlencode('Re: ' . (ASSUNTOS_CONTATO[$m['assunto']] ?? 'Contato') . ' | Proware')) ?>"><?= icone('email') ?> Responder por e-mail</a>
                    <?php foreach (['lida' => 'Marcar como lida', 'respondida' => 'Marcar como respondida'] as $novo => $rotulo): ?>
                        <?php if ($m['status'] !== $novo && !($novo === 'lida' && $m['status'] === 'respondida')): ?>
                            <form method="post" action="admin/mensagens.php">
                                <?= campo_csrf() ?>
                                <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                                <input type="hidden" name="marcar" value="<?= e($novo) ?>">
                                <input type="hidden" name="voltar_status" value="<?= e($status) ?>">
                                <button class="botao botao--contorno" type="submit"><?= icone('check') ?> <?= e($rotulo) ?></button>
                            </form>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php require APP . '/views/admin/rodape.php'; ?>
