<?php

/**
 * Admin sidebar navigation structure.
 *
 * Groups use Phoenix dropdown-indicator / Bootstrap collapse patterns.
 * Permissions mirror routes/admin.php middleware — items are filtered at render time.
 */
return [
    [
        'type' => 'link',
        'label' => 'Dashboard',
        'route' => 'admin.dashboard',
        'icon' => 'pie-chart',
        'active' => ['admin.dashboard'],
    ],
    [
        'type' => 'group',
        'id' => 'nv-catalog',
        'label' => 'Catalog',
        'icon' => 'box',
        'permission' => 'manage_products',
        'items' => [
            ['label' => 'Products', 'route' => 'admin.products.index', 'active' => ['admin.products.*']],
            ['label' => 'Inventory', 'route' => 'admin.inventory.index', 'active' => ['admin.inventory.*']],
            ['label' => 'Categories', 'route' => 'admin.categories.index', 'active' => ['admin.categories.*']],
            ['label' => 'Reviews', 'route' => 'admin.reviews.index', 'active' => ['admin.reviews.*']],
            ['label' => 'Banners', 'route' => 'admin.banners.index', 'active' => ['admin.banners.*']],
            ['label' => 'Coupons', 'route' => 'admin.coupons.index', 'active' => ['admin.coupons.*']],
            ['label' => 'Promotions', 'route' => 'admin.promotions.index', 'active' => ['admin.promotions.*']],
            ['label' => 'Bulk pricing', 'route' => 'admin.pricing.bulk.create', 'active' => ['admin.pricing.bulk.*']],
        ],
    ],
    [
        'type' => 'group',
        'id' => 'nv-orders',
        'label' => 'Orders & fulfillment',
        'icon' => 'shopping-cart',
        'permission' => ['manage_orders', 'manage_returns', 'manage_refunds'],
        'items' => [
            ['label' => 'Orders', 'route' => 'admin.orders.index', 'active' => ['admin.orders.*'], 'permission' => 'manage_orders'],
            ['label' => 'Logistics Hub', 'route' => 'admin.logistics.index', 'active' => ['admin.logistics.index'], 'permission' => 'manage_orders'],
            ['label' => 'Shipments', 'route' => 'admin.shipments.index', 'active' => ['admin.shipments.*'], 'permission' => 'manage_orders'],
            ['label' => 'Delivery Zones', 'route' => 'admin.delivery-zones.index', 'active' => ['admin.delivery-zones.*'], 'permission' => 'manage_orders'],
            ['label' => 'Locations', 'route' => 'admin.locations.index', 'active' => ['admin.locations.*'], 'permission' => 'manage_orders'],
            ['label' => 'Returns', 'route' => 'admin.returns.index', 'active' => ['admin.returns.*'], 'permission' => 'manage_returns'],
            ['label' => 'Store credit', 'route' => 'admin.store-credits.index', 'active' => ['admin.store-credits.*'], 'permission' => 'manage_refunds', 'icon' => 'award'],
        ],
    ],
    [
        'type' => 'group',
        'id' => 'nv-finance',
        'label' => 'Finance',
        'icon' => 'credit-card',
        'permission' => 'manage_payments',
        'items' => [
            ['label' => 'Payments', 'route' => 'admin.payments.index', 'active' => ['admin.payments.index', 'admin.payments.*']],
            ['label' => 'Transfers', 'route' => 'admin.transfers.index', 'active' => ['admin.transfers.*']],
            ['label' => 'Reconciliation', 'route' => 'admin.reconciliation', 'active' => ['admin.reconciliation']],
        ],
    ],
    [
        'type' => 'section',
        'label' => 'Insights',
        'permission' => 'view_analytics',
    ],
    [
        'type' => 'link',
        'label' => 'Analytics',
        'route' => 'admin.analytics',
        'icon' => 'bar-chart-2',
        'active' => ['admin.analytics'],
        'permission' => 'view_analytics',
    ],
    [
        'type' => 'section',
        'label' => 'People',
        'permission' => 'manage_customers',
    ],
    [
        'type' => 'group',
        'id' => 'nv-people',
        'label' => 'Customers',
        'icon' => 'users',
        'permission' => 'manage_customers',
        'items' => [
            ['label' => 'All customers', 'route' => 'admin.customers.index', 'active' => ['admin.customers.*']],
            ['label' => 'Wholesale', 'route' => 'admin.wholesale.index', 'active' => ['admin.wholesale.*']],
            ['label' => 'Bulk requests', 'route' => 'admin.bulk-requests.index', 'active' => ['admin.bulk-requests.*']],
        ],
    ],

    // Owner-only: staff & role management.
    [
        'type' => 'group',
        'id' => 'nv-team',
        'label' => 'Team',
        'icon' => 'shield',
        'super_admin' => true,
        'items' => [
            ['label' => 'Staff', 'route' => 'admin.staff.index', 'active' => ['admin.staff.*'], 'icon' => 'user-check'],
            ['label' => 'Roles', 'route' => 'admin.roles.index', 'active' => ['admin.roles.*'], 'icon' => 'shield'],
            ['label' => 'Activity log', 'route' => 'admin.activity.index', 'active' => ['admin.activity.*'], 'icon' => 'activity'],
        ],
    ],
];
