-- =====================================================
-- TAMBAHAN DATA UNTUK LOGIN
-- Jalankan query ini di phpMyAdmin setelah import rental (1).sql
-- =====================================================

-- Insert admin default (password: admin123)
INSERT INTO `tbl_user` (`user`, `pass`, `lvl`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Password yang digunakan: admin123
-- Username: admin
