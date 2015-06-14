-- phpMyAdmin SQL Dump
-- version 4.1.12
-- http://www.phpmyadmin.net
--
-- Počítač: 127.0.0.1
-- Vytvořeno: Ned 14. čen 2015, 19:02
-- Verze serveru: 5.6.16
-- Verze PHP: 5.5.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Databáze: `wot`
--

-- --------------------------------------------------------

--
-- Struktura tabulky `wot_equipment`
--

CREATE TABLE IF NOT EXISTS `wot_equipment` (
  `wot_equipment_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wot_version_id` int(10) unsigned NOT NULL,
  `name` varchar(128) NOT NULL,
  `name_node` varchar(128) NOT NULL,
  `description` tinytext NOT NULL,
  `icon` varchar(255) NOT NULL,
  `price` int(10) unsigned NOT NULL,
  `price_gold` int(10) unsigned DEFAULT NULL,
  `removable` tinyint(1) unsigned NOT NULL,
  `weight` int(10) unsigned DEFAULT NULL,
  `vehicle_tags_include` tinytext NOT NULL,
  `vehicle_tags_exclude` tinytext NOT NULL,
  PRIMARY KEY (`wot_equipment_id`),
  KEY `wot_version_id` (`wot_version_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=130 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `wot_equipment_params`
--

CREATE TABLE IF NOT EXISTS `wot_equipment_params` (
  `wot_equipment_params_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wot_equipment_id` int(10) unsigned NOT NULL,
  `param` varchar(128) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`wot_equipment_params_id`),
  KEY `wot_equipment_id` (`wot_equipment_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=290 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `wot_items_chassis`
--

CREATE TABLE IF NOT EXISTS `wot_items_chassis` (
  `wot_items_chassis_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `wot_tanks_id` int(11) unsigned DEFAULT NULL,
  `wot_version_id` int(10) unsigned DEFAULT NULL,
  `id` int(11) DEFAULT NULL,
  `name_node` varchar(255) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `level` int(11) DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  `weight` float DEFAULT NULL,
  `health` int(11) DEFAULT NULL,
  `health_regen` int(11) DEFAULT NULL,
  `repair` float DEFAULT NULL,
  `chassis_armor_left` int(11) DEFAULT NULL,
  `chassis_armor_right` int(11) DEFAULT NULL,
  `chassis_climb_edge` int(11) DEFAULT NULL,
  `chassis_load` int(11) DEFAULT NULL,
  `chassis_brake` int(11) DEFAULT NULL,
  `chassis_rotation_speed` int(11) DEFAULT NULL,
  `chassis_bulk_health` int(11) DEFAULT NULL,
  `chassis_terrain_resistance` varchar(128) DEFAULT NULL,
  `chassis_gun_dispersion_movement` float DEFAULT NULL,
  `chassis_gun_dispersion_rotation` float DEFAULT NULL,
  PRIMARY KEY (`wot_items_chassis_id`),
  KEY `fk_chassis_tanks_idx` (`wot_tanks_id`),
  KEY `fk_wotitemschassis_version_idx` (`wot_version_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=18051 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `wot_items_engines`
--

CREATE TABLE IF NOT EXISTS `wot_items_engines` (
  `wot_items_engines_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `wot_version_id` int(10) unsigned DEFAULT NULL,
  `id` int(11) DEFAULT NULL,
  `name_node` varchar(255) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `level` int(11) DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  `weight` float DEFAULT NULL,
  `health` int(11) DEFAULT NULL,
  `health_regen` int(11) DEFAULT NULL,
  `repair` float DEFAULT NULL,
  `engine_fire_chance` float DEFAULT NULL,
  `engine_power` int(11) DEFAULT NULL,
  PRIMARY KEY (`wot_items_engines_id`),
  KEY `fk_wotitemsengines_idx` (`wot_version_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9220 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `wot_items_engines_tanks`
--

CREATE TABLE IF NOT EXISTS `wot_items_engines_tanks` (
  `wot_items_engines_id` int(11) unsigned NOT NULL,
  `wot_tanks_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`wot_items_engines_id`,`wot_tanks_id`),
  KEY `fk_engines_tanks_idx` (`wot_tanks_id`),
  KEY `fk_engines_engines_idx` (`wot_items_engines_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabulky `wot_items_guns`
--

CREATE TABLE IF NOT EXISTS `wot_items_guns` (
  `wot_items_guns_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `wot_version_id` int(10) unsigned DEFAULT NULL,
  `id` int(11) DEFAULT NULL,
  `name_node` varchar(255) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `level` int(11) DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  `weight` float DEFAULT NULL,
  `health` int(11) DEFAULT NULL,
  `health_regen` int(11) DEFAULT NULL,
  `repair` float DEFAULT NULL,
  `gun_impulse` float DEFAULT NULL,
  `gun_recoil_amplitude` float DEFAULT NULL,
  `gun_recoil_backoffTime` float DEFAULT NULL,
  `gun_recoil_returnTime` float DEFAULT NULL,
  `gun_pitch_limits` varchar(255) DEFAULT NULL,
  `gun_rotation_speed` int(11) DEFAULT NULL,
  `gun_reload_time` float DEFAULT NULL,
  `gun_aiming_time` float DEFAULT NULL,
  `gun_clip_count` int(11) DEFAULT NULL,
  `gun_clip_rate` int(11) DEFAULT NULL,
  `gun_burst_count` int(11) DEFAULT NULL,
  `gun_burst_rate` int(11) DEFAULT NULL,
  `gun_dispersion_radius` float DEFAULT NULL,
  `gun_dispersion_turret_rotation` float DEFAULT NULL,
  `gun_dispersion_after_shot` float DEFAULT NULL,
  `gun_dispersion_damaged` float DEFAULT NULL,
  `gun_max_ammo` int(11) NOT NULL,
  `turret_yaw_limits` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`wot_items_guns_id`),
  KEY `fk_wotitemsguns_version_idx` (`wot_version_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=11559 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `wot_items_guns_turrets`
--

CREATE TABLE IF NOT EXISTS `wot_items_guns_turrets` (
  `wot_items_turrets_id` int(11) unsigned NOT NULL,
  `wot_items_guns_id` int(11) unsigned NOT NULL,
  `gun_max_ammo` int(11) DEFAULT NULL,
  `gun_pitch_limits` varchar(45) DEFAULT NULL,
  `gun_armor` varchar(255) DEFAULT NULL,
  `gun_armor_gun` int(11) DEFAULT NULL,
  `gun_aiming_time` float NOT NULL,
  `gun_reload_time` float NOT NULL,
  `gun_clip_count` int(11) DEFAULT NULL,
  `gun_clip_rate` int(11) DEFAULT NULL,
  `turret_yaw_limits` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`wot_items_turrets_id`,`wot_items_guns_id`),
  KEY `fk_guns_turrets_idx` (`wot_items_turrets_id`),
  KEY `fk_guns_guns_idx` (`wot_items_guns_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabulky `wot_items_radios`
--

CREATE TABLE IF NOT EXISTS `wot_items_radios` (
  `wot_items_radios_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `wot_version_id` int(10) unsigned DEFAULT NULL,
  `id` int(11) DEFAULT NULL,
  `name_node` varchar(255) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `level` int(11) DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  `weight` float DEFAULT NULL,
  `health` int(11) DEFAULT NULL,
  `health_regen` int(11) DEFAULT NULL,
  `repair` float DEFAULT NULL,
  `radio_distance` int(11) DEFAULT NULL,
  PRIMARY KEY (`wot_items_radios_id`),
  KEY `fk_wotitemsradios_version_idx` (`wot_version_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3514 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `wot_items_radios_tanks`
--

CREATE TABLE IF NOT EXISTS `wot_items_radios_tanks` (
  `wot_items_radios_id` int(11) unsigned NOT NULL,
  `wot_tanks_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`wot_items_radios_id`,`wot_tanks_id`),
  KEY `fk_radios_tanks_idx` (`wot_tanks_id`),
  KEY `fk_radios_radios_idx` (`wot_items_radios_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabulky `wot_items_shells`
--

CREATE TABLE IF NOT EXISTS `wot_items_shells` (
  `wot_items_shells_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `wot_version_id` int(10) unsigned DEFAULT NULL,
  `id` int(11) DEFAULT NULL,
  `name_node` varchar(255) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  `price_gold` int(11) NOT NULL,
  `shell_type` enum('AP','HE','APCR') DEFAULT NULL,
  `shell_caliber` int(11) DEFAULT NULL,
  `shell_tracer` tinyint(1) DEFAULT NULL,
  `shell_damage_armor` int(11) DEFAULT NULL,
  `shell_damage_device` int(11) DEFAULT NULL,
  `shell_explosion_radius` float DEFAULT NULL,
  PRIMARY KEY (`wot_items_shells_id`),
  KEY `fk_wotitemsshells_version_idx` (`wot_version_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=13932 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `wot_items_shells_guns`
--

CREATE TABLE IF NOT EXISTS `wot_items_shells_guns` (
  `wot_items_shells_id` int(11) unsigned NOT NULL DEFAULT '0',
  `wot_items_guns_id` int(11) unsigned NOT NULL DEFAULT '0',
  `shell_default_portion` float DEFAULT NULL,
  `shell_speed` int(11) DEFAULT NULL,
  `shell_max_distance` int(11) DEFAULT NULL,
  `shell_piercing_power` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`wot_items_shells_id`,`wot_items_guns_id`),
  KEY `fk_shells_guns_idx` (`wot_items_guns_id`),
  KEY `fk_shells_shells_idx` (`wot_items_shells_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabulky `wot_items_tanks`
--

CREATE TABLE IF NOT EXISTS `wot_items_tanks` (
  `wot_items_tanks_id` int(11) NOT NULL AUTO_INCREMENT,
  `wot_version_Id` int(10) unsigned DEFAULT NULL,
  `id` int(11) DEFAULT NULL,
  `name_node` varchar(255) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `nation` enum('ussr','usa','germany','china','france','uk','japan') NOT NULL,
  `price` int(11) DEFAULT NULL,
  `weight` float DEFAULT NULL,
  `health` int(11) DEFAULT NULL,
  `health_regen` int(11) DEFAULT NULL,
  `repair` float DEFAULT NULL,
  PRIMARY KEY (`wot_items_tanks_id`),
  KEY `fk_wotitemstanks_version_idx` (`wot_version_Id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1672 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `wot_items_turrets`
--

CREATE TABLE IF NOT EXISTS `wot_items_turrets` (
  `wot_items_turrets_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `wot_tanks_id` int(11) unsigned DEFAULT NULL,
  `wot_version_id` int(10) unsigned DEFAULT NULL,
  `id` int(11) DEFAULT NULL,
  `name_node` varchar(255) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `level` int(11) DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  `weight` float DEFAULT NULL,
  `health` int(11) DEFAULT NULL,
  `health_regen` int(11) DEFAULT NULL,
  `repair` float DEFAULT NULL,
  `turret_yaw_limits` varchar(100) DEFAULT NULL,
  `turret_armor` varchar(255) DEFAULT NULL,
  `turret_armor_primary` varchar(45) DEFAULT NULL,
  `turret_rotation_speed` int(11) DEFAULT NULL,
  `turret_rotator_health` int(11) DEFAULT NULL,
  `turret_rotator_health_regen` int(11) DEFAULT NULL,
  `turret_rotator_repair` float DEFAULT NULL,
  `turret_vision_radius` int(11) DEFAULT NULL,
  `turret_scope_health` int(11) DEFAULT NULL,
  `turret_scope_health_regen` int(11) DEFAULT NULL,
  `turret_scope_repair` float DEFAULT NULL,
  PRIMARY KEY (`wot_items_turrets_id`),
  KEY `fk_turrets_tanks_idx` (`wot_tanks_id`),
  KEY `fk_wotitemsturrets_version_idx` (`wot_version_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=15144 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `wot_tanks`
--

CREATE TABLE IF NOT EXISTS `wot_tanks` (
  `wot_tanks_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `wot_version_id` int(10) unsigned DEFAULT NULL,
  `id` int(11) DEFAULT NULL,
  `name_node` varchar(255) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `class` enum('light','medium','heavy','spg','td') DEFAULT NULL,
  `level` int(11) DEFAULT NULL,
  `nation` enum('germany','ussr','usa','china','france','uk','japan') DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  `price_gold` int(11) NOT NULL,
  `secret` tinyint(1) DEFAULT NULL,
  `igr` tinyint(1) DEFAULT NULL,
  `health` int(11) DEFAULT NULL,
  `speed_forward` int(11) DEFAULT NULL,
  `speed_backward` int(11) DEFAULT NULL,
  `repair` float DEFAULT NULL,
  `weight` float DEFAULT NULL,
  `armor` varchar(255) DEFAULT NULL,
  `armor_primary` varchar(45) DEFAULT NULL,
  `ammo_health` int(11) DEFAULT NULL,
  `ammo_repair` float DEFAULT NULL,
  `ammo_health_regen` int(11) DEFAULT NULL,
  `default_chassis` int(11) DEFAULT NULL,
  `default_turret` int(11) DEFAULT NULL,
  `default_engine` int(11) DEFAULT NULL,
  `default_radio` int(11) DEFAULT NULL,
  `default_tank` int(11) DEFAULT NULL,
  `tags` text,
  `image` varchar(150) DEFAULT NULL,
  `crew` int(1) NOT NULL,
  PRIMARY KEY (`wot_tanks_id`),
  KEY `fk_wottanks_version_idx` (`wot_version_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10605 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `wot_tanks_parents`
--

CREATE TABLE IF NOT EXISTS `wot_tanks_parents` (
  `wot_tanks_id` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned NOT NULL,
  `cost` int(11) DEFAULT NULL,
  KEY `fk_tankparents_tank_idx` (`wot_tanks_id`),
  KEY `fk_tankparents_tank_idx1` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabulky `wot_versions`
--

CREATE TABLE IF NOT EXISTS `wot_versions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(55) DEFAULT NULL,
  `published` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=35 ;

--
-- Omezení pro exportované tabulky
--

--
-- Omezení pro tabulku `wot_equipment`
--
ALTER TABLE `wot_equipment`
  ADD CONSTRAINT `wot_equipment_ibfk_1` FOREIGN KEY (`wot_version_id`) REFERENCES `wot_versions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `wot_equipment_params`
--
ALTER TABLE `wot_equipment_params`
  ADD CONSTRAINT `wot_equipment_params_ibfk_1` FOREIGN KEY (`wot_equipment_id`) REFERENCES `wot_equipment` (`wot_equipment_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `wot_items_chassis`
--
ALTER TABLE `wot_items_chassis`
  ADD CONSTRAINT `fk_chassis_tanks` FOREIGN KEY (`wot_tanks_id`) REFERENCES `wot_tanks` (`wot_tanks_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_wotitemschassis_version` FOREIGN KEY (`wot_version_id`) REFERENCES `wot_versions` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `wot_items_engines`
--
ALTER TABLE `wot_items_engines`
  ADD CONSTRAINT `fk_wotitemsengines_version` FOREIGN KEY (`wot_version_id`) REFERENCES `wot_versions` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `wot_items_engines_tanks`
--
ALTER TABLE `wot_items_engines_tanks`
  ADD CONSTRAINT `fk_engines_engines` FOREIGN KEY (`wot_items_engines_id`) REFERENCES `wot_items_engines` (`wot_items_engines_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_engines_tanks` FOREIGN KEY (`wot_tanks_id`) REFERENCES `wot_tanks` (`wot_tanks_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `wot_items_guns`
--
ALTER TABLE `wot_items_guns`
  ADD CONSTRAINT `fk_wotitemsguns_version` FOREIGN KEY (`wot_version_id`) REFERENCES `wot_versions` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `wot_items_guns_turrets`
--
ALTER TABLE `wot_items_guns_turrets`
  ADD CONSTRAINT `fk_guns_guns` FOREIGN KEY (`wot_items_guns_id`) REFERENCES `wot_items_guns` (`wot_items_guns_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_guns_turrets` FOREIGN KEY (`wot_items_turrets_id`) REFERENCES `wot_items_turrets` (`wot_items_turrets_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `wot_items_radios`
--
ALTER TABLE `wot_items_radios`
  ADD CONSTRAINT `fk_wotitemsradios_version` FOREIGN KEY (`wot_version_id`) REFERENCES `wot_versions` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `wot_items_radios_tanks`
--
ALTER TABLE `wot_items_radios_tanks`
  ADD CONSTRAINT `fk_radios_radios` FOREIGN KEY (`wot_items_radios_id`) REFERENCES `wot_items_radios` (`wot_items_radios_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_radios_tanks` FOREIGN KEY (`wot_tanks_id`) REFERENCES `wot_tanks` (`wot_tanks_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `wot_items_shells`
--
ALTER TABLE `wot_items_shells`
  ADD CONSTRAINT `fk_wotitemsshells_version` FOREIGN KEY (`wot_version_id`) REFERENCES `wot_versions` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `wot_items_shells_guns`
--
ALTER TABLE `wot_items_shells_guns`
  ADD CONSTRAINT `fk_shells_guns` FOREIGN KEY (`wot_items_guns_id`) REFERENCES `wot_items_guns` (`wot_items_guns_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_shells_shells` FOREIGN KEY (`wot_items_shells_id`) REFERENCES `wot_items_shells` (`wot_items_shells_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `wot_items_tanks`
--
ALTER TABLE `wot_items_tanks`
  ADD CONSTRAINT `fk_wotitemstanks_version` FOREIGN KEY (`wot_version_Id`) REFERENCES `wot_versions` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `wot_items_turrets`
--
ALTER TABLE `wot_items_turrets`
  ADD CONSTRAINT `fk_turrets_tanks` FOREIGN KEY (`wot_tanks_id`) REFERENCES `wot_tanks` (`wot_tanks_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_wotitemsturrets_version` FOREIGN KEY (`wot_version_id`) REFERENCES `wot_versions` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `wot_tanks`
--
ALTER TABLE `wot_tanks`
  ADD CONSTRAINT `fk_wottanks_version` FOREIGN KEY (`wot_version_id`) REFERENCES `wot_versions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `wot_tanks_parents`
--
ALTER TABLE `wot_tanks_parents`
  ADD CONSTRAINT `fk_tankparents_parent` FOREIGN KEY (`parent_id`) REFERENCES `wot_tanks` (`wot_tanks_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tankparents_tank` FOREIGN KEY (`wot_tanks_id`) REFERENCES `wot_tanks` (`wot_tanks_id`) ON DELETE CASCADE ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
