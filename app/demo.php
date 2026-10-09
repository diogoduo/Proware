<?php
declare(strict_types=1);

/**
 * Dados de demonstração: clientes, pedidos em todas as situações (espalhados
 * pelos últimos dias) e mensagens de contato, para apresentar a loja e o painel.
 *
 * Com a variável de ambiente PROWARE_DEMO=1 (usada na hospedagem gratuita),
 * eles são criados sozinhos sempre que o banco começa vazio, junto com um
 * acesso de visitante ao painel em modo somente leitura.
 */

const DEMO_SENHA_CLIENTES = 'demo12345';
const DEMO_CLIENTE_EMAIL = 'ana.souza@exemplo.com';
const DEMO_VISITANTE_EMAIL = 'visitante@proware.dev';
const DEMO_VISITANTE_SENHA = 'visitante2026';

function modo_demonstracao(): bool
{
    return getenv('PROWARE_DEMO') === '1';
}

/**
 * Prepara um banco vazio no modo demonstração. Usa uma trava de arquivo para
 * que duas visitas ao mesmo tempo (logo depois de o servidor acordar) não
 * criem os dados em dobro.
 */
function preparar_modo_demonstracao(): void
{
    $trava = fopen(STORAGE . '/demonstracao.lock', 'c');
    flock($trava, LOCK_EX);
    try {
        if (sql('SELECT COUNT(*) FROM usuarios')->fetchColumn() > 0) {
            return;
        }
        criar_dados_de_demonstracao();
        criar_usuario(['nome' => 'Visitante Proware', 'email' => DEMO_VISITANTE_EMAIL, 'senha' => DEMO_VISITANTE_SENHA], true, true);

        // Administrador de verdade, definido nas variáveis de ambiente da hospedagem.
        $email = getenv('ADMIN_EMAIL');
        $senha = getenv('ADMIN_SENHA');
        if ($email && $senha && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($senha) >= 8) {
            criar_usuario(['nome' => getenv('ADMIN_NOME') ?: 'Administrador Proware', 'email' => $email, 'senha' => $senha], true);
        }
    } finally {
        flock($trava, LOCK_UN);
        fclose($trava);
    }
}

