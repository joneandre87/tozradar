-- Run this in phpMyAdmin for tozradar_db

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) UNIQUE NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('user', 'admin', 'superadmin') DEFAULT 'user',
  `subscription_id` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `subscriptions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `plan_name` VARCHAR(100) NOT NULL,
  `plan_type` ENUM('free', 'basic', 'pro', 'enterprise') DEFAULT 'free',
  `price` DECIMAL(10, 2) DEFAULT 0.00,
  `status` ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
  `start_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `end_date` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `features` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) UNIQUE NOT NULL,
  `description` TEXT,
  `frontend_code` LONGTEXT,
  `backend_code` LONGTEXT,
  `sql_code` TEXT,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) UNIQUE NOT NULL,
  `setting_value` LONGTEXT,
  `setting_type` ENUM('text', 'css', 'js', 'json') DEFAULT 'text',
  `description` VARCHAR(255),
  `updated_by` INT,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`),
  INDEX `idx_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert demo superadmin user (password: tozradar69)
INSERT INTO `users` (`username`, `email`, `password`, `role`) VALUES
('admin', 'admin@tozradar.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin');

-- Insert demo subscription
INSERT INTO `subscriptions` (`user_id`, `plan_name`, `plan_type`, `price`, `status`) VALUES
(1, 'Enterprise Plan', 'enterprise', 99.99, 'active');

UPDATE `users` SET `subscription_id` = 1 WHERE `id` = 1;

-- Insert default settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('site_name', 'TozRadar', 'text', 'Site name displayed in header'),
('custom_css', '', 'css', 'Global custom CSS'),
('custom_js', '', 'js', 'Global custom JavaScript'),
('maintenance_mode', '0', 'text', 'Enable maintenance mode');

-- Insert demo features
INSERT INTO `features` (`title`, `slug`, `description`, `frontend_code`, `backend_code`, `created_by`) VALUES
('Security Scanner', 'security-scanner', 'Advanced security scanning and threat detection tools', '<div class="feature-content"><h2>Security Scanner</h2><p>Scan your network for vulnerabilities.</p></div>', '<div class="backend-content"><h3>Scanner Settings</h3><p>Configure scan parameters here.</p></div>', 1),
('Threat Monitor', 'threat-monitor', 'Real-time threat monitoring and alerting system', '<div class="feature-content"><h2>Threat Monitor</h2><p>Monitor threats in real-time.</p></div>', '<div class="backend-content"><h3>Monitor Settings</h3><p>Configure monitoring thresholds.</p></div>', 1);
