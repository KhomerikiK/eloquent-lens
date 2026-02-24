@extends('eloquent-lens::layouts.app')

@section('content')
<div
    x-data="eloquentLens('{{ $apiUrl }}')"
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

        {{-- ── Model List Sidebar ── --}}
        <div class="sidebar">
            <div class="sidebar-header">
                <span class="sidebar-title">Models</span>
                <span class="sidebar-count" x-text="modelCount"></span>
            </div>
            <div class="sidebar-search">
                <input
                    type="text"
                    class="sidebar-search-input"
                    placeholder="Filter..."
                    x-model="sidebarFilter"
                />
            </div>
            <div class="sidebar-list">
                <template x-for="name in sidebarModels" :key="'sb-'+name">
                    <button
                        class="sidebar-item"
                        :class="{ 'active': selectedModel === name }"
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

                        {{-- SVG Edge Lines --}}
                        <svg class="edge-svg" :viewBox="'0 0 ' + canvasSize.w + ' ' + canvasSize.h" :style="'width:' + canvasSize.w + 'px; height:' + canvasSize.h + 'px;'">
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
                                @mouseenter="hoveredModel = name"
                                @mouseleave="hoveredModel = null"
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

            {{-- Zoom Controls --}}
            <div class="zoom-controls">
                <button class="zoom-btn" @click="zoomOut()" title="Zoom out">−</button>
                <span class="zoom-level" x-text="Math.round(zoom * 100) + '%'"></span>
                <button class="zoom-btn" @click="zoomIn()" title="Zoom in">+</button>
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
        zoom: 1,
        sidebarFilter: '',
        loading: true,
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
            await this.fetchModels();
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

                // Step 3: Build adjacency graph (instant, but show it)
                await new Promise(r => setTimeout(r, 120));

                this.loadingSteps[2] = { ...this.loadingSteps[2], done: true, active: false };
                this.loadingSteps[3] = { ...this.loadingSteps[3], active: true };
                await this.$nextTick();

                // Step 4: Generate positions
                this.positions = {};
                this.generatePositions();

                this.loadingSteps[3] = { label: 'Layout ready — enjoy!', done: true, active: false };
                await new Promise(r => setTimeout(r, 300));

            } catch (e) {
                this.loadingSteps = this.loadingSteps.map(s => s.active ? { ...s, label: 'Error: ' + e.message, active: false } : s);
                this.loadingTip = 'Something went wrong. Check the console.';
                console.error('EloquentLens:', e);
                await new Promise(r => setTimeout(r, 2000));
            } finally {
                this.loading = false;
            }
        },

        async refreshModels() {
            this.selectedModel = null;
            await this.fetchModels();
            this.zoom = 1;
            this.$nextTick(() => {
                if (this.$refs.canvas) {
                    this.$refs.canvas.scrollTop = 0;
                    this.$refs.canvas.scrollLeft = 0;
                }
            });
        },

        generatePositions() {
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
            components.sort((a, b) => b.length - a.length);

            const componentGap = 60;
            let cursorX = 80;
            const startY = 60;

            components.forEach(comp => {
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
                });

                // Advance cursor past this component's bounding box
                let maxX = 0;
                comp.forEach(n => {
                    maxX = Math.max(maxX, nodePos[n].x + cardW);
                });
                cursorX += maxX + componentGap;
            });

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
            this.generatePositions();
            this.zoom = 1;
            this.selectedModel = null;
            this.$nextTick(() => {
                if (this.$refs.canvas) {
                    this.$refs.canvas.scrollTop = 0;
                    this.$refs.canvas.scrollLeft = 0;
                }
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

            Object.entries(this.allModels).forEach(([name, model]) => {
                Object.entries(model.relationships).forEach(([relName, rel]) => {
                    if (!rel.model || !this.allModels[rel.model]) return;
                    if (!this.positions[name] || !this.positions[rel.model]) return;
                    if (this.relFilter !== 'all' && !rel.type.includes(this.relFilter)) return;

                    const from = this.positions[name];
                    const to = this.positions[rel.model];
                    const fromH = this.getCardHeight(name);
                    const toH = this.getCardHeight(rel.model);

                    // Center points
                    const cx1 = from.x + nodeW / 2;
                    const cy1 = from.y + fromH / 2;
                    const cx2 = to.x + nodeW / 2;
                    const cy2 = to.y + toH / 2;

                    // Determine exit/entry sides
                    const dx = cx2 - cx1;
                    const dy = cy2 - cy1;

                    let sx, sy, ex, ey;

                    if (Math.abs(dx) > Math.abs(dy)) {
                        // Horizontal: connect left/right sides
                        if (dx > 0) {
                            sx = from.x + nodeW;
                            ex = to.x;
                        } else {
                            sx = from.x;
                            ex = to.x + nodeW;
                        }
                        sy = cy1;
                        ey = cy2;
                    } else {
                        // Vertical: connect top/bottom
                        sx = cx1;
                        ex = cx2;
                        if (dy > 0) {
                            sy = from.y + fromH;
                            ey = to.y;
                        } else {
                            sy = from.y;
                            ey = to.y + toH;
                        }
                    }

                    // Bezier control offset
                    const offset = Math.min(60, Math.sqrt(dx * dx + dy * dy) * 0.3);
                    let c1x, c1y, c2x, c2y;

                    if (Math.abs(dx) > Math.abs(dy)) {
                        c1x = sx + (dx > 0 ? offset : -offset);
                        c1y = sy;
                        c2x = ex + (dx > 0 ? -offset : offset);
                        c2y = ey;
                    } else {
                        c1x = sx;
                        c1y = sy + (dy > 0 ? offset : -offset);
                        c2x = ex;
                        c2y = ey + (dy > 0 ? -offset : offset);
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
            });

            return result;
        },

        get selectedModelData() {
            return this.selectedModel ? this.allModels[this.selectedModel] : null;
        },

        get modelNames() {
            return Object.keys(this.allModels);
        },

        get sidebarModels() {
            const names = Object.keys(this.allModels).sort();
            if (!this.sidebarFilter) return names;
            const q = this.sidebarFilter.toLowerCase();
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
            this.selectedModel = this.selectedModel === name ? null : name;
            this.activeTab = 'overview';
        },

        focusModel(name) {
            this.selectedModel = name;
            this.activeTab = 'overview';
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
            this.dragging = name;
            const canvas = this.$refs.canvas;
            const rect = canvas.getBoundingClientRect();
            this.dragOffset = {
                x: (e.clientX - rect.left + canvas.scrollLeft) / this.zoom - this.positions[name].x,
                y: (e.clientY - rect.top + canvas.scrollTop) / this.zoom - this.positions[name].y,
            };
        },

        onMouseMove(e) {
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
            const newZoom = Math.round(Math.max(0.25, Math.min(2, this.zoom * factor)) * 100) / 100;
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
            this.zoom = Math.round(Math.max(0.25, this.zoom / 1.15) * 100) / 100;
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
