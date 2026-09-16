# Como colocar o site no ar

O site é feito de arquivos soltos (HTML, CSS, imagens). Qualquer hospedagem serve, e
as três primeiras opções abaixo são de graça.

---

## 1. Registrar o domínio

No [registro.br](https://registro.br), procure por `luciamariacroche.com.br`.

- Custa cerca de **R$ 40 por ano**.
- Precisa de CPF ou do CNPJ do MEI.
- Registre também o Instagram com o mesmo nome, mesmo que não use agora.

> Faça isso **antes** de mandar imprimir cartão, etiqueta ou QR code.

---

## 2. Escolher onde hospedar

### Opção A — Netlify (mais simples, de graça)

1. Crie conta em [netlify.com](https://netlify.com).
2. Em **Add new site → Deploy manually**, arraste a pasta inteira do projeto.
3. O site sobe na hora, num endereço tipo `nome-aleatorio.netlify.app`.
4. Em **Domain settings**, aponte para `luciamariacroche.com.br` (o Netlify mostra
   quais valores colocar no registro.br).

Para atualizar depois, é só arrastar a pasta de novo.

### Opção B — GitHub Pages (de graça, versionado)

Este projeto já é um repositório Git.

1. Envie para o GitHub: `git push -u origin main`
2. No repositório, vá em **Settings → Pages**.
3. Em **Source**, escolha a branch `main` e a pasta `/ (root)`.
4. Em **Custom domain**, escreva `luciamariacroche.com.br`.

Vantagem: guarda o histórico de tudo que mudou, dá para voltar atrás.

### Opção C — Cloudflare Pages (de graça, mais rápido no Brasil)

Mesma ideia do Netlify, com entrega mais rápida. Conecte o repositório do GitHub e
deixe o comando de build vazio — não existe build neste projeto.

### Opção D — Hostinger ou outra hospedagem paga (R$ 10 a R$ 30/mês)

Só faz sentido se já houver uma contratada. Envie os arquivos por FTP ou pelo
gerenciador de arquivos, para dentro da pasta `public_html`.

---

## 3. Conferir depois de publicar

- [ ] Abrir o site no **celular** — é de onde vem quase todo mundo
- [ ] Clicar no botão verde do WhatsApp e ver se abre a conversa certa
- [ ] Colocar uma peça na sacola e fechar o pedido: a mensagem tem que chegar no
      WhatsApp com as peças listadas
- [ ] Ver se as fotos aparecem em todas as peças
- [ ] Mandar o link para alguém no WhatsApp e ver se a imagem de capa aparece
- [ ] `luciamariacroche.com.br/sitemap.xml` tem que abrir

---

## 4. Avisar o Google

1. Entre no [Google Search Console](https://search.google.com/search-console).
2. Cadastre `luciamariacroche.com.br`.
3. Em **Sitemaps**, envie `sitemap.xml`.

Demora de uma a três semanas para aparecer nas buscas.

### Muito mais importante que o Google: o Perfil da Empresa

Cadastre no [Google Meu Negócio](https://business.google.com) como **artesanato em
São Sebastião/SP**, com as fotos boas e os dias de feira. Quem procura "artesanato
Boiçucanga" no celular, na praia, encontra por ali — e essa busca converte muito mais
do que "bolsa de crochê", onde a briga é com 3.800 anúncios.

---

## 5. Atualizando depois

| Hospedagem | Como atualizar |
|---|---|
| Netlify manual | arrastar a pasta de novo |
| GitHub Pages | `git add . && git commit -m "peças novas" && git push` |
| Cloudflare Pages | dar push no GitHub, ele publica sozinho |
| FTP | subir só os arquivos que mudaram |

A atualização mais comum é **uma peça nova**: mexe em `dados/produtos.js` e em
`assets/img/produtos/`. Veja [COMO-ATUALIZAR.md](COMO-ATUALIZAR.md).
