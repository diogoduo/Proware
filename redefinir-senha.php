<?php
require __DIR__ . '/app/bootstrap.php';

$token = get('token') ?: post('token');
$usuario = usuario_do_token($token);

$titulo = 'Criar nova senha';
$pagina = 'conta';
$erros = [];

if ($usuario && requisicao_post()) {
    $senha = post('senha', false);
    $erros = csrf_valido()
        ? validar_nova_senha($senha, post('confirmar_senha', false))
        : ['geral' => 'Sua sessão expirou. Tente novamente.'];

    if (!$erros && redefinir_senha_com_token($token, $senha)) {
        flash('sucesso', 'Senha alterada! Agora é só entrar com a nova senha.');
        redirecionar('login.php');
    }
}

require APP . '/views/topo.php';
?>

<section class="secao secao--compacta">
    <div class="container autenticacao autenticacao--simples">
        <div class="cartao-formulario">
            <?php if (!$usuario): ?>
                <span class="estado-vazio__icone"><?= icone('alerta') ?></span>
                <h1>Este link não vale mais</h1>
                <p class="cartao-formulario__sub">Links de redefinição valem por 1 hora e só podem ser usados uma vez. Peça um novo link para continuar.</p>
                <a class="botao botao--primario botao--bloco" href="esqueci-senha.php">Pedir um novo link</a>
            <?php else: ?>
                <h1>Criar nova senha</h1>
                <p class="cartao-formulario__sub">Para a conta <strong><?= e($usuario['email']) ?></strong>.</p>

                <?php if (!empty($erros['geral'])): ?>
                    <div class="alerta alerta--erro" role="alert"><?= icone('alerta') ?><p><?= e($erros['geral']) ?></p></div>
                <?php endif; ?>

                <form class="formulario" method="post" action="redefinir-senha.php" novalidate data-validar>
                    <?= campo_csrf() ?>
                    <input type="hidden" name="token" value="<?= e($token) ?>">
                    <div class="campo<?= isset($erros['senha']) ? ' campo--erro' : '' ?>">
                        <label for="senha">Nova senha</label>
                        <div class="campo-senha">
                            <input id="senha" name="senha" type="password" autocomplete="new-password" required minlength="8" autofocus aria-describedby="senha-dica<?= isset($erros['senha']) ? ' erro-senha' : '' ?>"<?= isset($erros['senha']) ? ' aria-invalid="true"' : '' ?>>
                            <button class="campo-senha__alternar" type="button" data-mostrar-senha aria-label="Mostrar senha" aria-pressed="false"><?= icone('olho') ?></button>
                        </div>
                        <p class="campo__dica" id="senha-dica">Mínimo de 8 caracteres, com letras e números.</p>
                        <?= erro_campo($erros, 'senha') ?>
                    </div>
                    <div class="campo<?= isset($erros['confirmar_senha']) ? ' campo--erro' : '' ?>">
                        <label for="confirmar_senha">Confirme a nova senha</label>
                        <input id="confirmar_senha" name="confirmar_senha" type="password" autocomplete="new-password" required data-igual-a="senha"<?= attr_erro($erros, 'confirmar_senha') ?>>
                        <?= erro_campo($erros, 'confirmar_senha') ?>
                    </div>
                    <button class="botao botao--primario botao--grande botao--bloco" type="submit">Salvar nova senha</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require APP . '/views/rodape.php'; ?>
