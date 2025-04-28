CREATE DATABASE IF NOT EXISTS helphub;

USE helphub;

CREATE TABLE IF NOT EXISTS association (
    assoc_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(200) NOT NULL,
    fiscal_id VARCHAR(10) NOT NULL UNIQUE,
    logo_path VARCHAR(100),
    pseudo VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(60) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    representative_name VARCHAR(50) NOT NULL,
    representative_surname VARCHAR(50) NOT NULL,
    cin VARCHAR(8) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS donor (
    donor_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    surname VARCHAR(50) NOT NULL,
    ctn VARCHAR(8) NOT NULL UNIQUE,
    pseudo VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(60) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    profile_image VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS project (
    project_id INT AUTO_INCREMENT PRIMARY KEY,
    assoc_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(30) NOT NULL,
    goal_amount DECIMAL(10,2) NOT NULL,
    current_amount DECIMAL(10,2) DEFAULT 0,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    image_path VARCHAR(100),
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assoc_id) REFERENCES association(assoc_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS donation (
    donation_id INT AUTO_INCREMENT PRIMARY KEY,
    donor_id INT NOT NULL,
    project_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    anonymous BOOLEAN DEFAULT FALSE,
    donation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_id) REFERENCES donor(donor_id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES project(project_id) ON DELETE CASCADE
);

INSERT INTO association (name, address, fiscal_id, pseudo, password, email, representative_name, representative_surname, cin)
VALUES ('Global Aid Foundation', '123 Charity Lane, New York, NY 10001', '$ABC12', 'GlobalAid', '$2y$10$exampleHashedPassword1...', 'contact@globalaid.org', 'John', 'Smith', '12345678');

INSERT INTO donor (name, surname, ctn, pseudo, password, email)
VALUES ('Jane', 'Doe', '87654321', 'JaneDoe', '$2y$10$4sDMIGe.OgkCE.QKbTl.0upOVCeDb.EQbWQ.JezdY6w6UGqpHm/Y.', 'jane.doe@example.com');

INSERT INTO project (assoc_id, title, description, category, goal_amount, current_amount, start_date, end_date, image_path, status)
VALUES (1, 'Clean Water Initiative', 'This project aims to provide clean water to rural communities in developing countries. Access to clean water is a fundamental human right, yet millions around the world still lack this basic necessity.', 'Environment', 5000.00, 3200.00, '2023-10-01', '2023-12-31', NULL, 'active');

INSERT INTO donation (donor_id, project_id, amount, anonymous)
VALUES (1, 1, 250.00, false);
