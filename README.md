# Lúcia Maria Crochê

Loja on-line de bolsas, tapetes e peças de crochê em barbante, feitas à mão por
**Lúcia** e pela neta **Aurora**, de cinco anos, em Boiçucanga — São Sebastião, SP.
Elas vendem na feira de artesanato da Praça Pôr do Sol; este site é a continuação
dessa barraca fora da temporada.

---

## O que tem aqui

**O site** — loja completa, sem framework e sem build: catálogo com filtros, página
de produto, sacola que guarda o que você escolheu, e fechamento de pedido por
WhatsApp com Pix ou cartão.

**A análise** — pesquisa de mercado com fontes, parecer de viabilidade com as contas
abertas, guia de precificação e plano de 90 dias.

**As ferramentas** — uma calculadora de preço e um cadastrador de peças, ambos para
uso interno da família.

## Comece por aqui

| Documento | Para quê |
|---|---|
| **[Viabilidade](docs/VIABILIDADE.md)** | **o parecer: dá dinheiro? quanto? o que muda?** |
| [Pesquisa de mercado](docs/PESQUISA-MERCADO.md) | números do setor, concorrência, custos, com fontes |
| [Precificação](docs/PRECIFICACAO.md) | como calcular o preço de uma peça |
| [Plano de 90 dias](docs/PLANO-90-DIAS.md) | o que fazer, em ordem, até a alta temporada |
| [Como atualizar](docs/COMO-ATUALIZAR.md) | guia sem termos técnicos, para a família |
| [Publicar](docs/PUBLICAR.md) | domínio, hospedagem, Google |

## Ver funcionando

Clique duas vezes em `index.html`. Funciona tudo, inclusive a sacola — é um site
estático puro, não precisa de servidor.

Se preferir servir por HTTP:

```bash
python3 -m http.server 8000
# abra http://localhost:8000
```

## Antes de publicar

Abra `assets/js/config.js` e troque o que está marcado com `TROCAR`:

```js
whatsapp: "5512900000000",   // 55 + DDD + número
instagram: "luciamariacroche",
pix: { chave: "000.000.000-00", favorecido: "..." }
```

Sem isso, os botões de WhatsApp levam para um número que não existe.

## Estrutura

```
index.html          página inicial
produtos.html       catálogo com filtro por categoria
produto.html        página da peça (?id=nome-da-peca)
carrinho.html       sacola e fechamento do pedido
sobre.html          história da Lúcia e da Aurora
feiras.html         onde encontrar a barraca
cuidados.html       lavagem, prazos, pagamento, trocas, perguntas

dados/produtos.js   ← o catálogo. É o arquivo que mais muda.
assets/js/config.js ← contato, Pix, frete, feiras
assets/js/app.js    motor da loja (topo, rodapé, catálogo, sacola)
assets/css/style.css folha de estilo única
assets/img/         fotos das peças e da marca

ferramentas/precificacao.html   calculadora de preço (uso interno)
ferramentas/cadastro.html       gerador de cadastro de peça (uso interno)
docs/                           pesquisa, viabilidade, plano
```

## Decisões técnicas, e por quê

**Sem framework e sem build.** Quem mantém este site é uma família, não um time de
desenvolvimento. Dá para abrir um arquivo no bloco de notas, trocar uma palavra e
salvar. Não existe `npm install` que possa quebrar daqui a dois anos.

**Sem pagamento automático no site.** O pedido é fechado no WhatsApp, com Pix ou link
de cartão. Isso custa **0% de comissão** — contra 18% a 20% de marketplace e taxas de
plataforma — e mantém a conversa com o cliente, que é o ativo mais valioso de quem
vende artesanato. A troca: alguém precisa responder o WhatsApp. Para o volume real
(algumas peças por semana) vale muito a pena; acima de ~30 vendas/mês, vale
reconsiderar uma plataforma com checkout próprio.

**Sacola no navegador.** O que a pessoa escolheu fica guardado no aparelho dela.
Nada é enviado para lugar nenhum antes de ela mesma mandar a mensagem.

**Catálogo em um arquivo só.** `dados/produtos.js` é a única fonte de verdade das
peças. Um arquivo, uma lista, sem banco de dados.

**Limitação conhecida:** as páginas são montadas por JavaScript, então o conteúdo não
está no HTML cru. O Google executa JavaScript e indexa normalmente, mas se um dia o
tráfego de busca virar prioridade, a migração para um gerador estático é o próximo
passo natural — os dados já estão separados da apresentação.

## Custo de operação

| Item | Custo |
|---|---:|
| Domínio `.com.br` | R$ 40/ano |
| Hospedagem (Netlify, GitHub Pages ou Cloudflare) | R$ 0 |
| Comissão por venda | R$ 0 |
| **Total** | **R$ 40/ano** |

---

*As fotos das peças foram tiradas pela própria família e recortadas para o site.
A [análise de viabilidade](docs/VIABILIDADE.md) recomenda refazê-las — é a mudança
de maior impacto e menor custo do projeto inteiro.*
