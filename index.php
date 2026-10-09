<?php
require __DIR__ . '/app/bootstrap.php';

$titulo = LOJA['slogan'];
$pagina = 'inicio';

$pcsDestaque = array_filter(produtos_do_tipo('pc'), fn ($p) => $p['destaque']);
$perifericos = array_filter(produtos_do_tipo('periferico'), fn ($p) => $p['destaque']);
$exemplo = perfis()['jogos']['pecas'];
$equipe = ['barbara', 'caio', 'diogo', 'eduardo', 'estela', 'gabriel', 'luan', 'maria-eduarda'];

require APP . '/views/topo.php';
?>

<h1 class="visualmente-oculto">Proware: PCs gamer, workstations e periféricos montados do seu jeito</h1>

<section class="destaque" aria-roledescription="carrossel" aria-label="Destaques da loja" data-carrossel>
    <div class="destaque__slides">
        <article class="slide is-ativo" aria-roledescription="slide" aria-label="1 de 3" data-slide>
            <img class="slide__fundo" src="assets/img/banners/setup-neon.jpg" alt="" width="1920" height="1440" fetchpriority="high">
            <div class="container slide__conteudo">
                <p class="sobretitulo">Monte seu PC</p>
                <h2 class="slide__titulo">O PC dos seus sonhos, <span class="texto-gradiente">peça por peça.</span></h2>
                <p class="slide__texto">Você escolhe cada componente e a gente confere a compatibilidade na hora. Depois montamos, testamos e entregamos na sua casa.</p>
                <div class="slide__acoes">
                    <a class="botao botao--primario botao--grande" href="montar.php">Começar a montar <?= icone('seta') ?></a>
                    <a class="botao botao--claro botao--grande" href="pcs-prontos.php">Ver PCs prontos</a>
                </div>
            </div>
        </article>

        <article class="slide" aria-roledescription="slide" aria-label="2 de 3" data-slide>
            <img class="slide__fundo" src="assets/img/banners/setup-gamer.jpg" alt="" width="1000" height="450" loading="lazy">
            <div class="container slide__conteudo">
                <p class="sobretitulo">Ofertas da semana</p>
                <h2 class="slide__titulo">PCs prontos com <span class="texto-gradiente">até 20% OFF.</span></h2>
                <p class="slide__texto">Configurações equilibradas, montadas e testadas pela nossa equipe. É só ligar e jogar.</p>
                <div class="slide__acoes">
                    <a class="botao botao--primario botao--grande" href="pcs-prontos.php">Ver ofertas <?= icone('seta') ?></a>
                </div>
            </div>
        </article>

        <article class="slide slide--lateral" aria-roledescription="slide" aria-label="3 de 3" data-slide>
            <img class="slide__fundo" src="assets/img/banners/mesa-gamer.jpg" alt="" width="500" height="500" loading="lazy">
            <div class="container slide__conteudo">
                <p class="sobretitulo">Periféricos</p>
                <h2 class="slide__titulo">Complete o seu <span class="texto-gradiente">setup.</span></h2>
                <p class="slide__texto">Monitor ultrawide de 165 Hz, teclado mecânico ABNT2 e mouse RGB com preço de oferta.</p>
                <div class="slide__acoes">
                    <a class="botao botao--primario botao--grande" href="perifericos.php">Ver periféricos <?= icone('seta') ?></a>
                </div>
            </div>
        </article>
    </div>

    <div class="container destaque__controles">
        <button class="botao-icone botao-icone--vidro" type="button" data-anterior aria-label="Slide anterior"><?= icone('esquerda') ?></button>
        <div class="destaque__pontos" data-pontos></div>
        <button class="botao-icone botao-icone--vidro" type="button" data-proximo aria-label="Próximo slide"><?= icone('direita') ?></button>
        <button class="botao-icone botao-icone--vidro destaque__pausa" type="button" data-pausar aria-label="Pausar destaques" aria-pressed="false"><?= icone('pausar', 'icone-pausar') ?><?= icone('tocar', 'icone-tocar') ?></button>
    </div>
</section>

<?php parcial('beneficios'); ?>

