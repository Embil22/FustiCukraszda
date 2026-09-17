-- sql.sql
-- Füsti Cukrászda adatbázis séma és minta adatok
-- Futtatás: phpMyAdmin Import, vagy: mysql -u root -p < sql.sql

-- ============================================================
-- 1. ADATBÁZIS LÉTREHOZÁSA
-- ============================================================
CREATE DATABASE IF NOT EXISTS fusti_cukraszda
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_hungarian_ci;

USE fusti_cukraszda;

-- ============================================================
-- 2. TÁBLA LÉTREHOZÁSA
-- ============================================================
DROP TABLE IF EXISTS termekek;

CREATE TABLE termekek (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nev VARCHAR(150) NOT NULL,
  kategoria ENUM(
    'Szeletes sütemények',
    'Alkalmi Torták',
    'Aprósütemények',
    'Fagylalt'
  ) NOT NULL,
  ar INT NOT NULL,
  image_url VARCHAR(255) NOT NULL,
  letrehozva TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_kategoria (kategoria)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_hungarian_ci;

-- ============================================================
-- 3. MINTA ADATOK FELTÖLTÉSE
-- ============================================================
-- FONTOS: az image_url mezőben csak a relatív útvonal van
-- (uploads/xyz.jpg). A termekek.php automatikusan elé fűzi
-- a "Frontend/public/" előtagot.

INSERT INTO termekek (nev, kategoria, ar, image_url) VALUES

-- ------------------------------------------------------------
-- SZELETES SÜTEMÉNYEK
-- ------------------------------------------------------------
('Dobos torta szelet',          'Szeletes sütemények',  850,   'uploads/dobos_szelet.jpg'),
('Somlói galuska',              'Szeletes sütemények',  900,   'uploads/somloi.jpg'),
('Sacher szelet',               'Szeletes sütemények',  950,   'uploads/sacher.jpg'),
('Eszterházy szelet',           'Szeletes sütemények',  850,   'uploads/eszterhazy.jpg'),
('Krémes',                      'Szeletes sütemények',  700,   'uploads/kremes.jpg'),
('Rákóczi túrós',               'Szeletes sütemények',  800,   'uploads/rakoczi_turos.jpg'),
('Mákos guba szelet',           'Szeletes sütemények',  750,   'uploads/makos_guba.jpg'),

-- ------------------------------------------------------------
-- ALKALMI TORTÁK
-- ------------------------------------------------------------
('Bajor krém torta (7 szelet)', 'Alkalmi Torták',       6000,  'uploads/bajor.jpg'),
('Zsúr torta (10 szelet)',      'Alkalmi Torták',       9000,  'uploads/zsur.jpg'),
('Normál torta (16 szelet)',    'Alkalmi Torták',       13600, 'uploads/normal.jpg'),
('Malom torta (24 szelet)',     'Alkalmi Torták',       20400, 'uploads/malom.jpg'),
('Tortadoboz',                  'Alkalmi Torták',       500,   'uploads/tortadoboz.jpg'),
('Esküvői torta (3 emeletes)',  'Alkalmi Torták',       45000, 'uploads/eskuvoi_torta.jpg'),
('Gyerektorta (színes)',        'Alkalmi Torták',       12000, 'uploads/gyerektorta.jpg'),

-- ------------------------------------------------------------
-- APRÓSÜTEMÉNYEK
-- ------------------------------------------------------------
('Linzer aprósütemény',         'Aprósütemények',       1200,  'uploads/linzer.jpg'),
('Isler',                       'Aprósütemények',       1400,  'uploads/isler.jpg'),
('Puszedli',                    'Aprósütemények',       1100,  'uploads/puszedli.jpg'),
('Mézeskalács',                 'Aprósütemények',       900,   'uploads/mezeskalacs.jpg'),
('Kókuszgolyó',                 'Aprósütemények',       1000,  'uploads/kokuszgolyo.jpg'),
('Hókifli',                     'Aprósütemények',       950,   'uploads/hokifli.jpg'),
('Diós csók',                   'Aprósütemények',       1050,  'uploads/dios_csok.jpg'),

-- ------------------------------------------------------------
-- FAGYLALT
-- ------------------------------------------------------------
('Csokoládé gombóc',            'Fagylalt',             550,   'uploads/csoki_fagyi.jpg'),
('Vanília gombóc',              'Fagylalt',             550,   'uploads/vanilia_fagyi.jpg'),
('Pisztácia gombóc',            'Fagylalt',             600,   'uploads/pisztacia_fagyi.jpg'),
('Eper gombóc',                 'Fagylalt',             550,   'uploads/eper_fagyi.jpg'),
('Málna gombóc',                'Fagylalt',             550,   'uploads/malna_fagyi.jpg'),
('Citrom gombóc',               'Fagylalt',             550,   'uploads/citrom_fagyi.jpg'),
('Édes tölcsér',                'Fagylalt',             100,   'uploads/tolcser.jpg'),
('Ehető díszítés',              'Fagylalt',             50,    'uploads/edible_decor.jpg');

-- ============================================================
-- 4. ELLENŐRZÉS
-- ============================================================
-- SELECT kategoria, COUNT(*) AS darab
-- FROM termekek
-- GROUP BY kategoria;

-- SELECT * FROM termekek ORDER BY kategoria, nev;