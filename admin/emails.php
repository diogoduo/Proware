<?php
define('NA_AREA_ADMIN', true);
require dirname(__DIR__) . '/app/bootstrap.php';

$admin = exigir_admin('admin/emails.php');
if ($admin['somente_leitura']) {
    // Os e-mails têm links de redefinição de senha: o acesso de demonstração não vê.
    require RAIZ . '/404.php';
    exit;
}

$titulo = 'E-mails';
$secao = 'emails';
$selecionado = get('id') !== '' ? email_por_id((int) get('id')) : null;
$emails = listar_emails();
$subtitulo = envio_de_email_configurado()
    ? 'Envio ativo pelo Brevo. Aqui fica o registro de cada mensagem.'
    : 'Envio desativado: as mensagens só ficam registradas aqui. Veja no README como ativar o Brevo.';

$situacoes = [
    'enviado' => ['Enviado', 'sucesso'],
    'registrado' => ['Só registrado', 'info'],
    'falhou' => ['Falhou', 'erro'],
];
$tipos = ['pedido' => 'Pedido', 'senha' => 'Senha', 'geral' => 'Geral'];

require APP . '/views/admin/topo.php';
?>

<?php if ($selecionado): ?>
    <section class="admin-cartao email-detalhe" aria-labelledby="email-titulo">
        <div class="admin-cartao__topo">
            <div>
                <h2 id="email-titulo"><?= e($selecionado['assunto']) ?></h2>
                <p class="admin-cartao__sub">Para <?= e($selecionado['para']) ?> · <?= e(data_br($selecionado['criado_em'])) ?></p>
            </div>
            <a class="botao botao--contorno" href="admin/emails.php"><?= icone('voltar') ?> Todos os e-mails</a>
        </div>
        <?php if ($selecionado['erro']): ?>
            <div class="alerta alerta--erro"><?= icone('alerta') ?><p><?= e($selecionado['erro']) ?></p></div>
        <?php endif; ?>
        <?php preg_match_all('#https?://\S+#',$selecionado['texto'], $links); ?>
        <?php if ($links[0]): ?>
            <p class="email-detalhe__links"><?= icone('seta') ?> Links da mensagem:
                <?php foreach (array_unique($links[0]) as $link): ?>
                    <a href="<?= e($link) ?>"><?= e(mb_substr_seguro($link, 0, 70)) ?><?= strlen($link) > 70 ? '…' : '' ?></a>
                <?php endforeach; ?>
            </p>
        <?php endif; ?>
        <iframe class="email-detalhe__previa" src="admin/email-conteudo.php?id=<?= (int) $selecionado['id'] ?>" sandbox title="Conteúdo do e-mail"></iframe>
        <details class="grafico__tabela">
            <summary>Ver a versão em texto</summary>
            <pre class="email-detalhe__texto"><?= e($selecionado['texto']) ?></pre>
        </details>
    </section>
<?php endif; ?>

<section class="admin-cartao admin-cartao--tabela">
    <?php if (!$emails): ?>
        <div class="estado-vazio">
            <span class="estado-vazio__icone"><?= icone('email') ?></span>
            <h2>Nenhum e-mail ainda</h2>
            <p>Confirmações de pedido, avisos de entrega e links de nova senha aparecem aqui.</p>
        </div>
    <?php else: ?>
        <div class="tabela-rolagem">
            <table class="tabela-admin">
                <thead>
                    <tr>
                        <th scope="col">Assunto</th>
                        <th scope="col">Para</th>
                        <th scope="col">Tipo</th>
                        <th scope="col">Situação</th>
                        <th scope="col">Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($emails as $email): ?>
                        <?php [$rotulo, $classe] = $situacoes[$email['status']] ?? [$email['status'], 'info']; ?>
                        <tr<?= $selecionado && (int) $selecionado['id'] === (int) $email['id'] ? ' class="is-selecionada"' : '' ?>>
                            <td><a class="tabela-admin__link tabela-admin__link--quebra" href="admin/emails.php?id=<?= (int) $email['id'] ?>"><?= e($email['assunto']) ?></a></td>
                            <td><?= e($email['para']) ?></td>
                            <td><?= e($tipos[$email['tipo']] ?? $email['tipo']) ?></td>
                            <td><?= etiqueta($rotulo, $classe) ?></td>
                            <td><?= e(data_br($email['criado_em'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require APP . '/views/admin/rodape.php'; ?>
