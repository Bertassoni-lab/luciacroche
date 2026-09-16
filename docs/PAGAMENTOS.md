# Pagamentos: como o dinheiro entra

O checkout acontece **dentro do site**, sem mandar o cliente para fora. Existem três
caminhos, e os três funcionam.

---

## 1. Pix direto — funciona desde o primeiro dia, sem taxa

É o caminho padrão, e não depende de contratar ninguém.

Quando o cliente escolhe Pix, o site monta ali mesmo o **QR Code e o código
copia e cola**, já com o valor certo e com o número do pedido dentro. O cliente
abre o banco, lê o QR e paga direto na conta da Drisana. Nenhum intermediário,
**nenhuma taxa, nada descontado**.

| | |
|---|---|
| Custo | **R$ 0** |
| Dinheiro cai | na hora |
| Confirmação | manual: vocês olham o extrato e marcam como pago no painel |

O código é gerado no padrão do Banco Central (EMV QRCPS-MPM, o mesmo dos bancos),
e o dígito verificador foi conferido contra o exemplo oficial do BACEN.

### Como funciona no dia a dia

1. O cliente paga e toca em **“Já paguei — avisar a Lúcia”**.
2. O pedido aparece no painel como **“Cliente disse que pagou”**.
3. Vocês conferem o extrato e marcam **Pago** em `painel/pedidos.php`.
4. Um botão pronto manda a mensagem de confirmação no WhatsApp do cliente.

### Uma coisa importante sobre o nome no Pix

A chave é o celular da **Drisana Holland**, mãe da Aurora. Quem paga vê esse nome no
aplicativo do banco — e nome diferente do da loja assusta e derruba venda.

Por isso o checkout já avisa, embaixo do QR: *“O Pix é recebido por Drisana Holland,
mãe da Aurora e responsável financeira do ateliê.”* Não tirem esse aviso.

**Quando abrirem o MEI**, vale trocar para uma chave no CNPJ do MEI. Motivos:

- O nome que aparece passa a ser o da empresa, e some o estranhamento.
- O dinheiro do negócio para de se misturar com o dinheiro pessoal da Drisana —
  que é o que mais complica a contabilidade e a declaração anual.
- Movimentação alta em conta pessoal de quem não é o titular do negócio é o tipo de
  coisa que gera pergunta chata do banco e da Receita.

Enquanto isso não acontece, tudo bem: dá para operar assim, só precisa anotar tudo.

---

## 2. Cartão pelo Mercado Pago — para quando quiserem parcelar

Já está programado. Fica **desligado** até vocês preencherem as chaves, porque ligar
antes de ter conta só atrapalharia a venda.

Quando ligar, o cliente digita o cartão **dentro do site mesmo** (formulário do
Mercado Pago embutido na página), parcela em até 3x, e o pedido cai como pago
sozinho. O número do cartão nunca passa pelo servidor de vocês — quem guarda é o
Mercado Pago, que é quem tem certificação para isso.

### Como ligar

1. Crie conta em [mercadopago.com.br](https://mercadopago.com.br) — de graça, com
   CPF ou com o CNPJ do MEI.
2. Vá em **Seu negócio → Configurações → Gerenciar credenciais → Credenciais de produção**.
3. Copie a **Public Key** e o **Access Token**.
4. Abra `api/config.php` no servidor e preencha:

```php
'mercadopago' => [
    'ativo'        => true,
    'public_key'   => 'APP_USR-...',
    'access_token' => 'APP_USR-...',
    'webhook_chave' => '',
],
```

5. No painel do Mercado Pago, em **Webhooks**, cadastre a URL
   `https://luciamariacroche.com.br/api/webhook.php`, copie o segredo que ele
   gera e cole em `webhook_chave`.

Pronto. A aba **Cartão** do checkout se acende sozinha, e o Pix passa a ter baixa
automática também.

> **Nunca** coloque o `access_token` em nenhum arquivo dentro de `assets/`, nem mande
> por WhatsApp. Ele fica só em `api/config.php`, que o `.htaccess` bloqueia.

### O que o Mercado Pago cobra (setembro de 2026)

| Forma | Taxa aproximada | Quando o dinheiro cai |
|---|---|---|
| Pix | ~1% | na hora |
| Débito | ~2% | 1 dia |
| Crédito à vista | ~4,5% | 14 dias (ou na hora, pagando mais) |
| Crédito parcelado | ~5% + juros por parcela | conforme o plano |

Confirme as taxas no site deles antes de decidir — mudam de tempos em tempos.

**Numa bolsa de R$ 219, o cartão à vista come cerca de R$ 10.** É quase uma hora de
trabalho da Lúcia. Por isso o site oferece **5% de desconto no Pix**: é melhor dar o
desconto para o cliente do que pagar taxa para a máquina — e o dinheiro cai na hora.

---

## 3. Combinar no WhatsApp

A terceira aba. O pedido fica registrado no painel com tudo (peças, endereço,
observação) e a conversa continua na mão. Serve para encomenda sob medida, para
quem tem dúvida antes de pagar e para quem simplesmente prefere falar com gente.

---

## Qual oferecer

Comecem com **Pix direto e WhatsApp**. É o que custa zero e é o que a maioria dos
clientes de feira já usa.

Liguem o cartão quando uma das duas coisas acontecer:

- alguém desistir de uma peça porque queria parcelar (anotem quando acontecer); ou
- o ticket médio passar de R$ 250, quando parcelar começa a fazer diferença de verdade.

Antes disso, o cartão só traria taxa sem trazer venda.

---

## Conferindo se está tudo certo

Depois de publicar, faça um pedido de teste de verdade, de R$ 1:

1. Cadastre uma peça temporária de R$ 1 pelo `ferramentas/cadastro.html`.
2. Feche o pedido pelo site, escolhendo Pix.
3. Leia o QR com o seu próprio banco: confira **valor**, **nome do recebedor** e
   se a chave é mesmo a de vocês.
4. Pague, veja o pedido aparecer no painel e marque como pago.
5. Apague a peça de teste.

Vale a pena fazer isso antes da primeira venda real.
