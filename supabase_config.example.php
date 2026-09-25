<?php
// Copy this file to supabase_config.php, then fill in your Supabase database details.
// Do not upload supabase_config.php to a public GitHub repository.

return [
    'driver' => 'supabase',
    // Use the Session pooler for local PHP/XAMPP because this app uses prepared statements.
    'host' => 'aws-0-ap-southeast-1.pooler.supabase.com',
    'port' => 5432,
    'database' => 'postgres',
    'user' => 'postgres.your-project-ref',
    'password' => 'your-supabase-database-password',
];
