-- DevPortfolio - them khoang thoi gian du an (KHONG XOA DU LIEU)
-- Vao phpMyAdmin, CHON devportfolio_db truoc, sau do Import file nay MOT LAN.
-- Yeu cau bang projects da ton tai, sao luu SQL truoc khi chay.
ALTER TABLE projects
    ADD COLUMN start_date DATE NULL AFTER project_date,
    ADD COLUMN end_date DATE NULL AFTER start_date,
    ADD COLUMN is_ongoing TINYINT(1) NOT NULL DEFAULT 0 AFTER end_date;

-- Ngay du an cu khong ro la bat dau hay ket thuc: giu nguyen trong project_date,
-- sao chep sang start_date de hien thi, nguoi dung co the chinh sau.
UPDATE projects SET start_date = project_date WHERE start_date IS NULL AND project_date IS NOT NULL;
