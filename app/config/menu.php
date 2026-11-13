<?php
return [
  ['grupo' => '', 'items' => [
    ['label' => 'Panel',            'icon' => 'bi-grid-1x2',      'route' => 'dashboard', 'roles' => ['admin','cajero']],
    ['label' => 'Vender',           'icon' => 'bi-receipt',       'route' => 'ventas',    'roles' => ['admin','cajero']],
    ['label' => 'Mesas',            'icon' => 'bi-columns-gap',   'route' => 'mesas',     'roles' => ['admin','cajero']],
    ['label' => 'Mi caja',          'icon' => 'bi-cash-stack',    'route' => 'caja',      'roles' => ['admin','cajero']],
  ]],
  ['grupo' => 'Administración', 'items' => [
    ['label' => 'Productos',        'icon' => 'bi-cup-straw',     'route' => 'productos', 'roles' => ['admin']],
    ['label' => 'Categorías',       'icon' => 'bi-bookmarks',     'route' => 'categorias','roles' => ['admin']],
    ['label' => 'Combos',           'icon' => 'bi-tags',          'route' => 'combos',    'roles' => ['admin']],
    ['label' => 'Usuarios',         'icon' => 'bi-people',        'route' => 'usuarios',  'roles' => ['admin']],
    ['label' => 'Reportes',         'icon' => 'bi-bar-chart-line','route' => 'reportes',  'roles' => ['admin']],
    ['label' => 'Configuración',    'icon' => 'bi-gear',          'route' => 'configuracion', 'roles' => ['admin']],
  ]],
];
