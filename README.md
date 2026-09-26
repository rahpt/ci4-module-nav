# CodeIgniter 4 Module Navigation

[![Version](https://img.shields.io/badge/version-1.3.0-blue.svg)](https://github.com/rahpt/ci4-module-nav)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%3E%3D8.1-brightgreen.svg)](https://php.net)

Sistema corporativo de navegação, menus dinâmicos e breadcrumbs para módulos CodeIgniter 4. Possui filtragem avançada por permissões (Shield), isolamento Multi-Tenancy, renderizador seguro de ícones (`IconRegistry`), invalidação de cache reativa e proteção contra ataques de injeção.

---

## 📋 Índice

- [Características](#-características)
- [Instalação](#-instalação)
- [Definição de Menus no Módulo](#-definição-de-menus-no-módulo)
- [Filtros de Visibilidade e Segurança](#-filtros-de-visibilidade-e-segurança)
- [IconRegistry Seguro](#-iconregistry-seguro)
- [Cache Multi-Tenant e ACL Versionado](#-cache-multi-tenant-e-acl-versionado)
- [Breadcrumbs e Helpers](#-breadcrumbs-e-helpers)
- [Exibição em Layouts](#-exibição-em-layouts)
- [API Reference](#-api-reference)
- [Histórico de Versões](#-histórico-de-versões)
- [Licença](#-licença)

---

## ✨ Características

### Navegação & Descoberta
- ✅ **Menu Centralizado** - Agrega e ordena automaticamente os menus de todos os módulos ativos via `priority`.
- ✅ **Menús Agrupados** - Agrupamento automático por seções e categorias via `MenuRegistry::grouped()`.
- ✅ **Rota Nomeada (Alias)** - Suporte nativo ao helper `url_to($route)` através da chave `route`.
- ✅ **Breadcrumbs Inteligentes** - Rastreamento com marcação semântica e suporte a helpers de visualização.

### Segurança & Zero-Trust
- ✅ **IconRegistry Seguro** - Allowlist estrita de ícones evitando injeção de HTML ou quebra de atributos em tags `<i>`.
- ✅ **Sanitização de URLs e Labels** - Bloqueio de protocolos inseguros (`javascript:`, `data:`, `vbscript:`) e escape HTML automático (`label_escaped`).
- ✅ **Filtro Shield (RBAC)** - Visibilidade condicional por permissões (`permission`) e grupos (`group`).
- ✅ **Filtro Multi-Tenancy** - Exibição condicionada ao tenant ativo, escopo global ou allowlist de empresas/perfis.

### Performance & Cache
- ✅ **Cache Isolado Multi-Tenant/ACL** - Chaves de cache segmentadas por tenant, usuário e hash de grupos/permissões (`aclHash`).
- ✅ **Invalidação Reativa por Evento** - Limpeza atômica em resposta ao evento `rahpt.module.changed` incrementando a versão do cache.

---

## 🚀 Instalação

```bash
composer require rahpt/ci4-module-nav
```

---

## 📖 Definição de Menus no Módulo

Em qualquer módulo CodeIgniter 4 estendendo `BaseModule`, implemente o método `menu()`:

```php
<?php

namespace App\Modules\Contratos\Config;

use Rahpt\Ci4Module\BaseModule;

class Module extends BaseModule
{
    public string $name = 'Contratos';
    public int $priority = 20;

    public function menu(): array
    {
        return [
            [
                'label'      => 'Contratos',
                'route'      => 'contracts.index',          // Rota nomeada via url_to()
                'icon'       => 'contracts',                // Resolvido via IconRegistry
                'permission' => 'contracts.view',           // Checagem Shield: $user->can('contracts.view')
                'group'      => ['admin', 'gestor'],        // Checagem Shield: $user->inGroup(...)
                'tenant'     => true,                       // Apenas visível em contexto de tenant ativo
                'order'      => 10,
                'items'      => [
                    [
                        'label'      => 'Novo Contrato',
                        'url'        => 'contratos/novo',
                        'icon'       => 'file',
                        'permission' => 'contracts.create'
                    ],
                    [
                        'label'      => 'Relatórios Financeiros',
                        'url'        => 'contratos/relatorios',
                        'icon'       => 'chart',
                        'permission' => 'contracts.reports'
                    ]
                ]
            ]
        ];
    }
}
```

---

## 🛡️ Filtros de Visibilidade e Segurança

O `MenuRegistry::all()` aplica automaticamente filtros em cascata antes de retornar os itens para o layout:

### 1. Permissões e Grupos do CodeIgniter Shield
- **`permission`**: Se definida, valida se `auth()->user()->can($permission)`. Se falso, remove o item.
- **`group`**: Valida se o usuário pertence ao grupo especificado (`$user->inGroup(...)`).

### 2. Multi-Tenancy
- **`tenant => true`**: O item só aparece quando um tenant estiver ativo (`has_tenant() === true`).
- **`tenant => false`**: O item só aparece no painel global da plataforma (sem tenant).
- **`tenant => ['empresa-a', 'empresa-b']`**: O item só aparece para os tenants listados.
- **Módulos Permitidos no Perfil do Tenant**: Se a configuração `Tenancy::$tenantProfiles` restringir módulos para aquele tenant, módulos não contratados têm seus menus ocultados automaticamente.

### 3. Sanitização contra XSS e Protocol Injection
- Labels recebem escape seguro via `esc($label, 'html')` e ficam disponíveis em `label_escaped`.
- URLs contendo prefixos `javascript:`, `data:` ou `vbscript:` são neutralizadas para `#`.

---

## 🎨 IconRegistry Seguro

Evita que módulos externos injetem código HTML arbitrário em atributos de tags `<i>`:

```php
use Rahpt\Ci4ModuleNav\Support\IconRegistry;

// 1. Resolução segura de classe CSS
$class = IconRegistry::resolve('contracts');
// Retorna: 'fas fa-file-contract'

// 2. Renderização direta de tag HTML
echo IconRegistry::render('dashboard', 'mr-2 text-primary');
// Retorna: '<i class="fas fa-tachometer-alt mr-2 text-primary"></i>'

// 3. Registrar ou sobrescrever mapeamentos no bootstrap
IconRegistry::register('pix', 'fab fa-pix');
```

Mapeamentos padrão incluídos: `home`, `dashboard`, `users`, `user`, `settings`, `tools`, `file`, `contracts`, `calendar`, `tasks`, `folder`, `database`, `chart`, `shield`, `lock`, `bell`, `mail`, `box`, `building`, `store`, `circle`.

---

## ⚡ Cache Multi-Tenant e ACL Versionado

Para garantir máxima velocidade sem vazamento de visão entre usuários ou empresas, o sistema utiliza versionamento atômico:

```
module_menus_v{version}_{tenantId}_{userId}_acl{aclHash}
```

- **`version`**: Versão global do cache. Quando um módulo é ativado, desativado ou atualizado (`rahpt.module.changed`), a versão é incrementada atomicamente, invalidando o cache instantaneamente sem locks.
- **`tenantId`**: Impede que a visão de menus de uma empresa seja servida a outra.
- **`userId`**: Mantém as particularidades da sessão de cada usuário.
- **`aclHash`**: Hash MD5 curto dos grupos e permissões ativas. Se o papel do usuário mudar, o cache se renova de forma transparente.

---

## 🍞 Breadcrumbs e Helpers

O pacote registra helpers automáticos carregados pelo CodeIgniter:

```php
// No Controller
set_breadcrumb('Início', '/');
set_breadcrumb('Contratos', 'contratos');
set_breadcrumb('Editar Contrato #42'); // Sem URL: marca página ativa

// Na View
<?= render_breadcrumbs(' / ') ?>
```

---

## 🖥️ Exibição em Layouts (Exemplo AdminLTE)

```php
<?php
use Rahpt\Ci4ModuleNav\MenuRegistry;
use Rahpt\Ci4ModuleNav\Support\IconRegistry;

$menus = MenuRegistry::all();
?>

<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
    <?php foreach ($menus as $item): ?>
        <?php $hasChildren = !empty($item['items']); ?>
        <li class="nav-item <?= $hasChildren ? 'has-treeview' : '' ?>">
            <a href="<?= base_url($item['url'] ?? '#') ?>" class="nav-link">
                <?= IconRegistry::render($item['icon'] ?? 'circle', 'nav-icon') ?>
                <p>
                    <?= $item['label_escaped'] ?? $item['label'] ?>
                    <?php if ($hasChildren): ?>
                        <i class="right fas fa-angle-left"></i>
                    <?php endif; ?>
                </p>
            </a>

            <?php if ($hasChildren): ?>
                <ul class="nav nav-treeview pl-3">
                    <?php foreach ($item['items'] as $sub): ?>
                        <li class="nav-item">
                            <a href="<?= base_url($sub['url']) ?>" class="nav-link">
                                <?= IconRegistry::render($sub['icon'] ?? 'circle', 'nav-icon') ?>
                                <p><?= $sub['label_escaped'] ?? $sub['label'] ?></p>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
```

---

## 🔧 API Reference

### `MenuRegistry::all(): array`
Retorna array consolidado de menus de todos os módulos ativos com os filtros de permissão, tenancy e segurança aplicados.

### `MenuRegistry::grouped(): array`
Retorna menus agrupados por categoria/seção.

### `MenuRegistry::clearCache(): void`
Incrementa a versão global do cache, forçando renovação atômica em toda a aplicação.

---

## 🕒 Histórico de Versões

### [1.3.0] - 2026-09-26
- **Novo**: `IconRegistry` centralizado com allowlist e renderização segura contra injeção de HTML.
- **Novo**: Filtro nativo de permissões e grupos do CodeIgniter Shield nos menus (`permission`, `group`).
- **Novo**: Filtragem granular por Multi-Tenancy (`tenant`, perfis de módulos por tenant).
- **Novo**: Cache atômico com versionamento multi-tenant e hash ACL de permissões.
- **Novo**: Sanitização estrita contra esquemas de URL perigosos (`javascript:`, `data:`).
- **Novo**: Método `MenuRegistry::grouped()` para categorização de menus.

### [1.2.0] - 2026-02-26
- **Bug Fix**: Resolvido problema de colisão de cache entre usuários com UIDs dinâmicos.

### [1.1.0] - 2026-02-16
- **Melhoria**: Rota Nomeada (Alias) via `url_to()` no helper e menus.
- **Performance**: Invalidação reativa via evento `rahpt.module.changed`.

### [1.0.1] - 2026-02-15
- Versão inicial estável.

---

## 📄 Licença

MIT License. Desenvolvido por **Rahpt**.
