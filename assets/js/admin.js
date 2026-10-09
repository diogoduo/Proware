/* ==========================================================================
   Proware — painel administrativo
   Gráfico, edição do catálogo e confirmações. O painel funciona sem
   JavaScript; aqui ficam só as melhorias de uso.
   ========================================================================== */
(() => {
  'use strict';

  const $ = (seletor, raiz = document) => raiz.querySelector(seletor);
  const $$ = (seletor, raiz = document) => [...raiz.querySelectorAll(seletor)];
  const brl = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
  const somenteLeitura = document.body.classList.contains('admin--somente-leitura');

  /* ---------- Gráfico de faturamento ---------- */
  // A altura das barras vem de data-proporcao (0 a 1). Fica no JavaScript porque a
  // Content Security Policy do site não permite estilos escritos direto no HTML.

  $$('[data-grafico]').forEach((grafico) => {
    $$('[data-posicao]', grafico).forEach((linha) => {
      linha.style.bottom = `${Number(linha.dataset.posicao) * 100}%`;
    });

    requestAnimationFrame(() => {
      $$('[data-proporcao]', grafico).forEach((barra) => {
        const proporcao = Number(barra.dataset.proporcao);
        barra.style.height = proporcao > 0 ? `max(3px, ${proporcao * 100}%)` : '0';
      });
    });

    const area = $('.grafico__area', grafico);
    const dica = $('[data-dica-caixa]', grafico);
    if (!area || !dica) return;

    const mostrar = (coluna) => {
      const barra = $('.grafico__barra', coluna);
      const caixaArea = area.getBoundingClientRect();
      const caixaBarra = barra.getBoundingClientRect();
      $('[data-dica-valor]', dica).textContent = coluna.dataset.dica;
      $('[data-dica-data]', dica).textContent = coluna.dataset.dicaTitulo;
      dica.hidden = false;
      const centro = caixaBarra.left + caixaBarra.width / 2 - caixaArea.left;
      const metade = dica.offsetWidth / 2;
      dica.style.left = `${Math.min(Math.max(centro, metade), caixaArea.width - metade)}px`;
      dica.style.top = `${caixaBarra.top - caixaArea.top - 8}px`;
    };
    const esconder = () => (dica.hidden = true);

    $$('.grafico__coluna', grafico).forEach((coluna) => {
      coluna.addEventListener('pointerenter', () => mostrar(coluna));
      coluna.addEventListener('focus', () => mostrar(coluna));
      coluna.addEventListener('pointerleave', esconder);
      coluna.addEventListener('blur', esconder);
    });
  });

  /* ---------- Linhas editáveis do catálogo ---------- */

  const estadoDoFormulario = (form) => JSON.stringify([...new FormData(form).entries()]);

  $$('[data-linha-editavel]').forEach((form) => {
    const botao = $('[type="submit"]', form);
    const inicial = estadoDoFormulario(form);
    botao.disabled = true;

    const atualizar = () => {
      if (somenteLeitura) return;
      const alterada = estadoDoFormulario(form) !== inicial;
      botao.disabled = !alterada;
      form.classList.toggle('is-alterada', alterada);
    };
    form.addEventListener('input', atualizar);
    form.addEventListener('change', atualizar);

    // Prévia do preço do PC pronto conforme o desconto digitado.
    const desconto = $('[data-desconto]', form);
    const precoFinal = $('[data-preco-final]', form);
    if (desconto && precoFinal) {
      desconto.addEventListener('input', () => {
        const soma = Number(precoFinal.dataset.soma);
        const porcentagem = Math.min(90, Math.max(0, Number(desconto.value) || 0));
        const preco = porcentagem > 0 ? Math.floor((soma * (1 - porcentagem / 100)) / 10) * 10 - 0.1 : soma;
        precoFinal.textContent = brl.format(preco);
      });
    }

    // Avisa antes de desativar uma peça usada em PCs prontos.
    const ativo = $('[data-aviso-desativar]', form);
    if (ativo) {
      form.addEventListener('submit', (evento) => {
        if (ativo.defaultChecked && !ativo.checked && !window.confirm(ativo.dataset.avisoDesativar + ' Continuar?')) {
          evento.preventDefault();
        }
      });
    }
  });

  /* ---------- Acesso de demonstração (somente leitura) ---------- */
  // O servidor já recusa qualquer alteração; aqui os botões só deixam isso visível.

  if (somenteLeitura) {
    $$('form[method="post"]:not([action="sair.php"]) [type="submit"]').forEach((botao) => {
      botao.disabled = true;
      botao.title = 'Desativado no acesso de demonstração';
    });
  }

  /* ---------- Confirmações ---------- */

  document.addEventListener('submit', (evento) => {
    const mensagem = evento.target.dataset?.confirmar;
    if (mensagem && !window.confirm(mensagem)) {
      evento.preventDefault();
    }
  });
})();
