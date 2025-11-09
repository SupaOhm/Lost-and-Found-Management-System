CREATE DATABASE IF NOT EXISTS lost_found_db;
USE lost_found_db;

-- USER TABLE
CREATE TABLE User (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- LOST ITEM TABLE
CREATE TABLE LostItem (
    lost_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    item_name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    location VARCHAR(100),
    lost_date DATE,
    status ENUM('pending', 'claimed') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES User(user_id)
);

-- ADMIN TABLE
CREATE TABLE Admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- FOUND ITEM TABLE
CREATE TABLE FoundItem (
    found_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    item_name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    location VARCHAR(100),
    found_date DATE,
    status ENUM('available', 'returned') DEFAULT 'available',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES User(user_id)
);

-- CLAIM REQUEST TABLE
CREATE TABLE ClaimRequest (
    claim_id INT AUTO_INCREMENT PRIMARY KEY,
    lost_id INT,
    found_id INT,
    user_id INT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    claim_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    approved_by INT NULL,
    approved_date DATETIME NULL,
    FOREIGN KEY (lost_id) REFERENCES LostItem(lost_id),
    FOREIGN KEY (found_id) REFERENCES FoundItem(found_id),
    FOREIGN KEY (user_id) REFERENCES User(user_id),
    FOREIGN KEY (approved_by) REFERENCES Admin(admin_id)
);
