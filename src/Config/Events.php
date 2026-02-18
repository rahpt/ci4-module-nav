<?php

namespace Rahpt\Ci4ModuleNav\Config;

use CodeIgniter\Events\Events;
use Rahpt\Ci4ModuleNav\Support\EventHandlers;

/*
 * Listeners for module registry events
 */
log_message('debug', 'Rahpt/Ci4ModuleNav/Config/Events.php loaded');
Events::on('rahpt.module.changed', [EventHandlers::class, 'onModuleChanged']);
Events::on('rahpt.module.activated', [EventHandlers::class, 'onModuleChanged']);
Events::on('rahpt.module.deactivated', [EventHandlers::class, 'onModuleChanged']);
