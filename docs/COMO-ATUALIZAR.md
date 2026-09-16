# Como mexer no site (guia sem termos técnicos)

Este site foi feito para ser mexido por quem não programa. São arquivos de texto: você
abre, troca a palavra, salva. Não tem painel, não tem senha, não tem mensalidade.

> **Antes de qualquer mudança:** faça uma cópia do arquivo que vai mexer. Se der
> errado, é só voltar a cópia.

---

## Trocar telefone, Pix, Instagram e dias de feira

Arquivo: **`assets/js/config.js`**

Abra no bloco de notas (ou em qualquer editor de texto). Tudo o que precisa ser
trocado está marcado com a palavra `TROCAR`.

```js
whatsapp: "5512900000000",     ← 55 + DDD + número, só números
instagram: "luciamariacroche",
pix: {
  chave: "000.000.000-00",
  favorecido: "Lúcia Maria (nome completo do titular)",
},
```

**Cuidado:** não apague as aspas `"` nem as vírgulas `,` do fim da linha. Troque só o
que está **dentro** das aspas.

Na mesma página dá para mudar:

- `gratisAcimaDe` — a partir de quanto o frete sai de graça
- `descontoPix` — `0.05` quer dizer 5% de desconto
- `feiras` — dias, horário e lugar da barraca

---

## Colocar uma peça nova à venda

### Jeito fácil (recomendado)

1. Abra o arquivo **`ferramentas/cadastro.html`** (clique duas vezes, abre no navegador).
2. Preencha os campos da peça.
3. Clique em **Copiar**.
4. Abra `dados/produtos.js`, ache a linha `"produtos": [` e **cole logo embaixo dela**.
5. Salve.

### A foto

- Corte a foto em **quadrado** antes de salvar.
- Salve em `assets/img/produtos/` com nome sem acento e sem espaço:
  `bolsa-tote-azul.jpg`.
- Se puder, salve também uma versão menor com `-600` no fim do nome
  (`bolsa-tote-azul-600.jpg`). O site fica mais rápido no celular. Se não tiver, ele
  usa a foto grande mesmo.

---

## Tirar uma peça que foi vendida

Abra `dados/produtos.js`, ache o bloco da peça e **apague do `{` até o `},`**,
incluindo as chaves. Salve.

Se for voltar a fazer aquela peça, em vez de apagar troque a linha:

```js
"disponibilidade": "pronta-entrega",
```
por
```js
"disponibilidade": "sob-encomenda",
```

A etiqueta na foto muda sozinha e o site passa a avisar o prazo de produção.

---

## Mudar o preço

Em `dados/produtos.js`, ache a peça e troque o número:

```js
"preco": 189,        ← preço no site
"precoFeira": 160,   ← preço na barraca (não aparece no site, é só referência)
```

Sem `R$`, sem vírgula: só o número. `219` e não `R$ 219,00`.

---

## Mudar um texto de página

| Quero mudar | Arquivo |
|---|---|
| A frase grande da página inicial | `index.html` |
| A história da Lúcia e da Aurora | `sobre.html` |
| Dias e horários da feira | `assets/js/config.js` |
| Como lavar, prazos, trocas, perguntas | `cuidados.html` |
| Menu do topo e rodapé | `assets/js/app.js` (lista `PAGINAS`) |

Nos arquivos `.html`, mexa **só no texto entre os sinais `>` e `<`**. Por exemplo,
em `<h1>O crochê da Lúcia</h1>` você troca `O crochê da Lúcia` e deixa o resto.

---

## Ver como ficou antes de publicar

Clique duas vezes em `index.html`. Abre no navegador, funciona tudo (inclusive a
sacola). **Nada do que você vê aí está no ar** — só vai para o site depois de
publicar, e o passo a passo está em [PUBLICAR.md](PUBLICAR.md).

---

## Se algo quebrar

Sintoma mais comum: **a página abre em branco ou some a lista de peças.** Quase
sempre é vírgula ou aspas que faltou em `dados/produtos.js`.

O que fazer:

1. Volte a cópia que você fez antes de mexer.
2. Ou confira: toda linha termina com vírgula, menos a última de cada bloco; toda
   aspa que abre tem uma que fecha; todo `{` tem um `}`.
3. Para achar o erro: abra a página no navegador, aperte **F12**, clique em
   **Console**. A mensagem em vermelho diz o número da linha.

---

## As duas ferramentas de vocês

Não aparecem no menu e não vão para o Google. São de uso interno:

- **`ferramentas/precificacao.html`** — calcula o preço de uma peça e mostra quanto
  sobra por hora de trabalho. Use antes de fechar encomenda grande.
- **`ferramentas/cadastro.html`** — monta o texto de uma peça nova para colar no
  catálogo.
