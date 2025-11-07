CREATE DATABASE IF NOT EXISTS lost_found_db;
USE lost_found_db;



-- TABLE 1: USERS
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);



-- TABLE 2: ADMINS
CREATE TABLE admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);



-- TABLE 3: LOST ITEMS
CREATE TABLE lost_items (
    lost_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    item_name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    lost_date DATE,
    location VARCHAR(255),
    status ENUM('lost','found','claimed') DEFAULT 'lost',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);



-- TABLE 4: FOUND ITEMS
CREATE TABLE found_items (
    found_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    item_name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    found_date DATE,
    location VARCHAR(255),
    status ENUM('available','claimed') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);



-- TABLE 5: CLAIMS
CREATE TABLE claims (
    claim_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    found_id INT,
    reason TEXT,
    claim_status ENUM('pending','approved','rejected') DEFAULT 'pending',
    claim_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (found_id) REFERENCES found_items(found_id) ON DELETE CASCADE
);