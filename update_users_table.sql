-- SQL to add admin2 role to existing users table
-- This will modify the role enum to include admin2 option

-- Update the role enum to include admin2
ALTER TABLE `users` 
MODIFY COLUMN `role` ENUM('admin','admin2','vendor_user') NOT NULL DEFAULT 'vendor_user';

-- Optional: Create some admin2 users (uncomment if needed)
-- INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`) VALUES
-- ('Admin2 User', 'admin2@microfinance.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin2', 'active'),
-- ('Limited Admin', 'limited@admin.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin2', 'active');

-- Verify the change
SHOW COLUMNS FROM `users` LIKE 'role';
