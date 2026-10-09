<?php
declare(strict_types=1);

/**
 * Banco de dados SQLite (um único arquivo em app/storage/proware.sqlite).
 * Não precisa de servidor de banco: só da extensão pdo_sqlite do PHP.
 *
 * Valores em dinheiro são guardados em centavos (inteiros) para evitar
 * erros de arredondamento; o resto do código trabalha com reais.
 */

final class ErroDeConfiguracao extends RuntimeException
{
}

function banco(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!class_exists('PDO') || !in_array('sqlite', PDO::getAvailableDrivers(), true)) {
        throw new ErroDeConfiguracao(
            'A extensão pdo_sqlite do PHP não está ativa. Abra o php.ini, '
            . 'remova o ";" da linha ";extension=pdo_sqlite" e reinicie o servidor.'
        );
    }

    // PROWARE_BANCO permite usar outro arquivo (ou ":memory:" nos testes).
    $caminho = getenv('PROWARE_BANCO') ?: STORAGE . '/proware.sqlite';
    if ($caminho !== ':memory:' && !is_dir(dirname($caminho))) {
        mkdir(dirname($caminho), 0775, true);
    }

    $pdo = new PDO('sqlite:' . $caminho, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 5000');

    migrar_banco($pdo);
    if (modo_demonstracao() && $caminho !== ':memory:' && !$pdo->query('SELECT 1 FROM usuarios LIMIT 1')->fetchColumn()) {
        preparar_modo_demonstracao();
    }
    return $pdo;
}

/** Executa uma consulta preparada e devolve o PDOStatement. */
function sql(string $consulta, array $parametros = []): PDOStatement
{
    $comando = banco()->prepare($consulta);
    $comando->execute($parametros);
    return $comando;
}

function em_transacao(callable $operacao): mixed
{
    $pdo = banco();
    $pdo->beginTransaction();
    try {
        $resultado = $operacao($pdo);
        $pdo->commit();
        return $resultado;
    } catch (Throwable $erro) {
        $pdo->rollBack();
        throw $erro;
    }
}

function centavos(float $reais): int
{
    return (int) round($reais * 100);
}

function reais(?int $centavos): float
{
    return ($centavos ?? 0) / 100;
}

function agora(): string
{
    return date('Y-m-d H:i:s');
}

/* ---------- Estrutura do banco (migrations) ---------- */

/**
 * Cada item da lista é uma versão do banco. O número da versão atual fica
 * em PRAGMA user_version; ao abrir o banco, as versões que faltam são aplicadas.
 */
