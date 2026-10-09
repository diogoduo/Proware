<?php
/**
 * Gerencia contas de administrador pelo terminal.
 *
 *   php ferramentas/admin.php criar "Nome Sobrenome" email@exemplo.com [senha]
 *   php ferramentas/admin.php promover email@exemplo.com
 *   php ferramentas/admin.php remover email@exemplo.com
 *   php ferramentas/admin.php listar
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

$comando = $argv[1] ?? '';

switch ($comando) {
    case 'criar':
        [$nome, $email] = [$argv[2] ?? '', $argv[3] ?? ''];
        if (!str_contains(trim($nome), ' ') || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sair('Informe nome completo e e-mail: php ferramentas/admin.php criar "Nome Sobrenome" email@exemplo.com', 1);
        }
        $senha = $argv[4] ?? gerar_senha();
        if ($erros = validar_nova_senha($senha, $senha)) {
            sair(reset($erros), 1);
        }
        try {
            criar_usuario(['nome' => $nome, 'email' => $email, 'senha' => $senha], true);
        } catch (RuntimeException) {
            sair("Já existe uma conta com $email. Use: php ferramentas/admin.php promover $email", 1);
        }
        sair("Administrador criado.\n  E-mail: $email\n  Senha:  $senha\nEntre em /login.php e acesse o painel em /admin/.");

    case 'promover':
    case 'remover':
        $email = $argv[2] ?? '';
        if (!definir_admin($email, $comando === 'promover')) {
            sair("Nenhuma conta encontrada com o e-mail \"$email\".", 1);
        }
        sair($comando === 'promover' ? "$email agora é administrador." : "$email deixou de ser administrador.");

    case 'listar':
        $admins = sql('SELECT nome, email FROM usuarios WHERE admin = 1 ORDER BY nome')->fetchAll();
        if (!$admins) {
            sair('Nenhum administrador cadastrado.');
        }
        foreach ($admins as $a) {
            echo "  {$a['nome']} <{$a['email']}>\n";
        }
        exit(0);

    default:
        sair(<<<TXT
            Uso:
              php ferramentas/admin.php criar "Nome Sobrenome" email@exemplo.com [senha]
              php ferramentas/admin.php promover email@exemplo.com
              php ferramentas/admin.php remover email@exemplo.com
              php ferramentas/admin.php listar
            TXT, $comando === '' ? 0 : 1);
}

function gerar_senha(): string
{
    // Letras e números sem caracteres ambíguos (0/O, 1/l).
    $caracteres = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
    do {
        $senha = '';
        for ($i = 0; $i < 12; $i++) {
            $senha .= $caracteres[random_int(0, strlen($caracteres) - 1)];
        }
    } while (!preg_match('/\d/', $senha) || !preg_match('/[a-zA-Z]/', $senha));
    return $senha;
}

function sair(string $mensagem, int $codigo = 0): never
{
    fwrite($codigo ? STDERR : STDOUT, $mensagem . PHP_EOL);
    exit($codigo);
}
