<?php

namespace Rahpt\Ci4ModuleNav\Support;

/**
 * IconRegistry - Secure allowlist and renderer for menu icons.
 * Prevents arbitrary HTML or attribute injection in menu icon definitions.
 */
class IconRegistry
{
    /**
     * Map of logical icon identifiers to safe CSS classes.
     */
    protected static array $icons = [
        'home'       => 'fas fa-home',
        'dashboard'  => 'fas fa-tachometer-alt',
        'users'      => 'fas fa-users',
        'user'       => 'fas fa-user',
        'settings'   => 'fas fa-cog',
        'cog'        => 'fas fa-cog',
        'tools'      => 'fas fa-tools',
        'file'       => 'fas fa-file',
        'contracts'  => 'fas fa-file-contract',
        'calendar'   => 'fas fa-calendar',
        'tasks'      => 'fas fa-tasks',
        'folder'     => 'fas fa-folder',
        'database'   => 'fas fa-database',
        'chart'      => 'fas fa-chart-bar',
        'shield'     => 'fas fa-shield-alt',
        'lock'       => 'fas fa-lock',
        'bell'       => 'fas fa-bell',
        'mail'       => 'fas fa-envelope',
        'box'        => 'fas fa-box',
        'building'   => 'fas fa-building',
        'store'      => 'fas fa-store',
        'circle'     => 'fas fa-circle',
    ];

    /**
     * Registers or overrides a safe icon mapping.
     */
    public static function register(string $name, string $cssClass): void
    {
        $name = strtolower(trim($name));
        // Sanitize CSS class to prevent attribute breakout
        $cleanClass = preg_replace('/[^a-zA-Z0-9_\-\s]/', '', $cssClass);
        self::$icons[$name] = $cleanClass;
    }

    /**
     * Resolves an icon name to its safe CSS class.
     */
    public static function resolve(string $name): string
    {
        $normalized = strtolower(trim($name));

        if (isset(self::$icons[$normalized])) {
            return self::$icons[$normalized];
        }

        // If the user already provided standard fontawesome class names, validate format
        if (preg_match('/^(fas|far|fal|fad|fab|bi|fa)\s+fa-[a-z0-9_\-]+$/', $normalized)) {
            return $normalized;
        }

        return 'fas fa-circle';
    }

    /**
     * Renders safe <i> tag for the given icon name.
     */
    public static function render(string $name, string $extraClass = ''): string
    {
        $class = self::resolve($name);
        if ($extraClass !== '') {
            $class .= ' ' . preg_replace('/[^a-zA-Z0-9_\-\s]/', '', $extraClass);
        }

        return '<i class="' . $class . '"></i>';
    }
}
