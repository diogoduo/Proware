<?php
define('NA_AREA_ADMIN', true);
require dirname(__DIR__) . '/app/bootstrap.php';

$admin = exigir_admin('admin/catalogo.php');

$abas = ['pecas' => 'Peças do Monte seu PC', 'pcs' => 'PCs prontos', 'perifericos' => 'Periféricos'];
$aba = isset($abas[get('aba')]) ? get('aba') : 'pecas';

/** Lê um número enviado pelo formulário ("1099.90" ou "1099,90"). */
function numero_enviado(string $campo): ?float
{
    $valor = str_replace(',', '.', post($campo));
    return is_numeric($valor) ? (float) $valor : null;
}

if (requisicao_post()) {
    $aba = isset($abas[post('aba')]) ? post('aba') : 'pecas';
    exigir_edicao_no_painel($admin, 'catalogo.php?aba=' . $aba);
    $id = post('id');
    try {
        if (!csrf_valido()) {
            throw new InvalidArgumentException('Sua sessão expirou. Tente novamente.');
        }
        $ativo = post('ativo') === '1';
        $destaque = post('destaque') === '1';

        switch (post('acao')) {
            case 'peca':
                $peca = todas_as_pecas()[$id] ?? null;
                $preco = numero_enviado('preco');
                if (!$peca || $preco === null) {
                    throw new InvalidArgumentException('Preço inválido.');
                }
                atualizar_peca($id, $preco, $ativo);
                $nome = $peca['nome'];
                break;
            case 'pc':
                $desconto = numero_enviado('desconto');
                if (!isset(todos_os_produtos()[$id]) || $desconto === null) {
                    throw new InvalidArgumentException('Desconto inválido.');
                }
                atualizar_pc($id, $desconto / 100, $destaque, $ativo);
                $nome = todos_os_produtos()[$id]['nome'];
                break;
            case 'periferico':
                $preco = numero_enviado('preco');
                $precoOriginal = post('preco_original') === '' ? null : numero_enviado('preco_original');
                if (!isset(todos_os_produtos()[$id]) || $preco === null || (post('preco_original') !== '' && $precoOriginal === null)) {
                    throw new InvalidArgumentException('Preço inválido.');
                }
                atualizar_periferico($id, $preco, $precoOriginal, $destaque, $ativo);
                $nome = todos_os_produtos()[$id]['nome'];
                break;
            default:
                throw new InvalidArgumentException('Ação inválida.');
        }
        flash('sucesso', "\"$nome\" atualizado.");
    } catch (InvalidArgumentException $erro) {
        flash('erro', $erro->getMessage());
    }
    redirecionar('catalogo.php?aba=' . $aba . '#item-' . preg_replace('/[^a-z0-9-]/', '', $id));
}

$titulo = 'Catálogo e preços';
$subtitulo = 'Mudanças valem na hora para a loja. O preço dos PCs prontos acompanha o preço das peças.';
$secao = 'catalogo';

// Em quais PCs prontos cada peça aparece (para avisar antes de desativar).
$usoDasPecas = [];
foreach (todos_os_produtos() as $produto) {
    foreach ($produto['pecas'] ?? [] as $idPeca) {
        $usoDasPecas[$idPeca][] = $produto['nome'];
    }
}

require APP . '/views/admin/topo.php';
?>

<div class="admin-filtros">
    <nav class="abas" aria-label="Partes do catálogo">
        <?php foreach ($abas as $chave => $rotulo): ?>
            <a class="aba" href="admin/catalogo.php?aba=<?= e($chave) ?>"<?= $aba === $chave ? ' aria-current="page"' : '' ?>><?= e($rotulo) ?></a>
        <?php endforeach; ?>
    </nav>
</div>

<?php if ($aba === 'pecas'): ?>
    <?php foreach (categorias() as $categoria => $info): ?>
        <section class="admin-cartao catalogo-grupo" aria-labelledby="grupo-<?= e($categoria) ?>">
            <h2 id="grupo-<?= e($categoria) ?>"><?= icone($info['icone']) ?> <?= e($info['nome']) ?></h2>
            <?php foreach (array_filter(todas_as_pecas(), fn ($p) => $p['categoria'] === $categoria) as $peca): ?>
                <form class="linha-catalogo<?= $peca['ativo'] ? '' : ' is-inativa' ?>" id="item-<?= e($peca['id']) ?>" method="post" action="admin/catalogo.php" data-linha-editavel>
                    <?= campo_csrf() ?>
                    <input type="hidden" name="acao" value="peca">
                    <input type="hidden" name="aba" value="pecas">
                    <input type="hidden" name="id" value="<?= e($peca['id']) ?>">
                    <div class="linha-catalogo__nome">
                        <strong><?= e($peca['nome']) ?></strong>
                        <small><?= e($peca['detalhe']) ?></small>
                        <?php if (!empty($usoDasPecas[$peca['id']])): ?>
                            <small class="linha-catalogo__uso">Usada em: <?= e(implode(', ', $usoDasPecas[$peca['id']])) ?></small>
                        <?php endif; ?>
                    </div>
                    <label class="campo-preco">
                        <span>Preço</span>
                        <span class="campo-preco__caixa"><span aria-hidden="true">R$</span><input name="preco" type="number" min="0" max="100000" step="0.01" value="<?= e(number_format($peca['preco'], 2, '.', '')) ?>" required></span>
                    </label>
                    <label class="interruptor">
                        <input type="checkbox" name="ativo" value="1"<?= $peca['ativo'] ? ' checked' : '' ?><?= !empty($usoDasPecas[$peca['id']]) ? ' data-aviso-desativar="Desativar esta peça tira da vitrine: ' . e(implode(', ', $usoDasPecas[$peca['id']])) . '."' : '' ?>>
                        <span class="interruptor__trilho" aria-hidden="true"></span>
                        <span>Ativa</span>
                    </label>
                    <button class="botao botao--primario linha-catalogo__salvar" type="submit">Salvar</button>
                </form>
            <?php endforeach; ?>
        </section>
    <?php endforeach; ?>

