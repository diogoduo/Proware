<?php
declare(strict_types=1);

/**
 * E-mails da loja (pedido confirmado, pedido enviado, recuperação de senha...).
 *
 * Toda mensagem fica registrada na tabela "emails" (o painel mostra a lista).
 * O envio de verdade acontece pelo Brevo (https://www.brevo.com) quando as
 * variáveis de ambiente BREVO_API_KEY e EMAIL_REMETENTE estão definidas.
 * Sem elas — por exemplo, rodando no seu computador — o e-mail só é registrado.
 */

const DOMINIOS_FICTICIOS = ['exemplo.com', 'example.com', 'teste.com'];

function envio_de_email_configurado(): bool
{
    return getenv('BREVO_API_KEY') && filter_var(getenv('EMAIL_REMETENTE'), FILTER_VALIDATE_EMAIL);
}

/** Liga/desliga os e-mails (os dados de demonstração não devem gerar mensagens). */
function emails_silenciados(?bool $silenciar = null): bool
{
    static $silenciados = false;
    if ($silenciar !== null) {
        $silenciados = $silenciar;
    }
    return $silenciados;
}

/** Endereço completo do site, para os links dentro dos e-mails. */
function url_do_site(string $caminho = ''): string
{
    $base = getenv('APP_URL');
    if (!$base && !empty($_SERVER['HTTP_HOST'])) {
        $https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        $pasta = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        $pasta = preg_replace('#/admin$#', '', $pasta);
        $base = ($https ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $pasta;
    }
    return rtrim($base ?: 'http://localhost:8000', '/') . '/' . ltrim($caminho, '/');
}

/**
 * Registra e (se configurado) envia um e-mail.
 * Devolve o status: "enviado", "registrado" (sem envio real) ou "falhou".
 */
function enviar_email(string $para, string $nome, string $assunto, string $html, string $texto, string $tipo = 'geral'): string
{
    if (emails_silenciados()) {
        return 'silenciado';
    }

    $dominio = strtolower(substr(strrchr($para, '@') ?: '', 1));
    $status = 'registrado';
    $erro = null;

    if (envio_de_email_configurado() && !in_array($dominio, DOMINIOS_FICTICIOS, true)) {
        $erro = enviar_pelo_brevo($para, $nome, $assunto, $html, $texto);
        $status = $erro === null ? 'enviado' : 'falhou';
        if ($erro !== null) {
            error_log("Falha ao enviar e-mail para $para: $erro");
        }
    }

    sql('INSERT INTO emails (para, assunto, html, texto, tipo, status, erro, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [$para, $assunto, $html, $texto, $tipo, $status, $erro, agora()]);
    return $status;
}

/** Chama a API do Brevo. Devolve null em caso de sucesso ou a mensagem de erro. */
function enviar_pelo_brevo(string $para, string $nome, string $assunto, string $html, string $texto): ?string
{
    $corpo = json_encode([
        'sender' => ['name' => LOJA['nome'], 'email' => getenv('EMAIL_REMETENTE')],
        'to' => [['email' => $para, 'name' => $nome]],
        'subject' => $assunto,
        'htmlContent' => $html,
        'textContent' => $texto,
    ], JSON_UNESCAPED_UNICODE);

    $contexto = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => "accept: application/json\r\ncontent-type: application/json\r\napi-key: " . getenv('BREVO_API_KEY') . "\r\n",
        'content' => $corpo,
        'timeout' => 10,
        'ignore_errors' => true,
    ]]);

    $resposta = @file_get_contents('https://api.brevo.com/v3/smtp/email', false, $contexto);
    $codigo = (int) preg_replace('#^HTTP/\S+\s+(\d+).*#', '$1', $http_response_header[0] ?? '');
    if ($resposta === false || $codigo < 200 || $codigo >= 300) {
        return "HTTP $codigo " . mb_substr_seguro((string) $resposta, 0, 300);
    }
    return null;
}

/* ---------- Modelos ---------- */

/**
 * Monta o HTML de um e-mail no visual da loja.
 * E-mails precisam de estilos inline: muitos leitores ignoram <style>.
 * $paragrafos é uma lista de textos simples (escapados aqui).
 */
