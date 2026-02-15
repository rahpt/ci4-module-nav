<?php

use Rahpt\Ci4ModuleNav\MenuNavigation;
use Rahpt\Ci4ModuleNav\MenuRegistry;

if (!function_exists('current_route')) {
    function current_route(): string {
        return MenuNavigation::currentRoute();
    }
}

if (!function_exists('menu_is_active')) {
    function menu_is_active(string $route): bool {
        return MenuNavigation::isActive($route);
    }
}

if (!function_exists('breadcrumb_from_menu')) {
    function breadcrumb_from_menu(): array {
        return MenuNavigation::getBreadcrumbs();
    }
}

if (!function_exists('render_menu')) {
    /**
     * Renders the complete menu transition list.
     */
    function render_menu(): array {
        return MenuRegistry::all();
    }
}
