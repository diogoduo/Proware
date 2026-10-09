<?php
/**
 * Testes automatizados da Proware. Rodam num banco SQLite em memória,
 * sem tocar nos dados reais da loja.
 *
 *   php tests/executar.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

putenv('PROWARE_BANCO=:memory:');
require dirname(__DIR__) . '/app/bootstrap.php';

/* ---------- Mini executor de testes ---------- */

$testes = [];

function teste(string $nome, callable $corpo): void
{
    $GLOBALS['testes'][$nome] = $corpo;
}

function confirmar(bool $condicao, string $mensagem): void
{
    if (!$condicao) {
        throw new AssertionError($mensagem);
    }
}

function iguais(mixed $esperado, mixed $obtido, string $contexto = ''): void
{
    if ($esperado !== $obtido) {
        throw new AssertionError(trim("$contexto: esperado " . var_export($esperado, true) . ', obtido ' . var_export($obtido, true), ': '));
    }
}

function deve_falhar(callable $codigo, string $classe, string $contexto): void
{
    try {
        $codigo();
    } catch (Throwable $erro) {
        if ($erro instanceof $classe) {
            return;
        }
        throw new AssertionError("$contexto: lançou " . get_class($erro) . " em vez de $classe");
    }
    throw new AssertionError("$contexto: deveria ter lançado $classe");
}

/** Cria um cliente com e-mail único para o teste. */
function cliente_de_teste(): array
{
    static $contador = 0;
    $contador++;
    return criar_usuario([
        'nome' => "Cliente Teste $contador", 'email' => "cliente$contador@teste.com",
        'cpf' => '52998224725', 'telefone' => '11987654321', 'senha' => 'senha12345',
    ]);
}

function endereco_de_teste(): array
{
    return ['cep' => '01310-100', 'rua' => 'Av. Paulista', 'numero' => '1000', 'complemento' => '',
        'bairro' => 'Bela Vista', 'cidade' => 'São Paulo', 'uf' => 'SP'];
}

function carrinho(array $itens): array
{
    return montar_itens_do_carrinho(json_encode($itens));
}

const MONTAGEM_OK = ['cpu' => 'ryzen-5-7600', 'placa' => 'b650m-wifi', 'memoria' => 'ddr5-16gb', 'gpu' => 'rtx-5060',
    'ssd' => 'ssd-1tb', 'cooler' => 'cooler-box', 'fonte' => 'fonte-650', 'gabinete' => 'gabinete-neon'];

/* ---------- Funções gerais ---------- */

teste('valida CPF pelos dígitos verificadores', function () {
    confirmar(cpf_valido('529.982.247-25'), 'CPF válido recusado');
    confirmar(!cpf_valido('529.982.247-24'), 'dígito errado aceito');
    confirmar(!cpf_valido('111.111.111-11'), 'CPF com dígitos repetidos aceito');
    confirmar(!cpf_valido('123'), 'CPF curto aceito');
});

teste('formata valores em reais', function () {
    iguais('R$ 1.234,50', brl(1234.5));
    iguais('R$ 12,5 mil', valor_compacto(12500));
    iguais(5049.9, preco_promocional(5051.55), 'preço promocional');
});

teste('só redireciona para páginas internas', function () {
    iguais('checkout.php', destino_seguro('checkout.php'));
    iguais('admin/pedidos.php', destino_seguro('admin/pedidos.php'));
    iguais('conta.php', destino_seguro('https://site-malicioso.com'));
    iguais('conta.php', destino_seguro('//site-malicioso.com/x.php'));
    iguais('conta.php', destino_seguro(['array']));
});

/* ---------- Catálogo e compatibilidade ---------- */

teste('carrega o catálogo inicial com os preços esperados', function () {
    $esperados = ['proware-start' => 6143.0, 'proware-play' => 5049.9, 'proware-pro' => 7369.9,
        'proware-ultra' => 17642.0, 'proware-creator' => 15392.0, 'proware-essencial' => 2809.9];
    foreach ($esperados as $id => $preco) {
        iguais($preco, produto($id)['preco'], $id);
    }
    iguais(37, count(componentes()), 'número de peças');
    iguais(3, count(produtos_do_tipo('periferico')), 'número de periféricos');
});

