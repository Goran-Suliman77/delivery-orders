# Delivery Delivery API

A RESTful, API-only backend for a local delivery platform in Latakia, Syria. Delivery connects customers with restaurants and city shops, coordinates drivers, manages orders, and provides shared operational reports.

The project is built with **Laravel**, **PHP**, **MySQL**, and **Eloquent ORM**. It emphasizes centralized query rules, thin controllers, explicit business services, safe order-state transitions, authorization, consistent JSON responses, automated tests, and performance checks against realistic data volumes.

> **Implementation note:** This README defines the target architecture and API contract for the Delivery project. The project is being developed incrementally, so verify each endpoint and class against the current source code before assuming every item documented here is already implemented.

---

## About the Project

Delivery is a delivery platform with two kinds of merchants:

- **Restaurants** that prepare meals.
- **City shops** such as mini markets, pharmacies, and other local stores.

Restaurants and city shops use a shared `Store` entity. Their category is represented by the `type` field; they are not separate database entities.

The API supports:

- Customer registration, login, logout, and current-user information.
- Public browsing of active stores and active products.
- Order creation with server-side pricing and delivery-fee calculation.
- Order tracking and controlled status transitions.
- Driver assignment and availability management.
- Administrative store, product, and driver management.
- Shared operational statistics and order reports.
- Role- and permission-based access control.
- A demo dataset with at least **1,000 orders** for trying the API and investigating performance.

The application is **API-only**. It does not use Blade pages or provide a frontend interface.

---

## Technologies

- PHP
- Laravel
- MySQL
- Laravel Eloquent ORM
- Laravel Sanctum token authentication
- Form Requests and validation rules
- PHP backed Enums
- Local Eloquent query scopes and a limited Global Scope for public store visibility
- Service layer and dependency injection
- Policies and Gates
- Database transactions and row-level locking
- PHPUnit / Laravel Feature Tests (or Pest if configured)
- RESTful JSON API

Check `composer.json` and the installed Laravel version in the project before documenting a fixed framework version.

---

# Main Entities

## User

Represents an account in the system. A user may be a customer, a driver account, a staff member, or an administrator, based on assigned roles.

Typical fields:

- `id`
- `name`
- `email`
- `password`

Relationships:

```text
User 1 ──────── 0..1 Driver
User 1 ──────── * Orders (as customer)
User * ──────── * Roles
```

Passwords must never be returned in API responses. Public registration assigns the `customer` role on the server; a request must not be able to assign itself `admin` or other privileged roles.

## Role and Permission

`Role` and `Permission` define authorization centrally.

Relationships:

```text
User * ──────── * Role
Role * ──────── * Permission
```

Pivot tables:

- `role_user`
- `permission_role`

Roles used by the project:

- `customer`
- `driver`
- `staff`
- `admin`

Examples of permissions include `orders.create`, `orders.view_own`, `orders.view_all`, `orders.assign_driver`, `stores.manage`, `products.manage`, `drivers.manage`, and `reports.view`.

Authentication proves who the caller is; Policies and permissions decide what that caller is allowed to do.

## Driver

Represents delivery details associated with a user account.

Typical fields:

- `id`
- `user_id`
- `vehicle_type`
- `vehicle_number`
- `is_available`

Relationships:

```text
User 1 ──────── 0..1 Driver
Driver 1 ────── * Orders
```

`is_available` indicates whether a driver may be assigned a new order. Assignment and order completion must update this value consistently. A driver should become available after a delivery is completed or an assigned order is cancelled only when no other active order remains assigned to that driver.

## Store

Represents a restaurant or another local merchant.

Typical fields:

- `id`
- `name`
- `type` (for example, `restaurant`, `mini_market`, or `pharmacy`)
- `is_active`

Relationships:

```text
Store 1 ──────── * Products
Store 1 ──────── * Orders
```

All merchant types are stored in the same `stores` table. Public store queries must exclude inactive stores. Administrative queries may bypass the active-store Global Scope so administrators can inspect and reactivate stopped stores.

## Product

Represents an item offered by a store.

Typical fields:

- `id`
- `store_id`
- `name`
- `price`
- `is_active`

Relationships:

```text
Store 1 ──────── * Products
Product 1 ────── * Order Items
```

Public product queries must exclude inactive products and products whose store is inactive. Administrative endpoints may show inactive products for maintenance.

