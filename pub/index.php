<?php

require('../vendor/autoload.php');
require_once('../init.php');
require_once('../lib/session.php');
require_once('../lib/password.php');

$app = new \Slim\Slim();

$app->get('/hello/:name', function ($name) {
    echo "Hello, $name";
});

$app->get('/', function() {
    echo "<h1>Welcome! It Works</h1>";
});

$app->run();

function getConnection()
{
    $dbhost = DB_HOST;
    $dbname = DB_NAME;
    $dbuser = DB_USER;
    $dbpass = DB_PASS;
    $dbchar = DB_CHAR;

    $db = new PDO('mysql:host=$dbhost;dbname=$dbname;charset=$dbchar', $dbuser, $dbpass);
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $db;
}

function getGroups()
{
    $sql = "SELECT * FROM Groups ORDER BY `name`";
    try
    {
        $db = getConnection();
        $stmt = $db->query($sql);
        
        
    }
    catch (PDOException $e)
    {
        
    }
}

?>