teste('todos os PCs prontos e perfis são compatíveis', function () {
    foreach (produtos_do_tipo('pc') as $id => $pc) {
        iguais([], problemas_da_montagem($pc['pecas']), $id);
    }
    foreach (perfis() as $id => $perfil) {
        iguais([], problemas_da_montagem($perfil['pecas']), "perfil $id");
    }
});

teste('detecta cada tipo de incompatibilidade', function () {
    $casos = [
        'soquete diferente' => ['placa' => 'b760m'],
        'gabinete pequeno' => ['placa' => 'x870-wifi', 'gabinete' => 'gabinete-vortex'],
        'sem vídeo integrado' => ['cpu' => 'core-i5-14400f', 'placa' => 'b760m', 'gpu' => 'sem-gpu'],
        'sem cooler na caixa' => ['cpu' => 'ryzen-7-9700x'],
        'cooler fraco' => ['cpu' => 'ryzen-9-9950x', 'cooler' => 'air-120'],
        'fonte fraca' => ['gpu' => 'rtx-5080', 'fonte' => 'fonte-550'],
    ];
    foreach ($casos as $caso => $troca) {
        confirmar(count(problemas_da_montagem(array_merge(MONTAGEM_OK, $troca))) > 0, "não detectou: $caso");
    }
    confirmar(count(problemas_da_montagem(array_diff_key(MONTAGEM_OK, ['ssd' => 1]))) > 0, 'aceitou montagem incompleta');
});

/* ---------- Carrinho ---------- */

teste('recalcula os preços do carrinho no servidor', function () {
    $resultado = carrinho([
        ['tipo' => 'produto', 'id' => 'mouse-gamer-rgb', 'qtd' => 2, 'preco' => 0.01],
        ['tipo' => 'montagem', 'pecas' => MONTAGEM_OK, 'qtd' => 1],
    ]);
    iguais([], $resultado['erros'], 'erros');
    iguais(59.9, $resultado['itens'][0]['preco'], 'preço do mouse');
    iguais(preco_das_pecas(MONTAGEM_OK), $resultado['itens'][1]['preco'], 'preço da montagem');
    iguais(round(59.9 * 2 + preco_das_pecas(MONTAGEM_OK), 2), $resultado['subtotal'], 'subtotal');
});

teste('recusa itens inválidos no carrinho', function () {
    confirmar((bool) carrinho([['tipo' => 'produto', 'id' => 'nao-existe', 'qtd' => 1]])['erros'], 'aceitou produto inexistente');
    confirmar((bool) carrinho([['tipo' => 'produto', 'id' => 'mouse-gamer-rgb', 'qtd' => 99]])['erros'], 'aceitou quantidade 99');
    confirmar((bool) carrinho([['tipo' => 'montagem', 'pecas' => array_merge(MONTAGEM_OK, ['placa' => 'b760m']), 'qtd' => 1]])['erros'], 'aceitou montagem incompatível');
    confirmar((bool) montar_itens_do_carrinho('isto não é json')['erros'], 'aceitou JSON inválido');
});

/* ---------- Usuários ---------- */

teste('cria conta e autentica', function () {
    $usuario = cliente_de_teste();
    confirmar($usuario['senha_hash'] !== 'senha12345', 'senha guardada em texto puro');
    iguais($usuario['id'], autenticar(strtoupper($usuario['email']), 'senha12345')['id'] ?? null, 'login com e-mail em maiúsculas');
    iguais(null, autenticar($usuario['email'], 'senha-errada'), 'login com senha errada');
    deve_falhar(fn () => criar_usuario(['nome' => 'Outro Nome', 'email' => strtoupper($usuario['email']), 'senha' => 'abc12345']),
        RuntimeException::class, 'e-mail duplicado');
});

teste('valida o cadastro', function () {
    $erros = validar_cadastro(['nome' => 'Ana', 'email' => 'invalido', 'cpf' => '123', 'telefone' => '12',
        'senha' => 'curta', 'confirmar_senha' => 'outra']);
    foreach (['nome', 'email', 'cpf', 'telefone', 'senha', 'confirmar_senha'] as $campo) {
        confirmar(isset($erros[$campo]), "sem erro no campo $campo");
    }
});