function migrar_banco(PDO $pdo): void
{
    $migracoes = [
        1 => function (PDO $pdo): void {
            $pdo->exec(<<<'SQL'
                CREATE TABLE usuarios (
                    id          INTEGER PRIMARY KEY AUTOINCREMENT,
                    nome        TEXT    NOT NULL,
                    email       TEXT    NOT NULL UNIQUE COLLATE NOCASE,
                    cpf         TEXT    NOT NULL DEFAULT '',
                    telefone    TEXT    NOT NULL DEFAULT '',
                    senha_hash  TEXT    NOT NULL,
                    admin       INTEGER NOT NULL DEFAULT 0,
                    criado_em   TEXT    NOT NULL
                );

                CREATE TABLE pecas (
                    id              TEXT    PRIMARY KEY,
                    categoria       TEXT    NOT NULL,
                    nome            TEXT    NOT NULL,
                    detalhe         TEXT    NOT NULL,
                    preco_centavos  INTEGER NOT NULL CHECK (preco_centavos >= 0),
                    atributos       TEXT    NOT NULL DEFAULT '{}',
                    imagem          TEXT,
                    ativo           INTEGER NOT NULL DEFAULT 1,
                    ordem           INTEGER NOT NULL DEFAULT 0
                );

                CREATE TABLE produtos (
                    id                       TEXT    PRIMARY KEY,
                    tipo                     TEXT    NOT NULL CHECK (tipo IN ('pc', 'periferico')),
                    nome                     TEXT    NOT NULL,
                    resumo                   TEXT    NOT NULL,
                    categoria                TEXT,
                    icone                    TEXT,
                    uso                      TEXT,
                    imagem                   TEXT,
                    preco_centavos           INTEGER,
                    preco_original_centavos  INTEGER,
                    desconto                 REAL    NOT NULL DEFAULT 0 CHECK (desconto >= 0 AND desconto < 1),
                    pecas                    TEXT,
                    especificacoes           TEXT,
                    destaque                 INTEGER NOT NULL DEFAULT 0,
                    ativo                    INTEGER NOT NULL DEFAULT 1,
                    ordem                    INTEGER NOT NULL DEFAULT 0
                );

                CREATE TABLE pedidos (
                    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
                    codigo              TEXT    NOT NULL UNIQUE,
                    usuario_id          INTEGER NOT NULL REFERENCES usuarios (id),
                    status              TEXT    NOT NULL,
                    pagamento           TEXT    NOT NULL,
                    subtotal_centavos   INTEGER NOT NULL,
                    desconto_centavos   INTEGER NOT NULL DEFAULT 0,
                    frete_centavos      INTEGER NOT NULL DEFAULT 0,
                    total_centavos      INTEGER NOT NULL,
                    cep                 TEXT    NOT NULL,
                    rua                 TEXT    NOT NULL,
                    numero              TEXT    NOT NULL,
                    complemento         TEXT    NOT NULL DEFAULT '',
                    bairro              TEXT    NOT NULL,
                    cidade              TEXT    NOT NULL,
                    uf                  TEXT    NOT NULL,
                    rastreio            TEXT,
                    criado_em           TEXT    NOT NULL,
                    atualizado_em       TEXT    NOT NULL
                );
                CREATE INDEX pedidos_usuario ON pedidos (usuario_id);
                CREATE INDEX pedidos_status ON pedidos (status);

                CREATE TABLE pedido_itens (
                    id              INTEGER PRIMARY KEY AUTOINCREMENT,
                    pedido_id       INTEGER NOT NULL REFERENCES pedidos (id) ON DELETE CASCADE,
                    tipo            TEXT    NOT NULL,
                    produto_id      TEXT,
                    nome            TEXT    NOT NULL,
                    descricao       TEXT    NOT NULL DEFAULT '',
                    imagem          TEXT,
                    pecas           TEXT,
                    preco_centavos  INTEGER NOT NULL,
                    quantidade      INTEGER NOT NULL CHECK (quantidade > 0)
                );
                CREATE INDEX pedido_itens_pedido ON pedido_itens (pedido_id);

                CREATE TABLE pedido_eventos (
                    id          INTEGER PRIMARY KEY AUTOINCREMENT,
                    pedido_id   INTEGER NOT NULL REFERENCES pedidos (id) ON DELETE CASCADE,
                    status      TEXT    NOT NULL,
                    observacao  TEXT,
                    criado_em   TEXT    NOT NULL
                );
                CREATE INDEX pedido_eventos_pedido ON pedido_eventos (pedido_id);

                CREATE TABLE mensagens (
                    id          INTEGER PRIMARY KEY AUTOINCREMENT,
                    usuario_id  INTEGER REFERENCES usuarios (id) ON DELETE SET NULL,
                    nome        TEXT    NOT NULL,
                    email       TEXT    NOT NULL,
                    assunto     TEXT    NOT NULL,
                    mensagem    TEXT    NOT NULL,
                    status      TEXT    NOT NULL DEFAULT 'nova',
                    criado_em   TEXT    NOT NULL
                );

                CREATE TABLE tentativas_login (
                    id         INTEGER PRIMARY KEY AUTOINCREMENT,
                    chave      TEXT    NOT NULL,
                    momento    INTEGER NOT NULL
                );
                CREATE INDEX tentativas_login_chave ON tentativas_login (chave, momento);
                SQL);

            semear_catalogo($pdo, require APP . '/dados/catalogo-inicial.php');
            importar_dados_json($pdo);
        },

        // Versão 2: acesso de visitante ao painel, recuperação de senha e registro de e-mails.
        2 => function (PDO $pdo): void {
            $pdo->exec(<<<'SQL'
                ALTER TABLE usuarios ADD COLUMN somente_leitura INTEGER NOT NULL DEFAULT 0;

                CREATE TABLE redefinicoes_senha (
                    id          INTEGER PRIMARY KEY AUTOINCREMENT,
                    usuario_id  INTEGER NOT NULL REFERENCES usuarios (id) ON DELETE CASCADE,
                    token_hash  TEXT    NOT NULL UNIQUE,
                    expira_em   INTEGER NOT NULL,
                    usado_em    INTEGER,
                    criado_em   TEXT    NOT NULL
                );

                CREATE TABLE emails (
                    id          INTEGER PRIMARY KEY AUTOINCREMENT,
                    para        TEXT    NOT NULL,
                    assunto     TEXT    NOT NULL,
                    html        TEXT    NOT NULL,
                    texto       TEXT    NOT NULL,
                    tipo        TEXT    NOT NULL DEFAULT 'geral',
                    status      TEXT    NOT NULL,
                    erro        TEXT,
                    criado_em   TEXT    NOT NULL
                );
                SQL);
        },
    ];

    $versao = (int) $pdo->query('PRAGMA user_version')->fetchColumn();
    foreach ($migracoes as $numero => $migracao) {
        if ($numero <= $versao) {
            continue;
        }
        $pdo->beginTransaction();
        try {
            $migracao($pdo);
            $pdo->exec('PRAGMA user_version = ' . (int) $numero);
            $pdo->commit();
        } catch (Throwable $erro) {
            $pdo->rollBack();
            throw $erro;
        }
    }
}

