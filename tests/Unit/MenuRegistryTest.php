<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Rahpt\Ci4ModuleNav\MenuRegistry;

class MenuRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the 'service' function if not available
        if (!function_exists('service')) {
            function service($name) {
                static $services = [];
                if (!isset($services[$name])) {
                    $services[$name] = new class($name) {
                        public function __construct(public string $n) {}
                        public function get($k) { return null; }
                        public function save($k, $v, $t) { return true; }
                        public function put($m, $d) {}
                        public function all($m = null) { return []; }
                        public function getAvailableModules() { return []; }
                    };
                }
                return $services[$name];
            }
        }

        if (!function_exists('config')) {
            function config($name) {
                return new class {
                    public string $baseNamespace = 'App\\Modules';
                    public string $basePath = 'Modules';
                };
            }
        }
    }

    public function testAllReturnsArray()
    {
        $menus = MenuRegistry::all();
        $this->assertIsArray($menus);
    }
}
