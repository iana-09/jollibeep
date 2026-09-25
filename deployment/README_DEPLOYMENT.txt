JolliBeep Deployment Folder
==========================

This folder is a clean deployable copy of the JolliBeep ordering system.

Suggested local deployment:
1. Copy the jollibeep folder to your target XAMPP htdocs directory.
2. Start Apache and MySQL in XAMPP.
3. Create/import the needed database tables using the SQL files included in this folder.
4. Check db_connection.php and update database name, username, password, and host if needed.
5. Open http://localhost/jollibeep/index.php

Supabase setup:
1. Run supabase_schema.sql in Supabase SQL Editor.
2. Enable extension=pdo_pgsql and extension=pgsql in C:\xampp\php\php.ini, then restart Apache.
3. Copy supabase_config.example.php to supabase_config.php.
4. Fill in your Supabase Transaction Pooler database details.
5. Keep supabase_config.php private. It is ignored by Git.

Important entry pages:
- index.php: home page
- login.php: smooth login/signup popup
- page5_jollibee_orderform.php: ordering/menu page
- page7_stores.php: store finder
- page11_manager_orders.php: private controller/order manager side
- page12_admin_login.php: controller login

Included assets/data:
- images/
- addresses/
- menu_data.php
- stores_data.php
- orders_data.json
- supabase_schema.sql
- SUPABASE_MIGRATION.md

Before final deployment, use fresh database credentials and do not expose controller credentials publicly.
