# Lúcia Maria Crochê

Loja on-line de bolsas, tapetes e peças de crochê em barbante, feitas à mão por
**Lúcia** e pela neta **Aurora**, de cinco anos, em Boiçucanga — São Sebastião, SP.
Elas vendem na feira de artesanato da Praça Pôr do Sol; este site é a continuação
dessa barraca fora da temporada.

---

## O que tem aqui

**A loja** — catálogo com filtros, página de produto, sacola, **checkout dentro do
próprio site** (Pix com QR Code gerado na hora, cartão pelo Mercado Pago e a opção de
combinar no WhatsApp) e o **diário do ateliê**, que é o blog.

**O painel** — em `/painel`, protegido por senha: pedidos, **gestão financeira**
(quanto entrou, quanto saiu, quanto sobrou e **quanto rendeu cada hora de crochê**),
**marketing** (lista de clientes, quem sumiu, campanhas, QR da barraca, links com
origem) e o editor do diário.

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
| [O painel](docs/PAINEL.md) | como usar financeiro, marketing e diário |
| [Pagamentos](docs/PAGAMENTOS.md) | como o Pix funciona e como ligar o cartão |
| [Como atualizar](docs/COMO-ATUALIZAR.md) | guia sem termos técnicos, para a família |
| [Publicar na Hostinger](docs/PUBLICAR.md) | domínio, upload, permissões, HTTPS |

## Ver funcionando

```bash
php -S localhost:8000
# loja:   http://localhost:8000
# painel: http://localhost:8000/painel  (senha inicial: croche2026)
```

Precisa de **PHP 8.1 ou superior** para o painel e o checkout. Só a loja (sem
checkout e sem painel) abre até clicando duas vezes em `index.html`.

## Antes de publicar

1. **Troque a senha do painel.** A inicial é `croche2026` e está escrita aqui — gere
   uma nova em `painel/senha.php` e cole em `api/config.php`.
2. Confira os dados em `assets/js/config.js` (WhatsApp, Instagram, chave Pix) e em
   `api/config.php` (Pix e, se for usar, Mercado Pago).
3. Siga o [guia de publicação na Hostinger](docs/PUBLICAR.md).

## Estrutura

```
LOJA (aberta a todo mundo)
  index.html          página inicial
  produtos.html       catálogo com filtro por categoria
  produto.html        página da peça (?id=nome-da-peca)
  carrinho.html       sacola
  checkout.html       fechamento do pedido, com Pix e cartão
  blog.html           diário do ateliê
  post.html           texto do diário (?slug=nome-do-texto)
  sobre.html          história da Lúcia e da Aurora
  feiras.html         onde encontrar a barraca
  cuidados.html       lavagem, prazos, pagamento, trocas, perguntas

DADOS
  dados/produtos.js   ← o catálogo. É o arquivo que mais muda.
  dados/blog.js       ← textos publicados (gerado pelo painel)

PROGRAMAS DA LOJA
  assets/js/config.js    contato, Pix, frete, feiras
  assets/js/app.js       motor da loja (topo, rodapé, catálogo, sacola)
  assets/js/checkout.js  os três passos do fechamento do pedido
  assets/js/pix.js       gera o código Pix (padrão do Banco Central)
  assets/js/blog.js      lista e página de texto do diário
  assets/js/vendor/      biblioteca de QR Code (MIT, de terceiros)
  assets/css/style.css   folha de estilo única da loja
  assets/img/            fotos das peças e da marca

SERVIDOR (PHP)
  api/config.php        senha do painel, Pix, chaves do Mercado Pago
  api/pedido.php        registra o pedido e confere os preços
  api/pagamento.php     gera o Pix e cobra no cartão
  api/webhook.php       recebe a confirmação do Mercado Pago
  api/frete.php         calcula o frete pelo CEP
  api/catalogo.php      lê o catálogo do lado do servidor
  api/lib.php           funções compartilhadas

PAINEL (com senha, em /painel)
  painel/index.php      visão geral e login
  painel/pedidos.php    acompanhar e mudar situação dos pedidos
  painel/financeiro.php entradas, saídas, lucro, R$ por hora, MEI, CSV
  painel/marketing.php  clientes, campanhas, QR da barraca, links
  painel/blog.php       escrever e publicar no diário
  painel/senha.php      trocar a senha

  dados-privados/       pedidos, clientes, despesas (fora do Git)

FERRAMENTAS E DOCUMENTOS
  ferramentas/precificacao.html   calculadora de preço
  ferramentas/cadastro.html       gerador de cadastro de peça
  docs/                           pesquisa, viabilidade, plano, guias
```

## Decisões técnicas, e por quê

**Sem framework e sem build.** Quem mantém este site é uma família, não um time de
desenvolvimento. Dá para abrir um arquivo no bloco de notas, trocar uma palavra e
salvar. Não existe `npm install` que possa quebrar daqui a dois anos.

**Pix gerado no próprio site, sem intermediário.** O código Pix é montado na hora
pelo padrão do Banco Central (EMV QRCPS-MPM), com o dígito verificador conferido
contra o exemplo oficial. Resultado: **0% de taxa** e o dinheiro cai na hora. O
cartão, quando ligado, passa pelo Mercado Pago — que cobra, mas resolve o
parcelamento. Ver [Pagamentos](docs/PAGAMENTOS.md).

**Os preços são conferidos no servidor.** O navegador só manda quais peças e quantas;
quem diz quanto custa é `api/pedido.php`, lendo o catálogo. Adulterar o preço pelo
navegador não muda o valor cobrado.

**Sem banco de dados.** Os dados ficam em arquivos JSON dentro de `dados-privados/`,
com trava de escrita para não perder venda simultânea. Para o volume do ateliê é de
sobra, o backup é copiar uma pasta e não tem senha de banco para vazar.

**Sacola no navegador.** O que a pessoa escolheu fica guardado no aparelho dela, e só
vira pedido quando ela fecha a compra.

**Catálogo em um arquivo só.** `dados/produtos.js` é a única fonte de verdade das
peças — lido tanto pela loja (no navegador) quanto pelo servidor (em PHP).

**Limitação conhecida:** as páginas são montadas por JavaScript, então o conteúdo não
está no HTML cru. O Google executa JavaScript e indexa normalmente, mas se um dia o
tráfego de busca virar prioridade, a migração para um gerador estático é o próximo
passo natural — os dados já estão separados da apresentação.

## Custo de operação

| Item | Custo |
|---|---:|
| Domínio `.com.br` | R$ 40/ano |
| Hospedagem Hostinger (PHP 8.1+) | a partir de ~R$ 10/mês |
| Comissão por venda no Pix | **R$ 0** |
| Cartão pelo Mercado Pago (opcional) | ~4,5% só quando usado |
| **Total** | **~R$ 160/ano** |

---

*As fotos das peças foram tiradas pela própria família e recortadas para o site.
A [análise de viabilidade](docs/VIABILIDADE.md) recomenda refazê-las — é a mudança
de maior impacto e menor custo do projeto inteiro.*
