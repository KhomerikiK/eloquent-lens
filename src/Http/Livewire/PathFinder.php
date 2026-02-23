<?php

namespace EloquentLens\Http\Livewire;

use Livewire\Component;
use EloquentLens\Services\ModelParser;

class PathFinder extends Component
{
    public string $from = '';
    public string $to = '';
    public ?array $results = null;
    public array $models = [];

    public function mount()
    {
        $this->models = app(ModelParser::class)->parse();
    }

    public function findPaths(): void
    {
        if (! $this->from || ! $this->to || $this->from === $this->to) {
            return;
        }

        $this->results = [];
        $this->dfs($this->from, [], new \SplStack());
    }

    protected function dfs(string $current, array $path, \SplStack $visited): void
    {
        if ($current === $this->to) {
            $this->results[] = $path;
            return;
        }

        if (count($path) > 5) return;

        $visited->push($current);

        $model = $this->models[$current] ?? null;
        if (! $model) {
            $visited->pop();
            return;
        }

        foreach ($model['relationships'] as $relName => $rel) {
            if (! $rel['model']) continue;

            $alreadyVisited = false;
            foreach ($visited as $v) {
                if ($v === $rel['model']) {
                    $alreadyVisited = true;
                    break;
                }
            }

            if (! $alreadyVisited) {
                $path[] = [
                    'from' => $current,
                    'rel'  => $relName,
                    'type' => $rel['type'],
                    'to'   => $rel['model'],
                ];
                $this->dfs($rel['model'], $path, $visited);
                array_pop($path);
            }
        }

        $visited->pop();
    }

    public function render()
    {
        return view('eloquent-lens::livewire.path-finder');
    }
}
