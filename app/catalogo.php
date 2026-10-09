<?php
declare(strict_types=1);

/**
 * Catálogo da loja: peças do "Monte seu PC", PCs prontos e periféricos.
 * Os dados (nomes, preços, itens ativos) ficam no banco e são editados no
 * painel admin; as regras (categorias, perfis, compatibilidade) ficam aqui.
 * O preço de cada PC pronto é a soma das peças menos o desconto, então
 * mudar o preço de uma peça atualiza todos os PCs que a usam.
 */

/** Etapas do configurador, na ordem em que aparecem. */
function categorias(): array
{
    return [
        'cpu'      => ['nome' => 'Processador',    'icone' => 'cpu',      'dica' => 'O cérebro do computador. Ele define qual placa-mãe é compatível.'],
        'placa'    => ['nome' => 'Placa-mãe',      'icone' => 'placa',    'dica' => 'Precisa ter o mesmo soquete do processador.'],
        'memoria'  => ['nome' => 'Memória RAM',    'icone' => 'memoria',  'dica' => '16 GB para jogos e estudos; 32 GB ou mais para edição e multitarefa pesada.'],
        'gpu'      => ['nome' => 'Placa de vídeo', 'icone' => 'gpu',      'dica' => 'A peça que mais pesa no desempenho em jogos.'],
        'ssd'      => ['nome' => 'Armazenamento',  'icone' => 'ssd',      'dica' => 'SSDs NVMe deixam o sistema e os jogos muito mais rápidos.'],
        'cooler'   => ['nome' => 'Cooler',         'icone' => 'cooler',   'dica' => 'Mantém o processador frio. Precisa dar conta do consumo dele.'],
        'fonte'    => ['nome' => 'Fonte',          'icone' => 'fonte',    'dica' => 'Calculamos o consumo do seu PC e só mostramos fontes com folga.'],
        'gabinete' => ['nome' => 'Gabinete',       'icone' => 'gabinete', 'dica' => 'Precisa comportar o tamanho da placa-mãe.'],
    ];
}

/** Peças ativas do configurador, indexadas pelo id. */
function componentes(): array
{
    return memo('componentes', fn () => array_filter(todas_as_pecas(), fn ($p) => $p['ativo']));
}

/** Todas as peças, inclusive as desativadas no painel. */
function todas_as_pecas(): array
{
    return memo('todas_as_pecas', function () {
        $lista = [];
        foreach (sql('SELECT * FROM pecas ORDER BY ordem') as $linha) {
            $lista[$linha['id']] = [
                'id' => $linha['id'],
                'categoria' => $linha['categoria'],
                'nome' => $linha['nome'],
                'detalhe' => $linha['detalhe'],
                'preco' => reais((int) $linha['preco_centavos']),
                'ativo' => (bool) $linha['ativo'],
            ] + json_decode($linha['atributos'], true) + array_filter(['imagem' => $linha['imagem']]);
        }
        return $lista;
    });
}

function componente(string $id): ?array
{
    return componentes()[$id] ?? null;
}

function componentes_da_categoria(string $categoria): array
{
    return array_filter(componentes(), fn ($c) => $c['categoria'] === $categoria);
}

/** Para que serve cada PC pronto (usado nos filtros). */
function usos(): array
{
    return [
        'jogos'      => ['nome' => 'Jogos',                'icone' => 'gamepad'],
        'trabalho'   => ['nome' => 'Trabalho e criação',   'icone' => 'maleta'],
        'escritorio' => ['nome' => 'Estudos e escritório', 'icone' => 'livro'],
    ];
}

