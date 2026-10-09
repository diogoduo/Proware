<?php
require_once __DIR__ . '/app/bootstrap.php';

http_response_code(404);
$titulo = 'Página não encontrada';
$pagina = '';

require APP . '/views/topo.php';
?>

<section class="secao">
    <div class="container estado-vazio">
        <p class="estado-vazio__codigo texto-gradiente">404</p>
        <h1>Essa página deu tela azul</h1>
        <p>O endereço pode ter mudado ou o produto não está mais disponível.</p>
        <div class="estado-vazio__acoes">
            <a class="botao botao--primario" href="index.php">Voltar para o início</a>
            <a class="botao botao--contorno" href="pcs-prontos.php">Ver PCs prontos</a>
        </div>
    </div>
</section>

<?php require APP . '/views/rodape.php'; ?>
