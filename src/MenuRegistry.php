<?php

namespace Rahpt\Ci4ModuleNav;

/**
 * MenuRegistry - Centralizes module menu discovery with filtering support.
 */
class MenuRegistry
{

    /**
     * Cache version key for atomic cache busting across all users and tenants.
     */
    protected const CACHE_VERSION_KEY = 'module_menus_version';

    /**
     * Returns the consolidated list of menus from all active modules, filtered by permissions and tenant.
     * Note: Menu visibility is a presentation layer concern and does NOT replace Controller/Policy authorization.
     */
    public static function all(): array
    {
        $cache = service('cache');
        $version = (int) ($cache->get(self::CACHE_VERSION_KEY) ?? 1);

        $user = function_exists('auth') ? auth()->user() : null;
        $userId = $user ? $user->id : 'guest';
        $tenantId = function_exists('tenant') && tenant() ? (string) tenant() : 'global';

        // ACL versioning: Incorporate groups and permissions hash so role changes invalidate cache immediately
        $aclHash = 'guest';
        if ($user) {
            $permissions = method_exists($user, 'getPermissions') ? $user->getPermissions() : [];
            $groups = method_exists($user, 'getGroups') ? $user->getGroups() : [];
            $aclHash = substr(md5(implode(',', $groups) . ':' . implode(',', $permissions)), 0, 8);
        }

        // Contextual cache key incorporating version, tenant, user ID, and ACL hash
        $cacheKey = "module_menus_v{$version}_{$tenantId}_{$userId}_acl{$aclHash}";

        if (($cachedMenus = $cache->get($cacheKey)) !== null) {
            return $cachedMenus;
        }

        // Retrieve raw menus (cached across users within the current version)
        $rawCacheKey = "module_menus_raw_v{$version}";
        if (($rawMenus = $cache->get($rawCacheKey)) === null) {
            $rawMenus = self::discoverMenus();
            $cache->save($rawCacheKey, $rawMenus, 3600);
        }

        $filtered = self::applyFilters($rawMenus);

        // Cache filtered menu for this user/tenant context
        $cache->save($cacheKey, $filtered, 3600);

        return $filtered;
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
                $priority = $moduleInstance->priority ?? 0;

                if (method_exists($moduleInstance, 'menu')) {
                    foreach ($moduleInstance->menu() as $menu) {
                        // Inject module name for tenancy/permission filtering
                        $menu['_module'] = $name;
                        $menu['_priority'] = $priority;
                        $menus[] = $menu;
                    }
                }
            }
        }

        // Sort menus by priority (ascending)
        usort($menus, function ($a, $b) {
            $pa = $a['_priority'] ?? 0;
            $pb = $b['_priority'] ?? 0;
            return $pa <=> $pb;
        });

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
            }

            // Security: sanitize labels
            if (isset($item['label']) && function_exists('esc')) {
                $item['label_escaped'] = esc($item['label'], 'html');
            }

            // Safe Icon allowlisting
            if (isset($item['icon'])) {
                $item['icon_class'] = \Rahpt\Ci4ModuleNav\Support\IconRegistry::resolve($item['icon']);
            }

            // Route-to-URL normalization
            if (isset($item['route']) && empty($item['url']) && function_exists('url_to')) {
                try {
                    $item['url'] = url_to($item['route']);
                } catch (\Throwable) {
                    $item['url'] = $item['route'];
                }
            }

            // Prevent dangerous URI schemes (e.g. javascript:)
            if (isset($item['url']) && preg_match('/^(javascript|data|vbscript):/i', trim($item['url']))) {
                $item['url'] = '#';
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
                    if (!in_array($item['_module'], $allowedModules, true) && !in_array(ucfirst($item['_module']), $allowedModules, true)) {
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

        // 4. Tenancy Check (supports boolean requirement or specific tenant list)
        if (isset($item['tenant'])) {
            $hasActiveTenant = function_exists('tenant') && tenant() !== null && tenant() !== '';

            if (is_bool($item['tenant'])) {
                if ($item['tenant'] === true && !$hasActiveTenant) {
                    return false; // Requires active tenant
                }
                if ($item['tenant'] === false && $hasActiveTenant) {
                    return false; // Global only
                }
            } else {
                $allowedTenants = (array) $item['tenant'];
                $currentTenantId = function_exists('tenant') ? (string) tenant() : '';
                if (!in_array($currentTenantId, $allowedTenants, true)) {
                    return false;
                }
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
     * Returns menus organized by hierarchical structure: section -> group -> items.
     * Items are sorted by priority ascending.
     *
     * @return array<string, array<string, list<array>>>
     */
    public static function grouped(): array
    {
        $all = self::all();
        $grouped = [];

        foreach ($all as $item) {
            $section = $item['section'] ?? 'Main';
            $group = $item['group'] ?? 'Default';

            if (!isset($grouped[$section])) {
                $grouped[$section] = [];
            }
            if (!isset($grouped[$section][$group])) {
                $grouped[$section][$group] = [];
            }

            $grouped[$section][$group][] = $item;
        }

        // Sort items inside groups by priority
        foreach ($grouped as $sec => $groups) {
            foreach ($groups as $grp => $items) {
                usort($grouped[$sec][$grp], fn ($a, $b) => ($a['priority'] ?? $a['_priority'] ?? 0) <=> ($b['priority'] ?? $b['_priority'] ?? 0));
            }
        }

        return $grouped;
    }

    /**
     * Atomically clears the menu cache across all tenants and users.
     * Increments the cache version to instantly invalidate existing entries.
     */
    public static function clearCache(): void
    {
        $cache = service('cache');
        $currentVersion = (int) ($cache->get(self::CACHE_VERSION_KEY) ?? 1);
        $nextVersion = $currentVersion + 1;

        // Save new version to invalidate all current cache keys in O(1)
        $cache->save(self::CACHE_VERSION_KEY, $nextVersion, 86400 * 30);

        // Delete raw cache for previous version
        $cache->delete("module_menus_raw_v{$currentVersion}");
        $cache->delete('module_menus_raw');
    }
}