## Order

Represents a customer's order from one store.

Typical fields:

- `id`
- `customer_id`
- `store_id`
- `driver_id` (nullable until assignment)
- `status`
- `subtotal`
- `delivery_fee`
- `total`
- timestamps

Relationships:

```text
User   1 ──────── * Orders (customer)
Store  1 ──────── * Orders
Driver 1 ──────── * Orders (optional driver)
Order  1 ──────── * Order Items
```

The customer identity is taken from the authenticated user. The API must not trust a client-supplied `customer_id` when creating an order.

## OrderItem

Represents a product and quantity captured for a particular order.

Typical fields:

- `id`
- `order_id`
- `product_id`
- `quantity`
- `price`

`price` is a snapshot of the product price at the time the order was created. Updating a product's catalog price must not rewrite the price on historical order items.

---

# Entity Relationship Overview

```text
User ─────────── Driver ─────────── Orders ─────────── Order Items ─────────── Product
 │                                  │                                            │
 │                                  │                                            │
 ├────────────── Customer Orders     └──────── Store ────────────────────────────┘
 │
 └────────────── Role ────────────── Permission
```

More specifically:

```text
User     1 ──────── 0..1 Driver
User     1 ──────── * Orders (customer_id)
Store    1 ──────── * Products
Store    1 ──────── * Orders
Driver   1 ──────── * Orders (driver_id)
Order    1 ──────── * Order Items
Product  1 ──────── * Order Items
User     * ──────── * Roles
Role     * ──────── * Permissions
```

---

# Order Status

Order status is represented by a PHP backed Enum named `OrderStatus`.

Expected statuses:

```php
enum OrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PREPARING = 'preparing';
    case READY = 'ready';
    case ASSIGNED = 'assigned';
    case PICKED_UP = 'picked_up';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
}
```

The exact case names in the source Enum must remain consistent with application code. The allowed transition map belongs in the Enum or a dedicated state-transition component, not duplicated across controllers.

A typical lifecycle is:

```text
pending → confirmed → preparing → ready → assigned → picked_up → delivered
    └─────────────── cancellation where permitted ───────────────────→ cancelled
```

Assignment is a dedicated operation because it must attach a driver, update order status, and mark the driver unavailable atomically. Under the current business rules, `delivered` and `cancelled` are terminal states; a `picked_up` order cannot be cancelled through the ordinary status endpoint.

## Active Orders

An order is active when it has not been delivered and has not been cancelled.

The definition is centralized in the `Order::active()` Local Scope. Code must not repeat the active-status condition as handwritten query clauses in controllers or services.

Conceptually:

```php
Order::query()->active();
```

The scope should be reused wherever active orders are needed, including operational lists, driver workload checks, and active-order statistics.

---

# Query Rules and Local Scopes

Repeated business query conditions must be defined once as reusable Local Scopes (or another single, named query abstraction when appropriate). Controllers and services should compose scopes rather than copy the same conditions into multiple locations.

Examples of useful `Order` scopes include:

- `active()`
- `status($status)`
- `forCustomer($customerId)`
- `forDriver($driverId)`
- `forStore($storeId)`
- `withoutDriver()`
- `olderThanMinutes($minutes)`
- `today()`

Useful scopes for `Product`, `Store`, and `Driver` should also be introduced when a repeated filter becomes a business rule, such as public availability, active products, store type, administration visibility, available drivers, or drivers with/without active orders. Avoid defining the same business predicate independently in controllers and services. Request-specific optional filters may be composed by calling named scopes with validated parameters.

One-off request filters may still be composed dynamically, but any condition representing a repeated business rule must have one canonical definition. The Global Scope for active stores is reserved for public visibility; administrative queries must bypass that specific scope explicitly when they need to view inactive stores.

---

# Business Rules

## 1. Orders belong to the authenticated customer

Order creation takes `customer_id` from `$request->user()->id`. A client cannot create an order on behalf of another customer by sending a different ID.

## 2. An order uses one active store

An order refers to one store. Public ordering is allowed only when the store is active.

## 3. Ordered products must belong to that store

Every requested product must exist, be active, and belong to the store selected for the order. A product ID from another store must be rejected.

## 4. Product prices are snapshotted

