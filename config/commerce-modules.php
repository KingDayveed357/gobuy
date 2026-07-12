<?php

/*
|--------------------------------------------------------------------------
| Commerce Operations modules (Layer 2)
|--------------------------------------------------------------------------
|
| The Commerce Core (Layer 1) is always present. Everything here is OPTIONAL:
| advanced retail/omnichannel operations that a plain e-commerce merchant never
| needs to see. A module contributes routes, navigation, permissions and
| dashboards ONLY while it is enabled — so the admin stays clean by default.
|
| Definitions (this file) are owned by developers and describe what a module IS.
| Enabled STATE is owned by the business and lives in the `settings` store under
| `modules.<key>`, toggled at runtime from Settings → Modules (super admin only).
|
| `shipped` gates whether a module can be toggled at all: a module declared here
| but not yet built stays a read-only "coming soon" entry until its phase lands.
|
| Fields:
|   label        human name shown to the owner
|   description  one line explaining the capability
|   depends      keys that must be enabled first (enabling cascades on, disabling
|                cascades off any dependents)
|   permissions  RBAC permission keys the module owns (hidden when it is off)
|   routes       app-relative admin route file, loaded only while enabled
|   shipped      whether the module is built and toggleable yet
|
*/

return [

    'modules' => [

        'ops.inventory_ledger' => [
            'label' => 'Multi-location inventory',
            'description' => 'Track stock across locations with a full movement history — the audit trail that replaces the notebook.',
            'depends' => [],
            'permissions' => ['manage_inventory_ops'],
            'routes' => 'Modules/Operations/routes-inventory.php',
            'shipped' => true,
        ],

        'ops.walk_in' => [
            'label' => 'Walk-in & manual sales',
            'description' => 'Record in-store, phone and social sales through the same order pipeline as the website.',
            'depends' => [],
            'permissions' => ['manage_walk_in_sales'],
            'routes' => 'Modules/Operations/routes-walk-in.php',
            'shipped' => true,
        ],

        'ops.register' => [
            'label' => 'Cash register & day close',
            'description' => 'Open and close the business day with counted-vs-expected cash reconciliation.',
            'depends' => ['ops.walk_in'],
            'permissions' => ['manage_register'],
            'routes' => 'Modules/Operations/routes-register.php',
            'shipped' => true,
        ],

        'ops.transfers' => [
            'label' => 'Stock transfers',
            'description' => 'Move stock between locations with a recorded transfer history.',
            'depends' => ['ops.inventory_ledger'],
            'permissions' => ['manage_transfers'],
            'routes' => 'Modules/Operations/routes-transfers.php',
            'shipped' => true,
        ],

        'ops.purchasing' => [
            'label' => 'Suppliers & purchasing',
            'description' => 'Manage suppliers, raise purchase orders and receive goods into stock.',
            'depends' => ['ops.inventory_ledger'],
            'permissions' => ['manage_purchasing'],
            'routes' => 'Modules/Operations/routes-purchasing.php',
            'shipped' => true,
        ],

        'ops.packaging' => [
            'label' => 'Packaging units',
            'description' => 'Sell the same product as bottle, pack, carton or crate — inventory stays in sync.',
            'depends' => [],
            'permissions' => ['manage_packaging'],
            'routes' => 'Modules/Operations/routes-packaging.php',
            'shipped' => true,
        ],

        'ops.stock_counts' => [
            'label' => 'Stock counts & damage',
            'description' => 'Reconcile counted stock, write off damaged goods and run inventory audits.',
            'depends' => ['ops.inventory_ledger'],
            'permissions' => ['manage_stock_counts'],
            'routes' => 'Modules/Operations/routes-stock-counts.php',
            'shipped' => true,
        ],

        'ops.dashboards' => [
            'label' => 'Operations dashboards',
            'description' => 'Operational reporting: inventory by location, sales by channel, fast/slow movers and business health.',
            'depends' => [],
            'permissions' => ['view_ops_reports'],
            'routes' => 'Modules/Operations/routes-dashboards.php',
            'shipped' => true,
        ],

    ],

];
