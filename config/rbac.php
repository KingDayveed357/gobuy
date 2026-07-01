<?php

/*
|--------------------------------------------------------------------------
| RBAC catalog (single source of truth)
|--------------------------------------------------------------------------
|
| Ownership split:
|   - PERMISSIONS are a fixed vocabulary owned by developers. They are added
|     here when a feature ships, then synced to the DB by AdminAccessSeeder.
|   - ROLES + assignments are owned by the business and edited at runtime in
|     the admin UI. The `roles` below are only the seeded defaults/templates.
|
| Super-admin-only capabilities (staff/role/permission management, gateway &
| API credentials, security, backups, maintenance) are deliberately NOT listed
| here. They are gated by Admin::isSuperAdmin() / Gate::before, so they can
| never appear as a grantable toggle in the permission matrix.
|
| Permission names are kept as the existing `manage_*` / `view_*` set so route
| middleware (permission:manage_products,admin) keeps working unchanged. A finer
| `module.action` reshape is a later, separate phase.
|
*/

return [

    /*
     | Modules group permissions for the UI matrix. Each permission has a
     | human label so the owner never reads a raw permission key.
     */
    'modules' => [
        'Catalog' => [
            'manage_products' => 'Manage products, inventory, categories & promotions',
        ],
        'Orders' => [
            'manage_orders' => 'View & manage orders, shipments & logistics',
        ],
        'Customers' => [
            'manage_customers' => 'View customers & wholesale applications',
        ],
        'Returns' => [
            'manage_returns' => 'Review & process returns',
        ],
        'Finance' => [
            'manage_payments' => 'View payments, reconcile & resolve transfers',
            'manage_refunds' => 'Issue refunds & store credit',
        ],
        'Insights' => [
            'view_analytics' => 'View analytics & reports',
        ],
    ],

    /*
     | Default roles seeded as reusable templates. Super Admin is the immutable
     | owner (its access comes from Gate::before, not from this list — the empty
     | permission set is intentional). Every other role is fully editable by the
     | owner after seeding. `manage_admins` is intentionally retired as a
     | grantable permission — staff management is super-admin-only.
     */
    'roles' => [
        // Owner — access comes from Gate::before, so the permission set is empty
        // by design.
        'Super Admin' => [],

        // Original operational roles (preserved for backward compatibility).
        'Admin' => ['manage_products', 'manage_orders', 'manage_customers', 'manage_payments', 'manage_returns', 'manage_refunds', 'view_analytics'],
        'Manager' => ['manage_products', 'manage_orders', 'manage_returns', 'view_analytics'],
        'Support' => ['manage_orders', 'manage_customers', 'manage_returns'],

        // Ready-made templates for common hires — fully editable by the owner.
        'Inventory Manager' => ['manage_products'],
        'Product Manager' => ['manage_products', 'view_analytics'],
        'Marketing Manager' => ['manage_products', 'view_analytics'],
        'Sales Manager' => ['manage_orders', 'view_analytics'],
        'Finance Manager' => ['manage_payments', 'manage_refunds', 'view_analytics'],
    ],

    /*
     | The role the platform owner holds. Backs Admin::isSuperAdmin().
     */
    'super_admin_role' => 'Super Admin',
];
