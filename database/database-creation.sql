-- ============================================================
-- BIOGAS CONTAINMENT MONITORING SYSTEM — DATABASE SETUP
-- DB Name     : u442411629_bcms
-- DB User     : u442411629_dev_bcms
-- Run via phpMyAdmin or MySQL CLI
-- ============================================================

-- NOTE: When using shared hosting phpMyAdmin, the database
-- already exists. Run only from CREATE TABLE onward.
-- If running locally for dev, uncomment the DROP/CREATE lines.

-- DROP DATABASE IF EXISTS u442411629_bcms;
-- CREATE DATABASE u442411629_bcms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE u442411629_bcms;

-- ── USERS ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    user_id    INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(255) UNIQUE NOT NULL,
    password   VARCHAR(255)        NOT NULL,
    role       ENUM('admin','manager','user') NOT NULL DEFAULT 'user',
    verified   TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── GAS USAGE (Flow rate & volume consumed) ──────────────────
CREATE TABLE IF NOT EXISTS gas_usage (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT,
    flow_rate   FLOAT COMMENT 'L/min',
    gas_used    FLOAT COMMENT 'Total litres used',
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── METHANE MONITORING (ppm levels + alert status) ───────────
CREATE TABLE IF NOT EXISTS methane_monitoring (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT,
    methane_ppm FLOAT,
    status      ENUM('SAFE','WARNING','LEAK') DEFAULT 'SAFE',
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── GAS LEVEL (Pressure & tank percentage) ───────────────────
CREATE TABLE IF NOT EXISTS gas_level (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT,
    pressure_kpa    FLOAT COMMENT 'Pressure in kPa',
    gas_percentage  FLOAT COMMENT 'Tank fill level 0-100%',
    recorded_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── ACTIVITY LOGS ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS activity_logs (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NULL,
    email         VARCHAR(255),
    activity      VARCHAR(255) NOT NULL,
    activity_type ENUM('login','sensor','system','admin') DEFAULT 'system',
    ip_address    VARCHAR(50),
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
