-- Table for evaluation metrics (KPIs)
CREATE TABLE IF NOT EXISTS evaluation_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for rating scales
CREATE TABLE IF NOT EXISTS rating_scales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    scale_type ENUM('numeric','descriptive') NOT NULL DEFAULT 'numeric',
    min_value INT DEFAULT 1,
    max_value INT DEFAULT 5,
    labels TEXT, -- JSON array for descriptive labels
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for feedback source weights
CREATE TABLE IF NOT EXISTS feedback_source_weights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source VARCHAR(50) NOT NULL, -- e.g., manager, peer, self
    weight_percent INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for templates (role/department-based)
CREATE TABLE IF NOT EXISTS evaluation_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(50),
    department VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for template metrics and weights
CREATE TABLE IF NOT EXISTS template_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    metric_id INT NOT NULL,
    weight_percent INT NOT NULL,
    visibility VARCHAR(50) DEFAULT 'all', -- e.g., manager, peer, self, hr, all
    FOREIGN KEY (template_id) REFERENCES evaluation_templates(id) ON DELETE CASCADE,
    FOREIGN KEY (metric_id) REFERENCES evaluation_metrics(id) ON DELETE CASCADE
);
