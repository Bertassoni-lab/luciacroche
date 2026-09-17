/* ============================================================
   Lúcia Maria Crochê — motor da loja
   Sem framework, sem build. Só precisa de um navegador.
   ============================================================ */
(function () {
  "use strict";

  var LOJA = window.LOJA || {};
  var CATALOGO = window.CATALOGO || { produtos: [], categorias: [] };
  var ESTOQUE = window.ESTOQUE || { vendidas: [] };
  var CHAVE_SACOLA = "lmc:sacola:v1";

  /** Peça única que já foi embora com alguém. */
  function vendida(id) {
    return (ESTOQUE.vendidas || []).indexOf(id) >= 0;
  }

  /** O que ainda dá para comprar. */
  function disponiveis() {
    return CATALOGO.produtos.filter(function (p) { return !vendida(p.id); });
  }

  /* ---------------- utilidades ---------------- */

  function moeda(v) {
    return v.toLocaleString("pt-BR", { style: "currency", currency: "BRL", minimumFractionDigits: 2 });
  }

  function esc(s) {
    return String(s == null ? "" : s).replace(/[&<>"']/g, function (c) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
    });
  }

  function produto(id) {
    for (var i = 0; i < CATALOGO.produtos.length; i++) {
      if (CATALOGO.produtos[i].id === id) return CATALOGO.produtos[i];
    }
    return null;
  }

  function linkZap(texto) {
    return "https://wa.me/" + (LOJA.whatsapp || "") + "?text=" + encodeURIComponent(texto || "");
  }

  function precoPix(v) {
    return v * (1 - (LOJA.descontoPix || 0));
  }

  /* ---------------- sacola ---------------- */

  var sacola = {
    ler: function () {
      try {
        var bruto = localStorage.getItem(CHAVE_SACOLA);
        var lista = bruto ? JSON.parse(bruto) : [];
        return Array.isArray(lista)
          ? lista.filter(function (i) { return produto(i.id) && !vendida(i.id); })
          : [];
      } catch (e) {
        return [];
      }
    },
    salvar: function (lista) {
      try { localStorage.setItem(CHAVE_SACOLA, JSON.stringify(lista)); } catch (e) { /* modo anônimo */ }
      atualizarContador();
      document.dispatchEvent(new CustomEvent("sacola:mudou"));
    },
    adicionar: function (id, qtd) {
      var lista = sacola.ler(), achou = false;
      qtd = qtd || 1;
      for (var i = 0; i < lista.length; i++) {
        if (lista[i].id === id) { lista[i].qtd += qtd; achou = true; }
      }
      if (!achou) lista.push({ id: id, qtd: qtd });
      sacola.salvar(lista);
    },
    mudarQtd: function (id, delta) {
      var lista = sacola.ler();
      for (var i = lista.length - 1; i >= 0; i--) {
        if (lista[i].id === id) {
          lista[i].qtd += delta;
          if (lista[i].qtd < 1) lista.splice(i, 1);
        }
      }
      sacola.salvar(lista);
    },
    remover: function (id) {
      sacola.salvar(sacola.ler().filter(function (i) { return i.id !== id; }));
    },
    limpar: function () { sacola.salvar([]); },
    itens: function () {
      return sacola.ler().map(function (i) {
        var p = produto(i.id);
        return { produto: p, qtd: i.qtd, subtotal: p.preco * i.qtd };
      });
    },
    quantidade: function () {
      return sacola.ler().reduce(function (s, i) { return s + i.qtd; }, 0);
    },
    subtotal: function () {
      return sacola.itens().reduce(function (s, i) { return s + i.subtotal; }, 0);
    }
  };

  function atualizarContador() {
    var n = sacola.quantidade();
    [].forEach.call(document.querySelectorAll("[data-sacola-n]"), function (el) {
      el.textContent = n;
      el.hidden = n === 0;
    });
  }

  /* ---------------- topo e rodapé ---------------- */

  var PAGINAS = [
    { href: "index.html", rotulo: "Início" },
    { href: "produtos.html", rotulo: "Produtos" },
    { href: "sobre.html", rotulo: "A Lúcia e a Aurora" },
    { href: "feiras.html", rotulo: "Feiras" },
    { href: "blog.html", rotulo: "Diário" },
    { href: "cuidados.html", rotulo: "Cuidados e trocas" }
  ];

  var NOVELO = '<svg class="marca__novelo" viewBox="0 0 40 40" aria-hidden="true">' +
    '<circle cx="20" cy="20" r="18" fill="#b8432c"/>' +
    '<path d="M6 14c8 3 20 3 28 0M4 21c9 4 23 4 32 0M7 28c8 3 18 3 26 0" stroke="#fdfaf5" stroke-width="1.6" fill="none" stroke-linecap="round"/>' +
    '<path d="M14 3c-4 10-4 24 0 34M26 3c4 10 4 24 0 34" stroke="#fdfaf5" stroke-width="1.6" fill="none" stroke-linecap="round"/>' +
    '</svg>';

  function montarTopo() {
    var alvo = document.querySelector("[data-topo]");
    if (!alvo) return;
    var atual = (location.pathname.split("/").pop() || "index.html");

    alvo.innerHTML =
      '<div class="env topo__barra">' +
        '<a class="marca" href="index.html">' + NOVELO +
          '<span><span class="marca__nome">' + esc(LOJA.nome) + '</span>' +
          '<span class="marca__sub">Boiçucanga · SP</span></span>' +
        '</a>' +
        '<button class="menu-toggle" type="button" aria-expanded="false" aria-controls="menu-principal">' +
          '<span class="sr">Abrir menu</span>' +
          '<svg width="20" height="14" viewBox="0 0 20 14" aria-hidden="true"><path d="M0 1h20M0 7h20M0 13h20" stroke="#2b2320" stroke-width="2"/></svg>' +
        '</button>' +
        '<nav class="menu" id="menu-principal" aria-label="Navegação principal">' +
          PAGINAS.map(function (p) {
            return '<a href="' + p.href + '"' + (p.href === atual ? ' aria-current="page"' : "") + '>' + esc(p.rotulo) + '</a>';
          }).join("") +
        '</nav>' +
        '<a class="sacola-btn" href="carrinho.html">' +
          '<svg width="17" height="17" viewBox="0 0 20 20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7">' +
          '<path d="M3 6h14l-1.2 11H4.2L3 6Z"/><path d="M7 6V4.5a3 3 0 0 1 6 0V6"/></svg>' +
          '<span class="sacola-btn__rotulo">Sacola</span>' +
          '<span class="sacola-btn__n" data-sacola-n hidden>0</span>' +
        '</a>' +
      '</div>';

    var botao = alvo.querySelector(".menu-toggle");
    var menu = alvo.querySelector("#menu-principal");
    function ajustar() {
      var estreito = window.matchMedia("(max-width: 860px)").matches;
      menu.hidden = estreito && botao.getAttribute("aria-expanded") !== "true";
    }
    botao.addEventListener("click", function () {
      var aberto = botao.getAttribute("aria-expanded") === "true";
      botao.setAttribute("aria-expanded", String(!aberto));
      ajustar();
    });
    window.addEventListener("resize", ajustar);
    ajustar();
    atualizarContador();
  }

  function montarRodape() {
    var alvo = document.querySelector("[data-rodape]");
    if (!alvo) return;
    var feira = (LOJA.feiras && LOJA.feiras[0]) || {};
    alvo.innerHTML =
      '<div class="env">' +
        '<div class="rodape__grade">' +
          '<div>' +
            '<h4>' + esc(LOJA.nome) + '</h4>' +
            '<p>' + esc(LOJA.assinatura) + '</p>' +
            '<p><a href="' + linkZap("Oi! Vi o site de vocês e queria saber mais sobre as peças.") + '">WhatsApp</a> · ' +
            '<a href="https://instagram.com/' + esc(LOJA.instagram) + '">@' + esc(LOJA.instagram) + '</a></p>' +
          '</div>' +
          '<div>' +
            '<h4>Loja</h4>' +
            '<ul>' + PAGINAS.map(function (p) {
              return '<li><a href="' + p.href + '">' + esc(p.rotulo) + '</a></li>';
            }).join("") + '</ul>' +
          '</div>' +
          '<div>' +
            '<h4>Onde nos achar</h4>' +
            '<p>' + esc(feira.nome || "") + '<br>' + esc(feira.local || "") + '<br>' + esc(feira.quando || "") + '</p>' +
          '</div>' +
        '</div>' +
        '<div class="rodape__fim">' +
          '<span>© ' + new Date().getFullYear() + ' ' + esc(LOJA.nome) + ' · Peças feitas à mão, uma de cada vez.</span>' +
          '<span>Pagamento por Pix ou link de cartão · Envio para todo o Brasil</span>' +
        '</div>' +
      '</div>';
  }

  /* ---------------- cartões e grades ---------------- */

  function figura(p, classe) {
    if (p.imagem) {
      return '<img src="' + esc(p.thumb || p.imagem) + '" alt="' + esc(p.alt || p.nome) + '"' +
        ' loading="lazy" decoding="async" sizes="(max-width: 560px) 46vw, 260px" width="600" height="600">';
    }
    return '<div class="' + (classe || "cartao__sem-foto") + '">' +
      '<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">' +
      '<circle cx="12" cy="12" r="9"/><path d="M4 9c5 2 11 2 16 0M3 14c6 2 12 2 18 0M9 3c-2 6-2 12 0 18M15 3c2 6 2 12 0 18"/></svg>' +
      '<span>Foto a caminho<br>Peça feita sob encomenda</span></div>';
  }

  function etiqueta(p) {
    if (vendida(p.id)) return '<span class="etiqueta etiqueta--vendida">Vendida</span>';
    if (p.disponibilidade === "sob-encomenda") return '<span class="etiqueta etiqueta--encomenda">Sob encomenda</span>';
    if (p.colecao === "Aurora") return '<span class="etiqueta etiqueta--aurora">Coleção Aurora</span>';
    return '<span class="etiqueta">Pronta-entrega</span>';
  }

  function cartao(p) {
    return '<article class="cartao">' +
      '<a class="cartao__link" href="produto.html?id=' + encodeURIComponent(p.id) + '">' +
        '<div class="cartao__moldura">' + figura(p) + etiqueta(p) + '</div>' +
        '<h3 class="cartao__nome">' + esc(p.nome) + '</h3>' +
      '</a>' +
      '<p class="cartao__resumo">' + esc(p.resumo) + '</p>' +
      '<div class="cartao__rodape">' +
        '<span class="preco">' + moeda(p.preco) + '<small>' + moeda(precoPix(p.preco)) + ' no Pix</small></span>' +
      '</div>' +
    '</article>';
  }

  function renderGrade(seletor, lista) {
    var alvo = document.querySelector(seletor);
    if (!alvo) return;
    if (!lista.length) {
      alvo.innerHTML = '<p class="vazio">Nenhuma peça nesta categoria por enquanto. ' +
        '<a href="' + linkZap("Oi! Queria encomendar uma peça que não está no site.") + '">Fale com a gente pelo WhatsApp</a> — quase tudo dá para fazer sob encomenda.</p>';
      return;
    }
    alvo.innerHTML = lista.map(cartao).join("");
  }

  /* ---------------- páginas ---------------- */

  function paginaInicio() {
    var destaques = disponiveis().filter(function (p) { return p.destaque; }).slice(0, 4);
    renderGrade("[data-destaques]", destaques);
  }

  function paginaProdutos() {
    var alvo = document.querySelector("[data-lista-produtos]");
    if (!alvo) return;
    var filtros = document.querySelector("[data-filtros]");
    var categoriaUrl = new URLSearchParams(location.search).get("categoria") || "todos";

    var opcoes = [{ id: "todos", nome: "Todas as peças" }].concat(CATALOGO.categorias);
    filtros.innerHTML = opcoes.map(function (c) {
      return '<button class="filtro" type="button" data-cat="' + esc(c.id) + '" aria-pressed="false">' + esc(c.nome) + '</button>';
    }).join("");

    function aplicar(cat) {
      renderGrade("[data-lista-produtos]", disponiveis().filter(function (p) {
        return cat === "todos" || p.categoria === cat;
      }));
      [].forEach.call(filtros.querySelectorAll(".filtro"), function (b) {
        b.setAttribute("aria-pressed", String(b.dataset.cat === cat));
      });
      var desc = document.querySelector("[data-cat-descricao]");
      if (desc) {
        var achada = CATALOGO.categorias.filter(function (c) { return c.id === cat; })[0];
        desc.textContent = achada ? achada.descricao : "Tudo que está pronto no ateliê agora, mais as peças que fazemos sob encomenda.";
      }
      var url = new URL(location.href);
      if (cat === "todos") url.searchParams.delete("categoria"); else url.searchParams.set("categoria", cat);
      history.replaceState(null, "", url);
    }

    filtros.addEventListener("click", function (ev) {
      var b = ev.target.closest(".filtro");
      if (b) aplicar(b.dataset.cat);
    });
    aplicar(categoriaUrl);
  }

  function paginaProduto() {
    var alvo = document.querySelector("[data-produto]");
    if (!alvo) return;
    var id = new URLSearchParams(location.search).get("id");
    var p = produto(id);

    if (!p) {
      alvo.innerHTML = '<div class="vazio"><h1>Não achamos esta peça</h1>' +
        '<p>Ela pode ter sido vendida — quase tudo aqui é peça única.</p>' +
        '<a class="botao botao--principal" href="produtos.html">Ver o que está pronto</a></div>';
      return;
    }

    document.title = p.nome + " · " + LOJA.nome;
    var meta = document.querySelector('meta[name="description"]');
    if (meta) meta.setAttribute("content", p.resumo);

    var encomenda = p.disponibilidade === "sob-encomenda";
    var jaFoi = vendida(p.id);
    alvo.innerHTML =
      '<div class="produto">' +
        '<div class="produto__foto">' +
          (p.imagem
            ? '<img src="' + esc(p.imagem) + '"' +
              (p.thumb ? ' srcset="' + esc(p.thumb) + ' 600w, ' + esc(p.imagem) + ' 1200w"' +
                         ' sizes="(max-width: 760px) 92vw, 46vw"' : '') +
              ' alt="' + esc(p.alt || p.nome) + '" width="1200" height="1200" fetchpriority="high">'
            : figura(p)) +
        '</div>' +
        '<div>' +
          '<p style="margin:0;color:var(--tinta-fraca);font-size:.88rem;letter-spacing:.06em;text-transform:uppercase">' +
            esc(nomeCategoria(p.categoria)) + (p.colecao === "Aurora" ? " · Coleção Aurora" : "") + '</p>' +
          '<h1>' + esc(p.nome) + '</h1>' +
          '<p class="produto__preco">' + moeda(p.preco) + '</p>' +
          '<p class="produto__pix">' + moeda(precoPix(p.preco)) + ' à vista no Pix' +
            (LOJA.parcelamento && LOJA.parcelamento.ativo && p.preco >= LOJA.parcelamento.valorMinimoParcela
              ? ' · ou ' + LOJA.parcelamento.maxParcelas + 'x de ' + moeda(p.preco / LOJA.parcelamento.maxParcelas) + ' no cartão'
              : '') +
          '</p>' +
          '<p>' + esc(p.descricao) + '</p>' +
          (jaFoi
            ? '<p class="aviso aviso--vendida"><strong>Esta peça já foi vendida.</strong> ' +
              'Cada uma é única, então não existe outra igual a esta — mas a Lúcia faz uma parecida ' +
              'na cor que você quiser, em cerca de 15 dias.</p>'
            : encomenda
            ? '<p class="aviso"><strong>Feita só depois do pedido.</strong> ' + esc(LOJA.avisoEncomenda) +
              ' Prazo de produção: cerca de ' + (p.prazoEncomendaDias || 15) + ' dias, e o envio começa a contar depois disso.</p>'
            : '<p class="aviso"><strong>Peça única, pronta para enviar.</strong> É a única igual a esta — quando sai, sai de vez.</p>') +
          '<div class="produto__acoes">' +
            (jaFoi
              ? '<a class="botao botao--principal" href="' + linkZap("Oi! Vi que a peça “" + p.nome + "” já foi vendida. Dá para fazer uma parecida?") + '">Encomendar uma parecida</a>' +
                '<a class="botao botao--contorno" href="produtos.html">Ver o que está pronto</a>'
              : '<button class="botao botao--principal" type="button" data-add="' + esc(p.id) + '">Colocar na sacola</button>' +
                '<a class="botao botao--zap" href="' + linkZap("Oi! Tenho interesse na peça “" + p.nome + "” (" + moeda(p.preco) + "). Ainda está disponível?") + '">Perguntar no WhatsApp</a>') +
          '</div>' +
          '<div class="ficha"><dl>' +
            linhaFicha("Medidas", p.medidas) +
            linhaFicha("Material", p.materiais) +
            linhaFicha("Cores", (p.cores || []).join(", ")) +
            linhaFicha("Feito em", p.tempoProducao) +
            linhaFicha("Peso", p.pesoGramas ? p.pesoGramas + " g" : "") +
            linhaFicha("Entrega", (LOJA.frete && LOJA.frete.prazoUtil) || "") +
          '</dl></div>' +
          '<p style="font-size:.9rem;color:var(--tinta-media);margin-top:18px">' +
            'Frete grátis para todo o Brasil acima de ' + moeda(LOJA.frete.gratisAcimaDe) + '. ' +
            'Ou <a href="feiras.html">retire com a gente na feira</a> e não pague frete nenhum.</p>' +
        '</div>' +
      '</div>';

    dadosEstruturados(p);

    var relacionados = disponiveis().filter(function (o) {
      return o.id !== p.id && o.categoria === p.categoria;
    });
    if (relacionados.length < 3) {
      relacionados = relacionados.concat(disponiveis().filter(function (o) {
        return o.id !== p.id && o.categoria !== p.categoria;
      }));
    }
    renderGrade("[data-relacionados]", relacionados.slice(0, 4));
  }

  function nomeCategoria(id) {
    var c = CATALOGO.categorias.filter(function (x) { return x.id === id; })[0];
    return c ? c.nome : "";
  }

  function linhaFicha(rotulo, valor) {
    if (!valor) return "";
    return "<dt>" + esc(rotulo) + "</dt><dd>" + esc(valor) + "</dd>";
  }

  function dadosEstruturados(p) {
    var dados = {
      "@context": "https://schema.org",
      "@type": "Product",
      name: p.nome,
      description: p.resumo,
      image: p.imagem ? [LOJA.site + "/" + p.imagem] : undefined,
      brand: { "@type": "Brand", name: LOJA.nome },
      offers: {
        "@type": "Offer",
        price: p.preco.toFixed(2),
        priceCurrency: "BRL",
        availability: vendida(p.id)
          ? "https://schema.org/SoldOut"
          : (p.disponibilidade === "sob-encomenda"
            ? "https://schema.org/PreOrder"
            : "https://schema.org/InStock"),
        itemCondition: "https://schema.org/NewCondition"
      }
    };
    var tag = document.createElement("script");
    tag.type = "application/ld+json";
    tag.textContent = JSON.stringify(dados);
    document.head.appendChild(tag);
  }

  /* ---------------- sacola e fechamento do pedido ---------------- */

  function paginaSacola() {
    var alvo = document.querySelector("[data-sacola]");
    if (!alvo) return;

    function desenhar() {
      var itens = sacola.itens();
      if (!itens.length) {
        alvo.innerHTML = '<div class="vazio"><h1>Sua sacola está vazia</h1>' +
          '<p>Todas as peças são feitas à mão, uma de cada vez. Vale dar uma olhada no que está pronto.</p>' +
          '<a class="botao botao--principal" href="produtos.html">Ver as peças</a></div>';
        return;
      }

      var subtotal = sacola.subtotal();
      var temEncomenda = itens.some(function (i) { return i.produto.disponibilidade === "sob-encomenda"; });
      var freteGratis = subtotal >= LOJA.frete.gratisAcimaDe;
      var falta = LOJA.frete.gratisAcimaDe - subtotal;

      alvo.innerHTML =
        '<h1>Sua sacola</h1>' +
        '<div class="sacola-grade">' +
          '<div>' +
            itens.map(function (i) {
              var p = i.produto;
              return '<div class="item">' +
                (p.imagem
                  ? '<img class="item__foto" src="' + esc(p.thumb || p.imagem) + '" alt="' + esc(p.nome) + '" width="92" height="92">'
                  : '<div class="item__foto"></div>') +
                '<div>' +
                  '<p class="item__nome"><a href="produto.html?id=' + encodeURIComponent(p.id) + '">' + esc(p.nome) + '</a></p>' +
                  '<p class="item__meta">' + esc(p.medidas) +
                    (p.disponibilidade === "sob-encomenda" ? ' · sob encomenda' : ' · pronta-entrega') + '</p>' +
                  '<div class="qtd">' +
                    '<button type="button" data-menos="' + esc(p.id) + '" aria-label="Tirar uma unidade">–</button>' +
                    '<span>' + i.qtd + '</span>' +
                    '<button type="button" data-mais="' + esc(p.id) + '" aria-label="Somar uma unidade">+</button>' +
                  '</div>' +
                '</div>' +
                '<div class="item__fim">' +
                  '<p class="preco" style="margin:0">' + moeda(i.subtotal) + '</p>' +
                  '<button class="remover" type="button" data-remover="' + esc(p.id) + '">tirar da sacola</button>' +
                '</div>' +
              '</div>';
            }).join("") +
            (temEncomenda ? '<p class="aviso" style="margin-top:20px"><strong>Tem peça sob encomenda na sacola.</strong> ' + esc(LOJA.avisoEncomenda) + '</p>' : "") +
          '</div>' +

          '<aside class="resumo-box">' +
            '<h2 style="margin-top:0;font-size:1.35rem">Resumo</h2>' +
            '<div class="resumo-linha"><span>Peças (' + sacola.quantidade() + ')</span><span>' + moeda(subtotal) + '</span></div>' +
            '<div class="resumo-linha"><span>Frete</span><span>' +
              (freteGratis ? "grátis" : "calculado no próximo passo") + '</span></div>' +
            (freteGratis
              ? ''
              : '<p style="font-size:.85rem;color:var(--tinta-media);margin:4px 0 0">Faltam ' + moeda(falta) + ' para o frete sair de graça.</p>') +
            '<div class="resumo-linha resumo-linha--total"><span>Total</span><span>' + moeda(subtotal) + '</span></div>' +
            '<p style="font-size:.88rem;color:var(--verde);font-weight:600;margin:6px 0 0">' +
              moeda(precoPix(subtotal)) + ' pagando no Pix</p>' +

            '<a class="botao botao--principal botao--largo" href="checkout.html" style="margin-top:20px">Fechar pedido</a>' +

            '<p style="text-align:center;margin:14px 0 0;font-size:.9rem">' +
              'ou <a href="' + linkZap(mensagemSacolaSimples()) + '">combinar tudo pelo WhatsApp</a></p>' +

            '<ul class="garantias">' +
              '<li>Pix com ' + Math.round((LOJA.descontoPix || 0) * 100) + '% de desconto, direto no site</li>' +
              '<li>Cartão em até ' + LOJA.parcelamento.maxParcelas + 'x</li>' +
              '<li>Retirada na feira sem frete</li>' +
              '<li>7 dias para desistir, como manda a lei</li>' +
            '</ul>' +
          '</aside>' +
        '</div>';
    }

    alvo.addEventListener("click", function (ev) {
      var alvoBotao = ev.target.closest("[data-mais], [data-menos], [data-remover]");
      if (!alvoBotao) return;
      if (alvoBotao.dataset.mais) sacola.mudarQtd(alvoBotao.dataset.mais, 1);
      if (alvoBotao.dataset.menos) sacola.mudarQtd(alvoBotao.dataset.menos, -1);
      if (alvoBotao.dataset.remover) sacola.remover(alvoBotao.dataset.remover);
    });

    document.addEventListener("sacola:mudou", desenhar);
    desenhar();
  }

  function mensagemSacolaSimples() {
    var itens = sacola.itens();
    if (!itens.length) return "Oi! Queria falar sobre um pedido.";
    var linhas = ["Olá, Lúcia! Queria fechar um pedido com estas peças:", ""];
    itens.forEach(function (i) {
      linhas.push("• " + i.qtd + "x " + i.produto.nome + " — " + moeda(i.subtotal));
    });
    linhas.push("");
    linhas.push("*Total das peças:* " + moeda(sacola.subtotal()));
    return linhas.join("\n");
  }

  /* ---------------- ligações gerais ---------------- */

  function ligarBotoesAdicionar() {
    document.addEventListener("click", function (ev) {
      var b = ev.target.closest("[data-add]");
      if (!b) return;
      sacola.adicionar(b.dataset.add, 1);
      var original = b.textContent;
      b.textContent = "Está na sacola ✓";
      b.disabled = true;
      setTimeout(function () { b.textContent = original; b.disabled = false; }, 1800);
    });
  }

  function ligarLinksZap() {
    [].forEach.call(document.querySelectorAll("[data-zap]"), function (a) {
      a.href = linkZap(a.dataset.zap || "Oi! Vim pelo site de vocês.");
    });
    [].forEach.call(document.querySelectorAll("[data-instagram]"), function (a) {
      a.href = "https://instagram.com/" + LOJA.instagram;
    });
  }

  function preencherTextos() {
    [].forEach.call(document.querySelectorAll("[data-texto]"), function (el) {
      var caminho = el.dataset.texto.split(".");
      var v = LOJA;
      caminho.forEach(function (c) { v = v && v[c]; });
      if (v != null) el.textContent = typeof v === "number" && /preco|gratis|estimativa/i.test(el.dataset.texto) ? moeda(v) : v;
    });
  }

  function iniciar() {
    montarTopo();
    montarRodape();
    ligarBotoesAdicionar();
    ligarLinksZap();
    preencherTextos();
    paginaInicio();
    paginaProdutos();
    paginaProduto();
    paginaSacola();
    if (typeof window.aoCarregarPagina === "function") window.aoCarregarPagina({ moeda: moeda, esc: esc, linkZap: linkZap, LOJA: LOJA, CATALOGO: CATALOGO });
  }

  window.LMC = { moeda: moeda, esc: esc, linkZap: linkZap, sacola: sacola,
                 produto: produto, precoPix: precoPix, vendida: vendida, disponiveis: disponiveis };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", iniciar);
  } else {
    iniciar();
  }
})();
