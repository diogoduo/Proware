<?php
declare(strict_types=1);

/**
 * Armazenamento simples em arquivos JSON dentro de app/storage.
 * Não precisa de banco de dados: funciona em qualquer servidor com PHP.
 * Cada escrita trava o arquivo para que dois pedidos ao mesmo tempo
 * não sobrescrevam um ao outro.
 */

function caminho_armazenamento(string $nome): string
{
    if (!preg_match('/^[a-z]+$/', $nome)) {
        throw new InvalidArgumentException('Nome de coleção inválido.');
    }
    if (!is_dir(STORAGE)) {
        mkdir(STORAGE, 0775, true);
    }
    return STORAGE . '/' . $nome . '.json';
}

/** Lê todos os registros de uma coleção (ex.: "usuarios"). */
function ler_colecao(string $nome): array
{
    $arquivo = caminho_armazenamento($nome);
    if (!is_file($arquivo)) {
        return [];
    }
    $handle = fopen($arquivo, 'rb');
    flock($handle, LOCK_SH);
    $conteudo = stream_get_contents($handle);
    flock($handle, LOCK_UN);
    fclose($handle);

    $dados = json_decode($conteudo ?: '[]', true);
    return is_array($dados) ? $dados : [];
}

/**
 * Altera uma coleção com o arquivo travado do começo ao fim.
 * A função recebe os registros por referência e pode devolver um resultado.
 */
function alterar_colecao(string $nome, callable $alteracao): mixed
{
    $handle = fopen(caminho_armazenamento($nome), 'c+b');
    if ($handle === false) {
        throw new RuntimeException('Não foi possível abrir o armazenamento.');
    }
    try {
        flock($handle, LOCK_EX);
        $dados = json_decode(stream_get_contents($handle) ?: '[]', true);
        $dados = is_array($dados) ? $dados : [];

        $resultado = $alteracao($dados);

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        fflush($handle);
        return $resultado;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

/** Gera um identificador aleatório curto, ex.: "a3f9c2e1b7d4". */
function novo_id(int $bytes = 6): string
{
    return bin2hex(random_bytes($bytes));
}
