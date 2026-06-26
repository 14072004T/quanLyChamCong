-- Migration script to add face profiles and attendance photo evidence

-- 1. Create face_profiles table
CREATE TABLE IF NOT EXISTS `face_profiles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `maND` INT NOT NULL,
  `embedding` TEXT NOT NULL,
  `ngayTao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ngayCapNhat` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_maND` (`maND`),
  FOREIGN KEY (`maND`) REFERENCES `nguoidung` (`maND`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Add anhMinhChung column to lichSuChamCong
ALTER TABLE `lichSuChamCong` ADD COLUMN IF NOT EXISTS `anhMinhChung` VARCHAR(255) DEFAULT NULL;
