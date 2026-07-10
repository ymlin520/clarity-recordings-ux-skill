# Microsoft Clarity UX Insights Dashboard Skill

A reusable Hermes skill and front-end demo for building a **Microsoft Clarity UX analytics dashboard** that can run in:

- plain HTML websites
- WordPress admin pages
- WordPress front-end embeds
- custom PHP / Node / Python dashboards

## Goal

Turn a Clarity-style admin page such as:

- `hostwp-clarity-recordings`

into a **portable UX analysis dashboard** that can be reused across sites by changing only the project settings / key / token / backend data source.

## Important architecture note

This project is designed so that:

- the **front-end HTML is reusable**
- the **Clarity secret/token stays server-side**
- the UI only consumes a normalized JSON payload

That means you can use the same visual dashboard on a general HTML page, not only in WordPress.

## What this repo includes

- `SKILL.md` — reusable Hermes skill
- `demo/index.html` — premium HTML dashboard mockup
- `examples/payload.example.json` — canonical payload schema
- `api.php` — JSON endpoint that serves the latest cached payload
- `refresh-dashboard.php` — refresh job that rebuilds `data/dashboard-cache.json`
- `config.example.php` — config template for mock/file/remote JSON modes
- `.github/workflows/daily-refresh.yml` — GitHub Actions scheduler at 08:00 Asia/Taipei daily
- `references/source-mapping.md` — notes on the target page and portability assumptions

## Example use cases

### 1. General HTML page

- create `/api/clarity-ux-insights`
- return normalized JSON
- reuse `demo/index.html` as the front-end shell

### 2. WordPress admin page

- store project settings and token in WordPress options
- fetch Clarity data server-side
- print payload into an admin page
- reuse the same renderer

### 3. Front-end client reporting page

- show summary cards, issue trends, problem pages, recordings, and UX suggestions
- protect the endpoint if the report is private

## Recommended data blocks

- sessions / recordings summary
- rage clicks / dead clicks / quick backs / excessive scroll
- trend chart
- top problematic pages
- sample recordings
- device/source/landing page segments
- UX recommendations

## How to preview locally

```bash
cd clarity-ux-insights-skill
python3 -m http.server 8788
```

Then open:

- `http://localhost:8788/demo/`

## Why this is not hard-locked to WordPress

The current environment could confirm the target admin URL exists behind WordPress login, but could not inspect a matching local plugin source file in this filesystem. So this repo intentionally captures the **portable product pattern** rather than a one-site-only implementation.

## 直接拿來做成漂亮 HTML 的方式

這個 skill 已經附上可重用 HTML 範本：

- `templates/standalone-clarity-dashboard.html`

使用方式：

1. 準備一個後端 API，例如：`/api/clarity-ux-insights`
2. 後端用你的 Clarity key / token / project 設定去抓資料
3. 後端回傳符合 `templates/api-response.schema.json` 的 JSON
4. 把 HTML 範本中的：
   - `{{DASHBOARD_TITLE}}`
   - `{{API_ENDPOINT}}`
   - `{{SITE_LABEL}}`
   換成你的實際值
5. 上線後，這頁就是可直接顯示 UX 分析建議的漂亮 HTML

## PHP 通用版已內建

我已經補上：

- `api.php`
- `config.example.php`
- `build-dashboard.php`
- `dist/dashboard.html`（可建置輸出）

### 你要的 1 + 2 + 3 現在怎麼實現

1. **直接可上線的漂亮 HTML**  
   用 `build-dashboard.php` 自動產生 `dist/dashboard.html`

2. **只要填 token / project / endpoint 就能接資料**  
   在 `config.php` 填：
   - `project_id`
   - `remote_json_url`
   - `remote_bearer_token` 或 `remote_headers`
   - `public_api_endpoint`

3. **可直接打包 zip / push GitHub**  
   這個 repo 已整理成完整 skill 專案結構

### 支援模式

| mode | 用途 |
|---|---|
| `mock` | 直接讀 `examples/payload.example.json`，最快預覽 |
| `file` | 讀你自己整理好的本機 JSON |
| `remote_json` | 打你自己的 bridge API / 後端整理 API |

### 最快開始方式

1. 複製設定檔：
   ```bash
   cp config.example.php config.php
   ```
2. 在 `config.php` 先確認這幾個欄位：
   ```php
   'mode' => 'remote_json',
   'project_id' => '你的-project-id',
   'remote_json_url' => 'https://你的網站/api/clarity-bridge',
   'remote_bearer_token' => '你的token',
   'public_api_endpoint' => '/api.php',
   'public_site_label' => 'https://你的網站/'
   ```
3. 若你還沒 bridge，也可以先用 `mock` 模式確認畫面
4. 產生正式 HTML：
   ```bash
   php build-dashboard.php
   ```
5. 會輸出：
   - `dist/dashboard.html`
6. 前端 HTML 可直接用：
   - `dist/dashboard.html`（正式部署版）
   - `demo/index.html`（示範版）
   - `templates/standalone-clarity-dashboard.html`（模板版）

### 在一般主機上使用

若你要做成一般 HTML + PHP：

- `api.php` 當 JSON endpoint
- `standalone-clarity-dashboard.html` 當漂亮前端
- 把 `{{API_ENDPOINT}}` 改成 `/api.php`

這樣就能做到：

> 給 project / key / token（留在後端）→ 顯示漂亮的 UX HTML dashboard

## Suggested next step

如果你之後拿到可用的 Clarity bridge / export endpoint，下一步只要：

1. 在 `config.php` 設定 `remote_json_url`
2. 若需要認證，填 `remote_bearer_token` 或 `remote_headers`
3. 讓前端 HTML 指向 `api.php`

Then wire the front-end to real Clarity-derived JSON.

## 每日早上 8 點自動更新

這個 repo 現在已內建：

- `refresh-dashboard.php`
- `data/dashboard-cache.json`
- `.github/workflows/daily-refresh.yml`

### GitHub Actions 排程

排程時間：

- `0 0 * * *`（UTC）
- 等於 **Asia/Taipei 每天早上 8:00**

每天會自動執行：

```bash
php refresh-dashboard.php
```

並更新：

- `data/dashboard-cache.json`

### 一般 PHP 主機 cron

若你部署在一般主機，也可以直接設：

```bash
0 8 * * * /usr/bin/php /path/to/clarity-ux-insights-skill/refresh-dashboard.php
```

### 前端讀取位置

- `demo/index.html` → `../data/dashboard-cache.json`
- `api.php` → 回傳最新 cache JSON
