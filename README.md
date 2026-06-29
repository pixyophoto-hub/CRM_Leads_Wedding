# Pixyo Wedding CRM

CRM untuk studio fotografi perkahwinan (UI Bahasa Melayu). Satu repo, dua bahagian:

```
Dashboard CRM request/   # Sumber dashboard (front-end DC: index.html + support.js)
crm-server/              # Backend Laravel + MySQL (API + webhook Google) — melayani dashboard
```

## Bahagian
- **`crm-server/`** — app Laravel 12 + MySQL (`pixyo_wedding`). Melayani dashboard di `/`,
  API di `/api/*`, dan webhook Google di `/api/webhook/google/{secret}`. Lihat
  [`crm-server/README.md`](crm-server/README.md) untuk cara jalankan + sambung Google.
- **`Dashboard CRM request/`** — sumber dashboard (boleh edit). Selepas edit, sync ke backend
  dengan `python crm-server/sync-dashboard.py`. Lihat
  [`Dashboard CRM request/README.md`](Dashboard%20CRM%20request/README.md).

## Mula cepat (local)
1. Hidupkan MySQL XAMPP.
2. `cd crm-server` → `C:\xampp\php\php.exe artisan serve --port=8091`
3. Buka `http://127.0.0.1:8091/`.

## Status
- **Phase 1 (Google) — siap.** Lead dari Google Form/Sheet → webhook → CRM, data dalam pangkalan data.
- Akan datang: WhatsApp, Landing Page, Meta Ads, login berbilang pengguna, hosting 24/7.

## Nota keselamatan
Rahsia (token API, webhook secret, kunci app) ada dalam `crm-server/.env` yang **tidak**
dimasukkan ke git. `vendor/` juga dikecualikan — jalankan `composer install` selepas clone.
