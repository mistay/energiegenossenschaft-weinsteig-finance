-- Create reminder texts table for editable mahnung/reminder templates
-- This allows admins to customize reminder messages without code changes

CREATE TABLE IF NOT EXISTS `oc_weinsteig_reminder_texts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stage` smallint unsigned NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` longtext NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_unique_stage` (`stage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default reminder texts for 2 stages (2-stage system)
-- Stage 1: Zahlungserinnerung (1st payment reminder)
INSERT INTO `oc_weinsteig_reminder_texts`
  (`stage`, `subject`, `body`, `created_at`, `updated_at`)
VALUES (
  1,
  'Zahlungserinnerung - Energiegenossenschaft Weinsteig',
  'Liebe/r {name},

wir möchten Sie höflich an folgende ausstehende Zahlung erinnern:

Haus: {address}
Offener Betrag: {amount}€
Fälligkeitsdatum: {duedate}

Falls Sie die Zahlung bereits getätigt haben, können Sie diese Nachricht ignorieren.

Vielen Dank!
Energiegenossenschaft Weinsteig',
  NOW(),
  NOW()
) ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- Stage 2: Mahnung (formal notice/final dunning letter)
INSERT INTO `oc_weinsteig_reminder_texts`
  (`stage`, `subject`, `body`, `created_at`, `updated_at`)
VALUES (
  2,
  'Mahnung - Energiegenossenschaft Weinsteig',
  'Liebe/r {name},

trotz Zahlungserinnerung ist uns der folgende Betrag noch nicht eingegangen:

Haus: {address}
Offener Betrag: {amount}€
Ursprüngliches Fälligkeitsdatum: {duedate}

Bitte überweisen Sie den ausstehenden Betrag innerhalb von 14 Tagen.

Bei Fragen: office@langhofer.at

Energiegenossenschaft Weinsteig',
  NOW(),
  NOW()
) ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- Verify the data was inserted
SELECT * FROM `oc_weinsteig_reminder_texts` ORDER BY `stage` ASC;
