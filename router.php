<?php
/**
 * Roteador para o servidor embutido do PHP:
 *   php -S localhost:8000 router.php
 * Faz o mesmo papel do .htaccess: bloqueia a pasta app/ e mostra a página 404.
 */
$caminho = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');

if (preg_match('#(^|/)(\.|app(/|$)|router\.php)#', ltrim($caminho, '/'))) {
    http_response_code(403);
    exit('Acesso negado.');
}

$arquivo = __DIR__ . $caminho;
if (is_file($arquivo) || is_dir($arquivo)) {
    return false; // o servidor entrega o arquivo (ou executa o .php) normalmente
}

require __DIR__ . '/404.php';