teste('bloqueia o login depois de muitas tentativas erradas', function () {
    $email = 'alvo@teste.com';
    for ($i = 0; $i < LIMITE_TENTATIVAS_LOGIN; $i++) {
        iguais(0, minutos_de_bloqueio($email), "bloqueou cedo demais (tentativa $i)");
        registrar_falha_login($email);
    }
    confirmar(minutos_de_bloqueio($email) > 0, 'não bloqueou');
    iguais(0, minutos_de_bloqueio('outro@teste.com'), 'bloqueou outro e-mail');
    limpar_falhas_login($email);
    iguais(0, minutos_de_bloqueio($email), 'não liberou depois do login certo');
});

teste('atualiza dados e senha da conta', function () {
    $usuario = cliente_de_teste();
    atualizar_dados_pessoais($usuario['id'], '  Nome   Novo  ', '(21) 99999-0000');
    $atualizado = usuario_por_id($usuario['id']);
    iguais('Nome Novo', $atualizado['nome']);
    iguais('21999990000', $atualizado['telefone']);
    alterar_senha($usuario['id'], 'novaSenha123');
    confirmar(autenticar($usuario['email'], 'novaSenha123') !== null, 'nova senha não funciona');
});

/* ---------- Pedidos ---------- */

teste('cria pedido com desconto no Pix', function () {
    $usuario = cliente_de_teste();
    $pedido = criar_pedido($usuario, carrinho([['tipo' => 'produto', 'id' => 'proware-start', 'qtd' => 1]]), endereco_de_teste(), 'pix');
    iguais(6143.0, $pedido['subtotal'], 'subtotal');
    iguais(307.15, $pedido['desconto'], 'desconto');
    iguais(5835.85, $pedido['total'], 'total');
    iguais('recebido', $pedido['status'], 'situação inicial');
    iguais('01310100', $pedido['endereco']['cep'], 'CEP');
    iguais($pedido['codigo'], pedido_do_usuario($pedido['codigo'], $usuario['id'])['codigo'] ?? null, 'dono vê o pedido');
    iguais(null, pedido_do_usuario($pedido['codigo'], cliente_de_teste()['id']), 'outro cliente vê o pedido');
});

teste('pagamento no cartão já começa aprovado', function () {
    $pedido = criar_pedido(cliente_de_teste(), carrinho([['tipo' => 'produto', 'id' => 'mouse-gamer-rgb', 'qtd' => 1]]), endereco_de_teste(), 'cartao');
    iguais('pago', $pedido['status']);
    iguais(0.0, $pedido['desconto'], 'cartão não tem desconto');
    iguais(['recebido', 'pago'], array_column($pedido['eventos'], 'status'), 'histórico');
});

teste('segue o fluxo de situações do pedido', function () {
    $codigo = criar_pedido(cliente_de_teste(), carrinho([['tipo' => 'produto', 'id' => 'mouse-gamer-rgb', 'qtd' => 1]]), endereco_de_teste(), 'pix')['codigo'];

    deve_falhar(fn () => alterar_status_pedido($codigo, 'enviado'), InvalidArgumentException::class, 'pular etapas');
    alterar_status_pedido($codigo, 'pago');
    alterar_status_pedido($codigo, 'montagem');
    deve_falhar(fn () => alterar_status_pedido($codigo, 'enviado', 'abc'), InvalidArgumentException::class, 'rastreio inválido');
    $pedido = alterar_status_pedido($codigo, 'enviado', 'br123456789br');
    iguais('BR123456789BR', $pedido['rastreio'], 'rastreio');
    deve_falhar(fn () => alterar_status_pedido($codigo, 'cancelado'), InvalidArgumentException::class, 'cancelar depois de enviado');
    $pedido = alterar_status_pedido($codigo, 'entregue');
    iguais(null, proximo_status($pedido['status']), 'etapa depois de entregue');
    iguais(['recebido', 'pago', 'montagem', 'enviado', 'entregue'], array_column($pedido['eventos'], 'status'), 'histórico');
});

