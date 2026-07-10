---
name: clarity-ux-insights-dashboard
description: "Use when turning Microsoft Clarity behavior analytics into a reusable UX dashboard for plain HTML sites, WordPress admin pages, or embedded front-end reports."
version: 1.0.0
author: Hermes Agent
license: MIT
metadata:
  hermes:
    tags: [clarity, ux, dashboard, analytics, html, wordpress, heatmap, recordings]
    related_skills: [hermes-agent-skill-authoring]
---

# Microsoft Clarity UX Insights Dashboard

## Overview

This skill turns a Microsoft Clarity-style UX analysis page into a reusable architecture that works beyond one WordPress admin screen.

Use it when the user wants a dashboard that can:

- accept a **Clarity project ID**, **API key**, or **backend token/config**
- fetch behavioral analytics from **Microsoft Clarity** or a backend proxy that talks to Clarity
- display **recordings / behavior signals / UX issue summaries / top pages / recommendations**
- render in **plain HTML**, **WordPress admin**, **WordPress front-end**, or **custom apps**

The front-end must be portable. Sensitive credentials must stay server-side.

## When to Use

Use this skill when the user asks to:

- rebuild a Clarity dashboard outside WordPress
- show UX insights on a general HTML page
- create a reusable "UX analysis center" for multiple websites
- let each site input its own key/token/project settings and reuse one UI shell
- turn recordings / rage clicks / dead clicks / quick backs / scroll patterns into readable recommendations

Do **not** use this skill when:

- the user only wants the raw Clarity embed script
- the user only needs one-off screenshots, not a reusable dashboard
- the user wants direct browser-side calls with exposed secret tokens

## Important Security Rule

Do **not** place Clarity secrets directly in public HTML/JS.

Safe architecture:

1. front-end HTML page calls your own backend endpoint
2. backend stores the Clarity key/token/project configuration
3. backend fetches and normalizes data from Clarity
4. front-end renders the normalized JSON payload

Unsafe architecture:

- putting private API keys directly in `index.html`
- exposing management tokens in browser requests

## Reality Check from Current Site

The target admin URL provided by the user was:

- `https://hostswp.com/wp-admin/admin.php?page=hostwp-clarity-recordings`

Observed facts from this environment:

- requesting that URL without auth redirects to the WordPress login page
- no local PHP source matching `hostwp-clarity-recordings` or `clarity` was found under the current `/www/.../public` tree on this machine

So this skill is designed as a **portable abstraction** of the desired Clarity UX dashboard pattern, not a byte-for-byte extraction of a local plugin source file.

## Recommended UX Dashboard Sections

A polished Clarity-based UX dashboard should prioritize decision-making, not raw exports.

Recommended section order:

1. Hero / status area
2. Key UX issue cards
3. Behavior signal trend section
4. Top problematic pages
5. Recording spotlight list
6. Device / source / landing-page breakdowns
7. UX recommendations panel
8. Site configuration / data-source status

## Canonical Payload Shape

Front-end should consume one normalized payload regardless of platform.

```json
{
  "status": "ok",
  "statusLabel": "資料已同步",
  "statusMessage": "Clarity UX 行為資料已完成更新",
  "site": "https://example.com/",
  "projectId": "abc123",
  "rangeLabel": "近 7 天",
  "summaryCards": [],
  "issueCards": [],
  "charts": {},
  "problemPages": [],
  "recordings": [],
  "segments": {},
  "recommendations": [],
  "alerts": []
}
```

Suggested normalized structure:

