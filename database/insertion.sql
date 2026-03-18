-- ============================================================
-- BCMS — DEFAULT SEED DATA
-- Passwords are all: Password@123
-- Change immediately after first login!
-- ============================================================

USE u442411629_bcms;

INSERT INTO users (email, password, role, verified, created_at) VALUES
('admin@bcms.io',   '$2y$10$hXRkgpF58Biw2BLi/bkA.uJU8y1HxFi34ul8b8nV.ZG/30HcqCwc6', 'admin',   1, NOW()),
('manager@bcms.io', '$2y$10$hXRkgpF58Biw2BLi/bkA.uJU8y1HxFi34ul8b8nV.ZG/30HcqCwc6', 'manager', 1, NOW()),
('user@bcms.io',    '$2y$10$hXRkgpF58Biw2BLi/bkA.uJU8y1HxFi34ul8b8nV.ZG/30HcqCwc6', 'user',    1, NOW());

-- ── Sample sensor data ────────────────────────────────────────
INSERT INTO gas_usage (user_id, flow_rate, gas_used) VALUES
(3, 2.5, 12.3), (3, 3.1, 15.7), (3, 1.8, 9.2);

INSERT INTO methane_monitoring (user_id, methane_ppm, status) VALUES
(3, 450,  'SAFE'),
(3, 3200, 'WARNING'),
(3, 6500, 'LEAK');

INSERT INTO gas_level (user_id, pressure_kpa, gas_percentage) VALUES
(3, 101.3, 78.5),
(3, 98.6,  65.2),
(3, 95.1,  51.0);
