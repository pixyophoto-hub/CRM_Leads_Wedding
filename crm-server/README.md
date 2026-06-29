# Pixyo Wedding CRM — Backend (Phase 1: Google)

Backend Laravel + MySQL untuk dashboard Wedding CRM. **Berasingan** dari app Photobook/landing.
Ia melayani dashboard di `/`, API di `/api/*`, dan webhook Google di `/api/webhook/google/{secret}`.

## Keperluan
- XAMPP (PHP 8.2 + MySQL/MariaDB) — sudah ada.
- Database `pixyo_wedding` (sudah dicipta).
- cloudflared — sudah ada (untuk URL awam webhook).

## Jalankan (local)
1. Pastikan **MySQL XAMPP hidup** (XAMPP Control Panel → Start MySQL).
2. Mula server:
   ```
   C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8091
   ```
3. Buka dashboard: **http://127.0.0.1:8091/**
   (Data kini disimpan dalam pangkalan data, bukan pelayar.)

> Token API & webhook secret ada dalam `.env` (`API_TOKEN`, `WEBHOOK_SECRET`).
> Dashboard dapat token automatik (disuntik oleh `routes/web.php`).

## Sambung Google Form/Sheet (auto lead)
Webhook perlu URL awam. Jalankan tunnel:
```
cloudflared tunnel --url http://127.0.0.1:8091
```
Cloudflared beri URL cth `https://abcd-1234.trycloudflare.com`.
URL webhook penuh anda =
```
https://abcd-1234.trycloudflare.com/api/webhook/google/<WEBHOOK_SECRET>
```
(`<WEBHOOK_SECRET>` ambil dari `.env`.)

Kemudian ikut `google-apps-script.gs` (langkah-langkah di dalam fail itu) — tampal skrip
dalam Google Sheet (Extensions → Apps Script), tukar `WEBHOOK_URL`, pasang trigger
**On form submit**. Hantar jawapan ujian → lead muncul dalam dashboard.

> Nota: cloudflared free bagi URL baharu setiap kali dimulakan. Untuk URL tetap / 24-7,
> guna hosting sebenar (fasa kemudian).

## Uji webhook tanpa Google
```
curl -X POST "http://127.0.0.1:8091/api/webhook/google/<SECRET>" \
  -H "Content-Type: application/json" \
  -d '{"nama":"Ali","telefon":"012-345 6789","pakej":"Wedding"}'
```

## Edit dashboard
Sumber dashboard: `../Dashboard CRM request/index.html`. Selepas edit, sync ke app ini:
```
python sync-dashboard.py
```
(menyalin `support.js` → `public/`, dan `index.html` + suntikan token → `resources/dashboard.html`).

## Skop & had (Phase 1)
- Hanya integrasi **Google** (Form/Sheet) automatik. WhatsApp / Landing / Meta = fasa kemudian.
- Auth: satu **bearer token statik** (local). Login berbilang pengguna = fasa kemudian.
- Dedupe lead ikut nombor telefon (format `+60`/`0` dianggap sama).
- Host: local + cloudflared. Production always-on = fasa kemudian.
