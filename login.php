<?php
require __DIR__ . '/app/bootstrap.php';

$voltar = destino_seguro($_GET['voltar'] ?? $_POST['voltar'] ?? null);
if (usuario_logado()) {
    redirecionar($voltar);
}

$titulo = 'Entrar';
$pagina = 'conta';
$email = '';
$erro = '';

if (requisicao_post()) {
    $email = post('email');
    $bloqueio = minutos_de_bloqueio($email);
    if (!csrf_valido()) {
        $erro = 'Sua sessão expirou. Tente novamente.';
    } elseif ($bloqueio > 0) {
        $erro = "Muitas tentativas com este e-mail. Tente de novo em $bloqueio "
            . ($bloqueio === 1 ? 'minuto.' : 'minutos.');
    } elseif ($usuario = autenticar($email, post('senha', false))) {
        limpar_falhas_login($email);
        iniciar_sessao($usuario);
        flash('sucesso', 'Que bom ver você, ' . primeiro_nome($usuario['nome']) . '!');
        redirecionar($usuario['admin'] && $voltar === 'conta.php' ? 'admin/index.php' : $voltar);
    } else {
        registrar_falha_login($email);
        $erro = 'E-mail ou senha incorretos.';
    }
}

require APP . '/views/topo.php';
?>

<section class="secao secao--compacta">
    <div class="container autenticacao">
        <div class="autenticacao__painel">
            <p class="sobretitulo">Bem-vindo de volta</p>
            <h2>Seu PC novo está a poucos cliques</h2>
            <ul class="lista-check">
                <li><?= icone('check') ?> Acompanhe seus pedidos</li>
                <li><?= icone('check') ?> Compre mais rápido com seus dados salvos</li>
                <li><?= icone('check') ?> Fale com o suporte sobre suas compras</li>
            </ul>
        </div>

        <div class="cartao-formulario">
            <h1>Entrar na sua conta</h1>
            <p class="cartao-formulario__sub">Ainda não tem conta? <a href="cadastro.php?voltar=<?= e(urlencode($voltar)) ?>">Cadastre-se grátis</a></p>

            <?php if ($erro): ?>
                <div class="alerta alerta--erro" role="alert"><?= icone('alerta') ?><p><?= e($erro) ?></p></div>
            <?php endif; ?>

            <form class="formulario" method="post" action="login.php" novalidate data-validar>
                <?= campo_csrf() ?>
                <input type="hidden" name="voltar" value="<?= e($voltar) ?>">
                <div class="campo">
                    <label for="email">E-mail</label>
                    <input id="email" name="email" type="email" autocomplete="email" required value="<?= e($email) ?>" autofocus>
                </div>
                <div class="campo">
                    <label for="senha">Senha</label>
                    <div class="campo-senha">
                        <input id="senha" name="senha" type="password" autocomplete="current-password" required>
                        <button class="campo-senha__alternar" type="button" data-mostrar-senha aria-label="Mostrar senha" aria-pressed="false"><?= icone('olho') ?></button>
                    </div>
                </div>
                <button class="botao botao--primario botao--grande botao--bloco" type="submit">Entrar <?= icone('seta') ?></button>
            </form>
        </div>
    </div>
</section>

<?php require APP . '/views/rodape.php'; ?>