```json
{
  "status": "ok",
  "statusLabel": "資料已同步",
  "statusMessage": "Clarity UX 行為資料已完成更新",
  "site": "https://example.com/",
  "projectId": "clarity-project-demo",
  "rangeLabel": "近 7 天",
  "summaryCards": [
    {"label": "總工作階段", "value": "12,840", "sub": "Clarity sessions"},
    {"label": "錄影數", "value": "1,248", "sub": "Available recordings"},
    {"label": "平均捲動深度", "value": "62%", "sub": "Average scroll depth"},
    {"label": "平均活躍時間", "value": "01:42", "sub": "Avg active time"}
  ],
  "issueCards": [
    {"label": "Rage Clicks", "value": "214", "tone": "warn", "hint": "CTA 區塊與按鈕疑似卡住或預期落差"},
    {"label": "Dead Clicks", "value": "183", "tone": "warn", "hint": "元素看起來可點，但沒有互動結果"},
    {"label": "Quick Backs", "value": "91", "tone": "bad", "hint": "落地頁內容與搜尋/廣告期待不一致"},
    {"label": "Excessive Scroll", "value": "67", "tone": "neutral", "hint": "重要 CTA 可能埋太深"}
  ],
  "charts": {
    "labels": ["07/01", "07/02", "07/03", "07/04", "07/05", "07/06", "07/07"],
    "sessions": [1820, 1910, 1882, 2011, 2140, 2092, 2204],
    "rageClicks": [24, 30, 28, 33, 35, 29, 35],
    "deadClicks": [19, 22, 21, 26, 30, 29, 36],
    "quickBacks": [10, 14, 13, 12, 16, 11, 15],
    "scrollDepth": [58, 60, 61, 59, 63, 64, 62]
  },
  "problemPages": [
    {"path": "/preorder-online-class/", "sessions": "822", "rageClicks": "62", "deadClicks": "38", "quickBacks": "21", "note": "首屏 CTA 可再上移、價格說明不足"},
    {"path": "/blog/hermes-agent-guide/", "sessions": "614", "rageClicks": "41", "deadClicks": "29", "quickBacks": "18", "note": "目錄與下載入口辨識度不足"}
  ],
  "recordings": [
    {"title": "首頁訪客卡在首屏 CTA 附近", "url": "https://clarity.microsoft.com/...", "duration": "02:14", "device": "mobile", "country": "Taiwan", "signal": "rage_click"},
    {"title": "課程頁滑動很深但未點擊購買", "url": "https://clarity.microsoft.com/...", "duration": "03:02", "device": "mobile", "country": "Taiwan", "signal": "excessive_scroll"}
  ],
  "segments": {
    "devices": [{"label": "mobile", "value": 74}, {"label": "desktop", "value": 22}, {"label": "tablet", "value": 4}],
    "sources": [{"label": "google / organic", "value": 42}, {"label": "direct", "value": 27}, {"label": "facebook / paid", "value": 18}],
    "landingPages": [{"label": "/", "value": 31}, {"label": "/preorder-online-class/", "value": 22}, {"label": "/blog/hermes-agent-guide/", "value": 14}]
  },
  "recommendations": [
    {"priority": "high", "title": "把首屏主要 CTA 上移並提高對比", "detail": "課程頁出現偏高 dead click / rage click，推測使用者看見按鈕樣式相近元素但點不到真正行動點。"},
    {"priority": "high", "title": "縮短首屏到關鍵利益點的距離", "detail": "Quick back 與 excessive scroll 同時偏高，建議在首屏直接交代課程價值與適合對象。"},
    {"priority": "medium", "title": "新增 FAQ / 信任訊號區塊", "detail": "使用者停留後未轉換，可能仍缺價格、方案、保障與真實案例說明。"}
  ],
  "alerts": [
    {"tone": "warn", "message": "本頁資料需透過後端代理 Clarity API；請勿將私鑰直接放進前端。"}
  ]
}
```

## Data Collection Pattern

### Step 1 — Collect raw Clarity behavior data server-side

Backend may use:

- Clarity API / export endpoints if available in the user environment
- scheduled export jobs
- internal bridge plugin / proxy endpoint
- manual ingestion into a normalized store

Collect at least:

1. sessions / recordings count
2. behavior signal counts (rage, dead, quick back, excessive scroll)
3. page-level issue summaries
4. device/source/landing-page distributions
5. selected recording examples
6. UX recommendation inputs

Completion criteria:
- [ ] backend obtains all required sections or returns explicit empty-state fallbacks
- [ ] no Clarity secret is exposed to the browser

### Step 2 — Normalize into one front-end payload

Rules:

- all cards should already be display-ready
- chart arrays must align by index
- recommendations must be human-readable, not raw flags only
- problematic pages should include both metric counts and short diagnosis notes
- recordings should include title, device, duration, and deeplink URL when available

Completion criteria:
- [ ] one JSON schema works for all front-end renderers
- [ ] front-end never parses raw vendor-specific response shapes

