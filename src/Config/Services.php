<?php

namespace Rahpt\Ci4ModuleNav\Config;

use CodeIgniter\Config\BaseService;
use Rahpt\Ci4ModuleNav\MenuRegistry;
use Rahpt\Ci4ModuleNav\MenuNavigation;

class Services extends BaseService
{
    public static function menus($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('menus');
        }
        return new MenuRegistry();
    }

    public static function navigation($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('navigation');
        }
        return new MenuNavigation();
    }
}
