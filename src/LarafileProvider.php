<?php

namespace Grandwebdesign\Larafile;

use Illuminate\Support\ServiceProvider;

class LarafileProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/larafilesystem.php', 'filesystems.disks'
        );
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/larafile.php' => config_path('larafile.php'),
        ], 'config');
    }
}
