ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin TINYINT(1) NOT NULL DEFAULT 0;

-- Replace your_username_here with the registered account that should control the system.
UPDATE users SET is_admin = 1 WHERE username = 'your_username_here';
