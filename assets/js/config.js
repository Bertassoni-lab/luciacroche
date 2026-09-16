/* ============================================================
   CONFIGURAÇÃO DA LOJA — Lúcia Maria Crochê
   ------------------------------------------------------------
   Troque os valores abaixo pelos dados reais antes de publicar.
   Tudo que está marcado com "TROCAR" é obrigatório.
   ============================================================ */

window.LOJA = {
  nome: "Lúcia Maria Crochê",
  assinatura: "Feito à mão por Lúcia e Aurora · Boiçucanga, litoral norte de SP",

  /* ---- Contato (TROCAR) ------------------------------------ */
  // Número no formato internacional, só dígitos: 55 + DDD + número
  whatsapp: "5512900000000",
  instagram: "luciamariacroche",
  email: "contato@luciamariacroche.com.br",

  /* ---- Pagamento (TROCAR) ---------------------------------- */
  pix: {
    chave: "000.000.000-00",
    tipoChave: "CPF",
    favorecido: "Lúcia Maria (nome completo do titular)",
    banco: "Banco do titular"
  },
  // Desconto oferecido para quem paga no Pix (0.05 = 5%)
  descontoPix: 0.05,
  parcelamento: {
    ativo: true,
    maxParcelas: 3,
    valorMinimoParcela: 50,
    observacao: "Cartão em até 3x sem juros pelo link de pagamento."
  },

  /* ---- Entrega --------------------------------------------- */
  frete: {
    // Frete grátis para o Brasil inteiro a partir deste valor
    gratisAcimaDe: 350,
    // Estimativas para mostrar no site. O valor real é fechado no WhatsApp.
    estimativaSudeste: 28,
    estimativaOutrasRegioes: 46,
    prazoUtil: "5 a 10 dias úteis pelo PAC, depois da peça pronta",
    retiradaLocal: {
      ativa: true,
      titulo: "Retirada na feira, sem frete",
      texto: "Você separa pelo site e retira com a gente na feira de Boiçucanga, sem pagar frete."
    }
  },

  /* ---- Agenda das feiras ----------------------------------- */
  feiras: [
    {
      nome: "Feira de Artesanato de Boiçucanga",
      local: "Praça Pôr do Sol, Boiçucanga — São Sebastião/SP",
      quando: "Sexta, sábado e domingo, a partir do fim da tarde",
      observacao: "Na alta temporada (dezembro a fevereiro) e nos feriados, todos os dias.",
      mapa: "https://www.google.com/maps/search/?api=1&query=Pra%C3%A7a+P%C3%B4r+do+Sol+Boi%C3%A7ucanga+S%C3%A3o+Sebasti%C3%A3o+SP"
    },
    {
      nome: "Feira livre de Boiçucanga",
      local: "Rua central de Boiçucanga — São Sebastião/SP",
      quando: "Manhã de quarta-feira",
      observacao: "Confirme a data conosco no WhatsApp antes de ir — nem toda semana levamos barraca.",
      mapa: "https://www.google.com/maps/search/?api=1&query=Boi%C3%A7ucanga+S%C3%A3o+Sebasti%C3%A3o+SP"
    }
  ],

  /* ---- Textos do site -------------------------------------- */
  avisoEncomenda: "Peça sob encomenda: a Lúcia começa a sua depois da confirmação do pagamento.",
  site: "https://luciamariacroche.com.br"
};
