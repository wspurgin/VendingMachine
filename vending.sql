-- SQL Create syntax for VendingMachine Schema
DROP DATABASE IF EXISTS vending;
CREATE DATABASE IF NOT EXISTS vending DEFAULT CHARSET=utf8;
USE vending;

CREATE TABLE Groups(
    `id` int(11) AUTO_INCREMENT PRIMARY KEY,
    `name` varchar(30) NOT NULL
    
) ENGINE=INNODB DEFAULT CHARSET=utf8;

CREATE TABLE Users(
    `id` int(11) NOT NULL PRIMARY KEY,
    `password` varchar(128) NOT NULL,
    `name` varchar(30) NOT NULL,
    `email` varchar(64) NOT NULL,
    `group` int(11) NOT NULL,
    `balance` double(11, 2) UNSIGNED NOT NULL DEFAULT 0.00,

    INDEX(`group`),

    CONSTRAINT user_fk_group
    FOREIGN KEY (`group`)
        REFERENCES Groups(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE


) ENGINE=INNODB DEFAULT CHARSET=utf8;

CREATE TABLE Teams(
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `team_name` varchar(30) NOT NULL,
    `class` varchar(30) NOT NULL,
    `expiration` DATE NOT NULL,
    `team_balance` double(11, 2) UNSIGNED NOT NULL DEFAULT 0.00

) ENGINE=INNODB DEFAULT CHARSET=utf8;

CREATE TABLE Team_Members(
    `team_id` int(11),
    `user_id` int(11),

    CONSTRAINT pk_team_member_id 
    PRIMARY KEY (`team_id`, `user_id`),

    INDEX(`team_id`),
    INDEX(`user_id`),

    CONSTRAINT team_members_fk_team
    FOREIGN KEY (`team_id`)
        REFERENCES Teams(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT team_members_fk_user
    FOREIGN KEY (`user_id`)
        REFERENCES Users(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE

) ENGINE=INNODB DEFAULT CHARSET=utf8;

CREATE TABLE Products(
    `id` int(11) AUTO_INCREMENT PRIMARY KEY,
    `name` varchar(50) NOT NULL,
    `vendor` varchar(30) NOT NULL,
    `cost` double(11, 2) UNSIGNED NOT NULL DEFAULT 0.00
);

CREATE TABLE Machines(
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `machine_location` varchar(30) NOT NULL

) ENGINE=INNODB DEFAULT CHARSET=utf8;

CREATE TABLE Machine_Supplies(
    `machine_id` int(11) NOT NULL,
    `product_id` int(11) NOT NULL,
    `quantity` int(11) UNSIGNED NOT NULL DEFAULT 0,

    CONSTRAINT pk_machine_supply_id
    PRIMARY KEY (`machine_id`, `product_id`),

    INDEX(`machine_id`),
    INDEX(`product_id`),

    CONSTRAINT machine_supplies_fk_machine
    FOREIGN KEY (`machine_id`)
        REFERENCES Machines(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT machine_supplies_fk_product
    FOREIGN KEY (`product_id`)
        REFERENCES Products(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE

) ENGINE=INNODB DEFAULT CHARSET=utf8;

CREATE TABLE Log(
    `id` int(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` int(11),
    `product_id` int(11),
    `machine_id` int(11),
    `date_purchased` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX(`user_id`),
    INDEX(`product_id`),
    INDEX(`machine_id`),
    
    CONSTRAINT log_fk_user
    FOREIGN KEY (`user_id`)
        REFERENCES Users(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    
    CONSTRAINT log_fk_product
    FOREIGN KEY (`product_id`)
        REFERENCES Products(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
        
    CONSTRAINT log_fk_machine
    FOREIGN KEY (`machine_id`)
        REFERENCES Machines(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
    
) ENGINE=INNODB DEFAULT CHARSET=utf8;

CREATE TABLE Permissions(
    `id` int(11) AUTO_INCREMENT PRIMARY KEY,
    `description` varchar(80) NOT NULL,
    `code_name` varchar(20) NOT NULL
    
) ENGINE=INNODB DEFAULT CHARSET=utf8;

CREATE TABLE Group_Permissisons(
    `group_id` int(11),
    `permission_id` int(11),
    
    CONSTRAINT pk_group_permission_id
    PRIMARY KEY (`group_id`, `permission_id`),

    INDEX(`group_id`),
    INDEX(`permission_id`),

    CONSTRAINT group_permission_fk_group
    FOREIGN KEY (`group_id`)
        REFERENCES Groups(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT group_permission_fk_permission
    FOREIGN KEY (`permission_id`)
        REFERENCES Permissions(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=INNODB DEFAULT CHARSET=utf8;
