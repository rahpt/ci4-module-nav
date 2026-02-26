# CodeIgniter 4 Module Navigation

[![Version](https://img.shields.io/badge/version-1.2.0-blue.svg)](https://github.com/rahpt/ci4-module-nav)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%3E%3D8.1-brightgreen.svg)](https://php.net)

Sistema de navegação e breadcrumbs para módulos CodeIgniter 4. Consolida menus de todos os módulos ativos e gerencia breadcrumbs automaticamente.

---

## 📋 Características

- ✅ **Menu Consolidado** - Agrega menus de todos os módulos ativos
- ✅ **Breadcrumbs Automáticos** - Sistema de breadcrumbs com helper functions
- ✅ **Caching** - Cache de menus para melhor performance (1 hora)
- ✅ **Auto-Discovery** - Descobre menus automaticamente de módulos
- ✅ **Flexível** - Suporta menus hierárquicos e customizados

---

## 🚀 Instalação

```bash
composer require rahpt/ci4-module-nav
```

---

## 📖 Uso Básico

### Definir Menu no Módulo

```php
// app/Modules/Dashboard/Config/Module.php
class Module extends BaseModule
{
    public function menu(): array
    {
        return [
            [
                'label' => 'Dashboard',
                'url' => 'dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'order' => 1
            ],
            [
                'label' => 'Relatórios',
                'url' => 'dashboard/reports',
                'icon' => 'fas fa-chart-bar',
                'order' => 2,
                'children' => [
                    [
                        'label' => 'Vendas',
                        'url' => 'dashboard/reports/sales'
                    ],
                    [
                        'label' => 'Financeiro',
                        'url' => 'dashboard/reports/financial'
                    ]
                ]
            ]
        ];
    }
}
```

### Exibir Menu na View

```php
<?php
use Rahpt\Ci4ModuleNav\MenuRegistry;

$menus = MenuRegistry::all();
?>

<nav>
    <ul>
        <?php foreach ($menus as $item): ?>
            <li>
                <a href="<?= base_url($item['url']) ?>">
                    <?php if(isset($item['icon'])): ?>
                        <i class="<?= $item['icon'] ?>"></i>
                    <?php endif; ?>
                    <?= $item['label'] ?>
                </a>
                
                <?php if(isset($item['children'])): ?>
                    <ul>
                        <?php foreach ($item['children'] as $child): ?>
                            <li>
                                <a href="<?= base_url($child['url']) ?>">
                                    <?= $child['label'] ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
```

### Breadcrumbs

```php
// No Controller
set_breadcrumb('Home', '/');
set_breadcrumb('Dashboard', 'dashboard');
set_breadcrumb('Relatórios'); // Sem URL = item atual

// Na View
<?= render_breadcrumbs() ?>

// Output:
// Home > Dashboard > Relatórios
```

---

## 🎨 Helper Functions

### set_breadcrumb()

Adiciona item ao breadcrumb.

```php
set_breadcrumb(string $label, ?string $url = null): void
```

**Exemplos**:
```php
set_breadcrumb('Home', '/');
set_breadcrumb('Produtos', 'products');
set_breadcrumb('Editar'); // Página atual
```

### render_breadcrumbs()

Renderiza HTML dos breadcrumbs.

```php
render_breadcrumbs(string $separator = '>'): string
```

**Customizar**:
```php
// Com separador customizado
<?= render_breadcrumbs(' / ') ?>
// Home / Dashboard / Relatórios

// Com classes CSS
<?= render_breadcrumbs(' > ', 'breadcrumb-list') ?>
```

---

## ⚡ Performance: Caching

### Cache Automático

Menus são automaticamente cacheados por **1 hora**.

```php
// Primeira chamada: Busca de todos os módulos
$menus = MenuRegistry::all();

// Próximas chamadas (dentro de 1h): Retorna do cache
$menus = MenuRegistry::all(); // Instantâneo!
```

### Limpar Cache

Quando um módulo é ativado/desativado, o cache é automaticamente limpo.

**Manual**:
```php
MenuRegistry::clearCache();
```

---

## 🔧 API Reference

### MenuRegistry::all()

Retorna array consolidado de todos os menus dos módulos ativos.

```php
$menus = MenuRegistry::all();
// [
//     [
//         'label' => 'Dashboard',  
//         'url' => 'dashboard',
//         'icon' => 'fas fa-tachometer-alt',
//         'order' => 1
//     ],
//     ...
// ]
```

### MenuRegistry::clearCache()

Limpa o cache de menus.

```php
MenuRegistry::clearCache();
```

---

## 📦 Integração com Layouts

### AdminLTE Example

```php
<!-- app/Views/layouts/adminlte.php -->
<aside class="main-sidebar sidebar-dark-primary">
    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column">
                <?php
                use Rahpt\Ci4ModuleNav\MenuRegistry;
                $menus = MenuRegistry::all();
                
                foreach ($menus as $item):
                ?>
                    <li class="nav-item">
                        <a href="<?= base_url($item['url']) ?>" class="nav-link">
                            <i class="nav-icon <?= $item['icon'] ?? 'fas fa-circle' ?>"></i>
                            <p><?= $item['label'] ?></p>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </div>
</aside>
```

### Bootstrap Example

```php
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <?php
        $breadcrumbs = get_breadcrumbs();
        $count = count($breadcrumbs);
        
        foreach ($breadcrumbs as $index => $crumb):
            $isLast = ($index === $count - 1);
        ?>
            <li class="breadcrumb-item <?= $isLast ? 'active' : '' ?>">
                <?php if ($isLast || empty($crumb['url'])): ?>
                    <?= $crumb['label'] ?>
                <?php else: ?>
                    <a href="<?= base_url($crumb['url']) ?>">
                        <?= $crumb['label'] ?>
                    </a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
```

---

## 🎨 Customização Avançada

### Ordenação de Menus

```php
public function menu(): array
{
    return [
        [
            'label' => 'Dashboard',
            'order' => 1  // Primeiro
        ],
        [
            'label' => 'Configurações',
            'order' => 100  // Último
        ]
    ];
}
```

### Menus Condicionais

```php
public function menu(): array
{
    $menus = [
        [
            'label' => 'Dashboard',
            'url' => 'dashboard',
            'icon' => 'fas fa-tachometer-alt'
        ]
    ];
    
    // Adicionar apenas se usuário tem permissão
    if (auth()->user()->can('manage.users')) {
        $menus[] = [
            'label' => 'Usuários',
            'url' => 'users',
            'icon' => 'fas fa-users'
        ];
    }
    
    return $menus;
}
```

---

## 🧪 Testes

```bash
composer test
```

---

## 🕒 Histórico de Versões

### [1.2.0] - 2026-02-26
- **Bug Fix**: Resolvido problema de colisão de cache entre usuários. O cache agora é segmentado por ID de usuário para suportar menus com dados dinâmicos (como UIDs de perfil).
- **Melhoria**: Refatoração da chave de cache para maior isolamento de dados entre sessões.

### [1.1.0] - 2026-02-16
- **Melhoria**: Integrado suporte a **Rota Nomeada (Alias)** em `currentRoute()`, tornando os breadcrumbs e menus ativos imunes a mudanças de URL.
- **Performance**: Implementação de escuta de eventos (`rahpt.module.changed`) para limpeza reativa de cache de menus, eliminando verificação manual de status.
- **Arquitetura**: Uso do `Registrar` para carregamento automático de Eventos e Helpers.

### [1.0.1] - 2026-02-15
- Versão inicial estável.

---

## 📄 Licença

MIT License

---

## 👏 Créditos

Desenvolvido por **Rahpt**

---

**Versão**: 1.2.0  
**Última Atualização**: 2026-02-26
