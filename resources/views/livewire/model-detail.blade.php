{{-- Model Detail Side Panel --}}
<div class="detail-panel">
    {{-- Header --}}
    <div class="detail-header">
        <div class="detail-title-row">
            <div>
                <div class="detail-name" x-text="selectedModel" :title="selectedModel"></div>
                <div class="detail-namespace" x-text="selectedModelData.namespace + '\\' + selectedModel" :title="selectedModelData.namespace + '\\' + selectedModel"></div>
            </div>
            <button class="btn-close" @click="selectedModel = null">✕</button>
        </div>

        {{-- Complexity Meter --}}
        <div class="complexity-meter">
            <div class="complexity-header">
                <span class="complexity-label">Complexity Score</span>
                <span class="complexity-value"
                      :style="'color:' + complexityColor(selectedModelData.complexity)"
                      x-text="selectedModelData.complexity + '/100 · ' + complexityLabel(selectedModelData.complexity)">
                </span>
            </div>
            <div class="complexity-track">
                <div
                    class="complexity-fill"
                    :style="'width:' + selectedModelData.complexity + '%;background:linear-gradient(90deg,' + complexityColor(selectedModelData.complexity) + '88,' + complexityColor(selectedModelData.complexity) + ')'"
                ></div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-value" x-text="selectedModelData.linesOfCode"></div>
                <div class="stat-card-label">LOC</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-value" x-text="Object.keys(selectedModelData.relationships).length"></div>
                <div class="stat-card-label">Relations</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-value" x-text="selectedModelData.fillable.length"></div>
                <div class="stat-card-label">Fields</div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="tabs">
        <template x-for="tab in [{id:'overview',label:'Overview'},{id:'relationships',label:'Relations'},{id:'behavior',label:'Behavior'},{id:'security',label:'Security'}]" :key="tab.id">
            <button
                class="tab-btn"
                :class="{ 'active': activeTab === tab.id }"
                @click="activeTab = tab.id"
                x-text="tab.label"
            ></button>
        </template>
    </div>

    {{-- Tab Content --}}
    <div class="tab-content">

        {{-- Overview Tab --}}
        <template x-if="activeTab === 'overview'">
            <div>
                {{-- Table Name --}}
                <div class="section">
                    <div class="section-title">Table</div>
                    <div style="font-size: 12px; color: var(--accent-light); background: var(--accent-bg); display: inline-block; padding: 4px 10px; border-radius: 6px;">
                        <span x-text="selectedModelData.table"></span>
                    </div>
                </div>

                {{-- Traits --}}
                <div class="section">
                    <div class="section-title">Traits</div>
                    <div class="tag-list">
                        <template x-for="trait in selectedModelData.traits" :key="trait">
                            <span
                                class="tag"
                                :style="trait === 'SoftDeletes'
                                    ? 'color:#ef4444;background:rgba(239,68,68,0.08);border-color:rgba(239,68,68,0.2)'
                                    : trait === 'Searchable'
                                    ? 'color:#3b82f6;background:rgba(59,130,246,0.08);border-color:rgba(59,130,246,0.2)'
                                    : 'color:#64748b;background:rgba(100,116,139,0.08);border-color:rgba(100,116,139,0.2)'"
                                x-text="trait"
                            ></span>
                        </template>
                        <template x-if="selectedModelData.traits.length === 0">
                            <span class="empty-state">No traits</span>
                        </template>
                    </div>
                </div>

                {{-- Casts --}}
                <div class="section">
                    <div class="section-title">Casts</div>
                    <template x-for="(type, field) in selectedModelData.casts" :key="field">
                        <div style="display: flex; justify-content: space-between; padding: 4px 0;">
                            <span style="font-size: 12px; color: var(--text-secondary)" x-text="field"></span>
                            <span
                                style="font-size: 11px; color: var(--accent); background: var(--accent-bg); padding: 1px 8px; border-radius: 4px;"
                                x-text="type"
                            ></span>
                        </div>
                    </template>
                    <template x-if="Object.keys(selectedModelData.casts).length === 0">
                        <span class="empty-state">No casts defined</span>
                    </template>
                </div>

                {{-- Accessors --}}
                <div class="section">
                    <div class="section-title">Accessors</div>
                    <template x-for="accessor in selectedModelData.accessors" :key="accessor">
                        <div style="font-size: 12px; color: var(--success); padding: 2px 0;">
                            → $<span x-text="accessor"></span>
                        </div>
                    </template>
                    <template x-if="selectedModelData.accessors.length === 0">
                        <span class="empty-state">No accessors</span>
                    </template>
                </div>

                {{-- Mutators --}}
                <div class="section">
                    <div class="section-title">Mutators</div>
                    <template x-for="mutator in selectedModelData.mutators" :key="mutator">
                        <div style="font-size: 12px; color: var(--rel-has-many); padding: 2px 0;">
                            ← $<span x-text="mutator"></span>
                        </div>
                    </template>
                    <template x-if="selectedModelData.mutators.length === 0">
                        <span class="empty-state">No mutators</span>
                    </template>
                </div>

                {{-- Fillable Properties --}}
                <template x-if="selectedModelData.fillable && selectedModelData.fillable.length > 0">
                    <div class="section">
                        <div class="section-title">Fillable Properties</div>
                        <div class="tag-list">
                            <template x-for="field in selectedModelData.fillable" :key="field">
                                <span class="tag" style="color:#22c55e;background:rgba(34,197,94,0.06);border-color:rgba(34,197,94,0.12)" x-text="field"></span>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        {{-- Relationships Tab --}}
        <template x-if="activeTab === 'relationships'">
            <div>
                <template x-for="(rel, relName) in selectedModelData.relationships" :key="relName">
                    <div class="relation-card">
                        <div class="relation-header">
                            <span class="relation-name" x-text="relName"></span>
                            <span
                                class="relation-type"
                                :style="'color:' + relTypeColor(rel.type) + ';background:' + relTypeColor(rel.type) + '15'"
                                x-text="relTypeLabel(rel.type)"
                            ></span>
                        </div>
                        <div class="relation-target">
                            → <span x-text="rel.model || 'dynamic (morphTo)'"></span>
                            <template x-if="rel.pivot">
                                <span style="color: var(--text-dim);">&nbsp;(pivot: <span x-text="rel.pivot"></span>)</span>
                            </template>
                        </div>
                    </div>
                </template>
                <template x-if="Object.keys(selectedModelData.relationships).length === 0">
                    <span class="empty-state">No relationships defined</span>
                </template>
            </div>
        </template>

        {{-- Behavior Tab --}}
        <template x-if="activeTab === 'behavior'">
            <div>
                {{-- Scopes --}}
                <div class="section">
                    <div class="section-title">Local Scopes</div>
                    <template x-for="scope in selectedModelData.scopes" :key="scope">
                        <div class="scope-item">
                            <div class="scope-name" x-text="scope + '()'"></div>
                        </div>
                    </template>
                    <template x-if="selectedModelData.scopes.length === 0">
                        <span class="empty-state">No local scopes</span>
                    </template>
                </div>

                {{-- Global Scopes --}}
                <div class="section">
                    <div class="section-title">Global Scopes ⚠</div>
                    <template x-for="scope in selectedModelData.globalScopes" :key="scope">
                        <div style="display: flex; align-items: center; gap: 6px; padding: 3px 0;">
                            <span class="global-scope-dot"></span>
                            <span style="font-size: 12px; color: #fca5a5;" x-text="scope"></span>
                        </div>
                    </template>
                    <template x-if="selectedModelData.globalScopes.length === 0">
                        <span class="empty-state">No global scopes — queries are transparent</span>
                    </template>
                </div>

                {{-- Observers --}}
                <div class="section">
                    <div class="section-title">Observers & Events</div>
                    <template x-for="observer in selectedModelData.observers" :key="observer">
                        <div class="observer-item">
                            <span class="observer-handler" x-text="observer"></span>
                        </div>
                    </template>
                    <template x-if="selectedModelData.observers.length === 0">
                        <span class="empty-state">No observers registered</span>
                    </template>
                </div>

                {{-- Custom Methods --}}
                <div class="section">
                    <div class="section-title">Methods</div>
                    <template x-for="method in selectedModelData.methods" :key="method.name">
                        <div style="display: flex; align-items: baseline; gap: 4px; padding: 3px 0; border-bottom: 1px solid var(--border);">
                            <span style="font-size: 12px; font-weight: 600; color: var(--text-primary); white-space: nowrap;" x-text="method.name"></span>
                            <span style="font-size: 10px; color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" x-text="method.params"></span>
                        </div>
                    </template>
                    <template x-if="!selectedModelData.methods || selectedModelData.methods.length === 0">
                        <span class="empty-state">No custom methods</span>
                    </template>
                </div>

                {{-- Timestamps & SoftDeletes --}}
                <div class="section">
                    <div class="section-title">Model Flags</div>
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span :style="selectedModelData.hasTimestamps ? 'color:var(--success)' : 'color:var(--text-dim)'"
                                  x-text="selectedModelData.hasTimestamps ? '✓' : '✗'"></span>
                            <span style="font-size: 12px; color: var(--text-secondary);">Timestamps</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span :style="selectedModelData.hasSoftDeletes ? 'color:var(--danger)' : 'color:var(--text-dim)'"
                                  x-text="selectedModelData.hasSoftDeletes ? '✓' : '✗'"></span>
                            <span style="font-size: 12px; color: var(--text-secondary);">Soft Deletes</span>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        {{-- Security Tab --}}
        <template x-if="activeTab === 'security'">
            <div>
                {{-- Fillable --}}
                <div class="section">
                    <div class="section-title">Fillable</div>
                    <div class="tag-list">
                        <template x-for="field in selectedModelData.fillable" :key="field">
                            <span class="tag" style="color:#22c55e;background:rgba(34,197,94,0.08);border-color:rgba(34,197,94,0.2)" x-text="field"></span>
                        </template>
                        <template x-if="selectedModelData.fillable.length === 0">
                            <span class="empty-state">No fillable fields</span>
                        </template>
                    </div>
                </div>

                {{-- Guarded --}}
                <div class="section">
                    <div class="section-title">Guarded</div>
                    <div class="tag-list">
                        <template x-for="field in selectedModelData.guarded" :key="field">
                            <span class="tag" style="color:#f59e0b;background:rgba(245,158,11,0.08);border-color:rgba(245,158,11,0.2)" x-text="field"></span>
                        </template>
                        <template x-if="selectedModelData.guarded.length === 0">
                            <span class="empty-state">No guarded fields (check $fillable)</span>
                        </template>
                    </div>
                </div>

                {{-- Hidden --}}
                <div class="section">
                    <div class="section-title">Hidden from Serialization</div>
                    <div class="tag-list">
                        <template x-for="field in selectedModelData.hidden" :key="field">
                            <span class="tag" style="color:#ef4444;background:rgba(239,68,68,0.08);border-color:rgba(239,68,68,0.2)" x-text="field"></span>
                        </template>
                        <template x-if="selectedModelData.hidden.length === 0">
                            <span class="empty-state">Nothing hidden</span>
                        </template>
                    </div>
                </div>

                {{-- Policies --}}
                <div class="section">
                    <div class="section-title">Policies</div>
                    <template x-for="policy in selectedModelData.policies" :key="policy">
                        <div style="font-size: 12px; color: var(--rel-belongs-to-many); padding: 2px 0;">
                            🛡 <span x-text="policy"></span>
                        </div>
                    </template>
                    <template x-if="selectedModelData.policies.length === 0">
                        <div class="warning-box">
                            ⚠ No policy defined — this model has no authorization layer
                        </div>
                    </template>
                </div>

                {{-- Mass Assignment Check --}}
                <template x-if="selectedModelData.guarded.length === 1 && selectedModelData.guarded[0] === '*'">
                    <div class="section">
                        <div style="font-size: 11px; color: var(--success); background: rgba(34,197,94,0.06); padding: 8px 12px; border-radius: 6px; border: 1px solid rgba(34,197,94,0.18);">
                            ✓ Fully guarded — mass assignment protected
                        </div>
                    </div>
                </template>
                <template x-if="selectedModelData.guarded.length === 0 && selectedModelData.fillable.length === 0">
                    <div class="section">
                        <div class="warning-box">
                            ⚠ Neither $fillable nor $guarded defined — mass assignment vulnerable
                        </div>
                    </div>
                </template>
            </div>
        </template>

    </div>
</div>
