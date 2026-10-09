# Proware

Loja online de computadores onde você **monta o seu PC peça por peça**, com checagem de compatibilidade em tempo real, ou escolhe um PC pronto.

O projeto começou no ensino médio como um site estático de quatro páginas. Esta é a versão 2.0, refeita do zero **com as mesmas linguagens** (HTML, CSS, JavaScript e PHP), sem frameworks.

![Página inicial da Proware](docs/img/inicio.jpg)

## Antes e depois

| Versão original (ensino médio) | Versão 2.0 |
| --- | --- |
| ![Site original](docs/img/antes.jpg) | ![Site novo](docs/img/pcs-prontos.jpg) |

O código original está preservado no primeiro commit do repositório.

## O que dá para fazer

- **Monte seu PC**: escolha processador, placa-mãe, memória, placa de vídeo, armazenamento, cooler, fonte e gabinete. O configurador:
  - bloqueia peças incompatíveis e explica o motivo (soquete diferente, gabinete pequeno para a placa-mãe, processador sem vídeo integrado ou sem cooler na caixa);
  - calcula o consumo de energia e só libera fontes com folga;
  - remove sozinho uma peça que deixou de servir depois de uma troca, avisando o que mudou;
  - guarda a montagem no endereço da página, para compartilhar o link;
  - tem perfis prontos (jogos, trabalho, estudos) para começar.
- **PCs prontos** com filtro por uso e ordenação por preço. O preço de cada PC é a soma das peças, e o botão "Personalizar" abre o PC no configurador.
- **Periféricos** e **página de produto** com especificações técnicas.
- **Carrinho** em gaveta lateral, salvo no navegador e sincronizado entre abas.
- **Cadastro e login** com validação de CPF, máscaras de CPF, celular e CEP, e senha guardada com `password_hash`.
- **Checkout** com endereço preenchido automaticamente pelo CEP ([ViaCEP](https://viacep.com.br)), Pix com 5% de desconto, cartão em 10x ou boleto.
- **Minha conta** com histórico de pedidos e acompanhamento de cada um.
- **Contato** com formulário e perguntas frequentes.
- Layout responsivo, testado de 320 px (celular pequeno) até telas grandes.

<p align="center">
  <img src="docs/img/celular.jpg" alt="Página inicial, configurador e carrinho no celular" width="820">
</p>

![Configurador Monte seu PC](docs/img/monte-seu-pc.jpg)

## Tecnologias

| Camada | Como foi feito |
| --- | --- |
| HTML | Semântico e acessível: navegação por teclado, `aria-*`, link "pular para o conteúdo" e `<dialog>` nativo no carrinho |
| CSS | Um único arquivo, sem frameworks, com variáveis CSS, Grid, Flexbox e `:has()` |
| JavaScript | Puro (sem jQuery), dividido em `app.js` (carrinho, carrossel, formulários), `montar.js` (configurador) e `checkout.js` |
| PHP | 8.0 ou superior, sem banco de dados: clientes, pedidos e mensagens ficam em arquivos JSON com trava de escrita |

## Como rodar

Você precisa do **PHP 8.0 ou superior** (testado no 8.2).

**Opção 1: servidor embutido do PHP**

```bash
php -S localhost:8000 router.php
```

Depois abra <http://localhost:8000>.

**Opção 2: XAMPP**

Copie a pasta do projeto para `C:\xampp\htdocs\proware`, inicie o Apache e abra <http://localhost/proware/>.

Não é preciso configurar nada além disso: a pasta `app/storage` é criada e preenchida automaticamente no primeiro cadastro.

## Estrutura

```
├── index.php, montar.php, pcs-prontos.php, ...   páginas
├── app/
│   ├── bootstrap.php     sessão, cabeçalhos de segurança e constantes da loja
│   ├── catalogo.php      peças, PCs prontos, periféricos e regras de compatibilidade
│   ├── usuarios.php      cadastro, login e sessão
│   ├── pedidos.php       carrinho → pedido (preços recalculados no servidor)
│   ├── armazenamento.php leitura e escrita dos arquivos JSON
│   ├── views/            cabeçalho, rodapé e componentes reutilizáveis
│   └── storage/          dados gerados (ignorados pelo Git)
├── assets/
│   ├── css/estilo.css
│   ├── js/               app.js, montar.js, checkout.js
│   └── img/              banners, produtos e equipe
├── router.php            roteador para o `php -S`
└── .htaccess             configuração para Apache
```

Para mudar preços ou adicionar peças, edite `app/catalogo.php`. Os PCs prontos e os perfis se atualizam sozinhos.

## Segurança

- Os preços **sempre** são recalculados no servidor: alterar o carrinho no navegador não muda o valor do pedido.
- A compatibilidade das peças é validada de novo no PHP antes de criar o pedido.
- Senhas com `password_hash`/`password_verify` e sessão renovada no login.
- Token CSRF em todos os formulários, e sair da conta só por POST.
- Saídas escapadas com `htmlspecialchars` e Content Security Policy sem scripts nem estilos inline.
- Redirecionamentos só para páginas internas e a pasta `app/` bloqueada para acesso direto.

## Equipe

Bárbara Cziniel, Caio Caram, Diogo Duo, Eduardo Giovannini, Estela Maria, Gabriel Forte, Luan Fogaça e Maria Eduarda Sachi.

> Projeto de portfólio: a Proware não é uma loja real e nenhuma compra é cobrada.
