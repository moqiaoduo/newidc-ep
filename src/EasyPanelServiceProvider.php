<?php

namespace NewIDC\EasyPanel;

use Illuminate\Support\ServiceProvider;
use NewIDC\Plugin\Facade\PluginManager;

class EasyPanelServiceProvider extends ServiceProvider
{
    public function boot()
    {
        PluginManager::register(new Plugin());
    }
}