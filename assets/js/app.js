/* ==========================================================================
   Proware — scripts usados em todas as páginas
   Carrinho, avisos, menu, carrossel, filtros e formulários.
   ========================================================================== */
(() => {
  'use strict';

  const $ = (seletor, raiz = document) => raiz.querySelector(seletor);
  const $$ = (seletor, raiz = document) => [...raiz.querySelectorAll(seletor)];

  const dados = JSON.parse($('#dados-catalogo')?.textContent || '{}');
  const formatoBRL = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
  const brl = (valor) => formatoBRL.format(valor);
  const arredondar = (valor) => Math.round(valor * 100) / 100;
  const precoPix = (valor) => arredondar(valor * (1 - dados.descontoPix));
  const reduzMovimento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const QTD_MAXIMA = 10;

  const svg = (desenho) =>
    `<svg class="icone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${desenho}</svg>`;
  const ICONES = {
    sucesso: svg('<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>'),
    info: svg('<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>'),
  };

  // O localStorage pode falhar (aba anônima, cookies bloqueados): isso nunca deve quebrar a página.
  const armazenamento = {
    ler(chave, padrao) {
      try {
        const valor = localStorage.getItem(chave);
        return valor ? JSON.parse(valor) : padrao;
      } catch {
        return padrao;
      }
    },
    gravar(chave, valor) {
      try {
        localStorage.setItem(chave, JSON.stringify(valor));
      } catch {
        /* sem armazenamento: o carrinho vale só nesta página */
      }
    },
  };

  /* ---------- Avisos flutuantes ---------- */

  function avisar(mensagem, { tipo = 'sucesso', acao } = {}) {
    const caixa = $('[data-avisos]');
    if (!caixa) return;

    const aviso = document.createElement('div');
    aviso.className = `aviso aviso--${tipo}`;
    aviso.innerHTML = ICONES[tipo] || ICONES.info;
    const texto = document.createElement('span');
    texto.textContent = mensagem;
    aviso.append(texto);

    const remover = () => {
      aviso.classList.add('is-saindo');
      setTimeout(() => aviso.remove(), 250);
    };

    if (acao) {
      const botao = document.createElement('button');
      botao.type = 'button';
      botao.textContent = acao.rotulo;
      botao.addEventListener('click', () => {
        acao.executar();
        remover();
      });
      aviso.append(botao);
    }

    caixa.append(aviso);
    setTimeout(remover, 5000);
  }

  /* ---------- Carrinho (salvo no navegador) ---------- */

  const Carrinho = {
    chave: 'proware:carrinho',
    itens: [],
    ouvintes: [],

    carregar() {
      const salvos = armazenamento.ler(this.chave, []);
      this.itens = Array.isArray(salvos) ? salvos.filter((item) => this.detalhes(item)) : [];
    },

    salvar() {
      armazenamento.gravar(this.chave, this.itens);
      this.ouvintes.forEach((ouvinte) => ouvinte());
    },

    aoMudar(ouvinte) {
      this.ouvintes.push(ouvinte);
    },

    adicionarProduto(id) {
      if (!dados.produtos?.[id]) return;
      this.adicionar({ chave: `p:${id}`, tipo: 'produto', id, qtd: 1 });
    },

    adicionarMontagem(pecas) {
      const chave = 'm:' + Object.keys(dados.categorias).map((categoria) => pecas[categoria]).join('|');
      this.adicionar({ chave, tipo: 'montagem', pecas: { ...pecas }, qtd: 1 });
    },

    adicionar(novo) {
      const existente = this.itens.find((item) => item.chave === novo.chave);
      if (existente) {
        existente.qtd = Math.min(QTD_MAXIMA, existente.qtd + novo.qtd);
      } else {
        this.itens.push(novo);
      }
      this.salvar();
    },

    alterarQuantidade(chave, diferenca) {
      const item = this.itens.find((i) => i.chave === chave);
      if (!item) return;
      item.qtd = Math.max(1, Math.min(QTD_MAXIMA, item.qtd + diferenca));
      this.salvar();
    },

    remover(chave) {
      this.itens = this.itens.filter((item) => item.chave !== chave);
      this.salvar();
    },

    esvaziar() {
      this.itens = [];
      this.salvar();
    },

    quantidade() {
      return this.itens.reduce((total, item) => total + item.qtd, 0);
    },

    subtotal() {
      return arredondar(this.itens.reduce((total, item) => total + this.detalhes(item).preco * item.qtd, 0));
    },

    /** Nome, preço e imagem atuais de um item, sempre tirados do catálogo. */
    detalhes(item) {
      if (item?.tipo === 'produto') {
        const produto = dados.produtos?.[item.id];
        return produto && {
          nome: produto.nome,
          descricao: produto.categoria,
          preco: produto.preco,
          imagem: produto.imagem,
          url: produto.url,
        };
      }
      if (item?.tipo === 'montagem') {
        const peca = (categoria) => dados.pecas?.[item.pecas?.[categoria]];
        const pecas = Object.keys(dados.categorias || {}).map(peca);
        if (!pecas.length || pecas.some((p) => !p)) return null;
        return {
          nome: 'PC personalizado Proware',
          descricao: `${peca('cpu').nome} · ${peca('gpu').nome}`,
          preco: pecas.reduce((total, p) => total + p.preco, 0),
          imagem: peca('gabinete').imagem,
          url: 'montar.php?' + new URLSearchParams(item.pecas),
        };
      }
      return null;
    },

    /** Versão enxuta enviada ao servidor no checkout (o servidor recalcula os preços). */
    paraEnvio() {
      return JSON.stringify(this.itens.map(({ tipo, id, pecas, qtd }) => ({ tipo, id, pecas, qtd })));
    },
  };

  Carrinho.carregar();

  /* ---------- Gaveta do carrinho ---------- */

  const gaveta = $('[data-gaveta]');
  const listaGaveta = $('[data-carrinho-lista]');
  const modeloItem = $('#modelo-item-carrinho');

  function renderizarGaveta() {
    if (!listaGaveta || !modeloItem) return;
    const vazio = Carrinho.itens.length === 0;
    $('[data-carrinho-vazio]').hidden = !vazio;
    $('[data-carrinho-rodape]').hidden = vazio;
    listaGaveta.hidden = vazio;

    listaGaveta.replaceChildren(
      ...Carrinho.itens.map((item) => {
        const info = Carrinho.detalhes(item);
        const li = modeloItem.content.firstElementChild.cloneNode(true);
        li.dataset.chave = item.chave;
        $('img', li).src = info.imagem;
        const nome = $('[data-campo="nome"]', li);
        nome.textContent = info.nome;
        nome.href = info.url;
        $('[data-campo="descricao"]', li).textContent = info.descricao;
        $('[data-campo="qtd"]', li).textContent = item.qtd;
        $('[data-campo="preco"]', li).textContent = brl(info.preco * item.qtd);
        $('[data-acao="menos"]', li).disabled = item.qtd <= 1;
        $('[data-acao="mais"]', li).disabled = item.qtd >= QTD_MAXIMA;
        return li;
      })
    );

    const subtotal = Carrinho.subtotal();
    $('[data-carrinho-subtotal]').textContent = brl(subtotal);
    $('[data-carrinho-pix]').textContent = brl(precoPix(subtotal));
  }

  function abrirCarrinho() {
    if (!gaveta) return;
    renderizarGaveta();
    if (!gaveta.open) gaveta.showModal();
  }

  function atualizarContador() {
    const quantidade = Carrinho.quantidade();
    $$('[data-contador-carrinho]').forEach((contador) => {
      contador.textContent = quantidade > 99 ? '99+' : String(quantidade);
      contador.hidden = quantidade === 0;
    });
    $('.botao-carrinho')?.setAttribute(
      'aria-label',
      quantidade ? `Abrir carrinho, ${quantidade} ${quantidade === 1 ? 'item' : 'itens'}` : 'Abrir carrinho'
    );
  }

  function pulsarBotaoCarrinho() {
    const botao = $('.botao-carrinho');
    if (!botao || reduzMovimento) return;
    botao.classList.remove('is-pulsando');
    void botao.offsetWidth; // reinicia a animação
    botao.classList.add('is-pulsando');
  }

  Carrinho.aoMudar(() => {
    atualizarContador();
    if (gaveta?.open) renderizarGaveta();
  });

  // Mantém o carrinho igual em várias abas abertas.
  window.addEventListener('storage', (evento) => {
    if (evento.key !== Carrinho.chave) return;
    Carrinho.carregar();
    Carrinho.ouvintes.forEach((ouvinte) => ouvinte());
  });

  listaGaveta?.addEventListener('click', (evento) => {
    const botao = evento.target.closest('[data-acao]');
    if (!botao) return;
    const { chave } = botao.closest('[data-chave]').dataset;
    const acao = botao.dataset.acao;

    if (acao === 'mais') Carrinho.alterarQuantidade(chave, 1);
    if (acao === 'menos') Carrinho.alterarQuantidade(chave, -1);
    if (acao === 'remover') Carrinho.remover(chave);

    // A lista é redesenhada: devolve o foco para um lugar que faça sentido.
    const mesmoBotao = $(`[data-chave="${CSS.escape(chave)}"] [data-acao="${acao}"]`, listaGaveta);
    (mesmoBotao && !mesmoBotao.disabled ? mesmoBotao : $('[data-fechar-gaveta]', gaveta))?.focus();
  });

  gaveta?.addEventListener('click', (evento) => {
    if (evento.target === gaveta) gaveta.close(); // clique no fundo escurecido
  });

  document.addEventListener('click', (evento) => {
    if (evento.target.closest('[data-abrir-carrinho]')) {
      evento.preventDefault();
      abrirCarrinho();
      return;
    }
    if (evento.target.closest('[data-fechar-gaveta]')) {
      gaveta?.close();
      return;
    }

    const adicionar = evento.target.closest('[data-adicionar]');
    if (adicionar) {
      Carrinho.adicionarProduto(adicionar.dataset.adicionar);
      pulsarBotaoCarrinho();
      if (adicionar.hasAttribute('data-abrir-depois')) {
        abrirCarrinho();
      } else {
        avisar('Produto adicionado ao carrinho.', { acao: { rotulo: 'Ver carrinho', executar: abrirCarrinho } });
      }
    }
  });

  if ($('[data-limpar-carrinho]')) Carrinho.esvaziar();
  atualizarContador();

  /* ---------- Menu no celular ---------- */

  const botaoMenu = $('[data-alternar-menu]');
  const menu = $('[data-menu]');

  function alternarMenu(abrir) {
    menu.classList.toggle('is-aberto', abrir);
    botaoMenu.setAttribute('aria-expanded', String(abrir));
    botaoMenu.setAttribute('aria-label', abrir ? 'Fechar menu' : 'Abrir menu');
  }

  botaoMenu?.addEventListener('click', () => alternarMenu(!menu.classList.contains('is-aberto')));

  document.addEventListener('keydown', (evento) => {
    if (evento.key === 'Escape' && menu?.classList.contains('is-aberto')) {
      alternarMenu(false);
      botaoMenu.focus();
    }
  });

  document.addEventListener('click', (evento) => {
    if (menu?.classList.contains('is-aberto') && !evento.target.closest('[data-menu], [data-alternar-menu]')) {
      alternarMenu(false);
    }
  });

  /* ---------- Carrossel da página inicial ---------- */

  function iniciarCarrossel(raiz) {
    const slides = $$('[data-slide]', raiz);
    const areaPontos = $('[data-pontos]', raiz);
    const botaoPausa = $('[data-pausar]', raiz);
    if (slides.length < 2 || !areaPontos) return;

    const INTERVALO = 6500;
    let atual = 0;
    let temporizador = null;
    let pausadoPeloUsuario = reduzMovimento;

    const pontos = slides.map((_, indice) => {
      const ponto = document.createElement('button');
      ponto.type = 'button';
      ponto.className = 'destaque__ponto';
      ponto.setAttribute('aria-label', `Mostrar destaque ${indice + 1} de ${slides.length}`);
      ponto.addEventListener('click', () => irPara(indice));
      areaPontos.append(ponto);
      return ponto;
    });

    function mostrar(indice) {
      atual = (indice + slides.length) % slides.length;
      slides.forEach((slide, i) => {
        const ativo = i === atual;
        slide.classList.toggle('is-ativo', ativo);
        slide.inert = !ativo;
        slide.setAttribute('aria-hidden', String(!ativo));
      });
      pontos.forEach((ponto, i) => ponto.setAttribute('aria-current', String(i === atual)));
    }

    function parar() {
      clearInterval(temporizador);
      temporizador = null;
    }

    function tocar() {
      parar();
      if (!pausadoPeloUsuario && !document.hidden) {
        temporizador = setInterval(() => mostrar(atual + 1), INTERVALO);
      }
    }

    function irPara(indice) {
      mostrar(indice);
      tocar();
    }

    function atualizarBotaoPausa() {
      if (!botaoPausa) return;
      botaoPausa.setAttribute('aria-label', pausadoPeloUsuario ? 'Retomar destaques' : 'Pausar destaques');
      botaoPausa.setAttribute('aria-pressed', String(pausadoPeloUsuario));
    }

    $('[data-anterior]', raiz)?.addEventListener('click', () => irPara(atual - 1));
    $('[data-proximo]', raiz)?.addEventListener('click', () => irPara(atual + 1));
    botaoPausa?.addEventListener('click', () => {
      pausadoPeloUsuario = !pausadoPeloUsuario;
      atualizarBotaoPausa();
      tocar();
    });

    raiz.addEventListener('mouseenter', parar);
    raiz.addEventListener('mouseleave', tocar);
    raiz.addEventListener('focusin', parar);
    raiz.addEventListener('focusout', (evento) => {
      if (!raiz.contains(evento.relatedTarget)) tocar();
    });
    document.addEventListener('visibilitychange', tocar);

    let inicioToque = null;
    raiz.addEventListener('touchstart', (evento) => (inicioToque = evento.touches[0].clientX), { passive: true });
    raiz.addEventListener('touchend', (evento) => {
      if (inicioToque === null) return;
      const distancia = evento.changedTouches[0].clientX - inicioToque;
      if (Math.abs(distancia) > 50) irPara(atual + (distancia < 0 ? 1 : -1));
      inicioToque = null;
    });

    mostrar(0);
    atualizarBotaoPausa();
    tocar();
  }

  $$('[data-carrossel]').forEach(iniciarCarrossel);

  /* ---------- Filtros e ordenação da vitrine ---------- */

  const barraFiltros = $('[data-filtros]');
  if (barraFiltros) {
    const lista = $('[data-lista-produtos]');
    const cards = $$('.card-produto', lista);
    const contagem = $('[data-contagem]');
    let filtro = 'todos';
    let ordem = 'padrao';

    const aplicar = () => {
      let visiveis = 0;
      cards.forEach((card) => {
        card.hidden = filtro !== 'todos' && card.dataset.uso !== filtro;
        if (!card.hidden) visiveis++;
      });
      const ordenados = [...cards].sort((a, b) => {
        if (ordem === 'menor') return a.dataset.preco - b.dataset.preco;
        if (ordem === 'maior') return b.dataset.preco - a.dataset.preco;
        return 0;
      });
      lista.append(...ordenados);
      contagem.textContent = `${visiveis} ${visiveis === 1 ? 'computador' : 'computadores'}`;
    };

    barraFiltros.addEventListener('click', (evento) => {
      const botao = evento.target.closest('[data-filtro]');
      if (!botao) return;
      filtro = botao.dataset.filtro;
      $$('[data-filtro]', barraFiltros).forEach((b) => b.setAttribute('aria-pressed', String(b === botao)));
      aplicar();
    });

    $('[data-ordenar]', barraFiltros)?.addEventListener('change', (evento) => {
      ordem = evento.target.value;
      aplicar();
    });
  }

  /* ---------- Máscaras de CPF, celular e CEP ---------- */

  const mascaras = {
    cpf: (d) =>
      d
        .slice(0, 11)
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d{1,2})$/, '$1-$2'),
    telefone: (d) => {
      d = d.slice(0, 11);
      if (d.length > 10) return d.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
      if (d.length > 6) return d.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
      if (d.length > 2) return d.replace(/(\d{2})(\d{0,5})/, '($1) $2');
      return d.length ? `(${d}` : '';
    },
    cep: (d) => d.slice(0, 8).replace(/(\d{5})(\d)/, '$1-$2'),
  };

  $$('[data-mascara]').forEach((campo) => {
    const mascara = mascaras[campo.dataset.mascara];
    const aplicar = () => (campo.value = mascara(campo.value.replace(/\D/g, '')));
    campo.addEventListener('input', aplicar);
    if (campo.value) aplicar();
  });

  /* ---------- Endereço pelo CEP (ViaCEP) ---------- */

  const campoCep = $('[data-buscar-cep]');
  if (campoCep) {
    const status = $('[data-status-cep]');
    let ultimoBuscado = campoCep.value.replace(/\D/g, '');

    campoCep.addEventListener('input', async () => {
      const cep = campoCep.value.replace(/\D/g, '');
      if (cep.length !== 8 || cep === ultimoBuscado) return;
      ultimoBuscado = cep;
      status.classList.remove('is-erro');
      status.textContent = 'Buscando endereço…';

      try {
        const resposta = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const endereco = await resposta.json();
        if (!resposta.ok || endereco.erro) throw new Error('CEP não encontrado');

        const preencher = (id, valor) => {
          const campo = document.getElementById(id);
          if (campo && valor) {
            campo.value = valor;
            campo.dispatchEvent(new Event('input', { bubbles: true }));
          }
        };
        preencher('rua', endereco.logradouro);
        preencher('bairro', endereco.bairro);
        preencher('cidade', endereco.localidade);
        preencher('uf', endereco.uf);
        status.textContent = 'Endereço encontrado! Confira e informe o número.';
        document.getElementById('numero')?.focus();
      } catch {
        status.classList.add('is-erro');
        status.textContent = 'Não encontramos esse CEP. Preencha o endereço manualmente.';
      }
    });
  }

  /* ---------- Mostrar/ocultar senha ---------- */

  document.addEventListener('click', (evento) => {
    const botao = evento.target.closest('[data-mostrar-senha]');
    if (!botao) return;
    const campo = $('input', botao.parentElement);
    const mostrar = campo.type === 'password';
    campo.type = mostrar ? 'text' : 'password';
    botao.setAttribute('aria-pressed', String(mostrar));
    botao.setAttribute('aria-label', mostrar ? 'Ocultar senha' : 'Mostrar senha');
  });

  /* ---------- Validação dos formulários ---------- */
  // O PHP sempre valida de novo; aqui é só para o cliente ver o erro na hora.

  function cpfValido(cpf) {
    if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
    for (let posicao = 9; posicao < 11; posicao++) {
      let soma = 0;
      for (let i = 0; i < posicao; i++) soma += Number(cpf[i]) * (posicao + 1 - i);
      if (Number(cpf[posicao]) !== ((10 * soma) % 11) % 10) return false;
    }
    return true;
  }

  function mensagemDeErro(campo) {
    const valor = campo.type === 'password' ? campo.value : campo.value.trim();
    const digitos = valor.replace(/\D/g, '');

    if (campo.required && !valor) return 'Preencha este campo.';
    if (!valor) return '';
    if (campo.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valor)) return 'Informe um e-mail válido.';
    if (campo.minLength > 0 && valor.length < campo.minLength) return `Use pelo menos ${campo.minLength} caracteres.`;
    if (campo.dataset.mascara === 'cpf' && !cpfValido(digitos)) return 'CPF inválido. Confira os números.';
    if (campo.dataset.mascara === 'telefone' && digitos.length < 10) return 'Informe o celular com DDD.';
    if (campo.dataset.mascara === 'cep' && digitos.length !== 8) return 'Informe um CEP com 8 números.';
    if (campo.dataset.igualA && valor !== campo.form.elements[campo.dataset.igualA]?.value) {
      return 'As senhas não são iguais.';
    }
    return '';
  }

  function mostrarErro(campo, mensagem) {
    const caixa = campo.closest('.campo');
    if (!caixa) return;
    const idErro = `erro-${campo.name}`;
    let erro = document.getElementById(idErro);
    const descricoes = (campo.getAttribute('aria-describedby') || '').split(' ').filter((id) => id && id !== idErro);

    caixa.classList.toggle('campo--erro', Boolean(mensagem));
    if (!mensagem) {
      campo.removeAttribute('aria-invalid');
      erro?.remove();
    } else {
      if (!erro) {
        erro = document.createElement('p');
        erro.className = 'campo__erro';
        erro.id = idErro;
        caixa.append(erro);
      }
      erro.textContent = mensagem;
      campo.setAttribute('aria-invalid', 'true');
      descricoes.push(idErro);
    }
    if (descricoes.length) campo.setAttribute('aria-describedby', descricoes.join(' '));
    else campo.removeAttribute('aria-describedby');
  }

  $$('form[data-validar]').forEach((form) => {
    const campos = $$('input:not([type="hidden"]):not([type="radio"]), select, textarea', form);

    campos.forEach((campo) => {
      campo.addEventListener('blur', () => {
        if (campo.value) mostrarErro(campo, mensagemDeErro(campo));
      });
      campo.addEventListener('input', () => {
        if (campo.getAttribute('aria-invalid') === 'true') mostrarErro(campo, mensagemDeErro(campo));
      });
    });

    form.addEventListener('submit', (evento) => {
      let primeiroInvalido = null;
      campos.forEach((campo) => {
        const mensagem = mensagemDeErro(campo);
        mostrarErro(campo, mensagem);
        if (mensagem && !primeiroInvalido) primeiroInvalido = campo;
      });

      if (primeiroInvalido) {
        evento.preventDefault();
        primeiroInvalido.focus();
        return;
      }

      // Evita pedidos ou cadastros duplicados por clique duplo.
      const botoes = [...$$('[type="submit"]', form), ...(form.id ? $$(`[type="submit"][form="${form.id}"]`) : [])];
      setTimeout(() => botoes.forEach((botao) => (botao.disabled = true)), 0);
    });
  });

  // Ao voltar para a página pelo histórico, reativa os botões desativados no envio.
  window.addEventListener('pageshow', (evento) => {
    if (evento.persisted) $$('[type="submit"]').forEach((botao) => (botao.disabled = false));
  });

  window.Proware = { dados, Carrinho, brl, precoPix, avisar, abrirCarrinho, pulsarBotaoCarrinho, armazenamento };
})();
