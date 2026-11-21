# Project Chat — Mini Inventory Management System

This file is the running development log for the project. It records environment decisions, completed work, verification results, known issues, and the next milestone after each phase.

---

## Phase 1 — Environment and foundation

### Status

Completed.

### Environment and decisions

- Laravel `13.42.0` installed in the project root.
- PHP `8.3.31`, Composer `2.10.1`, Node `24.15.0`, npm `11.12.1`.
- Laravel Breeze `2.4.2` installed with the Vue/Inertia stack.
- Inertia Laravel `v2.0.28`, `@inertiajs/vue3` `^2.0.0`, Ziggy `v2.7.0`.
- PHPUnit `12.5.44` is the project test runner, so tests use PHPUnit rather than Pest.
- Frontend stack: Vue 3, Tailwind CSS v4 (`@tailwindcss/vite`), Pinia, Lucide icons via `@lucide/vue`, ApexCharts via `vue3-apexcharts`, axios.
- PDF support: `barryvdh/laravel-dompdf` `v3.1.2` installed for later phases.
- Database: local XAMPP MariaDB `10.4.32` at `127.0.0.1:3306`, user `root`, empty password.
- Application database: `mini_inventory`.
- Test database: `mini_inventory_test`.
- Money will be calculated server-side with `brick/math` BigDecimal at scale 2; stock at scale 3.
- `InventoryService` will be the sole writer of `products.current_stock` in later phases, using DB transactions, row locks, and a unique stock-movement guard.
- Authorization uses simple `role` and `status` columns, a `role` middleware alias, policies, and an admin bypass in `Gate::before`.
- The sidebar filters links by registered Ziggy routes so no dead links appear as later phases are added.
- Demo logins seeded by `UserSeeder`:
  - `admin@inventory.test` / `password`
  - `staff@inventory.test` / `password`

### Files created or changed

#### Backend and configuration

- `.env` — application name, MariaDB connection, seeded databases.
- `phpunit.xml` — MariaDB test database configuration.
- `composer.json` / `composer.lock` — Laravel Boost, Breeze, Inertia, Sanctum, Ziggy, dompdf, PHPUnit dependencies.
- `package.json` / `package-lock.json` — Vue, Tailwind v4, Pinia, Lucide, ApexCharts, axios dependencies.
- `bootstrap/app.php` — `role` middleware alias registration.
- `routes/web.php` — welcome redirect, dashboard route, profile routes with role protection.
- `database/migrations/0001_01_01_000000_create_users_table.php` — added `role` and `status`.
- `database/factories/UserFactory.php` — default role/status plus `admin()` and `inactive()` states.
- `database/seeders/UserSeeder.php` — demo admin and staff accounts.
- `database/seeders/DatabaseSeeder.php` — calls `UserSeeder`.
- `app/Models/User.php` — role/status constants and helper methods.
- `app/Http/Middleware/EnsureUserHasRole.php` — role and active-status enforcement.
- `app/Http/Middleware/HandleInertiaRequests.php` — shared auth user with `is_admin`.
- `app/Providers/AppServiceProvider.php` — admin bypass for Gates/policies.
- `app/Http/Controllers/DashboardController.php` — real dashboard counts and recent users.

#### Frontend foundation

- `resources/js/app.js` — Inertia app, Ziggy, Pinia, bootstrap import.
- `resources/js/bootstrap.js` — axios setup and XSRF token header.
- `resources/css/app.css` — Tailwind v4 CSS-first setup.
- `vite.config.js` — Tailwind v4 Vite plugin and Vue plugin.
- `postcss.config.js` — removed because Tailwind v4 no longer needs the v3 PostCSS setup.
- `resources/js/Stores/auth.js` — current user and logout.
- `resources/js/Stores/notification.js` — toast notification state.
- `resources/js/Stores/cart.js` — display-only sale draft state for later phases.
- `resources/js/Composables/usePermissions.js` — role-based permission helper.
- `resources/js/Composables/useCurrency.js` — display currency helper.
- `resources/js/Composables/useNotifications.js` — notification helpers.
- `resources/js/Composables/useDebounce.js` — search debounce helper.
- `resources/js/Utils/formatCurrency.js` — display-only currency formatter.
- `resources/js/Utils/formatDate.js` — date formatters.
- `resources/js/Utils/calculations.js` — display-only invoice draft totals; server remains authoritative.
- `resources/js/Layouts/AppLayout.vue` — main authenticated shell.
- `resources/js/Layouts/AuthLayout.vue` — reusable auth-page shell.
- `resources/js/Components/AppLogo.vue`, `AppSidebar.vue`, `AppHeader.vue`.
- `resources/js/Components/PageHeader.vue`, `StatCard.vue`, `StatusBadge.vue`.
- `resources/js/Components/DataTable.vue`, `SearchInput.vue`, `Pagination.vue`.
- `resources/js/Components/ConfirmDialog.vue`, `Modal.vue`.
- `resources/js/Components/FormInput.vue`, `FormSelect.vue`.
- `resources/js/Components/EmptyState.vue`, `LoadingSpinner.vue`, `NotificationToast.vue`.
- `resources/js/Pages/Dashboard/Index.vue` — dashboard page using real controller props.

#### Tests

- `tests/Feature/DashboardTest.php` — guest redirect, active staff access with real stats, admin access, inactive user denial.

### What works

- Guests are redirected from `/dashboard` to the login page.
- Authenticated active admin and staff users can open the dashboard.
- Inactive users receive HTTP 403.
- Dashboard stats and recent accounts come from the database, not static data.
- The authenticated shell has a responsive sidebar, header, route-aware navigation, and toast area.
- Shared components are in place for tables, forms, dialogs, pagination, states, and notifications.
- Demo admin and staff accounts can be used to sign in.
- Sidebar only displays links for routes that actually exist.

### Verification results

- `php artisan migrate:fresh --seed --force` — passed.
- `npm run build` — passed.
- `vendor/bin/pint --dirty --format agent` — passed.
- `php artisan test --compact tests/Feature/DashboardTest.php` — passed: 4 tests, 36 assertions.
- `php artisan test --compact` — passed: 29 tests, 97 assertions.

### Pending issues

- No known blocking issues.
- Browser-level visual smoke test of the dashboard shell is still pending.
- Breeze auth pages still use the default `GuestLayout`; they can be migrated to the new `AuthLayout` during UI polish.
- Inventory domain modules are intentionally not built yet.

### Next milestone

**Phase 2 — Master data:** implement Categories and Units with migrations, models, factories, seeders, Form Requests, policies, controllers, Inertia Vue pages, and feature tests.

---

## Phase 2 — Master data (Categories and Units)

### Status

Completed.

### Files created or changed

#### Backend

- `database/migrations/2026_11_17_123955_create_categories_table.php` — `name`, unique `slug`, `description`, `is_active`.
- `database/migrations/2026_11_17_123956_create_units_table.php` — `name`, unique `short_name`, `description`, `is_active`.
- `app/Models/Category.php`, `app/Models/Unit.php` — fillable attributes, boolean casts, `active()` scopes.
- `database/factories/CategoryFactory.php`, `database/factories/UnitFactory.php` — factories with `inactive()` states.
- `database/seeders/CategorySeeder.php`, `database/seeders/UnitSeeder.php` — default categories and units of measure.
- `database/seeders/DatabaseSeeder.php` — now calls `UserSeeder`, `CategorySeeder`, `UnitSeeder`.
- `app/Http/Requests/StoreCategoryRequest.php`, `UpdateCategoryRequest.php`.
- `app/Http/Requests/StoreUnitRequest.php`, `UpdateUnitRequest.php`.
- `app/Policies/CategoryPolicy.php`, `app/Policies/UnitPolicy.php` — admin-only access.
- `app/Http/Controllers/CategoryController.php`, `app/Http/Controllers/UnitController.php` — paginated index, store, update, destroy.
- `app/Http/Middleware/HandleInertiaRequests.php` — shares `flash.success` and `flash.error`.
- `routes/web.php` — admin-only resource routes for categories and units.

#### Frontend

