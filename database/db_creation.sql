CREATE DATABASE IF NOT EXISTS u442411629_bcmsdb
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE u442411629_bcmsdb;

-- USERS
CREATE TABLE IF NOT EXISTS users (
    user_id    INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(255) UNIQUE NOT NULL,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('admin','manager','user') NOT NULL DEFAULT 'user',
    verified   TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- GAS USAGE
CREATE TABLE IF NOT EXISTS gas_usage (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT,
    flow_rate   FLOAT,
    gas_used    FLOAT,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- METHANE MONITORING
CREATE TABLE IF NOT EXISTS methane_monitoring (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT,
    methane_ppm FLOAT,
    status      ENUM('SAFE','WARNING','LEAK') DEFAULT 'SAFE',
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- GAS LEVEL
CREATE TABLE IF NOT EXISTS gas_level (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT,
    pressure_kpa   FLOAT,
    gas_percentage FLOAT,
    recorded_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- ACTIVITY LOGS
CREATE TABLE IF NOT EXISTS activity_logs (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NULL,
    email         VARCHAR(255),
    activity      VARCHAR(255) NOT NULL,
    activity_type ENUM('login','logout','login_failed','sensor','system','admin'),
    ip_address    VARCHAR(50),
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);