<?php

namespace Rahpt\Ci4ModuleNav\Config;

/**
 * Registrar for Navigation Module
 * 
 * Registers module helpers and services with CodeIgniter 4
 */
class Registrar
{
    /**
     * Register helpers
     *
     * @return array
     */
    public static function Helpers(): array
    {
        return [
            'menu_helper' => 'Rahpt\Ci4ModuleNav\Helpers\menu_helper.php'
        ];
    }
}
