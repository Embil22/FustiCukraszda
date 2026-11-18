-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 18, 2026 at 10:27 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fusti_cukraszda`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int NOT NULL,
  `username` varchar(64) COLLATE utf8mb4_hungarian_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_hungarian_ci NOT NULL,
  `can_login` tinyint(1) NOT NULL DEFAULT '1',
  `must_change_password` tinyint(1) NOT NULL DEFAULT '0',
  `failed_login_attempts` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `locked_until` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password_hash`, `can_login`, `must_change_password`, `failed_login_attempts`, `locked_until`, `created_at`) VALUES
(1, 'admin', '$2y$10$bwsRr3TrdDmSRvn/s/BJuu82xsLkoOSavCnOE9kcBqkp...nrnaIa', 1, 1, 0, NULL, '2026-11-18 19:30:41'),
(16, 'fustikft', '$2y$10$GYdGcmtFIpgHlu8/1qhXluVFsA8OGaMq.srxRHdNuXzficXjbuTSS', 1, 0, 0, NULL, '2026-11-18 22:05:30');

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `setting_key` varchar(64) COLLATE utf8mb4_hungarian_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_hungarian_ci NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('offer_enabled', '1', '2026-11-18 21:27:16'),
('offer_image', 'Uploads/48a4dfbb4b6d5267ea616a1b6743c535.jpg', '2026-11-18 21:26:57'),
('offer_text', 'Szeretem a tortát, mert a torta az torta és édes', '2026-11-18 21:26:38');

-- --------------------------------------------------------

--
-- Table structure for table `termekek`
--

