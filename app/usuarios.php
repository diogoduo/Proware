<?php
declare(strict_types=1);

/**
 * Contas de clientes e administradores. As senhas nunca são guardadas:
 * só o hash gerado por password_hash(), que não pode ser revertido.
 */

const LIMITE_TENTATIVAS_LOGIN = 5;    // erros seguidos permitidos...
const JANELA_TENTATIVAS_LOGIN = 900;  // ...a cada 15 minutos

function usuario_da_linha(array|false $linha): ?array
{
    if (!$linha) {
        return null;
    }
    $linha['id'] = (int) $linha['id'];
    $linha['admin'] = (bool) $linha['admin'];
    $linha['somente_leitura'] = (bool) ($linha['somente_leitura'] ?? false);
    return $linha;
}

function usuario_por_email(string $email): ?array
{
    return usuario_da_linha(sql('SELECT * FROM usuarios WHERE email = ?', [trim($email)])->fetch());
}

function usuario_por_id(int $id): ?array
{
    return usuario_da_linha(sql('SELECT * FROM usuarios WHERE id = ?', [$id])->fetch());
}

/** Regras de nome e celular, usadas no cadastro e na edição da conta. */
function validar_dados_pessoais(array $dados): array
{
    $erros = [];
    if (mb_strlen_seguro($dados['nome']) < 3 || !str_contains($dados['nome'], ' ')) {
        $erros['nome'] = 'Informe seu nome completo.';
    }
    $telefone = so_digitos($dados['telefone']);
    if (strlen($telefone) < 10 || strlen($telefone) > 11) {
        $erros['telefone'] = 'Informe o celular com DDD.';
    }
    return $erros;
}

function validar_nova_senha(string $senha, string $confirmacao): array
{
    $erros = [];
    if (strlen($senha) < 8) {
        $erros['senha'] = 'A senha precisa ter pelo menos 8 caracteres.';
    } elseif (!preg_match('/[A-Za-z]/', $senha) || !preg_match('/\d/', $senha)) {
        $erros['senha'] = 'Use letras e números na senha.';
    }
    if ($senha !== $confirmacao) {
        $erros['confirmar_senha'] = 'As senhas não são iguais.';
    }
    return $erros;
}

/** Valida os dados do formulário de cadastro. Devolve os erros por campo. */
function validar_cadastro(array $dados): array
{
    $erros = validar_dados_pessoais($dados);
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erros['email'] = 'Informe um e-mail válido.';
    } elseif (usuario_por_email($dados['email'])) {
        $erros['email'] = 'Já existe uma conta com este e-mail. Que tal entrar?';
    }
    if (!cpf_valido($dados['cpf'])) {
        $erros['cpf'] = 'CPF inválido. Confira os números.';
    }
    return $erros + validar_nova_senha($dados['senha'], $dados['confirmar_senha']);
}

function criar_usuario(array $dados, bool $admin = false, bool $somenteLeitura = false): array
{
    try {
        sql(
            'INSERT INTO usuarios (nome, email, cpf, telefone, senha_hash, admin, somente_leitura, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                preg_replace('/\s+/', ' ', trim($dados['nome'])),
                strtolower(trim($dados['email'])),
                so_digitos($dados['cpf'] ?? ''),
                so_digitos($dados['telefone'] ?? ''),
                password_hash($dados['senha'], PASSWORD_DEFAULT),
                (int) $admin,
                (int) $somenteLeitura,
                agora(),
            ]
        );
    } catch (PDOException $erro) {
        if ($erro->getCode() === '23000') {
            throw new RuntimeException('E-mail já cadastrado.');
        }
        throw $erro;
    }
    return usuario_por_id((int) banco()->lastInsertId());
}

function atualizar_dados_pessoais(int $id, string $nome, string $telefone): void
{
    sql('UPDATE usuarios SET nome = ?, telefone = ? WHERE id = ?',
        [preg_replace('/\s+/', ' ', trim($nome)), so_digitos($telefone), $id]);
    limpar_memo();
}

function alterar_senha(int $id, string $novaSenha): void
{
    sql('UPDATE usuarios SET senha_hash = ? WHERE id = ?', [password_hash($novaSenha, PASSWORD_DEFAULT), $id]);
}

function definir_admin(string $email, bool $admin): bool
{
    return sql('UPDATE usuarios SET admin = ? WHERE email = ?', [(int) $admin, trim($email)])->rowCount() > 0;
}

function autenticar(string $email, string $senha): ?array
{
    $usuario = usuario_por_email($email);
    if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
        if (password_needs_rehash($usuario['senha_hash'], PASSWORD_DEFAULT)) {
            alterar_senha($usuario['id'], $senha);
        }
        return $usuario;
    }
    // Mesmo tempo de resposta com ou sem conta, para não revelar quais e-mails existem.
    if (!$usuario) {
        password_verify($senha, '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG');
    }
    return null;
}

/* ---------- Proteção contra tentativas de adivinhar a senha ---------- */

function chave_tentativa_login(string $email): string
{
    return hash('sha256', strtolower(trim($email)) . '|' . ip_do_cliente());
}