/** Configurações sugeridas no início do "Monte seu PC". */
function perfis(): array
{
    $perfis = [
        'jogos' => [
            'nome' => 'Jogos em Full HD', 'icone' => 'gamepad',
            'descricao' => 'Ótimo custo-benefício para jogar em 1080p.',
            'pecas' => ['cpu' => 'ryzen-5-7600', 'placa' => 'b650m-wifi', 'memoria' => 'ddr5-16gb', 'gpu' => 'rtx-5060', 'ssd' => 'ssd-1tb', 'cooler' => 'cooler-box', 'fonte' => 'fonte-650', 'gabinete' => 'gabinete-neon'],
        ],
        'jogos-pro' => [
            'nome' => 'Jogos em 1440p', 'icone' => 'raio',
            'descricao' => 'Alta taxa de quadros nos lançamentos.',
            'pecas' => ['cpu' => 'ryzen-7-7800x3d', 'placa' => 'b650m-wifi', 'memoria' => 'ddr5-32gb', 'gpu' => 'rtx-5070', 'ssd' => 'ssd-1tb', 'cooler' => 'air-120', 'fonte' => 'fonte-750', 'gabinete' => 'gabinete-prisma'],
        ],
        'criacao' => [
            'nome' => 'Trabalho e criação', 'icone' => 'maleta',
            'descricao' => 'Edição de vídeo, 3D e programação pesada.',
            'pecas' => ['cpu' => 'ryzen-9-9950x', 'placa' => 'x870-wifi', 'memoria' => 'ddr5-64gb', 'gpu' => 'rtx-5070', 'ssd' => 'ssd-2tb', 'cooler' => 'water-360', 'fonte' => 'fonte-850', 'gabinete' => 'gabinete-aurora'],
        ],
        'escritorio' => [
            'nome' => 'Estudos e escritório', 'icone' => 'livro',
            'descricao' => 'Rápido no dia a dia, sem gastar à toa.',
            'pecas' => ['cpu' => 'ryzen-5-7600', 'placa' => 'b650m-wifi', 'memoria' => 'ddr5-16gb', 'gpu' => 'sem-gpu', 'ssd' => 'ssd-500gb', 'cooler' => 'cooler-box', 'fonte' => 'fonte-550', 'gabinete' => 'gabinete-vortex'],
        ],
    ];

    // Peças desativadas no painel saem do perfil (o cliente escolhe outra).
    foreach ($perfis as &$perfil) {
        $perfil['pecas'] = array_filter($perfil['pecas'], fn ($id) => componente($id) !== null);
    }
    return $perfis;
}

/**
 * PCs prontos e periféricos à venda, já com os preços calculados.
 * Um PC sai da vitrine se alguma das suas peças for desativada.
 */
function produtos(): array
{
    return memo('produtos', function () {
        $lista = [];
        foreach (todos_os_produtos() as $id => $produto) {
            if ($produto['ativo'] && $produto['disponivel']) {
                $lista[$id] = $produto;
            }
        }
        return $lista;
    });
}

/** Todos os produtos, inclusive inativos (para o painel). */
function todos_os_produtos(): array
{
    return memo('todos_os_produtos', function () {
        $lista = [];
        foreach (sql('SELECT * FROM produtos ORDER BY ordem') as $linha) {
            $lista[$linha['id']] = produto_da_linha($linha);
        }
        return $lista;
    });
}

function produto_da_linha(array $linha): array
{
    $produto = [
        'id' => $linha['id'],
        'tipo' => $linha['tipo'],
        'nome' => $linha['nome'],
        'resumo' => $linha['resumo'],
        'destaque' => (bool) $linha['destaque'],
        'ativo' => (bool) $linha['ativo'],
    ];

    if ($linha['tipo'] === 'pc') {
        $pecas = json_decode($linha['pecas'], true);
        $soma = preco_das_pecas($pecas, todas_as_pecas());
        $desconto = (float) $linha['desconto'];
        $preco = $desconto > 0 ? preco_promocional($soma * (1 - $desconto)) : $soma;
        $inativas = array_keys(array_filter($pecas, fn ($id) => !componente($id)));

        return $produto + [
            'uso' => $linha['uso'],
            'categoria' => usos()[$linha['uso']]['nome'] ?? 'PC',
            'pecas' => $pecas,
            'desconto' => $desconto,
            'imagem' => todas_as_pecas()[$pecas['gabinete']]['imagem'] ?? '',
            'preco' => $preco,
            'preco_original' => $preco < $soma ? $soma : null,
            'disponivel' => !$inativas,
            'pecas_inativas' => $inativas,
        ];
    }

    return $produto + [
        'categoria' => $linha['categoria'],
        'icone' => $linha['icone'],
        'imagem' => $linha['imagem'],
        'preco' => reais((int) $linha['preco_centavos']),
        'preco_original' => $linha['preco_original_centavos'] ? reais((int) $linha['preco_original_centavos']) : null,
        'especificacoes' => json_decode($linha['especificacoes'] ?? '[]', true),
        'disponivel' => true,
    ];
}

