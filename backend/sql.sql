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
-- 3. A FELTÖLTÖTT SÜTEMÉNYEK FELTÖLTÉSE
-- ============================================================
-- Az árak kiinduló árak, a képek az Uploads mappából származnak.
-- A termekek.php a Backend/ előtagot automatikusan hozzáadja.

INSERT INTO termekek (nev, kategoria, ar, image_url) VALUES
('Áfonyás oroszkrém', 'Szeletes sütemények', 900, 'Uploads/afonyas_oroszkrem.JPEG'),
('Áfonyás túrórudi kocka', 'Szeletes sütemények', 900, 'Uploads/afonyas_turorudi_kocka.JPEG'),
('Alkalmi torta 1', 'Alkalmi Torták', 14000, 'Uploads/alkalmi_torta_1.JPEG'),
('Alkalmi torta 10', 'Alkalmi Torták', 14000, 'Uploads/alkalmi_torta_10.JPEG'),
('Alkalmi torta 2', 'Alkalmi Torták', 14000, 'Uploads/alkalmi_torta_2.jpg'),
('Alkalmi torta 3', 'Alkalmi Torták', 14000, 'Uploads/alkalmi_torta_3.JPEG'),
('Alkalmi torta 4', 'Alkalmi Torták', 14000, 'Uploads/alkalmi_torta_4.JPEG'),
('Alkalmi torta 5', 'Alkalmi Torták', 14000, 'Uploads/alkalmi_torta_5.JPEG'),
('Alkalmi torta 6', 'Alkalmi Torták', 14000, 'Uploads/alkalmi_torta_6.JPEG'),
('Alkalmi torta 7', 'Alkalmi Torták', 14000, 'Uploads/alkalmi_torta_7.JPG'),
('Alkalmi torta 9', 'Alkalmi Torták', 14000, 'Uploads/alkalmi_torta_9.JPEG'),
('Angol puncs', 'Szeletes sütemények', 900, 'Uploads/angolpuncs.JPEG'),
('Baklava', 'Aprósütemények', 400, 'Uploads/baklava.JPEG'),
('Barackos oroszkrém', 'Szeletes sütemények', 850, 'Uploads/barackos_oroszkrem.jpeg'),
('Barátfüle', 'Aprósütemények', 1100, 'Uploads/baratfule.JPG'),
('Bíborfehér', 'Szeletes sütemények', 950, 'Uploads/biborfeher.JPEG'),
('Bosnyák szilvás', 'Szeletes sütemények', 900, 'Uploads/bosnyak_szilvas.JPEG'),
('Bounty tortaszelet', 'Szeletes sütemények', 900, 'Uploads/bounty_tortaszelet.JPEG'),
('Citromos keksz', 'Aprósütemények', 900, 'Uploads/citromos_keksz.JPEG'),
('Citromos máktorta', 'Alkalmi Torták', 12000, 'Uploads/citromos_maktorta.JPEG'),
('Citromos sajttorta pohárkrém', 'Szeletes sütemények', 1100, 'Uploads/citromos_sajttorta_poharkrem.JPEG'),
('Csokikrémes diócska', 'Aprósütemények', 200, 'Uploads/csokikremes_diocska.JPEG'),
('Csokis málnás taller', 'Aprósütemények', 300, 'Uploads/csokis_malnas_taller.JPEG'),
('Csokis omlett', 'Szeletes sütemények', 950, 'Uploads/csokis_omlett.JPEG'),
('Dominó szelet', 'Szeletes sütemények', 500, 'Uploads/domino_szelet.JPEG'),
('Epres Oreo kocka', 'Szeletes sütemények', 700, 'Uploads/epres_oreo_kocka.JPEG'),
('Epres oroszkrém', 'Szeletes sütemények', 900, 'Uploads/epres_oroszkrem.JPEG'),
('Erdei gyümölcs torta', 'Alkalmi Torták', 12500, 'Uploads/erdeigyumolcs_torta.JPEG'),
('Esküvői torta 1', 'Alkalmi Torták', 30000, 'Uploads/eskuvoi_torta_1.JPEG'),
('Esküvői torta 2', 'Alkalmi Torták', 30000, 'Uploads/eskuvoi_torta_2.JPEG'),
('Eszterházy torta 1', 'Alkalmi Torták', 900, 'Uploads/eszterhazy_torta_1.JPEG'),
('Eszterházy torta 2', 'Alkalmi Torták', 900, 'Uploads/eszterhazy_torta_2.JPEG'),
('Farsangi fánk', 'Aprósütemények', 500, 'Uploads/farsangi_fank.JPG'),
('Flódni', 'Szeletes sütemények', 800, 'Uploads/flodni.JPEG'),
('Fonott kalács', 'Aprósütemények', 1800, 'Uploads/fonott_kalacs.JPEG'),
('Főzött krémes citrom torta', 'Alkalmi Torták', 13000, 'Uploads/fozottkremes_citrom_torta.JPEG'),
('Főzött krémes tortaszeletek', 'Szeletes sütemények', 850, 'Uploads/fozottkremes_tortaszeletek.JPEG'),
('Főzött krémes vanília torta', 'Alkalmi Torták', 13000, 'Uploads/fozott_kremes_vanilia_torta.JPEG'),
('Franciakrémes', 'Szeletes sütemények', 600, 'Uploads/franciakremes.JPEG'),
('Gesztenyepüré', 'Szeletes sütemények', 700, 'Uploads/gesztenyepure.JPEG'),
('Gesztenyés fehércsokis', 'Szeletes sütemények', 1000, 'Uploads/gesztenyes_fehercsokis.JPEG'),
('Gesztenyés fehércsokis szelet', 'Szeletes sütemények', 900, 'Uploads/gesztenyes_fehercsokis_szelet.JPEG'),
('Gesztenye Gusztáv', 'Szeletes sütemények', 800, 'Uploads/gesztenye_guszti.JPEG'),
('Grillázsmalac', 'Aprósütemények', 2500, 'Uploads/grillazsmalac.JPG'),
('Gyümölcskocka', 'Szeletes sütemények', 900, 'Uploads/gyumolcskocka.JPEG'),
('Gyümölcsomlett', 'Szeletes sütemények', 900, 'Uploads/gyumolcsomlett.JPEG'),
('Gyümölcsomlett torta', 'Alkalmi Torták', 13000, 'Uploads/gyumolcsomlett_torta.JPEG'),
('Habos isler', 'Aprósütemények', 400, 'Uploads/habos_isler.JPEG'),
('Habos mákos', 'Szeletes sütemények', 700, 'Uploads/habos_makos.JPEG'),
('Habroló', 'Aprósütemények', 700, 'Uploads/habrolo.JPEG'),
('Hagyományos franciakrémes', 'Szeletes sütemények', 600, 'Uploads/hagyfranciakremes.JPEG'),
('Hagyományos krémes', 'Szeletes sütemények', 500, 'Uploads/hagyomanyoskremes.JPEG'),
('Házi krémes', 'Szeletes sütemények', 850, 'Uploads/hazi_kremes.JPEG'),
('Horvát almás', 'Szeletes sütemények', 900, 'Uploads/horvat_almas.JPEG'),
('Isler torta', 'Alkalmi Torták', 600, 'Uploads/isler_torta.JPEG'),
('Japán torta', 'Alkalmi Torták', 900, 'Uploads/japan_torta.JPEG'),
('Jojo fánk', 'Aprósütemények', 500, 'Uploads/jojo_fank.JPEG'),
('Jókai torta', 'Alkalmi Torták', 13000, 'Uploads/Jokai_torta.JPEG'),
('Jókai tortaszelet', 'Szeletes sütemények', 900, 'Uploads/Jokai_tortaszelet.JPEG'),
('Kardinális', 'Szeletes sütemények', 900, 'Uploads/kardinalis.JPEG'),
('Keksztekercs', 'Szeletes sütemények', 700, 'Uploads/keksztekercs.JPEG'),
('Kinder Pingvin szelet', 'Szeletes sütemények', 750, 'Uploads/kinder_pingvin_szelet.JPEG'),
('Kinder torta', 'Alkalmi Torták', 900, 'Uploads/kinder_torta.jpeg'),
('Kréta-i fahéjas', 'Aprósütemények', 900, 'Uploads/kretai_fahejas.JPEG'),
('Krokant szelet', 'Szeletes sütemények', 850, 'Uploads/krokant_szelet.JPEG'),
('Linzer kifli', 'Aprósütemények', 500, 'Uploads/linzer_kifli.JPEG'),
('Lúdláb torta', 'Alkalmi Torták', 900, 'Uploads/ludlab_torta.JPEG'),
('Macaron', 'Aprósütemények', 500, 'Uploads/macaron.JPEG'),
('Macis muffin', 'Aprósütemények', 700, 'Uploads/macis_muffin.JPEG'),
('Mákos guba torta', 'Alkalmi Torták', 900, 'Uploads/makosguba_torta.JPEG'),
('Málnás joghurt kocka', 'Szeletes sütemények', 900, 'Uploads/malnas_joghurt_kocka.JPEG'),
('Mandulás fehércsokis tortaszelet', 'Szeletes sütemények', 900, 'Uploads/mandulas_fehercsokis_tortaszelet.JPEG'),
('Mangós szelet', 'Szeletes sütemények', 950, 'Uploads/mangos_szelet.JPEG'),
('Meggyes sarok', 'Szeletes sütemények', 500, 'Uploads/meggyes_sarok.JPEG'),
('Mignonok', 'Aprósütemények', 700, 'Uploads/mignonok.JPEG'),
('Mini csoki torták', 'Alkalmi Torták', 3500, 'Uploads/mini_csoki_tortak.JPEG'),
('Mini csoki torták 2', 'Alkalmi Torták', 3500, 'Uploads/mini_csoki_tortak_2.JPEG'),
('Nagy linzer', 'Aprósütemények', 900, 'Uploads/nagy_linzer.JPEG'),
('Nagy pogácsa', 'Aprósütemények', 1200, 'Uploads/nagy_pogacsa.JPEG'),
('Olasz hajdinatorta', 'Alkalmi Torták', 14000, 'Uploads/olasz_hajdinaltorta.JPEG'),
('Opera szelet', 'Szeletes sütemények', 1000, 'Uploads/opera_szelet.JPEG'),
('Oroszkrém torta', 'Alkalmi Torták', 14000, 'Uploads/oroszkrem_torta.JPEG'),
('Pepita szelet', 'Szeletes sütemények', 900, 'Uploads/pepita_szelet.JPEG'),
('Pilótakeksz', 'Aprósütemények', 900, 'Uploads/pilotakeksz.JPEG'),
('Pisztáciás Nutellás sajttorta', 'Alkalmi Torták', 850, 'Uploads/pisztacias_nutellas_sajttorta.JPEG'),
('Pisztáciás sajttorta', 'Alkalmi Torták', 14500, 'Uploads/pisztacias_sajttorta.JPEG'),
('Pogácsa', 'Aprósütemények', 1200, 'Uploads/pogacsa.JPEG'),
('Profiterol', 'Szeletes sütemények', 1100, 'Uploads/profiterol.JPEG'),
('Puncstorta', 'Alkalmi Torták', 900, 'Uploads/puncstorta.JPEG'),
('Rákóczi túrós', 'Szeletes sütemények', 900, 'Uploads/rakoczi_turos.JPEG'),
('Rákóczi túrós pohárkrém', 'Szeletes sütemények', 1100, 'Uploads/rakoczi_turos_poharkrem.JPEG'),
('Rama kockák', 'Szeletes sütemények', 900, 'Uploads/ramakockak.jPEG'),
('Red velvet tortaszelet', 'Szeletes sütemények', 1000, 'Uploads/red_velvet_tortaszelet.JPEG'),
('Sajtrúd', 'Aprósütemények', 1000, 'Uploads/sajtrolo.JPEG'),
('Sajtrúd torta', 'Alkalmi Torták', 12000, 'Uploads/sajtrolo_torta.JPEG'),
('Sanghai szelet', 'Szeletes sütemények', 900, 'Uploads/sanghai_szelet.jpg'),
('Somlói', 'Szeletes sütemények', 900, 'Uploads/somloi.JPEG'),
('Spanyol vaníliatorta', 'Alkalmi Torták', 14000, 'Uploads/spanyol_vaniliatorta.JPEG'),
('Sült túrótortaszelet', 'Szeletes sütemények', 1000, 'Uploads/sultturo_tortaszelet.JPEG'),
('Szilvafácska', 'Aprósütemények', 900, 'Uploads/szilvafacska.JPEG'),
('Tejszínes gesztenyés torta', 'Alkalmi Torták', 14000, 'Uploads/tejszines_gesztenyes_torta.JPEG'),
('Tejszínes túrótorta', 'Alkalmi Torták', 14000, 'Uploads/tejszines_turo_torta.JPEG'),
('Trüffel torta', 'Alkalmi Torták', 15000, 'Uploads/truffel_torta.JPEG'),
('Túrótorta', 'Alkalmi Torták', 13000, 'Uploads/turotorta.JPEG'),
('Valentin mini torta', 'Alkalmi Torták', 5000, 'Uploads/Valentin mini torta.JPEG'),
('Valentin aprósütemények', 'Aprósütemények', 900, 'Uploads/valentin_aprosutik.JPEG');
