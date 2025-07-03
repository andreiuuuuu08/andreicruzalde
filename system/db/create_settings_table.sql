CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default values (optional)
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('company_name', 'My Company'),
('system_email', 'admin@example.com'),
('maintenance_mode', 'off');
