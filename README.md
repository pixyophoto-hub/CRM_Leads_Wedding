# Pixyo CRM Dashboard

Single-page CRM dashboard for a wedding-photography studio (Bahasa Melayu UI). Fully operational front-end app — leads, messages, tasks, automation, reports, contacts and settings — with data persisted in the browser via `localStorage`.

## Files
- `index.html` — hostable entry point (open this).
- `CRM Dashboard.dc.html` — same app in Design Component format.
- `support.js` — the DC runtime (loads React 18 from a CDN and mounts the app). Must sit next to the HTML.

## Run
Serve the folder over HTTP (not `file://`):

```bash
python -m http.server 8077
# then open http://localhost:8077/index.html
```

Or upload `index.html` + `support.js` together to any static host (Netlify, GitHub Pages, etc.). Needs internet on first load (React CDN + Google Fonts).

## Features
- 9 views: Dashboard, Leads, Messages, Calendar, Tasks, Automation, Reports, Contacts, Settings.
- Real CRUD with persistence: add/edit-status/delete leads, send messages, add/tick/delete tasks, toggle automations, add contacts, save settings.
- All KPIs, donut/funnel charts, reports and the calendar are derived from your data.
- "Padam Semua Data" / "Muat Data Contoh" in Settings to reset or reseed.

## Note
Data lives in the browser's `localStorage`, so it is per-device and not shared between machines. Multi-user / shared data would require a backend (e.g. Laravel + database).
