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

/**
 * Situações de um pedido. "etapa" é o texto da linha do tempo que o cliente vê;
 * "classe" define a cor da etiqueta.
 */
const STATUS_PEDIDO = [
    'recebido'  => ['nome' => 'Aguardando pagamento', 'etapa' => 'Pedido recebido',    'classe' => 'aviso'],
    'pago'      => ['nome' => 'Pagamento aprovado',   'etapa' => 'Pagamento aprovado', 'classe' => 'info'],
    'montagem'  => ['nome' => 'Em montagem e testes', 'etapa' => 'Montagem e testes',  'classe' => 'info'],
    'enviado'   => ['nome' => 'Enviado',              'etapa' => 'Enviado',            'classe' => 'info'],
    'entregue'  => ['nome' => 'Entregue',             'etapa' => 'Entregue',           'classe' => 'sucesso'],
    'cancelado' => ['nome' => 'Cancelado',            'etapa' => 'Cancelado',          'classe' => 'erro'],
];

/** Caminho normal de um pedido, do recebimento à entrega. */
const FLUXO_PEDIDO = ['recebido', 'pago', 'montagem', 'enviado', 'entregue'];

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
                'produto_id' => $produto['id'],
                'nome' => $produto['nome'],
                'descricao' => $produto['categoria'],
                'imagem' => $produto['imagem'],
                'pecas' => null,
                'preco' => (float) $produto['preco'],
                'qtd' => $quantidade,
            ];
        } elseif (($linha['tipo'] ?? '') === 'montagem' && is_array($linha['pecas'] ?? null)) {
            $pecas = array_intersect_key($linha['pecas'], categorias());
            if (problemas_da_montagem($pecas)) {
                $erros[] = 'Um PC montado no carrinho tem peças incompatíveis ou indisponíveis. Revise a montagem.';
                continue;
            }
            $itens[] = [
                'tipo' => 'montagem',
                'produto_id' => null,
                'nome' => 'PC personalizado Proware',
                'descricao' => componente($pecas['cpu'])['nome'] . ' · ' . componente($pecas['gpu'])['nome'],
                'imagem' => componente($pecas['gabinete'])['imagem'],
                'pecas' => array_map(fn ($id) => componente($id)['nome'], $pecas),
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
    $total = round($carrinho['subtotal'] - $desconto, 2);
    $codigo = 'PW' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    // Cartão é aprovado na hora (simulado); Pix e boleto esperam o pagamento.
    $etapas = $pagamento === 'cartao' ? ['recebido', 'pago'] : ['recebido'];
    $quando = agora();

    em_transacao(function (PDO $pdo) use ($usuario, $carrinho, $endereco, $pagamento, $desconto, $total, $codigo, $etapas, $quando) {
        $pdo->prepare(
            'INSERT INTO pedidos (codigo, usuario_id, status, pagamento, subtotal_centavos, desconto_centavos,
                                  frete_centavos, total_centavos, cep, rua, numero, complemento, bairro, cidade, uf,
                                  criado_em, atualizado_em)
             VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $codigo, $usuario['id'], end($etapas), $pagamento, centavos($carrinho['subtotal']), centavos($desconto),
            centavos($total), so_digitos($endereco['cep']), $endereco['rua'], $endereco['numero'],
            $endereco['complemento'], $endereco['bairro'], $endereco['cidade'], $endereco['uf'], $quando, $quando,
        ]);
        $pedidoId = (int) $pdo->lastInsertId();

        $inserirItem = $pdo->prepare(
            'INSERT INTO pedido_itens (pedido_id, tipo, produto_id, nome, descricao, imagem, pecas, preco_centavos, quantidade)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($carrinho['itens'] as $item) {
            $inserirItem->execute([
                $pedidoId, $item['tipo'], $item['produto_id'], $item['nome'], $item['descricao'], $item['imagem'],
                $item['pecas'] ? json_encode($item['pecas'], JSON_UNESCAPED_UNICODE) : null,
                centavos($item['preco']), $item['qtd'],
            ]);
        }

        $inserirEvento = $pdo->prepare('INSERT INTO pedido_eventos (pedido_id, status, criado_em) VALUES (?, ?, ?)');
        foreach ($etapas as $status) {
            $inserirEvento->execute([$pedidoId, $status, $quando]);
        }
    });

    $pedido = pedido_por_codigo($codigo);
    email_pedido_confirmado($usuario, $pedido);
    return $pedido;
}

/* ---------- Leitura ---------- */

