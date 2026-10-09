<?php
require __DIR__ . '/app/bootstrap.php';

if (usuario_logado()) {
    redirecionar('editar-conta.php');
}

$titulo = 'Esqueci minha senha';
$pagina = 'conta';
$email = '';
$erro = '';
$enviado = false;

if (requisicao_post()) {
    $email = post('email');
    // Conta cada pedido para não deixar ninguém lotar a caixa de entrada de outra pessoa.
    $chaveLimite = 'senha:' . $email;

    if (!csrf_valido()) {
        $erro = 'Sua sessão expirou. Tente novamente.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } elseif (($minutos = minutos_de_bloqueio($chaveLimite)) > 0) {
        $erro = "Já enviamos vários links para este e-mail. Tente de novo em $minutos " . ($minutos === 1 ? 'minuto.' : 'minutos.');
    } else {
        registrar_falha_login($chaveLimite);
        $usuario = usuario_por_email($email);
        if ($usuario) {
            email_recuperacao_senha($usuario, criar_link_redefinicao($usuario));
        }
        // A resposta é sempre a mesma, exista ou não uma conta com o e-mail.
        $enviado = true;
    }
}

require APP . '/views/topo.php';
?>

<section class="secao secao--compacta">
    <div class="container autenticacao autenticacao--simples">
        <div class="cartao-formulario">
            <?php if ($enviado): ?>
                <span class="estado-vazio__icone"><?= icone('email') ?></span>
                <h1>Confira seu e-mail</h1>
                <p class="cartao-formulario__sub">Se houver uma conta com <strong><?= e($email) ?></strong>, enviamos um link para criar uma nova senha. Ele vale por 1 hora. Não achou? Olhe também a caixa de spam.</p>
                <?php if (!envio_de_email_configurado()): ?>
                    <div class="alerta alerta--info">
                        <?= icone('info') ?>
                        <p>Neste ambiente os e-mails não são enviados de verdade: a mensagem fica registrada no painel administrativo, em <strong>E-mails</strong>.</p>
                    </div>
                <?php endif; ?>
                <a class="botao botao--contorno botao--bloco" href="login.php">Voltar para o login</a>
            <?php else: ?>
                <h1>Esqueci minha senha</h1>
                <p class="cartao-formulario__sub">Informe o e-mail da sua conta e enviaremos um link para você criar uma nova senha.</p>

                <?php if ($erro): ?>
                    <div class="alerta alerta--erro" role="alert"><?= icone('alerta') ?><p><?= e($erro) ?></p></div>
                <?php endif; ?>

                <form class="formulario" method="post" action="esqueci-senha.php" novalidate data-validar>
                    <?= campo_csrf() ?>
                    <div class="campo">
                        <label for="email">E-mail</label>
                        <input id="email" name="email" type="email" autocomplete="email" required autofocus value="<?= e($email) ?>">
                    </div>
                    <button class="botao botao--primario botao--grande botao--bloco" type="submit">Enviar link <?= icone('seta') ?></button>
                </form>
                <p class="formulario__nota"><a href="login.php">Lembrei a senha, voltar para o login</a></p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require APP . '/views/rodape.php'; ?>
