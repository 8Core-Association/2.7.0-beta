-- SEUP Archive Tables Creation Script
-- (c) 2025 8Core Association

-- Create a_arhiva table for archived predmeti
CREATE TABLE IF NOT EXISTS `llx_a_arhiva` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `ID_predmeta` int(11) NOT NULL,
  `datum_arhiviranja` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `razlog_arhiviranja` text DEFAULT NULL,
  `fk_arhivska_gradiva` int(11) DEFAULT NULL,
  `postupak_po_isteku` enum('predaja_arhivu','ibp_izlucivanje','ibp_brisanje') DEFAULT 'predaja_arhivu',
  `status_arhive` enum('active','inactive') DEFAULT 'active',
  `datum_povrata` datetime DEFAULT NULL,
  `fk_user_arhiva` int(11) NOT NULL,
  `fk_user_povrat` int(11) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `unique_predmet_active` (`ID_predmeta`, `status_arhive`),
  KEY `idx_predmet` (`ID_predmeta`),
  KEY `idx_status` (`status_arhive`),
  KEY `idx_datum` (`datum_arhiviranja`),
  KEY `idx_gradiva` (`fk_arhivska_gradiva`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create a_arhivska_gradiva table for archive material types
CREATE TABLE IF NOT EXISTS `llx_a_arhivska_gradiva` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `oznaka` varchar(100) NOT NULL,
  `vrsta_gradiva` varchar(255) NOT NULL,
  `opisi_napomene` text DEFAULT NULL,
  `datec` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `entity` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `unique_oznaka_entity` (`oznaka`, `entity`),
  KEY `idx_entity` (`entity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default arhivska gradiva if table is empty
INSERT IGNORE INTO `llx_a_arhivska_gradiva` (`oznaka`, `vrsta_gradiva`, `opisi_napomene`, `entity`) VALUES
('ARH-001', 'Administrativni spisi', 'Opći administrativni dokumenti i korespondencija', 1),
('ARH-002', 'Financijski dokumenti', 'Računi, ugovori, financijski izvještaji', 1),
('ARH-003', 'Kadrovska dokumentacija', 'Dokumenti vezani uz zaposlene i kadrove', 1),
('ARH-004', 'Pravni akti', 'Odluke, rješenja, pravni dokumenti', 1),
('ARH-005', 'Projektna dokumentacija', 'Dokumenti vezani uz projekte i inicijative', 1);

-- Add indexes for better performance
ALTER TABLE `llx_a_arhiva` 
ADD INDEX `idx_entity` (`entity`),
ADD INDEX `idx_datum_status` (`datum_arhiviranja`, `status_arhive`);

-- Add foreign key constraints if tables exist
-- Note: These will only work if referenced tables exist
SET @sql = (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'llx_a_predmet') > 0,
  'ALTER TABLE `llx_a_arhiva` ADD CONSTRAINT `fk_arhiva_predmet` FOREIGN KEY (`ID_predmeta`) REFERENCES `llx_a_predmet` (`ID_predmeta`) ON DELETE CASCADE',
  'SELECT "llx_a_predmet table does not exist, skipping foreign key"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;