<?php

namespace EloquentLens\Http\Livewire;

use Livewire\Component;

class ModelDetail extends Component
{
    public string $modelName = '';

    public array $modelData = [];

    public function mount(string $modelName, array $modelData)
    {
        $this->modelName = $modelName;
        $this->modelData = $modelData;
    }

    public function render()
    {
        return view('eloquent-lens::livewire.model-detail');
    }
}
