-- Create vendor_requisitions table to store requisitions sent to vendor registration area

CREATE TABLE IF NOT EXISTS `vendor_requisitions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requisition_id` int(11) NOT NULL,
  `vendor_id` varchar(50) DEFAULT NULL,
  `sent_to` varchar(50) NOT NULL DEFAULT 'vendor_registration',
  `sent_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_by` varchar(50) NOT NULL DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_requisition_id` (`requisition_id`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_sent_to` (`sent_to`),
  FOREIGN KEY (`requisition_id`) REFERENCES `requisitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
