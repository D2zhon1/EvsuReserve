# EVSU Reserve — Database Setup

## Cashier schema

Import `evsu_reserve_cashier.sql` into MySQL (database name: **evsu_reserve**, same as `login_page.php`).

### Option A — phpMyAdmin

1. Start **Apache** and **MySQL** in XAMPP Control Panel.
2. Open http://localhost/phpmyadmin
3. **Import** → choose `evsu_reserve_cashier.sql` → Go

### Option B — Command line

```bash
c:\xampp\mysql\bin\mysql.exe -u root < c:\xampp\htdocs\EvsuReserve\database\evsu_reserve_cashier.sql
```

## Tables created

| Table | Purpose |
|-------|---------|
| `users` | Login (student, cashier, admin) |
| `products` | Catalog |
| `cart_items` | Student shopping cart |
| `orders` | Student orders |
| `order_items` | Line items per order |
| `payments` | Cashier payment verification |

## Demo logins

| Role | Student ID | Password |
|------|------------|----------|
| Cashier | `CASH-00001` | `password` |
| Student | `2020-00001` | `password` |
| Admin | `ADMIN-0001` | `password` |

## Cashier queries (for wiring PHP later)

**List payments for dashboard:**

```sql
SELECT p.payment_code AS id, o.order_number, u.name AS payer_name,
       p.method, p.amount, p.status, p.created_at AS created_date
FROM payments p
JOIN orders o ON o.id = p.order_id
JOIN users u ON u.id = o.user_id
ORDER BY p.created_at DESC;
```

**Verify payment:**

```sql
UPDATE payments
SET status = 'verified', verified_by = ?, verification_date = NOW()
WHERE payment_code = ?;

UPDATE orders
SET payment_status = 'verified', status = 'paid'
WHERE id = ?;
```
