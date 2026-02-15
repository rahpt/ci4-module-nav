<?php

namespace Rahpt\Ci4ModuleNav;

use Rahpt\Ci4Module\Support\ModuleSetupHelper;

/**
 * MenuRegistry - Centralizes module menu discovery
 */
class MenuRegistry {

    /**
     * Returns the consolidated list of menus from all active modules.
     * Implements caching to improve performance.
     */
    public static function all(): array {
        $cache = service('cache');
        $cacheKey = 'module_menus';

        if ($menus = $cache->get($cacheKey)) {
            return $menus;
        }

        $menus = [];
        $registry = service('modules');
        $modules = $registry->getAvailableModules();
        $config = config(\Rahpt\Ci4Module\Config\Modules::class);
        
        foreach ($modules as $name => $data) {
            if (!($data['active'] ?? false)) {
                continue;
            }

            $modulePath = $data['path'] ?? "Modules/" . ucfirst($name);
            $moduleFolder = basename($modulePath);
            $class = $config->baseNamespace . "\\{$moduleFolder}\\Config\\Module";

            if (class_exists($class)) {
                $moduleInstance = new $class();
                if (method_exists($moduleInstance, 'menu')) {
                    foreach ($moduleInstance->menu() as $menu) {
                        $menus[] = $menu;
                    }
                }
            }
        }

        // Cache for 1 hour
        $cache->save($cacheKey, $menus, 3600);

        return $menus;
    }

    /**
     * Clears the menu cache.
     */
    public static function clearCache(): void {
        service('cache')->delete('module_menus');
    }
}
