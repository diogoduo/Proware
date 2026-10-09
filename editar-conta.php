<?php
require __DIR__ . '/app/bootstrap.php';

$usuario = exigir_login('editar-conta.php');

$titulo = 'Editar dados e senha';
$pagina = 'conta';
$dados = ['nome' => $usuario['nome'], 'telefone' => formatar_telefone($usuario['telefone'])];
$errosDados = [];
$errosSenha = [];

if (requisicao_post()) {
    $acao = post('acao');

    if (!csrf_valido()) {
        flash('erro', 'Sua sessão expirou. Tente novamente.');
        redirecionar('editar-conta.php');
    }

    if ($acao === 'dados') {
        $dados = ['nome' => post('nome'), 'telefone' => post('telefone')];
        $errosDados = validar_dados_pessoais($dados);
        if (!$errosDados) {
            atualizar_dados_pessoais($usuario['id'], $dados['nome'], $dados['telefone']);
            flash('sucesso', 'Seus dados foram atualizados.');
            redirecionar('conta.php');
        }
    }

    if ($acao === 'senha') {
        $atual = post('senha_atual', false);
        $nova = post('senha', false);
        $errosSenha = validar_nova_senha($nova, post('confirmar_senha', false));
        if (!password_verify($atual, $usuario['senha_hash'])) {
            $errosSenha = ['senha_atual' => 'A senha atual está incorreta.'] + $errosSenha;
        }
        if (!$errosSenha) {
            alterar_senha($usuario['id'], $nova);
            session_regenerate_id(true);
            flash('sucesso', 'Senha alterada com sucesso.');
            redirecionar('conta.php');
        }
    }
}

require APP . '/views/topo.php';
?>

<section class="secao secao--compacta">
    <div class="container estreito">
        <nav class="migalhas migalhas--claro" aria-label="Você está em">
            <ol>
                <li><a href="index.php">Início</a></li>
                <li><a href="conta.php">Minha conta</a></li>
                <li aria-current="page">Editar dados e senha</li>
            </ol>
        </nav>
        <h1 class="titulo-pagina">Editar dados e senha</h1>

        <div class="grade-formularios">
            <div class="cartao-formulario">
                <h2>Dados pessoais</h2>
                <form class="formulario" method="post" action="editar-conta.php" novalidate data-validar>
                    <?= campo_csrf() ?>
                    <input type="hidden" name="acao" value="dados">
                    <div class="campo<?= isset($errosDados['nome']) ? ' campo--erro' : '' ?>">
                        <label for="nome">Nome completo</label>
                        <input id="nome" name="nome" type="text" autocomplete="name" required value="<?= e($dados['nome']) ?>"<?= attr_erro($errosDados, 'nome') ?>>
                        <?= erro_campo($errosDados, 'nome') ?>
                    </div>
                    <div class="campo<?= isset($errosDados['telefone']) ? ' campo--erro' : '' ?>">
                        <label for="telefone">Celular</label>
                        <input id="telefone" name="telefone" type="tel" autocomplete="tel-national" required data-mascara="telefone" value="<?= e($dados['telefone']) ?>"<?= attr_erro($errosDados, 'telefone') ?>>
                        <?= erro_campo($errosDados, 'telefone') ?>
                    </div>
                    <div class="campo">
                        <label for="email">E-mail</label>
                        <input id="email" type="email" value="<?= e($usuario['email']) ?>" disabled aria-describedby="email-dica">
                        <p class="campo__dica" id="email-dica">Para trocar o e-mail, <a href="contato.php?assunto=outro">fale com a gente</a>.</p>
                    </div>
                    <button class="botao botao--primario" type="submit">Salvar dados</button>
                </form>
            </div>

            <div class="cartao-formulario">
                <h2>Alterar senha</h2>
                <form class="formulario" method="post" action="editar-conta.php" novalidate data-validar>
                    <?= campo_csrf() ?>
                    <input type="hidden" name="acao" value="senha">
                    <div class="campo<?= isset($errosSenha['senha_atual']) ? ' campo--erro' : '' ?>">
                        <label for="senha_atual">Senha atual</label>
                        <div class="campo-senha">
                            <input id="senha_atual" name="senha_atual" type="password" autocomplete="current-password" required<?= attr_erro($errosSenha, 'senha_atual') ?>>
                            <button class="campo-senha__alternar" type="button" data-mostrar-senha aria-label="Mostrar senha" aria-pressed="false"><?= icone('olho') ?></button>
                        </div>
                        <?= erro_campo($errosSenha, 'senha_atual') ?>
                    </div>
                    <div class="campo<?= isset($errosSenha['senha']) ? ' campo--erro' : '' ?>">
                        <label for="senha">Nova senha</label>
                        <div class="campo-senha">
                            <input id="senha" name="senha" type="password" autocomplete="new-password" required minlength="8" aria-describedby="senha-dica<?= isset($errosSenha['senha']) ? ' erro-senha' : '' ?>"<?= isset($errosSenha['senha']) ? ' aria-invalid="true"' : '' ?>>
                            <button class="campo-senha__alternar" type="button" data-mostrar-senha aria-label="Mostrar senha" aria-pressed="false"><?= icone('olho') ?></button>
                        </div>
                        <p class="campo__dica" id="senha-dica">Mínimo de 8 caracteres, com letras e números.</p>
                        <?= erro_campo($errosSenha, 'senha') ?>
                    </div>
                    <div class="campo<?= isset($errosSenha['confirmar_senha']) ? ' campo--erro' : '' ?>">
                        <label for="confirmar_senha">Confirme a nova senha</label>
                        <input id="confirmar_senha" name="confirmar_senha" type="password" autocomplete="new-password" required data-igual-a="senha"<?= attr_erro($errosSenha, 'confirmar_senha') ?>>
                        <?= erro_campo($errosSenha, 'confirmar_senha') ?>
                    </div>
                    <button class="botao botao--primario" type="submit">Alterar senha</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require APP . '/views/rodape.php'; ?>