/** Minutos que faltam para poder tentar de novo (0 = liberado). */
function minutos_de_bloqueio(string $email): int
{
    $tentativas = sql(
        'SELECT momento FROM tentativas_login WHERE chave = ? AND momento > ? ORDER BY momento',
        [chave_tentativa_login($email), time() - JANELA_TENTATIVAS_LOGIN]
    )->fetchAll(PDO::FETCH_COLUMN);

    if (count($tentativas) < LIMITE_TENTATIVAS_LOGIN) {
        return 0;
    }
    $liberaEm = (int) $tentativas[count($tentativas) - LIMITE_TENTATIVAS_LOGIN] + JANELA_TENTATIVAS_LOGIN;
    return max(1, (int) ceil(($liberaEm - time()) / 60));
}

function registrar_falha_login(string $email): void
{
    sql('INSERT INTO tentativas_login (chave, momento) VALUES (?, ?)', [chave_tentativa_login($email), time()]);
    sql('DELETE FROM tentativas_login WHERE momento < ?', [time() - JANELA_TENTATIVAS_LOGIN]);
}

function limpar_falhas_login(string $email): void
{
    sql('DELETE FROM tentativas_login WHERE chave = ?', [chave_tentativa_login($email)]);
}

/* ---------- Sessão ---------- */

function iniciar_sessao(array $usuario): void
{
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = (int) $usuario['id'];
}

/** Apaga os dados da sessão e troca o identificador (o antigo deixa de valer). */
function encerrar_sessao(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

function usuario_logado(): ?array
{
    $id = $_SESSION['usuario_id'] ?? null;
    if (!is_int($id)) {
        return null;
    }
    return memo('usuario_logado_' . $id, fn () => usuario_por_id($id));
}

/** Manda para o login quem não entrou na conta, voltando depois para $voltar. */
function exigir_login(string $voltar): array
{
    $usuario = usuario_logado();
    if (!$usuario) {
        flash('info', 'Entre na sua conta para continuar.');
        redirecionar(url_raiz('login.php?voltar=' . urlencode($voltar)));
    }
    return $usuario;
}

/** Área administrativa: só para contas marcadas como admin. */
function exigir_admin(string $voltar): array
{
    $usuario = exigir_login($voltar);
    if (!$usuario['admin']) {
        // Para quem não é admin, o painel simplesmente "não existe".
        require RAIZ . '/404.php';
        exit;
    }
    return $usuario;
}

/* ---------- Clientes (painel) ---------- */

function listar_clientes(string $busca = ''): array
{
    $filtro = '';
    $parametros = [];
    if ($busca !== '') {
        $filtro = 'WHERE u.nome LIKE :busca OR u.email LIKE :busca';
        $parametros['busca'] = '%' . $busca . '%';
    }
    return sql(
        "SELECT u.id, u.nome, u.email, u.telefone, u.admin, u.criado_em,
                COUNT(p.id) AS pedidos,
                COALESCE(SUM(CASE WHEN p.status <> 'cancelado' THEN p.total_centavos END), 0) AS gasto_centavos,
                MAX(p.criado_em) AS ultimo_pedido
         FROM usuarios u
         LEFT JOIN pedidos p ON p.usuario_id = u.id
         $filtro
         GROUP BY u.id
         ORDER BY u.criado_em DESC",
        $parametros
    )->fetchAll();
}

/* ---------- Recuperação de senha ---------- */

const VALIDADE_LINK_SENHA = 3600; // 1 hora

/**
 * Cria um link de redefinição de senha. Só o hash do token fica no banco:
 * quem tiver acesso ao banco não consegue usar o link.
 */
function criar_link_redefinicao(array $usuario): ?string
{
    $pagina = url_do_site('redefinir-senha.php');
    if ($pagina === null) {
        return null; // sem endereço confiável, não há como mandar um link seguro
    }
    $token = bin2hex(random_bytes(32));
    sql('UPDATE redefinicoes_senha SET usado_em = ? WHERE usuario_id = ? AND usado_em IS NULL', [time(), $usuario['id']]);
    sql('INSERT INTO redefinicoes_senha (usuario_id, token_hash, expira_em, criado_em) VALUES (?, ?, ?, ?)',
        [$usuario['id'], hash('sha256', $token), time() + VALIDADE_LINK_SENHA, agora()]);
    return $pagina . '?token=' . $token;
}

/** Usuário dono de um token ainda válido (ou null se expirou, foi usado ou não existe). */
function usuario_do_token(string $token): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }
    $linha = sql(
        'SELECT usuario_id FROM redefinicoes_senha WHERE token_hash = ? AND usado_em IS NULL AND expira_em > ?',
        [hash('sha256', $token), time()]
    )->fetch();
    return $linha ? usuario_por_id((int) $linha['usuario_id']) : null;
}

function redefinir_senha_com_token(string $token, string $novaSenha): bool
{
    $usuario = usuario_do_token($token);
    if (!$usuario) {
        return false;
    }
    em_transacao(function () use ($usuario, $novaSenha) {
        alterar_senha($usuario['id'], $novaSenha);
        sql('UPDATE redefinicoes_senha SET usado_em = ? WHERE usuario_id = ? AND usado_em IS NULL', [time(), $usuario['id']]);
    });
    limpar_falhas_login($usuario['email']);
    return true;
}

/* ---------- Painel em modo somente leitura ---------- */

/**
 * Contas de visitante (modo demonstração) veem o painel mas não alteram nada.
 * Chamada antes de qualquer alteração feita pelo painel.
 */
function exigir_edicao_no_painel(array $admin, string $voltar): void
{
    if (!empty($admin['somente_leitura'])) {
        flash('info', 'Este é o acesso de demonstração: dá para ver tudo, mas as alterações estão desativadas.');
        redirecionar($voltar);
    }
}
