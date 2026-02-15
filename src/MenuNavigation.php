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
        $uri = service('request')->getUri();
        return trim($uri->getPath() ?: '', '/');
    }

    /**
     * Verifica se um item de menu está ativo baseando-se na rota
     */
    public static function isActive(string $route): bool {
        $current = self::currentRoute();
        $normalizedRoute = trim($route, '/');

        if ($normalizedRoute === '') {
            return $current === '';
        }

        // Verifica se a rota atual começa com a rota do menu
        // Ex: current = 'system/modules/marketplace', route = 'system/modules' -> true
        return $current === $normalizedRoute || str_starts_with($current, $normalizedRoute . '/');
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