/** Junta itens, histórico e endereço a um pedido vindo do banco. */
function completar_pedidos(array $linhas): array
{
    if (!$linhas) {
        return [];
    }
    $ids = array_column($linhas, 'id');
    $marcadores = implode(',', array_fill(0, count($ids), '?'));

    $itens = [];
    foreach (sql("SELECT * FROM pedido_itens WHERE pedido_id IN ($marcadores) ORDER BY id", $ids) as $item) {
        $itens[$item['pedido_id']][] = [
            'tipo' => $item['tipo'],
            'produto_id' => $item['produto_id'],
            'nome' => $item['nome'],
            'descricao' => $item['descricao'],
            'imagem' => $item['imagem'],
            'pecas' => $item['pecas'] ? json_decode($item['pecas'], true) : null,
            'preco' => reais((int) $item['preco_centavos']),
            'qtd' => (int) $item['quantidade'],
        ];
    }

    $eventos = [];
    foreach (sql("SELECT * FROM pedido_eventos WHERE pedido_id IN ($marcadores) ORDER BY id", $ids) as $evento) {
        $eventos[$evento['pedido_id']][] = $evento;
    }

    return array_map(fn ($p) => [
        'id' => (int) $p['id'],
        'codigo' => $p['codigo'],
        'usuario_id' => (int) $p['usuario_id'],
        'status' => $p['status'],
        'pagamento' => $p['pagamento'],
        'subtotal' => reais((int) $p['subtotal_centavos']),
        'desconto' => reais((int) $p['desconto_centavos']),
        'frete' => reais((int) $p['frete_centavos']),
        'total' => reais((int) $p['total_centavos']),
        'endereco' => [
            'cep' => $p['cep'], 'rua' => $p['rua'], 'numero' => $p['numero'], 'complemento' => $p['complemento'],
            'bairro' => $p['bairro'], 'cidade' => $p['cidade'], 'uf' => $p['uf'],
        ],
        'rastreio' => $p['rastreio'],
        'criado_em' => $p['criado_em'],
        'atualizado_em' => $p['atualizado_em'],
        'itens' => $itens[$p['id']] ?? [],
        'eventos' => $eventos[$p['id']] ?? [],
        'cliente_nome' => $p['cliente_nome'] ?? null,
        'cliente_email' => $p['cliente_email'] ?? null,
    ], $linhas);
}

function pedidos_do_usuario(int $usuarioId): array
{
    return completar_pedidos(
        sql('SELECT * FROM pedidos WHERE usuario_id = ? ORDER BY criado_em DESC, id DESC', [$usuarioId])->fetchAll()
    );
}

function pedido_por_codigo(string $codigo): ?array
{
    $linha = sql(
        'SELECT p.*, u.nome AS cliente_nome, u.email AS cliente_email
         FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id WHERE p.codigo = ?',
        [$codigo]
    )->fetch();
    return $linha ? completar_pedidos([$linha])[0] : null;
}

function pedido_do_usuario(string $codigo, int $usuarioId): ?array
{
    $pedido = pedido_por_codigo($codigo);
    return $pedido && $pedido['usuario_id'] === $usuarioId ? $pedido : null;
}

/** Lista para o painel, com filtro por situação e busca por código ou cliente. */
function listar_pedidos(string $status = '', string $busca = '', int $limite = 200): array
{
    $condicoes = [];
    $parametros = [];
    if (isset(STATUS_PEDIDO[$status])) {
        $condicoes[] = 'p.status = :status';
        $parametros['status'] = $status;
    }
    if ($busca !== '') {
        $condicoes[] = '(p.codigo LIKE :busca OR u.nome LIKE :busca OR u.email LIKE :busca)';
        $parametros['busca'] = '%' . $busca . '%';
    }
    $onde = $condicoes ? 'WHERE ' . implode(' AND ', $condicoes) : '';

    return completar_pedidos(sql(
        "SELECT p.*, u.nome AS cliente_nome, u.email AS cliente_email
         FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id
         $onde
         ORDER BY p.criado_em DESC, p.id DESC
         LIMIT " . (int) $limite,
        $parametros
    )->fetchAll());
}

function contar_pedidos_por_status(): array
{
    $contagem = array_fill_keys(array_keys(STATUS_PEDIDO), 0);
    foreach (sql('SELECT status, COUNT(*) AS total FROM pedidos GROUP BY status') as $linha) {
        $contagem[$linha['status']] = (int) $linha['total'];
    }
    return $contagem;
}

/* ---------- Andamento do pedido ---------- */

/** Próxima etapa do fluxo normal (ou null se já terminou ou foi cancelado). */
function proximo_status(string $atual): ?string
{
    $posicao = array_search($atual, FLUXO_PEDIDO, true);
    return $posicao === false ? null : (FLUXO_PEDIDO[$posicao + 1] ?? null);
}

