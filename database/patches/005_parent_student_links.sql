-- Patch: Add parent_student_links table for proper parent to many-students mapping

CREATE TABLE IF NOT EXISTS parent_student_links (
    id INT PRIMARY KEY AUTO_INCREMENT,
    parent_user_id INT NOT NULL,
    student_id INT NOT NULL,
    relationship VARCHAR(50) DEFAULT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_parent_student (parent_user_id, student_id),
    INDEX idx_parent_user (parent_user_id),
    INDEX idx_parent_student (student_id),
    CONSTRAINT fk_parent_student_links_parent_user
        FOREIGN KEY (parent_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_parent_student_links_student
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
