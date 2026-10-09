<?php
/**
 * Preenche a loja com dados de demonstração: clientes, pedidos em todas as
 * situações e mensagens de contato. Útil para apresentar o painel.
 *
 *   php ferramentas/demo.php
 *
 * Todos os clientes usam e-mails @exemplo.com e a senha "demo12345".
 * Para recomeçar do zero, apague o arquivo app/storage/proware.sqlite.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

if (sql("SELECT COUNT(*) FROM usuarios WHERE email LIKE '%@exemplo.com'")->fetchColumn() > 0) {
    fwrite(STDERR, "Os dados de demonstração já foram criados.
");
    exit(1);
}

$resumo = criar_dados_de_demonstracao();
echo "{$resumo['clientes']} clientes, {$resumo['pedidos']} pedidos e {$resumo['mensagens']} mensagens de demonstração criados.
";
echo 'Senha de todos os clientes de demonstração: ' . DEMO_SENHA_CLIENTES . "
";
