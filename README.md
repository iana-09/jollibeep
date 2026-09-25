# JolliBeep

JolliBeep is a PHP-based Jollibee ordering system built for a technical school project. Customers can browse menu items, choose a store, place orders, and manage their profiles. An admin page supports reviewing and managing orders.

## Built with

- PHP
- MySQL for local XAMPP setup, with Supabase PostgreSQL support
- HTML, CSS, and JavaScript

## Run locally

Place the project in XAMPP’s `htdocs` folder, start Apache and MySQL, and open the project through `http://localhost/S2_technical2/`.

## Deploy on Vercel

The project uses the community PHP runtime for Vercel. Import this repository with its root directory set to the repository root, then add these environment variables in Vercel Project Settings:

- `JOLLIBEEP_DB_DRIVER` = `supabase`
- `SUPABASE_DB_HOST`
- `SUPABASE_DB_PORT` = `5432`
- `SUPABASE_DB_NAME` = `postgres`
- `SUPABASE_DB_USER`
- `SUPABASE_DB_PASSWORD`

Use the Supabase Session Pooler values. Keep the database password in Vercel’s environment settings, never in this public repository. The `app_sessions` table is included in `supabase_schema.sql` for persistent logins.

For local database migration details, see [SUPABASE_MIGRATION.md](SUPABASE_MIGRATION.md).
