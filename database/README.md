# EVSU Reserve — Database Setup

## Automatic setup (recommended)

When you copy this project to another PC:

1. Start **Apache** and **MySQL** in XAMPP.
2. Open **any** page (homepage, login, or register). The app runs `database/bootstrap.php` automatically.
3. On first run it will:
   - Create the database `evsu_reserve` if it does not exist
   - Create all required tables (`CREATE TABLE IF NOT EXISTS`)
    - Leave tables EMPTY (no demo rows)

Optional: enable demo seeding by setting environment variable `EVSU_SEED_DEMO=1`.

No manual SQL import is required for normal use.

### Verify install (optional)

```bash
php c:\xampp\htdocs\EvsuReserve\database\test_bootstrap.php
```

This drops and recreates the database once to confirm auto-install works.

### Credentials

Edit `database/config.php`, or copy `database/config.local.php.example` to `database/config.local.php` and edit there.

Optional environment overrides: `EVSU_DB_HOST`, `EVSU_DB_USER`, `EVSU_DB_PASS`, `EVSU_DB_NAME`.

## Manual import (optional)

You can still import `evsu_reserve_cashier.sql` via phpMyAdmin or:

```bash
c:\xampp\mysql\bin\mysql.exe -u root < c:\xampp\htdocs\EvsuReserve\database\evsu_reserve_cashier.sql
```

## Tables

| Table | Purpose |
|-------|---------|
| `users` | Login (student, cashier, staff, admin) |
| `products` | Catalog |
| `cart_items` | Student shopping cart |
| `orders` | Student orders |
| `order_items` | Line items per order |
| `payments` | Cashier payment verification |
| `system_settings` | Admin configuration |
| `activity_logs` | Admin activity log |

## Demo logins

Password for all accounts below: **`password`**

| Role | Login (email or student ID) |
|------|-----------------------------|
| Student | `2020-00001` or `juan.delacruz@evsu.edu.ph` |
| Cashier | `CASH-00001` or `maria.santos@evsu.edu.ph` |
| Staff | `STAFF-0001` or `carmen.lopez@evsu.edu.ph` |
| Admin | `ADMIN-0001` or `admin@evsu.edu.ph` |
