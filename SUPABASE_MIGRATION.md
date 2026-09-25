# JolliBeep Supabase Migration

Supabase uses PostgreSQL, while the original XAMPP project used MySQL plus `orders_data.json`.

## 1. Create Your Supabase Project

1. Go to Supabase and create a new project.
2. Open **Project Settings > Database**.
3. Copy the **Session pooler** connection details for local XAMPP testing.
4. Keep your database password private.

## 2. Create Tables

Open **Supabase Dashboard > SQL Editor**, paste the contents of:

```text
supabase_schema.sql
```

Run it once. This creates:

- `users`
- `orders`

## 3. Enable PostgreSQL in XAMPP PHP

Your XAMPP already has these files:

```text
C:\xampp\php\ext\php_pdo_pgsql.dll
C:\xampp\php\ext\php_pgsql.dll
```

Open:

```text
C:\xampp\php\php.ini
```

Find these lines and remove the semicolon at the start:

```ini
extension=pdo_pgsql
extension=pgsql
```

Then restart Apache in XAMPP.

## 4. Add Supabase Config

Copy:

```text
supabase_config.example.php
```

Rename the copy to:

```text
supabase_config.php
```

Fill it with your Supabase database details:

```php
return [
    'driver' => 'supabase',
    'host' => 'your-supabase-pooler-host',
    'port' => 5432,
    'database' => 'postgres',
    'user' => 'postgres.your-project-ref',
    'password' => 'your-database-password',
];
```

Do not upload `supabase_config.php` to GitHub.

## 5. Move Existing Users

Start MySQL in XAMPP first. Then export your users:

```powershell
cd C:\xampp\htdocs\S2_technical2
C:\xampp\mysql\bin\mysqldump.exe -h 127.0.0.1 -P 3307 -u root --no-create-info --complete-insert jollibee users > users_mysql_export.sql
```

Because MySQL SQL is not always valid PostgreSQL SQL, the cleanest path is:

1. Export users from phpMyAdmin as CSV.
2. In Supabase, open **Table Editor > users**.
3. Click **Insert > Import data from CSV**.
4. Match columns by name.

Keep the `password_hash` values unchanged.

## 6. Move Existing Orders

Existing orders are in:

```text
orders_data.json
```

After Supabase is enabled, new orders will save to the Supabase `orders` table automatically.

For old JSON orders, you can manually insert them later or keep the file as archive data.

## 7. Test

After setting `supabase_config.php` and restarting Apache, your PHP files still run locally from XAMPP, but the database is Supabase:

1. Register a new account.
2. Log in.
3. Place an order.
4. Open the controller side.
5. Confirm the order appears in Supabase `orders`.

## Important

If you see this error:

```text
pdo_pgsql is not enabled
```

That means Apache is still using PHP without the PostgreSQL extension enabled. Recheck `php.ini`, save it, and restart Apache.