CREATE TABLE `termekek` (
  `id` int NOT NULL,
  `nev` varchar(150) COLLATE utf8mb4_hungarian_ci NOT NULL,
  `kategoria` enum('Szeletes sütemények','Alkalmi Torták','Aprósütemények','Fagylalt','Sütifree','Tejszínes torták','Főzött krémes torták','Különleges torták','Kreatív díszítésű torták','Pohárkrém') COLLATE utf8mb4_hungarian_ci NOT NULL,
  `ar` int NOT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_hungarian_ci NOT NULL,
  `letrehozva` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

--
-- Dumping data for table `termekek`
--

INSERT INTO `termekek` (`id`, `nev`, `kategoria`, `ar`, `image_url`, `letrehozva`) VALUES
(1, 'Áfonyás oroszkrém', 'Szeletes sütemények', 900, 'Uploads/afonyas_oroszkrem.JPEG', '2026-11-18 18:02:05'),
(2, 'Áfonyás túrórudi kocka', 'Szeletes sütemények', 900, 'Uploads/afonyas_turorudi_kocka.JPEG', '2026-11-18 18:02:05'),
(3, 'Alkalmi torta 1', 'Alkalmi Torták', 14000, 'Uploads/alkalmi_torta_1.JPEG', '2026-11-18 18:02:05'),
(4, 'Alkalmi torta 10', 'Alkalmi Torták', 14000, 'Uploads/alkalmi_torta_10.JPEG', '2026-11-18 18:02:05'),
(5, 'Alkalmi torta 2', 'Alkalmi Torták', 14000, 'Uploads/alkalmi_torta_2.jpg', '2026-11-18 18:02:05'),
(6, 'Alkalmi torta 3', 'Alkalmi Torták', 14000, 'Uploads/alkalmi_torta_3.JPEG', '2026-11-18 18:02:05'),
(7, 'Alkalmi torta 4', 'Alkalmi Torták', 14000, 'Uploads/alkalmi_torta_4.JPEG', '2026-11-18 18:02:05'),
(8, 'Alkalmi torta 5', 'Alkalmi Torták', 14000, 'Uploads/alkalmi_torta_5.JPEG', '2026-11-18 18:02:05'),
(9, 'Alkalmi torta 6', 'Alkalmi Torták', 14000, 'Uploads/alkalmi_torta_6.JPEG', '2026-11-18 18:02:05'),
(10, 'Alkalmi torta 7', 'Alkalmi Torták', 14000, 'Uploads/alkalmi_torta_7.JPG', '2026-11-18 18:02:05'),
(11, 'Alkalmi torta 9', 'Alkalmi Torták', 14000, 'Uploads/alkalmi_torta_9.JPEG', '2026-11-18 18:02:05'),
(12, 'Angol puncs', 'Szeletes sütemények', 900, 'Uploads/angolpuncs.JPEG', '2026-11-18 18:02:05'),
(13, 'Baklava', 'Aprósütemények', 400, 'Uploads/baklava.JPEG', '2026-11-18 18:02:05'),
(14, 'Barackos oroszkrém', 'Szeletes sütemények', 850, 'Uploads/barackos_oroszkrem.jpeg', '2026-11-18 18:02:05'),
(15, 'Barátfüle', 'Aprósütemények', 1100, 'Uploads/baratfule.JPG', '2026-11-18 18:02:05'),
(16, 'Bíborfehér', 'Szeletes sütemények', 950, 'Uploads/biborfeher.JPEG', '2026-11-18 18:02:05'),
(17, 'Bosnyák szilvás', 'Szeletes sütemények', 900, 'Uploads/bosnyak_szilvas.JPEG', '2026-11-18 18:02:05'),
(18, 'Bounty tortaszelet', 'Szeletes sütemények', 900, 'Uploads/bounty_tortaszelet.JPEG', '2026-11-18 18:02:05'),
(19, 'Citromos keksz', 'Aprósütemények', 900, 'Uploads/citromos_keksz.JPEG', '2026-11-18 18:02:05'),
(20, 'Citromos máktorta', 'Alkalmi Torták', 12000, 'Uploads/citromos_maktorta.JPEG', '2026-11-18 18:02:05'),
(21, 'Citromos sajttorta pohárkrém', 'Szeletes sütemények', 1100, 'Uploads/citromos_sajttorta_poharkrem.JPEG', '2026-11-18 18:02:05'),
(22, 'Csokikrémes diócska', 'Aprósütemények', 200, 'Uploads/csokikremes_diocska.JPEG', '2026-11-18 18:02:05'),
(23, 'Csokis málnás taller', 'Aprósütemények', 300, 'Uploads/csokis_malnas_taller.JPEG', '2026-11-18 18:02:05'),
(24, 'Csokis omlett', 'Szeletes sütemények', 950, 'Uploads/csokis_omlett.JPEG', '2026-11-18 18:02:05'),
(25, 'Dominó szelet', 'Szeletes sütemények', 500, 'Uploads/domino_szelet.JPEG', '2026-11-18 18:02:05'),
(26, 'Epres Oreo kocka', 'Szeletes sütemények', 700, 'Uploads/epres_oreo_kocka.JPEG', '2026-11-18 18:02:05'),
(27, 'Epres oroszkrém', 'Szeletes sütemények', 900, 'Uploads/epres_oroszkrem.JPEG', '2026-11-18 18:02:05'),
(28, 'Erdei gyümölcs torta', 'Alkalmi Torták', 12500, 'Uploads/erdeigyumolcs_torta.JPEG', '2026-11-18 18:02:05'),
(29, 'Esküvői torta 1', 'Alkalmi Torták', 30000, 'Uploads/eskuvoi_torta_1.JPEG', '2026-11-18 18:02:05'),
(30, 'Esküvői torta 2', 'Alkalmi Torták', 30000, 'Uploads/eskuvoi_torta_2.JPEG', '2026-11-18 18:02:05'),
(31, 'Eszterházy torta 1', 'Alkalmi Torták', 900, 'Uploads/eszterhazy_torta_1.JPEG', '2026-11-18 18:02:05'),
(32, 'Eszterházy torta 2', 'Alkalmi Torták', 900, 'Uploads/eszterhazy_torta_2.JPEG', '2026-11-18 18:02:05'),
(33, 'Farsangi fánk', 'Aprósütemények', 500, 'Uploads/farsangi_fank.JPG', '2026-11-18 18:02:05'),
(34, 'Flódni', 'Szeletes sütemények', 800, 'Uploads/flodni.JPEG', '2026-11-18 18:02:05'),
(35, 'Fonott kalács', 'Aprósütemények', 1800, 'Uploads/fonott_kalacs.JPEG', '2026-11-18 18:02:05'),
(36, 'Főzött krémes citrom torta', 'Alkalmi Torták', 13000, 'Uploads/fozottkremes_citrom_torta.JPEG', '2026-11-18 18:02:05'),
(37, 'Főzött krémes tortaszeletek', 'Szeletes sütemények', 850, 'Uploads/fozottkremes_tortaszeletek.JPEG', '2026-11-18 18:02:05'),
(38, 'Főzött krémes vanília torta', 'Alkalmi Torták', 13000, 'Uploads/fozott_kremes_vanilia_torta.JPEG', '2026-11-18 18:02:05'),
(39, 'Franciakrémes', 'Szeletes sütemények', 600, 'Uploads/franciakremes.JPEG', '2026-11-18 18:02:05'),
(40, 'Gesztenyepüré', 'Szeletes sütemények', 700, 'Uploads/gesztenyepure.JPEG', '2026-11-18 18:02:05'),
(41, 'Gesztenyés fehércsokis', 'Szeletes sütemények', 1000, 'Uploads/gesztenyes_fehercsokis.JPEG', '2026-11-18 18:02:05'),
(42, 'Gesztenyés fehércsokis szelet', 'Szeletes sütemények', 900, 'Uploads/gesztenyes_fehercsokis_szelet.JPEG', '2026-11-18 18:02:05'),
(43, 'Gesztenye Gusztáv', 'Szeletes sütemények', 800, 'Uploads/gesztenye_guszti.JPEG', '2026-11-18 18:02:05'),
(44, 'Grillázsmalac', 'Aprósütemények', 2500, 'Uploads/grillazsmalac.JPG', '2026-11-18 18:02:05'),
(45, 'Gyümölcskocka', 'Szeletes sütemények', 900, 'Uploads/gyumolcskocka.JPEG', '2026-11-18 18:02:05'),
(46, 'Gyümölcsomlett', 'Szeletes sütemények', 900, 'Uploads/gyumolcsomlett.JPEG', '2026-11-18 18:02:05'),
(47, 'Gyümölcsomlett torta', 'Alkalmi Torták', 13000, 'Uploads/gyumolcsomlett_torta.JPEG', '2026-11-18 18:02:05'),
(48, 'Habos isler', 'Aprósütemények', 400, 'Uploads/habos_isler.JPEG', '2026-11-18 18:02:05'),
(49, 'Habos mákos', 'Szeletes sütemények', 700, 'Uploads/habos_makos.JPEG', '2026-11-18 18:02:05'),
(50, 'Habroló', 'Aprósütemények', 700, 'Uploads/habrolo.JPEG', '2026-11-18 18:02:05'),
(51, 'Hagyományos franciakrémes', 'Szeletes sütemények', 600, 'Uploads/hagyfranciakremes.JPEG', '2026-11-18 18:02:05'),
(52, 'Hagyományos krémes', 'Szeletes sütemények', 500, 'Uploads/hagyomanyoskremes.JPEG', '2026-11-18 18:02:05'),
(53, 'Házi krémes', 'Szeletes sütemények', 850, 'Uploads/hazi_kremes.JPEG', '2026-11-18 18:02:05'),
(54, 'Horvát almás', 'Szeletes sütemények', 900, 'Uploads/horvat_almas.JPEG', '2026-11-18 18:02:05'),
(55, 'Isler torta', 'Alkalmi Torták', 600, 'Uploads/isler_torta.JPEG', '2026-11-18 18:02:05'),
(56, 'Japán torta', 'Alkalmi Torták', 900, 'Uploads/japan_torta.JPEG', '2026-11-18 18:02:05'),
(57, 'Jojo fánk', 'Aprósütemények', 500, 'Uploads/jojo_fank.JPEG', '2026-11-18 18:02:05'),
(58, 'Jókai torta', 'Alkalmi Torták', 13000, 'Uploads/Jokai_torta.JPEG', '2026-11-18 18:02:05'),
(59, 'Jókai tortaszelet', 'Szeletes sütemények', 900, 'Uploads/Jokai_tortaszelet.JPEG', '2026-11-18 18:02:05'),
(60, 'Kardinális', 'Szeletes sütemények', 900, 'Uploads/kardinalis.JPEG', '2026-11-18 18:02:05'),
(61, 'Keksztekercs', 'Szeletes sütemények', 700, 'Uploads/keksztekercs.JPEG', '2026-11-18 18:02:05'),
(62, 'Kinder Pingvin szelet', 'Szeletes sütemények', 750, 'Uploads/kinder_pingvin_szelet.JPEG', '2026-11-18 18:02:05'),
(63, 'Kinder torta', 'Alkalmi Torták', 900, 'Uploads/kinder_torta.jpeg', '2026-11-18 18:02:05'),
(64, 'Kréta-i fahéjas', 'Aprósütemények', 900, 'Uploads/kretai_fahejas.JPEG', '2026-11-18 18:02:05'),
(65, 'Krokant szelet', 'Szeletes sütemények', 850, 'Uploads/krokant_szelet.JPEG', '2026-11-18 18:02:05'),
(66, 'Linzer kifli', 'Aprósütemények', 500, 'Uploads/linzer_kifli.JPEG', '2026-11-18 18:02:05'),
(67, 'Lúdláb torta', 'Alkalmi Torták', 900, 'Uploads/ludlab_torta.JPEG', '2026-11-18 18:02:05'),
(68, 'Macaron', 'Aprósütemények', 500, 'Uploads/macaron.JPEG', '2026-11-18 18:02:05'),
(69, 'Macis muffin', 'Aprósütemények', 700, 'Uploads/macis_muffin.JPEG', '2026-11-18 18:02:05'),
(70, 'Mákos guba torta', 'Alkalmi Torták', 900, 'Uploads/makosguba_torta.JPEG', '2026-11-18 18:02:05'),
(71, 'Málnás joghurt kocka', 'Szeletes sütemények', 900, 'Uploads/malnas_joghurt_kocka.JPEG', '2026-11-18 18:02:05'),
(72, 'Mandulás fehércsokis tortaszelet', 'Szeletes sütemények', 900, 'Uploads/mandulas_fehercsokis_tortaszelet.JPEG', '2026-11-18 18:02:05'),
(73, 'Mangós szelet', 'Szeletes sütemények', 950, 'Uploads/mangos_szelet.JPEG', '2026-11-18 18:02:05'),
(74, 'Meggyes sarok', 'Szeletes sütemények', 500, 'Uploads/meggyes_sarok.JPEG', '2026-11-18 18:02:05'),
(75, 'Mignonok', 'Aprósütemények', 700, 'Uploads/mignonok.JPEG', '2026-11-18 18:02:05'),
(76, 'Mini csoki torták', 'Alkalmi Torták', 3500, 'Uploads/mini_csoki_tortak.JPEG', '2026-11-18 18:02:05'),
(77, 'Mini csoki torták 2', 'Alkalmi Torták', 3500, 'Uploads/mini_csoki_tortak_2.JPEG', '2026-11-18 18:02:05'),
(78, 'Nagy linzer', 'Aprósütemények', 900, 'Uploads/nagy_linzer.JPEG', '2026-11-18 18:02:05'),
(79, 'Nagy pogácsa', 'Aprósütemények', 1200, 'Uploads/nagy_pogacsa.JPEG', '2026-11-18 18:02:05'),
(80, 'Olasz hajdinatorta', 'Alkalmi Torták', 14000, 'Uploads/olasz_hajdinaltorta.JPEG', '2026-11-18 18:02:05'),
(81, 'Opera szelet', 'Szeletes sütemények', 1000, 'Uploads/opera_szelet.JPEG', '2026-11-18 18:02:05'),
(82, 'Oroszkrém torta', 'Alkalmi Torták', 14000, 'Uploads/oroszkrem_torta.JPEG', '2026-11-18 18:02:05'),
(83, 'Pepita szelet', 'Szeletes sütemények', 900, 'Uploads/pepita_szelet.JPEG', '2026-11-18 18:02:05'),
(84, 'Pilótakeksz', 'Aprósütemények', 900, 'Uploads/pilotakeksz.JPEG', '2026-11-18 18:02:05'),
(85, 'Pisztáciás Nutellás sajttorta', 'Alkalmi Torták', 850, 'Uploads/pisztacias_nutellas_sajttorta.JPEG', '2026-11-18 18:02:05'),
(86, 'Pisztáciás sajttorta', 'Alkalmi Torták', 14500, 'Uploads/pisztacias_sajttorta.JPEG', '2026-11-18 18:02:05'),
(87, 'Pogácsa', 'Aprósütemények', 1200, 'Uploads/pogacsa.JPEG', '2026-11-18 18:02:05'),
(88, 'Profiterol', 'Szeletes sütemények', 1100, 'Uploads/profiterol.JPEG', '2026-11-18 18:02:05'),
(89, 'Puncstorta', 'Alkalmi Torták', 900, 'Uploads/puncstorta.JPEG', '2026-11-18 18:02:05'),
(90, 'Rákóczi túrós', 'Szeletes sütemények', 900, 'Uploads/rakoczi_turos.JPEG', '2026-11-18 18:02:05'),
(91, 'Rákóczi túrós pohárkrém', 'Szeletes sütemények', 900, 'Uploads/rakoczi_turos_poharkrem.JPEG', '2026-11-18 18:02:05'),
(92, 'Rama kockák', 'Szeletes sütemények', 900, 'Uploads/ramakockak.jPEG', '2026-11-18 18:02:05'),
(93, 'Red velvet tortaszelet', 'Szeletes sütemények', 1000, 'Uploads/red_velvet_tortaszelet.JPEG', '2026-11-18 18:02:05'),
(94, 'Sajtrúd', 'Aprósütemények', 1000, 'Uploads/sajtrolo.JPEG', '2026-11-18 18:02:05'),
(95, 'Sajtrúd torta', 'Alkalmi Torták', 12000, 'Uploads/sajtrolo_torta.JPEG', '2026-11-18 18:02:05'),
(96, 'Sanghai szelet', 'Szeletes sütemények', 900, 'Uploads/sanghai_szelet.jpg', '2026-11-18 18:02:05'),
(97, 'Somlói', 'Szeletes sütemények', 900, 'Uploads/somloi.JPEG', '2026-11-18 18:02:05'),
(98, 'Spanyol vaníliatorta', 'Alkalmi Torták', 14000, 'Uploads/spanyol_vaniliatorta.JPEG', '2026-11-18 18:02:05'),
(99, 'Sült túrótortaszelet', 'Szeletes sütemények', 1000, 'Uploads/sultturo_tortaszelet.JPEG', '2026-11-18 18:02:05'),
(100, 'Szilvafácska', 'Aprósütemények', 900, 'Uploads/szilvafacska.JPEG', '2026-11-18 18:02:05'),
(101, 'Tejszínes gesztenyés torta', 'Alkalmi Torták', 14000, 'Uploads/tejszines_gesztenyes_torta.JPEG', '2026-11-18 18:02:05'),
(102, 'Tejszínes túrótorta', 'Alkalmi Torták', 14000, 'Uploads/tejszines_turo_torta.JPEG', '2026-11-18 18:02:05'),
(103, 'Trüffel torta', 'Alkalmi Torták', 15000, 'Uploads/truffel_torta.JPEG', '2026-11-18 18:02:05'),
(104, 'Túrótorta', 'Alkalmi Torták', 13000, 'Uploads/turotorta.JPEG', '2026-11-18 18:02:05'),
(105, 'Valentin mini torta', 'Alkalmi Torták', 5000, 'Uploads/Valentin mini torta.JPEG', '2026-11-18 18:02:05'),
(106, 'Valentin aprósütemények', 'Aprósütemények', 900, 'Uploads/valentin_aprosutik.JPEG', '2026-11-18 18:02:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `termekek`
--
ALTER TABLE `termekek`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kategoria` (`kategoria`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `termekek`
--
ALTER TABLE `termekek`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
