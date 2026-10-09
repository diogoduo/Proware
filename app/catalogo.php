<?php
declare(strict_types=1);

/**
 * Catálogo da loja: peças do "Monte seu PC", PCs prontos e periféricos.
 * O preço de cada PC pronto é a soma das peças, menos o desconto (se houver),
 * então basta mudar o preço de uma peça aqui para tudo se atualizar.
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

/** Todas as peças do configurador, indexadas pelo id. */
function componentes(): array
{
    static $lista = null;
    if ($lista !== null) {
        return $lista;
    }

    $porCategoria = [
        'cpu' => [
            'ryzen-5-7600'      => ['nome' => 'AMD Ryzen 5 7600',        'detalhe' => '6 núcleos / 12 threads · até 5,1 GHz',          'preco' => 1099, 'soquete' => 'AM5',     'consumo' => 88,  'video_integrado' => true,  'cooler_incluso' => true],
            'core-i5-14400f'    => ['nome' => 'Intel Core i5-14400F',    'detalhe' => '10 núcleos / 16 threads · até 4,7 GHz',         'preco' => 999,  'soquete' => 'LGA1700', 'consumo' => 148, 'video_integrado' => false, 'cooler_incluso' => true],
            'ryzen-7-9700x'     => ['nome' => 'AMD Ryzen 7 9700X',       'detalhe' => '8 núcleos / 16 threads · até 5,5 GHz',          'preco' => 1999, 'soquete' => 'AM5',     'consumo' => 88,  'video_integrado' => true,  'cooler_incluso' => false],
            'ryzen-7-7800x3d'   => ['nome' => 'AMD Ryzen 7 7800X3D',     'detalhe' => '8 núcleos / 16 threads · 96 MB de cache 3D',    'preco' => 2399, 'soquete' => 'AM5',     'consumo' => 120, 'video_integrado' => true,  'cooler_incluso' => false],
            'core-ultra-7-265k' => ['nome' => 'Intel Core Ultra 7 265K', 'detalhe' => '20 núcleos · até 5,5 GHz',                      'preco' => 2599, 'soquete' => 'LGA1851', 'consumo' => 250, 'video_integrado' => true,  'cooler_incluso' => false],
            'ryzen-9-9950x'     => ['nome' => 'AMD Ryzen 9 9950X',       'detalhe' => '16 núcleos / 32 threads · até 5,7 GHz',         'preco' => 4299, 'soquete' => 'AM5',     'consumo' => 200, 'video_integrado' => true,  'cooler_incluso' => false],
        ],
        'placa' => [
            'b760m'      => ['nome' => 'Placa-mãe B760M',         'detalhe' => 'LGA1700 · DDR5 · Micro-ATX',            'preco' => 899,  'soquete' => 'LGA1700', 'formato' => 'mATX'],
            'b650m-wifi' => ['nome' => 'Placa-mãe B650M Wi-Fi',   'detalhe' => 'AM5 · DDR5 · Micro-ATX · Wi-Fi 6',      'preco' => 1049, 'soquete' => 'AM5',     'formato' => 'mATX'],
            'x870-wifi'  => ['nome' => 'Placa-mãe X870 Wi-Fi 7',  'detalhe' => 'AM5 · DDR5 · ATX · Wi-Fi 7 · USB4',     'preco' => 2199, 'soquete' => 'AM5',     'formato' => 'ATX'],
            'z890-wifi'  => ['nome' => 'Placa-mãe Z890 Wi-Fi 7',  'detalhe' => 'LGA1851 · DDR5 · ATX · Wi-Fi 7',        'preco' => 2399, 'soquete' => 'LGA1851', 'formato' => 'ATX'],
        ],
        'memoria' => [
            'ddr5-16gb' => ['nome' => 'Memória 16 GB DDR5 5600 MHz', 'detalhe' => 'Kit 2×8 GB · dual channel',   'preco' => 449],
            'ddr5-32gb' => ['nome' => 'Memória 32 GB DDR5 6000 MHz', 'detalhe' => 'Kit 2×16 GB · CL30 · RGB',    'preco' => 849],
            'ddr5-64gb' => ['nome' => 'Memória 64 GB DDR5 6000 MHz', 'detalhe' => 'Kit 2×32 GB · CL30 · RGB',    'preco' => 1699],
        ],
        'gpu' => [
            'sem-gpu'    => ['nome' => 'Vídeo integrado do processador', 'detalhe' => 'Para estudos, escritório e jogos leves', 'preco' => 0,    'consumo' => 0,   'precisa_video_integrado' => true],
            'rtx-5060'   => ['nome' => 'NVIDIA GeForce RTX 5060 8 GB',   'detalhe' => 'Full HD no alto · DLSS 4',               'preco' => 2299, 'consumo' => 145],
            'rx-9060-xt' => ['nome' => 'AMD Radeon RX 9060 XT 16 GB',    'detalhe' => 'Full HD e 1440p · FSR 4',                'preco' => 2699, 'consumo' => 160],
            'rtx-5070'   => ['nome' => 'NVIDIA GeForce RTX 5070 12 GB',  'detalhe' => '1440p no ultra · DLSS 4',                'preco' => 4199, 'consumo' => 250],
            'rx-9070-xt' => ['nome' => 'AMD Radeon RX 9070 XT 16 GB',    'detalhe' => '1440p e 4K · FSR 4',                     'preco' => 5299, 'consumo' => 304],
            'rtx-5080'   => ['nome' => 'NVIDIA GeForce RTX 5080 16 GB',  'detalhe' => '4K no ultra · DLSS 4',                   'preco' => 8999, 'consumo' => 360],
        ],
        'ssd' => [
            'ssd-500gb' => ['nome' => 'SSD NVMe 500 GB', 'detalhe' => 'PCIe 4.0 · até 5.000 MB/s', 'preco' => 299],
            'ssd-1tb'   => ['nome' => 'SSD NVMe 1 TB',   'detalhe' => 'PCIe 4.0 · até 7.000 MB/s', 'preco' => 499],
            'ssd-2tb'   => ['nome' => 'SSD NVMe 2 TB',   'detalhe' => 'PCIe 4.0 · até 7.000 MB/s', 'preco' => 899],
        ],
        'cooler' => [
            'cooler-box' => ['nome' => 'Cooler original do processador', 'detalhe' => 'Vem na caixa de alguns processadores', 'preco' => 0,   'capacidade' => 0, 'box' => true],
            'air-120'    => ['nome' => 'Air cooler torre 120 mm ARGB',   'detalhe' => 'Dissipa até 180 W · 4 heat pipes',     'preco' => 199, 'capacidade' => 180],
            'water-240'  => ['nome' => 'Water cooler 240 mm ARGB',       'detalhe' => 'Dissipa até 250 W',                    'preco' => 499, 'capacidade' => 250],
            'water-360'  => ['nome' => 'Water cooler 360 mm ARGB',       'detalhe' => 'Dissipa até 300 W',                    'preco' => 799, 'capacidade' => 300],
        ],
        'fonte' => [
            'fonte-550'  => ['nome' => 'Fonte 550 W 80 Plus Bronze', 'detalhe' => 'PFC ativo',                       'preco' => 349,  'potencia' => 550],
            'fonte-650'  => ['nome' => 'Fonte 650 W 80 Plus Bronze', 'detalhe' => 'PFC ativo',                       'preco' => 449,  'potencia' => 650],
            'fonte-750'  => ['nome' => 'Fonte 750 W 80 Plus Gold',   'detalhe' => 'Modular · ATX 3.1',               'preco' => 699,  'potencia' => 750],
            'fonte-850'  => ['nome' => 'Fonte 850 W 80 Plus Gold',   'detalhe' => 'Modular · ATX 3.1 · PCIe 5.1',    'preco' => 899,  'potencia' => 850],
            'fonte-1000' => ['nome' => 'Fonte 1000 W 80 Plus Gold',  'detalhe' => 'Modular · ATX 3.1 · PCIe 5.1',    'preco' => 1299, 'potencia' => 1000],
        ],
        'gabinete' => [
            'gabinete-vortex'  => ['nome' => 'Gabinete Vortex',  'detalhe' => 'Micro-ATX · 3 fans RGB · controle remoto',        'preco' => 279, 'formatos' => ['mATX'],               'imagem' => 'gabinete-vortex.jpg'],
            'gabinete-inferno' => ['nome' => 'Gabinete Inferno', 'detalhe' => 'Mid tower · lateral em acrílico · LED vermelho',  'preco' => 299, 'formatos' => ['ATX', 'mATX'],        'imagem' => 'gabinete-inferno.webp'],
            'gabinete-neon'    => ['nome' => 'Gabinete Neon',    'detalhe' => 'Mid tower · vidro temperado · fita ARGB frontal', 'preco' => 349, 'formatos' => ['ATX', 'mATX'],        'imagem' => 'gabinete-neon.webp'],
            'gabinete-aurora'  => ['nome' => 'Gabinete Aurora',  'detalhe' => 'Mid tower · frontal mesh · 3 fans ARGB',          'preco' => 399, 'formatos' => ['ATX', 'mATX'],        'imagem' => 'gabinete-aurora.jpg'],
            'gabinete-prisma'  => ['nome' => 'Gabinete Prisma',  'detalhe' => 'Mid tower · vidro temperado · 4 fans RGB',        'preco' => 449, 'formatos' => ['ATX', 'mATX'],        'imagem' => 'gabinete-prisma.jpg'],
            'gabinete-titan'   => ['nome' => 'Gabinete Titan',   'detalhe' => 'Full tower · vidro temperado · 4 fans ARGB',      'preco' => 899, 'formatos' => ['E-ATX', 'ATX', 'mATX'], 'imagem' => 'gabinete-titan.jpg'],
        ],
    ];

    $lista = [];
    foreach ($porCategoria as $categoria => $itens) {
        foreach ($itens as $id => $item) {
            $lista[$id] = ['id' => $id, 'categoria' => $categoria] + $item;
        }
    }
    return $lista;
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
    return [
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
}

/** PCs prontos e periféricos, já com os preços calculados. */
function produtos(): array
{
    static $lista = null;
    if ($lista !== null) {
        return $lista;
    }

    $pcs = [
        'proware-start' => [
            'nome' => 'PC Gamer Proware Start', 'uso' => 'jogos', 'desconto' => 0, 'destaque' => true,
            'resumo' => 'A porta de entrada para jogar em Full HD com qualidade alta, sem gastar demais.',
            'pecas' => ['cpu' => 'ryzen-5-7600', 'placa' => 'b650m-wifi', 'memoria' => 'ddr5-16gb', 'gpu' => 'rtx-5060', 'ssd' => 'ssd-1tb', 'cooler' => 'cooler-box', 'fonte' => 'fonte-650', 'gabinete' => 'gabinete-inferno'],
        ],
        'proware-play' => [
            'nome' => 'PC Gamer Proware Play', 'uso' => 'jogos', 'desconto' => 0.15, 'destaque' => true,
            'resumo' => 'Processador Intel de 10 núcleos e RTX 5060 para jogar e fazer lives em Full HD.',
            'pecas' => ['cpu' => 'core-i5-14400f', 'placa' => 'b760m', 'memoria' => 'ddr5-16gb', 'gpu' => 'rtx-5060', 'ssd' => 'ssd-1tb', 'cooler' => 'cooler-box', 'fonte' => 'fonte-650', 'gabinete' => 'gabinete-neon'],
        ],
        'proware-pro' => [
            'nome' => 'PC Gamer Proware Pro', 'uso' => 'jogos', 'desconto' => 0.10, 'destaque' => true,
            'resumo' => 'Ryzen 7 com 16 GB de vídeo e 32 GB de RAM: pronto para os lançamentos em 1440p.',
            'pecas' => ['cpu' => 'ryzen-7-9700x', 'placa' => 'b650m-wifi', 'memoria' => 'ddr5-32gb', 'gpu' => 'rx-9060-xt', 'ssd' => 'ssd-1tb', 'cooler' => 'air-120', 'fonte' => 'fonte-650', 'gabinete' => 'gabinete-prisma'],
        ],
        'proware-ultra' => [
            'nome' => 'PC Gamer Proware Ultra', 'uso' => 'jogos', 'desconto' => 0, 'destaque' => false,
            'resumo' => 'O melhor processador para jogos com a RTX 5080: 4K no ultra, sem concessões.',
            'pecas' => ['cpu' => 'ryzen-7-7800x3d', 'placa' => 'x870-wifi', 'memoria' => 'ddr5-32gb', 'gpu' => 'rtx-5080', 'ssd' => 'ssd-2tb', 'cooler' => 'water-240', 'fonte' => 'fonte-850', 'gabinete' => 'gabinete-titan'],
        ],
        'proware-creator' => [
            'nome' => 'Workstation Proware Creator', 'uso' => 'trabalho', 'desconto' => 0, 'destaque' => false,
            'resumo' => '16 núcleos e 64 GB de RAM para edição de vídeo, 3D, IA e compilação.',
            'pecas' => ['cpu' => 'ryzen-9-9950x', 'placa' => 'x870-wifi', 'memoria' => 'ddr5-64gb', 'gpu' => 'rtx-5070', 'ssd' => 'ssd-2tb', 'cooler' => 'water-360', 'fonte' => 'fonte-850', 'gabinete' => 'gabinete-aurora'],
        ],
        'proware-essencial' => [
            'nome' => 'PC Proware Essencial', 'uso' => 'escritorio', 'desconto' => 0.20, 'destaque' => false,
            'resumo' => 'Compacto e rápido para estudar, trabalhar e navegar. Dá para colocar placa de vídeo depois.',
            'pecas' => ['cpu' => 'ryzen-5-7600', 'placa' => 'b650m-wifi', 'memoria' => 'ddr5-16gb', 'gpu' => 'sem-gpu', 'ssd' => 'ssd-500gb', 'cooler' => 'cooler-box', 'fonte' => 'fonte-550', 'gabinete' => 'gabinete-vortex'],
        ],
    ];

    $perifericos = [
        'monitor-odyssey-g5' => [
            'nome' => 'Monitor Gamer Samsung Odyssey G5 34" Curvo', 'categoria' => 'Monitor', 'icone' => 'monitor',
            'resumo' => 'Ultrawide de 34 polegadas com 165 Hz para mais imersão nos jogos e mais espaço para trabalhar.',
            'preco' => 2499, 'preco_original' => 2899, 'imagem' => 'monitor-odyssey-g5.jpg',
            'especificacoes' => [
                'Tela' => '34 polegadas, curvatura 1000R', 'Resolução' => '3440 × 1440 (UWQHD)', 'Taxa de atualização' => '165 Hz',
                'Tempo de resposta' => '1 ms (MPRT)', 'Painel' => 'VA', 'Recursos' => 'AMD FreeSync Premium e HDR10',
            ],
        ],
        'teclado-mecanico-tkl' => [
            'nome' => 'Teclado Mecânico Gamer TKL RGB ABNT2', 'categoria' => 'Teclado', 'icone' => 'teclado',
            'resumo' => 'Compacto, sem o teclado numérico, com switches mecânicos e o Ç no lugar certo.',
            'preco' => 229.90, 'preco_original' => 279.90, 'imagem' => 'teclado-mecanico-tkl.jpg',
            'especificacoes' => [
                'Formato' => 'TKL (sem teclado numérico)', 'Layout' => 'ABNT2', 'Switches' => 'Mecânicos',
                'Iluminação' => 'RGB com efeitos', 'Conexão' => 'USB com fio',
            ],
        ],
        'mouse-gamer-rgb' => [
            'nome' => 'Mouse Gamer RGB 3200 DPI', 'categoria' => 'Mouse', 'icone' => 'mouse',
            'resumo' => 'Leve, preciso e com iluminação RGB para combinar com o seu setup.',
            'preco' => 59.90, 'preco_original' => 79.90, 'imagem' => 'mouse-gamer-rgb.jpg',
            'especificacoes' => [
                'Sensor' => 'Óptico, até 3.200 DPI', 'Ajuste de DPI' => 'Botão dedicado', 'Iluminação' => 'RGB',
                'Conexão' => 'USB com fio',
            ],
        ],
    ];

    $lista = [];
    foreach ($pcs as $id => $pc) {
        $soma = preco_das_pecas($pc['pecas']);
        $preco = $pc['desconto'] > 0 ? preco_promocional($soma * (1 - $pc['desconto'])) : $soma;
        $lista[$id] = [
            'id' => $id,
            'tipo' => 'pc',
            'categoria' => usos()[$pc['uso']]['nome'],
            'imagem' => componente($pc['pecas']['gabinete'])['imagem'],
            'preco' => $preco,
            'preco_original' => $preco < $soma ? $soma : null,
        ] + $pc;
    }
    foreach ($perifericos as $id => $item) {
        $lista[$id] = ['id' => $id, 'tipo' => 'periferico', 'destaque' => true] + $item;
    }
    return $lista;
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

function preco_das_pecas(array $pecas): float
{
    $total = 0.0;
    foreach ($pecas as $id) {
        $total += componente($id)['preco'] ?? 0;
    }
    return $total;
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
