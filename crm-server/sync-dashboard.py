#!/usr/bin/env python3
# Copies the DC dashboard into this Laravel app:
#   - support.js            -> public/support.js  (served at /support.js)
#   - index.html (+ __CRM)  -> resources/dashboard.html  (served by routes/web.php)
# Run after editing the dashboard source:  python sync-dashboard.py
import os, shutil

SRC = os.path.join(os.path.dirname(__file__), "..", "Dashboard CRM request")
HERE = os.path.dirname(__file__)

# 1) runtime
shutil.copyfile(os.path.join(SRC, "support.js"), os.path.join(HERE, "public", "support.js"))

# 2) dashboard html with API config injected into <head>
html = open(os.path.join(SRC, "index.html"), encoding="utf-8").read()
inject = '<script>window.__CRM={apiBase:"",token:"__API_TOKEN__"};</script>'
assert inject not in html, "dashboard source already injected"
html = html.replace("<head>", "<head>\n" + inject, 1)
open(os.path.join(HERE, "resources", "dashboard.html"), "w", encoding="utf-8").write(html)

print("synced -> public/support.js, resources/dashboard.html")
