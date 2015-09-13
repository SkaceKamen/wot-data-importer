-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- -----------------------------------------------------
-- Schema mydb
-- -----------------------------------------------------
-- -----------------------------------------------------
-- Schema wot
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Schema wot
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `wot` DEFAULT CHARACTER SET utf8 ;
USE `wot` ;

-- -----------------------------------------------------
-- Table `wot`.`wot_versions`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `wot`.`wot_versions` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `version` VARCHAR(55) NULL DEFAULT NULL,
  `published` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
AUTO_INCREMENT = 10
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `wot`.`wot_equipment`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `wot`.`wot_equipment` (
  `wot_equipment_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `wot_version_id` INT(10) UNSIGNED NOT NULL,
  `name` VARCHAR(128) NOT NULL,
  `name_node` VARCHAR(128) NOT NULL,
  `description` TINYTEXT NOT NULL,
  `icon` VARCHAR(255) NOT NULL,
  `price` INT(10) UNSIGNED NOT NULL,
  `price_gold` INT(10) UNSIGNED NULL DEFAULT NULL,
  `removable` TINYINT(1) UNSIGNED NOT NULL,
  `weight` INT(10) UNSIGNED NULL DEFAULT NULL,
  `vehicle_tags_include` TINYTEXT NOT NULL,
  `vehicle_tags_exclude` TINYTEXT NOT NULL,
  PRIMARY KEY (`wot_equipment_id`),
  INDEX `wot_version_id` (`wot_version_id` ASC),
  CONSTRAINT `wot_equipment_ibfk_1`
    FOREIGN KEY (`wot_version_id`)
    REFERENCES `wot`.`wot_versions` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
AUTO_INCREMENT = 89
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `wot`.`wot_equipment_params`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `wot`.`wot_equipment_params` (
  `wot_equipment_params_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `wot_equipment_id` INT(10) UNSIGNED NOT NULL,
  `param` VARCHAR(128) NOT NULL,
  `value` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`wot_equipment_params_id`),
  INDEX `wot_equipment_id` (`wot_equipment_id` ASC),
  CONSTRAINT `wot_equipment_params_ibfk_1`
    FOREIGN KEY (`wot_equipment_id`)
    REFERENCES `wot`.`wot_equipment` (`wot_equipment_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
AUTO_INCREMENT = 184
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `wot`.`wot_tanks`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `wot`.`wot_tanks` (
  `wot_tanks_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `wot_version_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `id` INT(11) NULL DEFAULT NULL,
  `name_node` VARCHAR(255) NULL DEFAULT NULL,
  `name` VARCHAR(100) NULL DEFAULT NULL,
  `class` ENUM('light','medium','heavy','spg','td') NULL DEFAULT NULL,
  `level` INT(11) NULL DEFAULT NULL,
  `nation` ENUM('germany','ussr','usa','china','france','uk','japan') NULL DEFAULT NULL,
  `price` INT(11) NULL DEFAULT NULL,
  `price_gold` INT(11) NULL,
  `secret` TINYINT(1) NULL DEFAULT NULL,
  `igr` TINYINT(1) NULL DEFAULT NULL,
  `health` INT(11) NULL DEFAULT NULL,
  `speed_forward` INT(11) NULL DEFAULT NULL,
  `speed_backward` INT(11) NULL DEFAULT NULL,
  `repair` FLOAT NULL DEFAULT NULL,
  `weight` FLOAT NULL DEFAULT NULL,
  `armor` VARCHAR(255) NULL DEFAULT NULL,
  `armor_primary` VARCHAR(45) NULL DEFAULT NULL,
  `ammo_health` INT(11) NULL DEFAULT NULL,
  `ammo_repair` FLOAT NULL DEFAULT NULL,
  `ammo_health_regen` INT(11) NULL DEFAULT NULL,
  `default_chassis` INT(11) NULL DEFAULT NULL,
  `default_turret` INT(11) NULL DEFAULT NULL,
  `default_engine` INT(11) NULL DEFAULT NULL,
  `default_radio` INT(11) NULL DEFAULT NULL,
  `default_tank` INT(11) NULL DEFAULT NULL,
  `tags` TEXT NULL DEFAULT NULL,
  `image` VARCHAR(150) NULL DEFAULT NULL,
  `crew` INT(1) NOT NULL,
  PRIMARY KEY (`wot_tanks_id`),
  INDEX `fk_wottanks_version_idx` (`wot_version_id` ASC),
  CONSTRAINT `fk_wottanks_version`
    FOREIGN KEY (`wot_version_id`)
    REFERENCES `wot`.`wot_versions` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
AUTO_INCREMENT = 495
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `wot`.`wot_items_chassis`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `wot`.`wot_items_chassis` (
  `wot_items_chassis_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `wot_tanks_id` INT(11) UNSIGNED NULL DEFAULT NULL,
  `wot_version_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `id` INT(11) NULL DEFAULT NULL,
  `name_node` VARCHAR(255) NULL DEFAULT NULL,
  `name` VARCHAR(100) NULL DEFAULT NULL,
  `level` INT(11) NULL DEFAULT NULL,
  `price` INT(11) NULL DEFAULT NULL,
  `weight` FLOAT NULL DEFAULT NULL,
  `health` INT(11) NULL DEFAULT NULL,
  `health_regen` INT(11) NULL DEFAULT NULL,
  `repair` FLOAT NULL DEFAULT NULL,
  `chassis_armor_left` INT(11) NULL DEFAULT NULL,
  `chassis_armor_right` INT(11) NULL DEFAULT NULL,
  `chassis_climb_edge` INT(11) NULL DEFAULT NULL,
  `chassis_load` INT(11) NULL DEFAULT NULL,
  `chassis_brake` INT(11) NULL DEFAULT NULL,
  `chassis_rotation_speed` INT(11) NULL DEFAULT NULL,
  `chassis_bulk_health` INT(11) NULL DEFAULT NULL,
  `chassis_terrain_resistance` VARCHAR(128) NULL DEFAULT NULL,
  `chassis_gun_dispersion_movement` FLOAT NULL DEFAULT NULL,
  `chassis_gun_dispersion_rotation` FLOAT NULL DEFAULT NULL,
  PRIMARY KEY (`wot_items_chassis_id`),
  INDEX `fk_chassis_tanks_idx` (`wot_tanks_id` ASC),
  INDEX `fk_wotitemschassis_version_idx` (`wot_version_id` ASC),
  CONSTRAINT `fk_chassis_tanks`
    FOREIGN KEY (`wot_tanks_id`)
    REFERENCES `wot`.`wot_tanks` (`wot_tanks_id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_wotitemschassis_version`
    FOREIGN KEY (`wot_version_id`)
    REFERENCES `wot`.`wot_versions` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 780
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `wot`.`wot_items_engines`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `wot`.`wot_items_engines` (
  `wot_items_engines_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `wot_version_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `id` INT(11) NULL DEFAULT NULL,
  `name_node` VARCHAR(255) NULL DEFAULT NULL,
  `name` VARCHAR(100) NULL DEFAULT NULL,
  `level` INT(11) NULL DEFAULT NULL,
  `price` INT(11) NULL DEFAULT NULL,
  `weight` FLOAT NULL DEFAULT NULL,
  `health` INT(11) NULL DEFAULT NULL,
  `health_regen` INT(11) NULL DEFAULT NULL,
  `repair` FLOAT NULL DEFAULT NULL,
  `engine_fire_chance` FLOAT NULL DEFAULT NULL,
  `engine_power` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`wot_items_engines_id`),
  INDEX `fk_wotitemsengines_idx` (`wot_version_id` ASC),
  CONSTRAINT `fk_wotitemsengines_version`
    FOREIGN KEY (`wot_version_id`)
    REFERENCES `wot`.`wot_versions` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 479
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `wot`.`wot_items_engines_tanks`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `wot`.`wot_items_engines_tanks` (
  `wot_items_engines_id` INT(11) UNSIGNED NOT NULL,
  `wot_tanks_id` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`wot_items_engines_id`, `wot_tanks_id`),
  INDEX `fk_engines_tanks_idx` (`wot_tanks_id` ASC),
  INDEX `fk_engines_engines_idx` (`wot_items_engines_id` ASC),
  CONSTRAINT `fk_engines_engines`
    FOREIGN KEY (`wot_items_engines_id`)
    REFERENCES `wot`.`wot_items_engines` (`wot_items_engines_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_engines_tanks`
    FOREIGN KEY (`wot_tanks_id`)
    REFERENCES `wot`.`wot_tanks` (`wot_tanks_id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `wot`.`wot_items_guns`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `wot`.`wot_items_guns` (
  `wot_items_guns_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `wot_version_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `id` INT(11) NULL DEFAULT NULL,
  `name_node` VARCHAR(255) NULL DEFAULT NULL,
  `name` VARCHAR(100) NULL DEFAULT NULL,
  `level` INT(11) NULL DEFAULT NULL,
  `price` INT(11) NULL DEFAULT NULL,
  `weight` FLOAT NULL DEFAULT NULL,
  `health` INT(11) NULL DEFAULT NULL,
  `health_regen` INT(11) NULL DEFAULT NULL,
  `repair` FLOAT NULL DEFAULT NULL,
  `gun_impulse` FLOAT NULL DEFAULT NULL,
  `gun_recoil_amplitude` FLOAT NULL DEFAULT NULL,
  `gun_recoil_backoffTime` FLOAT NULL DEFAULT NULL,
  `gun_recoil_returnTime` FLOAT NULL DEFAULT NULL,
  `gun_pitch_limits` VARCHAR(255) NULL DEFAULT NULL,
  `gun_rotation_speed` INT(11) NULL DEFAULT NULL,
  `gun_reload_time` FLOAT NULL DEFAULT NULL,
  `gun_aiming_time` FLOAT NULL DEFAULT NULL,
  `gun_clip_count` INT(11) NULL DEFAULT NULL,
  `gun_clip_rate` INT(11) NULL DEFAULT NULL,
  `gun_burst_count` INT(11) NULL DEFAULT NULL,
  `gun_burst_rate` INT(11) NULL DEFAULT NULL,
  `gun_dispersion_radius` FLOAT NULL DEFAULT NULL,
  `gun_dispersion_turret_rotation` FLOAT NULL DEFAULT NULL,
  `gun_dispersion_after_shot` FLOAT NULL DEFAULT NULL,
  `gun_dispersion_damaged` FLOAT NULL DEFAULT NULL,
  `gun_max_ammo` INT(11) NOT NULL,
  `turret_yaw_limits` VARCHAR(32) NULL DEFAULT NULL,
  PRIMARY KEY (`wot_items_guns_id`),
  INDEX `fk_wotitemsguns_version_idx` (`wot_version_id` ASC),
  CONSTRAINT `fk_wotitemsguns_version`
    FOREIGN KEY (`wot_version_id`)
    REFERENCES `wot`.`wot_versions` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 547
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `wot`.`wot_items_turrets`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `wot`.`wot_items_turrets` (
  `wot_items_turrets_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `wot_tanks_id` INT(11) UNSIGNED NULL DEFAULT NULL,
  `wot_version_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `id` INT(11) NULL DEFAULT NULL,
  `name_node` VARCHAR(255) NULL DEFAULT NULL,
  `name` VARCHAR(100) NULL DEFAULT NULL,
  `level` INT(11) NULL DEFAULT NULL,
  `price` INT(11) NULL DEFAULT NULL,
  `weight` FLOAT NULL DEFAULT NULL,
  `health` INT(11) NULL DEFAULT NULL,
  `health_regen` INT(11) NULL DEFAULT NULL,
  `repair` FLOAT NULL DEFAULT NULL,
  `turret_yaw_limits` VARCHAR(100) NULL DEFAULT NULL,
  `turret_armor` VARCHAR(255) NULL DEFAULT NULL,
  `turret_armor_primary` VARCHAR(45) NULL DEFAULT NULL,
  `turret_rotation_speed` INT(11) NULL DEFAULT NULL,
  `turret_rotator_health` INT(11) NULL DEFAULT NULL,
  `turret_rotator_health_regen` INT(11) NULL DEFAULT NULL,
  `turret_rotator_repair` FLOAT NULL DEFAULT NULL,
  `turret_vision_radius` INT(11) NULL DEFAULT NULL,
  `turret_scope_health` INT(11) NULL DEFAULT NULL,
  `turret_scope_health_regen` INT(11) NULL DEFAULT NULL,
  `turret_scope_repair` FLOAT NULL DEFAULT NULL,
  PRIMARY KEY (`wot_items_turrets_id`),
  INDEX `fk_turrets_tanks_idx` (`wot_tanks_id` ASC),
  INDEX `fk_wotitemsturrets_version_idx` (`wot_version_id` ASC),
  CONSTRAINT `fk_turrets_tanks`
    FOREIGN KEY (`wot_tanks_id`)
    REFERENCES `wot`.`wot_tanks` (`wot_tanks_id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_wotitemsturrets_version`
    FOREIGN KEY (`wot_version_id`)
    REFERENCES `wot`.`wot_versions` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 665
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `wot`.`wot_items_guns_turrets`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `wot`.`wot_items_guns_turrets` (
  `wot_items_turrets_id` INT(11) UNSIGNED NOT NULL,
  `wot_items_guns_id` INT(11) UNSIGNED NOT NULL,
  `gun_max_ammo` INT(11) NULL DEFAULT NULL,
  `gun_pitch_limits` VARCHAR(45) NULL DEFAULT NULL,
  `gun_armor` VARCHAR(255) NULL DEFAULT NULL,
  `gun_armor_gun` INT(11) NULL DEFAULT NULL,
  `gun_aiming_time` FLOAT NOT NULL,
  `gun_reload_time` FLOAT NOT NULL,
  `gun_clip_count` INT(11) NULL DEFAULT NULL,
  `gun_clip_rate` INT(11) NULL DEFAULT NULL,
  `turret_yaw_limits` VARCHAR(32) NULL DEFAULT NULL,
  PRIMARY KEY (`wot_items_turrets_id`, `wot_items_guns_id`),
  INDEX `fk_guns_turrets_idx` (`wot_items_turrets_id` ASC),
  INDEX `fk_guns_guns_idx` (`wot_items_guns_id` ASC),
  CONSTRAINT `fk_guns_guns`
    FOREIGN KEY (`wot_items_guns_id`)
    REFERENCES `wot`.`wot_items_guns` (`wot_items_guns_id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_guns_turrets`
    FOREIGN KEY (`wot_items_turrets_id`)
    REFERENCES `wot`.`wot_items_turrets` (`wot_items_turrets_id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `wot`.`wot_items_radios`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `wot`.`wot_items_radios` (
  `wot_items_radios_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `wot_version_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `id` INT(11) NULL DEFAULT NULL,
  `name_node` VARCHAR(255) NULL DEFAULT NULL,
  `name` VARCHAR(100) NULL DEFAULT NULL,
  `level` INT(11) NULL DEFAULT NULL,
  `price` INT(11) NULL DEFAULT NULL,
  `weight` FLOAT NULL DEFAULT NULL,
  `health` INT(11) NULL DEFAULT NULL,
  `health_regen` INT(11) NULL DEFAULT NULL,
  `repair` FLOAT NULL DEFAULT NULL,
  `radio_distance` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`wot_items_radios_id`),
  INDEX `fk_wotitemsradios_version_idx` (`wot_version_id` ASC),
  CONSTRAINT `fk_wotitemsradios_version`
    FOREIGN KEY (`wot_version_id`)
    REFERENCES `wot`.`wot_versions` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 5307
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `wot`.`wot_items_radios_tanks`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `wot`.`wot_items_radios_tanks` (
  `wot_items_radios_id` INT(11) UNSIGNED NOT NULL,
  `wot_tanks_id` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`wot_items_radios_id`, `wot_tanks_id`),
  INDEX `fk_radios_tanks_idx` (`wot_tanks_id` ASC),
  INDEX `fk_radios_radios_idx` (`wot_items_radios_id` ASC),
  CONSTRAINT `fk_radios_radios`
    FOREIGN KEY (`wot_items_radios_id`)
    REFERENCES `wot`.`wot_items_radios` (`wot_items_radios_id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_radios_tanks`
    FOREIGN KEY (`wot_tanks_id`)
    REFERENCES `wot`.`wot_tanks` (`wot_tanks_id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `wot`.`wot_items_shells`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `wot`.`wot_items_shells` (
  `wot_items_shells_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `wot_version_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `id` INT(11) NULL DEFAULT NULL,
  `name_node` VARCHAR(255) NULL DEFAULT NULL,
  `name` VARCHAR(100) NULL DEFAULT NULL,
  `price` INT(11) NULL DEFAULT NULL,
  `price_gold` INT(11) NOT NULL,
  `shell_type` ENUM('AP','HE','APCR','HEAT') NULL DEFAULT NULL,
  `shell_caliber` INT(11) NULL DEFAULT NULL,
  `shell_tracer` TINYINT(1) NULL DEFAULT NULL,
  `shell_damage_armor` INT(11) NULL DEFAULT NULL,
  `shell_damage_device` INT(11) NULL DEFAULT NULL,
  `shell_explosion_radius` FLOAT NULL DEFAULT NULL,
  PRIMARY KEY (`wot_items_shells_id`),
  INDEX `fk_wotitemsshells_version_idx` (`wot_version_id` ASC),
  CONSTRAINT `fk_wotitemsshells_version`
    FOREIGN KEY (`wot_version_id`)
    REFERENCES `wot`.`wot_versions` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 654
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `wot`.`wot_items_shells_guns`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `wot`.`wot_items_shells_guns` (
  `wot_items_shells_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `wot_items_guns_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `shell_default_portion` FLOAT NULL DEFAULT NULL,
  `shell_speed` INT(11) NULL DEFAULT NULL,
  `shell_max_distance` INT(11) NULL DEFAULT NULL,
  `shell_piercing_power` VARCHAR(100) NULL DEFAULT NULL,
  PRIMARY KEY (`wot_items_shells_id`, `wot_items_guns_id`),
  INDEX `fk_shells_guns_idx` (`wot_items_guns_id` ASC),
  INDEX `fk_shells_shells_idx` (`wot_items_shells_id` ASC),
  CONSTRAINT `fk_shells_guns`
    FOREIGN KEY (`wot_items_guns_id`)
    REFERENCES `wot`.`wot_items_guns` (`wot_items_guns_id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_shells_shells`
    FOREIGN KEY (`wot_items_shells_id`)
    REFERENCES `wot`.`wot_items_shells` (`wot_items_shells_id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `wot`.`wot_items_tanks`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `wot`.`wot_items_tanks` (
  `wot_items_tanks_id` INT(11) NOT NULL AUTO_INCREMENT,
  `wot_version_Id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `id` INT(11) NULL DEFAULT NULL,
  `name_node` VARCHAR(255) NULL DEFAULT NULL,
  `name` VARCHAR(100) NULL DEFAULT NULL,
  `nation` ENUM('ussr','usa','germany','china','france','uk','japan') NOT NULL,
  `price` INT(11) NULL DEFAULT NULL,
  `weight` FLOAT NULL DEFAULT NULL,
  `health` INT(11) NULL DEFAULT NULL,
  `health_regen` INT(11) NULL DEFAULT NULL,
  `repair` FLOAT NULL DEFAULT NULL,
  PRIMARY KEY (`wot_items_tanks_id`),
  INDEX `fk_wotitemstanks_version_idx` (`wot_version_Id` ASC),
  CONSTRAINT `fk_wotitemstanks_version`
    FOREIGN KEY (`wot_version_Id`)
    REFERENCES `wot`.`wot_versions` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 66
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `wot`.`wot_tanks_parents`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `wot`.`wot_tanks_parents` (
  `wot_tanks_id` INT(10) UNSIGNED NOT NULL,
  `parent_id` INT(10) UNSIGNED NOT NULL,
  `cost` INT(11) NULL DEFAULT NULL,
  INDEX `fk_tankparents_tank_idx` (`wot_tanks_id` ASC),
  INDEX `fk_tankparents_tank_idx1` (`parent_id` ASC),
  CONSTRAINT `fk_tankparents_parent`
    FOREIGN KEY (`parent_id`)
    REFERENCES `wot`.`wot_tanks` (`wot_tanks_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_tankparents_tank`
    FOREIGN KEY (`wot_tanks_id`)
    REFERENCES `wot`.`wot_tanks` (`wot_tanks_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
