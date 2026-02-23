{{-- Path Finder Modal --}}
<div class="modal-overlay" @click.self="showPathFinder = false" @keydown.escape.window="showPathFinder = false">
    <div class="modal-content">
        {{-- Header --}}
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div class="modal-title">Relationship Path Finder</div>
            <button class="btn-close" @click="showPathFinder = false">✕</button>
        </div>

        <p style="font-size: 11px; color: var(--text-muted); margin-bottom: 16px;">
            Find how two models are connected through Eloquent relationships. Discovers all paths up to 5 hops deep.
        </p>

        {{-- Selects --}}
        <div style="display: flex; gap: 12px; margin-bottom: 16px;">
            <select x-model="pathFrom" class="filter-select" style="flex: 1; height: 40px;">
                <option value="">From model...</option>
                <template x-for="name in modelNames" :key="'pf-'+name">
                    <option :value="name" x-text="name"></option>
                </template>
            </select>

            <span style="color: var(--text-muted); align-self: center; font-size: 18px;">→</span>

            <select x-model="pathTo" class="filter-select" style="flex: 1; height: 40px;">
                <option value="">To model...</option>
                <template x-for="name in modelNames" :key="'pt-'+name">
                    <option :value="name" x-text="name"></option>
                </template>
            </select>
        </div>

        {{-- Find Button --}}
        <button
            class="btn btn-accent"
            style="width: 100%; height: 40px; font-size: 13px; margin-bottom: 20px;"
            :style="(!pathFrom || !pathTo || pathFrom === pathTo) ? 'opacity:0.4;cursor:not-allowed' : ''"
            @click="findPaths()"
            :disabled="!pathFrom || !pathTo || pathFrom === pathTo"
        >
            Find Paths
        </button>

        {{-- Results --}}
        <template x-if="pathResults !== null">
            <div>
                <div style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 12px;">
                    <span x-text="pathResults.length"></span> path<span x-show="pathResults.length !== 1">s</span> found
                </div>

                <template x-for="(path, i) in pathResults" :key="'path-'+i">
                    <div class="path-result">
                        <div style="font-size: 10px; color: var(--text-muted); margin-bottom: 8px;">
                            Path <span x-text="i + 1"></span> · <span x-text="path.length"></span> hop<span x-show="path.length !== 1">s</span>
                        </div>

                        <template x-for="(step, j) in path" :key="'step-'+i+'-'+j">
                            <div class="path-step">
                                <span class="path-model-name" x-text="step.from"></span>
                                <span class="path-relation" :style="'color:' + relTypeColor(step.type)">
                                    —<span x-text="step.rel"></span>→
                                </span>
                                <span class="path-model-name" x-text="step.to"></span>
                            </div>
                        </template>

                        {{-- Eloquent chain --}}
                        <div class="path-eloquent">
                            $<span x-text="pathFrom.toLowerCase()"></span>-><span x-text="path.map(s => s.rel).join('->')"></span>
                        </div>
                    </div>
                </template>

                <template x-if="pathResults.length === 0">
                    <div style="font-size: 12px; color: var(--text-muted); text-align: center; padding: 24px 0;">
                        No path found between these models. They may be in separate domains.
                    </div>
                </template>
            </div>
        </template>
    </div>
</div>
