-- Borrowing and Returning Monitoring System
-- Database: brms_db
CREATE DATABASE IF NOT EXISTS brms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE brms_db;

-- USERS
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL, -- hashed (password_hash)
    contact_number VARCHAR(20) NOT NULL,
    role ENUM('admin','borrower') NOT NULL DEFAULT 'borrower',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ITEMS
CREATE TABLE items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_code VARCHAR(30) UNIQUE NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    quantity INT NOT NULL DEFAULT 0,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- BORROW RECORDS
CREATE TABLE borrow_records (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    borrow_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    due_date DATE NOT NULL,
    return_date DATETIME NULL,
    status ENUM('borrowed','returned','overdue') NOT NULL DEFAULT 'borrowed',
    penalty DECIMAL(8,2) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- SMS LOGS
CREATE TABLE sms_logs (
    sms_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    contact_number VARCHAR(20),
    message TEXT,
    sms_type ENUM('borrow','return','overdue') NOT NULL,
    status VARCHAR(30),
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- SAMPLE DATA
-- default admin password = admin123 ; borrower password = user123
INSERT INTO users (full_name, username, password, contact_number, role) VALUES
('System Admin','admin','$2y$10$e0NRzC1m3zP5ATRT0jM5I.0Jx2Kk0Z6nQk1bCJqL8oCJjN4nO5jXq','09171234567','admin'),
('Juan Dela Cruz','juan','$2y$10$e0NRzC1m3zP5ATRT0jM5I.0Jx2Kk0Z6nQk1bCJqL8oCJjN4nO5jXq','09181234567','borrower'),
('Maria Santos','maria','$2y$10$e0NRzC1m3zP5ATRT0jM5I.0Jx2Kk0Z6nQk1bCJqL8oCJjN4nO5jXq','09191234567','borrower');

INSERT INTO items (item_code, item_name, category, quantity, description) VALUES
('BK001','Introduction to Programming','Book',5,'C++ programming book'),
('BK002','Database Systems','Book',3,'DBMS textbook'),
('LP001','Laptop Acer','Equipment',2,'For lab use'),
('PR001','Projector Epson','Equipment',1,'HDMI projector'),
('CL001','HDMI Cable','Accessory',10,'2 meter HDMI cable');

INSERT INTO borrow_records (user_id, item_id, borrow_date, due_date, status) VALUES
(2,1,NOW(),DATE_ADD(CURDATE(), INTERVAL 7 DAY),'borrowed'),
(3,3,NOW(),DATE_SUB(CURDATE(), INTERVAL 2 DAY),'overdue');
