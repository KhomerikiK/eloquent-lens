<?php

namespace EloquentLens\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'eloquent-lens:install';
    protected $description = 'Install EloquentLens package assets and configuration';

    public function handle(): int
    {
        $this->info('Installing EloquentLens...');

        // Publish config
        $this->callSilently('vendor:publish', [
            '--tag' => 'eloquent-lens-config',
        ]);
        $this->info('✓ Configuration published');

        // Publish assets
        $this->callSilently('vendor:publish', [
            '--tag' => 'eloquent-lens-assets',
        ]);
        $this->info('✓ Assets published');

        $this->newLine();
        $this->info('EloquentLens installed successfully!');
        $this->info('Visit /eloquent-lens to see your model visualizer.');

        return self::SUCCESS;
    }
}
