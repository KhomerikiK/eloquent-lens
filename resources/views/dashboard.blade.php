@extends('eloquent-lens::layouts.app')

@section('content')
<div
    x-data="eloquentLens(@js($models))"
    style="height: 100vh; display: flex; flex-direction: column; overflow: hidden;"
>
    {{-- ═══ Top Navigation Bar ═══ --}}
    <header class="topbar">
        <div class="topbar-left">
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

            {{-- Path Finder --}}
            <button class="btn btn-accent" @click="showPathFinder = true">
                ⇢ Path Finder
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

        {{-- ── Graph Canvas ── --}}
        <div
            class="graph-canvas"
            @click.self="selectedModel = null"
            @mousemove.window="onMouseMove($event)"
            @mouseup.window="onMouseUp()"
        >
            <div
                class="graph-canvas-inner"
                :style="'transform: translate(' + panX + 'px,' + panY + 'px) scale(' + zoom + ')'"
            >
                <div class="grid-bg"></div>

                {{-- SVG Edge Lines --}}
                <svg class="edge-svg" viewBox="0 0 3000 2000" preserveAspectRatio="none" style="width:3000px;height:2000px;">
                    {{-- Arrow markers --}}
                    <defs>
                        <template x-for="(color, type) in relColors" :key="'marker-'+type">
                            <marker :id="'arrow-'+type" viewBox="0 0 10 6" refX="10" refY="3" markerWidth="8" markerHeight="6" orient="auto">
                                <path d="M0,0 L10,3 L0,6 Z" :fill="color" />
                            </marker>
                        </template>
                    </defs>

                    <template x-for="edge in edges" :key="edge.key">
                        <path
                            :d="edge.path"
                            fill="none"
                            :stroke="edge.color"
                            :stroke-width="edge.isActive ? 2.5 : 1.5"
                            :stroke-dasharray="edge.isDashed ? '6 4' : 'none'"
                            :opacity="edge.opacity"
                            :marker-end="'url(#arrow-' + edge.type + ')'"
                            style="transition: opacity 0.3s ease"
                        />
                    </template>
                </svg>

                {{-- Model Nodes --}}
                <template x-for="(model, name) in filteredModels" :key="name">
                    <div
                        class="model-node"
                        :class="{
                            'selected': selectedModel === name,
                            'dimmed': isDimmed(name),
                            'dragging': dragging === name
                        }"
                        :style="'left:' + positions[name].x + 'px; top:' + positions[name].y + 'px'"
                        @click.stop="toggleSelect(name)"
                        @mouseenter="hoveredModel = name"
                        @mouseleave="hoveredModel = null"
                        @mousedown.stop="startDrag($event, name)"
                    >
                        <div class="node-complexity-bar" :style="'background:' + complexityColor(model.complexity)"></div>

                        <div class="node-header">
                            <span class="node-name" x-text="name"></span>
                            <span
                                class="node-score"
                                :style="'color:' + complexityColor(model.complexity) + ';background:' + complexityColor(model.complexity) + '15'"
                                x-text="model.complexity"
                            ></span>
                        </div>

                        <div class="node-meta">
                            <span x-text="Object.keys(model.relationships).length + ' rel'"></span>
                            <span x-text="model.fillable.length + ' fields'"></span>
                            <span x-text="model.traits.length + ' traits'"></span>
                        </div>
                    </div>
                </template>
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
</div>