teste('métricas ignoram pedidos cancelados', function () {
    $antes = metricas_da_loja();
    $usuario = cliente_de_teste();
    criar_pedido($usuario, carrinho([['tipo' => 'produto', 'id' => 'teclado-mecanico-tkl', 'qtd' => 1]]), endereco_de_teste(), 'boleto');
    $cancelado = criar_pedido($usuario, carrinho([['tipo' => 'produto', 'id' => 'proware-ultra', 'qtd' => 1]]), endereco_de_teste(), 'boleto');
    alterar_status_pedido($cancelado['codigo'], 'cancelado');
    $depois = metricas_da_loja();
    iguais(round($antes['faturamento'] + 229.9, 2), round($depois['faturamento'], 2), 'faturamento');
    iguais($antes['pedidos'] + 1, $depois['pedidos'], 'pedidos');
    iguais($antes['por_status']['cancelado'] + 1, $depois['por_status']['cancelado'], 'cancelados');
});

/* ---------- Painel: catálogo e mensagens ---------- */

teste('preço e disponibilidade das peças refletem nos PCs', function () {
    $original = todas_as_pecas()['ssd-1tb']['preco'];
    try {
        atualizar_peca('ssd-1tb', $original + 100, true);
        iguais(6243.0, produto('proware-start')['preco'], 'PC acompanha o preço da peça');

        atualizar_peca('ssd-1tb', $original, false);
        iguais(null, componente('ssd-1tb'), 'peça desativada continua no configurador');
        iguais(null, produto('proware-start'), 'PC com peça desativada continua na vitrine');
        confirmar((bool) carrinho([['tipo' => 'montagem', 'pecas' => MONTAGEM_OK, 'qtd' => 1]])['erros'], 'aceitou montagem com peça desativada');
    } finally {
        atualizar_peca('ssd-1tb', $original, true);
    }
    iguais(6143.0, produto('proware-start')['preco'], 'preço restaurado');
    deve_falhar(fn () => atualizar_peca('ssd-1tb', -5, true), InvalidArgumentException::class, 'preço negativo');
});

teste('edita desconto de PC e preço de periférico', function () {
    try {
        atualizar_pc('proware-start', 0.10, true, true);
        iguais(5519.9, produto('proware-start')['preco'], 'preço com 10% de desconto');
        iguais(6143.0, produto('proware-start')['preco_original'], 'preço "de"');
        deve_falhar(fn () => atualizar_periferico('mouse-gamer-rgb', 80, 70, true, true), InvalidArgumentException::class, 'preço "de" menor');
        atualizar_periferico('mouse-gamer-rgb', 49.9, null, true, true);
        iguais(null, produto('mouse-gamer-rgb')['preco_original'], 'oferta removida');
    } finally {
        atualizar_pc('proware-start', 0, true, true);
        atualizar_periferico('mouse-gamer-rgb', 59.9, 79.9, true, true);
    }
});

teste('guarda e organiza mensagens de contato', function () {
    salvar_mensagem(['usuario_id' => null, 'nome' => 'Visitante', 'email' => 'v@teste.com', 'assunto' => 'produto', 'mensagem' => 'Tem estoque?']);
    $id = (int) banco()->lastInsertId();
    iguais(1, contar_mensagens_por_status()['nova'], 'novas');
    marcar_mensagem($id, 'respondida');
    iguais(0, contar_mensagens_por_status()['nova'], 'novas depois de responder');
    deve_falhar(fn () => marcar_mensagem($id, 'apagada'), InvalidArgumentException::class, 'situação inválida');
});

teste('valida o endereço de entrega', function () {
    iguais([], validar_endereco(endereco_de_teste()));
    $erros = validar_endereco(['cep' => '123', 'rua' => '', 'numero' => '', 'bairro' => '', 'cidade' => '', 'uf' => 'XX']);
    iguais(['cep', 'rua', 'numero', 'bairro', 'cidade', 'uf'], array_keys($erros));
});

/* ---------- Recuperação de senha e e-mails ---------- */

function token_do_link(string $link): string
{
    parse_str((string) parse_url($link, PHP_URL_QUERY), $parametros);
    return $parametros['token'] ?? '';
}

