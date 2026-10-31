<?php
// ready = false muestra la opción como "Pronto" hasta que el módulo exista.
return [
  ['grupo' => 'Operación', 'items' => [
    ['label' => 'Panel',            'icon' => 'bi-grid-1x2',      'route' => 'dashboard', 'roles' => ['admin','cajero'], 'ready' => true],
    ['label' => 'Vender',           'icon' => 'bi-receipt',       'route' => 'ventas',    'roles' => ['admin','cajero'], 'ready' => false],
    ['label' => 'Salón y mesas',    'icon' => 'bi-columns-gap',   'route' => 'mesas',     'roles' => ['admin','cajero'], 'ready' => true],
    ['label' => 'Cola de karaoke',  'icon' => 'bi-mic',           'route' => 'cola',      'roles' => ['admin','cajero'], 'ready' => false],
    ['label' => 'Reservas',         'icon' => 'bi-calendar-check','route' => 'reservas',  'roles' => ['admin','cajero'], 'ready' => false],
    ['label' => 'Mi caja',          'icon' => 'bi-cash-stack',    'route' => 'caja',      'roles' => ['admin','cajero'], 'ready' => false],
  ]],
  ['grupo' => 'Administración', 'items' => [
    ['label' => 'Productos',        'icon' => 'bi-cup-straw',     'route' => 'productos', 'roles' => ['admin'], 'ready' => true],
    ['label' => 'Categorías',       'icon' => 'bi-bookmarks',     'route' => 'categorias','roles' => ['admin'], 'ready' => true],
    ['label' => 'Combos y promos',  'icon' => 'bi-tags',          'route' => 'combos',    'roles' => ['admin'], 'ready' => false],
    ['label' => 'Usuarios',         'icon' => 'bi-people',        'route' => 'usuarios',  'roles' => ['admin'], 'ready' => true],
    ['label' => 'Reportes',         'icon' => 'bi-bar-chart-line','route' => 'reportes',  'roles' => ['admin'], 'ready' => false],
    ['label' => 'Bitácora',         'icon' => 'bi-journal-text',  'route' => 'bitacora',  'roles' => ['admin'], 'ready' => false],
  ]],
];
