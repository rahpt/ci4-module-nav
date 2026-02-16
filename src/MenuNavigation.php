<?php

namespace Rahpt\Ci4ModuleNav;

/**
 * MenuNavigation - Utilitários para navegação e estado dos menus
 */
class MenuNavigation {

    /**
     * Retorna a rota atual limpa
     */
    public static function currentRoute(): string {
        $router = service('router');
        $opts = $router->getMatchedRouteOptions();
        
        // Return the route name (alias) if it exists, otherwise the path
        if (isset($opts['as'])) {
            return $opts['as'];
        }

        $uri = service('request')->getUri();
        return trim($uri->getPath() ?: '', '/');
    }

    /**
     * Verifica se um item de menu está ativo baseando-se na rota
     */
    public static function isActive(string $route): bool {
        $router = service('router');
        $opts = $router->getMatchedRouteOptions();
        $currentName = $opts['as'] ?? null;
        
        $uri = service('request')->getUri();
        $currentPath = trim($uri->getPath() ?: '', '/');
        
        $normalizedRoute = trim($route, '/');

        // Check by Route Name (Alias)
        if ($currentName === $normalizedRoute) {
            return true;
        }

        // Check by URI Path
        if ($normalizedRoute === '') {
            return $currentPath === '';
        }

        return $currentPath === $normalizedRoute || str_starts_with($currentPath, $normalizedRoute . '/');
    }

    /**
     * Gera breadcrumbs a partir da estrutura de menu e rota atual
     */
    public static function getBreadcrumbs(): array {
        $menus = MenuRegistry::all();
        $current = self::currentRoute();
        $breadcrumbs = [['label' => 'Home', 'route' => '/']];

        foreach ($menus as $menu) {
            if (isset($menu['route']) && self::isActive($menu['route'])) {
                 $breadcrumbs[] = ['label' => $menu['label'], 'route' => $menu['route']];
            }
            if (isset($menu['items'])) {
                foreach ($menu['items'] as $sub) {
                    if (isset($sub['route']) && self::isActive($sub['route'])) {
                         $breadcrumbs[] = ['label' => $sub['label'], 'route' => $sub['route']];
                    }
                }
            }
        }

        return $breadcrumbs;
    }
}