### Step 3 — Render in a platform-agnostic UI shell

Use the same renderer for:

- plain HTML page
- WordPress admin page
- WordPress front-end section
- protected client report portal

Completion criteria:
- [ ] the UI shell can be dropped into non-WordPress HTML
- [ ] only the API endpoint changes between environments

## Recommended General HTML Architecture

### Plain HTML version

```text
index.html
  ↓ fetch('/api/clarity-ux-insights')
backend proxy
  ↓
Clarity data source / export / bridge
```

### WordPress admin version

- store site / project / token config in WordPress options
- call backend PHP fetchers
- print normalized JSON into admin page
- reuse the same renderer as the HTML version

### WordPress front-end version

- create shortcode or page template
- fetch a protected normalized payload
- lazy-load charts and recording lists

## UI Style Guidance

Aim for a premium UX-ops dashboard style:

- deep navy / indigo / cyan palette
- large KPI cards with soft gradients
- separate "problem pages" and "recommendations" panels
- list cards with strong scanning hierarchy
- pills for severity (`high`, `medium`, `info`)
- device/source split shown as clean percentages, not raw debug tables
- mobile-first spacing and readable type scale

## Common Pitfalls

1. **Exposing tokens in front-end JS**  
   Always proxy through your own backend.

2. **Showing raw issue counts without diagnosis**  
   UX dashboards should explain what the counts imply.

3. **Treating recordings as the whole dashboard**  
   Users need page-level patterns and prioritized recommendations, not just isolated videos.

4. **Making recommendations too generic**  
   Tie each suggestion to a page, signal, or segment.

5. **Locking the renderer to WordPress**  
   Keep HTML/CSS/JS reusable so other sites can use the same front-end shell.

6. **Assuming live Clarity API shapes stay stable**  
   Normalize server-side and shield the front-end from provider changes.

## Verification Checklist

- [ ] dashboard works in a general HTML page
- [ ] credentials stay server-side
- [ ] summary cards render correctly
- [ ] issue cards render with severity styling
- [ ] daily trend chart arrays align
- [ ] top problematic pages include notes, not only counts
- [ ] recordings include valid deeplink URLs when available
- [ ] recommendations are tied to observed issues
- [ ] empty or partial data still renders a usable page
- [ ] the same payload shape can be reused in WordPress and plain HTML versions
- [ ] refresh script can rebuild `data/dashboard-cache.json`
- [ ] a daily 08:00 scheduler exists (cron or GitHub Actions)

## Daily Auto-Refresh Pattern

When the user asks for daily auto-update, prefer this pattern:

1. add a refresh script such as `refresh-dashboard.php`
2. normalize the latest payload into `data/dashboard-cache.json`
3. schedule it for **08:00 Asia/Taipei** via cron or GitHub Actions
4. let `api.php` or the front-end read the refreshed cache
5. keep tokens server-side only

Completion criteria:
- [ ] refresh script exists
- [ ] cache file exists and is writable
- [ ] daily 08:00 scheduler exists
- [ ] front-end reads refreshed data

## One-Shot Recipes

### Build a static prototype first

1. create a mock payload file
2. build a polished HTML dashboard shell
3. verify mobile stacking and card hierarchy
4. later swap mock payload for a live backend endpoint

### Given a key/project, generate a pretty standalone HTML dashboard

1. collect the user's site URL, project ID, and backend endpoint path
2. keep the key/token on the server side only
3. return normalized JSON matching `templates/api-response.schema.json`
4. clone `templates/standalone-clarity-dashboard.html`
5. replace `{{DASHBOARD_TITLE}}`, `{{API_ENDPOINT}}`, and `{{SITE_LABEL}}`
6. verify the page renders summary cards, issue cards, problem pages, recordings, and recommendations

Completion criteria:
- [ ] resulting artifact is a standalone HTML file
- [ ] HTML reads data from a backend endpoint, not embedded secrets
- [ ] UX recommendations render in a premium dashboard layout

### Turn an internal Clarity admin page into a reusable client-facing report

1. identify raw data sections
2. normalize them into one payload
3. separate UI shell from CMS/backend
4. host the dashboard in plain HTML or inside WordPress
5. keep token/key handling on the server only
