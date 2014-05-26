<?php

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../lib/config.php');


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

$twig = $app->view()->getEnvironment();
// Add function to flatten multi-dimensional array
$funct = new Twig_SimpleFunction('flatten', function($arr){
    $return = array();
    if(is_array($arr))
        array_walk_recursive($arr, function($a) use (&$return) { $return[] = $a; });
    else
        $return[] = $arr;
    return $return;
});
$twig->addFunction($funct);

// get all the bootstraped routes from config
foreach ($ROUTES as $route) {
    if($route->type == Route::GET)
        $app->get($route->url, $route->callable);
    elseif ($route->type == Route::POST)
        $app->post($route->url, $route->callable);
    elseif ($route->type == Route::PUT)
        $app->put($route->url, $route->callable);
    elseif ($route->type == Route::DELETE)
        $app->delete($route->url, $route->callable);
    elseif ($route->type == Route::PATCH)
        $app->patch($route->url, $route->callable);
    else
        $app->log("Unsupported route type for '$route->type', on Route $route");
}

// Group Routes
$app->get('/groups', 'getGroups');
$app->get('/groups/:id', 'getGroup');

// Permission Routes
$app->get('/permissions', 'getPermissions');
$app->get('/permissions/:id', 'getPermission');

// Group_Permissions Routes

# TODO: what's the plan for references? Foreign Keys, just hyperlink?

// Machine Routes
$app->get('/machines', 'getMachines');
$app->get('/machines/:id', 'getMachine');

// User Routes
$app->get('/users', 'getUsers');
$app->get('/users/:id', 'getUser');
$app->get('/users/:id/change', function($id) use ($app) {
    $response['page_title'] = "Password change: ".$id;
    $response['href'] = $app->request->getUrl()."/api/users/".$id."/change";
    $app->render('password_change.html', $response);
});

// Product Routes
$app->get('/products', 'getProducts');
$app->get('/products/:id', 'getProduct');

// Team Routes
$app->get('/teams', 'getTeams');
$app->get('/teams/:id', 'getTeam');

// Team Routes
$app->get('/logs', 'getLogs');
$app->get('/logs/:id', 'getLog');


// One to Many Routes
$app->get('/groups/:id/permissions', 'getGroupPermissions');
$app->get('/users/:id/permissions', 'getUserPermissions');
$app->get('/machines/:id/supplies', 'getMachineSupplies');

// Home page route
$app->get('/', function() use ($app) {
    $app->render('index.html', array('page_title' => "Home"));
});

$app->run();

function renderErrors($message=NULL)
{
    if(is_null($message))
        $message = "This is not the page you are looking for...";
    $app = \Slim\Slim::getInstance();
    $response['page_title'] = "Errors";
    $response['message'] = $message;
    $app->render('error.html', $response);
}

function getGroups()
{
    $app = \Slim\Slim::getInstance();
    global $api;
    try
    {
        $groups = $api->getGroups(false);

        $keys;
        if(empty($groups))
        {
            $response['page_title'] = "No Content";
            $response['keys'] = $api->getRelationKeys('groups');
        }
        else
        {
            $keys = array_keys((array)$groups[0]); //get keys from first 'group'
            $response['keys'] = $keys;
            $response['rows'] = $groups;
            $response['page_title'] = "Groups";
        }
        $response['href'] = $app->request->getUrl()."/groups";
        $response['api_href'] = $app->request->getUrl()."/api/groups";
        $app->render('table.html', $response);
    }
    catch (Exception $e)
    {
        // while still debugging
        renderErrors($e->getMessage());
    }
}

function getGroup($id)
{
    $app = \Slim\Slim::getInstance();
    global $api;
    try
    {
        $group = $api->getGroup($id, false);

        if(empty($group))
             renderErrors();
        else
        {
            $keys = array_keys((array)$group);
            $response['keys'] = $keys;
            $response['row'] = $group;
            $name = $group['name'];
            $response['page_title'] = "Group: $name";
            $response['href'] = $app->request->getUrl()."/groups/".$id;
            $response['many_names'] = array('permissions');
            $response['api_href'] = $app->request->getUrl()."/api/groups/".$id;
            
            $app->render('individual.html', $response);
        }
    }
    catch (Exception $e)
    {
        // while still debugging
        renderErrors($e->getMessage());
    }
}

