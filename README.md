# Proware

[![Testes](https://github.com/diogoduo/Proware/actions/workflows/testes.yml/badge.svg)](https://github.com/diogoduo/Proware/actions/workflows/testes.yml)

Loja online de computadores onde você **monta o seu PC peça por peça**, com checagem de compatibilidade em tempo real, ou escolhe um PC pronto. Tem também um **painel administrativo** para acompanhar vendas, mudar a situação dos pedidos e alterar preços.

**Veja no ar: [proware.onrender.com](https://proware.onrender.com)** (o primeiro acesso pode levar cerca de 1 minuto, enquanto o servidor gratuito acorda). Os acessos de teste da loja e do painel aparecem na página de login.

O projeto começou no ensino médio como um site estático de quatro páginas. Esta é a versão 2.0, refeita do zero **com as mesmas linguagens** (HTML, CSS, JavaScript e PHP), sem frameworks.

![Página inicial da Proware](docs/img/inicio.jpg)

## Antes e depois

| Versão original (ensino médio) | Versão 2.0 |
| --- | --- |
| ![Site original](docs/img/antes.jpg) | ![Site novo](docs/img/pcs-prontos.jpg) |

O código original está preservado no primeiro commit do repositório.

## A loja

- **Monte seu PC**: escolha processador, placa-mãe, memória, placa de vídeo, armazenamento, cooler, fonte e gabinete. O configurador:
  - bloqueia peças incompatíveis e explica o motivo (soquete diferente, gabinete pequeno para a placa-mãe, processador sem vídeo integrado ou sem cooler na caixa);
  - calcula o consumo de energia e só libera fontes com folga;
  - remove sozinho uma peça que deixou de servir depois de uma troca, avisando o que mudou;
  - guarda a montagem no endereço da página, para compartilhar o link;
  - tem perfis prontos (jogos, trabalho, estudos) para começar.
- **PCs prontos** com filtro por uso e ordenação por preço. O preço de cada PC é a soma das peças, e o botão "Personalizar" abre o PC no configurador.
- **Periféricos** e **página de produto** com especificações técnicas.
- **Carrinho** em gaveta lateral, salvo no navegador e sincronizado entre abas.
- **Cadastro e login** com validação de CPF e máscaras de CPF, celular e CEP. O login bloqueia por 15 minutos depois de 5 senhas erradas.
- **Checkout** com endereço preenchido pelo CEP ([ViaCEP](https://viacep.com.br)), Pix com 5% de desconto, cartão em 10x ou boleto.
- **Minha conta**: histórico de pedidos com a data de cada etapa e o código de rastreio, além de edição de dados e troca de senha.
- **Esqueci minha senha**: link por e-mail, válido por 1 hora e de uso único.
- **E-mails** de confirmação do pedido e de cada mudança de situação (pago, em montagem, enviado com rastreio, entregue).
- **Contato** com formulário e perguntas frequentes.
- Layout responsivo, testado de 320 px (celular pequeno) até telas grandes.

<p align="center">
  <img src="docs/img/celular.jpg" alt="Página inicial, configurador e carrinho no celular" width="820">
</p>

![Configurador Monte seu PC](docs/img/monte-seu-pc.jpg)

## O painel administrativo

Em `/admin/`, só para contas de administrador:

- **Visão geral**: faturamento, número de pedidos, ticket médio, gráfico de vendas dos últimos 14 dias (com os valores também em tabela), pendências e produtos mais vendidos.
- **Pedidos**: filtros por situação, busca por código, nome ou e-mail, e a página de cada pedido com as peças a separar. O pedido avança pelas etapas recebido → pago → montagem e testes → enviado (com código de rastreio obrigatório) → entregue, ou é cancelado. O cliente acompanha tudo na conta dele.
- **Mensagens** do formulário de contato, marcadas como novas, lidas ou respondidas.
- **Clientes**, com quantidade de pedidos e total gasto.
- **E-mails**: registro de todas as mensagens enviadas pela loja, com a prévia de cada uma.
- **Catálogo e preços**: muda o preço e ativa ou desativa cada peça, define o desconto e o destaque dos PCs prontos e o preço dos periféricos. Como o preço de um PC é a soma das peças, alterar uma peça atualiza todos os PCs que a usam. Desativar uma peça tira esses PCs da vitrine.

![Visão geral do painel](docs/img/painel.jpg)

| Pedido no painel | Catálogo e preços |
| --- | --- |
| ![Detalhe de um pedido](docs/img/painel-pedido.jpg) | ![Edição do catálogo](docs/img/painel-catalogo.jpg) |

### Acesso de demonstração

No modo demonstração (usado no site publicado), existe um acesso de **visitante** ao painel: dá para ver tudo, mas o servidor recusa qualquer alteração. Assim, quem visita o portfólio conhece o painel sem estragar os dados.

## Tecnologias

| Camada | Como foi feito |
| --- | --- |
| HTML | Semântico e acessível: navegação por teclado, `aria-*`, link "pular para o conteúdo" e `<dialog>` nativo no carrinho |
| CSS | Sem frameworks: variáveis CSS, Grid, Flexbox e `:has()`, em `estilo.css` (loja) e `admin.css` (painel) |
| JavaScript | Puro, sem jQuery: `app.js` (carrinho, carrossel, formulários), `montar.js` (configurador), `checkout.js` e `admin.js` (gráfico e edição do catálogo) |
| PHP | 8.1 ou superior, com banco **SQLite** via PDO: um único arquivo, sem precisar de servidor de banco de dados |

## Como rodar

Você precisa do **PHP 8.1 ou superior** com a extensão **pdo_sqlite** ativa. No `php.ini`, a linha `extension=pdo_sqlite` deve estar sem o `;` na frente. Se ela não estiver ativa, o site mostra uma mensagem explicando isso.

**Opção 1: servidor embutido do PHP**

```bash
php -S localhost:8000 router.php
```

Depois abra <http://localhost:8000>.

**Opção 2: XAMPP**

Copie a pasta do projeto para `C:\xampp\htdocs\proware`, inicie o Apache e abra <http://localhost/proware/>.

**Opção 3: Docker**

```bash
docker build -t proware .
docker run -p 8080:80 -e PROWARE_DEMO=1 proware
```

Depois abra <http://localhost:8080>. É o mesmo ambiente usado na publicação.

O banco (`app/storage/proware.sqlite`) é criado sozinho no primeiro acesso, já com o catálogo da loja.

### Criar um administrador

```bash
php ferramentas/admin.php criar "Seu Nome" seu@email.com
```

O comando mostra uma senha gerada (ou passe a sua como último argumento). Depois é só entrar pela página de login. Para dar acesso a uma conta que já existe, use `php ferramentas/admin.php promover email@exemplo.com`.

### Dados de demonstração

```bash
php ferramentas/demo.php
```

Cria 9 clientes fictícios, 14 pedidos em todas as situações e algumas mensagens, para o painel não começar vazio. Todos os clientes de demonstração usam a senha `demo12345`. Para recomeçar do zero, apague `app/storage/proware.sqlite`.

## Publicar na internet (Render)

O repositório já vem pronto para o [Render](https://render.com), que tem plano gratuito para sites em Docker:

1. Crie uma conta no Render entrando com o GitHub.
2. Clique em **New > Blueprint** e escolha este repositório. O Render lê o arquivo `render.yaml`.
3. Preencha as variáveis pedidas:
   - `ADMIN_EMAIL` e `ADMIN_SENHA`: o seu acesso de administrador (senha com pelo menos 8 caracteres);
   - `BREVO_API_KEY` e `EMAIL_REMETENTE`: opcionais, para enviar e-mails (veja abaixo). Podem ficar em branco.
4. Clique em **Apply**. Em alguns minutos o site fica no ar em um endereço `https://proware-xxxx.onrender.com`.

Cada envio para o GitHub publica a nova versão sozinho.

**Sobre o plano gratuito:** o site "dorme" depois de 15 minutos sem visitas e leva cerca de 1 minuto para acordar. Os arquivos também são apagados quando ele reinicia, por isso o site publicado roda em **modo demonstração** (`PROWARE_DEMO=1`): sempre que começa, recria os dados de exemplo e os acessos de teste, mostrados na página de login. Para guardar dados de verdade, seria preciso um plano pago com disco persistente.

## E-mails (Brevo)

Sem configuração, os e-mails só ficam registrados no painel, em **E-mails**. É assim que você testa a recuperação de senha no seu computador. Para enviar de verdade, use o [Brevo](https://www.brevo.com), que tem plano gratuito de 300 e-mails por dia:

1. Crie uma conta no Brevo.
2. Em **Senders, Domains & Dedicated IPs > Senders**, adicione e confirme o e-mail que vai aparecer como remetente.
3. Em **SMTP & API > API Keys**, gere uma chave.
4. Defina as variáveis `BREVO_API_KEY` (a chave) e `EMAIL_REMETENTE` (o e-mail confirmado), no Render ou no seu computador.

Os clientes fictícios (`@exemplo.com`) nunca recebem e-mails de verdade.

**Endereço do site nos links:** no Render, o endereço vem sozinho da variável `RENDER_EXTERNAL_URL`. Em outra hospedagem (XAMPP publicado, VPS etc.), defina `APP_URL` com o endereço do site, por exemplo `https://minhaloja.com`. Sem ela, os e-mails saem sem link: por segurança, o site nunca monta links a partir do endereço que o visitante informa na requisição.

## Testes

```bash
php tests/executar.php
```

São 28 testes, sem dependências externas, num banco em memória. Eles cobrem as regras de compatibilidade, o recálculo dos preços do carrinho, a criação de contas e pedidos, o fluxo de situações do pedido, o bloqueio de login, a recuperação de senha, os e-mails, a edição do catálogo e as métricas do painel. O [GitHub Actions](.github/workflows/testes.yml) roda os testes em PHP 8.1, 8.2, 8.3 e 8.4 a cada envio, e também constrói a imagem Docker, sobe o site como no Render e confere as páginas.

## Estrutura

```
├── index.php, montar.php, pcs-prontos.php, ...   páginas da loja
├── admin/                    painel administrativo
├── app/
│   ├── bootstrap.php         sessão, cabeçalhos de segurança e constantes da loja
│   ├── banco.php             conexão SQLite, tabelas (migrations) e carga inicial
│   ├── catalogo.php          peças, PCs prontos, periféricos e regras de compatibilidade
│   ├── usuarios.php          contas, login, bloqueio por tentativas e acesso de admin
│   ├── pedidos.php           carrinho → pedido, situações e métricas do painel
│   ├── mensagens.php         formulário de contato
│   ├── email.php             e-mails (registro, envio pelo Brevo e modelos)
│   ├── demo.php              dados e acessos do modo demonstração
│   ├── dados/                catálogo inicial (copiado para o banco na primeira vez)
│   ├── views/                cabeçalho, rodapé e componentes da loja e do painel
│   └── storage/              banco de dados (ignorado pelo Git)
├── assets/                   css, js e imagens
├── ferramentas/              comandos de terminal (administradores e demonstração)
├── tests/                    testes automatizados
├── docker/                   inicialização e configuração do PHP na imagem
├── Dockerfile, render.yaml   publicação (Docker e Render)
├── router.php                roteador para o `php -S`
└── .htaccess                 configuração para Apache
```

## Segurança

- Os preços **sempre** são recalculados no servidor: alterar o carrinho no navegador não muda o valor do pedido.
- A compatibilidade das peças é validada de novo no PHP antes de criar o pedido.
- Consultas ao banco sempre preparadas (sem risco de SQL injection) e dinheiro guardado em centavos.
- Senhas com `password_hash` e sessão renovada no login, com bloqueio temporário depois de várias senhas erradas.
- Links de nova senha aleatórios, de uso único e válidos por 1 hora; o banco guarda só o hash deles. A página "Esqueci minha senha" responde igual exista ou não a conta, e o endereço dos links nunca vem do cabeçalho `Host` da requisição.
- Atrás do proxy do Render, o bloqueio de login usa o IP real do visitante (cabeçalho da Cloudflare), não o do proxy.
- Token CSRF em todos os formulários, e sair da conta só por POST.
- Saídas escapadas com `htmlspecialchars` e Content Security Policy sem scripts nem estilos inline.
- O painel responde "página não encontrada" para quem não é administrador, e as pastas `app/`, `docker/`, `ferramentas/` e `tests/` ficam bloqueadas para acesso pelo navegador.

## Equipe

Bárbara Cziniel, Caio Caram, Diogo Duo, Eduardo Giovannini, Estela Maria, Gabriel Forte, Luan Fogaça e Maria Eduarda Sachi.

> Projeto de portfólio: a Proware não é uma loja real e nenhuma compra é cobrada.
