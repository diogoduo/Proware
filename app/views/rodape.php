</main>

<footer class="rodape">
    <div class="container rodape__grade">
        <div class="rodape__marca">
            <a class="logo logo--rodape" href="index.php" aria-label="Proware, página inicial">
                <span class="logo__pro" aria-hidden="true"><span>P</span><span>R</span><span>O</span></span><span class="logo__ware" aria-hidden="true">WARE</span>
            </a>
            <p>Ajudamos você a montar o computador certo para as suas necessidades, com peças compatíveis, montagem caprichada e testes antes do envio.</p>
            <ul class="selos-pagamento" aria-label="Formas de pagamento">
                <li><?= icone('pix') ?> Pix</li>
                <li><?= icone('cartao') ?> Cartão</li>
                <li><?= icone('boleto') ?> Boleto</li>
            </ul>
        </div>

        <nav class="rodape__coluna" aria-label="Loja">
            <h2>Loja</h2>
            <ul>
                <li><a href="montar.php">Monte seu PC</a></li>
                <li><a href="pcs-prontos.php">PCs Prontos</a></li>
                <li><a href="perifericos.php">Periféricos</a></li>
            </ul>
        </nav>

        <nav class="rodape__coluna" aria-label="Institucional">
            <h2>Proware</h2>
            <ul>
                <li><a href="quem-somos.php">Quem somos</a></li>
                <li><a href="contato.php">Contato</a></li>
                <li><a href="contato.php#perguntas">Perguntas frequentes</a></li>
            </ul>
        </nav>

        <div class="rodape__coluna">
            <h2>Atendimento</h2>
            <ul class="rodape__contato">
                <li><?= icone('email') ?> <a href="mailto:<?= e(LOJA['email']) ?>"><?= e(LOJA['email']) ?></a></li>
                <li><?= icone('relogio') ?> <?= e(LOJA['horario']) ?></li>
                <li><?= icone('caminhao') ?> Entregamos em todo o Brasil</li>
            </ul>
        </div>
    </div>

    <div class="container rodape__base">
        <p>© <?= date('Y') ?> Proware. Desenvolvido pela Equipe Proware.</p>
        <p>Projeto de portfólio: nenhuma compra é cobrada de verdade.</p>
    </div>
</footer>

<dialog class="gaveta" id="gaveta-carrinho" aria-labelledby="gaveta-titulo" data-gaveta>
    <div class="gaveta__painel">
        <div class="gaveta__topo">
            <h2 id="gaveta-titulo">Seu carrinho</h2>
            <button class="botao-icone" type="button" data-fechar-gaveta aria-label="Fechar carrinho"><?= icone('fechar') ?></button>
        </div>

        <ul class="gaveta__lista" data-carrinho-lista></ul>

        <div class="gaveta__vazio" data-carrinho-vazio hidden>
            <span class="gaveta__vazio-icone"><?= icone('carrinho') ?></span>
            <p><strong>Seu carrinho está vazio.</strong></p>
            <p>Que tal montar um PC do seu jeito?</p>
            <a class="botao botao--primario" href="montar.php">Monte seu PC</a>
            <a class="botao botao--texto" href="pcs-prontos.php">Ver PCs prontos</a>
        </div>

        <div class="gaveta__rodape" data-carrinho-rodape>
            <dl class="linhas-resumo">
                <div><dt>Subtotal</dt><dd data-carrinho-subtotal></dd></div>
                <div><dt>Frete</dt><dd class="texto-sucesso">Grátis</dd></div>
                <div class="linhas-resumo__destaque"><dt>No Pix (5% off)</dt><dd data-carrinho-pix></dd></div>
            </dl>
            <a class="botao botao--primario botao--bloco botao--grande" href="checkout.php">Finalizar compra <?= icone('seta') ?></a>
            <button class="botao botao--texto botao--bloco" type="button" data-fechar-gaveta>Continuar comprando</button>
        </div>
    </div>
</dialog>

<template id="modelo-item-carrinho">
    <li class="item-carrinho">
        <img class="item-carrinho__imagem" src="" alt="" width="72" height="72">
        <div class="item-carrinho__info">
            <a class="item-carrinho__nome" data-campo="nome"></a>
            <p class="item-carrinho__descricao" data-campo="descricao"></p>
            <div class="item-carrinho__linha">
                <div class="quantidade">
                    <button type="button" data-acao="menos" aria-label="Diminuir quantidade"><?= icone('menos') ?></button>
                    <span data-campo="qtd" aria-live="polite"></span>
                    <button type="button" data-acao="mais" aria-label="Aumentar quantidade"><?= icone('mais') ?></button>
                </div>
                <strong data-campo="preco"></strong>
            </div>
        </div>
        <button class="botao-icone item-carrinho__remover" type="button" data-acao="remover" aria-label="Remover do carrinho"><?= icone('lixeira') ?></button>
    </li>
</template>

<div class="avisos" data-avisos role="status" aria-live="polite"></div>

<script type="application/json" id="dados-catalogo"><?= json_encode(catalogo_para_js(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
</body>
</html>
