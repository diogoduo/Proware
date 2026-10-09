/* ==========================================================================
   Proware — Finalizar compra
   Mostra o resumo do carrinho e envia os itens para o PHP, que recalcula
   todos os preços antes de criar o pedido.
   ========================================================================== */
(() => {
  'use strict';

  const raiz = document.querySelector('[data-checkout]');
  if (!raiz || !window.Proware) return;

  const { dados, Carrinho, brl } = window.Proware;
  const form = document.getElementById('form-checkout');
  const campoItens = form.querySelector('[data-itens-carrinho]');
  const vazio = document.querySelector('[data-checkout-vazio]');
  const lista = raiz.querySelector('[data-resumo-itens]');
  const linhaDesconto = raiz.querySelector('[data-linha-desconto]');

  const formaPagamento = () => form.querySelector('[data-forma-pagamento]:checked')?.value;

  function renderizar() {
    const temItens = Carrinho.itens.length > 0;
    raiz.hidden = !temItens;
    vazio.hidden = temItens;
    campoItens.value = Carrinho.paraEnvio();
    if (!temItens) return;

    lista.replaceChildren(
      ...Carrinho.itens.map((item) => {
        const info = Carrinho.detalhes(item);
        const li = document.createElement('li');

        const imagem = document.createElement('img');
        imagem.src = info.imagem;
        imagem.alt = '';
        imagem.width = 52;
        imagem.height = 52;

        const texto = document.createElement('div');
        const nome = document.createElement('span');
        nome.textContent = info.nome;
        const quantidade = document.createElement('small');
        quantidade.textContent = `${item.qtd} × ${brl(info.preco)}`;
        texto.append(nome, quantidade);

        const preco = document.createElement('strong');
        preco.textContent = brl(info.preco * item.qtd);

        li.append(imagem, texto, preco);
        return li;
      })
    );

    const subtotal = Carrinho.subtotal();
    const forma = formaPagamento();
    const desconto = forma === 'pix' ? Math.round(subtotal * dados.descontoPix * 100) / 100 : 0;
    const total = subtotal - desconto;

    raiz.querySelector('[data-resumo-subtotal]').textContent = brl(subtotal);
    raiz.querySelector('[data-resumo-desconto]').textContent = `− ${brl(desconto)}`;
    raiz.querySelector('[data-resumo-total]').textContent = brl(total);
    linhaDesconto.hidden = desconto === 0;

    const parcelas = {
      pix: `Você economiza ${brl(desconto)} pagando no Pix.`,
      cartao: `Em até ${dados.parcelas}x de ${brl(total / dados.parcelas)} sem juros.`,
      boleto: 'À vista no boleto bancário.',
    };
    raiz.querySelector('[data-resumo-parcelas]').textContent = parcelas[forma] || '';
  }

  form.addEventListener('change', (evento) => {
    if (evento.target.matches('[data-forma-pagamento]')) renderizar();
  });

  form.addEventListener('submit', (evento) => {
    campoItens.value = Carrinho.paraEnvio();
    if (!Carrinho.itens.length) evento.preventDefault();
  });

  Carrinho.aoMudar(renderizar);
  renderizar();
})();