teste('redefine a senha com link de uso único', function () {
    $usuario = cliente_de_teste();
    $token = token_do_link(criar_link_redefinicao($usuario));

    iguais($usuario['id'], usuario_do_token($token)['id'] ?? null, 'token válido');
    confirmar(redefinir_senha_com_token($token, 'outraSenha77'), 'não redefiniu');
    confirmar(autenticar($usuario['email'], 'outraSenha77') !== null, 'nova senha não funciona');
    iguais(null, autenticar($usuario['email'], 'senha12345'), 'senha antiga ainda funciona');
    confirmar(!redefinir_senha_com_token($token, 'maisUma123'), 'link usado duas vezes');
    iguais(null, usuario_do_token('nao-e-um-token'), 'token em formato inválido');
});

teste('link de senha expira e um novo link anula o anterior', function () {
    $usuario = cliente_de_teste();
    $primeiro = token_do_link(criar_link_redefinicao($usuario));
    $segundo = token_do_link(criar_link_redefinicao($usuario));
    iguais(null, usuario_do_token($primeiro), 'link antigo continua valendo');
    confirmar(usuario_do_token($segundo) !== null, 'link novo não vale');

    sql('UPDATE redefinicoes_senha SET expira_em = ? WHERE token_hash = ?', [time() - 1, hash('sha256', $segundo)]);
    iguais(null, usuario_do_token($segundo), 'link expirado continua valendo');
});

teste('registra os e-mails da loja sem enviar para domínios fictícios', function () {
    $antes = count(listar_emails(1000));
    $usuario = cliente_de_teste();
    $pedido = criar_pedido($usuario, carrinho([['tipo' => 'produto', 'id' => 'mouse-gamer-rgb', 'qtd' => 1]]), endereco_de_teste(), 'pix');
    alterar_status_pedido($pedido['codigo'], 'pago');

    $emails = listar_emails(1000);
    iguais($antes + 2, count($emails), 'e-mails de confirmação e de pagamento');
    iguais($usuario['email'], $emails[0]['para'], 'destinatário');
    iguais('registrado', $emails[0]['status'], 'situação sem Brevo configurado');
    confirmar(str_contains($emails[1]['html'], $pedido['codigo']), 'código do pedido no e-mail');
    confirmar(str_contains($emails[1]['texto'], 'pedido.php?codigo=' . $pedido['codigo']), 'link do pedido no texto');

    emails_silenciados(true);
    enviar_email('x@teste.com', 'X', 'Não registrar', '<p>x</p>', 'x');
    emails_silenciados(false);
    iguais($antes + 2, count(listar_emails(1000)), 'e-mail silenciado foi registrado');
});

teste('conta de visitante do painel é somente leitura', function () {
    $visitante = criar_usuario(['nome' => 'Visitante Teste', 'email' => 'visitante@teste.com', 'senha' => 'visita1234'], true, true);
    confirmar($visitante['admin'] && $visitante['somente_leitura'], 'visitante sem as marcações certas');
    confirmar(!cliente_de_teste()['somente_leitura'], 'cliente marcado como somente leitura');
});

teste('gera os dados de demonstração sem disparar e-mails', function () {
    $emailsAntes = count(listar_emails(1000));
    $resumo = criar_dados_de_demonstracao();
    iguais(['clientes' => 9, 'pedidos' => 14, 'mensagens' => 4], $resumo, 'resumo');
    iguais($emailsAntes, count(listar_emails(1000)), 'e-mails disparados na demonstração');
    confirmar(autenticar(DEMO_CLIENTE_EMAIL, DEMO_SENHA_CLIENTES) !== null, 'cliente de demonstração não entra');
    iguais(1, contar_pedidos_por_status()['cancelado'] >= 1 ? 1 : 0, 'há pedido cancelado');
});

/* ---------- Execução ---------- */

$falhas = 0;
$inicio = microtime(true);
foreach ($testes as $nome => $corpo) {
    try {
        $corpo();
        echo "  [ok]     $nome\n";
    } catch (Throwable $erro) {
        $falhas++;
        echo "  [falhou] $nome\n           " . $erro->getMessage() . "\n";
    }
}

printf("\n%d testes, %d falha(s) em %.2fs\n", count($testes), $falhas, microtime(true) - $inicio);
exit($falhas > 0 ? 1 : 0);
