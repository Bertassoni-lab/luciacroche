/* ============================================================
   Blog — Diário do ateliê
   Lê dados/blog.js, que é gerado pelo painel quando um texto
   é publicado. O site público continua sendo só arquivo estático.
   ============================================================ */
(function () {
  "use strict";

  var BLOG = window.BLOG || { posts: [] };
  var esc = window.LMC.esc;

  function publicados() {
    return (BLOG.posts || [])
      .filter(function (p) { return p.publicado !== false; })
      .sort(function (a, b) { return (b.data || "").localeCompare(a.data || ""); });
  }

  function dataBonita(iso) {
    if (!iso) return "";
    var partes = String(iso).slice(0, 10).split("-");
    if (partes.length !== 3) return iso;
    var meses = ["janeiro", "fevereiro", "março", "abril", "maio", "junho",
                 "julho", "agosto", "setembro", "outubro", "novembro", "dezembro"];
    return Number(partes[2]) + " de " + meses[Number(partes[1]) - 1] + " de " + partes[0];
  }

  function tempoLeitura(texto) {
    var palavras = String(texto || "").split(/\s+/).length;
    return Math.max(1, Math.round(palavras / 200)) + " min de leitura";
  }

  function cartaoPost(p) {
    return '<article class="post-cartao">' +
      '<a href="post.html?slug=' + encodeURIComponent(p.slug) + '">' +
        (p.imagem
          ? '<img src="' + esc(p.imagem) + '" alt="' + esc(p.imagemAlt || p.titulo) + '" loading="lazy">'
          : '<div class="post-cartao__sem-foto"></div>') +
        '<div class="post-cartao__corpo">' +
          (p.tag ? '<span class="post-tag">' + esc(p.tag) + '</span>' : '') +
          '<h2>' + esc(p.titulo) + '</h2>' +
          '<p>' + esc(p.resumo || "") + '</p>' +
          '<p class="post-meta">' + esc(dataBonita(p.data)) + ' · ' + tempoLeitura(p.corpo) + '</p>' +
        '</div>' +
      '</a>' +
    '</article>';
  }

  /* ---------------- lista ---------------- */

  function paginaLista() {
    var alvo = document.querySelector("[data-blog-lista]");
    if (!alvo) return;

    var posts = publicados();
    if (!posts.length) {
      alvo.innerHTML = '<p class="vazio">O primeiro texto está sendo escrito. Volte em breve — ' +
        'ou <a href="' + window.LMC.linkZap("Oi! Vim pelo diário do ateliê.") + '">venha conversar no WhatsApp</a>.</p>';
      return;
    }

    var tags = [];
    posts.forEach(function (p) { if (p.tag && tags.indexOf(p.tag) < 0) tags.push(p.tag); });

    var caixaTags = document.querySelector("[data-blog-tags]");
    if (caixaTags && tags.length > 1) {
      caixaTags.innerHTML = '<button class="filtro" type="button" data-tag="todos" aria-pressed="true">Tudo</button>' +
        tags.map(function (t) {
          return '<button class="filtro" type="button" data-tag="' + esc(t) + '" aria-pressed="false">' + esc(t) + '</button>';
        }).join("");
      caixaTags.addEventListener("click", function (ev) {
        var b = ev.target.closest(".filtro");
        if (!b) return;
        [].forEach.call(caixaTags.querySelectorAll(".filtro"), function (o) {
          o.setAttribute("aria-pressed", String(o === b));
        });
        var t = b.dataset.tag;
        alvo.innerHTML = posts.filter(function (p) { return t === "todos" || p.tag === t; })
          .map(cartaoPost).join("");
      });
    }

    alvo.innerHTML = posts.map(cartaoPost).join("");
  }

  /* ---------------- post ---------------- */

  function paginaPost() {
    var alvo = document.querySelector("[data-post]");
    if (!alvo) return;

    var slug = new URLSearchParams(location.search).get("slug");
    var post = publicados().filter(function (p) { return p.slug === slug; })[0];

    if (!post) {
      alvo.innerHTML = '<div class="vazio"><h1>Texto não encontrado</h1>' +
        '<p><a class="botao botao--principal" href="blog.html">Ver o diário</a></p></div>';
      return;
    }

    document.title = post.titulo + " · Diário do ateliê · " + window.LOJA.nome;
    var meta = document.querySelector('meta[name="description"]');
    if (meta && post.resumo) meta.setAttribute("content", post.resumo);

    alvo.innerHTML =
      '<div class="prosa post-corpo">' +
        '<p class="post-volta"><a href="blog.html">← Diário do ateliê</a></p>' +
        (post.tag ? '<span class="post-tag">' + esc(post.tag) + '</span>' : '') +
        '<h1>' + esc(post.titulo) + '</h1>' +
        '<p class="post-meta">' + esc(dataBonita(post.data)) + ' · ' + tempoLeitura(post.corpo) +
          (post.autor ? ' · por ' + esc(post.autor) : '') + '</p>' +
        (post.imagem ? '<img class="post-capa" src="' + esc(post.imagem) + '" alt="' + esc(post.imagemAlt || post.titulo) + '">' : '') +
        formatar(post.corpo) +
        '<hr>' +
        '<p>Gostou? As peças do ateliê estão <a href="produtos.html">aqui</a>, e a gente conversa ' +
        '<a href="' + window.LMC.linkZap("Oi! Li o diário do ateliê e queria saber mais.") + '">por aqui</a>.</p>' +
      '</div>';

    var dados = {
      "@context": "https://schema.org",
      "@type": "BlogPosting",
      headline: post.titulo,
      description: post.resumo,
      datePublished: post.data,
      author: { "@type": "Person", name: post.autor || "Lúcia Maria" },
      publisher: { "@type": "Organization", name: window.LOJA.nome }
    };
    var tag = document.createElement("script");
    tag.type = "application/ld+json";
    tag.textContent = JSON.stringify(dados);
    document.head.appendChild(tag);
  }

  /**
   * Transforma o texto simples do painel em HTML.
   * Linha começando com ## vira subtítulo, com - vira lista,
   * com > vira citação. O resto vira parágrafo.
   * Tudo é escapado antes — nada de HTML solto vindo do painel.
   */
  function formatar(texto) {
    var linhas = String(texto || "").split(/\r?\n/);
    var html = [];
    var emLista = false;

    function fecharLista() {
      if (emLista) { html.push("</ul>"); emLista = false; }
    }

    linhas.forEach(function (linha) {
      var t = linha.trim();
      if (!t) { fecharLista(); return; }

      if (t.indexOf("## ") === 0) { fecharLista(); html.push("<h2>" + negrito(t.slice(3)) + "</h2>"); return; }
      if (t.indexOf("### ") === 0) { fecharLista(); html.push("<h3>" + negrito(t.slice(4)) + "</h3>"); return; }
      if (t.indexOf("> ") === 0) { fecharLista(); html.push("<blockquote>" + negrito(t.slice(2)) + "</blockquote>"); return; }
      if (t.indexOf("- ") === 0) {
        if (!emLista) { html.push("<ul>"); emLista = true; }
        html.push("<li>" + negrito(t.slice(2)) + "</li>");
        return;
      }
      fecharLista();
      html.push("<p>" + negrito(t) + "</p>");
    });

    fecharLista();
    return html.join("\n");
  }

  /** Aplica **negrito** e [texto](link) depois de escapar tudo. */
  function negrito(t) {
    return esc(t)
      .replace(/\*\*([^*]+)\*\*/g, "<strong>$1</strong>")
      .replace(/\[([^\]]+)\]\((https?:\/\/[^)\s]+|[a-z0-9._\-\/?=&amp;]+\.html[^)\s]*)\)/gi, '<a href="$2">$1</a>');
  }

  paginaLista();
  paginaPost();
})();
