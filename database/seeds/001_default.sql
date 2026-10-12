-- Password admin: admin123 (Argon2id — ganti segera setelah login)
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@videovault.local',
 '$argon2id$v=19$m=65536,t=4,p=1$Z2x5cGhpY2t1bmRlc2NyaWJlZHNhbHQ$9vXFiTzOaB1nBrY7QF3G0Vc4bTlZz3L4H5L3r0R8k2A',
 'admin')
ON DUPLICATE KEY UPDATE username = username;

INSERT INTO categories (name, slug) VALUES
('Film', 'film'),
('Animasi', 'animasi'),
('Dokumenter', 'dokumenter'),
('Tutorial', 'tutorial'),
('Hiburan', 'hiburan'),
('Teknologi', 'teknologi')
ON DUPLICATE KEY UPDATE name = name;
