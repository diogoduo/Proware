<?php
require __DIR__ . '/app/bootstrap.php';

// Sair só por POST com token, para que um link externo não desconecte ninguém.
if (requisicao_post() && csrf_valido()) {
    encerrar_sessao();
    flash('info', 'Você saiu da sua conta. Até a próxima!');
}

redirecionar('index.php');
