-- Wiki-Games Database Schema
-- Run this in your MySQL/phpMyAdmin

CREATE DATABASE IF NOT EXISTS wiki_games CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE wiki_games;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    genre VARCHAR(50),
    platform VARCHAR(100),
    price DECIMAL(10,2) DEFAULT 0.00,
    image VARCHAR(255),
    game_url VARCHAR(500),
    release_year YEAR,
    rating DECIMAL(3,1) DEFAULT 0.0,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Default admin account (password: Admin@1234)
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@wikigames.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Sample games
INSERT INTO games (name, description, genre, platform, price, image, game_url, release_year, rating, created_by) VALUES
('Cyberpunk 2077', 'Open-world RPG set in the dystopian Night City.', 'RPG', 'PC, PS5, Xbox', 29.99, 'https://upload.wikimedia.org/wikipedia/en/9/9f/Cyberpunk_2077_box_art.jpg', 'https://www.cyberpunk.net', 2020, 8.5, 1),
('Elden Ring', 'Action RPG crafted by FromSoftware and George R.R. Martin.', 'Action RPG', 'PC, PS5, Xbox', 59.99, 'https://upload.wikimedia.org/wikipedia/en/b/b9/Elden_Ring_Box_art.jpg', 'https://en.bandainamcoent.eu/elden-ring', 2022, 9.5, 1),
('Hollow Knight', 'Challenging underground kingdom adventure.', 'Metroidvania', 'PC, Switch', 14.99, 'https://upload.wikimedia.org/wikipedia/en/6/60/Hollow_Knight_first_cover_art.jpg', 'https://www.hollowknight.com', 2017, 9.0, 1);
