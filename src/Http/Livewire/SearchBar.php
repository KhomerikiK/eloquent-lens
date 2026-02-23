<?php

namespace EloquentLens\Http\Livewire;

use Livewire\Component;

class SearchBar extends Component
{
    public string $query = '';

    public function render()
    {
        return view('eloquent-lens::livewire.search-bar');
    }
}
