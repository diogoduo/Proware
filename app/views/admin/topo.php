<?php
/**
 * Início das páginas do painel administrativo.
 * Variáveis: $titulo, $subtitulo (opcional), $secao (item ativo do menu), $admin (usuário logado).
 * O <base href="../"> faz todos os links partirem da raiz do site.
 */
$subtitulo = $subtitulo ?? '';
$pendentes = contar_pedidos_por_status();
$novasMensagens = contar_mensagens_por_status()['nova'];
$aguardandoAcao = $pendentes['pago'] + $pendentes['montagem'];

$menuAdmin = [
    'inicio'    => ['Visão geral', 'admin/index.php', 'painel', 0],
    'pedidos'   => ['Pedidos', 'admin/pedidos.php', 'pacote', $aguardandoAcao],
    'mensagens' => ['Mensagens', 'admin/mensagens.php', 'mensagem', $novasMensagens],
    'clientes'  => ['Clientes', 'admin/clientes.php', 'usuarios', 0],
    'catalogo'  => ['Catálogo e preços', 'admin/catalogo.php', 'etiqueta', 0],
];
if (!$admin['somente_leitura']) {
    $menuAdmin['emails'] = ['E-mails', 'admin/emails.php', 'email', 0];
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <base href="../">
    <title><?= e($titulo) ?> | Painel Proware</title>
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#0f0b18">
    <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap">
    <link rel="stylesheet" href="<?= asset('assets/css/estilo.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
    <script src="<?= asset('assets/js/admin.js') ?>" defer></script>
</head>
<body class="admin<?= $admin['somente_leitura'] ? ' admin--somente-leitura' : '' ?>">
<a class="pular-conteudo" href="<?= e($menuAdmin[$secao][1] ?? 'admin/index.php') ?>#conteudo">Pular para o conteúdo</a>

<div class="admin-layout">
    <aside class="admin-menu">
        <a class="logo admin-menu__logo" href="admin/index.php" aria-label="Painel Proware">
            <span class="logo__pro" aria-hidden="true"><span>P</span><span>R</span><span>O</span></span><span class="logo__ware" aria-hidden="true">WARE</span>
            <span class="admin-menu__selo">Admin</span>
        </a>

        <nav aria-label="Painel">
            <ul class="admin-menu__lista">
                <?php foreach ($menuAdmin as $chave => [$rotulo, $link, $icone, $contador]): ?>
                    <li>
                        <a class="admin-menu__link" href="<?= e($link) ?>"<?= $secao === $chave ? ' aria-current="page"' : '' ?>>
                            <?= icone($icone) ?>
                            <span><?= e($rotulo) ?></span>
                            <?php if ($contador > 0): ?>
                                <span class="admin-menu__contador" aria-label="<?= $contador ?> pendentes"><?= $contador ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="admin-menu__rodape">
            <a class="admin-menu__link" href="index.php"><?= icone('loja') ?><span>Ver a loja</span></a>
            <div class="admin-menu__usuario">
                <span class="admin-menu__avatar" aria-hidden="true"><?= e(mb_strtoupper_seguro(mb_substr_seguro($admin['nome'], 0, 1))) ?></span>
                <span>
                    <strong><?= e(primeiro_nome($admin['nome'])) ?></strong>
                    <small><?= e($admin['email']) ?></small>
                </span>
            </div>
            <form method="post" action="sair.php">
                <?= campo_csrf() ?>
                <button class="admin-menu__link admin-menu__sair" type="submit"><?= icone('sair') ?><span>Sair</span></button>
            </form>
        </div>
    </aside>

    <main class="admin-conteudo" id="conteudo" tabindex="-1">
        <header class="admin-topo">
            <div>
                <h1><?= e($titulo) ?></h1>
                <?php if ($subtitulo): ?>
                    <p><?= e($subtitulo) ?></p>
                <?php endif; ?>
            </div>
            <?php if (!empty($acoesTopo)): ?>
                <div class="admin-topo__acoes"><?= $acoesTopo ?></div>
            <?php endif; ?>
        </header>
        <?php if ($admin['somente_leitura']): ?>
            <div class="alerta alerta--info admin-aviso-demo">
                <?= icone('olho') ?>
                <p><strong>Acesso de demonstração.</strong> Você pode ver todo o painel, mas as alterações estão desativadas.</p>
            </div>
        <?php endif; ?>
        <?php parcial('alertas'); ?>
