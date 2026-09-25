# JolliBeep Ordering System

Deployable copy of the JolliBeep PHP ordering system.

## Supabase Setup

1. Create a Supabase project.
2. Open **SQL Editor** in Supabase.
3. Run `supabase_schema.sql`.
4. Copy `supabase_config.example.php` to `supabase_config.php`.
5. Fill in your Supabase database connection details.
6. Enable these extensions in `C:\xampp\php\php.ini`:

```ini
extension=pdo_pgsql
extension=pgsql
```

7. Restart Apache.

Keep `supabase_config.php` private. It is ignored by Git.

## Main Pages

- `index.php` - home
- `login.php` - login/register popup flow
- `page5_jollibee_orderform.php` - menu and ordering
- `page7_stores.php` - stores
- `page11_manager_orders.php` - private controller/order manager
- `page12_admin_login.php` - controller login

## Migration Notes

Read `SUPABASE_MIGRATION.md` before switching from local MySQL to Supabase.
