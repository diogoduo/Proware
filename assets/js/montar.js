/* ==========================================================================
   Proware — Monte seu PC
   Confere a compatibilidade das peças, calcula consumo e preço em tempo real.
   As mesmas regras existem em app/catalogo.php (problemas_da_montagem),
   que valida de novo no servidor quando o pedido é feito.
   ========================================================================== */
(() => {
  'use strict';

  const raiz = document.querySelector('[data-configurador]');
  if (!raiz || !window.Proware) return;

  const { dados, Carrinho, brl, precoPix, avisar, abrirCarrinho, pulsarBotaoCarrinho } = window.Proware;
  const $ = (seletor, base = raiz) => base.querySelector(seletor);
  const $$ = (seletor, base = raiz) => [...base.querySelectorAll(seletor)];

  const form = $('[data-form-montagem]');
  const categorias = Object.keys(dados.categorias);
  const total = categorias.length;
  const barraMovel = document.querySelector('[data-barra-montagem]');

  /** Peças escolhidas no momento: { cpu: 'ryzen-5-7600', placa: null, ... } */
  function lerSelecao() {
    const selecao = {};
    categorias.forEach((categoria) => {
      selecao[categoria] = form.querySelector(`input[name="${categoria}"]:checked`)?.value || null;
    });
    return selecao;
  }

  const peca = (id) => (id ? dados.pecas[id] : null);

  function consumoEstimado(selecao) {
    const cpu = peca(selecao.cpu);
    const gpu = peca(selecao.gpu);
    if (!cpu && !gpu) return 0;
    return (cpu?.consumo || 0) + (gpu?.consumo || 0) + dados.consumoBase;
  }

  const potenciaRecomendada = (selecao) => Math.ceil(consumoEstimado(selecao) * dados.folgaFonte);

  /** Por que uma peça não pode ser escolhida com as demais (ou null se pode). */
  function motivoBloqueio(id, selecao) {
    const item = peca(id);
    const cpu = peca(selecao.cpu);
    const placa = peca(selecao.placa);

    switch (item.categoria) {
      case 'placa':
        if (cpu && item.soquete !== cpu.soquete) return `Soquete ${item.soquete}: o processador escolhido é ${cpu.soquete}`;
        break;
      case 'gpu':
        if (item.precisaVideoIntegrado && cpu && !cpu.videoIntegrado) return 'O processador escolhido não tem vídeo integrado';
        break;
      case 'cooler':
        if (item.box && cpu && !cpu.coolerIncluso) return 'O processador escolhido não vem com cooler';
        if (!item.box && cpu && item.capacidade < cpu.consumo) return `Dissipa ${item.capacidade} W; o processador chega a ${cpu.consumo} W`;
        break;
      case 'fonte': {
        const recomendada = potenciaRecomendada(selecao);
        if (recomendada && item.potencia < recomendada) return `Pouca folga: recomendamos pelo menos ${recomendada} W`;
        break;
      }
      case 'gabinete':
        if (placa && !item.formatos.includes(placa.formato)) return `Não comporta placa-mãe ${placa.formato}`;
        break;
    }
    return null;
  }

  /**
   * Bloqueia as opções incompatíveis e desmarca as que deixaram de servir.
   * As categorias estão em ordem de dependência (processador antes da placa-mãe,
   * placa de vídeo antes da fonte etc.), então uma única passada basta.
   */
  function aplicarRegras() {
    const removidas = [];
    let selecao = lerSelecao();

    categorias.forEach((categoria) => {
      $$(`input[name="${categoria}"]`).forEach((input) => {
        const motivo = motivoBloqueio(input.value, selecao);
        input.disabled = Boolean(motivo);
        input.closest('.opcao').querySelector('[data-motivo]').textContent = motivo || '';
        if (motivo && input.checked) {
          input.checked = false;
          removidas.push(dados.categorias[categoria].toLowerCase());
          selecao = lerSelecao();
        }
      });
    });

    return { selecao, removidas };
  }

  function renderizar(selecao) {
    const escolhidas = categorias.filter((categoria) => selecao[categoria]);
    const soma = escolhidas.reduce((acumulado, categoria) => acumulado + peca(selecao[categoria]).preco, 0);
    const completa = escolhidas.length === total;

    // Etapas e lista do resumo
    categorias.forEach((categoria) => {
      const item = peca(selecao[categoria]);
      document.getElementById(`etapa-${categoria}`).classList.toggle('etapa--completa', Boolean(item));
      const linha = $(`[data-resumo="${categoria}"]`);
      linha.classList.toggle('is-escolhido', Boolean(item));
      $('[data-nome]', linha).textContent = item ? item.nome : 'Não escolhido';
      $('[data-preco]', linha).textContent = item ? (item.preco ? brl(item.preco) : 'Incluso') : '';
    });

    const progresso = `${escolhidas.length} de ${total}`;
    $('[data-progresso-texto]').textContent = progresso;
    $('[data-progresso-barra]').style.width = `${(escolhidas.length / total) * 100}%`;

    // Consumo de energia
    const consumo = consumoEstimado(selecao);
    const fonte = peca(selecao.fonte);
    const recomendada = potenciaRecomendada(selecao);
    $('[data-consumo]').textContent = consumo ? `${consumo} W` : '—';
    const barraConsumo = $('[data-consumo-barra]');
    const proporcao = consumo ? Math.min(1, consumo / (fonte ? fonte.potencia : 1000)) : 0;
    barraConsumo.style.width = `${proporcao * 100}%`;
    barraConsumo.parentElement.classList.toggle('is-alto', proporcao > 0.7);
    $('[data-consumo-legenda]').textContent = !consumo
      ? 'Escolha o processador e a placa de vídeo.'
      : fonte
        ? `Fonte de ${fonte.potencia} W: sobra ${Math.round((1 - consumo / fonte.potencia) * 100)}% de potência.`
        : `Recomendamos uma fonte de pelo menos ${recomendada} W.`;

    // Situação da montagem
    const verificacao = $('[data-verificacao]');
    const faltando = categorias.filter((categoria) => !selecao[categoria]).map((categoria) => dados.categorias[categoria]);
    verificacao.className = `verificacao ${completa ? 'verificacao--ok' : 'verificacao--pendente'}`;
    verificacao.innerHTML = completa
      ? `${iconeCheck}<p><strong>Tudo compatível!</strong> Sua montagem está pronta para ir ao carrinho.</p>`
      : `${iconeInfo}<p>Falta escolher: ${faltando.join(', ')}.</p>`;

    // Totais
    $('[data-total]').textContent = brl(soma);
    $('[data-total-pix]').textContent = brl(precoPix(soma));
    $('[data-parcelas]').textContent = soma ? `ou ${dados.parcelas}x de ${brl(soma / dados.parcelas)} sem juros` : '';
    $('[data-adicionar-montagem]').disabled = !completa;

    if (barraMovel) {
      barraMovel.querySelector('[data-barra-progresso]').textContent = `${progresso} peças`;
      barraMovel.querySelector('[data-barra-total]').textContent = brl(precoPix(soma));
    }
  }

  const iconeCheck =
    '<svg class="icone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>';
  const iconeInfo =
    '<svg class="icone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>';

  /** Mantém a montagem no endereço da página, para poder compartilhar o link. */
  function atualizarEndereco(selecao) {
    const parametros = new URLSearchParams();
    categorias.forEach((categoria) => selecao[categoria] && parametros.set(categoria, selecao[categoria]));
    const consulta = parametros.toString();
    history.replaceState(null, '', consulta ? `?${consulta}` : location.pathname);
  }

  function atualizar({ avisarRemocoes = false } = {}) {
    const { selecao, removidas } = aplicarRegras();
    renderizar(selecao);
    atualizarEndereco(selecao);
    if (avisarRemocoes && removidas.length) {
      avisar(`Tiramos ${removidas.join(' e ')} da montagem: não combinava com a nova escolha.`, { tipo: 'info' });
    }
  }

  function aplicarPecas(pecas) {
    $$('input[type="radio"]', form).forEach((input) => (input.checked = false));
    categorias.forEach((categoria) => {
      const input = pecas[categoria] && form.querySelector(`input[name="${categoria}"][value="${CSS.escape(pecas[categoria])}"]`);
      if (input) input.checked = true;
    });
    atualizar();
  }

  form.addEventListener('submit', (evento) => evento.preventDefault());
  form.addEventListener('change', () => atualizar({ avisarRemocoes: true }));

  $$('[data-perfil]').forEach((botao) => {
    botao.addEventListener('click', () => {
      aplicarPecas(JSON.parse(botao.dataset.perfil));
      $$('[data-perfil]').forEach((b) => b.setAttribute('aria-pressed', String(b === botao)));
      avisar(`Perfil “${botao.textContent.trim()}” aplicado. Ajuste o que quiser.`, { tipo: 'info' });
    });
  });

  $('[data-limpar]')?.addEventListener('click', () => {
    aplicarPecas({});
    $$('[data-perfil]').forEach((b) => b.setAttribute('aria-pressed', 'false'));
    document.getElementById(`etapa-${categorias[0]}`).scrollIntoView({ block: 'start' });
  });

  $('[data-adicionar-montagem]').addEventListener('click', () => {
    Carrinho.adicionarMontagem(lerSelecao());
    pulsarBotaoCarrinho();
    abrirCarrinho();
  });

  $('[data-copiar-link]')?.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(location.href);
      avisar('Link copiado! Mande para quem quiser ver sua montagem.');
    } catch {
      avisar('Não foi possível copiar. Copie o endereço da barra do navegador.', { tipo: 'info' });
    }
  });

  atualizar();
})();