function produto(string $id): ?array
{
    return produtos()[$id] ?? null;
}

function produtos_do_tipo(string $tipo): array
{
    return array_filter(produtos(), fn ($p) => $p['tipo'] === $tipo);
}

/** Arredonda um preço promocional para o padrão de vitrine: 5051,55 → 5049,90. */
function preco_promocional(float $valor): float
{
    return floor($valor / 10) * 10 - 0.10;
}

function preco_das_pecas(array $pecas, ?array $fonte = null): float
{
    $fonte ??= componentes();
    $total = 0.0;
    foreach ($pecas as $id) {
        $total += $fonte[$id]['preco'] ?? 0;
    }
    return round($total, 2);
}

function porcentagem_desconto(array $produto): int
{
    if (empty($produto['preco_original'])) {
        return 0;
    }
    return (int) round((1 - $produto['preco'] / $produto['preco_original']) * 100);
}

/** As principais especificações de um PC pronto, para cards e tabelas. */
function especificacoes_pc(array $pc): array
{
    $especificacoes = [];
    foreach (categorias() as $categoria => $info) {
        $especificacoes[$info['nome']] = componente($pc['pecas'][$categoria])['nome'];
    }
    return $especificacoes;
}

/** Consumo estimado em watts de um conjunto de peças. */
function consumo_estimado(array $pecas): int
{
    $cpu = componente($pecas['cpu'] ?? '');
    $gpu = componente($pecas['gpu'] ?? '');
    return ($cpu['consumo'] ?? 0) + ($gpu['consumo'] ?? 0) + CONSUMO_BASE;
}

/**
 * Confere se as peças escolhidas funcionam juntas.
 * Devolve a lista de problemas (vazia quando está tudo certo).
 * As mesmas regras existem em assets/js/montar.js para o aviso instantâneo.
 */
function problemas_da_montagem(array $selecao): array
{
    $problemas = [];
    $pecas = [];

    foreach (categorias() as $categoria => $info) {
        $id = $selecao[$categoria] ?? '';
        $peca = is_string($id) ? componente($id) : null;
        if (!$peca || $peca['categoria'] !== $categoria) {
            $problemas[] = 'Falta escolher: ' . $info['nome'] . '.';
            continue;
        }
        $pecas[$categoria] = $peca;
    }
    if ($problemas) {
        return $problemas;
    }

    ['cpu' => $cpu, 'placa' => $placa, 'gpu' => $gpu, 'cooler' => $cooler, 'fonte' => $fonte, 'gabinete' => $gabinete] = $pecas;

    if ($cpu['soquete'] !== $placa['soquete']) {
        $problemas[] = "A placa-mãe ({$placa['soquete']}) não é compatível com o processador ({$cpu['soquete']}).";
    }
    if (!in_array($placa['formato'], $gabinete['formatos'], true)) {
        $problemas[] = "O gabinete não comporta uma placa-mãe {$placa['formato']}.";
    }
    if (!empty($gpu['precisa_video_integrado']) && !$cpu['video_integrado']) {
        $problemas[] = 'Este processador não tem vídeo integrado: escolha uma placa de vídeo.';
    }
    if (!empty($cooler['box']) && !$cpu['cooler_incluso']) {
        $problemas[] = 'Este processador não vem com cooler na caixa.';
    }
    if (empty($cooler['box']) && $cooler['capacidade'] < $cpu['consumo']) {
        $problemas[] = 'O cooler não dá conta do consumo do processador.';
    }
    if ($fonte['potencia'] < consumo_estimado($selecao) * FOLGA_FONTE) {
        $problemas[] = 'A fonte não tem potência suficiente para estas peças.';
    }
    return $problemas;
}