- `resources/js/Components/FormTextarea.vue` — reusable textarea component.
- `resources/js/Pages/MasterData/Categories/Index.vue` — table, search, create/edit modal, delete confirmation.
- `resources/js/Pages/MasterData/Units/Index.vue` — table, search, create/edit modal, delete confirmation.
- `resources/js/Layouts/AppLayout.vue` — title fallback from current route and global flash-toast handling.

#### Tests

- `tests/Feature/CategoryTest.php` — 9 tests.
- `tests/Feature/UnitTest.php` — 9 tests.

### What works

- Admin-only Categories and Units CRUD backed by MariaDB.
- Staff cannot view or create categories/units; routes, policies, and middleware all enforce this.
- Slugs are generated automatically from category names.
- Unique validation ignores the current record during updates.
- Search filters categories by name/slug and units by name/short name.
- Pagination preserves the search query string.
- Create/update/delete actions redirect back with flash success messages shown as toasts.
- Default categories and units are seeded: 4 categories, 5 units.
- The sidebar shows Categories and Units only for admins because staff permissions do not include them.

### Verification results

- `vendor/bin/pint --dirty --format agent` — passed.
- `php artisan route:list --except-vendor --path=categories` — 4 routes registered.
- `php artisan route:list --except-vendor --path=units` — 4 routes registered.
- `npm run build` — passed.
- `php artisan migrate:fresh --seed --force` — passed; 2 users, 4 categories, 5 units seeded.
- `php artisan test --compact` — passed: 47 tests, 173 assertions.

### Pending issues

- No known blocking issues.
- Browser-level visual smoke test of the new master-data pages is still pending.
- Categories and units are currently hard-deleted; foreign-key restrictions will be added in Phase 3 when products reference them.
- Staff intentionally have no access to master data by design.

### Next milestone

**Phase 3 — Products:** implement the products catalogue with migrations, models, factories, seeders, Form Requests, policy, controller, Inertia/Vue pages, category/unit relationships, SKU and pricing validation, low-stock flags, and feature tests.

---

## Phase 3 — Products catalogue

### Status

Completed.

### Files created or changed

#### Backend

- `database/migrations/2026_11_17_125206_create_products_table.php` — products with category/unit foreign keys, pricing, stock, reorder level, unique SKU/barcode.
- `app/Models/Product.php` — relationships, decimal casts, appended `stock_status` / `is_low_stock`, stock scopes.
- `app/Models/Category.php`, `app/Models/Unit.php` — added `products()` relationships.
- `app/Models/User.php` — added `isStaff()`.
- `database/factories/ProductFactory.php` — defaults plus `inactive()`, `lowStock()`, `outOfStock()` states.
- `database/seeders/ProductSeeder.php`, `database/seeders/DatabaseSeeder.php` — 5 realistic demo products.
- `app/Http/Requests/StoreProductRequest.php`, `UpdateProductRequest.php` — validation and admin authorization; no `current_stock` field accepted.
- `app/Policies/ProductPolicy.php` — staff may view; only admins may create/update/delete.
- `app/Http/Controllers/ProductController.php` — paginated filtered index, store, update, destroy.
- `routes/web.php` — products resource routes; writes restricted to admins.

#### Frontend

- `resources/js/Pages/Products/Index.vue` — stats, search/filter bar, product table, create/edit modal, delete dialog.

#### Tests

- `tests/Feature/ProductTest.php` — 12 tests.

### What works

- Admin full product CRUD; staff view-only.
- Product search by name, SKU, and barcode.
- Filters by category, stock status, and active status with preserved pagination query strings.
- Real stats for total, active, low-stock, and out-of-stock products.
- Stock status is derived server-side and appended to serialized products.
- `current_stock` is not writable through product forms or requests and stays unchanged on product updates.
- SKU and barcode are unique and normalized to uppercase.
- Products require active categories and units; category/unit deletion is restricted by foreign keys.
- Seeded catalogue includes in-stock, low-stock, and out-of-stock examples.

### Verification results

- `vendor/bin/pint --dirty --format agent` — passed.
- `php artisan route:list --except-vendor --path=products` — 4 routes registered.
- `npm run build` — passed.
- `php artisan migrate:fresh --seed --force` — passed; 5 products seeded.
- Database check — 5 products, 1 low-stock, 1 out-of-stock.
- `php artisan test --compact tests/Feature/ProductTest.php` — passed: 12 tests, 76 assertions.
- `php artisan test --compact` — passed: 59 tests, 249 assertions.
- An initial full-suite run timed out; isolating the tests revealed an incorrect option-count assertion, which was fixed. The final full suite passed.

### Pending issues

- No known blocking issues.
- Browser-level visual smoke test of the products page is still pending.
- Stock movements are not built yet, so `current_stock` is currently populated only by factories/seeders.
- Product deletion will need an additional "has transactions" guard when sales and purchases are added.

### Next milestone

**Phase 4 — Suppliers and Purchases:** supplier master data, purchase documents with line items, and `InventoryService` using DB transactions and row locks as the sole application writer of `products.current_stock`.

---

## Phase 4 — Suppliers, purchases, and inventory movements

### Status

Completed.

### Files created or changed

#### Backend

- `database/migrations/2026_11_17_132741_create_suppliers_table.php` — supplier master data with indexed name/status and unique email.
- `database/migrations/2026_11_17_132742_create_purchases_table.php` — purchase documents with supplier, unique invoice number, date, notes, total, and creator.
- `database/migrations/2026_11_17_132743_create_purchase_items_table.php` — immutable purchase line items with snapshot product details, quantity, price, and line total.
- `database/migrations/2026_11_17_132744_create_stock_movements_table.php` — polymorphic inventory ledger with type, quantity, balance after, notes, creator, and duplicate-reference guard.
- `app/Models/Supplier.php`, `app/Models/Purchase.php`, `app/Models/PurchaseItem.php`, `app/Models/StockMovement.php` — models, casts, constants, scopes, and relationships.
- `app/Models/Product.php`, `app/Models/User.php` — added purchase-item, stock-movement, and purchase relationships; `User` keeps creator history.
- `database/factories/SupplierFactory.php`, `PurchaseFactory.php`, `PurchaseItemFactory.php`, `StockMovementFactory.php` — test factories, including an inactive supplier state.
- `app/Http/Requests/StoreSupplierRequest.php`, `UpdateSupplierRequest.php` — supplier validation, normalization, and admin authorization.
- `app/Http/Requests/StorePurchaseRequest.php` — purchase validation, active supplier/product checks, duplicate line guard, and invoice normalization.
- `app/Policies/SupplierPolicy.php`, `app/Policies/PurchasePolicy.php` — admin-only access for supplier and purchase modules.
- `app/Services/InventoryService.php` — sole application writer of `products.current_stock`; requires a transaction, locks the product row, and rejects duplicate movements.
- `app/Services/PurchaseService.php` — atomic purchase creation with BigDecimal line totals and stock movement recording.
- `app/Http/Controllers/SupplierController.php` — paginated/filterable supplier CRUD with a delete guard for vendors with purchases.
- `app/Http/Controllers/PurchaseController.php` — purchase index, create, store, and show with filters, stats, and active select options.
- `app/Http/Controllers/ProductController.php` — added delete guard for products referenced by purchase items.
- `routes/web.php` — supplier and purchase resource routes inside the admin-only middleware group.
- `database/seeders/SupplierSeeder.php`, `PurchaseSeeder.php`, `DatabaseSeeder.php` — three realistic suppliers and two demo purchases recorded through `PurchaseService`.

#### Frontend

- `resources/js/Pages/MasterData/Suppliers/Index.vue` — supplier stats, filters, table, create/edit modal, and delete dialog.
- `resources/js/Pages/Purchases/Index.vue` — purchase stats, filters, paginated table, and view action.
- `resources/js/Pages/Purchases/Create.vue` — purchase document form with dynamic line items and display-only total preview.
- `resources/js/Pages/Purchases/Show.vue` — purchase detail, line items, and resulting stock movements.

#### Tests

