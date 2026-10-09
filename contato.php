<?php
require __DIR__ . '/app/bootstrap.php';

$titulo = 'Contato';
$subtitulo = 'Dúvida sobre peças, pedidos ou garantia? Mande uma mensagem e respondemos em até 1 dia útil.';
$descricao = 'Fale com a equipe Proware: dúvidas sobre montagem de PC, pedidos, garantia e suporte.';
$pagina = 'contato';

$assuntos = ASSUNTOS_CONTATO;

$usuario = usuario_logado();
$dados = [
    'nome' => $usuario['nome'] ?? '',
    'email' => $usuario['email'] ?? '',
    'assunto' => get('assunto'),
    'mensagem' => '',
];
$erros = [];

if (requisicao_post()) {
    foreach (array_keys($dados) as $campo) {
        $dados[$campo] = post($campo);
    }

    if (!csrf_valido()) {
        $erros['geral'] = 'Sua sessão expirou. Envie a mensagem novamente.';
    }
    if (mb_strlen_seguro($dados['nome']) < 2) {
        $erros['nome'] = 'Informe seu nome.';
    }
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erros['email'] = 'Informe um e-mail válido.';
    }
    if (!isset($assuntos[$dados['assunto']])) {
        $erros['assunto'] = 'Escolha um assunto.';
    }
    if (mb_strlen_seguro($dados['mensagem']) < 10 || mb_strlen_seguro($dados['mensagem']) > 2000) {
        $erros['mensagem'] = 'Escreva uma mensagem entre 10 e 2.000 caracteres.';
    }

    if (!$erros) {
        salvar_mensagem($dados + ['usuario_id' => $usuario['id'] ?? null]);
        flash('sucesso', 'Mensagem enviada! Vamos responder no seu e-mail em até 1 dia útil.');
        redirecionar('contato.php');
    }
}

$perguntas = [
    'Vocês montam o PC para mim?' => 'Sim. Todo computador, pronto ou personalizado, é montado pela nossa equipe, com os cabos organizados, e passa por 24 horas de testes de estresse antes de sair para entrega.',
    'Como sei se as peças são compatíveis?' => 'O Monte seu PC confere tudo automaticamente: o soquete do processador e da placa-mãe, o tamanho do gabinete, o cooler, o vídeo integrado e a potência da fonte. Combinações que não funcionam ficam bloqueadas.',
    'Qual é o prazo de entrega?' => 'A montagem e os testes levam até 3 dias úteis depois da aprovação do pagamento. O frete grátis leva de 3 a 10 dias úteis, dependendo da sua região.',
    'O computador vem com Windows?' => 'Os PCs saem sem sistema operacional. Se quiser, instalamos o Windows (com a sua licença) ou uma distribuição Linux: é só avisar pelo contato depois da compra.',
    'Como funciona a garantia?' => 'Todos os produtos têm 1 ano de garantia Proware contra defeitos de fabricação e de montagem. Se algo acontecer, fale com a gente e resolvemos.',
    'Quais são as formas de pagamento?' => 'Pix com 5% de desconto, cartão de crédito em até 10x sem juros ou boleto bancário.',
];

require APP . '/views/topo.php';
parcial('topo-pagina', compact('titulo', 'subtitulo'));
?>

<section class="secao secao--compacta">
    <div class="container contato">
        <div class="cartao-formulario">
            <h2>Envie uma mensagem</h2>

            <?php if (!empty($erros['geral'])): ?>
                <div class="alerta alerta--erro" role="alert"><?= icone('alerta') ?><p><?= e($erros['geral']) ?></p></div>
            <?php endif; ?>

            <form class="formulario" method="post" action="contato.php" novalidate data-validar>
                <?= campo_csrf() ?>
                <div class="formulario__linha">
                    <div class="campo<?= isset($erros['nome']) ? ' campo--erro' : '' ?>">
                        <label for="nome">Nome</label>
                        <input id="nome" name="nome" type="text" autocomplete="name" required minlength="2" value="<?= e($dados['nome']) ?>"<?= attr_erro($erros, 'nome') ?>>
                        <?= erro_campo($erros, 'nome') ?>
                    </div>
                    <div class="campo<?= isset($erros['email']) ? ' campo--erro' : '' ?>">
                        <label for="email">E-mail</label>
                        <input id="email" name="email" type="email" autocomplete="email" required value="<?= e($dados['email']) ?>"<?= attr_erro($erros, 'email') ?>>
                        <?= erro_campo($erros, 'email') ?>
                    </div>
                </div>
                <div class="campo<?= isset($erros['assunto']) ? ' campo--erro' : '' ?>">
                    <label for="assunto">Assunto</label>
                    <select id="assunto" name="assunto" required<?= attr_erro($erros, 'assunto') ?>>
                        <option value="">Selecione</option>
                        <?php foreach ($assuntos as $valor => $rotulo): ?>
                            <option value="<?= e($valor) ?>"<?= $dados['assunto'] === $valor ? ' selected' : '' ?>><?= e($rotulo) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= erro_campo($erros, 'assunto') ?>
                </div>
                <div class="campo<?= isset($erros['mensagem']) ? ' campo--erro' : '' ?>">
                    <label for="mensagem">Mensagem</label>
                    <textarea id="mensagem" name="mensagem" rows="6" required minlength="10" maxlength="2000"
                              placeholder="Ex.: quero um PC para jogar Valorant e editar vídeos, com orçamento de até R$ 6.000."<?= attr_erro($erros, 'mensagem') ?>><?= e($dados['mensagem']) ?></textarea>
                    <?= erro_campo($erros, 'mensagem') ?>
                </div>
                <button class="botao botao--primario botao--grande" type="submit">Enviar mensagem <?= icone('seta') ?></button>
            </form>
        </div>

        <aside class="contato__canais" aria-label="Outros canais">
            <div class="canal">
                <span class="canal__icone"><?= icone('email') ?></span>
                <div>
                    <h3>E-mail</h3>
                    <p><a href="mailto:<?= e(LOJA['email']) ?>"><?= e(LOJA['email']) ?></a></p>
                </div>
            </div>
            <div class="canal">
                <span class="canal__icone"><?= icone('relogio') ?></span>
                <div>
                    <h3>Horário de atendimento</h3>
                    <p><?= e(LOJA['horario']) ?></p>
                </div>
            </div>
            <div class="canal">
                <span class="canal__icone"><?= icone('caminhao') ?></span>
                <div>
                    <h3>Entrega</h3>
                    <p>Enviamos para todo o Brasil com frete grátis e seguro.</p>
                </div>
            </div>
        </aside>
    </div>
</section>

<section class="secao secao--alternada" id="perguntas">
    <div class="container estreito">
        <div class="secao__cabecalho secao__cabecalho--centro">
            <div>
                <p class="sobretitulo">Perguntas frequentes</p>
                <h2 class="secao__titulo">Antes de perguntar, veja se já respondemos</h2>
            </div>
        </div>
        <div class="perguntas">
            <?php foreach ($perguntas as $pergunta => $resposta): ?>
                <details class="pergunta">
                    <summary><?= e($pergunta) ?><?= icone('mais') ?></summary>
                    <p><?= e($resposta) ?></p>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require APP . '/views/rodape.php'; ?>
