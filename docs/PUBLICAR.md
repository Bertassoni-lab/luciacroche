# Colocar no ar pela Hostinger

O site tem duas partes:

- **a loja** — arquivos HTML, CSS, JavaScript e imagens;
- **o painel e o checkout** — PHP, que precisa de servidor.

A Hostinger roda as duas. **Requisito: PHP 8.1 ou superior** (a Hostinger já vem com
8.2 ou 8.3, e dá para escolher no hPanel em *Avançado → Configuração do PHP*).

---

## 1. Domínio

`luciamariacroche.com.br` precisa estar registrado no [registro.br](https://registro.br)
(cerca de R$ 40 por ano, com CPF ou com o CNPJ do MEI).

- **Se o domínio foi comprado junto com a Hostinger**, ela já cuida de tudo.
- **Se foi registrado direto no registro.br**, entre no painel do registro.br e troque
  os servidores DNS pelos dois que a Hostinger mostra em *Domínios → DNS*. Costuma
  levar de 15 minutos a algumas horas para valer.

---

## 2. Subir os arquivos

No hPanel da Hostinger, vá em **Arquivos → Gerenciador de arquivos** e entre em
`public_html`.

1. Apague o `default.php` ou o `index.html` de exemplo que vem lá.
2. Clique em **Enviar arquivos** e mande o `.zip` do projeto.
3. Clique com o botão direito no zip e escolha **Extrair**.
4. Confira que ficou assim — os arquivos **soltos** dentro de `public_html`, não
   dentro de uma pasta a mais:

```
public_html/
├── index.html
├── produtos.html
├── checkout.html
├── blog.html
├── api/
├── painel/
├── assets/
├── dados/
└── dados-privados/
```

> Se abrir `luciamariacroche.com.br` e aparecer uma lista de arquivos ou um erro 404,
> quase sempre é isto: o conteúdo ficou dentro de uma subpasta. Mova tudo um nível
> para cima.

Quem preferir FTP: em *Arquivos → Contas de FTP* a Hostinger mostra servidor, usuário
e senha para usar no FileZilla. O destino é o mesmo `public_html`.

---

## 3. Permissões das pastas

O painel precisa **escrever** em duas pastas. No gerenciador de arquivos, clique com o
botão direito em cada uma → **Permissões**:

| Pasta | Permissão | Para quê |
|---|---|---|
| `dados-privados/` | **755** | guardar pedidos, clientes, despesas |
| `dados/` | **755** | o painel regravar `blog.js` ao publicar um texto |

Se ao publicar um texto aparecer *“não consegui escrever em dados/blog.js”*, é isso.

---

## 4. Ligar o HTTPS (o cadeado)

Em **Segurança → SSL**, instale o certificado gratuito e ligue
**Forçar HTTPS**. Sem cadeado, o navegador avisa que o site é inseguro bem na hora
em que a pessoa vai pagar — e ela desiste.

---

## 5. Trocar a senha do painel

1. Abra `luciamariacroche.com.br/painel` e entre com a senha inicial **`croche2026`**.
2. Vá em `painel/senha.php`, digite a senha nova e copie o texto que aparece.
3. No gerenciador de arquivos, abra `api/config.php`, substitua a linha
   `'painel_senha_hash' => '...'` pela que você copiou e salve.

**Não pule este passo.** A senha inicial está escrita nesta documentação, que é
pública no repositório.

---

## 6. Conferir se está tudo funcionando

- [ ] `luciamariacroche.com.br` abre com o cadeado verde
- [ ] Abre bem **no celular** — é de onde vem quase todo mundo
- [ ] O botão verde do WhatsApp abre a conversa com **(12) 99614-8324**
- [ ] Colocar uma peça na sacola → **Fechar pedido** → o checkout abre
- [ ] Digitar um CEP e o frete calcular
- [ ] No passo do Pix, o **QR Code aparece**
- [ ] **Ler o QR com o seu banco** e conferir: valor certo e nome *Drisana Holland*
- [ ] O pedido aparece em `painel/pedidos.php`
- [ ] `luciamariacroche.com.br/dados-privados/pedidos.json` dá **erro 403** (se abrir
      o conteúdo, o `.htaccess` não está valendo — avise quem montou o site)
- [ ] Publicar um texto no diário e ver aparecer em `blog.html`

---

## 7. Avisar o Google

1. [Google Search Console](https://search.google.com/search-console) → cadastre o site
   → em **Sitemaps**, envie `sitemap.xml`.
2. Mais importante que isso: **[Google Meu Negócio](https://business.google.com)**,
   como *artesanato em São Sebastião/SP*, com as fotos boas e os dias de feira.

Quem procura “artesanato Boiçucanga” no celular, na praia, encontra por ali — e essa
busca vende muito mais do que “bolsa de crochê”, onde a briga é com 3.800 anúncios.

---

## 8. Atualizar depois

Para trocar preço, peça nova ou foto: mexa nos arquivos e mande de novo pelo
gerenciador de arquivos (ou por FTP), substituindo os antigos.

| O que mudou | Arquivos para subir |
|---|---|
| Preço ou peça nova | `dados/produtos.js` e a foto em `assets/img/produtos/` |
| Texto do diário | nada — o painel publica sozinho |
| Contato, Pix, feiras | `assets/js/config.js` |
| Chaves do Mercado Pago | `api/config.php` |

**Nunca sobrescreva a pasta `dados-privados/`** ao atualizar: ela tem os pedidos e os
clientes. Se mandar o zip inteiro de novo, extraia em outro lugar e copie só o que
mudou.

---

## Backup

Uma vez por mês, baixe a pasta `dados-privados/` e guarde no computador ou no Google
Drive. São os pedidos, os clientes e as despesas — o histórico do negócio. A
Hostinger tem backup automático nos planos maiores, mas não conte só com ele.

---

## Outras hospedagens

Se um dia sair da Hostinger, qualquer hospedagem com **PHP 8.1+** serve (Locaweb,
KingHost, Hostgator). Só não dá para usar hospedagem de site estático (GitHub Pages,
Netlify) **com o painel**: lá a loja funcionaria, mas o checkout e o painel, não.