- `tests/Feature/SupplierTest.php` — 10 tests.
- `tests/Feature/PurchaseTest.php` — 10 tests.
- `tests/Feature/InventoryServiceTest.php` — 3 tests.
- `tests/Feature/ProductTest.php` — 13 tests after adding the purchase-history delete guard.

### What works

- Admin has full supplier CRUD; staff cannot access supplier routes or pages.
- Suppliers can be searched by name, contact person, or email and filtered by active status.
- Supplier email is unique and normalized to lowercase; updating a supplier ignores its own email.
- Suppliers with purchase history and products with purchase-item history cannot be deleted and return friendly flash errors.
- Admin can record purchases with one or more active products from an active supplier.
- Purchase totals are calculated server-side with `brick/math` BigDecimal at money scale 2; stock is handled at scale 3.
- Purchase creation, line items, stock updates, and stock movements commit atomically in one DB transaction.
- `InventoryService` locks the product row with `lockForUpdate()`, guards duplicate movements, and is the only application code writing `products.current_stock`.
- Purchase documents are immutable in Phase 4: create and view only, with no edit or delete routes.
- Purchase index supports search, supplier, and date-range filters with real stats and preserved query strings.
- Purchase detail page shows supplier/creator/document totals, line items, and the stock movements created by the purchase.
- Seeded data includes 3 suppliers, 2 purchases, 4 purchase items, and 4 stock movements; seeded low-stock and out-of-stock examples remain realistic.
- Existing sidebar links for Purchases and Suppliers became active automatically because Ziggy filters links by registered routes.

### Verification results

- `vendor/bin/pint --dirty --format agent` — passed.
- `php artisan route:list --except-vendor --path=suppliers` — 4 routes registered.
- `php artisan route:list --except-vendor --path=purchases` — 4 routes registered.
- `npm run build` — passed.
- `php artisan migrate:fresh --seed --force` — passed; 3 suppliers, 2 purchases, 4 purchase items, and 4 stock movements seeded.
- Database check — purchase total `631500.00`; stock statuses: 3 in stock, 1 low stock (`ELEC-KEY-002`), 1 out of stock (`STAT-NOT-003`).
- `php artisan test --compact tests/Feature/SupplierTest.php` — passed: 10 tests, 56 assertions.
- `php artisan test --compact tests/Feature/PurchaseTest.php` — passed: 10 tests, 89 assertions.
- `php artisan test --compact tests/Feature/InventoryServiceTest.php` — passed: 3 tests, 15 assertions.
- `php artisan test --compact tests/Feature/ProductTest.php` — passed: 13 tests, 80 assertions.
- `php artisan test --compact` — passed: 83 tests, 413 assertions.
- An initial `InventoryServiceTest` failure came from `RefreshDatabase` wrapping tests in a transaction; the test now stubs `DB::transactionLevel()` and restores the original database manager after the assertion.

### Pending issues

- No known blocking issues.
- Browser-level visual smoke tests remain pending for dashboard, master data, products, suppliers, and purchases pages.
- Purchase edit/delete and stock adjustment workflows are intentionally deferred.
- Sales, customers, payments, and reports are not built yet.

### Next milestone

**Phase 5 — Customers and sales:** implement customer master data and sales orders with line items, server-side pricing/discount calculations, stock decrements through `InventoryService`, permissions for staff create/read and admin full access, and feature tests.

---

## Phase 5 — Customers and sales

### Status

Completed.

### Files created or changed

#### Backend

- `database/migrations/2026_11_17_134211_create_customers_table.php` — customer master data with indexed name/status, optional contact fields, and unique email.
- `database/migrations/2026_11_17_134212_create_sales_table.php` — sale documents with customer, unique invoice number, date, status, notes, totals, creator, and indexes.
- `database/migrations/2026_11_17_134214_create_sale_items_table.php` — immutable sale lines with snapshot product details, quantity, server-calculated price/discount, and a duplicate-product-per-sale guard.
- `app/Models/Customer.php`, `app/Models/Sale.php`, `app/Models/SaleItem.php` — casts, fillable attributes, active/completed scopes, and relationships.
- `app/Models/Product.php`, `app/Models/User.php` — added sale-item and sale relationships; product deletion now checks both purchase and sale history.
- `database/factories/CustomerFactory.php`, `SaleFactory.php`, `SaleItemFactory.php` — test factories, including an inactive customer state.
- `app/Http/Requests/StoreCustomerRequest.php`, `UpdateCustomerRequest.php` — customer validation, lowercased email normalization, and admin authorization.
- `app/Http/Requests/StoreSaleRequest.php` — sale validation, active customer/product existence checks, duplicate line guard, invoice uppercasing, and future-date rejection.
- `app/Policies/CustomerPolicy.php`, `app/Policies/SalePolicy.php` — staff view access for both modules, staff sale creation, admin customer management, and immutable sales.
- `app/Services/InventoryService.php` — added `recordSaleStock()` with row locking, insufficient-stock rejection, duplicate sale movement guard, and the same transaction requirement as purchase movements.
- `app/Services/SaleService.php` — atomic sale creation with server-side catalogue pricing, BigDecimal discounts, line totals, stock decrements, and rollback on failure.
- `app/Exceptions/InsufficientStockException.php` — domain exception handled by the sale controller with a friendly flash error.
- `app/Http/Controllers/CustomerController.php` — paginated/filterable customer CRUD with permission props and a delete guard for customers with sales history.
- `app/Http/Controllers/SaleController.php` — sale index, create, store, and show with filters, real stats, active options, and insufficient-stock handling.
- `app/Http/Controllers/ProductController.php` — extended the transaction-history delete guard to sale items.
- `routes/web.php` — customer and sale resource routes; customer mutations remain admin-only while sale create/read is available to staff.
- `database/seeders/CustomerSeeder.php`, `SaleSeeder.php`, `DatabaseSeeder.php` — three customers and two demo sales recorded through `SaleService`.

#### Frontend

- `resources/js/Pages/MasterData/Customers/Index.vue` — customer stats, search/status filters, paginated table, create/edit modal, and permission-aware actions.
- `resources/js/Pages/Sales/Index.vue` — sale stats, search/customer/date filters, paginated table, and view action.
- `resources/js/Pages/Sales/Create.vue` — dynamic sale line items with catalogue price display, discount inputs, and display-only total preview.
- `resources/js/Pages/Sales/Show.vue` — sale document details, line items, totals, and resulting stock movements.

#### Tests

- `tests/Feature/CustomerTest.php` — 11 tests covering guest/staff/admin access, CRUD, normalization, uniqueness, delete guard, policy, and active scope.
- `tests/Feature/SaleTest.php` — 12 tests covering access, atomic stock updates, server-side pricing/discounts, filters, detail page, validation, rollback, and policy.
- `tests/Feature/InventoryServiceTest.php` — extended from 3 to 9 tests with sale decrement, insufficient stock, duplicate sale movement, non-positive sale quantity, and outside-transaction coverage.
- `tests/Feature/ProductTest.php` — 14 tests after adding the sale-history delete guard.

### What works

- Staff can view customers and sales and create sales; all customer mutations remain admin-only.
- Admin has full customer CRUD and full sale view access; sales are immutable with no edit/delete routes.
- Customer search covers name, contact person, and email; status filters and sale search/customer/date filters preserve query strings.
- Customer email is unique, lowercased, and correctly ignores the customer's own value on update.
- Customers with sales history and products with purchase or sale history cannot be deleted and return friendly flash errors.
- Sale prices always come from `products.sale_price`; client-supplied `unit_price` and `line_total` values are ignored.
- Line discounts are percentage-based and recalculated server-side with `brick/math` BigDecimal at money scale 2 and stock scale 3.
- Sale creation, sale items, stock decrements, and stock movements commit atomically in one database transaction.
- `InventoryService` locks each product row with `lockForUpdate()` and rejects insufficient stock, non-positive quantities, duplicate sale movements, and movements outside a transaction.
- Insufficient stock produces a redirect back to the sale form with the detailed error message and rolls back the whole sale.
- Sale detail shows the customer, creator, totals, line items, and the exact stock movements generated by the sale.
- Seeded data includes 3 customers, 2 sales, 4 sale items, and 4 sale movements; seeded product stock levels reflect the decrements.
- Sidebar Customers and Sales links became active automatically through Ziggy route detection.
- No new dependencies were added in Phase 5.

