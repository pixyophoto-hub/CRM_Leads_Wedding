/**
 * Pixyo Wedding CRM — Google → CRM lead forwarder
 * --------------------------------------------------
 * Hantar setiap jawapan Google Form (atau baris Google Sheet) ke CRM.
 *
 * LANGKAH:
 * 1. Buka Google SHEET yang menyimpan jawapan borang anda.
 * 2. Menu: Extensions → Apps Script.
 * 3. Padam kod sedia ada, tampal SEMUA kod ini.
 * 4. Tukar WEBHOOK_URL di bawah kepada URL cloudflared anda + secret
 *    (lihat fail GOOGLE-INTEGRATION.md untuk URL penuh).
 * 5. Simpan (ikon disket).
 * 6. Menu kiri: Triggers (jam ⏰) → Add Trigger:
 *      - Choose function: onFormSubmit
 *      - Event source: From spreadsheet
 *      - Event type: On form submit
 *    Save (benarkan kebenaran bila diminta).
 * 7. Hantar satu jawapan ujian pada borang → lead patut muncul dalam CRM.
 */

// Tukar kepada URL cloudflared anda + WEBHOOK_SECRET dari crm-server/.env
const WEBHOOK_URL = 'https://YOUR-TUNNEL.trycloudflare.com/api/webhook/google/YOUR_WEBHOOK_SECRET';

function onFormSubmit(e) {
  const v = (e && e.namedValues) ? e.namedValues : {};

  // Ambil nilai ikut tajuk soalan (tidak case-sensitive). Tambah sinonim jika perlu.
  const get = function (keys) {
    for (const want of keys) {
      for (const key in v) {
        if (key.trim().toLowerCase() === want && v[key] && v[key][0]) return String(v[key][0]).trim();
      }
    }
    return '';
  };

  const payload = {
    name:    get(['nama', 'name', 'full name', 'nama penuh']),
    phone:   get(['no telefon', 'no. telefon', 'telefon', 'phone', 'whatsapp', 'nombor telefon', 'no wa']),
    email:   get(['email', 'emel', 'e-mel', 'e-mail']),
    service: get(['pakej', 'service', 'package', 'servis']),
    source:  'Google Form',
  };

  UrlFetchApp.fetch(WEBHOOK_URL, {
    method: 'post',
    contentType: 'application/json',
    payload: JSON.stringify(payload),
    muteHttpExceptions: true,
  });
}

/**
 * PILIHAN (jika lead ditaip TERUS dalam Sheet, bukan dari Form):
 * Guna trigger "Time-driven" (cth setiap 5 minit) pada fungsi scanSheet.
 * Ia hantar baris baharu sahaja (jejak baris terakhir diproses).
 * Anggapan: baris 1 = header dengan tajuk Nama / Telefon / Email / Pakej.
 */
function scanSheet() {
  const sheet = SpreadsheetApp.getActiveSpreadsheet().getSheets()[0];
  const props = PropertiesService.getScriptProperties();
  const last = Number(props.getProperty('lastRow') || 1); // 1 = lepas header
  const data = sheet.getDataRange().getValues();
  if (data.length <= 1) return;

  const header = data[0].map(function (h) { return String(h).trim().toLowerCase(); });
  const col = function (keys) { for (var i = 0; i < header.length; i++) { if (keys.indexOf(header[i]) !== -1) return i; } return -1; };
  const ci = {
    name: col(['nama', 'name', 'nama penuh']),
    phone: col(['no telefon', 'telefon', 'phone', 'whatsapp', 'no wa']),
    email: col(['email', 'emel', 'e-mel']),
    service: col(['pakej', 'service', 'package']),
  };

  for (var r = last; r < data.length; r++) {
    const row = data[r];
    UrlFetchApp.fetch(WEBHOOK_URL, {
      method: 'post',
      contentType: 'application/json',
      payload: JSON.stringify({
        name: ci.name >= 0 ? row[ci.name] : '',
        phone: ci.phone >= 0 ? row[ci.phone] : '',
        email: ci.email >= 0 ? row[ci.email] : '',
        service: ci.service >= 0 ? row[ci.service] : '',
        source: 'Google Sheet',
      }),
      muteHttpExceptions: true,
    });
  }
  props.setProperty('lastRow', String(data.length));
}
