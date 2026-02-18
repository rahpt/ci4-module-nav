<?php

namespace Rahpt\Ci4ModuleNav\Support;

use Rahpt\Ci4ModuleNav\MenuRegistry;

/**
 * EventHandlers - Listeners para eventos de módulos do ecossistema Rahpt.
 */
class EventHandlers
{
    /**
     * Limpa o cache de menus quando um módulo é alterado.
     */
    public static function onModuleChanged(): void
    {
        log_message('debug', 'MenuRegistry::clearCache() triggered by event');
        MenuRegistry::clearCache();
    }
}