### Verification results

- `vendor/bin/pint --dirty --format agent` — passed.
- `php artisan route:list --except-vendor --path=customers` — 4 routes registered.
- `php artisan route:list --except-vendor --path=sales` — 4 routes registered.
- `npm run build` — passed.
- `php artisan migrate:fresh --seed --force` — passed; 3 customers, 2 sales, 4 sale items, and 4 sale movements seeded.
- Database check — `SAL-2026-0001` totals `277800.00` after a `14500.00` discount; `SAL-2026-0002` totals `28550.00` after a `2450.00` discount; all four sale movement balances match the seeded stock levels.
- `php artisan test --compact tests/Feature/CustomerTest.php tests/Feature/SaleTest.php tests/Feature/InventoryServiceTest.php tests/Feature/ProductTest.php` — passed: 45 tests, 310 assertions.
- `php artisan test --compact` — passed: 112 tests, 628 assertions.
- Existing `InventoryServiceTest` transaction stubbing was preserved while adding sale movement coverage; the original database manager is restored after each outside-transaction assertion.

### Pending issues

- No known blocking issues.
- Browser-level visual smoke tests remain pending for dashboard, master data, products, suppliers, purchases, customers, and sales pages.
- Sale edit/delete, returns, and stock adjustment workflows are intentionally deferred.
- Payments and reports are not built yet.

### Next milestone

**Phase 6 — Payments and reports:** implement sales-linked payment records, outstanding-balance tracking, role-appropriate access, and real reporting summaries/charts using the existing sales and inventory data.

---

## Phase 6 — Payments and reports

### Status

Completed.

### Files created or changed

#### Backend

- `database/migrations/2026_11_17_142058_create_payments_table.php` — sale-linked receipts with unique receipt number, payment date, amount, method, reference, notes, creator, and indexes.
- `app/Models/Payment.php` — payment methods, labels, decimal/date casts, and sale/creator relationships.
- `app/Models/Sale.php` — payments relationship plus `paid_amount`, `due_amount`, and `payment_status` accessors backed by BigDecimal.
- `app/Models/User.php` — added the recorded-payments relationship.
- `database/factories/PaymentFactory.php` — payment factory with bank-transfer and card states.
- `app/Exceptions/OverpaymentException.php` — domain exception for receipts exceeding the outstanding balance.
- `app/Http/Requests/StorePaymentRequest.php` — sale existence, receipt uniqueness, date/method/amount validation, and receipt/reference/notes normalization.
- `app/Policies/PaymentPolicy.php` — both roles view, only admin records, and payments are immutable.
- `app/Services/PaymentService.php` — atomic receipt creation with `DB::transaction(..., attempts: 5)`, `lockForUpdate()` on the sale, BigDecimal due calculation, and overpayment rejection.
- `app/Services/ReportService.php` — server-side sales/payment/discount totals, lifetime outstanding balance, MariaDB `DATE_FORMAT` monthly trends, top products/customers, payment methods, and inventory snapshot.
- `app/Http/Controllers/PaymentController.php` — paginated filtered payment list, real collection stats, outstanding sale options, permission props, and overpayment flash handling.
- `app/Http/Controllers/ReportController.php` — date-filter validation, default current-year range, `view-reports` authorization, and Inertia render.
- `app/Http/Controllers/SaleController.php` — payment sums on the sale index, outstanding stat, and payment history with creator on the detail page.
- `app/Providers/AppServiceProvider.php` — `view-reports` gate and a refined admin bypass that still honors payment immutability.
- `routes/web.php` — payment index/store routes (store restricted to admin) and the `reports.sales` route.
- `database/seeders/PaymentSeeder.php`, `DatabaseSeeder.php` — two demo receipts recorded through `PaymentService`.

#### Frontend

- `resources/js/Pages/Payments/Index.vue` — collection stats, search/sale/method/date filters, paginated receipt table, and an admin-only record-payment modal.
- `resources/js/Pages/Reports/Sales.vue` — server-filtered report with sales/collection/inventory stats, ApexCharts trend/donut/bar charts, and top customer/low-stock tables.
- `resources/js/Pages/Sales/Index.vue` — paid/due/payment columns, payment status badges, and a live outstanding stat.
- `resources/js/Pages/Sales/Show.vue` — paid/due summary, payment status, and a receipt history table.
- `resources/js/Components/StatusBadge.vue` — paid/partial/unpaid badge styles.

#### Tests

- `tests/Feature/PaymentTest.php` — 11 tests covering guest/staff/admin access, filtering and stats, HTTP recording, receipt normalization, balance updates, overpayment rollback, uniqueness, validation, open-sale options, and the policy matrix.
- `tests/Feature/ReportTest.php` — 12 tests covering guest/inactive/staff/admin access, default and explicit ranges, aggregates, outstanding balance, monthly trends, top products/customers, payment methods, inventory snapshot, invalid filters, and the `view-reports` gate.
- `tests/Feature/SaleTest.php` — extended from 12 to 14 tests with live payment balances on the index and payment history/settlement status on the detail page.

### What works

- Payments are immutable receipts linked to sales; staff and admin can view them, but only an active admin can record one.
- Receipt numbers are normalized to trimmed uppercase on HTTP creation; duplicate receipts are rejected.
- `PaymentService` locks the sale row, recalculates the outstanding balance with BigDecimal at scale 2, and rolls back the entire transaction on overpayment with a friendly flash error.
- Sales expose `paid_amount`, `due_amount`, and `payment_status`; index and show queries eager-load sums/history so the new columns do not introduce N+1 queries.
- Sales index and detail pages display real payment progress alongside totals and stock movements.
- Reports use server-side aggregates only — no client-side recalculation of money — and render monthly sales/collections, payment methods, top products/customers, and current inventory health with ApexCharts.
- The global admin gate bypass now excludes payment update/delete so the immutability policy is enforced for admins too.
- Sidebar Payments and Reports links are visible to staff and admin through the existing permission map and Ziggy route detection.
- No new dependencies were added in Phase 6.

### Verification results

- `vendor/bin/pint --dirty --format agent` — passed; formatted `PaymentController.php`.
- `php artisan route:list --except-vendor --path=payments` — 2 routes registered.
- `php artisan route:list --except-vendor --path=reports` — 1 route registered.
- `npm run build` — passed; ApexCharts is code-split into the reports page chunk.
- `php artisan migrate:fresh --seed --force` — passed; `PaymentSeeder` added both demo receipts.
- Database check — `REC-2026-0001` is `100000.00` by bank transfer on `SAL-2026-0001` (`277800.00`), leaving `177800.00` due; `REC-2026-0002` is `28550.00` cash on `SAL-2026-0002` (`28550.00`), leaving `0.00` due; lifetime sales `306350.00`, lifetime payments `128550.00`, outstanding `177800.00`.
- `php artisan test --compact tests/Feature/PaymentTest.php tests/Feature/ReportTest.php tests/Feature/SaleTest.php` — passed: 37 tests, 448 assertions.
- `php artisan test --compact` — passed: 137 tests, 953 assertions.

### Pending issues

- No known blocking issues.
- Browser-level visual smoke tests remain pending for dashboard, master data, products, suppliers, purchases, customers, sales, payments, and reports pages.
- The ApexCharts bundle adds roughly 967 kB to the reports page chunk; optional lazy-loading can reduce initial report load time.
- Sale edit/delete, returns, and stock adjustment workflows are intentionally deferred.

### Next milestone

**Phase 7 — Dashboard and operational overview:** replace the account-only dashboard with real business KPIs (sales, collections, outstanding balances, stock health), low-stock alerts, and recent sale/payment activity, then perform browser-level smoke tests across all modules.

---

## Phase 7 — Dashboard and operational overview

### Status

Completed.

### Files created or changed

#### Backend