<script>
function eloquentLens(modelsData) {
    return {
        allModels: modelsData,
        positions: {},
        selectedModel: null,
        hoveredModel: null,
        searchQuery: '',
        relFilter: 'all',
        showPathFinder: false,
        activeTab: 'overview',
        dragging: null,
        dragOffset: { x: 0, y: 0 },
        panX: 0,
        panY: 0,
        zoom: 1,

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

        init() {
            this.generatePositions();
        },

        generatePositions() {
            const names = Object.keys(this.allModels);
            const cx = 600, cy = 400, radius = 320;

            names.forEach((name, i) => {
                const angle = (2 * Math.PI * i) / names.length - Math.PI / 2;
                this.positions[name] = {
                    x: Math.round(cx + radius * Math.cos(angle)),
                    y: Math.round(cy + radius * Math.sin(angle)),
                };
            });
        },

        resetLayout() {
            this.generatePositions();
            this.panX = 0;
            this.panY = 0;
            this.zoom = 1;
            this.selectedModel = null;
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
            if (!this.searchQuery) return this.allModels;
            const q = this.searchQuery.toLowerCase();
            const result = {};
            Object.entries(this.allModels).forEach(([name, model]) => {
                if (name.toLowerCase().includes(q)) {
                    result[name] = model;
                }
            });
            return result;
        },

        get connectedModels() {
            if (!this.selectedModel || !this.allModels[this.selectedModel]) return null;
            const connected = new Set([this.selectedModel]);

            // Direct relationships from selected model
            Object.values(this.allModels[this.selectedModel].relationships).forEach(rel => {
                if (rel.model) connected.add(rel.model);
            });

            // Models that reference selected model
            Object.entries(this.allModels).forEach(([name, model]) => {
                Object.values(model.relationships).forEach(rel => {
                    if (rel.model === this.selectedModel) connected.add(name);
                });
            });

            return connected;
        },

        get edges() {
            const result = [];
            const nodeW = 210, nodeH = 68;

            Object.entries(this.allModels).forEach(([name, model]) => {
                Object.entries(model.relationships).forEach(([relName, rel]) => {
                    if (!rel.model || !this.allModels[rel.model]) return;
                    if (!this.positions[name] || !this.positions[rel.model]) return;
                    if (this.relFilter !== 'all' && !rel.type.includes(this.relFilter)) return;

                    const from = this.positions[name];
                    const to = this.positions[rel.model];

                    const x1 = from.x + nodeW / 2;
                    const y1 = from.y + nodeH / 2;
                    const x2 = to.x + nodeW / 2;
                    const y2 = to.y + nodeH / 2;

                    const dx = x2 - x1;
                    const dy = y2 - y1;
                    const dist = Math.sqrt(dx * dx + dy * dy) || 1;
                    const nx = dx / dist;
                    const ny = dy / dist;

                    const sx = x1 + nx * 50;
                    const sy = y1 + ny * 34;
                    const ex = x2 - nx * 50;
                    const ey = y2 - ny * 34;

                    const midX = (sx + ex) / 2 + ny * 30;
                    const midY = (sy + ey) / 2 - nx * 30;

                    const isActive =
                        this.selectedModel === name || this.selectedModel === rel.model ||
                        this.hoveredModel === name || this.hoveredModel === rel.model;
                    const hasHighlight = this.selectedModel || this.hoveredModel;

                    result.push({
                        key: `${name}-${rel.model}-${relName}`,
                        path: `M${sx},${sy} Q${midX},${midY} ${ex},${ey}`,
                        color: this.relColors[rel.type] || '#666',
                        type: rel.type,
                        isActive,
                        isDashed: rel.type.includes('morph'),
                        opacity: hasHighlight ? (isActive ? 1 : 0.08) : 0.5,
                    });
                });
            });

            return result;
        },

        get selectedModelData() {
            return this.selectedModel ? this.allModels[this.selectedModel] : null;
        },

        get modelNames() {
            return Object.keys(this.allModels);
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
            this.selectedModel = this.selectedModel === name ? null : name;
            this.activeTab = 'overview';
        },

        // ── Dragging ──

        startDrag(e, name) {
            this.dragging = name;
            this.dragOffset = {
                x: e.clientX / this.zoom - this.positions[name].x,
                y: e.clientY / this.zoom - this.positions[name].y,
            };
        },

        onMouseMove(e) {
            if (!this.dragging) return;
            this.positions[this.dragging] = {
                x: e.clientX / this.zoom - this.dragOffset.x,
                y: e.clientY / this.zoom - this.dragOffset.y,
            };
        },

        onMouseUp() {
            this.dragging = null;
        },

        // ── Path Finder ──

        findPaths() {
            if (!this.pathFrom || !this.pathTo || this.pathFrom === this.pathTo) return;

            const results = [];
            const models = this.allModels;

            function dfs(current, path, visited) {
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
    };
}
</script>
@endsection
