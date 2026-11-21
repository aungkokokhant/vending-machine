# Vending Machine

Laravel vending machine code test with a Blade website and a versioned REST API. Application data access uses explicit PDO prepared statements, injected repository contracts, and a shared atomic purchase service. No npm install or frontend build is required.

## Live preview

- Website: [https://vending.aungkokokhant.online](https://vending.aungkokokhant.online)
- Sign in: [https://vending.aungkokokhant.online/login](https://vending.aungkokokhant.online/login)
- Source code: [aungkokokhant/vending-machine](https://github.com/aungkokokhant/vending-machine)
- API base URL: `https://vending.aungkokokhant.online/api/v1`

The evaluation instance is deployed over HTTPS. Use the [demo accounts](#demo-accounts) below to sign in as an Admin or User.

## Current verification

- 57 automated tests pass with 309 assertions using in-memory SQLite; one opt-in MySQL concurrency test is skipped by default.
- PHP attribute purchase routing has been checked with the route cache enabled.
- Blade templates compile; PHP formatting and Composer/platform checks are part of the handoff checks.
- The existing local app at `http://localhost:8000/products` has been verified in Chrome. Admin/User login, logout, Admin product creation/editing/deletion, client price validation, price sorting, User role restrictions, purchases, out-of-stock behavior and private purchase history all passed live browser checks.
- The opt-in MySQL concurrency test passed in the local terminal on November 21, 2026: 1 test, 8 assertions, 0.61 seconds. Two PHP processes attempted to purchase the final unit; exactly one succeeded, stock reached zero, and exactly one transaction was stored with the correct price.
- The deployed HTTPS instance was checked on November 21, 2026: the catalog displays the three required products and prices, price sorting works, the purchase form calculates three Pepsi units as exactly `20.655` USD, and the purchase history page renders. A User receives `403` on an Admin management page, and the API rejects requests without a Bearer token. The `/up` health page reports that the application is running.
- Admin CRUD, purchase completion and authenticated API flows are covered by the automated tests; those write operations were not repeated against the deployed instance during the final review.

## Requirements

Use PHP 8.5 (the local implementation uses 8.5.8), Composer 2, and MySQL 8 with InnoDB tables. The locked dependencies require PHP 8.4.1 or newer. Enable PDO, pdo_mysql, pdo_sqlite for tests, mbstring, openssl, tokenizer, XML/DOM, fileinfo and the standard Laravel extensions. Install the locked dependencies with `composer install`; do not use `composer update` to set up a copy.

## Local setup

Clone the repository and enter the application directory:

```sh
git clone https://github.com/aungkokokhant/vending-machine.git
cd vending-machine
composer install
cp .env.example .env
php artisan key:generate --no-interaction
```

Skip copying `.env` or regenerating the application key when continuing the existing local installation: its database credentials are already configured. The application expects the existing database named `vending-machine`. For a new installation, create that database in MySQL and configure:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vending-machine
DB_USERNAME=root
DB_PASSWORD=your-local-password
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

Run in your own terminal:

```sh
php artisan config:clear --no-interaction
php artisan migrate --seed --no-interaction
php artisan serve --host=127.0.0.1 --port=8000 --no-interaction
```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000). Sign in to browse products. Admins can create, inspect, edit and delete products. Both roles can purchase and view their own purchase history.

### Demo accounts

| Role | Email | Password |
| --- | --- | --- |
| Admin | admin@vending.test | AdminDemo123! |
| User | user@vending.test | UserDemo123! |

These are test accounts created by `DemoUserSeeder`. Use them only for an evaluation instance; change passwords before broader use. There is no public registration endpoint and no way for a public request to select an Admin role.

### Seed data and assumptions

| Product | Unit price (USD) | Initial quantity |
| --- | --- | --- |
| Coke | 3.990 | 20 |
| Pepsi | 6.885 | 20 |
| Water | 0.500 | 20 |

The prices are specified in the PDF. Initial stock, demo accounts, nine products per catalog page, ten purchases per history page, and one-hour API token expiry are implementation assumptions. Both roles may buy products. Seeders insert missing records but preserve existing prices, inventory, passwords and soft-deleted records.

## Database design

Migrations are in `database/migrations/`.

| Table | Fields and relationships |
| --- | --- |
| users | bigint ID; name; unique email; password hash; indexed role (`admin` or `user`, default `user`); timestamps; nullable deleted_at. One user has many transactions and API tokens. |
| products | bigint ID; indexed name; unique readable slug; price DECIMAL(10,3); unsigned integer quantity_available; timestamps; nullable deleted_at. One product has many transactions. Price is indexed for sorting. |
| transactions | bigint ID; user_id and product_id foreign keys; unsigned integer quantity; unit_price DECIMAL(10,3); total_price DECIMAL(20,3); timestamps. Composite indexes support user/product purchase history. Permanent deletion of referenced products/users is restricted. |
| api_tokens | bigint ID; user_id foreign key; unique SHA-256 token_hash; indexed expires_at; created_at. Permanent user deletion cascades to tokens. |

The scaffold also retains standard Laravel infrastructure tables. File sessions/cache and synchronous queues do not require those infrastructure tables to run.

Prices accept up to seven integer digits and three decimal places; price must be positive. Stock is a whole number between 0 and 4,294,967,295. Purchase quantity must be a positive whole number within that range and within current stock. Repository pagination caps page numbers at 1,000,000 to keep offsets bounded.

`PdoConnection` takes Laravel's configured connection and explicitly obtains its PDO instance. All repositories prepare SQL and bind values, including integer LIMIT/OFFSET parameters. Table/column identifiers come only from application-defined allowlists. Eloquent is retained for models, factories, seeders and schema relationship tests; it is not the production CRUD data-access implementation.

### Purchase consistency

`PurchaseService` opens a transaction on the same connection used by the repositories. On MySQL it selects the active product `FOR UPDATE`, checks inventory, applies a guarded decrement, and inserts a transaction. Failure rolls back both changes. Laravel's transaction wrapper permits nested test transactions and retries deadlocks up to three times. It does not reserve stock before purchase confirmation.

The server obtains the price from the database, disregarding submitted prices and user IDs. Brick Math decimal arithmetic preserves Pepsi's `6.885` price: three units cost exactly `20.655`. Transaction snapshots retain purchase-time unit and total prices after later catalog changes. Products and users are soft-deleted to preserve references. Transaction CRUD exists in the internal repository as required; HTTP clients cannot edit or delete purchase records.

SQLite tests cover validation, snapshots, guarded stock changes and rollback, but do not prove MySQL row-locking behavior. Use the opt-in check below before submission.

## Website authentication and routing

`AuthController` verifies password hashes, regenerates the session after login, and stores user ID, name and role in the session. `RequireSessionUser` reloads the active account on each protected request and refreshes its session role. `RequireAdmin` checks that stored role before permitting management pages/actions. Logout invalidates the session and regenerates the CSRF token. Laravel's web middleware protects forms with CSRF tokens.

`ProductsController::purchase` declares the actual PHP `#[PurchaseRoute]` attribute. `AttributeRouteRegistrar` discovers that declaration inside the protected web route group. Purchase URLs use product slugs, e.g. `/products/pepsi/purchase`. New products get a readable name prefix plus a unique ULID suffix; URLs stay stable when a product is renamed. Admin routes use numeric IDs.

```sh
php artisan route:list --no-interaction
php artisan route:cache --no-interaction
php artisan route:list --name=products.purchase --no-interaction
php artisan route:clear --no-interaction
```

## REST API

Base path: `/api/v1`. Send `Accept: application/json`. Authenticated requests require `Authorization: Bearer TOKEN`; website sessions do not authenticate the API. API tokens are random 256-bit opaque values, issued once, stored only as SHA-256 hashes, expire after one hour, and can be revoked at logout. The PDF permits token authentication with JWT as an example; this application uses opaque tokens without adding a package. Roles and soft deletion are rechecked on every authenticated API request.

| Method | Endpoint | Access | Result |
| --- | --- | --- | --- |
| POST | /login | Public, rate limited | Token and safe user fields |
| POST | /logout | Token | Revoke current token |
| GET | /products | Token | Paginated catalog |
| GET | /products/{id} | Token | One active product |
| POST | /products | Admin token | Create product, 201 |
| PUT/PATCH | /products/{id} | Admin token | Update product; full editable payload required |
| DELETE | /products/{id} | Admin token | Soft-delete product, 204 |
| POST | /products/{slug}/purchase | Token | Purchase and log transaction, 201 |
| GET | /transactions | Token | Current user's paginated purchase history |

Catalog query parameters: `sort=name|price|quantity_available|id`, `direction=asc|desc`, and `page`. Unsupported sort values fall back to name/ascending. Login allows five requests per email/IP per minute; the API also allows 120 requests per IP per minute.

### Examples

```sh
curl -sS http://127.0.0.1:8000/api/v1/login \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"user@vending.test","password":"UserDemo123!"}'
```

Copy the returned `token` into `TOKEN` in subsequent commands:

```sh
curl -sS 'http://127.0.0.1:8000/api/v1/products?sort=price&direction=asc' \
  -H 'Accept: application/json' -H 'Authorization: Bearer TOKEN'

curl -sS http://127.0.0.1:8000/api/v1/products/pepsi/purchase \
  -H 'Accept: application/json' -H 'Authorization: Bearer TOKEN' \
  -H 'Content-Type: application/json' -d '{"quantity":3}'

curl -sS http://127.0.0.1:8000/api/v1/transactions \
  -H 'Accept: application/json' -H 'Authorization: Bearer TOKEN'
```

A successful purchase returns a `data` object with `id`, `user_id`, `product_id`, `quantity`, `unit_price`, `total_price`, `created_at`, and `updated_at`. Decimal amounts are JSON strings, e.g. `"unit_price":"6.885","total_price":"20.655"`.

Product create/update payload (use an Admin token):

```json
{"name":"Tea","price":"1.125","quantity_available":12}
```

Errors use HTTP 401 for missing/invalid/expired tokens, 403 for insufficient roles, 404 for missing/deleted products, 422 for invalid input or insufficient stock, and 429 for rate limiting. Validation bodies contain `message` and field-keyed `errors`:

```json
{
  "message": "There is not enough stock for this purchase.",
  "errors": {"quantity": ["There is not enough stock for this purchase."]}
}
```

## Tests

```sh
php artisan test --compact
php artisan test --compact --testsuite=Unit
php artisan test --compact --filter=PurchaseTest
```

Default tests use in-memory SQLite and never migrate the application's MySQL database. The PDO repository tests use `RefreshDatabase`: direct PDO statements bypass the query hooks that trigger Laravel's lazy migrations. Controller isolation tests boot the framework for views/redirects but inject mocked repositories and purchase services and do not access a database.

### Opt-in MySQL concurrency check

Create a separate empty MySQL database named `vending_machine_test` using the same local connection credentials. Do not use the development or production database. Then run:

```sh
VENDING_MYSQL_TEST_DATABASE=vending_machine_test \
  php artisan test --compact --filter=MySqlPurchaseConcurrencyTest
```

The check requires a name ending in `_test`, applies migrations to that separate database, starts two PHP processes at a shared barrier, and asks both to buy the final unit. Exactly one purchase must succeed, one must fail, remaining stock must be zero, and the stored price must remain `6.885`. It removes the records it creates. This check passed in the local terminal on November 21, 2026 (1 test, 8 assertions); rerun it against a separate test database when validating another environment.

## PDF task mapping

| Task | Implementation |
| --- | --- |
| 1. Database setup and relationships | This README; database/migrations; app/Models |
| 2. PDO connection and CRUD for three entities | app/Database/PdoConnection.php; app/Repositories; app/Contracts |
| 3. Sessions, hashing and roles | AuthController; PdoUserRepository; DemoUserSeeder |
| 4. Session role access checks | RequireSessionUser; RequireAdmin; routes/web.php |
| 5. ProductsController CRUD | app/Http/Controllers/ProductsController.php |
| 6. Purchase updates and transaction logging | ProductsController::purchase; PurchaseService |
| 7. Admin CRUD views | resources/views/products/{form,show,delete}.blade.php |
| 8. Product list, pagination and sorting | products/index.blade.php; PdoProductRepository::paginate |
| 9. Purchase view and input | products/purchase.blade.php |
| 10. Routing | routes/web.php; routes/api.php |
| 11. Purchase attributes and readable URLs | app/Routing; ProductsController::purchase |
| 12. Required/positive/non-negative validation | ProductRequest; PurchaseRequest |
| 13. Client/server validation and errors | Form Requests; Blade forms and errors; public/js/app.js |
| 14. Controller tests and edge cases | tests/Unit/ProductsControllerTest.php; tests/Feature |
| 15. DI and mocking | AppServiceProvider; Contracts; ProductsControllerTest |
| 16. REST API | routes/api.php; app/Http/Controllers/Api |
| 17. Token authentication and authorization | TokenService; RequireApiToken; RequireAdmin; api_tokens migration |

## Deployment and submission

The application is deployed at [https://vending.aungkokokhant.online](https://vending.aungkokokhant.online) on Ubuntu 24.04 with Nginx, PHP 8.5-FPM and MySQL 8.4 running in Docker. Database migrations and demo seeders completed successfully. The public source repository is [https://github.com/aungkokokhant/vending-machine](https://github.com/aungkokokhant/vending-machine).

For a separate deployment, use the following steps. Ensure `php` resolves to a compatible version; on the current server, use `php8.5` explicitly because the default CLI remains PHP 8.3.

1. Copy the source, including `composer.lock`, to the server and run `composer install --no-dev --optimize-autoloader`.
2. Configure a server-local `.env`, a fresh application key, MySQL credentials, `APP_ENV=production`, `APP_DEBUG=false`, the HTTPS `APP_URL`, and `SESSION_SECURE_COOKIE=true`.
3. Configure Nginx/Apache with document root set to this application's `public/` directory and PHP-FPM. Give the service account write access to `storage/` and `bootstrap/cache/`.
4. Run `php artisan migrate --seed --force --no-interaction` for the evaluation data, then `php artisan config:cache --no-interaction`, `php artisan route:cache --no-interaction`, and `php artisan view:cache --no-interaction`.
5. Verify Admin/User website flows and token API requests over HTTPS. Run the MySQL concurrency check against the separate test database.
6. Provide the preview URL and source repository/archive to the evaluator, together with the demo login details. Exclude `.env`, `vendor/`, logs, sessions, caches and local database files from source archives; retain `.env.example`, migrations, tests and the dependency lockfile. Sending the submission is a separate step from deployment.

The application records simulated purchases. Hardware dispensing and payment-gateway integration are outside the PDF's scope.
