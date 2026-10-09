<?php
declare(strict_types=1);

/**
 * Contas de clientes. As senhas nunca são guardadas: só o hash
 * gerado por password_hash(), que não pode ser revertido.
 */

function usuario_por_email(string $email): ?array
{
    $email = strtolower(trim($email));
    foreach (ler_colecao('usuarios') as $usuario) {
        if ($usuario['email'] === $email) {
            return $usuario;
        }
    }
    return null;
}

function usuario_por_id(string $id): ?array
{
    foreach (ler_colecao('usuarios') as $usuario) {
        if ($usuario['id'] === $id) {
            return $usuario;
        }
    }
    return null;
}

/** Valida os dados do formulário de cadastro. Devolve os erros por campo. */
function validar_cadastro(array $dados): array
{
    $erros = [];

    if (mb_strlen_seguro($dados['nome']) < 3 || !str_contains($dados['nome'], ' ')) {
        $erros['nome'] = 'Informe seu nome completo.';
    }
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erros['email'] = 'Informe um e-mail válido.';
    } elseif (usuario_por_email($dados['email'])) {
        $erros['email'] = 'Já existe uma conta com este e-mail. Que tal entrar?';
    }
    if (!cpf_valido($dados['cpf'])) {
        $erros['cpf'] = 'CPF inválido. Confira os números.';
    }
    $telefone = so_digitos($dados['telefone']);
    if (strlen($telefone) < 10 || strlen($telefone) > 11) {
        $erros['telefone'] = 'Informe o celular com DDD.';
    }
    if (strlen($dados['senha']) < 8) {
        $erros['senha'] = 'A senha precisa ter pelo menos 8 caracteres.';
    } elseif (!preg_match('/[A-Za-z]/', $dados['senha']) || !preg_match('/\d/', $dados['senha'])) {
        $erros['senha'] = 'Use letras e números na senha.';
    }
    if ($dados['senha'] !== $dados['confirmar_senha']) {
        $erros['confirmar_senha'] = 'As senhas não são iguais.';
    }
    return $erros;
}

function criar_usuario(array $dados): array
{
    $usuario = [
        'id' => novo_id(),
        'nome' => preg_replace('/\s+/', ' ', trim($dados['nome'])),
        'email' => strtolower(trim($dados['email'])),
        'cpf' => so_digitos($dados['cpf']),
        'telefone' => so_digitos($dados['telefone']),
        'senha_hash' => password_hash($dados['senha'], PASSWORD_DEFAULT),
        'criado_em' => date('c'),
    ];

    $criado = alterar_colecao('usuarios', function (array &$usuarios) use ($usuario) {
        foreach ($usuarios as $existente) {
            if ($existente['email'] === $usuario['email']) {
                return false;
            }
        }
        $usuarios[] = $usuario;
        return true;
    });

    if (!$criado) {
        throw new RuntimeException('E-mail já cadastrado.');
    }
    return $usuario;
}

function autenticar(string $email, string $senha): ?array
{
    $usuario = usuario_por_email($email);
    if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
        return $usuario;
    }
    // Mesmo tempo de resposta com ou sem conta, para não revelar quais e-mails existem.
    if (!$usuario) {
        password_verify($senha, '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG');
    }
    return null;
}

function iniciar_sessao(array $usuario): void
{
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = $usuario['id'];
}

/** Apaga os dados da sessão e troca o identificador (o antigo deixa de valer). */
function encerrar_sessao(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

function usuario_logado(): ?array
{
    static $usuario = false;
    if ($usuario === false) {
        $id = $_SESSION['usuario_id'] ?? null;
        $usuario = is_string($id) ? usuario_por_id($id) : null;
    }
    return $usuario;
}

/** Manda para o login quem não entrou na conta, voltando depois para $voltar. */
function exigir_login(string $voltar): array
{
    $usuario = usuario_logado();
    if (!$usuario) {
        flash('info', 'Entre na sua conta para continuar.');
        redirecionar('login.php?voltar=' . urlencode($voltar));
    }
    return $usuario;
}