<?php elseif ($aba === 'pcs'): ?>
    <section class="admin-cartao catalogo-grupo">
        <?php foreach (array_filter(todos_os_produtos(), fn ($p) => $p['tipo'] === 'pc') as $pc): ?>
            <?php $soma = preco_das_pecas($pc['pecas'], todas_as_pecas()); ?>
            <form class="linha-catalogo linha-catalogo--produto<?= $pc['ativo'] && $pc['disponivel'] ? '' : ' is-inativa' ?>" id="item-<?= e($pc['id']) ?>" method="post" action="admin/catalogo.php" data-linha-editavel>
                <?= campo_csrf() ?>
                <input type="hidden" name="acao" value="pc">
                <input type="hidden" name="aba" value="pcs">
                <input type="hidden" name="id" value="<?= e($pc['id']) ?>">
                <img class="linha-catalogo__imagem" src="assets/img/produtos/<?= e($pc['imagem']) ?>" alt="" width="56" height="56">
                <div class="linha-catalogo__nome">
                    <strong><a href="produto.php?id=<?= e($pc['id']) ?>"><?= e($pc['nome']) ?></a></strong>
                    <small>Soma das peças: <?= brl($soma) ?> · Preço final: <strong data-preco-final data-soma="<?= e($soma) ?>"><?= brl($pc['preco']) ?></strong></small>
                    <?php if (!$pc['disponivel']): ?>
                        <small class="linha-catalogo__alerta"><?= icone('alerta') ?> Fora da vitrine: <?= e(implode(', ', array_map(fn ($id) => todas_as_pecas()[$id]['nome'], $pc['pecas_inativas']))) ?> desativada(s).</small>
                    <?php endif; ?>
                </div>
                <label class="campo-preco">
                    <span>Desconto</span>
                    <span class="campo-preco__caixa"><input name="desconto" type="number" min="0" max="90" step="1" value="<?= e(round($pc['desconto'] * 100)) ?>" required data-desconto><span aria-hidden="true">%</span></span>
                </label>
                <label class="interruptor">
                    <input type="checkbox" name="destaque" value="1"<?= $pc['destaque'] ? ' checked' : '' ?>>
                    <span class="interruptor__trilho" aria-hidden="true"></span>
                    <span>Destaque</span>
                </label>
                <label class="interruptor">
                    <input type="checkbox" name="ativo" value="1"<?= $pc['ativo'] ? ' checked' : '' ?>>
                    <span class="interruptor__trilho" aria-hidden="true"></span>
                    <span>À venda</span>
                </label>
                <button class="botao botao--primario linha-catalogo__salvar" type="submit">Salvar</button>
            </form>
        <?php endforeach; ?>
        <p class="admin-nota"><?= icone('info') ?> "Destaque" coloca o PC na página inicial. Com desconto, o preço é arredondado para terminar em ,90.</p>
    </section>

<?php else: ?>
    <section class="admin-cartao catalogo-grupo">
        <?php foreach (array_filter(todos_os_produtos(), fn ($p) => $p['tipo'] === 'periferico') as $item): ?>
            <form class="linha-catalogo linha-catalogo--produto linha-catalogo--periferico<?= $item['ativo'] ? '' : ' is-inativa' ?>" id="item-<?= e($item['id']) ?>" method="post" action="admin/catalogo.php" data-linha-editavel>
                <?= campo_csrf() ?>
                <input type="hidden" name="acao" value="periferico">
                <input type="hidden" name="aba" value="perifericos">
                <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                <img class="linha-catalogo__imagem" src="assets/img/produtos/<?= e($item['imagem']) ?>" alt="" width="56" height="56">
                <div class="linha-catalogo__nome">
                    <strong><a href="produto.php?id=<?= e($item['id']) ?>"><?= e($item['nome']) ?></a></strong>
                    <small><?= e($item['categoria']) ?></small>
                </div>
                <label class="campo-preco">
                    <span>Preço</span>
                    <span class="campo-preco__caixa"><span aria-hidden="true">R$</span><input name="preco" type="number" min="0.01" max="100000" step="0.01" value="<?= e(number_format($item['preco'], 2, '.', '')) ?>" required></span>
                </label>
                <label class="campo-preco">
                    <span>Preço "de"</span>
                    <span class="campo-preco__caixa"><span aria-hidden="true">R$</span><input name="preco_original" type="number" min="0" max="100000" step="0.01" placeholder="Sem oferta" value="<?= $item['preco_original'] ? e(number_format($item['preco_original'], 2, '.', '')) : '' ?>"></span>
                </label>
                <label class="interruptor">
                    <input type="checkbox" name="destaque" value="1"<?= $item['destaque'] ? ' checked' : '' ?>>
                    <span class="interruptor__trilho" aria-hidden="true"></span>
                    <span>Destaque</span>
                </label>
                <label class="interruptor">
                    <input type="checkbox" name="ativo" value="1"<?= $item['ativo'] ? ' checked' : '' ?>>
                    <span class="interruptor__trilho" aria-hidden="true"></span>
                    <span>À venda</span>
                </label>
                <button class="botao botao--primario linha-catalogo__salvar" type="submit">Salvar</button>
            </form>
        <?php endforeach; ?>
        <p class="admin-nota"><?= icone('info') ?> Deixe o preço "de" vazio para tirar o selo de oferta.</p>
    </section>
<?php endif; ?>

<?php require APP . '/views/admin/rodape.php'; ?>
