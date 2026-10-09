<?php
declare(strict_types=1);

/**
 * Pedidos. O carrinho fica no navegador, mas os preços sempre são
 * recalculados aqui a partir do catálogo: o cliente não consegue
 * alterar o valor do pedido editando o JavaScript.
 */

const FORMAS_PAGAMENTO = [
    'pix'    => ['nome' => 'Pix',               'icone' => 'pix',    'descricao' => 'Aprovação na hora e 5% de desconto.'],
    'cartao' => ['nome' => 'Cartão de crédito', 'icone' => 'cartao', 'descricao' => 'Em até 10x sem juros.'],
    'boleto' => ['nome' => 'Boleto bancário',   'icone' => 'boleto', 'descricao' => 'Aprovação em até 2 dias úteis.'],
];

const QUANTIDADE_MAXIMA = 10;

const ESTADOS = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB',
    'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];

/**
 * Converte o carrinho enviado pelo navegador (JSON) em itens com preço oficial.
 * Devolve ['itens' => [...], 'subtotal' => float, 'erros' => [...]].
 */
function montar_itens_do_carrinho(string $json): array
{
    $entrada = json_decode($json, true);
    $itens = [];
    $erros = [];

    if (!is_array($entrada) || !$entrada) {
        return ['itens' => [], 'subtotal' => 0.0, 'erros' => ['Seu carrinho está vazio.']];
    }

    foreach (array_slice($entrada, 0, 30) as $linha) {
        $quantidade = (int) ($linha['qtd'] ?? 0);
        if ($quantidade < 1 || $quantidade > QUANTIDADE_MAXIMA) {
            $erros[] = 'Quantidade inválida no carrinho.';
            continue;
        }

        if (($linha['tipo'] ?? '') === 'produto') {
            $produto = produto((string) ($linha['id'] ?? ''));
            if (!$produto) {
                $erros[] = 'Um dos produtos do carrinho não está mais disponível.';
                continue;
            }
            $itens[] = [
                'tipo' => 'produto',
                'id' => $produto['id'],
                'nome' => $produto['nome'],
                'descricao' => $produto['categoria'],
                'imagem' => $produto['imagem'],
                'preco' => (float) $produto['preco'],
                'qtd' => $quantidade,
            ];
        } elseif (($linha['tipo'] ?? '') === 'montagem' && is_array($linha['pecas'] ?? null)) {
            $pecas = array_intersect_key($linha['pecas'], categorias());
            if (problemas_da_montagem($pecas)) {
                $erros[] = 'Um PC montado no carrinho tem peças incompatíveis. Revise a montagem.';
                continue;
            }
            $itens[] = [
                'tipo' => 'montagem',
                'nome' => 'PC personalizado Proware',
                'descricao' => componente($pecas['cpu'])['nome'] . ' · ' . componente($pecas['gpu'])['nome'],
                'pecas' => array_map(fn ($id) => componente($id)['nome'], $pecas),
                'imagem' => componente($pecas['gabinete'])['imagem'],
                'preco' => preco_das_pecas($pecas),
                'qtd' => $quantidade,
            ];
        } else {
            $erros[] = 'Item inválido no carrinho.';
        }
    }

    $subtotal = array_sum(array_map(fn ($i) => $i['preco'] * $i['qtd'], $itens));
    return ['itens' => $itens, 'subtotal' => round($subtotal, 2), 'erros' => $erros];
}

function validar_endereco(array $dados): array
{
    $erros = [];
    if (strlen(so_digitos($dados['cep'])) !== 8) {
        $erros['cep'] = 'Informe um CEP com 8 números.';
    }
    foreach (['rua' => 'a rua', 'numero' => 'o número', 'bairro' => 'o bairro', 'cidade' => 'a cidade'] as $campo => $nome) {
        if ($dados[$campo] === '') {
            $erros[$campo] = "Informe $nome.";
        }
    }
    if (!in_array($dados['uf'], ESTADOS, true)) {
        $erros['uf'] = 'Selecione o estado.';
    }
    return $erros;
}

function criar_pedido(array $usuario, array $carrinho, array $endereco, string $pagamento): array
{
    $desconto = $pagamento === 'pix' ? round($carrinho['subtotal'] * DESCONTO_PIX, 2) : 0.0;

    $pedido = [
        'codigo' => 'PW' . date('ymd') . '-' . strtoupper(novo_id(3)),
        'usuario_id' => $usuario['id'],
        'criado_em' => date('c'),
        'status' => $pagamento === 'cartao' ? 'Pagamento aprovado' : 'Aguardando pagamento',
        'itens' => $carrinho['itens'],
        'subtotal' => $carrinho['subtotal'],
        'desconto' => $desconto,
        'frete' => 0.0,
        'total' => round($carrinho['subtotal'] - $desconto, 2),
        'pagamento' => $pagamento,
        'endereco' => [
            'cep' => so_digitos($endereco['cep']),
            'rua' => $endereco['rua'],
            'numero' => $endereco['numero'],
            'complemento' => $endereco['complemento'],
            'bairro' => $endereco['bairro'],
            'cidade' => $endereco['cidade'],
            'uf' => $endereco['uf'],
        ],
    ];

    alterar_colecao('pedidos', function (array &$pedidos) use ($pedido) {
        $pedidos[] = $pedido;
    });
    return $pedido;
}

function pedidos_do_usuario(string $usuarioId): array
{
    $pedidos = array_filter(ler_colecao('pedidos'), fn ($p) => $p['usuario_id'] === $usuarioId);
    usort($pedidos, fn ($a, $b) => strcmp($b['criado_em'], $a['criado_em']));
    return $pedidos;
}

function pedido_do_usuario(string $codigo, string $usuarioId): ?array
{
    foreach (ler_colecao('pedidos') as $pedido) {
        if ($pedido['codigo'] === $codigo && $pedido['usuario_id'] === $usuarioId) {
            return $pedido;
        }
    }
    return null;
}

function salvar_mensagem(array $mensagem): void
{
    alterar_colecao('mensagens', function (array &$mensagens) use ($mensagem) {
        $mensagens[] = ['id' => novo_id(), 'recebida_em' => date('c')] + $mensagem;
    });
}
