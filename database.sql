-- UMMA Student Voting System Database Schema
-- XAMPP / MariaDB compatible
-- This script resets the application tables and creates a working demo database.

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';

CREATE DATABASE IF NOT EXISTS umma_voting
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE umma_voting;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS votes;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS elections;
DROP TABLE IF EXISTS candidates;
DROP TABLE IF EXISTS admin_users;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ===== USERS / STUDENTS =====
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    national_id VARCHAR(50) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    student_id VARCHAR(50) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    voted TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_national_id (national_id),
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_student_id (student_id),
    KEY idx_users_status (status),
    KEY idx_users_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== ADMIN USERS =====
CREATE TABLE admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    full_name VARCHAR(150) NOT NULL DEFAULT 'Super Admin',
    last_login_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_admin_username (username),
    UNIQUE KEY uq_admin_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin: admin@gmail.com / 123456
INSERT INTO admin_users (id, username, password, email, full_name) VALUES
(1, 'admin', '$2y$10$.yE185KkWGg5SjzCT9diFOvy12834p8p4tfR8CCw.Z7fgJEiSnMT6', 'admin@gmail.com', 'Super Admin')
ON DUPLICATE KEY UPDATE
    username = VALUES(username),
    password = VALUES(password),
    email = VALUES(email),
    full_name = VALUES(full_name);

-- ===== CANDIDATES =====
CREATE TABLE candidates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    party VARCHAR(100) NOT NULL,
    position VARCHAR(100) NOT NULL DEFAULT 'SRC President',
    photo VARCHAR(255) DEFAULT NULL,
    manifesto TEXT,
    status ENUM('active', 'pending', 'rejected') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_candidates_status (status),
    KEY idx_candidates_position (position),
    KEY idx_candidates_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== ELECTIONS =====
CREATE TABLE elections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    max_votes_per_student INT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('upcoming', 'active', 'completed', 'cancelled') NOT NULL DEFAULT 'upcoming',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_elections_status (status),
    KEY idx_elections_dates (start_date, end_date),
    KEY idx_elections_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default active election
INSERT INTO elections (id, title, description, start_date, end_date, max_votes_per_student, status) VALUES
(1, 'SRC General Election 2026', 'Election for UMMA Student Representative Council positions', '2026-07-01 08:00:00', '2026-12-31 17:00:00', 5, 'active')
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    description = VALUES(description),
    start_date = VALUES(start_date),
    end_date = VALUES(end_date),
    max_votes_per_student = VALUES(max_votes_per_student),
    status = VALUES(status);

-- Default candidates grouped by election position
INSERT INTO candidates (name, party, position, manifesto, status)
SELECT 'Fatima A. Hassan', 'UMMA First Alliance', 'SRC President', 'Transparent leadership, stronger welfare, and better access to student services.', 'active'
WHERE NOT EXISTS (SELECT 1 FROM candidates WHERE name = 'Fatima A. Hassan' AND position = 'SRC President')
UNION ALL
SELECT 'Mohammed Ahmed', 'Student Voice Movement', 'SRC President', 'A practical student agenda focused on hostels, library hours, and internships.', 'active'
WHERE NOT EXISTS (SELECT 1 FROM candidates WHERE name = 'Mohammed Ahmed' AND position = 'SRC President')
UNION ALL
SELECT 'Aisha Mushi', 'Unity Student Party', 'SRC President', 'Inclusive representation across Kajiado, Thika, and Garissa campuses.', 'active'
WHERE NOT EXISTS (SELECT 1 FROM candidates WHERE name = 'Aisha Mushi' AND position = 'SRC President')
UNION ALL
SELECT 'Musa I. Ali', 'Progress Party UMMA', 'Vice President', 'Digital transformation, better Wi-Fi, and stronger student innovation support.', 'active'
WHERE NOT EXISTS (SELECT 1 FROM candidates WHERE name = 'Musa I. Ali' AND position = 'Vice President')
UNION ALL
SELECT 'Hanan Ibrahim', 'Student Voice Movement', 'Vice President', 'Affordable learning support and improved student mental health services.', 'active'
WHERE NOT EXISTS (SELECT 1 FROM candidates WHERE name = 'Hanan Ibrahim' AND position = 'Vice President')
UNION ALL
SELECT 'Farriz Kimani', 'Reform Coalition', 'Secretary General', 'Clear communication, transparent minutes, and responsive student representation.', 'active'
WHERE NOT EXISTS (SELECT 1 FROM candidates WHERE name = 'Farriz Kimani' AND position = 'Secretary General')
UNION ALL
SELECT 'Nassir Ahmed', 'UMMA First', 'Secretary General', 'Efficient administration and better coordination between student offices.', 'active'
WHERE NOT EXISTS (SELECT 1 FROM candidates WHERE name = 'Nassir Ahmed' AND position = 'Secretary General')
UNION ALL
SELECT 'Grace Wanjiku', 'Accountability Team', 'Treasurer', 'Transparent student funds, regular reports, and responsible budgeting.', 'active'
WHERE NOT EXISTS (SELECT 1 FROM candidates WHERE name = 'Grace Wanjiku' AND position = 'Treasurer')
UNION ALL
SELECT 'Samuel Kiprop', 'Campus Growth Party', 'Treasurer', 'Fair resource allocation and stronger support for student clubs.', 'active'
WHERE NOT EXISTS (SELECT 1 FROM candidates WHERE name = 'Samuel Kiprop' AND position = 'Treasurer')
UNION ALL
SELECT 'Zainab A. Hassan', 'Academic Voice', 'Academic Representative', 'Better academic feedback channels and stronger lecturer-student engagement.', 'active'
WHERE NOT EXISTS (SELECT 1 FROM candidates WHERE name = 'Zainab A. Hassan' AND position = 'Academic Representative')
UNION ALL
SELECT 'Peter O. Okello', 'Student Success Forum', 'Academic Representative', 'Improved academic advising, exam support, and learning resources.', 'active'
WHERE NOT EXISTS (SELECT 1 FROM candidates WHERE name = 'Peter O. Okello' AND position = 'Academic Representative');

-- ===== VOTES =====
CREATE TABLE votes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    candidate_id INT UNSIGNED NOT NULL,
    election_id INT UNSIGNED NOT NULL,
    position VARCHAR(100) NOT NULL,
    voted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_votes_user_election_position (user_id, election_id, position),
    KEY idx_votes_candidate (candidate_id),
    KEY idx_votes_election (election_id),
    KEY idx_votes_position (position),
    KEY idx_votes_voted_at (voted_at),
    CONSTRAINT fk_votes_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_votes_candidate
        FOREIGN KEY (candidate_id) REFERENCES candidates(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_votes_election
        FOREIGN KEY (election_id) REFERENCES elections(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== CONTACT MESSAGES =====
CREATE TABLE contact_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_contact_is_read (is_read),
    KEY idx_contact_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== SETTINGS =====
CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_settings_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'UMMA Student Voting System'),
('maintenance_mode', '0'),
('email_notifications', '1'),
('auto_backup', '1'),
('two_factor_auth', '0')
ON DUPLICATE KEY UPDATE
    setting_value = VALUES(setting_value);

-- ===== ACTIVITY LOGS =====
CREATE TABLE activity_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('admin', 'student') NOT NULL DEFAULT 'admin',
    user_id INT UNSIGNED DEFAULT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_activity_user (user_type, user_id),
    KEY idx_activity_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