/** Dados do catálogo que o JavaScript usa (carrinho e configurador). */
function catalogo_para_js(): array
{
    $produtos = [];
    foreach (produtos() as $id => $p) {
        $produtos[$id] = [
            'nome' => $p['nome'],
            'categoria' => $p['categoria'],
            'preco' => $p['preco'],
            'imagem' => 'assets/img/produtos/' . $p['imagem'],
            'url' => 'produto.php?id=' . $id,
        ];
    }

    $pecas = [];
    foreach (componentes() as $id => $c) {
        $pecas[$id] = array_filter([
            'categoria' => $c['categoria'],
            'nome' => $c['nome'],
            'preco' => $c['preco'],
            'soquete' => $c['soquete'] ?? null,
            'formato' => $c['formato'] ?? null,
            'formatos' => $c['formatos'] ?? null,
            'consumo' => $c['consumo'] ?? null,
            'capacidade' => $c['capacidade'] ?? null,
            'potencia' => $c['potencia'] ?? null,
            'videoIntegrado' => $c['video_integrado'] ?? null,
            'coolerIncluso' => $c['cooler_incluso'] ?? null,
            'precisaVideoIntegrado' => $c['precisa_video_integrado'] ?? null,
            'box' => $c['box'] ?? null,
            'imagem' => isset($c['imagem']) ? 'assets/img/produtos/' . $c['imagem'] : null,
        ], fn ($v) => $v !== null);
    }

    $categorias = [];
    foreach (categorias() as $chave => $c) {
        $categorias[$chave] = $c['nome'];
    }

    return [
        'descontoPix' => DESCONTO_PIX,
        'parcelas' => PARCELAS,
        'folgaFonte' => FOLGA_FONTE,
        'consumoBase' => CONSUMO_BASE,
        'categorias' => $categorias,
        'produtos' => $produtos,
        'pecas' => $pecas,
    ];
}

/* ---------- Edição pelo painel admin ---------- */

function atualizar_peca(string $id, float $preco, bool $ativo): void
{
    if ($preco < 0 || $preco > 100000) {
        throw new InvalidArgumentException('Preço inválido.');
    }
    sql('UPDATE pecas SET preco_centavos = ?, ativo = ? WHERE id = ?', [centavos($preco), (int) $ativo, $id]);
    limpar_memo();
}

/** PC pronto: desconto (0 a 0,9), destaque e ativo. */
function atualizar_pc(string $id, float $desconto, bool $destaque, bool $ativo): void
{
    if ($desconto < 0 || $desconto > 0.9) {
        throw new InvalidArgumentException('Desconto inválido.');
    }
    sql("UPDATE produtos SET desconto = ?, destaque = ?, ativo = ? WHERE id = ? AND tipo = 'pc'",
        [round($desconto, 4), (int) $destaque, (int) $ativo, $id]);
    limpar_memo();
}

/** Periférico: preço, preço "de" (opcional), destaque e ativo. */
function atualizar_periferico(string $id, float $preco, ?float $precoOriginal, bool $destaque, bool $ativo): void
{
    if ($preco <= 0 || $preco > 100000 || ($precoOriginal !== null && $precoOriginal <= $preco)) {
        throw new InvalidArgumentException('Preço inválido: o preço "de" precisa ser maior que o preço atual.');
    }
    sql("UPDATE produtos SET preco_centavos = ?, preco_original_centavos = ?, destaque = ?, ativo = ?
         WHERE id = ? AND tipo = 'periferico'",
        [centavos($preco), $precoOriginal === null ? null : centavos($precoOriginal), (int) $destaque, (int) $ativo, $id]);
    limpar_memo();
}
