<?php
require __DIR__ . '/app/bootstrap.php';

$usuario = exigir_login('conta.php');

$titulo = 'Minha conta';
$subtitulo = 'Olá, ' . primeiro_nome($usuario['nome']) . '! Aqui você acompanha seus pedidos e seus dados.';
$pagina = 'conta';
$pedidos = pedidos_do_usuario($usuario['id']);

require APP . '/views/topo.php';
parcial('topo-pagina', compact('titulo', 'subtitulo'));
?>

<section class="secao secao--compacta">
    <div class="container conta">
        <div class="conta__pedidos">
            <h2>Meus pedidos</h2>
            <?php if (!$pedidos): ?>
                <div class="estado-vazio estado-vazio--cartao">
                    <span class="estado-vazio__icone"><?= icone('pacote') ?></span>
                    <h3>Você ainda não fez nenhum pedido</h3>
                    <p>Que tal começar montando um PC do seu jeito?</p>
                    <a class="botao botao--primario" href="montar.php">Monte seu PC</a>
                </div>
            <?php else: ?>
                <ul class="lista-pedidos">
                    <?php foreach ($pedidos as $pedido): ?>
                        <li class="cartao-pedido">
                            <div class="cartao-pedido__topo">
                                <div>
                                    <strong>Pedido <?= e($pedido['codigo']) ?></strong>
                                    <span><?= e(data_br($pedido['criado_em'])) ?></span>
                                </div>
                                <span class="etiqueta<?= $pedido['status'] === 'Pagamento aprovado' ? ' etiqueta--sucesso' : ' etiqueta--aviso' ?>"><?= e($pedido['status']) ?></span>
                            </div>
                            <ul class="cartao-pedido__itens">
                                <?php foreach ($pedido['itens'] as $item): ?>
                                    <li><?= (int) $item['qtd'] ?>× <?= e($item['nome']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="cartao-pedido__rodape">
                                <span><?= e(FORMAS_PAGAMENTO[$pedido['pagamento']]['nome'] ?? '') ?> · <strong><?= brl((float) $pedido['total']) ?></strong></span>
                                <a class="link-seta" href="pedido.php?codigo=<?= e($pedido['codigo']) ?>">Ver detalhes <?= icone('seta') ?></a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <aside class="conta__dados">
            <div class="cartao">
                <h2>Meus dados</h2>
                <dl class="dados-lista">
                    <div><dt>Nome</dt><dd><?= e($usuario['nome']) ?></dd></div>
                    <div><dt>E-mail</dt><dd><?= e($usuario['email']) ?></dd></div>
                    <div><dt>CPF</dt><dd><?= e(mascarar_cpf($usuario['cpf'])) ?></dd></div>
                    <div><dt>Celular</dt><dd><?= e(formatar_telefone($usuario['telefone'])) ?></dd></div>
                    <div><dt>Cliente desde</dt><dd><?= e(date_create($usuario['criado_em'])->format('d/m/Y')) ?></dd></div>
                </dl>
                <form method="post" action="sair.php">
                    <?= campo_csrf() ?>
                    <button class="botao botao--contorno botao--bloco" type="submit"><?= icone('sair') ?> Sair da conta</button>
                </form>
            </div>
            <div class="cartao cartao--destaque">
                <h2>Precisa de ajuda?</h2>
                <p>Dúvidas sobre um pedido, garantia ou instalação: fale com a equipe.</p>
                <a class="botao botao--claro botao--bloco" href="contato.php?assunto=pedido">Falar com o suporte</a>
            </div>
        </aside>
    </div>
</section>

<?php require APP . '/views/rodape.php'; ?>
