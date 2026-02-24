<?php

namespace EloquentLens\Http\Livewire;

use EloquentLens\Services\ModelParser;
use Livewire\Component;

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
