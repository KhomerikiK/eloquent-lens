@extends('eloquent-lens::layouts.app')

@section('content')
<div
    x-data="eloquentLens('{{ $apiUrl }}')"
    x-cloak
    @keydown.window="onKeydown($event)"
    style="height: 100vh; display: flex; flex-direction: column; overflow: hidden;"
>
    {{-- Loading overlay --}}
    <template x-if="loading">
        <div class="lens-loader">
            <div class="lens-loader-content">
                <div class="lens-loader-icon">
                    <span class="lens-loader-icon-text">E</span>
                    <div class="lens-loader-ring"></div>
                </div>
                <div class="lens-loader-text">Eloquent<span>Lens</span></div>

                <div class="lens-loader-steps">
                    <template x-for="(step, i) in loadingSteps" :key="i">
                        <div class="lens-loader-step" :class="{ 'done': step.done, 'active': step.active }">
                            <span class="lens-loader-step-icon" x-text="step.done ? '✓' : step.active ? '›' : '·'"></span>
                            <span x-text="step.label"></span>
                        </div>
                    </template>
                </div>

                <div class="lens-loader-tip" x-text="loadingTip"></div>
            </div>
        </div>
    </template>
    {{-- ═══ Top Navigation Bar ═══ --}}
    <header class="topbar">
        <div class="topbar-left">
            <button
                class="btn btn-ghost sidebar-toggle"
                @click="sidebarOpen = !sidebarOpen"
                :aria-expanded="sidebarOpen"
                aria-label="Toggle models sidebar"
                title="Toggle models sidebar"
            >☰</button>
            <div class="logo">
                <div class="logo-icon">E</div>
                <div class="logo-text">Eloquent<span>Lens</span></div>
            </div>
            <div class="divider-v"></div>
            <div class="stats-row">
                <div class="stat-item">
                    <span class="stat-value" x-text="modelCount"></span>
                    <span class="stat-label">Models</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value" x-text="totalRelations"></span>
                    <span class="stat-label">Relations</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value" :style="{ color: complexityColor(avgComplexity) }" x-text="avgComplexity"></span>
                    <span class="stat-label">Avg Complexity</span>
                </div>
            </div>
        </div>

        <div class="topbar-right">
            {{-- Search --}}
            <div class="search-box">
                <span class="search-icon">⌕</span>
                <input
                    type="text"
                    class="search-input"
                    placeholder="Search models..."
                    x-model="searchQuery"
                />
            </div>

            {{-- Relationship Filter --}}
            <select class="filter-select" x-model="relFilter">
                <option value="all">All Relations</option>
                <option value="hasMany">hasMany</option>
                <option value="hasOne">hasOne</option>
                <option value="belongsTo">belongsTo</option>
                <option value="belongsToMany">belongsToMany</option>
                <option value="morphMany">morph*</option>
                <option value="hasOneThrough">*Through</option>
            </select>

            {{-- Layout Mode --}}
            <div class="seg-control" role="group" aria-label="Layout mode">
                <button
                    class="seg-btn"
                    :class="{ 'active': layoutMode === 'force' }"
                    @click="layoutMode = 'force'"
                    title="Force-directed layout"
                >Force</button>
                <button
                    class="seg-btn"
                    :class="{ 'active': layoutMode === 'dagre' }"
                    @click="layoutMode = 'dagre'"
                    title="Hierarchical layout (better for large graphs)"
                >Hierarchy</button>
            </div>

            {{-- Focus Mode (cycles off → 1 → 2 → 3 → off) --}}
            <button
                class="btn btn-ghost"
                :class="{ 'btn-active': focusMode }"
                @click="cycleFocus()"
                :title="focusMode
                    ? 'Showing ' + focusHops + '-hop neighborhood around selection — click to ' + (focusHops < 3 ? 'expand' : 'turn off')
                    : 'Focus mode: show only selected model + neighbors. Requires a selection.'"
            >
                <span x-text="focusMode ? '◉' : '◎'"></span>
                Focus<span x-show="focusMode" style="opacity:0.6;margin-left:4px;" x-text="focusHops + 'h'"></span>
            </button>

            {{-- Path Finder --}}
            <button class="btn btn-accent" @click="showPathFinder = true">
                ⇢ Path Finder
            </button>

            {{-- Refresh Models --}}
            <button class="btn btn-ghost" @click="refreshModels()" :disabled="loading">
                ⟳ Refresh
            </button>

            {{-- Reset Layout --}}
            <button class="btn btn-ghost" @click="resetLayout()">
                ↻ Reset
            </button>
        </div>
    </header>

    {{-- ═══ Relationship Legend ═══ --}}
    <div class="legend-bar">
        <template x-for="(item, key) in legendItems" :key="key">
            <div class="legend-item">
                <div
                    class="legend-line"
                    :class="{ 'dashed': item.dashed }"
                    :style="item.dashed
                        ? 'border-top-color:' + item.color
                        : 'background:' + item.color"
                ></div>
                <span class="legend-label" x-text="item.label"></span>
            </div>
        </template>
    </div>

    {{-- ═══ Main Content ═══ --}}
    <div class="main-layout">

        {{-- ── Sidebar backdrop (mobile only) ── --}}
        <div class="sidebar-backdrop" x-show="sidebarOpen" @click="sidebarOpen = false" x-transition.opacity></div>

        {{-- ── Model List Sidebar ── --}}
        <div class="sidebar" :class="{ 'open': sidebarOpen }">
            <div class="sidebar-header">
                <span class="sidebar-title">Models</span>
                <span class="sidebar-count" x-text="modelCount"></span>
            </div>
            <div class="sidebar-search">
                <input
                    type="text"
                    class="sidebar-search-input"
                    placeholder="Filter..."
                    x-model="searchQuery"
                />
            </div>
            <div class="sidebar-list">
                <template x-for="name in sidebarModels" :key="'sb-'+name">
                    <button
                        class="sidebar-item"
                        :class="{ 'active': selectedModel === name }"
                        :aria-pressed="selectedModel === name"
                        @click="focusModel(name)"
                    >
                        <span class="sidebar-item-dot" :style="'background:' + complexityColor(allModels[name].complexity)"></span>
                        <span class="sidebar-item-name" x-text="name" :title="name"></span>
                        <span class="sidebar-item-rels" x-text="Object.keys(allModels[name].relationships).length"></span>
                    </button>
                </template>
            </div>
        </div>

        {{-- ── Graph Canvas ── --}}
        <div class="graph-wrapper">
            <div
                class="graph-canvas"
                x-ref="canvas"
                @mousemove.window="onMouseMove($event)"
                @mouseup.window="onMouseUp()"
                @wheel="onWheel($event)"
                @scroll="updateViewportRect()"
            >
                <div
                    class="graph-canvas-inner"
                    :style="'width:' + (canvasSize.w * zoom) + 'px; height:' + (canvasSize.h * zoom) + 'px;'"
                >
                    <div
                        class="graph-zoom-layer"
                        :style="'transform: scale(' + zoom + '); width:' + canvasSize.w + 'px; height:' + canvasSize.h + 'px;'"
                        @click="if (!$event.target.closest('.entity-card')) selectedModel = null"
                    >
                        <div class="grid-bg"></div>

                        {{-- SVG Edge Lines (no <template> inside SVG — browsers don't support it) --}}
                        <svg class="edge-svg" :viewBox="'0 0 ' + canvasSize.w + ' ' + canvasSize.h" :style="'width:' + canvasSize.w + 'px; height:' + canvasSize.h + 'px;'">
                            <defs>
                                <marker id="arrow-hasMany" viewBox="0 0 10 6" refX="10" refY="3" markerWidth="8" markerHeight="6" orient="auto"><path d="M0,0 L10,3 L0,6 Z" fill="#f97316" /></marker>
                                <marker id="arrow-hasOne" viewBox="0 0 10 6" refX="10" refY="3" markerWidth="8" markerHeight="6" orient="auto"><path d="M0,0 L10,3 L0,6 Z" fill="#22c55e" /></marker>
                                <marker id="arrow-belongsTo" viewBox="0 0 10 6" refX="10" refY="3" markerWidth="8" markerHeight="6" orient="auto"><path d="M0,0 L10,3 L0,6 Z" fill="#3b82f6" /></marker>
                                <marker id="arrow-belongsToMany" viewBox="0 0 10 6" refX="10" refY="3" markerWidth="8" markerHeight="6" orient="auto"><path d="M0,0 L10,3 L0,6 Z" fill="#a855f7" /></marker>
                                <marker id="arrow-morphMany" viewBox="0 0 10 6" refX="10" refY="3" markerWidth="8" markerHeight="6" orient="auto"><path d="M0,0 L10,3 L0,6 Z" fill="#ec4899" /></marker>
                                <marker id="arrow-morphOne" viewBox="0 0 10 6" refX="10" refY="3" markerWidth="8" markerHeight="6" orient="auto"><path d="M0,0 L10,3 L0,6 Z" fill="#ec4899" /></marker>
                                <marker id="arrow-morphTo" viewBox="0 0 10 6" refX="10" refY="3" markerWidth="8" markerHeight="6" orient="auto"><path d="M0,0 L10,3 L0,6 Z" fill="#ec4899" /></marker>
                                <marker id="arrow-morphToMany" viewBox="0 0 10 6" refX="10" refY="3" markerWidth="8" markerHeight="6" orient="auto"><path d="M0,0 L10,3 L0,6 Z" fill="#ec4899" /></marker>
                                <marker id="arrow-morphedByMany" viewBox="0 0 10 6" refX="10" refY="3" markerWidth="8" markerHeight="6" orient="auto"><path d="M0,0 L10,3 L0,6 Z" fill="#ec4899" /></marker>
                                <marker id="arrow-hasOneThrough" viewBox="0 0 10 6" refX="10" refY="3" markerWidth="8" markerHeight="6" orient="auto"><path d="M0,0 L10,3 L0,6 Z" fill="#06b6d4" /></marker>
                                <marker id="arrow-hasManyThrough" viewBox="0 0 10 6" refX="10" refY="3" markerWidth="8" markerHeight="6" orient="auto"><path d="M0,0 L10,3 L0,6 Z" fill="#06b6d4" /></marker>
                            </defs>
                            <g x-html="edgeSvgHtml"></g>
                        </svg>

                        {{-- Entity Cards (ERD) --}}
                        <template x-for="(model, name) in filteredModels" :key="name">
                            <div
                                x-show="positions[name]"
                                class="entity-card"
                                :class="{
                                    'selected': selectedModel === name,
                                    'dimmed': isDimmed(name),
                                    'dragging': dragging === name
                                }"
                                :style="positions[name] ? 'left:' + positions[name].x + 'px; top:' + positions[name].y + 'px' : ''"
                                @click.stop="toggleSelect(name)"
                                @mouseenter="if (!selectedModel) hoveredModel = name"
                                @mouseleave="if (!selectedModel) hoveredModel = null"
                                @mousedown.stop="startDrag($event, name)"
                            >
                                {{-- Header --}}
                                <div class="entity-header">
                                    <div class="entity-header-bar" :style="'background:' + complexityColor(model.complexity)"></div>
                                    <span class="entity-name" x-text="name" :title="name"></span>
                                    <span
                                        class="entity-score"
                                        :style="'color:' + complexityColor(model.complexity) + ';background:' + complexityColor(model.complexity) + '15'"
                                        x-text="model.complexity"
                                    ></span>
                                </div>

                                {{-- Columns --}}
                                <div class="entity-columns">
                                    <template x-if="model.columns && model.columns.length">
                                        <div>
                                            <template x-for="col in model.columns.slice(0, 8)" :key="col">
                                                <div class="entity-col-row">
                                                    <span class="entity-col-icon" x-text="col === 'id' ? '🔑' : '·'"></span>
                                                    <span class="entity-col-name" x-text="col" :title="col"></span>
                                                </div>
                                            </template>
                                            <template x-if="model.columns.length > 8">
                                                <div class="entity-col-overflow" x-text="'and ' + (model.columns.length - 8) + ' more...'"></div>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="!model.columns || !model.columns.length">
                                        <div class="entity-empty">No properties detected</div>
                                    </template>
                                </div>

                                {{-- Relationships --}}
                                <div class="entity-relations">
                                    <template x-if="Object.keys(model.relationships).length > 0">
                                        <div>
                                            <template x-for="(rel, relName) in model.relationships" :key="relName">
                                                <div class="entity-rel-row">
                                                    <span class="entity-rel-arrow" :style="'color:' + (relColors[rel.type] || '#666')">→</span>
                                                    <span class="entity-rel-type" x-text="relTypeLabel(rel.type)"></span>
                                                    <span class="entity-rel-target" x-text="rel.model || relName" :title="rel.model || relName"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="Object.keys(model.relationships).length === 0">
                                        <div class="entity-empty">No relationships</div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Dense-graph hint: surfaces when the full view is too noisy to read --}}
            <div
                class="dense-hint"
                x-show="modelCount > 40 && !selectedModel && !focusMode && !hintDismissed"
                x-transition.opacity
            >
                <div class="dense-hint-body">
                    <span class="dense-hint-icon">◎</span>
                    <div class="dense-hint-text">
                        <strong x-text="modelCount + ' models · ' + totalRelations + ' relations'"></strong>
                        <span>Too dense to read all at once. Pick a model and press <kbd>N</kbd> to focus on its neighborhood.</span>
                    </div>
                    <button class="dense-hint-close" @click="hintDismissed = true" aria-label="Dismiss hint">✕</button>
                </div>
            </div>

            {{-- Mini-map --}}
            <div
                class="minimap"
                x-show="showMiniMap && Object.keys(positions).length > 1"
                @mousedown="onMinimapMouseDown($event)"
                @mousemove="onMinimapMouseMove($event)"
                @mouseup.window="draggingMinimap = false"
            >
                <button
                    class="minimap-close"
                    @click.stop="showMiniMap = false"
                    title="Hide mini-map"
                    aria-label="Hide mini-map"
                >×</button>
                <svg
                    class="minimap-svg"
                    :viewBox="'0 0 ' + canvasSize.w + ' ' + canvasSize.h"
                    preserveAspectRatio="xMidYMid meet"
                >
                    {{-- Cards as small rects. Inflate height so flat LR layouts stay visible. --}}
                    <template x-for="(pos, name) in positions" :key="'mm-'+name">
                        <rect
                            :x="pos.x"
                            :y="pos.y - 40"
                            width="260"
                            :height="getCardHeight(name) + 80"
                            :fill="selectedModel === name ? '#a78bfa' : complexityColor(allModels[name] ? allModels[name].complexity : 0)"
                            :opacity="focusedModels && !focusedModels.has(name) ? 0.18 : (selectedModel === name ? 1 : 0.75)"
                            rx="40"
                        />
                    </template>

                    {{-- Viewport rectangle --}}
                    <rect
                        :x="viewportRect.x"
                        :y="viewportRect.y"
                        :width="viewportRect.w"
                        :height="viewportRect.h"
                        fill="rgba(124, 58, 237, 0.15)"
                        stroke="#a78bfa"
                        stroke-width="2"
                        vector-effect="non-scaling-stroke"
                        pointer-events="none"
                    />
                </svg>
            </div>

            <button
                x-show="!showMiniMap"
                class="minimap-show-btn"
                @click="showMiniMap = true; $nextTick(() => updateViewportRect())"
                title="Show mini-map"
                aria-label="Show mini-map"
            >▢</button>

            {{-- Zoom Controls --}}
            <div class="zoom-controls">
                <button class="zoom-btn" @click="zoomOut()" title="Zoom out" aria-label="Zoom out">−</button>
                <span class="zoom-level" x-text="Math.round(zoom * 100) + '%'"></span>
                <button class="zoom-btn" @click="zoomIn()" title="Zoom in" aria-label="Zoom in">+</button>
                <button class="zoom-btn" @click="showShortcuts = true" title="Keyboard shortcuts (?)" aria-label="Show keyboard shortcuts">?</button>
            </div>
        </div>

        {{-- ── Detail Panel ── --}}
        <template x-if="selectedModel && allModels[selectedModel]">
            @include('eloquent-lens::livewire.model-detail')
        </template>
    </div>

    {{-- ═══ Path Finder Modal ═══ --}}
    <template x-if="showPathFinder">
        @include('eloquent-lens::livewire.path-finder')
    </template>

    {{-- ═══ Keyboard Shortcuts Modal ═══ --}}
    <template x-if="showShortcuts">
        <div class="modal-overlay" @click.self="showShortcuts = false">
            <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="shortcuts-title" style="width: 380px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div class="modal-title" id="shortcuts-title">Keyboard Shortcuts</div>
                    <button class="btn-close" @click="showShortcuts = false" aria-label="Close shortcuts">✕</button>
                </div>
                <div class="shortcuts-grid">
                    <template x-for="row in [
                        ['/',      'Focus search'],
                        ['Cmd K',  'Focus search (anywhere)'],
                        ['F',      'Fit graph to viewport'],
                        ['R',      'Reset layout'],
                        ['L',      'Toggle Force / Hierarchy layout'],
                        ['N',      'Cycle focus mode (1h → 2h → 3h → off)'],
                        ['P',      'Toggle Path Finder'],
                        ['Esc',    'Close panel / modal'],
                        ['?',      'Show this list'],
                    ]" :key="row[0]">
                        <div class="shortcut-row">
                            <kbd class="shortcut-key" x-text="row[0]"></kbd>
                            <span class="shortcut-label" x-text="row[1]"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