- `app/Services/DashboardService.php` — new service that builds today/month sales and payment KPIs, lifetime outstanding balance, active/stock-health counts, a six-month trend, low-stock alerts, and recent sale/payment activity from live database aggregates.
- `app/Http/Controllers/DashboardController.php` — now injects `DashboardService` and renders the real overview props instead of account-only user counts.
- `app/Services/ReportService.php` — `salesTrend()` is now public so the dashboard can reuse the same monthly aggregate query instead of duplicating SQL.
- `routes/web.php` — unchanged; the dashboard remains available to active admin and staff users.

#### Frontend

- `resources/js/Pages/Dashboard/Index.vue` — rewritten with KPI cards, a six-month sales/collections ApexCharts area chart, a stock-health donut chart, recent sales and payments tables with payment-status badges, and low-stock alerts. The inventory alert link points to the existing reports page because no `inventory.index` route is registered yet.
- `resources/js/Pages/Dashboard.vue` — unused Breeze-era dashboard page left untouched.

#### Tests

- `tests/Feature/DashboardTest.php` — rewritten with 9 tests covering guest/inactive/staff/admin access, today/month sale and payment totals, outstanding balance, stock health, low-stock alerts, recent sales with settlement status, recent payments with creator/customer details, and the six-month trend.
- `tests/Feature/SmokeTest.php` — new HTTP-level smoke coverage that renders every registered staff page and admin page with the expected Inertia component and verifies admin-only pages return 403 for staff.

### What works

- The dashboard shows real sales today/month, collections, outstanding balance, and product stock health rather than static or account-only data.
- Low-stock alerts list active products below their reorder level with stock quantities formatted to three decimal places.
- Recent sales show invoice, customer, date, total, due amount, and paid/partial/unpaid status; recent payments show receipt, linked sale, customer, date, method, amount, and recording user.
- The dashboard reuses `ReportService::salesTrend()`, so its six-month trend uses the same MariaDB `DATE_FORMAT` aggregate as the reports page.
- Every registered application page renders successfully for the appropriate role, and admin-only pages remain forbidden to staff.
- No new dependencies were added in Phase 7.

### Verification results

- `vendor/bin/pint --dirty --format agent` — passed.
- `npm run build` — passed; the new dashboard chart chunk was generated successfully (the existing ApexCharts bundle-size warning remains).
- `php artisan test --compact tests/Feature/DashboardTest.php` — passed: 9 tests, 173 assertions.
- `php artisan test --compact tests/Feature/SmokeTest.php` — passed: 3 tests, 195 assertions.
- `php artisan test --compact` — passed: 145 tests, 1285 assertions.

### Pending issues

- Browser-level visual smoke tests remain pending; HTTP-level smoke coverage now exists but does not verify rendered layout or chart behavior.
- `inventory.index`, `users.index`, and `settings.index` are sidebar placeholders only — the sidebar hides them because those routes are not registered. The dashboard low-stock link intentionally targets `reports.sales` until a dedicated inventory page exists.
- The ApexCharts bundle remains large (roughly 959 kB in the chart chunk); optional lazy-loading can reduce initial load time.
- Sale edit/delete, returns, and stock adjustment workflows remain intentionally deferred.

### Next milestone

**Phase 8 — Inventory overview and stock movements:** add a real view-only inventory page for staff/admin showing current stock levels, reorder alerts, and the stock movement ledger created by purchases and sales, then consider admin-only user/settings management to replace the remaining sidebar placeholders.

---

## Phase 8 — Inventory overview and stock movements

### Status

Completed.

### Files created or changed

#### Backend

- `app/Services/InventoryOverviewService.php` — new read-only service that builds stock-level rows, the stock movement ledger, filtered ledger totals, filter values, and select options. Money uses BigDecimal scale 2 and stock quantities scale 3.
- `app/Http\Controllers\InventoryController.php` — new controller that authorizes the `view-inventory` gate, validates all stock and ledger filters, and renders the page with role-aware reference permissions.
- `app/Providers/AppServiceProvider.php` — added a `view-inventory` gate for active admins and staff.
- `app/Services/ReportService.php` — `inventorySnapshot()` is now public so the inventory page reuses the same stock-value and health calculation as reports.
- `routes/web.php` — registered `GET /inventory` as `inventory.index` for active admins and staff.

#### Frontend

- `resources/js/Pages/Inventory/Index.vue` — new page with stock-health KPIs, a filterable/sortable-by-id stock table, signed-quantity movement ledger, ledger totals, date/type/product/search filters, and role-aware links to sale or purchase documents. The two tables paginate independently through `products_page` and `movements_page`.
- `resources/js/Pages/Dashboard/Index.vue` — low-stock alert link now opens the real inventory page instead of the reports page.
- `resources/js/Components/AppSidebar.vue` — unchanged; the existing Inventory link is now registered and visible automatically through Ziggy route detection.

#### Tests

- `tests/Feature/InventoryTest.php` — new 15-test feature suite covering guest/inactive/staff/admin access, the `view-inventory` gate matrix, stock statistics and stock-value rows, product search/category/stock-status/active filters, movement references and balance display, movement type/product/date filters, movement search, validation errors, and independent pagination.
- `tests/Feature/SmokeTest.php` — added `inventory.index` to the shared staff/admin page smoke coverage.

### What works

- Active staff and admins can view a real inventory overview; inactive users and guests are refused.
- Stock levels are computed from live product data with stock value calculated at purchase price using BigDecimal scale 2.
- Stock rows can be filtered by name/SKU/barcode search, category, stock status, and active state.
- The movement ledger shows purchase, sale, adjustment, and return rows with signed quantities, resulting balances, notes, recorder, and linked sale/purchase references where the user is permitted to view them.
- Ledger totals and rows share the same filters, and the stock and movement tables paginate independently.
- The dashboard low-stock alert and sidebar Inventory link now lead to the new page.
- No new dependencies were added in Phase 8.

### Verification results

- `vendor/bin/pint --dirty --format agent` — passed.
- `npm run build` — passed; the new Inventory page chunk was generated successfully (the existing ApexCharts bundle-size warning remains).
- `php artisan route:list --except-vendor --path=inventory` — 1 route registered.
- `php artisan test --compact tests/Feature/InventoryTest.php tests/Feature/SmokeTest.php` — passed: 18 tests, 445 assertions.
- `php artisan test --compact` — passed: 160 tests, 1535 assertions.

### Pending issues

- Browser-level visual smoke tests remain pending; HTTP-level smoke coverage does not verify rendered layout, filters, or pagination interactions.
- `users.index` and `settings.index` are still hidden sidebar placeholders because no routes are registered for them.
- Stock adjustments and returns are visible in the ledger when recorded, but no write workflow for them exists yet; `InventoryService` remains the sole writer of `products.current_stock`.
- The ApexCharts bundle remains large; optional lazy-loading can reduce initial load time.

### Next milestone

**Phase 9 — User and settings management:** implement admin-only user listing/role management and application settings pages with policies, validation, and tests, replacing the remaining sidebar placeholders.

---

## Phase 9 — User and settings management

### Status

Completed.

### Files created or changed

#### Backend

- `database/migrations/2026_11_17_151218_create_settings_table.php` — new key/value `settings` table with a unique `key` and nullable text `value`.
- `app/Models/Setting.php`, `database/factories/SettingFactory.php`, `database/seeders/SettingSeeder.php` — settings model/factory and default seed data.
- `database/seeders/DatabaseSeeder.php` — now calls `SettingSeeder` before the other seeders.
- `app/Services/SettingsService.php` — default settings, allowed currency/locale lists, merged settings lookup, key whitelist, and transactional updates.
- `app/Services/UserManagementService.php` — transactional user create/update, hashed passwords, verified email addresses, and last-active-admin protection with `lockForUpdate()`.
- `app/Policies/UserPolicy.php`, `app/Policies/SettingPolicy.php` — admin-only access for user management and settings; inactive admins are refused.
- `app/Http/Requests/StoreUserRequest.php`, `UpdateUserRequest.php` — user validation, email normalization, unique-email handling, password confirmation, and policy authorization.
- `app/Http/Requests/UpdateSettingsRequest.php` — validates identity/contact fields plus restricted currency and locale values.
- `app/Http/Controllers/UserController.php` — user directory with search/role/status filters, statistics, reference options, create, and update actions.
- `app/Http/Controllers/SettingsController.php` — settings page and update action.
- `app/Http/Middleware/HandleInertiaRequests.php` — shares saved settings globally as a lazy `settings` prop.
- `app/Models/User.php` — added `email_verified_at` to fillable attributes so admin-created accounts are verified.
- `routes/web.php` — registered `users.index`, `users.store`, `users.update`, `settings.index`, and `settings.update` inside the existing `auth`, `verified`, `role:admin` group.

