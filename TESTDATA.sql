-- Test Data für weinsteigfinance
-- Fügt Vorschreibungen und Zahlungen für die ersten 3 Häuser der letzten 6 Monate hinzu

-- Beispiel: Angenommen heute ist 2026-08-15
-- Wir generieren Daten für 2026-02 bis 2026-08

-- VORSCHREIBUNGEN für Januar 2026 bis August 2026
INSERT INTO oc_weinsteig_vorschreibungen (member_id, year, month, amount, status, created_at, updated_at) VALUES
(1, 2026, 2, 60.00, 'paid', '2026-02-01 10:00:00', '2026-02-15 12:00:00'),
(1, 2026, 3, 60.00, 'paid', '2026-03-01 10:00:00', '2026-03-20 14:30:00'),
(1, 2026, 4, 60.00, 'paid', '2026-04-01 10:00:00', '2026-04-18 09:15:00'),
(1, 2026, 5, 60.00, 'paid', '2026-05-01 10:00:00', '2026-05-25 16:45:00'),
(1, 2026, 6, 60.00, 'open', '2026-06-01 10:00:00', NULL),
(1, 2026, 7, 60.00, 'open', '2026-07-01 10:00:00', NULL),
(1, 2026, 8, 60.00, 'open', '2026-08-01 10:00:00', NULL),

(2, 2026, 2, 60.00, 'paid', '2026-02-01 10:00:00', '2026-02-10 11:00:00'),
(2, 2026, 3, 60.00, 'paid', '2026-03-01 10:00:00', '2026-03-15 10:30:00'),
(2, 2026, 4, 60.00, 'paid', '2026-04-01 10:00:00', '2026-04-12 14:00:00'),
(2, 2026, 5, 60.00, 'paid', '2026-05-01 10:00:00', '2026-05-20 13:20:00'),
(2, 2026, 6, 60.00, 'paid', '2026-06-01 10:00:00', '2026-06-28 15:45:00'),
(2, 2026, 7, 60.00, 'open', '2026-07-01 10:00:00', NULL),
(2, 2026, 8, 60.00, 'open', '2026-08-01 10:00:00', NULL),

(3, 2026, 2, 60.00, 'paid', '2026-02-01 10:00:00', '2026-02-22 10:15:00'),
(3, 2026, 3, 60.00, 'paid', '2026-03-01 10:00:00', '2026-03-25 11:30:00'),
(3, 2026, 4, 60.00, 'paid', '2026-04-01 10:00:00', '2026-04-20 09:45:00'),
(3, 2026, 5, 60.00, 'open', '2026-05-01 10:00:00', NULL),
(3, 2026, 6, 60.00, 'open', '2026-06-01 10:00:00', NULL),
(3, 2026, 7, 60.00, 'open', '2026-07-01 10:00:00', NULL),
(3, 2026, 8, 60.00, 'open', '2026-08-01 10:00:00', NULL);

-- ZAHLUNGEN für member_id 1 (Haus 1)
INSERT INTO oc_weinsteig_zahlungen (member_id, buchungsdatum, valutadatum, partnername, verwendungszweck, betrag, waehrung, iban, bic, match_type, match_confidence, status, created_at, updated_at) VALUES
(1, '2026-02-10', '2026-02-12', 'Max Mustermann', 'Akontozahlung 02/2026', 60.00, 'EUR', 'AT123456789012345678901234', 'GIBAATWWXXX', 'exact_address', 95, 'matched', '2026-02-12 09:30:00', '2026-02-12 09:35:00'),
(1, '2026-03-15', '2026-03-17', 'Max Mustermann', 'Akontozahlung 03/2026', 60.00, 'EUR', 'AT123456789012345678901234', 'GIBAATWWXXX', 'exact_address', 95, 'matched', '2026-03-17 10:15:00', '2026-03-17 10:20:00'),
(1, '2026-04-12', '2026-04-14', 'Max Mustermann', 'Akontozahlung 04/2026', 60.00, 'EUR', 'AT123456789012345678901234', 'GIBAATWWXXX', 'exact_address', 95, 'matched', '2026-04-14 11:00:00', '2026-04-14 11:05:00'),
(1, '2026-05-20', '2026-05-22', 'Max Mustermann', 'Akontozahlung 05/2026', 60.00, 'EUR', 'AT123456789012345678901234', 'GIBAATWWXXX', 'exact_address', 95, 'matched', '2026-05-22 13:45:00', '2026-05-22 13:50:00'),
(1, '2026-07-01', '2026-07-03', 'Unbekannter Partner', 'Zahlung', 30.00, 'EUR', NULL, NULL, 'pending', 0, 'pending', '2026-07-03 08:20:00', NULL),

