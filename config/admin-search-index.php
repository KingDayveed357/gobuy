<?php

/**
 * Admin search index — ACTIONS ONLY.
 *
 * This file contains searchable quick-action entries that do NOT correspond
 * to a sidebar navigation link. Navigation pages are indexed from
 * config/admin-navigation.php automatically.
 *
 * Only add entries here when:
 *  1. The target is a create/import/export action page (e.g. /products/create)
 *  2. There is no existing nav entry covering this route.
 *
 * Schema per entry:
 *   id          — unique string identifier
 *   label       — result title shown in the palette
 *   subtitle    — one-line description
 *   breadcrumb  — navigation path (e.g. "Catalog > Products > Create")
 *   route       — Laravel route name (preferred over url)
 *   icon        — feather icon name (reuses the admin icon system)
 *   category    — grouping label in the results list
 *   type        — "action" (default) | "navigate"
 *   permission  — permission string, 'super_admin', or null (always visible)
 *   keywords    — array of extra search terms
 *   aliases     — alternate names a user might type
 *   priority    — 0–100 (higher = shown sooner); actions typically 55–75
 *
 * Future-proofing note:
 *   When database-backed global search is added (products, orders, customers),
 *   register it as an additional "source" via the AdminSearchIndex service rather
 *   than adding database entries to this static file.
 */
