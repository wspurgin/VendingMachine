-- SQL Create syntax for VendingMachine Schema
DROP DATABASE IF EXISTS vending;
CREATE DATABASE IF NOT EXISTS vending DEFAULT CHARSET=utf8;
USE vending;

# Dump of table Group_Permissions
# ------------------------------------------------------------

CREATE TABLE `Group_Permissions` (
  `group_id` int(11) NOT NULL DEFAULT '0',
  `permission_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`group_id`,`permission_id`),
  KEY `group_id` (`group_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `group_permission_fk_group` FOREIGN KEY (`group_id`) REFERENCES `Groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `group_permission_fk_permission` FOREIGN KEY (`permission_id`) REFERENCES `Permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table Groups
# ------------------------------------------------------------

CREATE TABLE `Groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table Logs
# ------------------------------------------------------------

CREATE TABLE `Logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `machine_id` int(11) NOT NULL,
  `date_purchased` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`),
  KEY `machine_id` (`machine_id`),
  CONSTRAINT `log_fk_machine` FOREIGN KEY (`machine_id`) REFERENCES `Machines` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `log_fk_product` FOREIGN KEY (`product_id`) REFERENCES `Products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `log_fk_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table Machine_Supplies
# ------------------------------------------------------------

CREATE TABLE `Machine_Supplies` (
  `machine_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`machine_id`,`product_id`),
  KEY `machine_id` (`machine_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `machine_supplies_fk_machine` FOREIGN KEY (`machine_id`) REFERENCES `Machines` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `machine_supplies_fk_product` FOREIGN KEY (`product_id`) REFERENCES `Products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table Machines
# ------------------------------------------------------------

CREATE TABLE `Machines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `machine_location` varchar(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table Permissions
# ------------------------------------------------------------

CREATE TABLE `Permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(80) NOT NULL,
  `code_name` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table Products
# ------------------------------------------------------------

CREATE TABLE `Products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sku` varchar(128) NOT NULL DEFAULT '',
  `name` varchar(50) NOT NULL,
  `vendor` varchar(30) NOT NULL,
  `cost` double(11,2) unsigned NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table Team_Members
# ------------------------------------------------------------

CREATE TABLE `Team_Members` (
  `team_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`team_id`,`user_id`),
  KEY `team_id` (`team_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `team_members_fk_team` FOREIGN KEY (`team_id`) REFERENCES `Teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `team_members_fk_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table Teams
# ------------------------------------------------------------

CREATE TABLE `Teams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_name` varchar(30) NOT NULL,
  `class` varchar(30) NOT NULL,
  `expiration_date` date NOT NULL,
  `team_balance` double(11,2) unsigned NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table User_Permissions
# ------------------------------------------------------------

CREATE TABLE `User_Permissions` (
  `user_id` int(11) NOT NULL DEFAULT '0',
  `permission_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`,`permission_id`),
  KEY `user_id` (`user_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `user_permission_fk_group` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_permission_fk_permission` FOREIGN KEY (`permission_id`) REFERENCES `Permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table Users
# ------------------------------------------------------------

CREATE TABLE `Users` (
  `id` int(11) NOT NULL,
  `password` varchar(128) NOT NULL,
  `name` varchar(30) NOT NULL,
  `email` varchar(64) NOT NULL,
  `group_id` int(11) NOT NULL,
  `balance` double(11,2) unsigned NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `group` (`group_id`),
  CONSTRAINT `user_fk_group` FOREIGN KEY (`group_id`) REFERENCES `Groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Triggers
DELIMITER ;;
CREATE TRIGGER `insert_current_date` BEFORE INSERT ON `Logs`
 FOR EACH ROW BEGIN
    SET new.date_purchased=CURRENT_DATE;
END;;
DELIMITER ;
