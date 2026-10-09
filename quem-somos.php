<?php
require __DIR__ . '/app/bootstrap.php';

$titulo = 'Quem somos';
$subtitulo = 'A mais nova loja futurística de computadores.';
$descricao = 'Conheça a Proware e a equipe que ajuda você a montar o computador certo para as suas necessidades.';
$pagina = 'sobre';


require APP . '/views/topo.php';
parcial('topo-pagina', compact('titulo', 'subtitulo'));
?>

<section class="secao">
    <div class="container sobre">
        <div class="sobre__texto">
            <p class="sobretitulo">Nossa missão</p>
            <h2 class="secao__titulo">Ajudar você a montar o computador certo para as suas necessidades</h2>
            <p>Nós somos a Proware. Acreditamos que ter um bom computador não deveria depender de saber de cor o soquete de cada processador ou a potência de cada fonte.</p>
            <p>Por isso criamos um configurador que confere a compatibilidade de cada peça na hora, calcula o consumo de energia e mostra o preço final sem surpresas. Se preferir, é só escolher um dos nossos PCs prontos.</p>
            <p>Depois do pedido, cada computador é montado com cuidado, passa por 24 horas de testes de estresse e sai daqui pronto para ligar.</p>
        </div>
        <figure class="sobre__imagem">
            <img src="assets/img/banners/setup-neon.jpg" alt="Setup gamer com gabinete iluminado em RGB, monitor e teclado sob luz roxa" width="1920" height="1440" loading="lazy">
        </figure>
    </div>
</section>

<section class="secao secao--alternada">
    <div class="container">
        <div class="secao__cabecalho secao__cabecalho--centro">
            <div>
                <p class="sobretitulo">No que acreditamos</p>
                <h2 class="secao__titulo">O que você pode esperar da gente</h2>
            </div>
        </div>
        <div class="grade-valores">
            <div class="valor">
                <span class="valor__icone"><?= icone('check') ?></span>
                <h3>Peças que funcionam juntas</h3>
                <p>Nenhum PC sai daqui com peça incompatível. O configurador bloqueia combinações que não funcionam e a equipe confere tudo de novo antes da montagem.</p>
            </div>
            <div class="valor">
                <span class="valor__icone"><?= icone('pix') ?></span>
                <h3>Preço transparente</h3>
                <p>O preço é a soma das peças. Montagem, testes e frete já estão inclusos, sem taxa escondida no fim da compra.</p>
            </div>
            <div class="valor">
                <span class="valor__icone"><?= icone('suporte') ?></span>
                <h3>Atendimento de verdade</h3>
                <p>Ficou em dúvida entre duas placas de vídeo? Fale com a gente. Respondemos como amigos que entendem do assunto, não como robôs.</p>
            </div>
        </div>
    </div>
</section>

<section class="secao">
    <div class="container">
        <div class="secao__cabecalho secao__cabecalho--centro">
            <div>
                <p class="sobretitulo">Equipe</p>
                <h2 class="secao__titulo">Quem faz a Proware</h2>
                <p class="secao__subtitulo">Oito amigos apaixonados por tecnologia que decidiram transformar uma ideia em loja.</p>
            </div>
        </div>
        <ul class="grade-equipe">
            <?php foreach (EQUIPE_ORIGINAL as $i => $nome): ?>
                <li class="membro">
                    <span class="membro__iniciais iniciais--<?= $i % 4 ?>" aria-hidden="true"><?= e(iniciais($nome)) ?></span>
                    <h3><?= e($nome) ?></h3>
                    <p>Equipe fundadora</p>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<section class="secao secao--alternada">
    <div class="container historia">
        <img class="historia__logo" src="assets/img/logo-original.jpg" alt="Primeira versão do logo da Proware: um monitor com a palavra PROWARE" width="280" height="280" loading="lazy">
        <div>
            <p class="sobretitulo">Nossa história</p>
            <h2 class="secao__titulo">De projeto de escola a loja completa</h2>
            <p>A Proware começou no ensino médio, como um trabalho em grupo: quatro páginas em HTML, um slider em jQuery e muita vontade de criar algo nosso. O logo desta seção é daquela época.</p>
            <p>Esta é a versão 2.0, refeita do zero com as mesmas linguagens (HTML, CSS, JavaScript e PHP), agora com configurador de PC, carrinho, cadastro de clientes, pedidos e painel administrativo.</p>
        </div>
    </div>
</section>

<section class="secao">
    <div class="container">
        <div class="faixa-chamada">
            <span class="faixa-chamada__icone"><?= icone('mensagem') ?></span>
            <div>
                <h2>Vamos montar o seu PC juntos?</h2>
                <p>Conte para a gente o que você precisa e o quanto quer investir. A gente sugere a melhor configuração.</p>
            </div>
            <a class="botao botao--primario" href="contato.php">Falar com a equipe <?= icone('seta') ?></a>
        </div>
    </div>
</section>

<?php require APP . '/views/rodape.php'; ?>
