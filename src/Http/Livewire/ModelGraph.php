<?php

namespace EloquentLens\Http\Livewire;

use Livewire\Component;
use EloquentLens\Services\ModelParser;

class ModelGraph extends Component
{
    public array $models = [];

    public function mount()
    {
        $this->models = app(ModelParser::class)->parse();
    }

    public function render()
    {
        return view('eloquent-lens::livewire.model-graph');
    }
}
