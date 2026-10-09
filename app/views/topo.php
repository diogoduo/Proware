<?php
/**
 * Início de todas as páginas.
 * Variáveis aceitas: $titulo, $descricao, $pagina (item ativo do menu), $scripts.
 */
$titulo = $titulo ?? LOJA['slogan'];
$descricao = $descricao ?? 'Monte seu PC peça por peça com checagem de compatibilidade ou escolha um PC pronto. Montagem e testes inclusos, frete grátis e 5% de desconto no Pix.';
$pagina = $pagina ?? '';
$scripts = $scripts ?? [];
$usuarioLogado = usuario_logado();

$menu = [
    'inicio'      => ['Início', 'index.php'],
    'montar'      => ['Monte seu PC', 'montar.php'],
    'pcs'         => ['PCs Prontos', 'pcs-prontos.php'],
    'perifericos' => ['Periféricos', 'perifericos.php'],
    'sobre'       => ['Quem somos', 'quem-somos.php'],
    'contato'     => ['Contato', 'contato.php'],
];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php if (defined('NA_AREA_ADMIN')): ?><base href="../"><?php endif; ?>
    <title><?= e($titulo) ?> | Proware</title>
    <meta name="description" content="<?= e($descricao) ?>">
    <meta name="theme-color" content="#0f0b18">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Proware">
    <meta property="og:title" content="<?= e($titulo) ?> | Proware">
    <meta property="og:description" content="<?= e($descricao) ?>">
    <meta property="og:locale" content="pt_BR">
    <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap">
    <link rel="stylesheet" href="<?= asset('assets/css/estilo.css') ?>">
    <script src="<?= asset('assets/js/app.js') ?>" defer></script>
    <?php foreach ($scripts as $script): ?>
        <script src="<?= asset($script) ?>" defer></script>
    <?php endforeach; ?>
</head>
<body>
<a class="pular-conteudo" href="#conteudo">Pular para o conteúdo</a>

<div class="faixa-topo">
    <div class="container faixa-topo__itens">
        <span><?= icone('caminhao') ?> Frete grátis para todo o Brasil</span>
        <span><?= icone('pix') ?> 5% de desconto no Pix</span>
        <span><?= icone('cartao') ?> Até <?= PARCELAS ?>x sem juros</span>
    </div>
</div>

<header class="cabecalho" data-cabecalho>
    <div class="container cabecalho__barra">
        <button class="botao-icone cabecalho__menu" type="button" data-alternar-menu
                aria-controls="menu-principal" aria-expanded="false" aria-label="Abrir menu">
            <?= icone('menu', 'icone-abrir') ?><?= icone('fechar', 'icone-fechar') ?>
        </button>

        <a class="logo" href="index.php" aria-label="Proware, página inicial">
            <span class="logo__pro" aria-hidden="true"><span>P</span><span>R</span><span>O</span></span><span class="logo__ware" aria-hidden="true">WARE</span>
        </a>

        <nav class="menu" id="menu-principal" aria-label="Principal" data-menu>
            <ul class="menu__lista">
                <?php foreach ($menu as $chave => [$rotulo, $link]): ?>
                    <li>
                        <a class="menu__link" href="<?= e($link) ?>"<?= $pagina === $chave ? ' aria-current="page"' : '' ?>>
                            <?= e($rotulo) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="cabecalho__acoes">
            <?php if ($usuarioLogado && $usuarioLogado['admin']): ?>
                <a class="cabecalho__painel" href="admin/index.php"><?= icone('painel') ?><span>Painel</span></a>
            <?php endif; ?>
            <?php if ($usuarioLogado): ?>
                <a class="cabecalho__conta" href="conta.php"<?= $pagina === 'conta' ? ' aria-current="page"' : '' ?>>
                    <?= icone('usuario') ?>
                    <span>Olá, <?= e(primeiro_nome($usuarioLogado['nome'])) ?></span>
                </a>
            <?php else: ?>
                <a class="cabecalho__conta" href="login.php"<?= $pagina === 'conta' ? ' aria-current="page"' : '' ?>>
                    <?= icone('usuario') ?>
                    <span>Entrar</span>
                </a>
            <?php endif; ?>

            <button class="botao-carrinho" type="button" data-abrir-carrinho aria-label="Abrir carrinho">
                <?= icone('carrinho') ?>
                <span class="botao-carrinho__contador" data-contador-carrinho hidden>0</span>
            </button>
        </div>
    </div>
</header>

<main id="conteudo" tabindex="-1">
<?php parcial('alertas'); ?>
