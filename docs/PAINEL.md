# O painel do ateliê

Endereço: **luciamariacroche.com.br/painel**
Senha inicial: **`croche2026`** — troque na primeira semana, em
`painel/senha.php` (o link aparece depois de entrar).

O painel não aparece no menu do site, não vai para o Google e só abre com senha.

---

## Visão geral

A primeira tela responde quatro perguntas:

- **Receita do mês** — quanto entrou de pedidos já pagos.
- **Sobrou no mês** — o que restou depois do barbante, do frete e das despesas.
- **Por hora de crochê** — o número mais importante do painel. Ver abaixo.
- **Ticket médio** — quanto vale, em média, cada pedido.

Tem ainda o que precisa de ação hoje, a receita dos últimos seis meses em barrinhas
(é aí que a sazonalidade da praia fica visível) e quanto do teto do MEI já foi usado.

### O número que importa: R$ por hora

O painel estima as horas de cada pedido a partir do tempo de produção cadastrado em
`dados/produtos.js`, e divide o que sobrou por essas horas.

- **Abaixo de R$ 9/h** — está pagando menos que o salário mínimo por hora. O painel
  avisa em vermelho.
- **R$ 15/h** — a meta configurada em `api/config.php`.
- **Acima disso** — é o número que precisa subir ano a ano, mais do que o faturamento.

Faturar mais trabalhando muito mais não é crescer. Este número mostra a diferença.

---

## Pedidos

Cada pedido traz as peças, o cliente, o endereço, o que ele pediu no campo de
observação, e a estimativa de horas e de barbante daquele pedido.

**O caminho normal de um pedido:**

1. `Aguardando pagamento` — acabou de entrar.
2. `Cliente disse que pagou` — ele tocou em “Já paguei”. **Confira o extrato.**
3. `Pago` — você marcou depois de conferir. Um botão manda a confirmação no WhatsApp.
4. `Enviado` — cole o código de rastreio e use o botão que manda o código ao cliente.
5. `Entregue`.

Com o Mercado Pago ligado, os passos 2 e 3 acontecem sozinhos.

> Nunca marque como pago sem ver o dinheiro na conta. Golpe de comprovante falso é
> comum, e o print sempre parece verdadeiro.

---

## Financeiro

### Lançar despesa

Lance **tudo**: barbante, barraca, gasolina, saco kraft, DAS do MEI, domínio.
O que não é lançado vira lucro que não existe — e aí a conta engana vocês.

O barbante das peças vendidas já é estimado automaticamente pelo peso cadastrado de
cada peça, para não ficar de fora quando esquecerem de lançar a compra.

### Os períodos

Este mês, mês passado, este ano, **temporada (dezembro a fevereiro)** e desde o
começo. O botão da temporada existe porque em praia o verão é outro negócio: compare
a temporada com o resto do ano para enxergar o tamanho da sazonalidade.

### Planilha

**Baixar planilha (CSV)** abre no Excel, no Google Planilhas ou no LibreOffice. É o
arquivo para mandar ao contador na declaração anual do MEI.

### Limite do MEI

A barra mostra quanto do teto de R$ 81.000 já foi usado no ano. Passando de 80%, ela
fica vermelha — aí é hora de conversar com um contador antes de continuar vendendo.

---

## Marketing

### Chamar de volta quem sumiu

Lista de quem comprou e não voltou há mais de 90 dias, ordenada por quanto já gastou.
Cada linha tem um botão que abre o WhatsApp com a mensagem pronta.

**Este é o recurso mais valioso do painel.** Quem já comprou uma vez tem muito mais
chance de comprar de novo do que um estranho — e falar com essa pessoa custa zero.
Uma mensagem por vez, escrita como gente, não como loja.

### Clientes

A lista se preenche sozinha com os pedidos do site. Quem marcou “pode me avisar
quando tiver peça nova” aparece marcado — **só mande campanha para esses.**

Na feira, use o caderno: nome, WhatsApp, o que levou e a pergunta
*“posso te avisar quando tiver peça nova?”*. Depois passe para cá.

### De onde vieram as vendas

Para isso funcionar, use os **links com origem**: um link diferente para a bio do
Instagram, outro para o story, outro para o QR da barraca. Aí o painel mostra qual
canal traz dinheiro, em vez de vocês adivinharem.

### QR code da barraca

Gera o QR que leva ao site, já marcado como vindo da feira. **Baixe, imprima grande,
plastifique** e deixe na frente da barraca com a frase *“Não levou hoje? Compre pelo
site.”*

É o elo que transforma turista de uma semana em cliente de anos. Sem ele, a feira e o
site viram dois negócios separados.

### Campanhas e cupons

Registre a campanha com nome, cupom, período e canal. **O cupom é aplicado à mão, no
WhatsApp, na hora de fechar** — o site não desconta sozinho. É de propósito: ninguém
usa cupom vencido sem vocês saberem.

---

## Diário (blog)

Escrever é simples: título, chamada, texto. A formatação usa marcas fáceis:

| Você escreve | Vira |
|---|---|
| `## Um título` | subtítulo |
| `- item` | item de lista |
| `> frase` | frase em destaque |
| `**palavra**` | palavra em negrito |

Deixe uma linha em branco entre os parágrafos.

Ao salvar com **“Publicar no site”** marcado, o texto vai para o ar na hora.
Desmarcado, fica guardado como rascunho, só visível no painel.

### Para que serve o diário

Duas coisas, e nenhuma delas é “ter um blog”:

1. **O Google acha vocês.** Quem procura “como lavar tapete de crochê” cai no texto e
   descobre a loja. Isso traz gente sem pagar anúncio.
2. **Quem comprou continua por perto.** Dá assunto para o Instagram e razão para a
   pessoa voltar ao site fora da temporada.

**Um texto por mês basta.** Não vire obrigação.

---

## Cuidados

- **Troque a senha inicial** em `painel/senha.php`.
- **Baixe a pasta `dados-privados` uma vez por mês.** É todo o histórico financeiro e
  a lista de clientes. Se o servidor sumir sem backup, não tem como recuperar.
- Não mande o endereço do painel em grupo de WhatsApp nem deixe a senha anotada na
  barraca.
- Lista de clientes é dado pessoal: guardem porque é útil para vocês, não repassem
  para ninguém, e tirem quem pedir para sair. Isso é o que a LGPD espera de qualquer
  negócio, do tamanho que for.
