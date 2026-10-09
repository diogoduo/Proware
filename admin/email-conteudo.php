<?php
/**
 * HTML de um e-mail registrado, exibido dentro de um <iframe sandbox> na página E-mails.
 * E-mails usam estilos inline, então esta resposta tem uma política própria:
 * libera estilos inline, mas continua sem scripts e sem nada externo.
 */
define('NA_AREA_ADMIN', true);
require dirname(__DIR__) . '/app/bootstrap.php';

$admin = exigir_admin('admin/emails.php');
$email = !$admin['somente_leitura'] ? email_por_id((int) get('id')) : null;
if (!$email) {
    http_response_code(404);
    exit;
}

header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; img-src data:; frame-ancestors 'self'");
header('X-Frame-Options: SAMEORIGIN');
header('Content-Type: text/html; charset=utf-8');
echo $email['html'];
