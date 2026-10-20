<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Drug Stock Alert System</title>
  @php
    $appUrlPath = parse_url(config('app.url'), PHP_URL_PATH) ?: '/';
    $basePath = rtrim($appUrlPath, '/');
    $basePath = $basePath === '' ? '/' : $basePath . '/';
  @endphp
  <script>
    window.__APP_BASE_PATH__ = @json($basePath);
  </script>
  <style>
    .ds-icon-chip { display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 9px; font-size: 15px; line-height: 1; flex: 0 0 auto; }
    .ds-icon-chip--blue { background: rgba(59, 130, 246, 0.16); color: #2563eb; }
    .ds-icon-chip--green { background: rgba(16, 185, 129, 0.16); color: #059669; }
    .ds-icon-chip--amber { background: rgba(245, 158, 11, 0.16); color: #d97706; }
    .ds-icon-chip--red { background: rgba(239, 68, 68, 0.16); color: #dc2626; }
    .ds-icon-chip--violet { background: rgba(139, 92, 246, 0.16); color: #7c3aed; }
    .ds-icon-chip--teal { background: rgba(20, 184, 166, 0.16); color: #0f766e; }
    .ds-icon-chip--slate { background: rgba(100, 116, 139, 0.16); color: #475569; }
    .v-navigation-drawer .ds-nav-list .v-list-item .v-avatar { box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.55); }
    .v-navigation-drawer .ds-nav-list .v-list-item:nth-child(1) .v-avatar { background: linear-gradient(180deg, rgba(239, 246, 255, 0.98), rgba(191, 219, 254, 0.92)) !important; color: #2563eb !important; }
    .v-navigation-drawer .ds-nav-list .v-list-item:nth-child(2) .v-avatar { background: linear-gradient(180deg, rgba(240, 253, 244, 0.98), rgba(187, 247, 208, 0.92)) !important; color: #16a34a !important; }
    .v-navigation-drawer .ds-nav-list .v-list-item:nth-child(3) .v-avatar { background: linear-gradient(180deg, rgba(238, 242, 255, 0.98), rgba(199, 210, 254, 0.92)) !important; color: #4f46e5 !important; }
    .v-navigation-drawer .ds-nav-list .v-list-item:nth-child(4) .v-avatar { background: linear-gradient(180deg, rgba(255, 251, 235, 0.98), rgba(254, 240, 138, 0.92)) !important; color: #d97706 !important; }
    .v-navigation-drawer .ds-nav-list .v-list-item:nth-child(5) .v-avatar { background: linear-gradient(180deg, rgba(255, 247, 237, 0.98), rgba(253, 186, 116, 0.92)) !important; color: #f97316 !important; }
    .v-navigation-drawer .ds-nav-list .v-list-item:nth-child(6) .v-avatar { background: linear-gradient(180deg, rgba(245, 243, 255, 0.98), rgba(221, 214, 254, 0.92)) !important; color: #a855f7 !important; }
    .v-navigation-drawer .ds-nav-list .v-list-item:nth-child(7) .v-avatar { background: linear-gradient(180deg, rgba(240, 253, 250, 0.98), rgba(153, 246, 228, 0.92)) !important; color: #0f766e !important; }
    .v-navigation-drawer .ds-nav-list .v-list-item:nth-child(8) .v-avatar { background: linear-gradient(180deg, rgba(236, 254, 255, 0.98), rgba(165, 243, 252, 0.92)) !important; color: #0891b2 !important; }
    .v-navigation-drawer .ds-nav-list .v-list-item:nth-child(9) .v-avatar { background: linear-gradient(180deg, rgba(248, 250, 252, 0.98), rgba(226, 232, 240, 0.92)) !important; color: #475569 !important; }
    .v-navigation-drawer .ds-nav-list .v-list-item:nth-child(10) .v-avatar { background: linear-gradient(180deg, rgba(255, 241, 242, 0.98), rgba(251, 207, 232, 0.92)) !important; color: #e11d48 !important; }
    .page-hero, .ds-settings-hero, .ds-roles-hero, .ds-users-hero { background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96)); border: 1px solid rgba(148, 163, 184, 0.16); box-shadow: 0 14px 40px rgba(15, 23, 42, 0.06); }
    .page-hero .v-avatar, .ds-settings-hero .v-avatar, .ds-roles-hero .v-avatar, .ds-users-hero .v-avatar { box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.55); }
    .page-hero .v-btn, .ds-settings-hero .v-btn, .ds-roles-hero .v-btn, .ds-users-hero .v-btn { border-radius: 14px; }
    body.ds-sidebar-rail .v-navigation-drawer { width: 88px !important; max-width: 88px !important; }
    body.ds-sidebar-rail .v-navigation-drawer .v-list-item-title,
    body.ds-sidebar-rail .v-navigation-drawer .v-list-item-subtitle,
    body.ds-sidebar-rail .v-navigation-drawer [data-ds-sidebar-brand] .ds-brand-copy,
    body.ds-sidebar-rail .v-navigation-drawer [data-ds-sidebar-footer] .v-card,
    body.ds-sidebar-rail .v-navigation-drawer [data-ds-settings-link] span:last-child { display: none !important; }
    body.ds-sidebar-rail .v-navigation-drawer .v-list { padding-left: 8px !important; padding-right: 8px !important; }
    body.ds-sidebar-rail .v-navigation-drawer .v-list-item { justify-content: center !important; padding-inline: 0 !important; }
    body.ds-sidebar-rail .v-navigation-drawer .v-list-item__prepend { margin-inline-end: 0 !important; }
    .ds-summary-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin: 16px 0; }
    .ds-summary-card { padding: 14px 16px; border: 1px solid rgba(148, 163, 184, 0.18); border-radius: 16px; background: rgba(255, 255, 255, 0.78); }
    .ds-summary-card__label { font-size: 12px; color: #64748b; margin-bottom: 6px; }
    .ds-summary-card__value { font-size: 28px; line-height: 1; font-weight: 800; color: #0f172a; }
    .ds-mini-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
    .ds-mini-card { padding: 14px 16px; border-radius: 14px; border: 1px solid rgba(148, 163, 184, 0.18); background: rgba(248, 250, 252, 0.9); }
    .ds-mini-card__label { font-size: 12px; color: #64748b; margin-bottom: 8px; }
    .ds-mini-card__value { font-size: 17px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
    .ds-mini-card__meta { font-size: 12px; color: #475569; }
    .ds-panel--slate { background: linear-gradient(180deg, rgba(241, 245, 249, 0.96), rgba(248, 250, 252, 0.96)); }
    .ds-panel--blue { background: linear-gradient(180deg, rgba(239, 246, 255, 0.98), rgba(255, 255, 255, 0.96)); border-color: rgba(96, 165, 250, 0.18); }
    .ds-panel--amber { background: linear-gradient(180deg, rgba(255, 247, 237, 0.98), rgba(255, 255, 255, 0.96)); border-color: rgba(245, 158, 11, 0.18); }
    .ds-panel--teal { background: linear-gradient(180deg, rgba(240, 253, 250, 0.98), rgba(255, 255, 255, 0.96)); border-color: rgba(20, 184, 166, 0.18); }
    .ds-panel--violet { background: linear-gradient(180deg, rgba(245, 243, 255, 0.98), rgba(255, 255, 255, 0.96)); border-color: rgba(139, 92, 246, 0.18); }
    .ds-panel__head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
    .ds-panel__title { display: flex; align-items: center; gap: 10px; font-size: 18px; font-weight: 800; color: #0f172a; }
    .ds-panel__title .ds-icon-chip { width: 30px; height: 30px; border-radius: 10px; font-size: 16px; }
    .ds-panel__note { color: #475569; line-height: 1.6; font-size: 14px; margin-bottom: 14px; }
    .ds-settings-shell { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(320px, 0.7fr); gap: 16px; align-items: start; }
    .ds-settings-stack { display: grid; gap: 16px; min-width: 0; }
    .ds-settings-side { position: sticky; top: 20px; display: grid; gap: 16px; }
    .ds-settings-hero { padding: 18px 20px; border-radius: 22px; background: radial-gradient(circle at top right, rgba(45, 212, 191, 0.18), transparent 30%), linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(240, 253, 250, 0.9)); border: 1px solid rgba(148, 163, 184, 0.16); box-shadow: 0 16px 40px rgba(15, 23, 42, 0.06); }
    .ds-settings-hero__top { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; }
    .ds-settings-hero__eyebrow { font-size: 12px; letter-spacing: .08em; text-transform: uppercase; color: #0f766e; font-weight: 700; }
    .ds-settings-hero__title { margin: 6px 0 6px; font-size: clamp(24px, 3vw, 34px); line-height: 1.1; color: #0f172a; font-weight: 800; }
    .ds-settings-hero__subtitle { margin: 0; color: #475569; line-height: 1.6; max-width: 64ch; }
    .ds-settings-kpis { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-top: 18px; }
    .ds-settings-kpi { padding: 14px 16px; border-radius: 18px; background: rgba(255, 255, 255, 0.9); border: 1px solid rgba(148, 163, 184, 0.16); }
    .ds-settings-kpi__label { font-size: 12px; color: #64748b; margin-bottom: 6px; }
    .ds-settings-kpi__value { font-size: 20px; font-weight: 800; color: #0f172a; line-height: 1.1; }
    .ds-settings-kpi__meta { font-size: 12px; color: #64748b; margin-top: 4px; }
    .ds-settings-panel { padding: 18px 20px; border-radius: 22px; background: rgba(255, 255, 255, 0.94); border: 1px solid rgba(148, 163, 184, 0.16); box-shadow: 0 12px 30px rgba(15, 23, 42, 0.04); }
    .ds-settings-panel__head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
    .ds-settings-panel__title { display: flex; align-items: center; gap: 10px; font-size: 18px; font-weight: 800; color: #0f172a; }
    .ds-settings-panel__title span { font-size: 22px; }
    .ds-settings-panel__note { color: #475569; line-height: 1.6; font-size: 14px; margin-bottom: 14px; }
    .ds-settings-tabs { display: flex; flex-wrap: wrap; gap: 10px; padding: 10px; background: rgba(248, 250, 252, 0.95); border-radius: 18px; border: 1px solid rgba(148, 163, 184, 0.12); }
    .ds-settings-tabs .ds-tab { border: 0; border-radius: 999px; padding: 10px 14px; background: transparent; color: #475569; font-weight: 700; }
    .ds-settings-tabs .ds-tab.is-active { background: #0f172a; color: #fff; box-shadow: 0 8px 16px rgba(15, 23, 42, 0.12); }
    .ds-settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .ds-settings-grid .ds-field--wide { grid-column: 1 / -1; }
    .ds-settings-cta { display: grid; gap: 12px; padding: 18px 20px; border-radius: 22px; background: linear-gradient(180deg, rgba(239, 246, 255, 0.98), rgba(255, 255, 255, 0.96)); border: 1px solid rgba(96, 165, 250, 0.18); }
    .ds-settings-cta__title { font-size: 15px; font-weight: 800; color: #0f172a; }
    .ds-settings-cta__desc { color: #475569; line-height: 1.6; font-size: 13px; }
    .ds-settings-cta__actions { display: flex; flex-wrap: wrap; gap: 10px; }
    .ds-settings-badge-row { display: flex; flex-wrap: wrap; gap: 8px; }
    .ds-settings-list { display: grid; gap: 10px; }
    .ds-settings-list__item { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 14px; border-radius: 16px; background: rgba(248, 250, 252, 0.95); border: 1px solid rgba(148, 163, 184, 0.12); }
    .ds-settings-list__label { font-size: 13px; color: #64748b; }
    .ds-settings-list__value { font-size: 14px; font-weight: 700; color: #0f172a; text-align: right; }
    .ds-warehouse-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 14px 16px; border-radius: 18px; background: linear-gradient(90deg, rgba(239, 246, 255, 0.92) 0%, rgba(255, 255, 255, 0.98) 100%); border: 1px solid rgba(96, 165, 250, 0.20); box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05); }
    .ds-warehouse-toolbar__left { display: flex; flex-direction: column; gap: 4px; }
    .ds-warehouse-toolbar__eyebrow { font-size: 12px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #0369a1; }
    .ds-warehouse-toolbar__title { font-size: 16px; font-weight: 800; color: #0f172a; }
    .ds-warehouse-toolbar__desc { font-size: 13px; color: #475569; line-height: 1.5; }
    .ds-warehouse-toolbar__actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 8px; }
    .ds-warehouse-toolbar__actions .ds-chip { border: 1px solid rgba(148, 163, 184, 0.24); background: rgba(255, 255, 255, 0.9); box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04); }
    .ds-warehouse-toolbar__stats { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
    .ds-warehouse-toolbar__stats .ds-badge { background: rgba(255, 255, 255, 0.92); }
    .metric-card { position: relative; overflow: hidden; border: 1px solid rgba(148, 163, 184, 0.16) !important; border-radius: 22px !important; background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.98)) !important; box-shadow: 0 14px 34px rgba(15, 23, 42, 0.06) !important; }
    .metric-card::before { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, rgba(255, 255, 255, 0.66), transparent 42%); pointer-events: none; }
    .metric-card.metric-primary { background: linear-gradient(180deg, rgba(239, 246, 255, 0.98), rgba(255, 255, 255, 0.96)) !important; }
    .metric-card.metric-success { background: linear-gradient(180deg, rgba(240, 253, 244, 0.98), rgba(255, 255, 255, 0.96)) !important; }
    .metric-card.metric-warning { background: linear-gradient(180deg, rgba(255, 247, 237, 0.98), rgba(255, 255, 255, 0.96)) !important; }
    .metric-card .v-card-text { position: relative; z-index: 1; }
    .metric-card .text-overline { letter-spacing: .08em; font-weight: 700; }
    .metric-card .text-h3, .metric-card .text-h4, .metric-card .text-h5 { line-height: 1; }
    .metric-card .text-h3, .metric-card .text-h4 { font-size: clamp(2rem, 4vw, 2.9rem) !important; }
    .metric-card .text-body-2 { color: #475569 !important; }
    .filter-card { border: 1px solid rgba(148, 163, 184, 0.16) !important; border-radius: 22px !important; background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96)) !important; box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05) !important; }
    .filter-card .v-card-text { padding: 18px 20px !important; }
    .filter-card .v-field { border-radius: 16px; }
    .filter-card .v-btn { border-radius: 999px; }
    .warehouse-table { border: 1px solid rgba(148, 163, 184, 0.16) !important; border-radius: 22px !important; overflow: hidden; box-shadow: 0 14px 34px rgba(15, 23, 42, 0.05) !important; }
    .warehouse-table .v-table__wrapper { background: rgba(255, 255, 255, 0.98); }
    .warehouse-table table { border-collapse: separate; border-spacing: 0; }
    .warehouse-table thead th { background: linear-gradient(180deg, rgba(241, 245, 249, 0.98), rgba(248, 250, 252, 0.95)); color: #334155; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; border-bottom: 1px solid rgba(148, 163, 184, 0.16); }
    .warehouse-table tbody td { border-bottom: 1px solid rgba(226, 232, 240, 0.9); }
    .warehouse-table tbody tr:hover { background: rgba(239, 246, 255, 0.75); }
    .warehouse-table .v-data-table-footer { background: rgba(248, 250, 252, 0.95); border-top: 1px solid rgba(148, 163, 184, 0.14); }
    .ds-modal-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.56); display: flex; align-items: center; justify-content: center; padding: 20px; z-index: 60; }
    .ds-modal { width: min(720px, 100%); border-radius: 24px; background: #fff; border: 1px solid rgba(148, 163, 184, 0.18); box-shadow: 0 30px 80px rgba(15, 23, 42, 0.22); overflow: hidden; }
    .ds-modal__head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 18px 20px; background: linear-gradient(135deg, rgba(239, 246, 255, 0.95), rgba(255, 255, 255, 1)); border-bottom: 1px solid rgba(148, 163, 184, 0.14); }
    .ds-modal__title { font-size: 18px; font-weight: 800; color: #0f172a; }
    .ds-modal__sub { margin-top: 6px; color: #64748b; font-size: 13px; line-height: 1.5; }
    .ds-modal__body { padding: 18px 20px 10px; }
    .ds-modal__foot { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 20px 20px; }
    .ds-role-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px; margin-top: 14px; }
    .ds-role-pick { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 14px; border: 1px solid rgba(148, 163, 184, 0.14); background: rgba(248, 250, 252, 0.96); }
    .ds-user-table { width: 100%; border-collapse: collapse; }
    .ds-user-table th, .ds-user-table td { padding: 14px 12px; border-bottom: 1px solid rgba(148, 163, 184, 0.14); vertical-align: top; }
    .ds-user-table th { text-align: left; color: #475569; font-size: 13px; font-weight: 800; white-space: nowrap; }
    .ds-user-table td { color: #0f172a; }
    .ds-user-name { font-weight: 800; color: #0f172a; }
    .ds-user-meta { font-size: 12px; color: #64748b; margin-top: 4px; }
    .ds-user-roles { display: flex; flex-wrap: wrap; gap: 6px; }
    .ds-user-roles .ds-badge { margin: 0; }
    .ds-user-split { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(300px, 0.7fr); gap: 16px; align-items: start; }
    .ds-user-actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-end; }
    @media (max-width: 768px) { .ds-summary-grid { grid-template-columns: 1fr; } .ds-settings-shell { grid-template-columns: 1fr; } .ds-settings-side { position: static; } .ds-settings-grid { grid-template-columns: 1fr; } .ds-settings-kpis { grid-template-columns: 1fr; } .ds-user-split { grid-template-columns: 1fr; } .ds-warehouse-toolbar { flex-direction: column; align-items: stretch; } .ds-warehouse-toolbar__actions { justify-content: flex-start; } }
    .shell-bar-layout { display: flex; flex-direction: column; gap: 6px; width: 100%; min-width: 0; }
    .shell-bar-row-top { display: flex !important; align-items: center; justify-content: space-between; gap: 12px; width: 100%; min-width: 0; flex-wrap: nowrap; }
    .shell-bar-title-group { display: flex !important; align-items: center; gap: 12px; min-width: 0; flex: 1 1 auto; width: auto !important; }
    .shell-bar-title-block-top { display: flex; flex-direction: column; min-width: 0; }
    .shell-bar-hospital { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .shell-bar-utilities { display: flex !important; align-items: center; justify-content: flex-end; gap: 12px; margin-left: auto; flex: 0 0 auto; width: auto !important; }
    .shell-bar-utilities > div { width: auto !important; }
    @media (max-width: 768px) { .shell-bar-row-top { flex-wrap: wrap; } .shell-bar-utilities { margin-left: 0; width: 100%; justify-content: flex-start; } }
  </style>
  <script>
    (function () {
      const applyShellBarLayout = () => {
        const row = document.querySelector('.shell-bar-row-top')
        const titleGroup = document.querySelector('.shell-bar-title-group')
        const titleBlock = document.querySelector('.shell-bar-title-block-top')
        const pageLabel = document.querySelector('.shell-bar-page')
        const utilities = document.querySelector('.shell-bar-utilities')
        const utilitiesInner = utilities?.firstElementChild

        if (row) {
          row.style.display = 'flex'
          row.style.flexDirection = 'row'
          row.style.flexWrap = 'nowrap'
          row.style.alignItems = 'center'
          row.style.justifyContent = 'space-between'
          row.style.gap = '12px'
          row.style.minWidth = '0'
        }
        if (titleGroup) {
          titleGroup.style.display = 'flex'
          titleGroup.style.flexDirection = 'row'
          titleGroup.style.alignItems = 'center'
          titleGroup.style.gap = '12px'
          titleGroup.style.flex = '1 1 auto'
          titleGroup.style.minWidth = '0'
          titleGroup.style.width = 'auto'
        }
        if (titleBlock) {
          titleBlock.style.display = 'flex'
          titleBlock.style.flexDirection = 'column'
          titleBlock.style.minWidth = '0'
        }
        if (pageLabel) {
          pageLabel.style.display = 'none'
        }
        if (utilities) {
          utilities.style.display = 'flex'
          utilities.style.alignItems = 'center'
          utilities.style.justifyContent = 'flex-end'
          utilities.style.gap = '12px'
          utilities.style.marginLeft = 'auto'
          utilities.style.flex = '0 0 auto'
          utilities.style.width = 'auto'
          utilities.style.whiteSpace = 'nowrap'
        }
        if (utilitiesInner) {
          utilitiesInner.style.display = 'flex'
          utilitiesInner.style.alignItems = 'center'
          utilitiesInner.style.width = 'auto'
          utilitiesInner.style.whiteSpace = 'nowrap'
          if (utilities.lastElementChild !== utilitiesInner) {
            utilities.appendChild(utilitiesInner)
          }
        }
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyShellBarLayout, { once: true })
      } else {
        applyShellBarLayout()
      }

      window.setTimeout(applyShellBarLayout, 0)
      window.setTimeout(applyShellBarLayout, 500)
    })()
  </script>
  @vite(['resources/js/app.js'])
</head>
<body>
  <div id="app"></div>
</body>
</html>