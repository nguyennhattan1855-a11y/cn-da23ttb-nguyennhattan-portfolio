-- Chỉ chạy MỘT LẦN, trên CSDL devportfolio_db đã sao lưu.
-- Giữ nguyên cột degree: dữ liệu cũ trong cột này là NGÀNH HỌC.
-- Thêm cột qualification cho bằng cấp / trạng thái chưa tốt nghiệp.
ALTER TABLE educations
  ADD COLUMN qualification VARCHAR(150) NULL AFTER degree;
