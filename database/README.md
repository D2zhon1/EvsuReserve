# EVSU Reserve — Database Setup

## Manual setup (required)

1. Start **Apache** and **MySQL** in XAMPP.
2. Import the SQL file via phpMyAdmin, or from a terminal:

```bash
c:\xampp\mysql\bin\mysql.exe -u root < c:\xampp\htdocs\EvsuReserve\database\evsu_reserve_cashier.sql
```

3. Edit `database/config.php`, or copy `database/config.local.php.example` to `database/config.local.php` and set your credentials.

Optional environment overrides: `EVSU_DB_HOST`, `EVSU_DB_USER`, `EVSU_DB_PASS`, `EVSU_DB_NAME`.

The app does **not** create the database or tables automatically. If MySQL is down or the database is missing, pages that need the database will show a connection error.

### Order completion emails

When staff sets an order to **Completed**, the student receives an email at their registered `@evsu.edu.ph` address.

Copy `.env.example` to `.env` and configure mail (quote values that contain spaces, e.g. `MAIL_FROM_NAME="EVSU Reserve"`):

- **`MAIL_DRIVER=log`** — writes to `storage/logs/mail.log` (local testing).
- **`MAIL_DRIVER=smtp`** — sends via SMTP (Gmail, etc.).

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
| `activity_logs` | Activity log |

## Demo logins

Password for all accounts below: **`password`**

| Role | Login (email or student ID) |
|------|-----------------------------|
| Student | `2020-00001` or `juan.delacruz@evsu.edu.ph` |
| Cashier | `CASH-00001` or `maria.santos@evsu.edu.ph` |
| Staff | `STAFF-0001` or `carmen.lopez@evsu.edu.ph` |
| Admin | `ADMIN-0001` or `admin@evsu.edu.ph` |
