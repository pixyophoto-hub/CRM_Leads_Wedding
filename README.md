# Pixyo CRM Dashboard

Single-page CRM dashboard for a wedding-photography studio (Bahasa Melayu UI) — leads, messages, tasks, automation, reports, contacts and settings.

> **This is now the front-end of a backend-driven app.** As of Phase 1 the dashboard
> reads/writes data from a Laravel + MySQL backend (the `crm-server` app) via `/api/*`,
> so leads from Google can flow in automatically and data is shared, not per-browser.
> Opening `index.html` on its own will just show "Memuatkan…" — it needs the backend.

## Files
- `index.html` — dashboard source (DC template + logic, wired to the API).
- `CRM Dashboard.dc.html` — same app in Design Component format (kept in sync).
- `support.js` — the DC runtime (loads React 18 from a CDN and mounts the app).

## Run
The backend serves this dashboard at its root URL. Run the `crm-server` Laravel app
(see its README), then open `http://127.0.0.1:8091/`. After editing this source, copy it
into the backend with `python ../crm-server/sync-dashboard.py`.

The earlier standalone/localStorage version (no backend) is in this repo's git history.

## Features
- 9 views: Dashboard, Leads, Messages, Calendar, Tasks, Automation, Reports, Contacts, Settings.
- Real CRUD with persistence: add/edit-status/delete leads, send messages, add/tick/delete tasks, toggle automations, add contacts, save settings.
- All KPIs, donut/funnel charts, reports and the calendar are derived from your data.
- "Padam Semua Data" / "Muat Data Contoh" in Settings to reset or reseed.

## Note
Data lives in the browser's `localStorage`, so it is per-device and not shared between machines. Multi-user / shared data would require a backend (e.g. Laravel + database).
