# Como calcular o preço de uma peça

Guia prático. Tem uma calculadora pronta em
[`ferramentas/precificacao.html`](../ferramentas/precificacao.html) — abra no
navegador, digite os números e ela faz as contas. Este documento explica o porquê.

---

## A fórmula

```
custo da peça = barbante + aviamentos + (horas × valor da sua hora) + custo fixo
preço de venda direta = custo × (1 + margem)
```

Cinco parcelas, e a que mais se esquece é a terceira.

### 1. Barbante

```
barbante = (gramas ÷ 1000) × preço do quilo × 1,08
```

O 1,08 é a perda: ponta de novelo, carreira desmanchada, sobra curta demais. Oito
por cento é uma estimativa conservadora e realista.

Preço do quilo em setembro de 2026: **R$ 70 a R$ 85** para barbante nº 6. Comprando
cor fechada em quantidade no armarinho, cai para perto de R$ 60.

### 2. Aviamentos

Alça de couro (R$ 30 a R$ 40 o par), argola de madeira, ímã, zíper, forro de tecido,
etiqueta. Some tudo o que não é barbante.

### 3. As horas — a parte que quase todo mundo esquece

Esta é a razão de tanta artesã trabalhar muito e não ver dinheiro: ela cobra o
material e o lucro, e **doa o próprio tempo**.

Referências para o valor da hora:

| Valor/hora | O que significa |
|---|---|
| R$ 9 | salário mínimo por hora — **piso absoluto** |
| R$ 15 a R$ 18 | trabalho manual qualificado, o razoável para começar |
| R$ 25 a R$ 35 | artesã com marca própria, peça autoral, clientela formada |

Cronometre de verdade uma peça, uma vez. Quase sempre o tempo real é maior do que o
que a gente imagina.

### 4. Custo fixo por peça

```
custo fixo = (barraca + transporte + embalagem + internet + domínio) ÷ peças vendidas no mês
```

Exemplo: R$ 40 de barraca no fim de semana + R$ 60 de transporte no mês + R$ 25 de
embalagem + R$ 4 de domínio ÷ 16 peças = **R$ 8 por peça**.

### 5. Margem

De 30% a 40% sobre o custo. **A margem não é o pagamento da Lúcia** — ele já entrou
nas horas. A margem é o que compra barbante novo, repõe a etiqueta, cobre a peça que
encalhou e permite crescer.

---

## Preço em cada canal

O preço de tabela não serve para todo canal. Quem cobra comissão precisa ser
descontado *antes*:

```
preço no canal = (preço direto + taxa fixa) ÷ (1 − comissão)
```

Uma peça de R$ 219 vendida no Elo7 (18% + R$ 3,99) precisaria custar **R$ 272** para
deixar o mesmo dinheiro no bolso. Foi por isso que a análise de viabilidade
recomendou não entrar em marketplace.

| Canal | Comissão | Fórmula |
|---|---|---|
| Feira, WhatsApp, este site com Pix | 0% | preço direto |
| Site com link de cartão | 3% a 5% | preço ÷ 0,96 |
| Elo7 | 18% a 20% + R$ 3,99 | (preço + 3,99) ÷ 0,81 |
| Shopee | 20% + taxa por faixa | (preço + taxa) ÷ 0,80 |

**Preço de feira menor que o do site é proposital e correto:** na feira não tem
frete, não tem embalagem, não tem risco de devolução e o dinheiro entra na hora.
Em geral a feira fica 10% a 15% abaixo do site.

---

## Conferindo com a realidade

Depois de achar o preço, faça três perguntas:

**1. Quanto sobrou por hora?**
```
(preço − barbante − aviamentos − custo fixo) ÷ horas
```
Abaixo de R$ 9 a peça paga menos que o salário mínimo. Ou o preço sobe, ou a peça
precisa ficar mais rápida, ou é melhor não fazer essa peça.

**2. O mercado paga isso?** Procure peça parecida no Elo7 e olhe a faixa. Se o seu
preço ficou 80% acima, ou tem algo errado na conta, ou a peça precisa de uma história
que justifique — e a etiqueta com nome, a peça única e a Coleção Aurora são história
de verdade.

**3. Eu venderia por esse preço na barraca, olhando no olho?** Se a resposta é sim
sem hesitar, provavelmente ainda dá para subir um pouco.

---

## Três peças, calculadas de ponta a ponta

**Bolsa tote vermelha** — 520 g, 16 h
```
barbante   0,52 × 78 × 1,08 = R$ 43,80
aviamentos                     R$  0,00
trabalho   16 × R$ 10           R$ 160,00
custo fixo                     R$  8,00
custo total                    R$ 211,80
preço com 35% de margem        R$ 286,00  ← mais que o mercado paga
preço praticado                R$ 219,00  → R$ 10,45 por hora
```
A peça não fecha na conta ideal. Vende porque atrai gente na barraca e ancora o
preço das outras — mas não é ela que sustenta o mês.

**Bolsinha de mão da Aurora** — sobras, 5 h
```
barbante   sobra de outras peças  R$  5,00
trabalho   5 × R$ 16              R$ 80,00
custo fixo                        R$  4,00
custo total                       R$ 89,00
preço praticado                   R$ 89,00  → R$ 16,00 por hora
```
A peça de melhor retorno de todo o ateliê, feita com o que ia para o lixo.

**Bolsa chevron com couro** — 640 g, 24 h, alça de couro
```
barbante   0,64 × 78 × 1,08 = R$ 53,90
alça de couro                   R$ 35,00
trabalho   24 × R$ 10           R$ 240,00
custo fixo                      R$  8,00
custo total                     R$ 336,90
preço praticado                 R$ 319,00  → R$ 9,25 por hora
```
A peça mais bonita do ateliê e a segunda pior por hora. Vale manter **como vitrine**,
em uma ou duas por temporada — não como produto de linha.

---

## Regra de bolso, para quando não der para calcular

Para barbante nº 6, venda direta:

```
preço ≈ (gramas ÷ 10) + (horas × R$ 11)
```

Bolsa de 520 g e 16 h → 52 + 176 = **R$ 228**. Bem perto dos R$ 219 calculados.
Serve para responder rápido na feira, mas **confira na calculadora** antes de fechar
uma encomenda grande.
