<?php

namespace Rahpt\Ci4ModuleNav;

/**
 * MenuRegistry - Centralizes module menu discovery with filtering support.
 */
class MenuRegistry
{

    /**
     * Returns the consolidated list of menus from all active modules, filtered by permissions.
     */
    public static function all(): array
    {
        $cache = service('cache');
        $cacheKey = 'module_menus_raw';

        if (!($menus = $cache->get($cacheKey))) {
            $menus = self::discoverMenus();
            $cache->save($cacheKey, $menus, 3600);
        }

        return self::applyFilters($menus);
    }

    /**
     * Discovers all menus from active modules.
     */
    protected static function discoverMenus(): array
    {
        $menus = [];
        $registry = service('modules');
        $modules = $registry->getAvailableModules();
        $config = config(\Rahpt\Ci4Module\Config\Modules::class);

        foreach ($modules as $name => $data) {
            if (!($data['active'] ?? false)) {
                continue;
            }

            $modulePath = $data['path'] ?? $config->basePath . "/" . ucfirst($name);
            $moduleFolder = basename($modulePath);
            $class = $config->baseNamespace . "\\{$moduleFolder}\\Config\\Module";

            if (class_exists($class)) {
                $moduleInstance = new $class();
                if (method_exists($moduleInstance, 'menu')) {
                    foreach ($moduleInstance->menu() as $menu) {
                        // Inject module name for tenancy/permission filtering
                        $menu['_module'] = $name;
                        $menus[] = $menu;
                    }
                }
            }
        }

        return $menus;
    }

    /**
     * Applies visibility filters (permissions, roles, tenancy) to the menu structure.
     */
    protected static function applyFilters(array $menus): array
    {
        $filtered = [];

        foreach ($menus as $item) {
            if (!self::shouldShow($item)) {
                continue;
            }

            if (isset($item['items']) && is_array($item['items'])) {
                $item['items'] = self::applyFilters($item['items']);

                // Optional: hide parent if all children are filtered out
                // if (empty($item['items']) && isset($item['route']) && $item['route'] === '#') {
                //     continue;
                // }
            }

            $filtered[] = $item;
        }

        return $filtered;
    }

    /**
     * Decision engine to check if a menu item should be visible.
     */
    protected static function shouldShow(array $item): bool
    {
        // 1. Check Tenancy (Tenant Profile/Allowed Modules)
        if (isset($item['_module']) && function_exists('tenant')) {
            $tenantId = tenant();
            if ($tenantId) {
                $tenancyConfig = config('Tenancy');
                if (isset($tenancyConfig->tenantProfiles[$tenantId]['modules'])) {
                    $allowedModules = $tenancyConfig->tenantProfiles[$tenantId]['modules'];
                    // If the module is not in the tenant's allowed list, hide it
                    if (!in_array($item['_module'], $allowedModules) && !in_array(ucfirst($item['_module']), $allowedModules)) {
                        return false;
                    }
                }
            }
        }

        // 2. Check Shield Permissions
        if (isset($item['permission']) && function_exists('auth')) {
            $user = auth()->user();
            if (!$user || !$user->can($item['permission'])) {
                return false;
            }
        }

        // 3. Check Shield Groups
        if (isset($item['group']) && function_exists('auth')) {
            $user = auth()->user();
            if (!$user || !$user->inGroup(...(array) $item['group'])) {
                return false;
            }
        }

        // 4. Manual Tenancy Check (Specific Tenant IDs)
        if (isset($item['tenant']) && function_exists('tenant')) {
            $currentTenantId = tenant();
            $allowedTenants = (array) $item['tenant'];

            if ($currentTenantId && !in_array($currentTenantId, $allowedTenants)) {
                return false;
            }
        }

        // 5. Custom Filter Closure
        if (isset($item['filter']) && is_callable($item['filter'])) {
            if (!$item['filter']($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Clears the menu cache.
     */
    public static function clearCache(): void
    {
        service('cache')->delete('module_menus_raw');
    }
}
