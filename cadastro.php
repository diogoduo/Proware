<?php
require __DIR__ . '/app/bootstrap.php';

$voltar = destino_seguro($_GET['voltar'] ?? $_POST['voltar'] ?? null);
if (usuario_logado()) {
    redirecionar($voltar);
}

$titulo = 'Criar conta';
$pagina = 'conta';
$dados = ['nome' => '', 'email' => '', 'cpf' => '', 'telefone' => '', 'senha' => '', 'confirmar_senha' => ''];
$erros = [];

if (requisicao_post()) {
    foreach (array_keys($dados) as $campo) {
        $dados[$campo] = post($campo, !in_array($campo, ['senha', 'confirmar_senha'], true));
    }

    $erros = csrf_valido() ? validar_cadastro($dados) : ['geral' => 'Sua sessão expirou. Tente novamente.'];

    if (!$erros) {
        try {
            $usuario = criar_usuario($dados);
            iniciar_sessao($usuario);
            flash('sucesso', 'Conta criada! Boas-vindas à Proware, ' . primeiro_nome($usuario['nome']) . '.');
            redirecionar($voltar);
        } catch (RuntimeException) {
            $erros['email'] = 'Já existe uma conta com este e-mail. Que tal entrar?';
        }
    }
}

require APP . '/views/topo.php';
?>

<section class="secao secao--compacta">
    <div class="container autenticacao">
        <div class="autenticacao__painel">
            <p class="sobretitulo">Crie sua conta</p>
            <h2>Leva menos de um minuto</h2>
            <ul class="lista-check">
                <li><?= icone('check') ?> Acompanhe a montagem e o envio</li>
                <li><?= icone('check') ?> Histórico de todos os pedidos</li>
                <li><?= icone('cadeado') ?> Senha protegida com criptografia</li>
            </ul>
        </div>

        <div class="cartao-formulario">
            <h1>Criar conta</h1>
            <p class="cartao-formulario__sub">Já tem conta? <a href="login.php?voltar=<?= e(urlencode($voltar)) ?>">Entrar</a></p>

            <?php if (!empty($erros['geral'])): ?>
                <div class="alerta alerta--erro" role="alert"><?= icone('alerta') ?><p><?= e($erros['geral']) ?></p></div>
            <?php elseif ($erros): ?>
                <div class="alerta alerta--erro" role="alert"><?= icone('alerta') ?><p>Confira os campos destacados abaixo.</p></div>
            <?php endif; ?>

            <form class="formulario" method="post" action="cadastro.php" novalidate data-validar>
                <?= campo_csrf() ?>
                <input type="hidden" name="voltar" value="<?= e($voltar) ?>">

                <div class="campo<?= isset($erros['nome']) ? ' campo--erro' : '' ?>">
                    <label for="nome">Nome completo</label>
                    <input id="nome" name="nome" type="text" autocomplete="name" required value="<?= e($dados['nome']) ?>"<?= attr_erro($erros, 'nome') ?>>
                    <?= erro_campo($erros, 'nome') ?>
                </div>
                <div class="campo<?= isset($erros['email']) ? ' campo--erro' : '' ?>">
                    <label for="email">E-mail</label>
                    <input id="email" name="email" type="email" autocomplete="email" required value="<?= e($dados['email']) ?>"<?= attr_erro($erros, 'email') ?>>
                    <?= erro_campo($erros, 'email') ?>
                </div>
                <div class="formulario__linha">
                    <div class="campo<?= isset($erros['cpf']) ? ' campo--erro' : '' ?>">
                        <label for="cpf">CPF</label>
                        <input id="cpf" name="cpf" type="text" inputmode="numeric" autocomplete="off" required placeholder="000.000.000-00" data-mascara="cpf" value="<?= e($dados['cpf']) ?>"<?= attr_erro($erros, 'cpf') ?>>
                        <?= erro_campo($erros, 'cpf') ?>
                    </div>
                    <div class="campo<?= isset($erros['telefone']) ? ' campo--erro' : '' ?>">
                        <label for="telefone">Celular</label>
                        <input id="telefone" name="telefone" type="tel" autocomplete="tel-national" required placeholder="(00) 00000-0000" data-mascara="telefone" value="<?= e($dados['telefone']) ?>"<?= attr_erro($erros, 'telefone') ?>>
                        <?= erro_campo($erros, 'telefone') ?>
                    </div>
                </div>
                <div class="campo<?= isset($erros['senha']) ? ' campo--erro' : '' ?>">
                    <label for="senha">Senha</label>
                    <div class="campo-senha">
                        <input id="senha" name="senha" type="password" autocomplete="new-password" required minlength="8" aria-describedby="senha-dica<?= isset($erros['senha']) ? ' erro-senha' : '' ?>"<?= isset($erros['senha']) ? ' aria-invalid="true"' : '' ?>>
                        <button class="campo-senha__alternar" type="button" data-mostrar-senha aria-label="Mostrar senha" aria-pressed="false"><?= icone('olho') ?></button>
                    </div>
                    <p class="campo__dica" id="senha-dica">Mínimo de 8 caracteres, com letras e números.</p>
                    <?= erro_campo($erros, 'senha') ?>
                </div>
                <div class="campo<?= isset($erros['confirmar_senha']) ? ' campo--erro' : '' ?>">
                    <label for="confirmar_senha">Confirme a senha</label>
                    <input id="confirmar_senha" name="confirmar_senha" type="password" autocomplete="new-password" required data-igual-a="senha"<?= attr_erro($erros, 'confirmar_senha') ?>>
                    <?= erro_campo($erros, 'confirmar_senha') ?>
                </div>
                <button class="botao botao--primario botao--grande botao--bloco" type="submit">Criar minha conta <?= icone('seta') ?></button>
                <p class="formulario__nota"><?= icone('cadeado') ?> Seus dados são usados só para processar seus pedidos.</p>
            </form>
        </div>
    </div>
</section>

<?php require APP . '/views/rodape.php'; ?>
