<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EloquentLens</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <style>
        /* ═══════════════════════════════════════════════════════════
           EloquentLens — Dark Dashboard Theme
           ═══════════════════════════════════════════════════════════ */

        :root {
            --bg-primary: #09090f;
            --bg-secondary: #0d0d14;
            --bg-tertiary: #111118;
            --bg-elevated: #1a1a2e;
            --border: #1e1e2e;
            --border-hover: #2e2e44;
            --text-primary: #e2e8f0;
            --text-secondary: #cbd5e1;
            --text-muted: #9aa6bb;
            --text-dim: #7886a0;
            --accent: #7c3aed;
            --accent-light: #a78bfa;
            --accent-bg: rgba(124, 58, 237, 0.08);
            --accent-border: rgba(124, 58, 237, 0.25);
            --danger: #ef4444;
            --warning: #f59e0b;
            --success: #22c55e;
            --info: #3b82f6;
            --rel-has-many: #f97316;
            --rel-has-one: #22c55e;
            --rel-belongs-to: #3b82f6;
            --rel-belongs-to-many: #a855f7;
            --rel-morph: #ec4899;
            --rel-through: #06b6d4;
            --mono: 'JetBrains Mono', 'Fira Code', ui-monospace, monospace;
            --sans: 'Inter', -apple-system, sans-serif;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        [x-cloak] { display: none !important; }

        :focus { outline: none; }
        button:focus-visible,
        input:focus-visible,
        select:focus-visible,
        [role="button"]:focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
        }

        body {
            font-family: var(--mono);
            background: var(--bg-primary);
            color: var(--text-primary);
            overflow: hidden;
            height: 100vh;
        }

        /* ── Loading Screen ─────────────────────────────────────── */
        .lens-loader {
            position: fixed;
            inset: 0;
            background: var(--bg-primary);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .lens-loader-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
            animation: loaderFadeIn 0.3s ease;
        }

        @keyframes loaderFadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .lens-loader-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--accent), #a855f7);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--mono);
            position: relative;
        }

        .lens-loader-icon-text {
            font-size: 24px;
            font-weight: 800;
            color: white;
            z-index: 1;
        }

        .lens-loader-ring {
            position: absolute;
            inset: -6px;
            border: 2px solid transparent;
            border-top-color: var(--accent);
            border-right-color: rgba(124, 58, 237, 0.3);
            border-radius: 20px;
            animation: spin 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .lens-loader-text {
            font-family: var(--mono);
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.03em;
        }

        .lens-loader-text span { color: var(--accent); }

        .lens-loader-steps {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 280px;
        }

        .lens-loader-step {
            font-family: var(--mono);
            font-size: 11px;
            color: var(--text-dim);
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 0;
            transition: color 0.2s;
        }

        .lens-loader-step.active {
            color: var(--text-primary);
        }

        .lens-loader-step.done {
            color: var(--success);
        }

        .lens-loader-step-icon {
            width: 14px;
            text-align: center;
            flex-shrink: 0;
            font-weight: 700;
        }

        .lens-loader-step.active .lens-loader-step-icon {
            animation: pulse 0.8s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .lens-loader-tip {
            font-family: var(--mono);
            font-size: 10px;
            color: var(--text-dim);
            font-style: italic;
            max-width: 320px;
            text-align: center;
            line-height: 1.5;
            margin-top: 8px;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--border-hover); }
        ::-webkit-scrollbar-corner { background: transparent; }

        /* ── Top Navigation Bar ───────────────────────────────── */
        .topbar {
            min-height: 56px;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 20px;
            flex-shrink: 0;
            flex-wrap: wrap;
            gap: 10px;
            z-index: 50;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logo-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--accent), #a855f7);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 800;
            color: white;
        }

        .logo-text {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: -0.03em;
        }

        .logo-text span { color: var(--accent); }

        .divider-v {
            width: 1px;
            height: 20px;
            background: var(--border);
        }

        .stats-row {
            display: flex;
            gap: 16px;
        }

        .stat-item {
            display: flex;
            align-items: baseline;
            gap: 5px;
        }

        .stat-value {
            font-size: 14px;
            font-weight: 700;
        }

        .stat-label {
            font-size: 9px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        /* ── Search ───────────────────────────────────────────── */
        .search-box {
            background: var(--bg-tertiary);
            border: 1px solid var(--border);
            border-radius: 8px;
            display: flex;
            align-items: center;
            padding: 0 12px;
            height: 34px;
            transition: border-color 0.2s;
        }

        .search-box:focus-within { border-color: var(--accent); }

        .search-icon {
            color: var(--text-muted);
            font-size: 13px;
            margin-right: 8px;
        }

        .search-input {
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 12px;
            font-family: var(--mono);
            outline: none;
            width: 160px;
        }

        .search-input::placeholder { color: var(--text-dim); }

        /* ── Filter Select ────────────────────────────────────── */
        .filter-select {
            background: var(--bg-tertiary);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-primary);
            padding: 0 12px;
            height: 34px;
            font-size: 11px;
            font-family: var(--mono);
            cursor: pointer;
            transition: border-color 0.2s;
            appearance: none;
            -webkit-appearance: none;
            padding-right: 28px;
            background-image: url("data:image/svg+xml,%3Csvg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1L5 5L9 1' stroke='%23475569' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--accent);
        }

        .filter-select option {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        /* ── Buttons ──────────────────────────────────────────── */
        .btn {
            border: none;
            border-radius: 8px;
            padding: 0 14px;
            height: 34px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            font-family: var(--mono);
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-accent {
            background: var(--accent-bg);
            border: 1px solid var(--accent-border);
            color: var(--accent-light);
        }

        .btn-accent:hover {
            background: rgba(124, 58, 237, 0.15);
        }

        .btn-ghost {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-muted);
        }

        .btn-ghost:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .btn-ghost:hover:not(:disabled) {
            border-color: var(--border-hover);
            color: var(--text-secondary);
        }

        /* ── Legend Bar ────────────────────────────────────────── */
        .legend-bar {
            min-height: 36px;
            background: #0b0b12;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 6px 20px;
            gap: 18px;
            flex-shrink: 0;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .legend-line {
            width: 18px;
            height: 2px;
            border-radius: 1px;
        }

        .legend-line.dashed {
            background: none !important;
            border-top: 2px dashed;
        }

        .legend-label {
            font-size: 9px;
            color: var(--text-muted);
            letter-spacing: 0.04em;
        }

        /* ── Main Layout ──────────────────────────────────────── */
        .main-layout {
            display: flex;
            flex: 1;
            min-height: 0;
            overflow: hidden;
        }

        /* ── Sidebar ─────────────────────────────────────────── */
        .sidebar {
            width: 220px;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            overflow: hidden;
        }

        .sidebar-header {
            padding: 14px 16px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sidebar-title {
            font-size: 10px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .sidebar-count {
            font-size: 10px;
            font-weight: 600;
            color: var(--text-dim);
            background: var(--bg-tertiary);
            padding: 1px 6px;
            border-radius: 4px;
        }

        .sidebar-search {
            padding: 10px 12px 6px;
        }

        .sidebar-search-input {
            width: 100%;
            background: var(--bg-tertiary);
            border: 1px solid var(--border);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 11px;
            font-family: var(--mono);
            padding: 6px 10px;
            outline: none;
            transition: border-color 0.2s;
        }

        .sidebar-search-input:focus {
            border-color: var(--accent);
        }

        .sidebar-search-input::placeholder {
            color: var(--text-dim);
        }

        .sidebar-list {
            flex: 1;
            overflow-y: auto;
            padding: 2px 8px 12px;
        }

        .sidebar-item {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 8px;
            background: none;
            border: 1px solid transparent;
            border-radius: 6px;
            cursor: pointer;
            font-family: var(--mono);
            transition: all 0.15s;
            text-align: left;
        }

        .sidebar-item:hover {
            background: var(--bg-tertiary);
            border-color: var(--border);
        }

        .sidebar-item.active {
            background: var(--accent-bg);
            border-color: var(--accent-border);
        }

        .sidebar-item-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .sidebar-item-name {
            font-size: 11px;
            color: var(--text-secondary);
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sidebar-item.active .sidebar-item-name {
            color: var(--text-primary);
        }

        .sidebar-item-rels {
            font-size: 9px;
            color: var(--text-dim);
            flex-shrink: 0;
        }

        /* ── Graph Canvas ─────────────────────────────────────── */
        .graph-wrapper {
            flex: 1;
            position: relative;
            overflow: hidden;
        }

        .graph-canvas {
            width: 100%;
            height: 100%;
            overflow: auto;
        }

        .graph-canvas-inner {
            position: relative;
        }

        .graph-zoom-layer {
            position: relative;
            transform-origin: 0 0;
        }

        /* ── Zoom Controls ───────────────────────────────────── */
        .zoom-controls {
            position: absolute;
            bottom: 14px;
            right: 14px;
            z-index: 30;
            display: flex;
            align-items: center;
            gap: 2px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 3px 4px;
        }

        .zoom-btn {
            background: none;
            border: 1px solid transparent;
            color: var(--text-muted);
            font-size: 14px;
            font-family: var(--mono);
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s;
        }

        .zoom-btn:hover {
            color: var(--text-primary);
            border-color: var(--border);
            background: var(--bg-tertiary);
        }

        .zoom-level {
            font-size: 10px;
            font-family: var(--mono);
            color: var(--text-secondary);
            min-width: 40px;
            text-align: center;
            user-select: none;
        }

        .grid-bg {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle, var(--bg-elevated) 1px, transparent 1px);
            background-size: 24px 24px;
            opacity: 0.4;
        }

        /* ── Entity Card (ERD style) ─────────────────────────── */
        .entity-card {
            position: absolute;
            width: 260px;
            background: var(--bg-primary);
            border: 1.5px solid var(--border);
            border-radius: 10px;
            cursor: pointer;
            z-index: 10;
            transition: all 0.25s ease;
            user-select: none;
            overflow: hidden;
        }

        .entity-card:hover {
            border-color: var(--border-hover);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            z-index: 15;
        }

        .entity-card.selected {
            border-color: var(--accent);
            box-shadow: 0 0 30px rgba(124, 58, 237, 0.25), 0 8px 32px rgba(0, 0, 0, 0.4);
            z-index: 20;
        }

        .entity-card.dimmed {
            opacity: 0.15;
        }

        .entity-card.dragging {
            z-index: 50;
            transition: none;
        }

        .entity-header {
            padding: 10px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
            position: relative;
        }

        .entity-header-bar {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }

        .entity-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.02em;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            min-width: 0;
        }

        .entity-score {
            font-size: 9px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 4px;
            flex-shrink: 0;
            margin-left: 8px;
        }

        /* Columns section */
        .entity-columns {
            padding: 6px 0;
            border-bottom: 1px solid var(--border);
        }

        .entity-col-row {
            display: flex;
            align-items: center;
            padding: 2px 14px;
            font-size: 11px;
            gap: 6px;
        }

        .entity-col-icon {
            width: 14px;
            text-align: center;
            flex-shrink: 0;
            font-size: 10px;
            color: var(--text-dim);
        }

        .entity-col-name {
            color: var(--text-secondary);
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .entity-col-overflow {
            padding: 2px 14px;
            font-size: 10px;
            color: var(--text-dim);
            font-style: italic;
        }

        /* Relationships section */
        .entity-relations {
            padding: 6px 0;
        }

        .entity-rel-row {
            display: flex;
            align-items: center;
            padding: 2px 14px;
            font-size: 11px;
            gap: 6px;
        }

        .entity-rel-arrow {
            flex-shrink: 0;
            font-size: 10px;
        }

        .entity-rel-type {
            color: var(--text-dim);
            flex-shrink: 0;
            font-size: 10px;
        }

        .entity-rel-target {
            color: var(--text-secondary);
            font-weight: 600;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .entity-empty {
            padding: 4px 14px;
            font-size: 10px;
            color: var(--text-dim);
            font-style: italic;
        }

        /* ── Detail Panel ─────────────────────────────────────── */
        .detail-panel {
            width: 380px;
            height: 100%;
            background: var(--bg-secondary);
            border-left: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            overflow: hidden;
            animation: slideIn 0.25s ease;
        }

        @keyframes slideIn {
            from { transform: translateX(20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .detail-header {
            padding: 20px 24px 16px;
            border-bottom: 1px solid var(--border);
        }

        .detail-title-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
        }

        .detail-title-row > div:first-child {
            min-width: 0;
            flex: 1;
        }

        .detail-name {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.03em;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .detail-namespace {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 4px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .btn-close {
            background: none;
            border: 1px solid var(--border);
            color: var(--text-muted);
            font-size: 14px;
            cursor: pointer;
            border-radius: 6px;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .btn-close:hover {
            border-color: var(--border-hover);
            color: var(--text-secondary);
        }

        /* Complexity Meter */
        .complexity-meter {
            margin-top: 16px;
        }

        .complexity-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
        }

        .complexity-label {
            font-size: 10px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .complexity-value {
            font-size: 11px;
            font-weight: 600;
        }

        .complexity-track {
            height: 4px;
            background: var(--bg-elevated);
            border-radius: 2px;
            overflow: hidden;
        }

        .complexity-fill {
            height: 100%;
            border-radius: 2px;
            transition: width 0.5s ease;
        }

        /* Stats grid */
        .stats-grid {
            display: flex;
            gap: 10px;
            margin-top: 14px;
        }

        .stat-card {
            flex: 1;
            background: var(--bg-tertiary);
            border-radius: 8px;
            padding: 8px 10px;
            border: 1px solid var(--border);
        }

        .stat-card-value {
            font-size: 14px;
            font-weight: 700;
        }

        .stat-card-label {
            font-size: 9px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 2px;
        }

        /* Tabs */
        .tabs {
            display: flex;
            border-bottom: 1px solid var(--border);
            padding: 0 24px;
        }

        .tab-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 11px;
            font-weight: 600;
            padding: 10px 0;
            margin-right: 20px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            font-family: var(--mono);
            letter-spacing: 0.02em;
            transition: color 0.2s;
        }

        .tab-btn:hover { color: var(--text-secondary); }

        .tab-btn.active {
            color: var(--text-primary);
            border-bottom-color: var(--accent);
        }

        /* Tab content */
        .tab-content {
            flex: 1;
            overflow-y: auto;
            padding: 16px 24px;
        }

        /* Section */
        .section { margin-bottom: 18px; }

        .section-title {
            font-size: 10px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 8px;
        }

        /* Tags */
        .tag-list {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .tag {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: var(--mono);
            border: 1px solid;
        }

        /* Relation card */
        .relation-card {
            background: var(--bg-tertiary);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
            transition: border-color 0.2s;
        }

        .relation-card:hover { border-color: var(--border-hover); }

        .relation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .relation-name {
            font-size: 13px;
            font-weight: 600;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            min-width: 0;
        }

        .relation-type {
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 600;
        }

        .relation-target {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 4px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Scope item */
        .scope-item {
            padding: 6px 0;
            border-bottom: 1px solid var(--border);
        }

        .scope-name {
            font-size: 12px;
            font-weight: 600;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Observer item */
        .observer-item {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
        }

        .observer-event {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .observer-handler {
            font-size: 11px;
            color: var(--warning);
        }

        /* Warning box */
        .warning-box {
            font-size: 11px;
            color: var(--danger);
            background: rgba(239, 68, 68, 0.06);
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid rgba(239, 68, 68, 0.18);
        }

        .empty-state {
            font-size: 11px;
            color: var(--text-dim);
            font-style: italic;
        }

        /* Global scope indicator */
        .global-scope-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--danger);
            flex-shrink: 0;
            display: inline-block;
        }

        /* ── Path Finder Modal ────────────────────────────────── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(8px);
            animation: fadeIn 0.2s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 32px;
            width: 500px;
            max-height: 80vh;
            overflow: auto;
            animation: slideUp 0.25s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(16px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-title {
            font-size: 16px;
            font-weight: 700;
        }

        .path-result {
            background: var(--bg-tertiary);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 14px;
            margin-bottom: 8px;
        }

        .path-step {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
        }

        .path-model-name {
            font-size: 12px;
            font-weight: 600;
        }

        .path-relation {
            font-size: 10px;
        }

        .path-eloquent {
            font-size: 10px;
            color: var(--accent);
            margin-top: 8px;
            background: var(--accent-bg);
            padding: 6px 10px;
            border-radius: 4px;
        }

        /* ── Keyboard Shortcuts ──────────────────────────────── */
        .shortcuts-grid {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .shortcut-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border);
            border-radius: 6px;
        }

        .shortcut-key {
            font-family: var(--mono);
            font-size: 11px;
            font-weight: 600;
            color: var(--text-primary);
            background: var(--bg-elevated);
            border: 1px solid var(--border-hover);
            border-bottom-width: 2px;
            border-radius: 4px;
            padding: 2px 8px;
            min-width: 48px;
            text-align: center;
        }

        .shortcut-label {
            font-size: 12px;
            color: var(--text-secondary);
        }

        /* ── SVG Edges ────────────────────────────────────────── */
        .edge-svg {
            position: absolute;
            top: 0;
            left: 0;
            pointer-events: none;
            z-index: 5;
        }

        /* ── Sidebar toggle (mobile) ──────────────────────────── */
        .sidebar-toggle {
            display: none;
            padding: 0;
            width: 34px;
            justify-content: center;
            font-size: 16px;
        }

        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 39;
            backdrop-filter: blur(2px);
        }

        /* ── Responsive ───────────────────────────────────────── */
        @media (max-width: 1200px) {
            .detail-panel { width: 340px; }
            .sidebar { width: 180px; }
            .stats-row { display: none; }
        }

        @media (max-width: 900px) {
            .sidebar-toggle { display: inline-flex; }
            .sidebar {
                position: fixed;
                top: 0;
                bottom: 0;
                left: 0;
                width: 240px;
                z-index: 40;
                transform: translateX(-100%);
                transition: transform 0.25s ease;
                display: flex;
            }
            .sidebar.open {
                transform: translateX(0);
                box-shadow: 4px 0 24px rgba(0, 0, 0, 0.5);
            }
            .sidebar-backdrop { display: block; }
            .detail-panel { width: 300px; }
        }

        @media (max-width: 600px) {
            .detail-panel {
                position: fixed;
                top: 0;
                bottom: 0;
                right: 0;
                width: 90vw;
                max-width: 360px;
                z-index: 41;
            }
        }
    </style>
</head>
<body>
    @yield('content')

</body>
</html>
