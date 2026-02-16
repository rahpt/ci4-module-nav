<?php

namespace Rahpt\Ci4ModuleNav\Config;

/**
 * Registrar - Autoconfiguração de componentes do pacote ci4-module-nav no CodeIgniter 4.
 */
class Registrar
{
    /**
     * Register helpers
     */
    public static function Helpers(): array
    {
        return [
            'menu_helper' => 'Rahpt\Ci4ModuleNav\Helpers\menu_helper.php'
        ];
    }

    /**
     * Register events
     */
    public static function Events(): array
    {
        return [
            'rahpt.module.changed' => [
                'Rahpt\Ci4ModuleNav\Support\EventHandlers::onModuleChanged'
            ],
            'rahpt.module.activated' => [
                'Rahpt\Ci4ModuleNav\Support\EventHandlers::onModuleChanged'
            ],
            'rahpt.module.deactivated' => [
                'Rahpt\Ci4ModuleNav\Support\EventHandlers::onModuleChanged'
            ],
        ];
    }
}
