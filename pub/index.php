<?php

require('../vendor/autoload.php');
require_once('../init.php');
require_once('../lib/session.php');
require_once('../lib/password.php');

session_cache_limiter(false);


$app = new \Slim\Slim(array('templates.path' => 'templates',));
$app->log->setEnabled(true);

$app->view(new \Slim\Views\Twig());
$app->view->parserOptions = array(
    'charset' => 'utf-8',
    'cache' => realpath('templates/cache'),
    'auto_reload' => true,
    'strict_variables' => false,
    'autoescape' => true
);

$app->view->parserExtensions = array(new \Slim\Views\TwigExtension());

$app->get('/groups', 'getGroups');
$app->post('/groups', 'addGroup');
$app->get('/groups/:id', 'getGroup');
$app->put('/groups/:id', 'updateGroup');

$app->get('/', function() use ($app) {
    $app->render('index.html', array('page_title' => "Home"));
});

$app->run();

function getConnection()
{
    $dbhost = DB_HOST;
    $dbname = DB_NAME;
    $dbuser = DB_USER;
    $dbpass = DB_PASS;

    $dbset = "mysql:host=$dbhost;dbname=$dbname;";

    $db = new PDO($dbset, $dbuser, $dbpass);
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $db;
}

function getGroups()
{
    $app = \Slim\Slim::getInstance();
    $sql = "SELECT * FROM Groups";
    try
    {
        $db = getConnection();
        $stmt = $db->query($sql);
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $app->render('table.html', array('keys' => array('Id', 'Name'),
            'rows' => $groups, 'page_title' => "Groups",
            'href' => $app->request->getUrl()."/groups")
        );
    }
    catch (Exception $e)
    {
        // while still debugging
        $response['message'] = $e->getMessage();
        $app->render('error.html', $response);
    }

}

function addGroup()
{
    $app = \Slim\Slim::getInstance();
    $sql = "INSERT INTO `Groups`(`name`) VALUES (:name)";
    try
    {
        # get the request
        $body = $app->request->getBody();
        $group = json_decode($body);

        $db = getConnection();
        $stmt->prepare($sql);
        $stmt->bindParam('name', $group['name']);
        $stmt->execute();

        $last_id = $db->lastInsetedId();

        getGroup($last_id); 
    }
    catch (Exception $e)
    {
        // while still debugging
        $response['message'] = $e->getMessage();
        $app->render('error.html', $response);
    }
}

function getGroup($id)
{
    $app = \Slim\Slim::getInstance();
    $sql = "SELECT * FROM Groups WHERE id=:id";
    try
    {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        if(!isset($group))
            $app->halt(404, "This is not the page you are looking for...");
        else
        {
            $keys = array_keys($group);
            $response['keys'] = $keys;
            $response['row'] = $group;
            $name = $group['name'];
            $response['page_title'] = "Group: $name";
            $response['href'] = $app->request->getUrl()."/groups/".$id;
            
            $app->render('individual.html', $response);
        }
    }
    catch (Exception $e)
    {
        // while still debugging
        $response['message'] = $e->getMessage();
        $app->render('error.html', $response);
    }
}

function updateGroup($id)
{
    $app = \Slim\Slim::getInstance();
    $sql = "UPDATE `Groups` SET `name`=:name WHERE `id`=:id";
    $response;

    try
    {
        $body = $app->request->getBody();
        $user = json_decode($body);

        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':name', $user->name);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $response['success'] = true;
        $response['message'] = "Group updated sucessfully";
    }
    catch(Exception $e)
    {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
}

?>