return [

    // ── Catalog actions ──────────────────────────────────────────────────────

    [
        'id'         => 'action.products.create',
        'label'      => 'Create Product',
        'subtitle'   => 'Add a new product to the catalog',
        'breadcrumb' => 'Catalog > Products > Create',
        'route'      => 'admin.products.create',
        'icon'       => 'plus-circle',
        'category'   => 'Catalog',
        'permission' => 'manage_products',
        'keywords'   => ['new product', 'add product', 'create listing', 'new item'],
        'aliases'    => ['add product', 'new product', 'new item'],
        'priority'   => 80,
    ],
    [
        'id'         => 'action.categories.index',
        'label'      => 'Add Category',
        'subtitle'   => 'Create a new product category',
        'breadcrumb' => 'Catalog > Categories > Add',
        'route'      => 'admin.categories.index',
        'icon'       => 'plus-circle',
        'category'   => 'Catalog',
        'permission' => 'manage_products',
        'keywords'   => ['new category', 'add category', 'create taxonomy'],
        'aliases'    => ['new category', 'add category'],
        'priority'   => 65,
    ],
    [
        'id'         => 'action.coupons.create',
        'label'      => 'Create Coupon',
        'subtitle'   => 'Add a new discount code or coupon',
        'breadcrumb' => 'Catalog > Coupons > Create',
        'route'      => 'admin.coupons.create',
        'icon'       => 'tag',
        'category'   => 'Catalog',
        'permission' => 'manage_products',
        'keywords'   => ['new coupon', 'discount code', 'promo code', 'add coupon'],
        'aliases'    => ['new coupon', 'add coupon', 'new discount'],
        'priority'   => 70,
    ],
    [
        'id'         => 'action.campaigns.create',
        'label'      => 'Create Campaign',
        'subtitle'   => 'Launch a new sales campaign',
        'breadcrumb' => 'Catalog > Campaigns > Create',
        'route'      => 'admin.campaigns.index',
        'icon'       => 'zap',
        'category'   => 'Catalog',
        'permission' => 'manage_products',
        'keywords'   => ['new campaign', 'add campaign', 'flash sale', 'new sale event'],
        'aliases'    => ['new campaign', 'add campaign'],
        'priority'   => 70,
    ],
    [
        'id'         => 'action.banners.create',
        'label'      => 'Create Banner',
        'subtitle'   => 'Add a new homepage or promotional banner',
        'breadcrumb' => 'Catalog > Banners > Create',
        'route'      => 'admin.banners.index',
        'icon'       => 'image',
        'category'   => 'Catalog',
        'permission' => 'manage_products',
        'keywords'   => ['new banner', 'add banner', 'hero image', 'new hero'],
        'aliases'    => ['new banner', 'add banner'],
        'priority'   => 60,
    ],
    [
        'id'         => 'action.collections.create',
        'label'      => 'Create Collection',
        'subtitle'   => 'Create a curated product collection',
        'breadcrumb' => 'Catalog > Collections > Create',
        'route'      => 'admin.collections.index',
        'icon'       => 'layers',
        'category'   => 'Catalog',
        'permission' => 'manage_products',
        'keywords'   => ['new collection', 'add collection', 'product group'],
        'aliases'    => ['new collection', 'add collection'],
        'priority'   => 58,
    ],
    [
        'id'         => 'action.pages.create',
        'label'      => 'Create CMS Page',
        'subtitle'   => 'Add a new storefront content page',
        'breadcrumb' => 'Catalog > Pages > Create',
        'route'      => 'admin.pages.index',
        'icon'       => 'file-text',
        'category'   => 'Content',
        'permission' => 'manage_products',
        'keywords'   => ['new page', 'add page', 'cms', 'landing page', 'static page'],
        'aliases'    => ['new page', 'add page', 'new cms page'],
        'priority'   => 60,
    ],
    [
        'id'         => 'action.inventory.import',
        'label'      => 'Import Inventory CSV',
        'subtitle'   => 'Upload a CSV to bulk-update stock levels',
        'breadcrumb' => 'Catalog > Inventory > Import',
        'route'      => 'admin.inventory.import.create',
        'icon'       => 'upload',
        'category'   => 'Catalog',
        'permission' => 'manage_products',
        'keywords'   => ['csv upload', 'stock import', 'bulk update', 'spreadsheet import'],
        'aliases'    => ['stock csv', 'upload inventory'],
        'priority'   => 58,
    ],
    [
        'id'         => 'action.products.bulk-price',
        'label'      => 'Bulk Price Adjustment',
        'subtitle'   => 'Adjust prices across a category or entire catalog',
        'breadcrumb' => 'Catalog > Bulk Pricing',
        'route'      => 'admin.pricing.bulk.create',
        'icon'       => 'percent',
        'category'   => 'Catalog',
        'permission' => 'manage_products',
        'keywords'   => ['mass price change', 'bulk discount', 'catalog pricing'],
        'aliases'    => ['mass pricing', 'bulk price change'],
        'priority'   => 55,
    ],

    // ── Order & fulfillment actions ──────────────────────────────────────────

    [
        'id'         => 'action.orders.export',
        'label'      => 'Export Orders',
        'subtitle'   => 'Download orders as a CSV export',
        'breadcrumb' => 'Orders & Fulfillment > Orders > Export',
        'route'      => 'admin.orders.export',
        'icon'       => 'download',
        'category'   => 'Orders',
        'permission' => 'manage_orders',
        'keywords'   => ['download orders', 'order csv', 'export sales'],
        'aliases'    => ['download orders', 'order export'],
        'priority'   => 60,
    ],
    [
        'id'         => 'action.returns.export',
        'label'      => 'Export Returns',
        'subtitle'   => 'Download returns/RMAs as a CSV export',
        'breadcrumb' => 'Orders & Fulfillment > Returns > Export',
        'route'      => 'admin.returns.export',
        'icon'       => 'download',
        'category'   => 'Orders',
        'permission' => 'manage_returns',
        'keywords'   => ['download returns', 'rma csv', 'export returns'],
        'aliases'    => ['download returns', 'returns export'],
        'priority'   => 55,
    ],

    // ── Finance actions ──────────────────────────────────────────────────────

    [
        'id'         => 'action.reconciliation.print',
        'label'      => 'Print Reconciliation Report',
        'subtitle'   => 'Open the printable daily reconciliation report',
        'breadcrumb' => 'Finance > Reconciliation > Print',
        'route'      => 'admin.reconciliation.print',
        'icon'       => 'printer',
        'category'   => 'Finance',
        'permission' => 'manage_payments',
        'keywords'   => ['print report', 'daily settlement', 'eod report'],
        'aliases'    => ['daily report print'],
        'priority'   => 50,
    ],

    // ── Customer actions ─────────────────────────────────────────────────────

    [
        'id'         => 'action.customers.export',
        'label'      => 'Export Customers',
        'subtitle'   => 'Download customer list as a CSV',
        'breadcrumb' => 'Customers > All Customers > Export',
        'route'      => 'admin.customers.export',
        'icon'       => 'download',
        'category'   => 'Customers',
        'permission' => 'manage_customers',
        'keywords'   => ['download customers', 'customer csv', 'export users'],
        'aliases'    => ['customer export', 'user export'],
        'priority'   => 60,
    ],
    [
        'id'         => 'action.store-credits.issue',
        'label'      => 'Issue Store Credit',
        'subtitle'   => 'Add store credit to a customer wallet',
        'breadcrumb' => 'Orders & Fulfillment > Store Credit > Issue',
        'route'      => 'admin.store-credits.index',
        'icon'       => 'gift',
        'category'   => 'Customers',
        'permission' => 'manage_refunds',
        'keywords'   => ['credit wallet', 'add credit', 'refund credit', 'customer credit'],
        'aliases'    => ['add credit', 'customer wallet'],
        'priority'   => 65,
    ],

    // ── Team actions ─────────────────────────────────────────────────────────

    [
        'id'         => 'action.staff.invite',
        'label'      => 'Invite Staff Member',
        'subtitle'   => 'Send an invitation to a new admin user',
        'breadcrumb' => 'Team > Staff > Invite',
        'route'      => 'admin.staff.index',
        'icon'       => 'user-plus',
        'category'   => 'Team',
        'permission' => 'super_admin',
        'keywords'   => ['new staff', 'add admin', 'invite user', 'new team member'],
        'aliases'    => ['invite admin', 'add team member'],
        'priority'   => 70,
    ],
    [
        'id'         => 'action.roles.create',
        'label'      => 'Create Role',
        'subtitle'   => 'Define a new permission role for staff',
        'breadcrumb' => 'Team > Roles > Create',
        'route'      => 'admin.roles.create',
        'icon'       => 'shield',
        'category'   => 'Team',
        'permission' => 'super_admin',
        'keywords'   => ['new role', 'add role', 'permission set', 'access level'],
        'aliases'    => ['new role', 'add role'],
        'priority'   => 65,
    ],
];
