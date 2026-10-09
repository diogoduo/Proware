<?php
declare(strict_types=1);

/**
 * Arquivo carregado no início de todas as páginas.
 * Configura a sessão, os cabeçalhos de segurança e as funções do projeto.
 */

define('RAIZ', dirname(__DIR__));
define('APP', __DIR__);
define('STORAGE', APP . '/storage');

const LOJA = [
    'nome'      => 'Proware',
    'slogan'    => 'PCs montados do seu jeito',
    // Troque pelos dados reais antes de publicar.
    'email'     => 'contato@proware.com.br',
    'horario'   => 'Seg. a sex., das 9h às 18h',
];

const DESCONTO_PIX = 0.05;   // 5% de desconto no Pix
const PARCELAS     = 10;     // em até 10x sem juros
const FOLGA_FONTE  = 1.2;    // a fonte precisa de 20% de folga sobre o consumo estimado
const CONSUMO_BASE = 75;     // watts da placa-mãe, memórias, SSD e fans

date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', '0');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_name('proware_sessao');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (!headers_sent()) {
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; "
        . "style-src 'self' https://fonts.googleapis.com; "
        . "font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; "
        . "connect-src 'self' https://viacep.com.br; form-action 'self'; "
        . "frame-ancestors 'none'; base-uri 'self'");
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

require APP . '/funcoes.php';
require APP . '/icones.php';
require APP . '/banco.php';
require APP . '/catalogo.php';
require APP . '/usuarios.php';
require APP . '/pedidos.php';
require APP . '/mensagens.php';

// Erros inesperados viram uma página amigável (e o detalhe vai para o log do PHP).
set_exception_handler(function (Throwable $erro): void {
    error_log((string) $erro);
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'Erro: ' . $erro->getMessage() . PHP_EOL);
        exit(1);
    }
    if (!headers_sent()) {
        http_response_code(500);
    }
    $detalhe = $erro instanceof ErroDeConfiguracao ? $erro->getMessage() : null;
    require APP . '/views/erro.php';
});