/** Copia o catálogo inicial (app/dados/catalogo-inicial.php) para o banco. */
function semear_catalogo(PDO $pdo, array $dados): void
{
    $inserirPeca = $pdo->prepare(
        'INSERT INTO pecas (id, categoria, nome, detalhe, preco_centavos, atributos, imagem, ordem)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $ordem = 0;
    foreach ($dados['pecas'] as $categoria => $pecas) {
        foreach ($pecas as $id => $peca) {
            $atributos = array_diff_key($peca, array_flip(['nome', 'detalhe', 'preco', 'imagem']));
            $inserirPeca->execute([
                $id, $categoria, $peca['nome'], $peca['detalhe'], centavos((float) $peca['preco']),
                json_encode($atributos, JSON_UNESCAPED_UNICODE), $peca['imagem'] ?? null, $ordem++,
            ]);
        }
    }

    $inserirProduto = $pdo->prepare(
        'INSERT INTO produtos (id, tipo, nome, resumo, categoria, icone, uso, imagem, preco_centavos,
                               preco_original_centavos, desconto, pecas, especificacoes, destaque, ordem)
         VALUES (:id, :tipo, :nome, :resumo, :categoria, :icone, :uso, :imagem, :preco,
                 :preco_original, :desconto, :pecas, :especificacoes, :destaque, :ordem)'
    );
    $ordem = 0;
    foreach ($dados['pcs'] as $id => $pc) {
        $inserirProduto->execute([
            'id' => $id, 'tipo' => 'pc', 'nome' => $pc['nome'], 'resumo' => $pc['resumo'],
            'categoria' => null, 'icone' => null, 'uso' => $pc['uso'], 'imagem' => null,
            'preco' => null, 'preco_original' => null, 'desconto' => (float) $pc['desconto'],
            'pecas' => json_encode($pc['pecas']), 'especificacoes' => null,
            'destaque' => (int) $pc['destaque'], 'ordem' => $ordem++,
        ]);
    }
    foreach ($dados['perifericos'] as $id => $item) {
        $inserirProduto->execute([
            'id' => $id, 'tipo' => 'periferico', 'nome' => $item['nome'], 'resumo' => $item['resumo'],
            'categoria' => $item['categoria'], 'icone' => $item['icone'], 'uso' => null, 'imagem' => $item['imagem'],
            'preco' => centavos((float) $item['preco']), 'preco_original' => centavos((float) $item['preco_original']),
            'desconto' => 0, 'pecas' => null,
            'especificacoes' => json_encode($item['especificacoes'], JSON_UNESCAPED_UNICODE),
            'destaque' => 1, 'ordem' => $ordem++,
        ]);
    }
}

/**
 * A primeira versão da loja salvava tudo em arquivos JSON.
 * Se eles existirem, os dados são trazidos para o banco uma única vez
 * e os arquivos são renomeados para *.importado.
 */
