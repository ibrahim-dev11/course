-- تەیبلەکان بۆ InfinityFree (بەبێ CREATE DATABASE)

CREATE TABLE IF NOT EXISTS polls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(500) NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    allow_multiple TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    poll_id INT NOT NULL,
    option_text VARCHAR(500) NOT NULL,
    option_icon VARCHAR(100) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    poll_id INT NOT NULL,
    option_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    voter_name VARCHAR(100) DEFAULT NULL,
    comment TEXT DEFAULT NULL,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
    FOREIGN KEY (option_id) REFERENCES options(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vote (poll_id, ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO polls (title, description, is_active) VALUES (
    'ئایا دەتەوێت کۆرسی مۆبایل ئەپلیکەیشن بکەمەوە بە فوول ستاکی؟',
    'Flutter بۆ فرۆنت ئێند + Laravel بۆ باک ئێند',
    1
);

SET @poll_id = LAST_INSERT_ID();

INSERT INTO options (poll_id, option_text, option_icon, sort_order) VALUES
(@poll_id, 'بەڵێ، دەمەوێت! زۆر باشە 🔥', 'yes', 1),
(@poll_id, 'نەخێر ❌', 'no', 2);