function getPermissions()
{
    $app = \Slim\Slim::getInstance();
    global $api;
    try
    {
        $permissions = $api->getPermissions(false);

        if(empty($permissions))
        {
            $response['page_title'] = "No Content";
            $response['keys'] = $api->getRelationKeys('permissions');
        }
        else
        {
            $keys = array_keys((array)$permissions[0]); //get keys from first 'permission'
            $response['keys'] = $keys;
            $response['rows'] = $permissions;
            $response['page_title'] = "Permissions";
        }
        $response['href'] = $app->request->getUrl()."/permissions";
        $response['api_href'] = $app->request->getUrl()."/api/permissions";
        $app->render('table.html', $response);
    }
    catch(Exception $e)
    {
        // while still debugging
        renderErrors($e->getMessage());
    }
}

function getPermission($id)
{
    $app = \Slim\Slim::getInstance();
    global $api;
    try
    {
        $permission = $api->getPermission($id, false);

        if(empty($permission))
            renderErrors();
        else
        {
            $keys = array_keys((array)$permission);
            $response['keys'] = $keys;
            $response['row'] = $permission;
            $name = $permission['code_name'];
            $response['page_title'] = "Permissions: $name";
            $response['href'] = $app->request->getUrl()."/permissions/".$id;
            $response['api_href'] = $app->request->getUrl()."/api/permissions/".$id;
            
            $app->render('individual.html', $response);
        }
    }
    catch (Exception $e)
    {
        // while still debugging
        renderErrors($e->getMessage());
    }
}

function getMachines()
{
    $app = \Slim\Slim::getInstance();
    global $api;
    try
    {
        $machines = $api->getMachines(false);

        $keys;
        if(empty($machines))
        {
            $response['page_title'] = "No Content";
            $response['keys'] = $api->getRelationKeys('machines');
        }
        else
        {
            $keys = array_keys((array)$machines[0]); //get keys from first 'machine'
            $response['keys'] = $keys;
            $response['rows'] = $machines;
            $response['page_title'] = "Machines";
        }
        $response['href'] = $app->request->getUrl()."/machines";
        $response['api_href'] = $app->request->getUrl()."/api/machines";
        $app->render('table.html', $response);
    }
    catch (Exception $e)
    {
        // while still debugging
        renderErrors($e->getMessage());
    }
}

function getMachine($id)
{
    $app = \Slim\Slim::getInstance();
    global $api;
    try
    {
        $machine = $api->getMachine($id, false);

        if(empty($machine))
            renderErrors();
        else
        {
            $keys = array_keys((array)$machine);
            $response['keys'] = $keys;
            $response['row'] = $machine;
            $name = $machine['machine_location'];
            $response['page_title'] = "Machine: $name";
            $response['many_names'] = array('supplies');
            $response['href'] = $app->request->getUrl()."/machines/".$id;
            $response['api_href'] = $app->request->getUrl()."/api/machines/".$id;
            
            $app->render('individual.html', $response);
        }
    }
    catch (Exception $e)
    {
        // while still debugging
        renderErrors($e->getMessage());
    }  
}

function getUsers()
{
    $app = \Slim\Slim::getInstance();
    global $api;
    try
    {
        $users = $api->getUsers(false);

        if(empty($users))
        {
            $response['page_title'] = "No Content";
            $response['keys'] = $api->getRelationKeys('users');
        }
        else
        {
            $keys = array_keys((array)$users[0]); //get keys from first 'user'
            $response['keys'] = $keys;
            $response['rows'] = $users;
            $response['page_title'] = "Users";
        }
        $response['href'] = $app->request->getUrl()."/users";
        $response['api_href'] = $app->request->getUrl()."/api/users";
        $app->render('table_user.html', $response);
    }
    catch (Exception $e)
    {
        // while still debugging
        renderErrors($e->getMessage());
    }
}

