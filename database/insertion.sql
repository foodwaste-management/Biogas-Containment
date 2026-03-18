-- password is: password123
INSERT INTO users (email, password, role, verified) VALUES
('admin@example.com',   '$2y$10$hXRkgpF58Biw2BLi/bkA.uJU8y1HxFi34ul8b8nV.ZG/30HcqCwc6', 'admin',   1),
('manager@example.com', '$2y$10$hXRkgpF58Biw2BLi/bkA.uJU8y1HxFi34ul8b8nV.ZG/30HcqCwc6', 'manager', 1),
('user@example.com',    '$2y$10$hXRkgpF58Biw2BLi/bkA.uJU8y1HxFi34ul8b8nV.ZG/30HcqCwc6', 'user',    1);

-- Sample gas_usage
INSERT INTO gas_usage (user_id, flow_rate, gas_used) VALUES
(3, 2.5, 12.3), (3, 3.1, 15.7), (3, 1.8, 9.0);

-- Sample methane_monitoring
INSERT INTO methane_monitoring (user_id, methane_ppm, status) VALUES
(3, 450, 'SAFE'), (3, 1100, 'WARNING'), (3, 2500, 'LEAK');

-- Sample gas_level
INSERT INTO gas_level (user_id, pressure_kpa, gas_percentage) VALUES
(3, 101.3, 78.5), (3, 98.7, 65.2), (3, 105.1, 85.0);