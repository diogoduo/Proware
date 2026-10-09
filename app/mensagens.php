<?php
declare(strict_types=1);

/** Mensagens enviadas pelo formulário de contato. */

const ASSUNTOS_CONTATO = [
    'montagem' => 'Ajuda para montar meu PC',
    'produto'  => 'Dúvida sobre um produto',
    'pedido'   => 'Meu pedido',
    'garantia' => 'Garantia e suporte',
    'outro'    => 'Outro assunto',
];

const STATUS_MENSAGEM = [
    'nova'       => ['nome' => 'Nova',       'plural' => 'Novas',       'classe' => 'aviso'],
    'lida'       => ['nome' => 'Lida',       'plural' => 'Lidas',       'classe' => 'info'],
    'respondida' => ['nome' => 'Respondida', 'plural' => 'Respondidas', 'classe' => 'sucesso'],
];

function salvar_mensagem(array $mensagem): void
{
    sql(
        'INSERT INTO mensagens (usuario_id, nome, email, assunto, mensagem, criado_em) VALUES (?, ?, ?, ?, ?, ?)',
        [$mensagem['usuario_id'], $mensagem['nome'], $mensagem['email'], $mensagem['assunto'], $mensagem['mensagem'], agora()]
    );
}

function listar_mensagens(string $status = ''): array
{
    if (isset(STATUS_MENSAGEM[$status])) {
        return sql('SELECT * FROM mensagens WHERE status = ? ORDER BY criado_em DESC, id DESC', [$status])->fetchAll();
    }
    return sql('SELECT * FROM mensagens ORDER BY criado_em DESC, id DESC')->fetchAll();
}

function contar_mensagens_por_status(): array
{
    $contagem = array_fill_keys(array_keys(STATUS_MENSAGEM), 0);
    foreach (sql('SELECT status, COUNT(*) AS total FROM mensagens GROUP BY status') as $linha) {
        $contagem[$linha['status']] = (int) $linha['total'];
    }
    return $contagem;
}

function marcar_mensagem(int $id, string $status): void
{
    if (!isset(STATUS_MENSAGEM[$status])) {
        throw new InvalidArgumentException('Situação inválida.');
    }
    sql('UPDATE mensagens SET status = ? WHERE id = ?', [$status, $id]);
}