#### Frontend

- `resources/js/Pages/Users/Index.vue` — new user directory with stats, filters, paginated table, create modal, edit modal, password management, role/status controls, and explicit pagination `meta`.
- `resources/js/Pages/Settings/Index.vue` — new settings form for workspace identity, contact details, currency/locale preferences, and a live preview.
- `resources/js/Components/AppLogo.vue` — displays the saved `app_name`.
- `resources/js/Components/AppSidebar.vue` — displays the saved `company_name`; Users and Settings links now appear automatically because their routes are registered.
- `resources/js/Composables/useCurrency.js` — already referenced shared settings; it now receives real saved `currency_symbol` and `locale` values.

#### Tests

- `tests/Feature/UserManagementTest.php` — new 18-test feature suite covering guest/staff/inactive-admin denial, policy matrix, filters/statistics, create/update validation, hashing/verification, unchanged passwords, duplicate emails, and last-active-admin protection.
- `tests/Feature/SettingsTest.php` — new 11-test feature suite covering guest/staff/inactive-admin denial, policy matrix, defaults, seeder, persistence, globally shared props, validation, and unrecognized-key rejection.
- `tests/Feature/SmokeTest.php` — added `users.index` and `settings.index` to admin page smoke coverage.

### What works

- Admin-only user management is backed by the database with real filtering, pagination, statistics, create, and update workflows; there is no delete route.
- Staff, guests, and inactive admins cannot view or modify users.
- Admin-created accounts have hashed passwords and verified email addresses; blank passwords during updates leave the existing password untouched.
- The last active administrator cannot be demoted or deactivated; the guard uses a transactional row lock and returns field-level validation errors.
- Application settings persist to the database and fall back to service defaults for missing keys.
- Settings support seven ISO 4217 currency codes and four locales; unsupported values and unrecognized keys are rejected or ignored.
- Saved settings are shared through all Inertia pages, so the sidebar app name/company name and currency formatter reflect real saved values.
- Public registration remains enabled and unchanged.
- No new dependencies were added in Phase 9.

### Verification results

- `npm run build` — passed after correcting the new pages' relative imports to `../../`; the existing ApexCharts chunk-size warning remains.
- `vendor/bin/pint --dirty --format agent` — passed.
- `php artisan migrate --no-interaction` — applied `2026_11_17_151218_create_settings_table`.
- `php artisan db:seed --class=SettingSeeder --no-interaction` — seeded the seven default settings.
- `php artisan route:list --except-vendor` — 57 routes registered, including the three `users.*` and two `settings.*` routes.
- `php artisan test --compact tests/Feature/UserManagementTest.php tests/Feature/SettingsTest.php tests/Feature/SmokeTest.php` — passed: 32 tests, 405 assertions.
- `php artisan test --compact` — passed: 189 tests, 1725 assertions.
- The initial targeted-test failure was traced to `email_verified_at` missing from `User` fillable attributes; adding it fixed account verification without changing public registration.

### Pending issues

- Browser-level visual smoke tests remain pending across all modules; HTTP smoke coverage does not verify rendered layout or interactions.
- User management intentionally supports index/create/update only — there is no delete workflow.
- Currency selection is limited to the seven ISO 4217 codes defined in `SettingsService::CURRENCIES`.
- `Pagination.vue` still expects a `meta` prop while older pages pass serialized Laravel paginators with flat fields; the new Users page passes explicit `meta`, but older pages can show “0 total records”.
- Stock adjustments and returns remain visible in the ledger only when recorded; no write workflow exists yet.
- The ApexCharts bundle remains large; optional lazy-loading can reduce initial load time.

### Next milestone

**Phase 10 — QA and UI polish:** perform real browser-level visual smoke tests where browser tooling is approved, normalize pagination props across older pages, optionally lazy-load chart bundles, and verify seeded demo data end-to-end.

---

## Phase 10 — QA and UI polish

### Status

Completed. Real-browser testing remains intentionally pending because no browser-automation dependency or driver is installed or approved.

### Files created or changed

#### Frontend

- `resources/js/Components/Pagination.vue` — now accepts one `paginator` object and derives `links`, `from`, `to`, and `total` directly from Laravel's serialized paginator; removed unused Lucide imports.
- `resources/js/Pages/Payments/Index.vue`, `MasterData/Units/Index.vue`, `Products/Index.vue`, `MasterData/Suppliers/Index.vue`, `Purchases/Index.vue`, `Sales/Index.vue`, `MasterData/Customers/Index.vue`, `MasterData/Categories/Index.vue`, `Users/Index.vue` — normalized to `<Pagination :paginator="..." />`.
- `resources/js/Pages/Inventory/Index.vue` — both the stock table and movement ledger now pass their paginators through the same component API.
- `resources/js/Components/ChartPlaceholder.vue` — new lightweight loading placeholder for asynchronously loaded charts.
- `resources/js/Pages/Dashboard/Index.vue`, `resources/js/Pages/Reports/Sales.vue` — replaced static `vue3-apexcharts` imports with `defineAsyncComponent`, using `ChartPlaceholder` as the immediate fallback.

#### Tests

- `tests/Feature/DatabaseSeederTest.php` — new two-test feature suite that seeds the full demo dataset, asserts cross-module counts and sale balances, renders every admin page with seeded data, and verifies dashboard/inventory payloads.

#### Documentation

- `PROJECT_CHAT.md` — records this phase.

### What works

- Pagination now displays real `Showing X–Y of Z` metadata on all paginated pages instead of falling back to `0 total records`.
- Inventory's stock and movement tables keep independent pagination while sharing the normalized component contract.
- Chart libraries are no longer statically imported into Dashboard and Reports page chunks; they load on demand with a visible placeholder.
- The complete seeder creates a consistent demo dataset: 7 settings, 2 users, 4 categories, 5 units, 5 products, 3 suppliers, 2 purchases, 4 purchase items, 3 customers, 2 sales, 4 sale items, 2 payments, and 8 stock movements.
- Seeded sales settle correctly: `SAL-2026-0001` is partially paid (277800.00 total, 100000.00 paid, 177800.00 due) and `SAL-2026-0002` is fully paid (28550.00 total and paid).
- The seeded admin can render every admin page, and the dashboard/inventory props reflect the seeded sales, payments, products, and movements.
- No new dependencies were added in Phase 10.

### Verification results

- `php artisan test --compact tests/Feature/DatabaseSeederTest.php` — passed: 2 tests, 204 assertions.
- `npm run build` — passed; `vue3-apexcharts` is now emitted as an async chunk and dynamically imported by the Dashboard and Reports page chunks.
- `vendor/bin/pint --dirty --format agent` — passed.
- `php artisan test --compact` — passed: 191 tests, 1929 assertions.
- Read-only database verification against the dev database confirmed the seeded counts, sales totals, payment totals, and per-sale balances listed above.
- Browser tooling check found no `laravel/dusk`, Playwright, Puppeteer, DuskTestCase, browser suites, or browser-driver commands; real-browser smoke tests cannot run without adding approved tooling.

### Pending issues

- Real-browser visual smoke tests remain pending across all modules; HTTP-level smoke tests do not verify rendered layout, Tailwind styling, chart rendering, or interactions.
- The chart bundle remains roughly 960 kB, although it is now fetched only when Dashboard or Reports loads; the Vite chunk-size warning remains.
- `Pagination.vue` now requires a `paginator` prop; all current usages were updated, but any future page must follow this contract.
- Sale edit/delete, returns, stock adjustment write workflows, and user deletion remain intentionally deferred.

### Next milestone