/** Cria clientes, pedidos e mensagens fictícios. Devolve um resumo do que foi criado. */
function criar_dados_de_demonstracao(): array
{
    emails_silenciados(true);
    try {
    mt_srand(2026);

    $clientes = [
        ['Ana Beatriz Souza', 'SP'], ['Bruno Henrique Lima', 'RJ'], ['Carla Mendes', 'MG'],
        ['Daniel Oliveira', 'PR'], ['Eduarda Ramos', 'RS'], ['Felipe Andrade', 'BA'],
        ['Gabriela Costa', 'SC'], ['Henrique Martins', 'PE'], ['Isabela Rocha', 'GO'],
    ];

    $enderecos = [
        'SP' => ['Rua Augusta', 'Consolação', 'São Paulo', '01305000'],
        'RJ' => ['Rua Voluntários da Pátria', 'Botafogo', 'Rio de Janeiro', '22270010'],
        'MG' => ['Avenida Afonso Pena', 'Centro', 'Belo Horizonte', '30130001'],
        'PR' => ['Rua XV de Novembro', 'Centro', 'Curitiba', '80020310'],
        'RS' => ['Avenida Ipiranga', 'Azenha', 'Porto Alegre', '90160093'],
        'BA' => ['Avenida Sete de Setembro', 'Vitória', 'Salvador', '40080001'],
        'SC' => ['Rua Felipe Schmidt', 'Centro', 'Florianópolis', '88010001'],
        'PE' => ['Avenida Boa Viagem', 'Boa Viagem', 'Recife', '51011000'],
        'GO' => ['Avenida T-63', 'Setor Bueno', 'Goiânia', '74230100'],
    ];

    $usuarios = [];
    foreach ($clientes as $i => [$nome, $uf]) {
        $partes = explode(' ', $nome);
        $usuario = criar_usuario([
            'nome' => $nome,
            'email' => sem_acento($partes[0] . '.' . end($partes)) . '@exemplo.com',
            'cpf' => cpf_aleatorio(),
            'telefone' => sprintf('11%09d', mt_rand(900000000, 999999999)),
            'senha' => DEMO_SENHA_CLIENTES,
        ]);
        $diasAtras = 30 - $i * 2;
        sql('UPDATE usuarios SET criado_em = ? WHERE id = ?', [date('Y-m-d H:i:s', strtotime("-$diasAtras days 10:00")), $usuario['id']]);
        $usuarios[] = $usuario + ['uf' => $uf];
    }

    // Cada pedido: [cliente, dias atrás, situação final, forma de pagamento, itens do carrinho]
    $montagem = ['cpu' => 'ryzen-7-7800x3d', 'placa' => 'b650m-wifi', 'memoria' => 'ddr5-32gb', 'gpu' => 'rtx-5070', 'ssd' => 'ssd-2tb', 'cooler' => 'air-120', 'fonte' => 'fonte-750', 'gabinete' => 'gabinete-aurora'];
    $pedidos = [
        [0, 13, 'entregue', 'pix', [['produto', 'proware-pro', 1], ['produto', 'mouse-gamer-rgb', 1]]],
        [1, 12, 'entregue', 'cartao', [['produto', 'proware-start', 1]]],
        [2, 11, 'entregue', 'pix', [['montagem', $montagem, 1]]],
        [3, 10, 'cancelado', 'boleto', [['produto', 'proware-ultra', 1]]],
        [4, 9, 'entregue', 'cartao', [['produto', 'monitor-odyssey-g5', 1], ['produto', 'teclado-mecanico-tkl', 1]]],
        [5, 8, 'enviado', 'pix', [['produto', 'proware-play', 1]]],
        [6, 7, 'enviado', 'cartao', [['produto', 'proware-creator', 1]]],
        [0, 6, 'enviado', 'pix', [['produto', 'teclado-mecanico-tkl', 1], ['produto', 'mouse-gamer-rgb', 2]]],
        [7, 5, 'montagem', 'pix', [['produto', 'proware-essencial', 2]]],
        [8, 4, 'montagem', 'cartao', [['montagem', array_merge($montagem, ['gpu' => 'rx-9070-xt', 'fonte' => 'fonte-850']), 1]]],
        [1, 3, 'pago', 'pix', [['produto', 'proware-pro', 1]]],
        [2, 2, 'pago', 'cartao', [['produto', 'monitor-odyssey-g5', 1]]],
        [3, 1, 'recebido', 'boleto', [['produto', 'proware-start', 1], ['produto', 'mouse-gamer-rgb', 1]]],
        [4, 0, 'recebido', 'pix', [['produto', 'proware-play', 1]]],
    ];

    foreach ($pedidos as [$indiceCliente, $diasAtras, $situacao, $pagamento, $itens]) {
        $cliente = $usuarios[$indiceCliente];
        [$rua, $bairro, $cidade, $cep] = $enderecos[$cliente['uf']];

        $carrinho = montar_itens_do_carrinho(json_encode(array_map(
            fn ($i) => $i[0] === 'produto' ? ['tipo' => 'produto', 'id' => $i[1], 'qtd' => $i[2]] : ['tipo' => 'montagem', 'pecas' => $i[1], 'qtd' => $i[2]],
            $itens
        )));
        if ($carrinho['erros']) {
            throw new RuntimeException('Pedido de demonstração inválido: ' . implode(' ', $carrinho['erros']));
        }

        $pedido = criar_pedido($cliente, $carrinho, [
            'cep' => $cep, 'rua' => $rua, 'numero' => (string) mt_rand(10, 2500), 'complemento' => mt_rand(0, 1) ? 'Apto ' . mt_rand(11, 154) : '',
            'bairro' => $bairro, 'cidade' => $cidade, 'uf' => $cliente['uf'],
        ], $pagamento);

        // Avança o pedido até a situação desejada, como a equipe faria no painel.
        if ($situacao === 'cancelado') {
            alterar_status_pedido($pedido['codigo'], 'cancelado', '', 'Boleto não foi pago no prazo.');
        } else {
            while ($pedido['status'] !== $situacao) {
                $proximo = proximo_status($pedido['status']);
                $rastreio = $proximo === 'enviado' ? sprintf('BR%09dBR', mt_rand(100000000, 999999999)) : '';
                $pedido = alterar_status_pedido($pedido['codigo'], $proximo, $rastreio);
            }
        }

        // Espalha as datas: o pedido no dia escolhido e cada etapa algumas horas depois.
        $inicio = strtotime("-$diasAtras days " . sprintf('%02d:%02d', mt_rand(9, 21), mt_rand(0, 59)));
        $momento = $inicio;
        foreach (sql('SELECT id FROM pedido_eventos WHERE pedido_id = (SELECT id FROM pedidos WHERE codigo = ?) ORDER BY id', [$pedido['codigo']])->fetchAll(PDO::FETCH_COLUMN) as $n => $idEvento) {
            $momento = $n === 0 ? $inicio : min(time(), $momento + mt_rand(6, 26) * 3600);
            sql('UPDATE pedido_eventos SET criado_em = ? WHERE id = ?', [date('Y-m-d H:i:s', $momento), $idEvento]);
        }
        sql('UPDATE pedidos SET criado_em = ?, atualizado_em = ? WHERE codigo = ?',
            [date('Y-m-d H:i:s', $inicio), date('Y-m-d H:i:s', $momento), $pedido['codigo']]);
    }

    $mensagens = [
        [2, 'montagem', 'Oi! Quero um PC para editar vídeos em 4K no DaVinci Resolve e jogar de vez em quando. Meu orçamento é de uns R$ 10 mil. O que vocês recomendam?', 'nova', 0],
        [5, 'pedido', 'Meu pedido já foi enviado? Gostaria de saber o prazo de entrega para Salvador.', 'nova', 1],
        [null, 'produto', 'O monitor Odyssey G5 vem com cabo DisplayPort na caixa?', 'lida', 3],
        [6, 'garantia', 'A garantia de 1 ano cobre o water cooler também?', 'respondida', 6],
    ];
    foreach ($mensagens as [$indiceCliente, $assunto, $texto, $status, $diasAtras]) {
        $cliente = $indiceCliente === null ? null : $usuarios[$indiceCliente];
        salvar_mensagem([
            'usuario_id' => $cliente['id'] ?? null,
            'nome' => $cliente['nome'] ?? 'Rafael Teixeira',
            'email' => $cliente['email'] ?? 'rafael.teixeira@exemplo.com',
            'assunto' => $assunto,
            'mensagem' => $texto,
        ]);
        $id = (int) banco()->lastInsertId();
        marcar_mensagem($id, $status);
        sql('UPDATE mensagens SET criado_em = ? WHERE id = ?', [date('Y-m-d H:i:s', strtotime("-$diasAtras days -" . mt_rand(1, 8) . ' hours')), $id]);
    }
    } finally {
        emails_silenciados(false);
    }

    return ['clientes' => count($usuarios), 'pedidos' => count($pedidos), 'mensagens' => count($mensagens)];
}

function cpf_aleatorio(): string
{
    $n = array_map(fn () => mt_rand(0, 9), range(1, 9));
    for ($posicao = 9; $posicao < 11; $posicao++) {
        $soma = 0;
        for ($i = 0; $i < $posicao; $i++) {
            $soma += $n[$i] * ($posicao + 1 - $i);
        }
        $n[] = ((10 * $soma) % 11) % 10;
    }
    return implode('', $n);
}

function sem_acento(string $texto): string
{
    return strtolower(strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ç' => 'c', 'ã' => 'a', 'õ' => 'o', 'ê' => 'e', 'â' => 'a']));
}