function getUser($id)
{
    $app = \Slim\Slim::getInstance();
    global $api;
    try
    {
        $user = $api->getUser($id, false);

        if(empty($user))
            renderErrors();
        else
        {
            $keys = array_keys((array)$user);
            $response['keys'] = $keys;
            $response['row'] = $user;
            $name = $user['name'];
            $response['page_title'] = "User: $name";
            $response['href'] = $app->request->getUrl()."/users/".$id;
            $response['many_names'] = array('permissions');
            $response['api_href'] = $app->request->getUrl()."/api/users/".$id;
            
            $app->render('individual.html', $response);
        }
    }
    catch (Exception $e)
    {
        // while still debugging
        renderErrors($e->getMessage());
    }  
}

function getProducts()
{
    $app = \Slim\Slim::getInstance();
    global $api;
    try
    {
        $products = $api->getProducts(false);

        $keys;
        if(empty($products))
        {
            $response['page_title'] = "No Content";
            $response['keys'] = $api->getRelationKeys('products');
        }
        else
        {
            $keys = array_keys((array)$products[0]); //get keys from first 'product'
            $response['keys'] = $keys;
            $response['rows'] = $products;
            $response['page_title'] = "Products";
        }
        $response['href'] = $app->request->getUrl()."/products";
        $response['api_href'] = $app->request->getUrl()."/api/products";
        $app->render('table.html', $response);
    }
    catch (Exception $e)
    {
        // while still debugging
        renderErrors($e->getMessage());
    }
}

function getProduct($id)
{
    $app = \Slim\Slim::getInstance();
    global $api;
    try
    {
        $product = $api->getProduct($id, false);

        if(empty($product))
            renderErrors();
        else
        {
            $keys = array_keys((array)$product);
            $response['keys'] = $keys;
            $response['row'] = $product;
            $name = $product['name'];
            $response['page_title'] = "Product: $name";
            $response['href'] = $app->request->getUrl()."/products/".$id;
            $response['api_href'] = $app->request->getUrl()."/api/products/".$id;
            
            $app->render('individual.html', $response);
        }
    }
    catch (Exception $e)
    {
        // while still debugging
        renderErrors($e->getMessage());
    }
}

function getTeams()
{
    $app = \Slim\Slim::getInstance();
    global $api;
    try
    {
        $teams = $api->getTeams(false);

        $keys;
        if(empty($teams))
        {
            $response['page_title'] = "No Content";
            $response['keys'] = $api->getRelationKeys('teams');
        }
        else
        {
            $keys = array_keys((array)$teams[0]); //get keys from first 'team'
            $response['keys'] = $keys;
            $response['rows'] = $teams;
            $response['page_title'] = "Teams";
        }
        $response['href'] = $app->request->getUrl()."/teams";
        $response['api_href'] = $app->request->getUrl()."/api/teams";
        $app->render('table.html', $response);
    }
    catch (Exception $e)
    {
        // while still debugging
        $response['page_title'] = "Errors";
        $response['message'] = $e->getMessage();
        $app->render('error.html', $response);
    }
}

function getTeam($id)
{
    $app = \Slim\Slim::getInstance();
    global $api;
    try
    {
        $team = $api->getTeam($id, false);

        if(empty($team))
            renderErrors();
        else
        {
            $keys = array_keys((array)$team);
            $response['keys'] = $keys;
            $response['row'] = $team;
            $name = $team['team_name'];
            $response['page_title'] = "Teams: $name";
            $response['href'] = $app->request->getUrl()."/teams/".$id;
            $response['api_href'] = $app->request->getUrl()."/api/teams/".$id;
            
            $app->render('individual.html', $response);
        }
    }
    catch (Exception $e)
    {
        // while still debugging
        renderErrors($e->getMessage());
    }
}

function getLogs()
{
    $app = \Slim\Slim::getInstance();
    global $api;
    try
    {
        $logs = $api->getLogs(false);

        $keys;
        if(empty($logs))
        {
            $response['page_title'] = "No Content";
            $response['keys'] = $api->getRelationKeys('logs');
        }
        else
        {
            $keys = array_keys((array)$logs[0]); //get keys from first 'log'
            $response['keys'] = $keys;
            $response['rows'] = $logs;
            $response['page_title'] = "Logs";
        }
        $response['href'] = $app->request->getUrl()."/logs";
        $response['api_href'] = $app->request->getUrl()."/api/logs";
        $app->render('table.html', $response);
    }
    catch (Exception $e)
    {
        // while still debugging
        renderErrors($e->getMessage());
    }
}

