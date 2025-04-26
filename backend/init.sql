-- Create database
CREATE DATABASE IF NOT EXISTS helphub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use database
USE helphub;

-- Association table
CREATE TABLE IF NOT EXISTS association (
    assoc_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    fiscal_id VARCHAR(10) NOT NULL UNIQUE,
    logo_path VARCHAR(255),
    pseudo VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    representative_name VARCHAR(100) NOT NULL,
    representative_surname VARCHAR(100) NOT NULL,
    cin VARCHAR(8) NOT NULL UNIQUE, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT check_fiscal_id CHECK (fiscal_id REGEXP '^\\$[A-Z]{3}[0-9]{2}$'),
    CONSTRAINT check_cin CHECK (cin REGEXP '^[0-9]{8}$')
);

-- Donor table
CREATE TABLE IF NOT EXISTS donor (
    donor_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    surname VARCHAR(100) NOT NULL,
    ctn VARCHAR(8) NOT NULL UNIQUE,
    pseudo VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT check_ctn CHECK (ctn REGEXP '^[0-9]{8}$')
);

-- Project table
CREATE TABLE IF NOT EXISTS project (
    project_id INT AUTO_INCREMENT PRIMARY KEY,
    assoc_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(50) NOT NULL,
    goal_amount DECIMAL(10,2) NOT NULL,
    current_amount DECIMAL(10,2) DEFAULT 0,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    image_path VARCHAR(255),
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assoc_id) REFERENCES association(assoc_id) ON DELETE CASCADE,
    CONSTRAINT check_amount CHECK (goal_amount > 0),
    CONSTRAINT check_dates CHECK (end_date >= start_date),
    CONSTRAINT check_current_amount CHECK (current_amount >= 0)
);

-- Donation table
CREATE TABLE IF NOT EXISTS donation (
    donation_id INT AUTO_INCREMENT PRIMARY KEY,
    donor_id INT NOT NULL,
    project_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    anonymous BOOLEAN DEFAULT FALSE,
    donation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_id) REFERENCES donor(donor_id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES project(project_id) ON DELETE CASCADE,
    CONSTRAINT check_donation_amount CHECK (amount > 0)
);

-- Trigger to update current_amount when a new donation is made
DELIMITER //
CREATE TRIGGER after_donation_insert
AFTER INSERT ON donation
FOR EACH ROW
BEGIN
    UPDATE project
    SET current_amount = current_amount + NEW.amount
    WHERE project_id = NEW.project_id;
END//
DELIMITER ;

-- Insert sample data
-- Association
INSERT INTO association (name, address, fiscal_id, logo_path, pseudo, password, email, representative_name, representative_surname, cin)
VALUES ('Green Earth Foundation', '123 Environmental Way, Green City, GC 12345', '$ABC12', NULL, 'GreenEarth', '$2y$10$4sDMIGe.OgkCE.QKbTl.0upOVCeDb.EQbWQ.JezdY6w6UGqpHm/Y.', 'john.doe@greenearthfoundation.org', 'John', 'Doe', '12345678');

-- Donor
INSERT INTO donor (name, surname, ctn, pseudo, password, email)
VALUES ('Jane', 'Doe', '87654321', 'JaneDoe', '$2y$10$4sDMIGe.OgkCE.QKbTl.0upOVCeDb.EQbWQ.JezdY6w6UGqpHm/Y.', 'jane.doe@example.com');

-- Project
INSERT INTO project (assoc_id, title, description, category, goal_amount, current_amount, start_date, end_date, image_path, status)
VALUES (1, 'Clean Water Initiative', 'This project aims to provide clean water to rural communities in developing countries. Access to clean water is a fundamental human right, yet millions around the world still lack this basic necessity.', 'Environment', 5000.00, 3200.00, '2023-10-01', '2023-12-31', NULL, 'active');

-- Donation
INSERT INTO donation (donor_id, project_id, amount, anonymous)
VALUES (1, 1, 250.00, false);