**Phase 11 — Release hardening:** run real browser visual/responsive smoke tests once browser tooling is approved, perform an accessibility pass across shared components, and prepare a production deployment checklist.

---

## Phase 11 — Release hardening

### Status

Completed for all work that does not require new dependencies or browser tooling. Real-browser visual and responsive smoke tests remain blocked because no browser-automation dependency or driver is installed or approved.

### Files created or changed

#### Frontend — forms and inputs

- `resources/js/Components/FormInput.vue`, `FormSelect.vue`, `FormTextarea.vue` — added generated hint/error IDs, `aria-describedby`, `aria-invalid`, `role="alert"` on error text, and an `aria-label` fallback derived from `name`/placeholder when no visible label exists. Attribute-derived descriptions are preserved by merging them with generated IDs.
- `resources/js/Components/SearchInput.vue` — the search field now uses the placeholder text as its accessible name.
- `resources/js/Components/InputError.vue` — error text now has `role="alert"`.

#### Frontend — navigation and structure

- `resources/js/Components/Pagination.vue` — active page links expose `aria-current="page"`; disabled entries expose `aria-disabled="true"`.
- `resources/js/Components/NavLink.vue`, `ResponsiveNavLink.vue` — active links expose `aria-current="page"`.
- `resources/js/Components/AppSidebar.vue` — sidebar `<nav>` is labelled `Main navigation`; active items expose `aria-current="page"`.
- `resources/js/Components/AppHeader.vue` — breadcrumb `<nav>` is labelled `Breadcrumb` with `aria-current="page"` on the last crumb; profile toggle has `aria-label`, `aria-controls`, and `aria-expanded`; the dropdown menu now has the matching `id`.
- `resources/js/Layouts/AuthenticatedLayout.vue` — primary/mobile navigation landmarks are labelled, the logo link is named, the profile trigger is named, and the hamburger button now has `type="button"`, `aria-label`, `aria-controls`, and `aria-expanded`.
- `resources/js/Layouts/AuthLayout.vue`, `GuestLayout.vue` — page content now uses a `<main>` landmark; the decorative logo link in `GuestLayout` has an accessible name.
- `resources/js/Components/ApplicationLogo.vue` — decorative SVG is `aria-hidden="true"`; both logo links supply their own names.

#### Frontend — dialogs, data, and feedback

- `resources/js/Components/Modal.vue` — added `label`/`labelledby` props for an accessible dialog name, `nextTick()` before `showModal()`, an immediate watcher so initially-open modals work, and focus restoration to the triggering element on close. `close()` now guards against closing a non-open dialog.
- `resources/js/Components/ConfirmDialog.vue` — passes `:label="title"` to `Modal`.
- `resources/js/Components/DataTable.vue` — new `caption` prop rendered as a visually hidden `<caption>`; the loading cell is a polite live region.
- `resources/js/Components/NotificationToast.vue` — toast container is a polite `status` live region; error notifications use `role="alert"`.
- `resources/js/Components/ChartPlaceholder.vue` — loading state is a polite `status` live region.
- `resources/js/Components/PrimaryButton.vue`, `DangerButton.vue` — explicit `type` prop with the native `submit` default, so existing form submissions are unchanged while callers can opt out.

#### Configuration and documentation

- `.env.example` — aligned with the application: `APP_NAME="Mini Inventory"`, production-safe `APP_ENV`/`APP_DEBUG`/`LOG_LEVEL`, and MariaDB-compatible `DB_CONNECTION=mysql` with `mini_inventory` defaults. Local-development overrides are documented in a header comment.
- `PROJECT_CHAT.md` — records this phase and the production deployment checklist.

### What works

- All shared form controls now have programmatic names and expose validation/hint text to assistive technology.
- Current-page state is announced in sidebar, top navigation, breadcrumbs, and pagination.
- Modals are named dialogs, open reliably even when initially visible, and return focus to the element that opened them.
- Tables can receive a screen-reader-only caption, and loading/empty/notification states are announced without visual changes.
- The frontend production build still succeeds with no new dependencies.
- The `.env.example` template now matches the project name and the MariaDB-backed local setup, with production-safe defaults.

### Verification results

- `npm run build` — passed: 2943 modules transformed, no compile errors. The known chunk-size warning remains for the two ~960 kB chart chunks, which are still loaded asynchronously on Dashboard and Reports.
- `vendor/bin/pint --dirty --format agent` — passed.
- `php artisan test --compact` — passed: 191 tests, 1929 assertions (unchanged baseline; Phase 11 changed no PHP logic).
- `php artisan route:cache` — passed.
- `php artisan config:cache` — passed.
- `php artisan view:cache` — passed.
- `php artisan event:cache` — passed.
- `php artisan optimize:clear` — passed; all caches cleared again after verification so local development is unaffected.
- `php artisan about --only=environment` — confirmed `Mini Inventory`, Laravel 13.42.0, PHP 8.3.31, `local` environment, debug enabled, `localhost:8000`, maintenance mode off.

### Production deployment checklist