function importar_dados_json(PDO $pdo): void
{
    if (getenv('PROWARE_BANCO')) {
        return; // banco alternativo (testes): não mexe nos arquivos reais
    }

    $ler = function (string $nome): ?array {
        $arquivo = STORAGE . "/$nome.json";
        if (!is_file($arquivo)) {
            return null;
        }
        $dados = json_decode((string) file_get_contents($arquivo), true);
        return is_array($dados) ? $dados : [];
    };

    $usuarios = $ler('usuarios');
    $pedidos = $ler('pedidos');
    $mensagens = $ler('mensagens');
    if ($usuarios === null && $pedidos === null && $mensagens === null) {
        return;
    }

    $novosIds = [];
    $inserirUsuario = $pdo->prepare(
        'INSERT OR IGNORE INTO usuarios (nome, email, cpf, telefone, senha_hash, criado_em) VALUES (?, ?, ?, ?, ?, ?)'
    );
    foreach ($usuarios ?? [] as $u) {
        $inserirUsuario->execute([$u['nome'], $u['email'], $u['cpf'], $u['telefone'], $u['senha_hash'], substr(str_replace('T', ' ', $u['criado_em']), 0, 19)]);
        $novosIds[$u['id']] = (int) $pdo->query('SELECT id FROM usuarios WHERE email = ' . $pdo->quote($u['email']))->fetchColumn();
    }

    $statusAntigos = ['Pagamento aprovado' => 'pago', 'Aguardando pagamento' => 'recebido'];
    foreach ($pedidos ?? [] as $p) {
        $usuarioId = $novosIds[$p['usuario_id']] ?? null;
        if (!$usuarioId) {
            continue;
        }
        $quando = substr(str_replace('T', ' ', $p['criado_em']), 0, 19);
        $status = $statusAntigos[$p['status']] ?? 'recebido';
        $e = $p['endereco'];
        $pdo->prepare(
            'INSERT INTO pedidos (codigo, usuario_id, status, pagamento, subtotal_centavos, desconto_centavos,
                                  frete_centavos, total_centavos, cep, rua, numero, complemento, bairro, cidade, uf,
                                  criado_em, atualizado_em)
             VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $p['codigo'], $usuarioId, $status, $p['pagamento'], centavos($p['subtotal']), centavos($p['desconto']),
            centavos($p['total']), $e['cep'], $e['rua'], $e['numero'], $e['complemento'], $e['bairro'], $e['cidade'],
            $e['uf'], $quando, $quando,
        ]);
        $pedidoId = (int) $pdo->lastInsertId();
        foreach ($p['itens'] as $i) {
            $pdo->prepare(
                'INSERT INTO pedido_itens (pedido_id, tipo, produto_id, nome, descricao, imagem, pecas, preco_centavos, quantidade)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $pedidoId, $i['tipo'], $i['id'] ?? null, $i['nome'], $i['descricao'], $i['imagem'],
                isset($i['pecas']) ? json_encode($i['pecas'], JSON_UNESCAPED_UNICODE) : null,
                centavos($i['preco']), $i['qtd'],
            ]);
        }
        foreach ($status === 'pago' ? ['recebido', 'pago'] : ['recebido'] as $s) {
            $pdo->prepare('INSERT INTO pedido_eventos (pedido_id, status, criado_em) VALUES (?, ?, ?)')
                ->execute([$pedidoId, $s, $quando]);
        }
    }

    foreach ($mensagens ?? [] as $m) {
        $pdo->prepare('INSERT INTO mensagens (usuario_id, nome, email, assunto, mensagem, criado_em) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([
                $novosIds[$m['usuario_id'] ?? ''] ?? null, $m['nome'], $m['email'], $m['assunto'], $m['mensagem'],
                substr(str_replace('T', ' ', $m['recebida_em']), 0, 19),
            ]);
    }

    foreach (['usuarios', 'pedidos', 'mensagens'] as $nome) {
        $arquivo = STORAGE . "/$nome.json";
        if (is_file($arquivo)) {
            rename($arquivo, $arquivo . '.importado');
        }
    }
}
