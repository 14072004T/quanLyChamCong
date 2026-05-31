SET FOREIGN_KEY_CHECKS=0;

ALTER TABLE nguoidung CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Fix mojibake caused by importing UTF-8 data as latin1
UPDATE nguoidung
SET phongBan = CONVERT(BINARY CONVERT(phongBan USING latin1) USING utf8mb4)
WHERE phongBan IS NOT NULL AND phongBan <> '';

SET FOREIGN_KEY_CHECKS=1;
