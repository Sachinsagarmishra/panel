-- Bucket List System SQL
CREATE TABLE IF NOT EXISTS goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category ENUM('Business', 'Travel', 'Health', 'Wealth', 'Personal', 'Other') DEFAULT 'Personal',
    priority ENUM('High', 'Medium', 'Low') DEFAULT 'Medium',
    status ENUM('Dreaming', 'In Progress', 'Accomplished') DEFAULT 'Dreaming',
    target_date DATE NULL,
    image_url VARCHAR(255) NULL,
    motivation_note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS goal_milestones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    goal_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    is_completed TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE
);