function getLog($id)
{
    $app = \Slim\Slim::getInstance();
    global $api;
    try
    {
        $log = $api->getLog($id, false);

        if(empty($log))
            renderErrors();
        else
        {
            $keys = array_keys((array)$log);
            $response['keys'] = $keys;
            $response['row'] = $log;
            $name = $log['id'] . ':' . $log['date_purchased'];
            $response['page_title'] = "Logs: $name";
            $response['href'] = $app->request->getUrl()."/logs/".$id;
            $response['api_href'] = $app->request->getUrl()."/api/logs/".$id;
            
            $app->render('individual.html', $response);
        }
    }
    catch (Exception $e)
    {
        // while still debugging
        renderErrors($e->getMessage());
    }
}

function getGroupPermissions($id)
{
    $app = \Slim\Slim::getInstance();
    global $api;
    try
    {
        list($group_permissions, $permissions) = $api->getGroupPermissions(
            $id, 
            false
        );

        if(empty($group_permissions))
        {
            $response['page_title'] = "No Content";
            # explicitly state key names (for now)
            $response['keys'] = array('group', 'permission');
            $response['name'] = $api->getGroup($id, false)['name'];
        }
        else
        {
            $keys = array_keys((array)$group_permissions[0]); // keys from first row
            $response['keys'] = $keys;
            $response['rows'] = $group_permissions;
            
            $name = $group_permissions[0]['group'];
            $response['page_title'] = "Group: $name - Permissions";
            $response['name'] = $name;
        }
        $response['many'] = $permissions;
        $response['ref_href'] = $app->request->getUrl()."/groups/$id";
        $response['href'] = $app->request->getUrl()."/groups/$id/permissions";
        $response['api_href'] = $app->request->getUrl()."/api/groups/$id/permissions";
        $app->render('one_to_many.html', $response);
    }
    catch (Exception $e)
    {
        // while still debugging
        renderErrors($e->getMessage());
    }
}

function getUserPermissions($id)
{
    $app = \Slim\Slim::getInstance();
    global $api;
    try
    {
        list($user_permissions, $permissions) = $api->getUserPermissions(
            $id,
            false
        );

        if(empty($user_permissions))
        {
            $response['page_title'] = "No Content";
            #explicitly state key names (for now)
            $response['keys'] = array('User', 'Permission');
            $response['name'] = $api->getUser($id, false)['name'];
        }
        else
        {
            $keys = array_keys((array) $user_permissions[0]);
            $response['keys'] = $keys;
            $response['rows'] = $user_permissions;
            
            $name = $user_permissions[0]['user'];
            $response['page_title'] = "User: $name - Permissions";
            $response['name'] = $name;

        }
        $response['many'] = $permissions;
        $response['ref_href'] = $app->request->getUrl()."/users/$id";
        $response['href'] = $app->request->getUrl()."/users/$id/permissions";
        $response['api_href'] = $app->request->getUrl()."/api/users/$id/permissions";
        $app->render('one_to_many.html', $response);
    }
    catch (Exception $e)
    {
        renderErrors($e->getMessage());
    }
}

function getMachineSupplies($id)
{
    $app = \Slim\Slim::getInstance();
    global $api;
    try
    {
        list($machine_supplies, $supplies) = $api->getMachineSupplies(
            $id, 
            false
        );

        if(empty($machine_supplies))
        {
            $response['page_title'] = "No Content";
            # explicitly state key names (for now)
            $response['keys'] = array('Machine', 'Supplies');
            $response['name'] = $api->getMachine($id, false)['machine_location'];
        }
        else
        {
            $keys = array_keys((array)$machine_supplies[0]); // keys from first row
            $response['keys'] = $keys;
            $response['rows'] = $machine_supplies;
            
            $name = $machine_supplies[0]['machine'];
            $response['page_title'] = "Machine: $name - supplies";
            $response['name'] = $name;
        }
        $response['many'] = $supplies;
        $response['ref_href'] = $app->request->getUrl()."/machines/$id";
        $response['href'] = $app->request->getUrl()."/machines/$id/supplies";
        $response['api_href'] = $app->request->getUrl()."/api/machines/$id/supplies";
        $app->render('machine_supplies.html', $response);
    }
    catch (Exception $e)
    {
        // while still debugging
        renderErrors($e->getMessage());
    }
}