<section class="secao">
    <div class="container como-funciona">
        <div class="como-funciona__texto">
            <p class="sobretitulo">Como funciona</p>
            <h2 class="secao__titulo">Montar um PC nunca foi tão simples</h2>
            <p class="secao__subtitulo">Nada de ficar pesquisando se uma peça encaixa na outra. O nosso configurador faz isso por você.</p>

            <ol class="passos">
                <li class="passo">
                    <span class="passo__numero">1</span>
                    <div>
                        <h3>Diga para que vai usar</h3>
                        <p>Jogos, trabalho ou estudos: comece por um perfil pronto ou do zero.</p>
                    </div>
                </li>
                <li class="passo">
                    <span class="passo__numero">2</span>
                    <div>
                        <h3>Escolha as peças</h3>
                        <p>Mostramos só o que é compatível e calculamos o consumo de energia.</p>
                    </div>
                </li>
                <li class="passo">
                    <span class="passo__numero">3</span>
                    <div>
                        <h3>A gente monta e entrega</h3>
                        <p>Montagem profissional, testes de estresse e frete grátis.</p>
                    </div>
                </li>
            </ol>

            <a class="botao botao--primario botao--grande" href="montar.php">Montar meu PC agora <?= icone('seta') ?></a>
        </div>

        <div class="previa-montagem" aria-hidden="true">
            <div class="previa-montagem__topo">
                <span>Sua montagem</span>
                <span class="etiqueta etiqueta--sucesso"><?= icone('check') ?> Tudo compatível</span>
            </div>
            <ul>
                <?php foreach (categorias() as $categoria => $info): ?>
                    <?php if (!$peca = componente($exemplo[$categoria] ?? '')) continue; ?>
                    <li>
                        <span class="previa-montagem__icone"><?= icone($info['icone']) ?></span>
                        <span class="previa-montagem__peca">
                            <small><?= e($info['nome']) ?></small>
                            <?= e($peca['nome']) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="previa-montagem__total">
                <span>Total no Pix</span>
                <strong><?= brl(preco_pix(preco_das_pecas($exemplo))) ?></strong>
            </div>
        </div>
    </div>
</section>

<?php if ($pcsDestaque): ?>
<section class="secao secao--alternada">
    <div class="container">
        <div class="secao__cabecalho">
            <div>
                <p class="sobretitulo">PCs Prontos</p>
                <h2 class="secao__titulo">Os mais procurados</h2>
            </div>
            <a class="link-seta" href="pcs-prontos.php">Ver todos os PCs <?= icone('seta') ?></a>
        </div>
        <div class="grade-produtos">
            <?php foreach ($pcsDestaque as $produto): ?>
                <?php parcial('card-produto', ['produto' => $produto]); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="secao">
    <div class="container">
        <div class="secao__cabecalho secao__cabecalho--centro">
            <div>
                <p class="sobretitulo">Por onde começar</p>
                <h2 class="secao__titulo">Para que você vai usar o seu PC?</h2>
                <p class="secao__subtitulo">Escolha um perfil e receba uma configuração equilibrada. Depois é só ajustar do seu jeito.</p>
            </div>
        </div>
        <div class="grade-perfis">
            <?php foreach (perfis() as $chave => $perfil): ?>
                <a class="perfil" href="montar.php?perfil=<?= e($chave) ?>">
                    <span class="perfil__icone"><?= icone($perfil['icone']) ?></span>
                    <h3><?= e($perfil['nome']) ?></h3>
                    <p><?= e($perfil['descricao']) ?></p>
                    <span class="perfil__preco">a partir de <strong><?= brl(preco_pix(preco_das_pecas($perfil['pecas']))) ?></strong></span>
                    <span class="perfil__seta"><?= icone('seta') ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if ($perifericos): ?>
<section class="secao secao--alternada" id="perifericos">
    <div class="container">
        <div class="secao__cabecalho">
            <div>
                <p class="sobretitulo">Periféricos em oferta</p>
                <h2 class="secao__titulo">Complete o seu setup</h2>
            </div>
            <a class="link-seta" href="perifericos.php">Ver periféricos <?= icone('seta') ?></a>
        </div>
        <div class="grade-produtos">
            <?php foreach ($perifericos as $produto): ?>
                <?php parcial('card-produto', ['produto' => $produto]); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="secao">
    <div class="container chamada">
        <div class="chamada__texto">
            <p class="sobretitulo">Quem somos</p>
            <h2>Uma loja feita por quem é apaixonado por tecnologia</h2>
            <p>A Proware nasceu da vontade de oito amigos de ajudar as pessoas a montar o computador certo, sem complicação e sem gastar à toa.</p>
            <div class="chamada__acoes">
                <a class="botao botao--claro" href="quem-somos.php">Conheça a equipe</a>
                <a class="botao botao--vidro" href="contato.php"><?= icone('mensagem') ?> Tirar uma dúvida</a>
            </div>
        </div>
        <ul class="avatares" aria-hidden="true">
            <?php foreach ($equipe as $pessoa): ?>
                <li><img src="assets/img/equipe/<?= e($pessoa) ?>.jpg" alt="" width="72" height="72" loading="lazy"></li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<?php require APP . '/views/rodape.php'; ?>
