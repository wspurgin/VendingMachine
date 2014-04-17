VendingMachine
==============

##Setting Up Repo

###Requirements

Apache, Mysql, PHP 5.x.x

###Installing Project: Cloning

First you must clone this repository either through https:

    git clone https://github.com/wspurgin/VendingMachine.git

or with ssh

    git clone git@github.com:wspurgin/VendingMachine.git

##Installing composer
Assuming that you have cloned the repo into the directory 'VendingMachine', switch into that directory and install composer.
    
    cd VendingMachine    

To download **composer** follow its simple [installation instructions](https://getcomposer.org/download/)

## Installing the DB

After you have cloned the repository and installed composer change into the directory and run MySQL with the SQL file to set up the DB.
Assuming that you have cloned the repo into the directory 'VendingMachine':

    cd VendingMachine
    mysql -u yourUser -p < vending.sql

Ensure the DB is created by using something like phpMyAdmin, (or if your a OS X user, I highly recommend [Sequel Pro](http://www.sequelpro.com/)

If none of those are options for you, you can check via the shell

    mysql -u yourUser -p

    mysql> show databases;

You should see somthing like this, but specifically DB 'vending' should be there.

```
+--------------------+
| Database           |
+--------------------+
| information_schema |
| mysql              |
| performance_schema |
| test               |
| vending            |
+--------------------+
5 rows in set (0.00 sec)
```
Next, in the mysql prompt write

    mysql> use vending;

Which will output something like this:

```
Reading table information for completion of table and column names
You can turn off this feature to get a quicker startup with -A

Database changed
```

Finally in the prompt write

    mysql> show tables;

Which, if everything was created successfully should output this:

```
+--------------------+
| Tables_in_vending  |
+--------------------+
| Group_Permissions  |
| Groups             |
| Logs               |
| Machine_Supplies   |
| Machines           |
| Permissions        |
| Products           |
| Team_Members       |
| Teams              |
| Users              |
+--------------------+
10 rows in set (0.00 sec)
```

Finally you can quit the mysql prompt by entering

    mysql> quit

##Configuring init.php

**In order for this application to work you must create an 'init.php' file inside the main directory of this project.**

This file does not exist in the repo and is ignored by git because this file contains sensitive information to connect to you database.
Inside 'init.php' you must define the following in php


```php
<?php

define('DB_HOST', '[your IP address]');
define('DB_NAME', 'vending');
define('DB_USER', '[yourUser]');
define('DB_PASS', '[yourPassword]');

?>
```

Here is an example of an init.php file for this application that connects to local mysql host with a user named 'root' and password as 'password'

```php
<?php

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'vending');
define('DB_USER', 'root');
define('DB_PASS', 'password');

?>
```

##You're Good to Go! (almost)

After all of these steps, you will need to setup a virtual host with Apache to point to the 'pub' directory of your local copy of the repo.

**Note** Depending on what OS you are running, setting up apache 'vhosts' varies. I suggest Googling "Setting up apache vhosts [your OS]" to find a good guide.

However the contents of the vhost can be quite simple. Here is an example vhost for 'local.vending.com'

```apache
<Virtualhost *:80>
    ServerName local.vending.com
    DocumentRoot /path/to/repo/VendingMachine/pub/
  
    <Directory /path/to/repo/VendingMachine/pub/>
        Options FollowSymLinks
        AllowOverride All
        Order Allow,Deny
        Allow from All
    </Directory>


    ErrorLog /path/to/logs/error.log
    LogLevel warn
    # debug, info, notice, warn, error, crit, alert, emerg
    CustomLog /path/to/logs/access.log combined
</Virtualhost>
```

You can edit and add as you see fit, but you will cetainly need the line inside the 'Directory' tag:

    AllowOverride All

This allows the .htaccess file in the 'pub' directory to send the url through the Slim app framework URL router.

You must also ensure the you are loading the Apache Module 'rewrite'.
Depending on which OS you are using, the way to enable this Module differs.
Again depending on you OS there are different ways to check if the rewrite module is enabled, but for most OSs this is a valid way to check your Apche Modules in the shell:

    apachectl -M

This will dump the currently loaded modules in Apache. If you see 'rewrite' in those settings, you should be good to go.

**NOTE** MAMP and XXAMP are reportedly bad at handling overriding even with 'mod_rewrite' enabled.
There are work arounds if you run into issues using MAMP, or XXAMP.
(Though it is my personal 2cents that it's better to run Apache and MySQL locally anyway)

After you have ensured that you have completed all these steps and have Apache properly configured, go ahead and restart apache and get coding!

Installation instructions Last Updated: 04/17/2014

Last Commit that Changed Installation process: 7501ede7d4ab58367b9198f3af9ae6c51ed041a6