1. Copy `.env.example` to `.env`, generate `APP_KEY` with `php artisan key:generate`, and set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, and `LOG_LEVEL=info` or stricter.
2. Provision a MariaDB database and set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`; keep `DB_CONNECTION=mysql`.
3. Configure a real `SESSION_DRIVER`, `CACHE_STORE`, and `QUEUE_CONNECTION` backend as appropriate for the target host (the defaults use the `database` driver, so the migrations must be present).
4. Replace `MAIL_MAILER=log` with the production mailer and credentials so password-reset and verification emails are delivered.
5. Run `php artisan migrate --force`, then `php artisan db:seed --force` only if demo data is intended; otherwise seed only `SettingSeeder`.
6. Run `php artisan optimize` (or `route:cache`, `config:cache`, `view:cache`, `event:cache`) after deployment, and `php artisan optimize:clear` before making configuration changes.
7. Build assets with `npm ci && npm run build` and serve `public/` as the document root.
8. HTTPS, secure cookies, and correct `SESSION_DOMAIN` are the web server's responsibility; verify them separately.
9. Change the demo account passwords (`admin@inventory.test`, `staff@inventory.test`) or remove those accounts before any real deployment.

### Pending issues

- Real-browser visual, responsive, and interaction smoke tests remain blocked: no `laravel/dusk`, Playwright, Puppeteer, browser suite, or driver is installed, and adding tooling requires approval.
- The chart bundle remains roughly 960 kB; it is only fetched on Dashboard/Reports but still triggers Vite's chunk-size warning.
- Automated accessibility regression tests are not present; the fixes above were verified by build and code review, not by a browser-based accessibility scanner.
- Sale edit/delete, returns, stock adjustment write workflows, and user deletion remain intentionally deferred.
- All work remains uncommitted in the parent repository (`?? ./`).

### Next milestone

**Phase 12 — Browser verification and final sign-off:** with approved browser tooling, run real visual/responsive smoke tests across every module, confirm screen-reader behavior for the new accessibility attributes, and address any defects found before the first release commit.

---

## Phase 12 — Browser verification and final sign-off

### Status

Completed. Real-browser verification was performed with the already installed system Chrome through the Chrome DevTools Protocol. No browser-automation package or other dependency was added to the project.

### Files created or changed

#### Frontend

- `resources/js/Components/AppSidebar.vue` — imported Inertia's `Link` component, replaced the unavailable `page.props.ziggy?.routes` lookup with `route().has(item.routeName)`, and kept active-link `aria-current="page"` support. Without this fix the workspace navigation rendered as an empty `<ul>`.
- `resources/js/Components/FormInput.vue`, `FormSelect.vue`, `FormTextarea.vue` — preserved an explicitly passed `aria-label` when generating the accessible-name fallback for controls without a visible label.
- `resources/js/Layouts/AppLayout.vue` — added `<Head :title="headerTitle" />` and a route-section label map so pages no longer showed a generic or empty document title.
- `resources/js/Pages/Dashboard/Index.vue` — added `min-w-0` to chart and recent-table grid children so wide tables no longer force horizontal scrolling on mobile.

#### Documentation

- `PROJECT_CHAT.md` — records the browser verification, defects found, fixes, and final sign-off.

#### Temporary verification artifacts outside the repository

- `browser_smoke2.ps1` — headless Chrome smoke script covering login, nine desktop routes, and two mobile routes.
- `browser_interactions.ps1` — role/navigation and modal focus interaction script.
- `probe_overflow.ps1` — DOM diagnostic script used to locate the dashboard overflow and empty sidebar.
- `browser-report-2.json`, `browser-issues-2.txt`, `browser-shots-2/` — final DOM/accessibility metrics, browser error log, and screenshots.

### Defects found and fixed

1. **Sidebar rendered no links.** The markup used `<Link>` without importing it, and its route filter depended on `page.props.ziggy?.routes`, which was unavailable in the Inertia page props. The rendered workspace `<ul>` was empty. Both causes were fixed and verified: admin now sees 13 navigation links, active links expose `aria-current="page"`, and staff sees exactly the seven permitted links.
2. **Mobile dashboard horizontal overflow.** At a 390 px viewport the dashboard had `scrollWidth 610` and `clientWidth 390`; wide recent-sales/recent-payments tables inside grid children were the offenders. Adding `min-w-0` to the affected grid children reduced both measured widths to 390.
3. **Unnamed Inventory date filters.** The `From date` and `To date` inputs passed `aria-label`, but the form components' fallback logic treated that attribute as if a visible label existed. The fix preserves the explicit accessible names.
4. **Generic page titles and headings.** Most routes produced `- Mini Inventory` as the document title and derived unhelpful headings such as `Index`. `AppLayout` now supplies `Dashboard - Mini Inventory`, `Products - Mini Inventory`, etc.; browser checks confirmed meaningful `<h1>` values on all captured routes.

### What works

- Real admin login through the application form succeeds and produces a genuine authenticated browser session.
- All nine admin routes render their Inertia pages with real database-backed content: Dashboard, Products, Sales, Inventory, Payments, Reports, Purchases, Users, and Settings.
- Desktop and mobile screenshots were captured for every changed/responsive case.
- Every audited page has one active `aria-current` item, zero unnamed form controls, zero buttons without an explicit type, no horizontal overflow, and the expected page heading.
- The admin New Product dialog opens as a labelled dialog, places focus inside it, closes on Escape, and returns focus to the `New product` trigger.
- Staff navigation is correctly limited to Dashboard, Products, Sales, Inventory, Customers, Payments, and Reports.
- Staff access to `/users` is denied with `403 You do not have permission to access this resource.`
- No runtime JavaScript exceptions or browser console errors were captured in the final runs.

### Verification results

- Final browser report — all 11 captured states reported `current=1`, `unnamed=0`, and `overflow=False` with correct `<h1>` values:
  - desktop: Dashboard, Products, Sales, Inventory, Payments, Reports, Purchases, Users, Settings;
  - mobile: Dashboard, Products.
- Admin interaction result — navigation contained 13 permitted links; modal output was `OPEN={"open":true,"label":"Dialog",...}` and `AFTER={"open":false,"active":"New product"}`.
- Staff interaction result — `STAFF_NAV=["Dashboard","Products","Sales","Inventory","Customers","Payments","Reports"]`; `/users` returned the expected 403 message.
- Browser issue logs — `ISSUES=0`; no runtime exceptions or console errors.
- `npm run build` — passed after the final frontend fixes. The known chunk-size warning remains for the two approximately 960 kB chart chunks, which are loaded asynchronously by Dashboard and Reports.
- `vendor/bin/pint --dirty --format agent` — passed; no PHP files changed in Phase 12.
- `php artisan test --compact` — passed with the unchanged 191-test, 1929-assertion baseline.
- The temporary browser scripts, profiles, report, and screenshots live outside the repository; no package or Composer dependency was added.

### Pending issues

- Automated pixel-by-pixel visual review was not performed by the assistant; screenshots were saved for optional human inspection.
- No browser suite was committed to the project, so future code changes will need either a rerun of the temporary scripts or approval to add a permanent browser-testing dependency.
- The asynchronous chart bundle still triggers Vite's chunk-size warning, although it does not block the build or initial page rendering.
- Sale edit/delete, returns, stock adjustment write workflows, and user deletion remain intentionally deferred.
- All project work is still uncommitted in the parent repository (`?? ./`).

### Next milestone

**First release:** optionally inspect the saved screenshots, review the complete diff, run the production build and full test suite once more, then create the first release commit (and tag, if desired). Cloud deployment can follow separately with the `deploying-to-cloud` skill.

---

## Release v1.0.0 — First release

### Status

Completed.

### Files created or changed

- `PROJECT_CHAT.md` — records the first release in the running project log.

### What works

- The complete Mini Inventory Management System was committed to the parent repository as its first release.
- An annotated `v1.0.0` tag points to the release commit.
- Only files under `Laravel/Mini Inventory Management System/` were staged. Unrelated modified and untracked work elsewhere in the parent repository was left untouched.
- `.env`, `vendor/`, `node_modules/`, and `public/build/` remain ignored, so no secrets or generated dependencies were committed.

### Verification results

- `npm run build` — passed.
- `php artisan test --compact` — passed: 191 tests, 1929 assertions.
- Final browser verification — passed: all pages rendered correctly, no runtime errors, roles behaved as expected, and `ISSUES=0`.
- The release commit contains 344 files.

### Pending issues

- The release has not been pushed to the remote repository.
- Optional human pixel review of the saved screenshots remains available.
- Cloud deployment has not been performed; use the `deploying-to-cloud` skill when requested.
- Deferred product enhancements remain unchanged: sale edit/delete, returns, stock-adjustment write workflows, and user deletion.

### Next milestone

**Optional deployment or next feature phase:** push the commit/tag when ready, deploy using Laravel Cloud if desired, or begin the next requested enhancement from a clean committed baseline.

---

## Phase 13 — Default route redirect

### Status

Completed.

### Files created or changed

#### Backend

- `routes/web.php` — `/` now always redirects to `dashboard`; removed the unused `Application` and `Inertia` imports while retaining the `welcome` route name for compatibility.

#### Tests

- `tests/Feature/ExampleTest.php` — the root smoke test now asserts a redirect to the dashboard instead of a successful Welcome response.
- `tests/Feature/RootRouteTest.php` — new feature tests cover guest and authenticated root redirects plus the final destinations after following redirects: login for guests and dashboard for authenticated users.

#### Documentation

- `PROJECT_CHAT.md` — records the default-route change and verification results.

### What works

- Guest requests to `/` receive a redirect to `/dashboard`, and the dashboard's `auth` middleware then sends them to `/login`.
- Authenticated requests to `/` receive a redirect to `/dashboard`, which renders the dashboard for verified admin and staff users.
- The `welcome` route name remains registered, so any existing references to it continue to resolve.

### Verification results

- `vendor/bin/pint --dirty --format agent` — passed.
- `php artisan test --compact tests/Feature/ExampleTest.php tests/Feature/RootRouteTest.php` — passed: 5 tests, 26 assertions.
- `php artisan test --compact` — passed: 195 tests, 1954 assertions.
- `npm run build` — passed. The known asynchronous chart chunk-size warning remains unchanged.

### Pending issues

- `resources/js/Pages/Welcome.vue` is now unused but has been retained for compatibility and possible future use.
- The first-release commit and tag have not been pushed to the remote repository.
- This phase's changes are not yet committed; they can be committed separately from `v1.0.0`.

### Next milestone

**Optional follow-up commit:** commit the default-route change, or continue directly with the next requested enhancement.

---

## Phase 14 — README demo credentials

### Status

Completed.

### Files created or changed

#### Documentation

- `README.md` — added a **Demo accounts** section with both seeded email addresses, their roles, the shared `password` password, and the `php artisan migrate --seed` command that creates them.
- `PROJECT_CHAT.md` — records the documentation update.

### Verification results

- Markdown table reviewed in `README.md`; no application code changed, so the test suite and production build were not rerun.

### Next milestone

**Optional follow-up commit:** include this documentation update with the root-route change, or continue with the next requested enhancement.
