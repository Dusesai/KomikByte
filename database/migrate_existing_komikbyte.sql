-- Use only when a previous, incomplete `komikbyte` database already exists.
-- It preserves existing user records while aligning the table with KomikByte.
USE komikbyte;

ALTER TABLE users
    MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    MODIFY username VARCHAR(30) NOT NULL,
    MODIFY email VARCHAR(254) NOT NULL,
    MODIFY password_hash VARCHAR(255) NOT NULL,
    MODIFY wallet_balance DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00,
    MODIFY created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE users
    ADD UNIQUE KEY uq_users_username (username),
    ADD UNIQUE KEY uq_users_email (email);

CREATE TABLE IF NOT EXISTS comics (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    author VARCHAR(120) NOT NULL,
    cover_image VARCHAR(500) NULL,
    status ENUM('ONGOING', 'COMPLETED', 'HIATUS') NOT NULL DEFAULT 'ONGOING',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_comics_title (title)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS chapters (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    comic_id INT UNSIGNED NOT NULL,
    chapter_number DECIMAL(6,1) UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    content LONGTEXT NOT NULL,
    price DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_chapters_comic FOREIGN KEY (comic_id) REFERENCES comics(id) ON DELETE CASCADE,
    UNIQUE KEY uq_chapter_number (comic_id, chapter_number),
    KEY idx_chapters_comic (comic_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS chapter_access (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    chapter_id INT UNSIGNED NOT NULL,
    unlocked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_access_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_access_chapter FOREIGN KEY (chapter_id) REFERENCES chapters(id) ON DELETE CASCADE,
    UNIQUE KEY uq_user_chapter_access (user_id, chapter_id),
    KEY idx_access_chapter (chapter_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS wallet_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type ENUM('TOP_UP', 'CHAPTER_PURCHASE', 'REFUND', 'ADJUSTMENT') NOT NULL,
    amount DECIMAL(10,2) UNSIGNED NOT NULL,
    balance_before DECIMAL(10,2) UNSIGNED NOT NULL,
    balance_after DECIMAL(10,2) UNSIGNED NOT NULL,
    chapter_id INT UNSIGNED NULL,
    status ENUM('SUCCESS', 'FAILED', 'REVERSED') NOT NULL DEFAULT 'SUCCESS',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transactions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_transactions_chapter FOREIGN KEY (chapter_id) REFERENCES chapters(id) ON DELETE SET NULL,
    KEY idx_transactions_user_date (user_id, created_at),
    KEY idx_transactions_chapter (chapter_id)
) ENGINE=InnoDB;

INSERT INTO comics (title, description, author, cover_image, status) VALUES
('Neon Ronin', 'In a rain-soaked future Manila, a courier discovers a blade that remembers every battle.', 'A. Reyes', NULL, 'ONGOING'),
('Skybound Notes', 'A young mapmaker charts floating islands and finds a hidden route home.', 'M. Santos', NULL, 'ONGOING'),
('The Last Lantern', 'One tiny lantern can guide a whole town through the longest night.', 'C. Dela Cruz', NULL, 'COMPLETED')
ON DUPLICATE KEY UPDATE title = VALUES(title);

INSERT IGNORE INTO chapters (comic_id, chapter_number, title, content, price) VALUES
((SELECT id FROM comics WHERE title = 'Neon Ronin'), 1.0, 'Signal in the Rain', 'The city never sleeps. Neon reflected off every flooded street as Kai received a delivery with no return address.\n\nAt its center was an old steel sheath, warm despite the midnight rain.', 0.00),
((SELECT id FROM comics WHERE title = 'Neon Ronin'), 2.0, 'Memory Blade', 'The sheath opened with a sound like distant thunder. A voice spoke from the blade: “I remember the last person who carried me.”\n\nKai ran before the patrol drones saw the light.', 0.00),
((SELECT id FROM comics WHERE title = 'Neon Ronin'), 3.0, 'Circuit Shrine', 'Beneath the elevated rails, Kai found the shrine drawn on the delivery map. Every candle was electric. Every prayer was encrypted.', 5.00),
((SELECT id FROM comics WHERE title = 'Neon Ronin'), 4.0, 'The Borrowed Name', 'A stranger called Kai by a name he had never heard — and the blade answered for him.', 10.00),
((SELECT id FROM comics WHERE title = 'Skybound Notes'), 1.0, 'The Island Above', 'Lina’s first map was a mistake. The mountain she drew rose into the sky, and by morning it was waiting above her village.', 0.00),
((SELECT id FROM comics WHERE title = 'Skybound Notes'), 2.0, 'Cloud Ferry', 'The ferry captain only asked for one thing before the voyage: a promise never to look down.', 5.00),
((SELECT id FROM comics WHERE title = 'The Last Lantern'), 1.0, 'A Small Light', 'When the lamps went out, Tala lit the last lantern. Its gentle glow reached farther than anyone expected.', 0.00),
((SELECT id FROM comics WHERE title = 'The Last Lantern'), 2.0, 'Morning Path', 'The townspeople followed the lantern through the forest, discovering that dawn had been close all along.', 5.00);
