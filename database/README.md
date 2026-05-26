# EVSU Reserve — Database Setup

## Automatic setup (recommended)

When you copy this project to another PC:

1. Start **Apache** and **MySQL** in XAMPP.
2. Open any page that uses `database.php` (e.g. `login_page.php`).
3. The app will **automatically**:
   - Create the database `evsu_reserve` (if missing)
   - Create all tables
   - Insert demo users, products, orders, and payments (only on a fresh/empty database)

No manual SQL import is required for normal use.

### Credentials

Edit `database/config.php` if your MySQL user/password differs from XAMPP defaults (`root` with no password).

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
