/* ============================================================
   Gerador de Pix Copia e Cola (BR Code)
   ------------------------------------------------------------
   Monta o payload do padrão EMV QRCPS-MPM usado pelo Banco
   Central para o Pix. Roda inteiramente no navegador: nenhum
   dado de pagamento sai do aparelho do cliente, e não existe
   intermediário cobrando taxa.
   ============================================================ */
(function () {
  "use strict";

  /** Monta um campo no formato ID + tamanho (2 dígitos) + valor. */
  function campo(id, valor) {
    var v = String(valor);
    return id + String(v.length).padStart(2, "0") + v;
  }

  /** CRC16/CCITT-FALSE — polinômio 0x1021, inicial 0xFFFF. */
  function crc16(texto) {
    var crc = 0xFFFF;
    for (var i = 0; i < texto.length; i++) {
      crc ^= texto.charCodeAt(i) << 8;
      for (var b = 0; b < 8; b++) {
        crc = (crc & 0x8000) ? ((crc << 1) ^ 0x1021) : (crc << 1);
        crc &= 0xFFFF;
      }
    }
    return crc.toString(16).toUpperCase().padStart(4, "0");
  }

  /** Tira acento, símbolo e espaço duplo — o padrão só aceita ASCII simples. */
  function limpar(texto, limite) {
    var t = String(texto || "")
      .normalize("NFD").replace(/[̀-ͯ]/g, "")
      .replace(/[^A-Za-z0-9 ]/g, "")
      .replace(/\s+/g, " ")
      .trim()
      .toUpperCase();
    return limite ? t.slice(0, limite) : t;
  }

  /** Identificador do pedido: só letras e números, até 25 caracteres. */
  function limparTxid(texto) {
    var t = String(texto || "").normalize("NFD").replace(/[̀-ͯ]/g, "")
      .replace(/[^A-Za-z0-9]/g, "").toUpperCase().slice(0, 25);
    return t || "***";
  }

  /**
   * Gera o código Pix Copia e Cola.
   * @param {{chave:string, favorecido:string, cidade:string,
   *          valor:number, txid:string, descricao?:string}} p
   */
  function gerar(p) {
    if (!p || !p.chave) throw new Error("Chave Pix não configurada.");

    var conta = campo("00", "br.gov.bcb.pix") + campo("01", String(p.chave).trim());
    if (p.descricao) {
      var desc = limpar(p.descricao, 60);
      // O campo 26 inteiro não pode passar de 99 caracteres.
      if (desc && conta.length + desc.length + 4 <= 99) conta += campo("02", desc);
    }

    var payload =
      campo("00", "01") +                                   // versão do padrão
      campo("01", "12") +                                   // uso único (valor fechado)
      campo("26", conta) +                                  // conta Pix
      campo("52", "0000") +                                 // categoria do lojista
      campo("53", "986") +                                  // moeda: real
      (p.valor > 0 ? campo("54", Number(p.valor).toFixed(2)) : "") +
      campo("58", "BR") +
      campo("59", limpar(p.favorecido, 25) || "RECEBEDOR") +
      campo("60", limpar(p.cidade, 15) || "SAO PAULO") +
      campo("62", campo("05", limparTxid(p.txid)));

    payload += "6304";
    return payload + crc16(payload);
  }

  /** Confere se um código Pix está íntegro (CRC bate). */
  function validar(codigo) {
    if (typeof codigo !== "string" || codigo.length < 8) return false;
    var corpo = codigo.slice(0, -4);
    if (!corpo.endsWith("6304")) return false;
    return crc16(corpo) === codigo.slice(-4).toUpperCase();
  }

  /** Desenha o QR Code dentro de um elemento, usando a lib vendorizada. */
  function desenharQR(elemento, codigo, tamanhoCelula) {
    if (typeof qrcode !== "function") {
      elemento.innerHTML = '<p style="font-size:.85rem">Não foi possível desenhar o QR Code. ' +
        'Use o botão "Copiar código" e cole no aplicativo do banco.</p>';
      return false;
    }
    var qr = qrcode(0, "M");          // versão automática, correção média
    qr.addData(codigo, "Byte");
    qr.make();
    elemento.innerHTML = qr.createSvgTag({
      cellSize: tamanhoCelula || 4,
      margin: 4,
      scalable: true
    });
    var svg = elemento.querySelector("svg");
    if (svg) {
      svg.setAttribute("role", "img");
      svg.setAttribute("aria-label", "QR Code do Pix");
      svg.style.width = "100%";
      svg.style.height = "auto";
      svg.style.maxWidth = "260px";
      svg.style.background = "#fff";
    }
    return true;
  }

  window.PIX = { gerar: gerar, validar: validar, desenharQR: desenharQR, crc16: crc16 };
})();