The server reads the current product price and saves it to `order_items.price` during order creation. Clients must not submit trusted prices or totals. The server calculates subtotal, delivery fee, and total.

## 5. Active order logic is centralized

`Order::active()` is the canonical definition. Reports and order lists that compare active orders must call the same scope and use consistent date/store filters.

## 6. Driver assignment is safe under concurrency

Assignment must execute in a database transaction and lock relevant records. It must verify that:

- The order is in `ready` state.
- The order has no driver assigned yet.
- The selected driver is available.
- The driver has no other active order.

Then the service assigns the driver, changes the order to `assigned`, and marks the driver unavailable atomically.

## 7. Driver availability is restored safely

After an order is delivered or cancelled, the service checks whether the driver still has another active order. The driver is marked available only when no active order remains assigned to them.

## 8. Inactive stores and products are hidden publicly

Public store lists and details exclude inactive stores. Public product lists and details exclude inactive products and products whose store is inactive. Administrators can still inspect inactive records using dedicated, authorized endpoints.

## 9. Historical records are retained

Stores and products should normally be deactivated rather than deleted when historical orders reference them. Order-item price snapshots preserve the historical price charged.

---

# Architecture: Thin Controllers and Service Layer

Controllers should remain thin. They handle the HTTP boundary only:

- Invoke authorization.
- Receive validated input from Form Requests.
- Call a service or contract.
- Transform the result into a resource or the standard API response.

Business logic belongs in service classes, not in controllers. Query rules that are reused belong in scopes/query abstractions, not as repeated handwritten conditions inside controllers or services.

Target service responsibilities:

| Class | Responsibility |
|---|---|
| `OrderService` | Order creation transaction, store/product checks, and persistence of order items. |
| `OrderPricingService` | Server-side subtotal, delivery fee, total, and item price snapshots. |
| `DeliveryFeeService` | Resolve configured delivery fees. |
| `OrderStatusService` | Enforce status transitions and restore driver availability when a job finishes. |
| `DriverAssignmentService` | Safely assign an available driver to a ready order. |
| `OrderStatisticsService` | Shared active-order summaries and operational reports. |
| `StoreQueryService` | Public store lists and details. |
| `ProductQueryService` | Public product lists, details, and products for an active store. |
| `StoreManagementService` | Administrative store creation, update, listing, activation, and deactivation. |
| `ProductManagementService` | Administrative product creation, update, listing, activation, and deactivation. |
| `OrderQueryService` | Order listing, visibility-scoped queries, and detail relationship loading. |
| `AdminDriverQueryService` (if implemented) | Administrative driver listing and details. |

### Service contracts and bindings

Where a stable abstraction is useful, controllers depend on an interface and Laravel's service container binds that interface to its implementation in `AppServiceProvider`. The expected contracts used in the training architecture are:

- `OrderServiceInterface` → `OrderService`
- `OrderQueryServiceInterface` → `OrderQueryService`
- `OrderStatusServiceInterface` → `OrderStatusService`
- `DriverAssignmentServiceInterface` → `DriverAssignmentService`
- `StoreQueryServiceInterface` → `StoreQueryService`
- `ProductQueryServiceInterface` → `ProductQueryService`
- `StoreManagementServiceInterface` → `StoreManagementService`
- `ProductManagementServiceInterface` → `ProductManagementService`

Only list a contract as implemented after its file exists and its binding resolves in the running application. Match method signatures in each interface to the implementation; PHP will reject incompatible signatures. Laravel's service container supports interface-to-implementation bindings and constructor injection. See the official [Service Container documentation](https://laravel.com/docs/container).

Expected request flow:

```text
HTTP Request
     ↓
Auth Middleware
     ↓
Form Request Validation
     ↓
Policy / Gate Authorization
     ↓
Thin Controller
     ↓
Service / Contract
     ↓
Eloquent Model + Query Scopes
     ↓
Database Transaction / MySQL
     ↓
Resource + ApiResponse JSON
```

---

# Roles and Permissions

The role seeder should create or update the following roles and permissions idempotently.

## Roles

- `customer`
- `driver`
- `staff`
- `admin`

## Example permissions