function eloquentLens(apiUrl) {
    return {
        apiUrl: apiUrl,
        allModels: {},
        positions: {},
        selectedModel: null,
        hoveredModel: null,
        searchQuery: '',
        relFilter: 'all',
        showPathFinder: false,
        activeTab: 'overview',
        dragging: null,
        dragOffset: { x: 0, y: 0 },
        pendingDrag: null,
        zoom: 1,
        sidebarOpen: false,
        showShortcuts: false,
        loading: true,
        layoutMode: 'force',     // 'force' | 'dagre'
        focusMode: false,
        focusHops: 1,
        showMiniMap: true,
        viewportRect: { x: 0, y: 0, w: 0, h: 0 },
        draggingMinimap: false,
        hintDismissed: false,
        loadingSteps: [],
        loadingTip: '',

        // Path finder
        pathFrom: '',
        pathTo: '',
        pathResults: null,

        relColors: {
            hasMany: '#f97316',
            hasOne: '#22c55e',
            belongsTo: '#3b82f6',
            belongsToMany: '#a855f7',
            morphMany: '#ec4899',
            morphOne: '#ec4899',
            morphTo: '#ec4899',
            morphToMany: '#ec4899',
            morphedByMany: '#ec4899',
            hasOneThrough: '#06b6d4',
            hasManyThrough: '#06b6d4',
        },

        relLabels: {
            hasMany: 'has many',
            hasOne: 'has one',
            belongsTo: 'belongs to',
            belongsToMany: 'many to many',
            morphMany: 'morph many',
            morphOne: 'morph one',
            morphTo: 'morph to',
            morphToMany: 'morph to many',
            morphedByMany: 'morphed by many',
            hasOneThrough: 'has one through',
            hasManyThrough: 'has many through',
        },

        legendItems: {
            hasMany:        { color: '#f97316', label: 'has many', dashed: false },
            hasOne:         { color: '#22c55e', label: 'has one', dashed: false },
            belongsTo:      { color: '#3b82f6', label: 'belongs to', dashed: false },
            belongsToMany:  { color: '#a855f7', label: 'many to many', dashed: false },
            morph:          { color: '#ec4899', label: 'polymorphic', dashed: true },
            through:        { color: '#06b6d4', label: 'through', dashed: false },
        },

        async init() {
            this.loadStored();
            await this.fetchModels();
            this.applyUrlState();
            this.watchPersistedState();
            window.addEventListener('hashchange', () => this.applyUrlState());
            this.$nextTick(() => this.updateViewportRect());
            window.addEventListener('resize', () => this.updateViewportRect());
        },

        updateViewportRect() {
            const c = this.$refs.canvas;
            if (!c) return;
            this.viewportRect = {
                x: c.scrollLeft / this.zoom,
                y: c.scrollTop / this.zoom,
                w: c.clientWidth / this.zoom,
                h: c.clientHeight / this.zoom,
            };
        },

        get storageKey() {
            return 'eloquent-lens:' + this.apiUrl;
        },

        loadStored() {
            try {
                const raw = localStorage.getItem(this.storageKey);
                if (!raw) return;
                const s = JSON.parse(raw);
                if (s.positions && typeof s.positions === 'object') this.positions = s.positions;
                if (typeof s.zoom === 'number') this.zoom = s.zoom;
                if (typeof s.searchQuery === 'string') this.searchQuery = s.searchQuery;
                if (typeof s.relFilter === 'string') this.relFilter = s.relFilter;
                if (typeof s.selectedModel === 'string') this.selectedModel = s.selectedModel;
                if (s.layoutMode === 'force' || s.layoutMode === 'dagre') this.layoutMode = s.layoutMode;
                if (typeof s.focusMode === 'boolean') this.focusMode = s.focusMode;
                if (typeof s.focusHops === 'number') this.focusHops = Math.max(1, Math.min(4, s.focusHops));
            } catch (e) {
                console.warn('EloquentLens: failed to load stored state', e);
            }
        },

        _saveTimer: null,
        saveStored() {
            clearTimeout(this._saveTimer);
            this._saveTimer = setTimeout(() => {
                try {
                    localStorage.setItem(this.storageKey, JSON.stringify({
                        positions: this.positions,
                        zoom: this.zoom,
                        searchQuery: this.searchQuery,
                        relFilter: this.relFilter,
                        selectedModel: this.selectedModel,
                        layoutMode: this.layoutMode,
                        focusMode: this.focusMode,
                        focusHops: this.focusHops,
                    }));
                } catch (e) {
                    console.warn('EloquentLens: failed to save state', e);
                }
            }, 200);
        },

        clearStored() {
            try { localStorage.removeItem(this.storageKey); } catch (e) {}
        },

        watchPersistedState() {
            this.$watch('positions', () => this.saveStored());
            this.$watch('zoom', () => this.saveStored());
            this.$watch('searchQuery', () => this.saveStored());
            this.$watch('relFilter', () => this.saveStored());
            this.$watch('selectedModel', () => { this.saveStored(); this.syncUrl(); });
            this.$watch('showPathFinder', () => this.syncUrl());
            this.$watch('pathFrom', () => this.syncUrl());
            this.$watch('pathTo', () => this.syncUrl());
            this.$watch('focusMode', () => this.saveStored());
            this.$watch('focusHops', () => this.saveStored());
            this.$watch('layoutMode', () => { this.saveStored(); this.relayout(); });
            this.$watch('zoom', () => this.updateViewportRect());
        },

        // ── URL state (deep links) ──

        applyUrlState() {
            const hash = window.location.hash.replace(/^#/, '');
            if (!hash) return;
            if (hash.startsWith('path/')) {
                const [, from, to] = hash.split('/');
                if (from && this.allModels[decodeURIComponent(from)]) this.pathFrom = decodeURIComponent(from);
                if (to && this.allModels[decodeURIComponent(to)]) this.pathTo = decodeURIComponent(to);
                this.showPathFinder = true;
            } else {
                const name = decodeURIComponent(hash);
                if (this.allModels[name]) this.focusModel(name);
            }
        },

        _syncingUrl: false,
        syncUrl() {
            if (this._syncingUrl) return;
            this._syncingUrl = true;
            let hash = '';
            if (this.showPathFinder && (this.pathFrom || this.pathTo)) {
                hash = '#path/' + encodeURIComponent(this.pathFrom || '') + '/' + encodeURIComponent(this.pathTo || '');
            } else if (this.selectedModel) {
                hash = '#' + encodeURIComponent(this.selectedModel);
            }
            if (hash !== window.location.hash) {
                history.replaceState(null, '', hash || window.location.pathname + window.location.search);
            }
            this._syncingUrl = false;
        },

        async fetchModels() {
            const tips = [
                'Eloquent models are like onions — they have layers.',
                'Fun fact: the average Laravel app has 12 models.',
                'Relationships are complicated... even in databases.',
                'Every belongsTo deserves a hasMany in return.',
                'Your models called — they want to be visualized.',
                'Polymorphic relations: because one type is never enough.',
                'N+1 queries hate this one weird trick.',
                'Behind every great app is a well-structured schema.',
            ];
            this.loadingTip = tips[Math.floor(Math.random() * tips.length)];
            this.loading = true;
            this.loadingSteps = [
                { label: 'Discovering model files...', done: false, active: true },
                { label: 'Parsing relationships & columns...', done: false, active: false },
                { label: 'Mapping the graph...', done: false, active: false },
                { label: 'Arranging layout...', done: false, active: false },
            ];

            try {
                // Step 1 → 2: fetch from API (server does discovery + parsing)
                await this.$nextTick();
                const res = await fetch(this.apiUrl);
                if (!res.ok) throw new Error('Failed to fetch');

                this.loadingSteps[0] = { ...this.loadingSteps[0], done: true, active: false };
                this.loadingSteps[1] = { ...this.loadingSteps[1], active: true };
                await this.$nextTick();

                this.allModels = await res.json();
                const count = Object.keys(this.allModels).length;
                const rels = Object.values(this.allModels).reduce((s, m) => s + Object.keys(m.relationships).length, 0);

                this.loadingSteps[1] = { label: `Parsed ${count} models, ${rels} relationships`, done: true, active: false };
                this.loadingSteps[2] = { ...this.loadingSteps[2], active: true };
                await this.$nextTick();

                this.loadingSteps[2] = { ...this.loadingSteps[2], done: true, active: false };
                this.loadingSteps[3] = { ...this.loadingSteps[3], active: true };
                await this.$nextTick();

                const names = Object.keys(this.allModels);
                const hasAllStored = names.length > 0 && names.every(n => this.positions[n]);

                if (hasAllStored) {
                    // Drop stale positions for removed models, keep the rest
                    const kept = {};
                    names.forEach(n => { kept[n] = this.positions[n]; });
                    this.positions = kept;
                    this.loadingSteps[3] = { label: 'Restored saved layout', done: true, active: false };
                } else {
                    // Auto-pick dagre for large graphs on first visit
                    if (!localStorage.getItem(this.storageKey) && names.length > 30 && typeof dagre !== 'undefined') {
                        this.layoutMode = 'dagre';
                    }
                    this.positions = {};
                    this.generatePositions();
                    this.loadingSteps[3] = { label: 'Layout ready — enjoy!', done: true, active: false };
                    this.$nextTick(() => { this.fitToView(); });
                }

            } catch (e) {
                this.loadingSteps = this.loadingSteps.map(s => s.active ? { ...s, label: 'Error: ' + e.message, active: false } : s);
                this.loadingTip = 'Something went wrong. Check the console.';
                console.error('EloquentLens:', e);
            } finally {
                this.loading = false;
            }
        },

        async refreshModels() {
            this.searchQuery = '';
            this.relFilter = 'all';
            await this.fetchModels();
        },

        relayout() {
            this.positions = {};
            this.generatePositions();
            this.$nextTick(() => this.fitToView());
        },

        generatePositions() {
            if (this.layoutMode === 'dagre' && typeof dagre !== 'undefined') {
                this.generateDagrePositions();
                return;
            }
            this.generateForcePositions();
        },

        generateDagrePositions() {
            const models = this.allModels;
            const names = Object.keys(models);
            if (names.length === 0) return;

            const cardW = 260;
            const cardHeights = {};
            names.forEach(n => { cardHeights[n] = this.getCardHeight(n); });

            // Build dagre graph. TB on dense graphs reads better than LR on wide monitors
            // because vertical scroll is cheaper and most Eloquent graphs have a few central
            // hubs (User, Order) that benefit from a top-down arrangement.
            const g = new dagre.graphlib.Graph();
            const dense = names.length > 40;
            g.setGraph({
                rankdir: dense ? 'TB' : 'LR',
                nodesep: dense ? 24 : 40,
                ranksep: dense ? 60 : 90,
                marginx: 60,
                marginy: 60,
                ranker: 'tight-tree',
            });
            g.setDefaultEdgeLabel(() => ({}));

            names.forEach(n => g.setNode(n, { width: cardW, height: cardHeights[n] }));

            // Add edges, dedupe so the layout isn't biased by parallel edges
            const seenEdges = new Set();
            names.forEach(name => {
                Object.values(models[name].relationships).forEach(rel => {
                    if (!rel.model || !models[rel.model]) return;
                    const key = name + '→' + rel.model;
                    if (seenEdges.has(key)) return;
                    seenEdges.add(key);
                    g.setEdge(name, rel.model);
                });
            });

            dagre.layout(g);

            // Translate dagre's center-based coords into top-left positions
            const finalPos = {};
            names.forEach(n => {
                const node = g.node(n);
                if (!node) {
                    finalPos[n] = { x: 80, y: 80 };
                    return;
                }
                finalPos[n] = {
                    x: node.x - cardW / 2,
                    y: node.y - cardHeights[n] / 2,
                };
            });

            this.positions = finalPos;
        },

        generateForcePositions() {
            const models = this.allModels;
            const names = Object.keys(models);
            if (names.length === 0) return;

            const cardW = 260;
            const minGap = 15;

            // Pre-cache card heights to avoid repeated reactive reads
            const heights = {};
            names.forEach(n => {
                const m = models[n];
                if (!m) { heights[n] = 120; return; }
                const colCount = Math.min(m.columns ? m.columns.length : 0, 8);
                const hasOverflow = (m.columns ? m.columns.length : 0) > 8;
                const relCount = Object.keys(m.relationships).length;
                heights[n] = 40 + Math.max(colCount, 1) * 18 + (hasOverflow ? 18 : 0) + 14 + Math.max(relCount, 1) * 18 + 14;
            });

            // Step 1: Build adjacency graph
            const adj = {};
            names.forEach(n => adj[n] = new Set());
            names.forEach(name => {
                Object.values(models[name].relationships).forEach(rel => {
                    if (rel.model && models[rel.model]) {
                        adj[name].add(rel.model);
                        adj[rel.model].add(name);
                    }
                });
            });

            // Step 2: Find connected components via BFS
            const visited = new Set();
            const components = [];
            names.forEach(name => {
                if (visited.has(name)) return;
                const component = [];
                const queue = [name];
                visited.add(name);
                while (queue.length) {
                    const node = queue.shift();
                    component.push(node);
                    adj[node].forEach(neighbor => {
                        if (!visited.has(neighbor)) {
                            visited.add(neighbor);
                            queue.push(neighbor);
                        }
                    });
                }
                components.push(component);
            });

            // Step 3: Force-directed simulation within each component
            // All work done on plain objects to avoid Alpine reactivity overhead
            const nodePos = {};
            components.forEach(comp => {
                if (comp.length === 1) {
                    nodePos[comp[0]] = { x: 0, y: 0 };
                    return;
                }

                // Initialize in a circle
                comp.forEach((name, i) => {
                    const angle = (2 * Math.PI * i) / comp.length;
                    const radius = Math.max(120, comp.length * 40);
                    nodePos[name] = {
                        x: Math.cos(angle) * radius,
                        y: Math.sin(angle) * radius,
                    };
                });

                const iterations = 60;
                const repulsionStrength = 8000;
                const springStrength = 0.02;

                for (let iter = 0; iter < iterations; iter++) {
                    const cooling = 1 - iter / iterations;
                    const maxStep = Math.max(1, 80 * cooling);
                    const forces = {};
                    comp.forEach(n => forces[n] = { x: 0, y: 0 });

                    // Repulsion between all pairs (box-aware)
                    for (let i = 0; i < comp.length; i++) {
                        for (let j = i + 1; j < comp.length; j++) {
                            const a = comp[i], b = comp[j];
                            const pa = nodePos[a], pb = nodePos[b];
                            const hA = heights[a], hB = heights[b];

                            const dx = pb.x - pa.x;
                            const dy = pb.y - pa.y;
                            const dist = Math.sqrt(dx * dx + dy * dy) || 1;

                            // Check bounding-box overlap (with gap)
                            const overlapX = (cardW + minGap) - Math.abs(dx);
                            const overlapY = ((hA + hB) / 2 + minGap) - Math.abs(dy);

                            let fx, fy;
                            if (overlapX > 0 && overlapY > 0) {
                                // Boxes overlap — strong push apart
                                const pushStrength = 15;
                                fx = (dx / dist) * pushStrength * Math.max(overlapX, overlapY);
                                fy = (dy / dist) * pushStrength * Math.max(overlapX, overlapY);
                            } else {
                                // Normal distance-based repulsion
                                const force = repulsionStrength / (dist * dist);
                                fx = (dx / dist) * force;
                                fy = (dy / dist) * force;
                            }

                            forces[a].x -= fx;
                            forces[a].y -= fy;
                            forces[b].x += fx;
                            forces[b].y += fy;
                        }
                    }

                    // Spring attraction between connected pairs
                    comp.forEach(name => {
                        adj[name].forEach(neighbor => {
                            if (!nodePos[neighbor]) return;
                            const pa = nodePos[name], pb = nodePos[neighbor];
                            const dx = pb.x - pa.x;
                            const dy = pb.y - pa.y;
                            const dist = Math.sqrt(dx * dx + dy * dy) || 1;
                            const force = dist * springStrength;
                            forces[name].x += (dx / dist) * force;
                            forces[name].y += (dy / dist) * force;
                        });
                    });

                    // Apply forces with clamping
                    comp.forEach(name => {
                        let fx = forces[name].x * cooling;
                        let fy = forces[name].y * cooling;
                        const mag = Math.sqrt(fx * fx + fy * fy) || 1;
                        if (mag > maxStep) {
                            fx = (fx / mag) * maxStep;
                            fy = (fy / mag) * maxStep;
                        }
                        nodePos[name].x += fx;
                        nodePos[name].y += fy;
                    });
                }
            });

            // Step 4: Normalize and arrange components horizontally
            // Build final positions in a plain object, assign to reactive proxy once at end
            const finalPos = {};
            // Separate singletons (no relations) from connected components
            const singletons = [];
            const connected = [];
            components.forEach(comp => {
                if (comp.length === 1) singletons.push(comp[0]);
                else connected.push(comp);
            });
            connected.sort((a, b) => b.length - a.length);

            const componentGap = 60;
            let cursorX = 80;
            const startY = 60;
            let connectedMaxY = 0;

            connected.forEach(comp => {
                // Normalize component positions to start at (0, 0)
                let minX = Infinity, minY = Infinity;
                comp.forEach(n => {
                    minX = Math.min(minX, nodePos[n].x);
                    minY = Math.min(minY, nodePos[n].y);
                });
                comp.forEach(n => {
                    nodePos[n].x -= minX;
                    nodePos[n].y -= minY;
                });

                // Place at cursor position
                comp.forEach(n => {
                    finalPos[n] = {
                        x: cursorX + nodePos[n].x,
                        y: startY + nodePos[n].y,
                    };
                    connectedMaxY = Math.max(connectedMaxY, finalPos[n].y + heights[n]);
                });

                // Advance cursor past this component's bounding box
                let maxX = 0;
                comp.forEach(n => {
                    maxX = Math.max(maxX, nodePos[n].x + cardW);
                });
                cursorX += maxX + componentGap;
            });

            // Arrange singletons in a compact grid below connected components
            if (singletons.length > 0) {
                singletons.sort((a, b) => a.localeCompare(b));
                const gridTop = connected.length > 0 ? connectedMaxY + componentGap : startY;
                const cols = Math.max(1, Math.ceil(Math.sqrt(singletons.length)));
                const colW = cardW + minGap;
                singletons.forEach((n, i) => {
                    const col = i % cols;
                    const row = Math.floor(i / cols);
                    // Stack rows using actual card heights of preceding rows
                    let rowY = gridTop;
                    for (let r = 0; r < row; r++) {
                        // Find max height in row r
                        let rowMaxH = 0;
                        for (let c = 0; c < cols; c++) {
                            const idx = r * cols + c;
                            if (idx < singletons.length) {
                                rowMaxH = Math.max(rowMaxH, heights[singletons[idx]]);
                            }
                        }
                        rowY += rowMaxH + minGap;
                    }
                    finalPos[n] = { x: 80 + col * colW, y: rowY };
                });
            }

            // Step 5: Final overlap removal (safety net)
            for (let pass = 0; pass < 5; pass++) {
                let moved = false;
                for (let i = 0; i < names.length; i++) {
                    for (let j = i + 1; j < names.length; j++) {
                        const a = names[i], b = names[j];
                        const pa = finalPos[a], pb = finalPos[b];
                        const hA = heights[a], hB = heights[b];

                        const overlapX = (cardW + minGap) - Math.abs(pb.x - pa.x);
                        const overlapY = ((hA + hB) / 2 + minGap) - Math.abs(pb.y - pa.y);

                        if (overlapX > 0 && overlapY > 0) {
                            moved = true;
                            // Push apart along axis of least overlap
                            if (overlapX < overlapY) {
                                const push = overlapX / 2 + 1;
                                if (pb.x >= pa.x) { pb.x += push; pa.x -= push; }
                                else { pa.x += push; pb.x -= push; }
                            } else {
                                const push = overlapY / 2 + 1;
                                if (pb.y >= pa.y) { pb.y += push; pa.y -= push; }
                                else { pa.y += push; pb.y -= push; }
                            }
                        }
                    }
                }
                if (!moved) break;
            }

            // Step 6: Clamp — ensure no card is off-screen at negative coords
            let globalMinX = Infinity, globalMinY = Infinity;
            names.forEach(n => {
                globalMinX = Math.min(globalMinX, finalPos[n].x);
                globalMinY = Math.min(globalMinY, finalPos[n].y);
            });
            const padX = 40, padY = 40;
            if (globalMinX < padX || globalMinY < padY) {
                const shiftX = globalMinX < padX ? padX - globalMinX : 0;
                const shiftY = globalMinY < padY ? padY - globalMinY : 0;
                names.forEach(n => {
                    finalPos[n].x += shiftX;
                    finalPos[n].y += shiftY;
                });
            }

            // Single reactive assignment — triggers one Alpine update instead of thousands
            this.positions = finalPos;
        },

        resetLayout() {
            this.positions = {};
            this.generatePositions();
            this.selectedModel = null;
            this.$nextTick(() => { this.fitToView(); });
        },

        fitToView() {
            const canvas = this.$refs.canvas;
            if (!canvas) return;
            const cs = this.canvasSize;
            if (cs.w <= 0 || cs.h <= 0) return;
            const vw = canvas.clientWidth;
            const vh = canvas.clientHeight;
            const z = Math.min(vw / cs.w, vh / cs.h);
            this.zoom = Math.round(Math.max(0.1, Math.min(2, z)) * 100) / 100;
            this.$nextTick(() => {
                canvas.scrollLeft = 0;
                canvas.scrollTop = 0;
            });
        },

        // ── Computed ──

        get modelCount() {
            return Object.keys(this.allModels).length;
        },

        get totalRelations() {
            return Object.values(this.allModels).reduce((sum, m) => sum + Object.keys(m.relationships).length, 0);
        },

        get avgComplexity() {
            const models = Object.values(this.allModels);
            if (!models.length) return 0;
            return Math.round(models.reduce((sum, m) => sum + m.complexity, 0) / models.length);
        },

        get filteredModels() {
            const focus = this.focusedModels;
            const q = this.searchQuery ? this.searchQuery.toLowerCase() : null;

            if (!focus && !q) return this.allModels;

            const result = {};
            Object.entries(this.allModels).forEach(([name, model]) => {
                if (focus && !focus.has(name)) return;
                if (q && !name.toLowerCase().includes(q)) return;
                result[name] = model;
            });
            return result;
        },

        get connectedModels() {
            return this.neighborhood(1);
        },

        get focusedModels() {
            if (!this.focusMode || !this.selectedModel) return null;
            return this.neighborhood(this.focusHops);
        },

        neighborhood(hops) {
            if (!this.selectedModel || !this.allModels[this.selectedModel]) return null;

            // Build undirected adjacency lazily
            const adj = {};
            Object.entries(this.allModels).forEach(([name, model]) => {
                if (!adj[name]) adj[name] = new Set();
                Object.values(model.relationships).forEach(rel => {
                    if (!rel.model || !this.allModels[rel.model]) return;
                    adj[name].add(rel.model);
                    if (!adj[rel.model]) adj[rel.model] = new Set();
                    adj[rel.model].add(name);
                });
            });

            const visited = new Set([this.selectedModel]);
            let frontier = [this.selectedModel];
            for (let h = 0; h < hops && frontier.length; h++) {
                const next = [];
                frontier.forEach(n => {
                    (adj[n] || new Set()).forEach(neighbor => {
                        if (!visited.has(neighbor)) {
                            visited.add(neighbor);
                            next.push(neighbor);
                        }
                    });
                });
                frontier = next;
            }
            return visited;
        },

        get canvasSize() {
            let maxX = 0, maxY = 0;
            const nodeW = 260;
            Object.keys(this.allModels).forEach(name => {
                if (!this.positions[name]) return;
                const h = this.getCardHeight(name);
                maxX = Math.max(maxX, this.positions[name].x + nodeW);
                maxY = Math.max(maxY, this.positions[name].y + h);
            });
            return { w: Math.max(maxX + 100, 800), h: Math.max(maxY + 100, 600) };
        },

        getCardHeight(name) {
            const model = this.allModels[name];
            if (!model) return 120;
            const colCount = Math.min(model.columns ? model.columns.length : 0, 8);
            const hasOverflow = (model.columns ? model.columns.length : 0) > 8;
            const relCount = Object.keys(model.relationships).length;
            return 40 + Math.max(colCount, 1) * 18 + (hasOverflow ? 18 : 0) + 14 + Math.max(relCount, 1) * 18 + 14;
        },

        get edges() {
            const result = [];
            const nodeW = 260;
            const focus = this.focusedModels;

            // Pass 1: collect candidates that pass all filters
            const candidates = [];
            Object.entries(this.allModels).forEach(([name, model]) => {
                if (focus && !focus.has(name)) return;
                Object.entries(model.relationships).forEach(([relName, rel]) => {
                    if (!rel.model || !this.allModels[rel.model]) return;
                    if (focus && !focus.has(rel.model)) return;
                    if (!this.positions[name] || !this.positions[rel.model]) return;
                    if (this.relFilter !== 'all' && !rel.type.includes(this.relFilter)) return;
                    candidates.push({ name, relName, rel });
                });
            });

            // Pass 2: group parallel edges by directed pair (so the slot index is stable)
            const groups = {};
            candidates.forEach((c, i) => {
                const key = c.name + '|' + c.rel.model;
                (groups[key] = groups[key] || []).push(i);
            });

            // Pass 3: build paths with perpendicular offset for parallels
            candidates.forEach((c, i) => {
                const { name, relName, rel } = c;
                const group = groups[name + '|' + rel.model];
                const slot = group.indexOf(i);
                const parallel = group.length > 1 ? (slot - (group.length - 1) / 2) * 14 : 0;

                const from = this.positions[name];
                const to = this.positions[rel.model];
                const fromH = this.getCardHeight(name);
                const toH = this.getCardHeight(rel.model);

                const cx1 = from.x + nodeW / 2;
                const cy1 = from.y + fromH / 2;
                const cx2 = to.x + nodeW / 2;
                const cy2 = to.y + toH / 2;

                const dx = cx2 - cx1;
                const dy = cy2 - cy1;
                const horizontal = Math.abs(dx) > Math.abs(dy);

                let sx, sy, ex, ey;
                if (horizontal) {
                    if (dx > 0) { sx = from.x + nodeW; ex = to.x; }
                    else        { sx = from.x;         ex = to.x + nodeW; }
                    sy = cy1; ey = cy2;
                } else {
                    sx = cx1; ex = cx2;
                    if (dy > 0) { sy = from.y + fromH; ey = to.y; }
                    else        { sy = from.y;        ey = to.y + toH; }
                }

                // Apply perpendicular parallel-edge offset
                if (parallel) {
                    if (horizontal) { sy += parallel; ey += parallel; }
                    else            { sx += parallel; ex += parallel; }
                }

                const dist = Math.sqrt(dx * dx + dy * dy);
                const ctrl = Math.min(60, dist * 0.3);
                let c1x, c1y, c2x, c2y;
                if (horizontal) {
                    c1x = sx + (dx > 0 ? ctrl : -ctrl); c1y = sy;
                    c2x = ex + (dx > 0 ? -ctrl : ctrl); c2y = ey;
                } else {
                    c1x = sx; c1y = sy + (dy > 0 ? ctrl : -ctrl);
                    c2x = ex; c2y = ey + (dy > 0 ? -ctrl : ctrl);
                }

                const isActive =
                    this.selectedModel === name || this.selectedModel === rel.model ||
                    this.hoveredModel === name || this.hoveredModel === rel.model;
                const hasHighlight = this.selectedModel || this.hoveredModel;

                result.push({
                    key: `${name}-${rel.model}-${relName}`,
                    path: `M${sx},${sy} C${c1x},${c1y} ${c2x},${c2y} ${ex},${ey}`,
                    color: this.relColors[rel.type] || '#666',
                    type: rel.type,
                    isActive,
                    isDashed: rel.type.includes('morph'),
                    opacity: hasHighlight ? (isActive ? 1 : 0.08) : 0.5,
                });
            });

            return result;
        },

        get edgeSvgHtml() {
            return this.edges.map(e => {
                const safeType = e.type.replace(/[^a-zA-Z]/g, '');
                return `<path d="${e.path}" fill="none" stroke="${e.color}" stroke-width="${e.isActive ? 2.5 : 1.5}" stroke-dasharray="${e.isDashed ? '6 4' : 'none'}" opacity="${e.opacity}" marker-end="url(#arrow-${safeType})" style="transition:opacity .3s ease"/>`;
            }).join('');
        },

        get selectedModelData() {
            return this.selectedModel ? this.allModels[this.selectedModel] : null;
        },

        get modelNames() {
            return Object.keys(this.allModels);
        },

        get sidebarModels() {
            const names = Object.keys(this.allModels).sort();
            if (!this.searchQuery) return names;
            const q = this.searchQuery.toLowerCase();
            return names.filter(n => n.toLowerCase().includes(q));
        },

        // ── Methods ──

        complexityColor(score) {
            if (score >= 70) return '#ef4444';
            if (score >= 40) return '#f59e0b';
            return '#22c55e';
        },

        complexityLabel(score) {
            if (score >= 70) return 'High';
            if (score >= 40) return 'Medium';
            return 'Low';
        },

        isDimmed(name) {
            if (!this.connectedModels) return false;
            return !this.connectedModels.has(name);
        },

        toggleSelect(name) {
            const turningOn = this.selectedModel !== name;
            const firstSelection = !this.selectedModel && !this.focusMode && this.modelCount > 40;
            this.selectedModel = turningOn ? name : null;
            this.activeTab = 'overview';
            if (turningOn && firstSelection) {
                this.focusMode = true;
                this.focusHops = 1;
            }
        },

        focusModel(name) {
            // First selection in a dense graph auto-enables focus mode so users
            // aren't stuck staring at the hairball. They can press N to widen
            // the radius or click Focus to turn it off.
            const firstSelection = !this.selectedModel && !this.focusMode && this.modelCount > 40;
            this.selectedModel = name;
            this.activeTab = 'overview';
            if (firstSelection) {
                this.focusMode = true;
                this.focusHops = 1;
            }
            this.$nextTick(() => {
                const canvas = this.$refs.canvas;
                if (!canvas || !this.positions[name]) return;
                const pos = this.positions[name];
                const h = this.getCardHeight(name);
                const cx = (pos.x + 130) * this.zoom - canvas.clientWidth / 2;
                const cy = (pos.y + h / 2) * this.zoom - canvas.clientHeight / 2;
                canvas.scrollTo({ left: Math.max(0, cx), top: Math.max(0, cy), behavior: 'smooth' });
            });
        },

        // ── Dragging ──

        startDrag(e, name) {
            const canvas = this.$refs.canvas;
            const rect = canvas.getBoundingClientRect();
            this.pendingDrag = {
                name,
                startX: e.clientX,
                startY: e.clientY,
                offsetX: (e.clientX - rect.left + canvas.scrollLeft) / this.zoom - this.positions[name].x,
                offsetY: (e.clientY - rect.top + canvas.scrollTop) / this.zoom - this.positions[name].y,
            };
        },

        onMouseMove(e) {
            if (this.pendingDrag) {
                const dx = e.clientX - this.pendingDrag.startX;
                const dy = e.clientY - this.pendingDrag.startY;
                if (dx * dx + dy * dy < 16) return; // 4px threshold
                this.dragging = this.pendingDrag.name;
                this.dragOffset = { x: this.pendingDrag.offsetX, y: this.pendingDrag.offsetY };
                this.pendingDrag = null;
            }
            if (!this.dragging) return;
            const canvas = this.$refs.canvas;
            const rect = canvas.getBoundingClientRect();
            this.positions[this.dragging] = {
                x: (e.clientX - rect.left + canvas.scrollLeft) / this.zoom - this.dragOffset.x,
                y: (e.clientY - rect.top + canvas.scrollTop) / this.zoom - this.dragOffset.y,
            };
        },

        onMouseUp() {
            this.dragging = null;
            this.pendingDrag = null;
        },

        // ── Zoom ──

        onWheel(e) {
            if (!e.ctrlKey && !e.metaKey) return;
            e.preventDefault();

            const canvas = this.$refs.canvas;
            const rect = canvas.getBoundingClientRect();

            // Mouse position in content coordinates before zoom
            const mx = e.clientX - rect.left;
            const my = e.clientY - rect.top;
            const contentX = (canvas.scrollLeft + mx) / this.zoom;
            const contentY = (canvas.scrollTop + my) / this.zoom;

            // Smooth multiplicative zoom
            const factor = Math.pow(0.99, e.deltaY);
            const newZoom = Math.round(Math.max(0.1, Math.min(2, this.zoom * factor)) * 100) / 100;
            if (newZoom === this.zoom) return;
            this.zoom = newZoom;

            // Adjust scroll to keep same content point under cursor
            this.$nextTick(() => {
                canvas.scrollLeft = contentX * this.zoom - mx;
                canvas.scrollTop = contentY * this.zoom - my;
            });
        },

        zoomIn() {
            this.zoom = Math.round(Math.min(2, this.zoom * 1.15) * 100) / 100;
        },

        zoomOut() {
            this.zoom = Math.round(Math.max(0.1, this.zoom / 1.15) * 100) / 100;
        },

        // ── Path Finder ──

        findPaths() {
            if (!this.pathFrom || !this.pathTo || this.pathFrom === this.pathTo) return;

            const results = [];
            const models = this.allModels;

            function dfs(current, path, visited) {
                if (results.length >= 50) return;
                if (current === this.pathTo) {
                    results.push([...path]);
                    return;
                }
                if (path.length > 5) return;
                visited.add(current);

                const model = models[current];
                if (!model) return;

                Object.entries(model.relationships).forEach(([relName, rel]) => {
                    if (rel.model && !visited.has(rel.model)) {
                        path.push({ from: current, rel: relName, type: rel.type, to: rel.model });
                        dfs.call(this, rel.model, path, visited);
                        path.pop();
                    }
                });

                visited.delete(current);
            }

            dfs.call(this, this.pathFrom, [], new Set());
            this.pathResults = results;
        },

        relTypeColor(type) {
            return this.relColors[type] || '#666';
        },

        relTypeLabel(type) {
            return this.relLabels[type] || type;
        },

        lcfirst(s) {
            return s ? s.charAt(0).toLowerCase() + s.slice(1) : s;
        },

        // ── Mini-map ──

        panToMinimapPoint(e) {
            const svg = e.currentTarget.querySelector('.minimap-svg') || e.currentTarget;
            const rect = svg.getBoundingClientRect();
            const cs = this.canvasSize;
            // Compute the actual rendered SVG area inside the box (preserveAspectRatio=meet)
            const scale = Math.min(rect.width / cs.w, rect.height / cs.h);
            const renderedW = cs.w * scale;
            const renderedH = cs.h * scale;
            const offsetX = (rect.width - renderedW) / 2;
            const offsetY = (rect.height - renderedH) / 2;
            // Mouse position inside the minimap, accounting for centering offset
            const mx = e.clientX - rect.left - offsetX;
            const my = e.clientY - rect.top - offsetY;
            // Translate to content coords
            const contentX = mx / scale;
            const contentY = my / scale;
            const canvas = this.$refs.canvas;
            if (!canvas) return;
            canvas.scrollLeft = contentX * this.zoom - canvas.clientWidth / 2;
            canvas.scrollTop  = contentY * this.zoom - canvas.clientHeight / 2;
        },

        onMinimapMouseDown(e) {
            if (e.target.classList.contains('minimap-close')) return;
            this.draggingMinimap = true;
            this.panToMinimapPoint(e);
        },

        onMinimapMouseMove(e) {
            if (!this.draggingMinimap) return;
            this.panToMinimapPoint(e);
        },

        cycleFocus() {
            if (!this.focusMode) {
                this.focusMode = true;
                this.focusHops = 1;
            } else if (this.focusHops < 3) {
                this.focusHops += 1;
            } else {
                this.focusMode = false;
                this.focusHops = 1;
            }
        },

        // ── Keyboard shortcuts ──

        onKeydown(e) {
            const tag = e.target.tagName;
            const isTyping = tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || e.target.isContentEditable;

            // Cmd/Ctrl+K: focus search (works while typing)
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                this.focusSearch();
                return;
            }

            // Esc: close topmost layer
            if (e.key === 'Escape') {
                if (this.showPathFinder) { this.showPathFinder = false; return; }
                if (this.selectedModel)  { this.selectedModel = null; return; }
                if (this.sidebarOpen)    { this.sidebarOpen = false; return; }
                if (isTyping) e.target.blur();
                return;
            }

            if (isTyping || e.metaKey || e.ctrlKey || e.altKey) return;

            switch (e.key) {
                case '/':
                    e.preventDefault();
                    this.focusSearch();
                    break;
                case 'f':
                case 'F':
                    e.preventDefault();
                    this.fitToView();
                    break;
                case 'r':
                case 'R':
                    e.preventDefault();
                    this.resetLayout();
                    break;
                case 'p':
                case 'P':
                    e.preventDefault();
                    this.showPathFinder = !this.showPathFinder;
                    break;
                case 'n':
                case 'N':
                    e.preventDefault();
                    this.cycleFocus();
                    break;
                case 'l':
                case 'L':
                    e.preventDefault();
                    this.layoutMode = this.layoutMode === 'force' ? 'dagre' : 'force';
                    break;
                case '?':
                    e.preventDefault();
                    this.showShortcuts = !this.showShortcuts;
                    break;
            }
        },

        focusSearch() {
            const el = document.querySelector('.topbar .search-input');
            if (el) { el.focus(); el.select(); }
        },

        traitTagStyle(trait) {
            const specials = {
                SoftDeletes: '#ef4444',
                Searchable:  '#3b82f6',
                Notifiable:  '#22c55e',
                HasApiTokens: '#a855f7',
                HasFactory:   '#06b6d4',
                HasUuids:     '#f59e0b',
                HasRoles:     '#ec4899',
            };
            const palette = ['#22c55e', '#3b82f6', '#a855f7', '#06b6d4', '#f59e0b', '#ec4899', '#f97316', '#84cc16'];
            let color = specials[trait];
            if (!color) {
                let h = 0;
                for (let i = 0; i < trait.length; i++) h = (h * 31 + trait.charCodeAt(i)) >>> 0;
                color = palette[h % palette.length];
            }
            return `color:${color};background:${color}14;border-color:${color}33`;
        },
    };
}
</script>
@endsection
