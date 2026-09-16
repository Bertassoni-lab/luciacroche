/* ============================================================
   Checkout dentro do site — Lúcia Maria Crochê
   Passo 1: dados e entrega · Passo 2: pagamento · Passo 3: pronto
   ============================================================ */
(function () {
  "use strict";

  var LOJA = window.LOJA || {};
  var alvo = document.querySelector("[data-checkout]");
  if (!alvo) return;

  var estado = {
    passo: 1,
    servidor: null,      // preenchido por api/config-publica.php
    frete: null,         // { valor, regiao, motivo }
    pedido: null,        // resposta de api/pedido.php
    dados: {},
    forma: "pix"
  };

  var moeda = window.LMC.moeda;
  var esc = window.LMC.esc;
  var sacola = window.LMC.sacola;

  /* ---------------- servidor ---------------- */

  function api(caminho, corpo) {
    return fetch(caminho, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(corpo || {})
    }).then(function (r) {
      return r.json().then(function (d) {
        if (!r.ok) throw new Error(d.erro || "Não consegui falar com o servidor.");
        return d;
      });
    });
  }

  function detectarServidor() {
    return fetch("api/config-publica.php")
      .then(function (r) { return r.ok ? r.json() : null; })
      .catch(function () { return null; })
      .then(function (d) { estado.servidor = d; return d; });
  }

  /* ---------------- cálculos ---------------- */

  function itens() { return sacola.itens(); }
  function subtotal() { return sacola.subtotal(); }
  function peso() {
    return itens().reduce(function (s, i) { return s + (i.produto.pesoGramas || 400) * i.qtd; }, 0);
  }
  function freteValor() {
    if (estado.dados.entrega === "retirada") return 0;
    return estado.frete && estado.frete.valor != null ? estado.frete.valor : null;
  }
  function desconto() {
    return estado.forma === "pix" ? subtotal() * (LOJA.descontoPix || 0) : 0;
  }
  function total() {
    return subtotal() + (freteValor() || 0) - desconto();
  }

  function calcularFrete(cep) {
    if (estado.dados.entrega === "retirada") {
      estado.frete = { valor: 0, regiao: "Retirada na feira", motivo: "Sem frete" };
      return Promise.resolve(estado.frete);
    }
    return api("api/frete.php", { cep: cep, peso: peso(), subtotal: subtotal() })
      .then(function (d) {
        estado.frete = d.ok ? d : null;
        return estado.frete;
      })
      .catch(function () {
        // Sem servidor: mostra a estimativa da configuração, deixando claro que é estimativa.
        var digito = (cep || "").replace(/\D/g, "").charAt(0);
        var base = ["0", "1"].indexOf(digito) >= 0
          ? LOJA.frete.estimativaSudeste
          : LOJA.frete.estimativaOutrasRegioes;
        estado.frete = {
          valor: subtotal() >= LOJA.frete.gratisAcimaDe ? 0 : base,
          regiao: "estimativa",
          motivo: "Valor conferido com você no WhatsApp"
        };
        return estado.frete;
      });
  }

  /* ---------------- desenho ---------------- */

  var passoDesenhado = null;

  function desenhar() {
    // Ao trocar de passo, volta ao topo — senão a pessoa cai no meio da tela nova.
    if (passoDesenhado !== null && passoDesenhado !== estado.passo) {
      window.scrollTo({ top: 0, behavior: "smooth" });
    }
    passoDesenhado = estado.passo;

    if (!itens().length && estado.passo < 3) {
      alvo.innerHTML = '<div class="vazio"><h1>Sua sacola está vazia</h1>' +
        '<p><a class="botao botao--principal" href="produtos.html">Ver as peças</a></p></div>';
      return;
    }
    if (estado.passo === 1) return passoDados();
    if (estado.passo === 2) return passoPagamento();
    return passoPronto();
  }

  function trilha(n) {
    var nomes = ["Seus dados", "Pagamento", "Pronto"];
    return '<ol class="trilha">' + nomes.map(function (t, i) {
      var estadoPasso = (i + 1) < n ? "feito" : ((i + 1) === n ? "atual" : "");
      return '<li class="' + estadoPasso + '"><span>' + (i + 1) + '</span>' + t + '</li>';
    }).join("") + '</ol>';
  }

  function resumoHTML(mostrarTotal) {
    var f = freteValor();
    return '<aside class="resumo-box">' +
      '<h2 style="margin-top:0;font-size:1.25rem">Seu pedido</h2>' +
      itens().map(function (i) {
        return '<div class="resumo-item">' +
          (i.produto.thumb ? '<img src="' + esc(i.produto.thumb) + '" alt="" width="48" height="48">' : '<div class="resumo-item__vazio"></div>') +
          '<span>' + i.qtd + '× ' + esc(i.produto.nome) + '</span>' +
          '<strong>' + moeda(i.subtotal) + '</strong></div>';
      }).join("") +
      '<div class="resumo-linha"><span>Peças</span><span>' + moeda(subtotal()) + '</span></div>' +
      '<div class="resumo-linha"><span>Frete' + (estado.frete && estado.frete.regiao ? ' <small>(' + esc(estado.frete.regiao) + ')</small>' : '') + '</span><span>' +
        (f === null ? "informe o CEP" : (f === 0 ? "grátis" : moeda(f))) + '</span></div>' +
      (desconto() > 0 ? '<div class="resumo-linha" style="color:var(--verde)"><span>Desconto do Pix</span><span>− ' + moeda(desconto()) + '</span></div>' : '') +
      (mostrarTotal ? '<div class="resumo-linha resumo-linha--total"><span>Total</span><span>' + moeda(total()) + '</span></div>' : '') +
      '<p style="font-size:.82rem;color:var(--tinta-media);margin:14px 0 0">' +
        'Peça feita à mão por Lúcia e Aurora, em Boiçucanga.</p>' +
      '</aside>';
  }

  /* ---------------- passo 1: dados ---------------- */

  function passoDados() {
    var d = estado.dados;
    alvo.innerHTML = trilha(1) +
      '<div class="checkout-grade">' +
        '<form class="checkout-form" novalidate>' +
          '<h1>Seus dados</h1>' +

          '<label for="c-nome">Nome completo *</label>' +
          '<input id="c-nome" name="nome" autocomplete="name" required value="' + esc(d.nome || "") + '">' +

          '<div class="campo-duplo">' +
            '<div><label for="c-whats">WhatsApp *</label>' +
            '<input id="c-whats" name="whatsapp" inputmode="tel" autocomplete="tel" required placeholder="(12) 99999-9999" value="' + esc(d.whatsapp || "") + '"></div>' +
            '<div><label for="c-email">E-mail (opcional)</label>' +
            '<input id="c-email" name="email" type="email" autocomplete="email" value="' + esc(d.email || "") + '"></div>' +
          '</div>' +

          '<h2>Como você quer receber</h2>' +
          '<div class="opcoes">' +
            '<label class="opcao"><input type="radio" name="entrega" value="correios"' + (d.entrega !== "retirada" ? " checked" : "") + '>' +
              '<span><strong>Receber em casa</strong><small>Correios, ' + esc(LOJA.frete.prazoUtil) + '</small></span></label>' +
            '<label class="opcao"><input type="radio" name="entrega" value="retirada"' + (d.entrega === "retirada" ? " checked" : "") + '>' +
              '<span><strong>Retirar na feira de Boiçucanga</strong><small>Sem frete. Guardamos no seu nome por 7 dias.</small></span></label>' +
          '</div>' +

          '<div data-endereco' + (d.entrega === "retirada" ? " hidden" : "") + '>' +
            '<div class="campo-duplo">' +
              '<div><label for="c-cep">CEP *</label>' +
              '<input id="c-cep" name="cep" inputmode="numeric" autocomplete="postal-code" placeholder="00000-000" value="' + esc(d.cep || "") + '"></div>' +
              '<div><label for="c-frete-info">Frete</label>' +
              '<p id="c-frete-info" class="frete-info" data-frete>Digite o CEP para calcular.</p></div>' +
            '</div>' +
            '<label for="c-endereco">Endereço completo *</label>' +
            '<textarea id="c-endereco" name="endereco" placeholder="Rua, número, complemento, bairro, cidade e estado">' + esc(d.endereco || "") + '</textarea>' +
          '</div>' +

          '<label for="c-obs">Quer pedir alguma coisa? (opcional)</label>' +
          '<textarea id="c-obs" name="observacao" placeholder="Cor preferida, é presente, prazo…">' + esc(d.observacao || "") + '</textarea>' +

          '<label class="opcao opcao--simples"><input type="checkbox" name="aceitaAviso"' + (d.aceitaAviso ? " checked" : "") + '>' +
            '<span>Pode me avisar no WhatsApp quando tiver peça nova</span></label>' +

          '<p class="erro" data-erro hidden></p>' +
          '<button class="botao botao--principal botao--largo" type="submit" style="margin-top:20px">Ir para o pagamento</button>' +
          '<p style="font-size:.82rem;color:var(--tinta-media);text-align:center;margin-top:12px">' +
            'Nada é cobrado antes de você escolher como pagar.</p>' +
        '</form>' +
        resumoHTML(false) +
      '</div>';

    var form = alvo.querySelector("form");

    form.addEventListener("change", function (ev) {
      if (ev.target.name === "entrega") {
        estado.dados.entrega = ev.target.value;
        alvo.querySelector("[data-endereco]").hidden = ev.target.value === "retirada";
        if (ev.target.value === "retirada") {
          estado.frete = { valor: 0, regiao: "Retirada na feira", motivo: "Sem frete" };
          atualizarResumo();
        }
      }
    });

    var campoCep = form.querySelector("#c-cep");
    if (campoCep) {
      campoCep.addEventListener("blur", function () {
        var cep = campoCep.value.replace(/\D/g, "");
        if (cep.length !== 8) return;
        var aviso = alvo.querySelector("[data-frete]");
        aviso.textContent = "Calculando…";
        calcularFrete(cep).then(function (f) {
          aviso.textContent = !f ? "Confira o CEP."
            : (f.valor === 0 ? "Frete grátis!" : moeda(f.valor) + " · " + f.motivo);
          atualizarResumo();
        });
      });
    }

    form.addEventListener("submit", function (ev) {
      ev.preventDefault();
      var dados = new FormData(form);
      estado.dados = {
        nome: (dados.get("nome") || "").trim(),
        whatsapp: (dados.get("whatsapp") || "").trim(),
        email: (dados.get("email") || "").trim(),
        entrega: dados.get("entrega"),
        cep: (dados.get("cep") || "").trim(),
        endereco: (dados.get("endereco") || "").trim(),
        observacao: (dados.get("observacao") || "").trim(),
        aceitaAviso: !!dados.get("aceitaAviso")
      };

      var erro = validar(estado.dados);
      var caixaErro = form.querySelector("[data-erro]");
      if (erro) {
        caixaErro.textContent = erro;
        caixaErro.hidden = false;
        caixaErro.scrollIntoView({ block: "center", behavior: "smooth" });
        return;
      }
      caixaErro.hidden = true;
      estado.passo = 2;
      desenhar();
    });
  }

  function validar(d) {
    if (d.nome.length < 3) return "Escreva seu nome completo.";
    if (d.whatsapp.replace(/\D/g, "").length < 10) return "Escreva um WhatsApp com DDD.";
    if (d.entrega !== "retirada") {
      if (d.cep.replace(/\D/g, "").length !== 8) return "O CEP precisa ter 8 números.";
      if (d.endereco.length < 12) return "Escreva o endereço completo, com número e cidade.";
    }
    return null;
  }

  function atualizarResumo() {
    var antigo = alvo.querySelector(".resumo-box");
    if (!antigo) return;
    var novo = document.createElement("div");
    novo.innerHTML = resumoHTML(estado.passo >= 2);
    antigo.replaceWith(novo.firstChild);
  }

  /* ---------------- passo 2: pagamento ---------------- */

  function passoPagamento() {
    var cartaoAtivo = estado.servidor && estado.servidor.cartaoAtivo;

    alvo.innerHTML = trilha(2) +
      '<div class="checkout-grade">' +
        '<div class="checkout-form">' +
          '<p><button class="voltar" type="button" data-voltar>← voltar e corrigir os dados</button></p>' +
          '<h1>Como você quer pagar</h1>' +

          '<div class="abas" role="tablist">' +
            '<button class="aba" role="tab" data-forma="pix" aria-selected="true">Pix' +
              '<small>' + Math.round((LOJA.descontoPix || 0) * 100) + '% de desconto</small></button>' +
            '<button class="aba" role="tab" data-forma="cartao" aria-selected="false">Cartão' +
              '<small>' + (cartaoAtivo ? "até " + LOJA.parcelamento.maxParcelas + "x" : "falar com a gente") + '</small></button>' +
            '<button class="aba" role="tab" data-forma="whatsapp" aria-selected="false">WhatsApp' +
              '<small>combinar tudo</small></button>' +
          '</div>' +

          '<div data-painel-pagamento></div>' +
        '</div>' +
        resumoHTML(true) +
      '</div>';

    alvo.querySelector("[data-voltar]").addEventListener("click", function () {
      estado.passo = 1;
      desenhar();
    });

    alvo.querySelector(".abas").addEventListener("click", function (ev) {
      var b = ev.target.closest(".aba");
      if (!b) return;
      estado.forma = b.dataset.forma;
      [].forEach.call(alvo.querySelectorAll(".aba"), function (a) {
        a.setAttribute("aria-selected", String(a === b));
      });
      atualizarResumo();
      abrirForma();
    });

    abrirForma();
  }

  function abrirForma() {
    var painel = alvo.querySelector("[data-painel-pagamento]");
    if (estado.forma === "pix") return formaPix(painel);
    if (estado.forma === "cartao") return formaCartao(painel);
    return formaWhatsApp(painel);
  }

  /** Cria o pedido no servidor (ou monta um pedido local, se não houver servidor). */
  function garantirPedido() {
    if (estado.pedido) return Promise.resolve(estado.pedido);

    var corpo = {
      cliente: { nome: estado.dados.nome, whatsapp: estado.dados.whatsapp, email: estado.dados.email },
      entrega: { tipo: estado.dados.entrega === "retirada" ? "retirada" : "correios",
                 cep: estado.dados.cep, endereco: estado.dados.endereco },
      itens: sacola.ler(),
      pagamento: estado.forma,
      observacao: estado.dados.observacao,
      aceitaAviso: estado.dados.aceitaAviso,
      origem: new URLSearchParams(location.search).get("utm_source") || "site"
    };

    return api("api/pedido.php", corpo)
      .then(function (d) { estado.pedido = d; return d; })
      .catch(function () {
        // Sem servidor (pré-visualização local): monta o Pix aqui mesmo.
        var id = "LMC" + Date.now().toString(36).toUpperCase().slice(-7);
        estado.pedido = {
          pedido: id,
          total: total(),
          semServidor: true,
          pixCodigo: window.PIX ? window.PIX.gerar({
            chave: LOJA.pix.chave, favorecido: LOJA.pix.favorecido,
            cidade: LOJA.pix.cidade, valor: total(), txid: id,
            descricao: "Lucia Maria Croche"
          }) : null
        };
        return estado.pedido;
      });
  }

  function formaPix(painel) {
    painel.innerHTML = '<p class="carregando">Preparando o Pix…</p>';

    garantirPedido().then(function (pedido) {
      if (pedido.semServidor) return mostrarPix(painel, pedido.pixCodigo, false, pedido.pedido);
      return api("api/pagamento.php", { acao: "pix", pedido: pedido.pedido })
        .then(function (r) { mostrarPix(painel, r.copiaECola, !!r.baixaAutomatica, pedido.pedido); })
        .catch(function () { mostrarPix(painel, pedido.pixCodigo, false, pedido.pedido); });
    });
  }

  function mostrarPix(painel, codigo, baixaAutomatica, idPedido) {
    if (!codigo) {
      painel.innerHTML = '<p class="aviso">Não consegui gerar o Pix agora. ' +
        '<a href="' + window.LMC.linkZap("Oi! Quero pagar meu pedido no Pix.") + '">Fale com a gente no WhatsApp</a>.</p>';
      return;
    }

    painel.innerHTML =
      '<div class="pix-caixa">' +
        '<div class="pix-qr" data-qr></div>' +
        '<div class="pix-lado">' +
          '<p class="pix-valor">' + moeda(total()) + '</p>' +
          '<p>Abra o aplicativo do seu banco, escolha <strong>Pix</strong> → <strong>Ler QR Code</strong>, ' +
            'ou use o código abaixo.</p>' +
          '<textarea class="pix-codigo" readonly rows="3" aria-label="Código Pix copia e cola">' + esc(codigo) + '</textarea>' +
          '<button class="botao botao--principal botao--largo" type="button" data-copiar>Copiar código Pix</button>' +
          '<p class="pix-favorecido">Quem recebe: <strong>' + esc(LOJA.pix.favorecido) + '</strong><br>' +
            '<small>' + esc(LOJA.pix.observacao || "") + '</small></p>' +
        '</div>' +
      '</div>' +
      '<p class="aviso">Pedido <strong>' + esc(idPedido) + '</strong>. ' +
        (baixaAutomatica
          ? 'O pagamento cai automaticamente — assim que confirmar, a gente começa a embalar.'
          : 'Depois de pagar, toque no botão abaixo para a gente conferir e separar sua peça.') +
      '</p>' +
      '<button class="botao botao--zap botao--largo" type="button" data-paguei>Já paguei — avisar a Lúcia</button>';

    if (window.PIX) window.PIX.desenharQR(painel.querySelector("[data-qr]"), codigo, 4);

    painel.querySelector("[data-copiar]").addEventListener("click", function (ev) {
      var area = painel.querySelector(".pix-codigo");
      area.select();
      var pronto = function () {
        ev.target.textContent = "Código copiado ✓";
        setTimeout(function () { ev.target.textContent = "Copiar código Pix"; }, 2500);
      };
      if (navigator.clipboard) navigator.clipboard.writeText(codigo).then(pronto, pronto);
      else { document.execCommand("copy"); pronto(); }
    });

    painel.querySelector("[data-paguei]").addEventListener("click", function () {
      if (!estado.pedido.semServidor) {
        api("api/pagamento.php", { acao: "avisei", pedido: idPedido }).catch(function () {});
      }
      concluir(idPedido, "Pix", codigo);
    });
  }

  function formaCartao(painel) {
    if (!estado.servidor || !estado.servidor.cartaoAtivo) {
      painel.innerHTML =
        '<p class="aviso"><strong>O cartão ainda não está ligado nesta loja.</strong> ' +
        'Enquanto isso, o Pix tem ' + Math.round((LOJA.descontoPix || 0) * 100) + '% de desconto, ' +
        'ou a gente manda um link de cartão pelo WhatsApp em até ' + LOJA.parcelamento.maxParcelas + 'x.</p>' +
        '<button class="botao botao--zap botao--largo" type="button" data-link-cartao>Pedir link de cartão no WhatsApp</button>';
      painel.querySelector("[data-link-cartao]").addEventListener("click", function () {
        garantirPedido().then(function (p) { concluir(p.pedido, "Cartão (link pelo WhatsApp)", null); });
      });
      return;
    }

    painel.innerHTML = '<p class="carregando">Abrindo o pagamento seguro…</p><div id="brick-cartao"></div>';

    garantirPedido().then(function (pedido) {
      carregarSdkMercadoPago().then(function () {
        var mp = new MercadoPago(estado.servidor.mpPublicKey, { locale: "pt-BR" });
        var bricks = mp.bricks();
        painel.querySelector(".carregando").remove();

        bricks.create("payment", "brick-cartao", {
          initialization: { amount: Number(total().toFixed(2)) },
          customization: {
            paymentMethods: {
              creditCard: "all",
              debitCard: "all",
              maxInstallments: LOJA.parcelamento.maxParcelas
            }
          },
          callbacks: {
            onReady: function () {},
            onSubmit: function (dados) {
              return api("api/pagamento.php", {
                acao: "cartao", pedido: pedido.pedido, cartao: dados.formData
              }).then(function (r) {
                if (r.aprovado) {
                  concluir(pedido.pedido, "Cartão", null);
                } else {
                  alert(r.mensagem || "Pagamento em análise. A gente te avisa no WhatsApp.");
                  concluir(pedido.pedido, "Cartão (em análise)", null);
                }
              });
            },
            onError: function (erro) {
              console.error(erro);
              alert("Não deu certo com este cartão. Tente o Pix, que ainda tem desconto.");
            }
          }
        });
      }).catch(function () {
        painel.innerHTML = '<p class="aviso">Não consegui abrir o pagamento por cartão. ' +
          'Use o Pix ou fale com a gente no WhatsApp.</p>';
      });
    });
  }

  function carregarSdkMercadoPago() {
    if (window.MercadoPago) return Promise.resolve();
    return new Promise(function (resolve, reject) {
      var s = document.createElement("script");
      s.src = "https://sdk.mercadopago.com/js/v2";
      s.onload = resolve;
      s.onerror = reject;
      document.head.appendChild(s);
    });
  }

  function formaWhatsApp(painel) {
    painel.innerHTML =
      '<p>Prefere resolver conversando? Sem problema — a gente combina o pagamento, ' +
      'o frete e o prazo por mensagem, do jeito que você preferir.</p>' +
      '<button class="botao botao--zap botao--largo" type="button" data-zap-fechar>Fechar pelo WhatsApp</button>';

    painel.querySelector("[data-zap-fechar]").addEventListener("click", function () {
      garantirPedido().then(function (p) { concluir(p.pedido, "A combinar no WhatsApp", null); });
    });
  }

  /* ---------------- passo 3: pronto ---------------- */

  function concluir(idPedido, forma, codigoPix) {
    estado.concluido = { id: idPedido, forma: forma, pix: codigoPix, total: total(), itens: itens() };
    estado.passo = 3;
    desenhar();
    sacola.limpar();
  }

  function mensagemWhatsApp(c) {
    var linhas = ["Olá! Acabei de fazer o pedido *" + c.id + "* no site.", "", "*Peças*"];
    c.itens.forEach(function (i) {
      linhas.push("• " + i.qtd + "x " + i.produto.nome + " — " + moeda(i.subtotal));
    });
    linhas.push("");
    linhas.push("*Total:* " + moeda(c.total));
    linhas.push("*Pagamento:* " + c.forma);
    linhas.push("*Entrega:* " + (estado.dados.entrega === "retirada" ? "retirada na feira" : "Correios"));
    linhas.push("*Nome:* " + estado.dados.nome);
    if (estado.dados.entrega !== "retirada") {
      linhas.push("*Endereço:* " + estado.dados.endereco + " — CEP " + estado.dados.cep);
    }
    if (estado.dados.observacao) linhas.push("*Observação:* " + estado.dados.observacao);
    return linhas.join("\n");
  }

  function passoPronto() {
    var c = estado.concluido;
    alvo.innerHTML = trilha(3) +
      '<div class="pronto">' +
        '<div class="pronto__selo">✓</div>' +
        '<h1>Pedido feito!</h1>' +
        '<p class="pronto__numero">Número do pedido: <strong>' + esc(c.id) + '</strong></p>' +
        '<p>Anote esse número. Agora é só mandar uma mensagem para a gente confirmar — ' +
          'a Lúcia separa sua peça e te avisa quando sair para entrega.</p>' +
        '<p><a class="botao botao--zap" href="' + window.LMC.linkZap(mensagemWhatsApp(c)) + '" target="_blank" rel="noopener">' +
          'Confirmar no WhatsApp</a></p>' +
        '<div class="pronto__caixa">' +
          '<h2>O que acontece agora</h2>' +
          '<ol>' +
            '<li>A gente confere o pagamento (costuma ser no mesmo dia).</li>' +
            '<li>Peça pronta sai em até 2 dias úteis; peça sob encomenda leva de 12 a 15 dias.</li>' +
            '<li>Você recebe o código de rastreio no WhatsApp.</li>' +
          '</ol>' +
        '</div>' +
        '<p style="margin-top:26px"><a href="produtos.html">Ver outras peças do ateliê →</a></p>' +
      '</div>';
  }

  /* ---------------- início ---------------- */

  detectarServidor().then(desenhar);
})();