- `orders.create`
- `orders.view_own`
- `orders.view_all`
- `orders.view_assigned`
- `orders.update_status`
- `orders.update_any_status`
- `orders.update_assigned_status`
- `orders.assign_driver`
- `orders.filter_by_customer`
- `stores.view_all`
- `stores.manage`
- `products.manage`
- `drivers.view_all`
- `drivers.manage`
- `reports.view`
- `users.manage`

Assigning `admin` must be a trusted administrative operation, never a field accepted by public registration. Exact permission assignments should be maintained in the seeder so they can be reproduced in development, testing, and deployment environments.

---

# API Endpoints

All endpoints return JSON. Protected endpoints require a valid Sanctum token and the appropriate permission/policy check.

## Authentication

| Method | Endpoint | Purpose | Authentication |
|---|---|---|---|
| `POST` | `/api/auth/register` | Register a customer account | No |
| `POST` | `/api/auth/login` | Log in and issue a token | No |
| `GET` | `/api/auth/me` | Get the authenticated user | Yes |
| `POST` | `/api/auth/logout` | Revoke the current token | Yes |

## Public Stores and Products

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/stores` | List active stores; optionally filter by type |
| `GET` | `/api/stores/{store}` | Show an active store |
| `GET` | `/api/stores/{store}/products` | List active products for an active store |
| `GET` | `/api/products` | List active products belonging to active stores |
| `GET` | `/api/products/{product}` | Show an active product belonging to an active store |

## Orders

| Method | Endpoint | Purpose | Authentication |
|---|---|---|---|
| `GET` | `/api/orders` | List orders visible to the current user | Yes |
| `POST` | `/api/orders` | Create an order for the current customer | Yes |
| `GET` | `/api/orders/{order}` | Show a permitted order | Yes |
| `PATCH` | `/api/orders/{order}/status` | Change status if authorized and transition is valid | Yes |
| `PATCH` | `/api/orders/{order}/assign-driver` | Assign an available driver | Yes |

Order query filters may include status, store, driver, today, age in minutes, and page size. The controller must validate filters and enforce authorization before returning results. Customer and driver results must be scoped to records the user is permitted to see.

## Administration: Stores

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/admin/stores` | List all stores, including inactive stores |
| `POST` | `/api/admin/stores` | Create a store |
| `GET` | `/api/admin/stores/{storeId}` | Show a store, including inactive stores |
| `PATCH` | `/api/admin/stores/{storeId}` | Update store details |
| `PATCH` | `/api/admin/stores/{storeId}/activate` | Activate a store |
| `PATCH` | `/api/admin/stores/{storeId}/deactivate` | Deactivate a store |

## Administration: Products

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/admin/stores/{storeId}/products` | List the store's products, including inactive ones |
| `POST` | `/api/admin/stores/{storeId}/products` | Add a product to a store |
| `GET` | `/api/admin/products/{productId}` | Show product details |
| `PATCH` | `/api/admin/products/{productId}` | Update product details |
| `PATCH` | `/api/admin/products/{productId}/activate` | Activate a product |
| `PATCH` | `/api/admin/products/{productId}/deactivate` | Deactivate a product |

## Administration: Drivers and Reports

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/admin/drivers` | List drivers; optionally filter by availability |
| `GET` | `/api/admin/drivers/{driverId}` | Show driver details |
| `PATCH` | `/api/admin/drivers/{driverId}` | Update vehicle details |
| `GET` | `/api/admin/reports/orders` | Get shared order summary and status breakdown |

The report endpoint may accept `from`, `to`, and `store_id`. Report authorization uses `reports.view`. Confirm route definitions in `routes/api.php`; this endpoint table defines the intended API contract.

---

# Standard API Response Format

Successful responses use the project's custom `ApiResponse` helper.

```json
{
    "success": true,
    "message": "تم جلب البيانات بنجاح",
    "data": {}
}
```

Error responses follow the same general shape:

```json
{
    "success": false,
    "message": "البيانات المرسلة غير صحيحة",
    "data": null,
    "errors": {}
}
```

Paginated list responses include `pagination` metadata:

```json
{
    "success": true,
    "message": "تم جلب الطلبات بنجاح",
    "data": [],
    "pagination": {
        "current_page": 1,
        "per_page": 50,
        "total": 0,
        "last_page": 1,
        "from": null,
        "to": null
    }
}
```

Responses should communicate whether a result set is empty. For example, a filtered request with no matching records should return HTTP `200`, an empty array, and a meaningful message rather than pretending the route or resource does not exist.