function modelo_email(string $titulo, array $paragrafos, ?array $botao = null, array $linhas = []): array
{
    $html = '<!doctype html><html lang="pt-BR"><body style="margin:0;padding:0;background:#f4f3f7;font-family:Arial,Helvetica,sans-serif;color:#17141f">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f3f7;padding:32px 12px"><tr><td align="center">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden">'
        . '<tr><td style="background:#0f0b18;padding:22px 28px;font-size:24px;font-weight:bold;font-style:italic">'
        . '<span style="color:#c06ce8">P</span><span style="color:#7db8ee">R</span><span style="color:#8fe3c6">O</span>'
        . '<span style="color:#ffffff;font-size:15px;font-style:normal;letter-spacing:2px"> WARE</span></td></tr>'
        . '<tr><td style="padding:28px">'
        . '<h1 style="margin:0 0 16px;font-size:22px;line-height:1.3">' . e($titulo) . '</h1>';

    foreach ($paragrafos as $paragrafo) {
        $html .= '<p style="margin:0 0 14px;font-size:15px;line-height:1.6;color:#4f4a5e">' . e($paragrafo) . '</p>';
    }

    if ($linhas) {
        $html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:8px 0 18px;border-top:1px solid #e4e1ec">';
        foreach ($linhas as [$rotulo, $valor]) {
            $html .= '<tr><td style="padding:10px 0;border-bottom:1px solid #e4e1ec;font-size:14px;color:#4f4a5e">' . e($rotulo)
                . '</td><td align="right" style="padding:10px 0;border-bottom:1px solid #e4e1ec;font-size:14px;font-weight:bold">' . e($valor) . '</td></tr>';
        }
        $html .= '</table>';
    }

    if ($botao) {
        $html .= '<p style="margin:22px 0"><a href="' . e($botao[1]) . '" style="display:inline-block;padding:13px 24px;border-radius:10px;'
            . 'background:#7a2fd0;color:#ffffff;font-size:15px;font-weight:bold;text-decoration:none">' . e($botao[0]) . '</a></p>';
    }

    $html .= '</td></tr><tr><td style="padding:18px 28px;background:#f4f3f7;font-size:12px;color:#6d6880">'
        . 'Proware · ' . e(LOJA['email']) . '<br>Projeto de portfólio: nenhuma compra é cobrada de verdade.'
        . '</td></tr></table></td></tr></table></body></html>';

    $texto = $titulo . "\n\n" . implode("\n\n", $paragrafos);
    foreach ($linhas as [$rotulo, $valor]) {
        $texto .= "\n$rotulo: $valor";
    }
    if ($botao) {
        $texto .= "\n\n{$botao[0]}: {$botao[1]}";
    }
    $texto .= "\n\n— Proware";

    return ['html' => $html, 'texto' => $texto];
}

function email_pedido_confirmado(array $usuario, array $pedido): void
{
    $linhas = array_map(fn ($i) => ["{$i['qtd']}× {$i['nome']}", brl($i['preco'] * $i['qtd'])], $pedido['itens']);
    if ($pedido['desconto'] > 0) {
        $linhas[] = ['Desconto Pix', '− ' . brl($pedido['desconto'])];
    }
    $linhas[] = ['Total', brl($pedido['total'])];

    $proximoPasso = match ($pedido['pagamento']) {
        'pix' => 'Assim que o Pix for confirmado, começamos a montagem e os testes.',
        'boleto' => 'Assim que o boleto for compensado, começamos a montagem e os testes.',
        default => 'O pagamento foi aprovado e o pedido já vai para a montagem e os testes.',
    };

    $email = modelo_email(
        'Recebemos o seu pedido!',
        ['Olá, ' . primeiro_nome($usuario['nome']) . '! Obrigado por comprar na Proware.', $proximoPasso],
        ['Acompanhar pedido', url_do_site('pedido.php?codigo=' . $pedido['codigo'])],
        $linhas
    );
    enviar_email($usuario['email'], $usuario['nome'], "Pedido {$pedido['codigo']} recebido", $email['html'], $email['texto'], 'pedido');
}

function email_status_do_pedido(array $pedido): void
{
    $mensagens = [
        'pago' => ['Pagamento aprovado', 'Seu pagamento foi aprovado. Agora o seu pedido vai para a montagem e os testes.'],
        'montagem' => ['Seu PC está sendo montado', 'Nossa equipe está montando e testando o seu pedido. Avisamos quando ele sair para entrega.'],
        'enviado' => ['Seu pedido saiu para entrega', "Seu pedido já está com a transportadora. Código de rastreio: {$pedido['rastreio']}."],
        'entregue' => ['Pedido entregue', 'Seu pedido foi entregue. Esperamos que você aproveite! Qualquer problema, é só falar com a gente.'],
        'cancelado' => ['Pedido cancelado', 'Seu pedido foi cancelado. Se o pagamento já tinha sido feito, o valor será devolvido na mesma forma de pagamento.'],
    ];
    if (!isset($mensagens[$pedido['status']])) {
        return;
    }
    [$titulo, $texto] = $mensagens[$pedido['status']];

    $email = modelo_email(
        $titulo,
        ['Olá, ' . primeiro_nome($pedido['cliente_nome']) . '!', $texto],
        ['Ver o pedido', url_do_site('pedido.php?codigo=' . $pedido['codigo'])]
    );
    enviar_email($pedido['cliente_email'], $pedido['cliente_nome'], "Pedido {$pedido['codigo']}: $titulo", $email['html'], $email['texto'], 'pedido');
}

function email_recuperacao_senha(array $usuario, string $link): void
{
    $email = modelo_email(
        'Redefinir sua senha',
        [
            'Olá, ' . primeiro_nome($usuario['nome']) . '! Recebemos um pedido para criar uma nova senha para a sua conta.',
            'O link abaixo vale por 1 hora e só pode ser usado uma vez. Se não foi você, ignore este e-mail: sua senha continua a mesma.',
        ],
        ['Criar nova senha', $link]
    );
    enviar_email($usuario['email'], $usuario['nome'], 'Redefinição de senha da Proware', $email['html'], $email['texto'], 'senha');
}

/* ---------- Painel ---------- */

function listar_emails(int $limite = 100): array
{
    return sql('SELECT * FROM emails ORDER BY id DESC LIMIT ' . (int) $limite)->fetchAll();
}

function email_por_id(int $id): ?array
{
    return sql('SELECT * FROM emails WHERE id = ?', [$id])->fetch() ?: null;
}
