-- สคริปต์สำหรับอัปเดตรหัสผ่านในฐานข้อมูล
-- รันสคริปต์นี้เพื่อสร้าง password hash ที่ถูกต้องสำหรับผู้ใช้

-- สร้างตาราง login_attempts สำหรับป้องกัน brute force attack
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(40) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `success` tinyint(1) DEFAULT 0,
  `attempt_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_username_time` (`username`, `attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- อัปเดตรหัสผ่านสำหรับผู้ใช้ (รหัสผ่านเริ่มต้น: password123)
-- สำหรับ admin: password123
UPDATE `staffs` SET `password_hash` = '$2y$12$mkf0lMlKVW7.7TPfiyiPuuahY9vGWdJYS41QoD7XMKGz8cLUEOq2q' WHERE `username` = 'admin';

-- สำหรับ tech01: password123
UPDATE `staffs` SET `password_hash` = '$2y$12$mkf0lMlKVW7.7TPfiyiPuuahY9vGWdJYS41QoD7XMKGz8cLUEOq2q' WHERE `username` = 'tech01';

-- สำหรับ user01: password123
UPDATE `staffs` SET `password_hash` = '$2y$12$mkf0lMlKVW7.7TPfiyiPuuahY9vGWdJYS41QoD7XMKGz8cLUEOq2q' WHERE `username` = 'user01';

-- หมายเหตุ: 
-- รหัสผ่านเริ่มต้นสำหรับทุกบัญชีคือ: password123
-- ควรเปลี่ยนรหัสผ่านทันทีหลังจาก login สำเร็จ
-- Hash ที่ใช้คือ bcrypt (PASSWORD_DEFAULT) ซึ่งมีความปลอดภัยสูง