Expected HTTP status conventions:

- `200`: successful read/update or an empty list.
- `201`: successful resource creation.
- `401`: unauthenticated.
- `403`: authenticated but not authorized.
- `404`: route or requested resource not found/available to that caller.
- `409`: invalid business-state transition or conflicting operation.
- `422`: request validation failure.
- `500`: unexpected server error; production responses must not leak stack traces or secrets.

API exceptions should be normalized centrally in `bootstrap/app.php`. Do not create duplicate exception handlers for the same exception type.

---

# Reporting and Performance

## Shared statistics

`OrderStatisticsService` is the common place for reusable order-statistics logic. Administrator reports and operational screens should use the same query rules and compatible filters, rather than independently redefining active orders.

A report must clearly distinguish between:

- Orders created within a time period.
- Orders created in that period that are still active now.
- All currently active orders, regardless of creation date.
- Delivered order value (not the platform's net profit unless commissions and fees are explicitly accounted for).

## Query performance

Performance work should be based on actual SQL and data:

1. Use eager loading (`with`) to avoid N+1 queries.
2. Select only columns needed by list responses.
3. Use `withCount` when counts are needed without loading every related row.
4. Bound page size (for example, between 1 and 100).
5. Use the shared scopes for recurring filters.
6. Inspect existing indexes with `SHOW INDEX FROM orders`.
7. Inspect query plans with `EXPLAIN` and, when supported, `EXPLAIN ANALYZE` in a safe environment.
8. Add indexes only after verifying that a real, common query benefits from them.

Potential order indexes depend on the measured query plan. Examples to investigate—not blindly add—include indexes supporting customer-, driver-, or store-filtered chronological listings.

---

# Seeders and Demo Data

## Roles and permissions

The role/permission seeder should be idempotent, so rerunning it does not create duplicate roles or permissions:

```bash
php artisan db:seed --class=RolePermissionSeeder
```

## Required demo dataset

A dedicated demo-data seeder is a project requirement. It should generate realistic, internally consistent test data including:

- Customer accounts and driver accounts.
- Staff/admin demo accounts only for explicitly local/testing use.
- Multiple restaurants and city shops with different types.
- Products with varied prices and active/inactive states.
- Drivers with varied availability.
- At least **1,000 orders**, distributed across all valid statuses.
- Corresponding order items with valid foreign keys and price snapshots.
- A mixture of recent and older `created_at` values to exercise date filters and report queries.

The data generator should use factories and/or batched inserts where appropriate, avoid generating duplicate emails/vehicle numbers, and keep the relationships valid. It should be repeatable or clearly documented as a resettable demo operation.

> **Safety:** Never run a destructive demo seeder against production. Seed test/demo data only in a local or dedicated testing database. Do not commit real passwords, tokens, customer data, or personal data to the repository.

If `DemoDataSeeder` is implemented in the source tree, run it only after migrations and role/permission seeding, and only against a dedicated local/demo database:

```bash
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=DemoDataSeeder
```

The demo seeder must create at least 1,000 orders with valid `order_items`, existing customers, stores and products, correct price snapshots, and consistent driver/status relationships. Verify the resulting counts with SQL rather than relying only on the seeder's success message. Do not run this seeder in production.


---

# Testing

The project should provide feature tests for the core business and authorization rules, including:

- Guest access to protected order endpoints returns `401`.
- A customer sees only their own orders.
- A driver sees only assigned orders and cannot update another driver's order.
- Users without permissions receive `403` on administrative endpoints.
- An administrator can view an inactive store and reactivate it.
- Public listings hide inactive stores and their products.
- Order creation uses the authenticated customer and server-side prices.
- Products must be active and belong to the selected store.
- Invalid status transitions are rejected.
- Driver assignment rejects unavailable drivers and drivers with another active order.
- Driver availability is restored after delivery/cancellation only when no active order remains.
- The report totals match the selected date and store filters.
- A demo dataset of at least 1,000 orders can be generated without broken relationships.

Run tests with:

```bash
php artisan test
```

Use a dedicated test database. Confirm `.env.testing` and `phpunit.xml` point to that database before running tests that use `RefreshDatabase`.

---

# Installation

## 1. Get the source code

```bash
git clone <repository-url>
cd Delivery
```

## 2. Install dependencies

```bash
composer install
```

## 3. Create environment configuration

Linux/macOS:

```bash
cp .env.example .env
```

Windows Command Prompt:

```bat
copy .env.example .env
```

## 4. Generate the application key

```bash
php artisan key:generate
```

## 5. Configure MySQL

Set the database values in `.env` for the development database:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=delivery
DB_USERNAME=your_mysql_username
DB_PASSWORD=your_mysql_password

Delivery_DELIVERY_FEE=4.00
```

Adjust the delivery fee to the project's configured business rule. Do not commit local secrets to source control.

## 6. Configure API authentication

If Sanctum/API setup has not already been installed for the project, follow the command appropriate for the installed Laravel version, then review the generated files before continuing:

```bash
php artisan install:api
```

Do not rerun setup commands blindly if the API scaffolding and `personal_access_tokens` migration already exist.

## 7. Run database migrations

```bash
php artisan migrate
```

Do not use `php artisan migrate:fresh` on any database containing data that must be preserved.

## 8. Seed roles and permissions

```bash
php artisan db:seed --class=RolePermissionSeeder
```

The demo-data seeder that creates at least 1,000 orders must be implemented and documented before relying on its command. Run it only against a dedicated development/demo database.

## 9. Start the API server

```bash
php artisan serve
```

The local base URL is typically:

```text
http://127.0.0.1:8000
```

The API routes are under `/api`.

---

# Project Structure

Target directory layout:

```text
app/
├── Contracts/                 # Service interfaces where used
│   ├── OrderServiceInterface.php
│   ├── OrderQueryServiceInterface.php
│   ├── OrderStatusServiceInterface.php
│   ├── DriverAssignmentServiceInterface.php
│   ├── StoreQueryServiceInterface.php
│   ├── ProductQueryServiceInterface.php
│   ├── StoreManagementServiceInterface.php
│   └── ProductManagementServiceInterface.php
├── Enums/
│   └── OrderStatus.php
├── Exceptions/                # Explicit business-rule exceptions
├── Http/
│   ├── Controllers/Api/        # Thin HTTP controllers
│   ├── Requests/               # Form Request validation
│   ├── Resources/              # API representation of entities
│   └── Responses/
│       └── ApiResponse.php     # Consistent JSON responses
├── Models/                     # Eloquent models and local scopes
├── Policies/                   # Authorization policies
├── Scopes/                     # Global scopes, e.g. active stores
├── Services/                   # Business operations and transactions
└── Providers/
    └── AppServiceProvider.php  # Container bindings and Gates

database/
├── factories/
├── migrations/
└── seeders/

tests/
└── Feature/Api/

routes/
└── api.php
```

The actual project tree may differ while the training implementation is in progress. Keep controllers focused on HTTP concerns and keep reusable business/query rules in their designated layer.

### Controller refactoring rule

A controller should generally authorize the action, receive validated input, call the appropriate service, and return `ApiResponse`/a resource. It should not contain reusable business query predicates, pricing calculations, transaction orchestration, driver assignment rules, or order-status transition logic. When refactoring an existing controller, preserve route names, HTTP status codes, JSON response shape, authorization checks, and not-found behavior.

---

# Design Principles

The Delivery API follows these principles:

- **API-only:** no frontend or Blade pages are required.
- **Thin controllers:** controllers authorize, validate, call services, and return responses.
- **Single source of truth for repeated query rules:** reusable business conditions are expressed through Local Scopes or a named query abstraction.
- **Service layer:** transactional business logic stays out of controllers.
- **Dependency injection:** services are supplied through Laravel's service container.
- **Centralized authorization:** Policies, Gates, and permissions control access.
- **Server-side pricing:** clients cannot dictate order totals or historical item prices.
- **Concurrency safety:** critical order/driver operations use transactions and appropriate row locks.
- **Consistent JSON:** success, validation, authorization, and not-found responses follow a predictable format.
- **Evidence-based performance tuning:** measure SQL plans before adding indexes.
- **Realistic demo data:** at least 1,000 orders are required to demonstrate and investigate performance behavior.
- **Automated regression tests:** core business rules and access boundaries should be tested after changes.

---

# License

This project is developed as a Laravel API training project for the Delivery delivery platform. Add a specific open-source license only if the project owner intends to publish and license the repository.