-- ZAHLUNGEN für member_id 2 (Haus 2)
(2, '2026-02-08', '2026-02-10', 'Erika Muster', 'Akontozahlung 02/2026', 60.00, 'EUR', 'AT234567890123456789012345', 'GIBAATWWXXX', 'exact_address', 95, 'matched', '2026-02-10 09:00:00', '2026-02-10 09:05:00'),
(2, '2026-03-12', '2026-03-14', 'Erika Muster', 'Akontozahlung 03/2026', 60.00, 'EUR', 'AT234567890123456789012345', 'GIBAATWWXXX', 'exact_address', 95, 'matched', '2026-03-14 10:30:00', '2026-03-14 10:35:00'),
(2, '2026-04-10', '2026-04-12', 'Erika Muster', 'Akontozahlung 04/2026', 60.00, 'EUR', 'AT234567890123456789012345', 'GIBAATWWXXX', 'exact_address', 95, 'matched', '2026-04-12 11:15:00', '2026-04-12 11:20:00'),
(2, '2026-05-18', '2026-05-20', 'Erika Muster', 'Akontozahlung 05/2026', 60.00, 'EUR', 'AT234567890123456789012345', 'GIBAATWWXXX', 'exact_address', 95, 'matched', '2026-05-20 14:00:00', '2026-05-20 14:05:00'),
(2, '2026-06-25', '2026-06-27', 'Erika Muster', 'Akontozahlung 06/2026', 60.00, 'EUR', 'AT234567890123456789012345', 'GIBAATWWXXX', 'exact_address', 95, 'matched', '2026-06-27 15:30:00', '2026-06-27 15:35:00'),

-- ZAHLUNGEN für member_id 3 (Haus 3)
(3, '2026-02-18', '2026-02-20', 'Hans Schmidt', 'Akontozahlung 02/2026', 60.00, 'EUR', 'AT345678901234567890123456', 'GIBAATWWXXX', 'exact_address', 95, 'matched', '2026-02-20 10:45:00', '2026-02-20 10:50:00'),
(3, '2026-03-22', '2026-03-24', 'Hans Schmidt', 'Akontozahlung 03/2026', 60.00, 'EUR', 'AT345678901234567890123456', 'GIBAATWWXXX', 'exact_address', 95, 'matched', '2026-03-24 11:20:00', '2026-03-24 11:25:00'),
(3, '2026-04-18', '2026-04-20', 'Hans Schmidt', 'Akontozahlung 04/2026', 60.00, 'EUR', 'AT345678901234567890123456', 'GIBAATWWXXX', 'exact_address', 95, 'matched', '2026-04-20 12:00:00', '2026-04-20 12:05:00');

-- KONFIGURATION (Dummy-Creditor-ID, damit Mandats-PDFs in der Testumgebung erstellt werden können)
INSERT INTO oc_weinsteig_config (config_key, config_value, updated_at) VALUES
('creditor_id', 'AT00ZZZ00000000000', '2026-08-16 10:00:00');

-- Ausgabe
SELECT '✓ Test-Daten eingefügt!' as 'Status';
SELECT COUNT(*) as 'Vorschreibungen gesamt' FROM oc_weinsteig_vorschreibungen;
SELECT COUNT(*) as 'Zahlungen gesamt' FROM oc_weinsteig_zahlungen;