/** Dá para cancelar enquanto o pedido não saiu para entrega. */
function pode_cancelar(string $atual): bool
{
    return in_array($atual, ['recebido', 'pago', 'montagem'], true);
}

/**
 * Muda a situação de um pedido, respeitando o fluxo.
 * Para marcar como "enviado" é obrigatório informar o código de rastreio.
 */
function alterar_status_pedido(string $codigo, string $novo, string $rastreio = '', string $observacao = ''): array
{
    $pedido = pedido_por_codigo($codigo);
    if (!$pedido) {
        throw new InvalidArgumentException('Pedido não encontrado.');
    }

    $permitido = $novo === 'cancelado' ? pode_cancelar($pedido['status']) : proximo_status($pedido['status']) === $novo;
    if (!$permitido) {
        throw new InvalidArgumentException('Não é possível mudar este pedido de "'
            . STATUS_PEDIDO[$pedido['status']]['nome'] . '" para "' . (STATUS_PEDIDO[$novo]['nome'] ?? $novo) . '".');
    }

    $rastreio = strtoupper(preg_replace('/\s+/', '', $rastreio) ?? '');
    if ($novo === 'enviado' && !preg_match('/^[A-Z0-9-]{8,30}$/', $rastreio)) {
        throw new InvalidArgumentException('Informe um código de rastreio válido (8 a 30 letras e números).');
    }

    em_transacao(function (PDO $pdo) use ($pedido, $novo, $rastreio, $observacao) {
        $quando = agora();
        $pdo->prepare('UPDATE pedidos SET status = ?, rastreio = COALESCE(?, rastreio), atualizado_em = ? WHERE id = ?')
            ->execute([$novo, $novo === 'enviado' ? $rastreio : null, $quando, $pedido['id']]);
        $pdo->prepare('INSERT INTO pedido_eventos (pedido_id, status, observacao, criado_em) VALUES (?, ?, ?, ?)')
            ->execute([$pedido['id'], $novo, trim($observacao) ?: null, $quando]);
    });

    $atualizado = pedido_por_codigo($codigo);
    email_status_do_pedido($atualizado);
    return $atualizado;
}

/** Data em que o pedido chegou a cada etapa (para a linha do tempo). */
function datas_das_etapas(array $pedido): array
{
    $datas = [];
    foreach ($pedido['eventos'] as $evento) {
        $datas[$evento['status']] ??= $evento['criado_em'];
    }
    return $datas;
}

/* ---------- Números do painel ---------- */

function metricas_da_loja(int $dias = 14): array
{
    $resumo = sql(
        "SELECT COUNT(*) AS pedidos,
                COALESCE(SUM(total_centavos), 0) AS faturamento,
                COUNT(DISTINCT usuario_id) AS compradores
         FROM pedidos WHERE status <> 'cancelado'"
    )->fetch();

    // Faturamento por dia, incluindo os dias sem venda.
    $inicio = date('Y-m-d', strtotime('-' . ($dias - 1) . ' days'));
    $porDia = array_fill_keys(
        array_map(fn ($i) => date('Y-m-d', strtotime("$inicio +$i days")), range(0, $dias - 1)),
        0.0
    );
    foreach (sql(
        "SELECT substr(criado_em, 1, 10) AS dia, SUM(total_centavos) AS total
         FROM pedidos WHERE status <> 'cancelado' AND criado_em >= ? GROUP BY dia",
        [$inicio]
    ) as $linha) {
        $porDia[$linha['dia']] = reais((int) $linha['total']);
    }

    $maisVendidos = sql(
        "SELECT i.nome, SUM(i.quantidade) AS unidades, SUM(i.preco_centavos * i.quantidade) AS receita
         FROM pedido_itens i JOIN pedidos p ON p.id = i.pedido_id
         WHERE p.status <> 'cancelado'
         GROUP BY i.nome ORDER BY unidades DESC, receita DESC LIMIT 5"
    )->fetchAll();

    $pedidos = (int) $resumo['pedidos'];
    $faturamento = reais((int) $resumo['faturamento']);
    return [
        'faturamento' => $faturamento,
        'pedidos' => $pedidos,
        'ticket_medio' => $pedidos ? round($faturamento / $pedidos, 2) : 0.0,
        'clientes' => (int) sql('SELECT COUNT(*) FROM usuarios WHERE admin = 0')->fetchColumn(),
        'por_dia' => $porDia,
        'por_status' => contar_pedidos_por_status(),
        'mais_vendidos' => array_map(fn ($l) => [
            'nome' => $l['nome'], 'unidades' => (int) $l['unidades'], 'receita' => reais((int) $l['receita']),
        ], $maisVendidos),
    ];
}
