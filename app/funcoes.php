<?php
declare(strict_types=1);

/** Escapa texto para exibir com segurança no HTML. */
function e(mixed $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Formata um valor em reais: 1234.5 → "R$ 1.234,50". */
function brl(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function preco_pix(float $valor): float
{
    return round($valor * (1 - DESCONTO_PIX), 2);
}

function valor_parcela(float $valor): float
{
    return round($valor / PARCELAS, 2);
}

/** Caminho de um arquivo estático com versão, para o navegador não usar cache antigo. */
function asset(string $caminho): string
{
    $arquivo = RAIZ . '/' . $caminho;
    $versao = is_file($arquivo) ? filemtime($arquivo) : 0;
    return e($caminho) . '?v=' . $versao;
}

function redirecionar(string $url): void
{
    header('Location: ' . $url, true, 303);
    exit;
}

function requisicao_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Lê um campo enviado por POST (sem espaços nas pontas, a não ser que $aparar seja false). */
function post(string $campo, bool $aparar = true): string
{
    $valor = $_POST[$campo] ?? '';
    if (!is_string($valor)) {
        return '';
    }
    return $aparar ? trim($valor) : $valor;
}

/** Lê um parâmetro da URL como texto. */
function get(string $campo): string
{
    $valor = $_GET[$campo] ?? '';
    return is_string($valor) ? trim($valor) : '';
}

/**
 * Aceita apenas destinos internos (ex.: "checkout.php") para evitar
 * que um link malicioso redirecione o usuário para outro site.
 */
function destino_seguro(mixed $destino, string $padrao = 'conta.php'): string
{
    if (is_string($destino) && preg_match('/^[a-z0-9-]+\.php(\?[a-zA-Z0-9=&_-]*)?$/', $destino)) {
        return $destino;
    }
    return $padrao;
}

/* ---------- Proteção CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function campo_csrf(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valido(): bool
{
    $enviado = $_POST['_csrf'] ?? '';
    return is_string($enviado) && hash_equals(csrf_token(), $enviado);
}

/* ---------- Mensagens que sobrevivem a um redirecionamento ---------- */

function flash(string $tipo, string $mensagem): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

function flashes(): array
{
    $lista = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $lista;
}

/* ---------- Validação e formatação de documentos ---------- */

function so_digitos(string $texto): string
{
    return preg_replace('/\D+/', '', $texto) ?? '';
}

function cpf_valido(string $cpf): bool
{
    $cpf = so_digitos($cpf);
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }
    for ($posicao = 9; $posicao < 11; $posicao++) {
        $soma = 0;
        for ($i = 0; $i < $posicao; $i++) {
            $soma += (int) $cpf[$i] * ($posicao + 1 - $i);
        }
        $digito = ((10 * $soma) % 11) % 10;
        if ((int) $cpf[$posicao] !== $digito) {
            return false;
        }
    }
    return true;
}

function formatar_cpf(string $cpf): string
{
    $d = so_digitos($cpf);
    return strlen($d) === 11
        ? substr($d, 0, 3) . '.' . substr($d, 3, 3) . '.' . substr($d, 6, 3) . '-' . substr($d, 9)
        : $cpf;
}

/** Mostra só o começo e o fim do CPF: 123.***.***-09 */
function mascarar_cpf(string $cpf): string
{
    $d = so_digitos($cpf);
    return strlen($d) === 11 ? substr($d, 0, 3) . '.***.***-' . substr($d, 9) : '';
}

function formatar_telefone(string $telefone): string
{
    $d = so_digitos($telefone);
    if (strlen($d) === 11) {
        return '(' . substr($d, 0, 2) . ') ' . substr($d, 2, 5) . '-' . substr($d, 7);
    }
    if (strlen($d) === 10) {
        return '(' . substr($d, 0, 2) . ') ' . substr($d, 2, 4) . '-' . substr($d, 6);
    }
    return $telefone;
}

function formatar_cep(string $cep): string
{
    $d = so_digitos($cep);
    return strlen($d) === 8 ? substr($d, 0, 5) . '-' . substr($d, 5) : $cep;
}

function mb_strlen_seguro(string $texto): int
{
    return function_exists('mb_strlen') ? mb_strlen($texto, 'UTF-8') : strlen($texto);
}

function primeiro_nome(string $nome): string
{
    return explode(' ', trim($nome))[0] ?? $nome;
}

function data_br(string $iso): string
{
    $data = date_create($iso);
    return $data ? $data->format('d/m/Y \à\s H:i') : $iso;
}

/* ---------- Renderização ---------- */

/** Inclui um trecho de HTML de app/views com as variáveis informadas. */
function parcial(string $nome, array $variaveis = []): void
{
    extract($variaveis, EXTR_SKIP);
    require APP . '/views/' . $nome . '.php';
}

/** Mensagem de erro de um campo de formulário (ou nada). */
function erro_campo(array $erros, string $campo): string
{
    if (empty($erros[$campo])) {
        return '';
    }
    return '<p class="campo__erro" id="erro-' . e($campo) . '">' . e($erros[$campo]) . '</p>';
}

/** Atributos de acessibilidade para um campo com erro. */
function attr_erro(array $erros, string $campo): string
{
    return empty($erros[$campo]) ? '' : ' aria-invalid="true" aria-describedby="erro-' . e($campo) . '"';
}
