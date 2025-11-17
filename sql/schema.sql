DROP DATABASE IF EXISTS lost_found_db;
CREATE DATABASE IF NOT EXISTS lost_found_db;
USE lost_found_db;

-- USER TABLE
CREATE TABLE User (
    user_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARBINARY(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARBINARY(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- LOST ITEM TABLE
CREATE TABLE LostItem (
    lost_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT,
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
    admin_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARBINARY(255) NOT NULL,
    email VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- STAFF TABLE
CREATE TABLE Staff (
    staff_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARBINARY(255) NOT NULL,
    email VARCHAR(100),
    full_name VARCHAR(100),
    phone VARBINARY(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Stored procedure to verify admin login


-- FOUND ITEM TABLE
CREATE TABLE FoundItem (
    found_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT,
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
    claim_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    found_id BIGINT,
    description VARCHAR(500),
    user_id BIGINT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    claim_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    approver_id BIGINT NULL,
    approver_type ENUM('admin', 'staff') NULL,
    approved_date DATETIME NULL,
    FOREIGN KEY (found_id) REFERENCES FoundItem(found_id),
    FOREIGN KEY (user_id) REFERENCES User(user_id)
);
