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

// Group Routes
$app->get('/groups', 'getGroups');
$app->post('/groups', 'addGroup');
$app->get('/groups/:id', 'getGroup');
$app->put('/groups/:id', 'updateGroup');
$app->delete('/groups/:id', 'deleteGroup');

// Permission Routes
$app->get('/permissions', 'getPermissions');
$app->post('/permissions', 'addPermission');
$app->get('/permissions/:id', 'getPermission');
$app->put('/permissions/:id', 'updatePermission');
$app->delete('/permissions/:id', 'deletePermission');

// Group_Permissions Routes

# TODO: what's the plan for references? Foreign Keys, just hyperlink?

// Machine Routes
$app->get('/machines', 'getMachines');
$app->post('/machines', 'addMachine');
$app->get('/machines/:id', 'getMachine');
$app->put('/machines/:id', 'updateMachine');
$app->delete('/machines/:id', 'deleteMachine');

// User Routes
$app->get('/users', 'getUsers');
$app->post('/users', 'addUser');
$app->get('/users/:id', 'getUser');
$app->put('/users/:id', 'updateUser');
$app->delete('/users/:id', 'deleteUser');
$app->get('/users/:id/change', function($id) use ($app) {
    $response['page_title'] = "Password change: ".$id;
    $response['href'] = $app->request->getUrl()."/users/".$id."/change";
    $app->render('password_change.html', $response);
});
$app->put('/users/:id/change', 'changePassword');
$app->put('/users/:id/reset', 'resetPassword');

// Home page route
$app->get('/', function() use ($app) {
    $app->render('index.html', array('page_title' => "Home"));
});

$app->run();

// helper functions

// create a connection to the database
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

function session()
{
    // false for disabling guest session, 'user_id' is the Session key
    // that has to exist in order to be a valid session.
    $good_session = getSession(false) && validateSession('user_id');

}

// get the permissions of a specific user
// (good for verifying a session for specific content)
function getUserPermissions($id)
{
    $sql = "SELECT `permission_id`, `description`, `code_name`
    FROM `User_Permissions` INNER JOIN (`Permissions`) ON
    (`User_Permissions`.`permission_id`=`Permissions`.`id`)
    WHERE `user_id`=:id";

    try
    {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $permissions;
    }
    catch (Exception $e)
    {
        // throw the error for calling functions
        throw $e;
    }
}

// function for getting the attribure names for a given relation in SQL
function getRelationKeys($relation)
{
    $dbname = DB_NAME;

    $sql = "SELECT `COLUMN_NAME` 
    FROM `INFORMATION_SCHEMA`.`COLUMNS` 
    WHERE `TABLE_SCHEMA`='$dbname' 
    AND `TABLE_NAME`='$relation'";

    try
    {
        $db = getConnection();
        $stmt = $db->query($sql);
        $col_names = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($col_names as $col)
            $keys[] = $col['COLUMN_NAME'];
        return $keys;
    }
    catch (Exception $e)
    {
        // throw the error for calling functions
        throw $e;
    }
}

function string_gen($length=10)
{
    try
    {
        return substr(md5(rand()), 0, $length);
    }
    catch (Exception $e)
    {
        throw $e;
    }
}

// end of helper functions

function getGroups()
{
    $app = \Slim\Slim::getInstance();
    $sql = "SELECT * FROM Groups";

    try
    {
        $db = getConnection();
        $stmt = $db->query($sql);
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $keys;
        if(empty($groups))
        {
            $response['page_title'] = "No Content";
            $response['keys'] = getRelationKeys('groups');
        }
        else
        {
            $keys = array_keys($groups[0]); //get keys from first 'group'
            $response['keys'] = $keys;
            $response['rows'] = $groups;
            $response['page_title'] = "Groups";
            $response['href'] = $app->request->getUrl()."/groups";
        }
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
        $stmt = $db->prepare($sql);
        $stmt->bindParam('name', $group->name);
        $stmt->execute();

        $last_id = $db->lastInsertId();
        $response['id'] = $last_id;

        $response['success'] = true;
        $response['message'] = "Group added sucessfully";
    }
    catch (Exception $e)
    {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
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
        if(empty($group))
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
        $response['page_title'] = "Errors";
        $response['message'] = $e->getMessage();
        $app->render('error.html', $response);
    }
}

function updateGroup($id)
{
    $app = \Slim\Slim::getInstance();
    $sql = "UPDATE `Groups` SET `name`=:name WHERE `id`=:id";

    try
    {
        $body = $app->request->getBody();
        $group = json_decode($body);

        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':name', $group->name);
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

function deleteGroup($id)
{
    $app = \Slim\Slim::getInstance();
    $sql = "DELETE FROM `Groups` WHERE `id`=:id";
   
    try
    {

        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $response['success'] = true;
        $response['message'] = "Group deleted sucessfully";

        //give base url in response
        $response['href'] = $app->request->getUrl() . '/groups';
    }
    catch(Exception $e)
    {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }
    echo json_encode($response);
}

function getPermissions()
{
    $app = \Slim\Slim::getInstance();
    $sql = "SELECT * FROM `Permissions`";

    try
    {
        $db = getConnection();
        $stmt = $db->query($sql);
        $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if(empty($permissions))
        {
            $response['page_title'] = "No Content";
            $response['keys'] = getRelationKeys('permissions');
        }
        else
        {
            $keys = array_keys($permissions[0]); //get keys from first 'permission'
            $response['keys'] = $keys;
            $response['rows'] = $permissions;
            $response['page_title'] = "Permissions";
            $response['href'] = $app->request->getUrl()."/permissions";
            $app->render('table.html', $response);
        }
    }
    catch(Exception $e)
    {
        // while still debugging
        $response['page_title'] = "Errors";
        $response['message'] = $e->getMessage();
        $app->render('error.html', $response);
    }
}

function addPermission()
{
    $app = \Slim\Slim::getInstance();
    $sql = "INSERT INTO `Permissions`(`description`, `code_name`)
    VALUES (:description, :code_name)";

    try
    {
        # get the request
        $body = $app->request->getBody();
        $permission = json_decode($body);

        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':description', $permission->description);
        $stmt->bindParam(':code_name', $permission->code_name); 
        $stmt->execute();

        $last_id = $db->lastInsertId();
        $response['id'] = $last_id;

        $response['success'] = true;
        $response['message'] = "Permission added sucessfully";
    }
    catch (Exception $e)
    {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }
    echo json_encode($response);
}

function getPermission($id)
{
    $app = \Slim\Slim::getInstance();
    $sql = "SELECT * FROM `Permissions` WHERE id=:id";
    try
    {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $permission = $stmt->fetch(PDO::FETCH_ASSOC);
        if(empty($permission))
            $app->halt(404, "This is not the page you are looking for...");
        else
        {
            $keys = array_keys($permission);
            $response['keys'] = $keys;
            $response['row'] = $permission;
            $name = $permission['code_name'];
            $response['page_title'] = "Permissions: $name";
            $response['href'] = $app->request->getUrl()."/permissions/".$id;
            
            $app->render('individual.html', $response);
        }
    }
    catch (Exception $e)
    {
        // while still debugging
        $response['page_title'] = "Errors";
        $response['message'] = $e->getMessage();
        $app->render('error.html', $response);
    }
}

function updatePermission($id)
{
    $app = \Slim\Slim::getInstance();
    $sql = "UPDATE `Permissions` SET `description`=:description,
    `code_name`=:code_name WHERE `id`=:id";

    try
    {
        $body = $app->request->getBody();
        $permission = json_decode($body);

        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':description', $permission->description);
        $stmt->bindParam(':code_name', $permission->code_name);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $response['success'] = true;
        $response['message'] = "Permission updated sucessfully";
    }
    catch(Exception $e)
    {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
}

function deletePermission($id)
{
    $app = \Slim\Slim::getInstance();
    $sql = "DELETE FROM `Permissions` WHERE `id`=:id";
   
    try
    {

        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $response['success'] = true;
        $response['message'] = "Permission deleted sucessfully";

        //give base url in response
        $response['href'] = $app->request->getUrl() . '/permissions';
    }
    catch(Exception $e)
    {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }
    echo json_encode($response);
}

function getMachines()
{
    $app = \Slim\Slim::getInstance();
    $sql = "SELECT * FROM `Machines`";

    try
    {
        $db = getConnection();
        $stmt = $db->query($sql);
        $machines = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $keys;
        if(empty($machines))
        {
            $response['page_title'] = "No Content";
            $response['keys'] = getRelationKeys('machines');
        }
        else
        {
            $keys = array_keys($machines[0]); //get keys from first 'machine'
            $response['keys'] = $keys;
            $response['rows'] = $machines;
            $response['page_title'] = "Machines";
            $response['href'] = $app->request->getUrl()."/machines";
        }
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

function addMachine()
{
    $app = \Slim\Slim::getInstance();
    $sql = "INSERT INTO `Machines`(`machine_location`) VALUES (:machine_location)";

    try
    {
        # get the request
        $body = $app->request->getBody();
        $machine = json_decode($body);

        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':machine_location', $machine->machine_location);
        $stmt->execute();

        $last_id = $db->lastInsertId();
        $response['id'] = $last_id;

        $response['success'] = true;
        $response['message'] = "Machine added sucessfully";
    }
    catch (Exception $e)
    {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
}

function getMachine($id)
{
    $app = \Slim\Slim::getInstance();
    $sql = "SELECT * FROM `Machines` WHERE id=:id";
    try
    {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $machine = $stmt->fetch(PDO::FETCH_ASSOC);
        if(empty($machine))
            $app->halt(404, "This is not the page you are looking for...");
        else
        {
            $keys = array_keys($machine);
            $response['keys'] = $keys;
            $response['row'] = $machine;
            $name = $machine['machine_location'];
            $response['page_title'] = "Machine: $name";
            $response['href'] = $app->request->getUrl()."/machines/".$id;
            
            $app->render('individual.html', $response);
        }
    }
    catch (Exception $e)
    {
        // while still debugging
        $response['page_title'] = "Errors";
        $response['message'] = $e->getMessage();
        $app->render('error.html', $response);
    }  
}

function updateMachine($id)
{
    $app = \Slim\Slim::getInstance();
    $sql = "UPDATE `Machines` SET `machine_location`=:machine_location
    WHERE `id`=:id";

    try
    {
        $body = $app->request->getBody();
        $machine = json_decode($body);

        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':machine_location', $machine->machine_location);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $response['success'] = true;
        $response['message'] = "Machine updated sucessfully";
    }
    catch(Exception $e)
    {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
}

function deleteMachine($id)
{
    $app = \Slim\Slim::getInstance();
    $sql = "DELETE FROM `Machines` WHERE `id`=:id";
   
    try
    {

        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $response['success'] = true;
        $response['message'] = "Machine deleted sucessfully";

        //give base url in response
        $response['href'] = $app->request->getUrl() . '/machines';
    }
    catch(Exception $e)
    {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }
    echo json_encode($response);
}

function getUsers()
{
    $app = \Slim\Slim::getInstance();
    $sql = "SELECT `id`, `name`, `email`, `group_id`, `balance` FROM `Users`";

    try
    {
        $db = getConnection();
        $stmt = $db->query($sql);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if(empty($users))
        {
            $response['page_title'] = "No Content";
            $response['keys'] = getRelationKeys('users');
        }
        else
        {
            $keys = array_keys($users[0]); //get keys from first 'user'
            $keys[] = "password"; // append password to the end
            $response['keys'] = $keys;
            $response['rows'] = $users;
            $response['page_title'] = "Users";
        }
        $response['href'] = $app->request->getUrl()."/users";
        $app->render('table_user.html', $response);
    }
    catch (Exception $e)
    {
        // while still debugging
        $response['page_title'] = "Errors";
        $response['message'] = $e->getMessage();
        $app->render('error.html', $response);
    }

}

function addUser()
{
    $app = \Slim\Slim::getInstance();
    $sql = "INSERT INTO `Users`(`id`, `password`, `name`, `email`, `group_id`,
    `balance`) VALUES (:id, :password, :name, :email, :group_id, :balance)";

    try
    {
        $body = $app->request->getBody();
        $user = json_decode($body);

        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $user->id);
        $stmt->bindParam(':password', password_hash($user->password, PASSWORD_DEFAULT));
        $stmt->bindParam(':name', $user->name);
        $stmt->bindParam(':email', $user->email);
        $stmt->bindParam(':group_id', $user->group_id);
        $stmt->bindParam(':balance', $user->balance);
        $stmt->execute();

        $response['id'] = $user->id;

        $response['success'] = true;
        $response['message'] = "User added sucessfully";

    }
    catch (Exception $e)
    {
        // while still debugging
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }
    echo json_encode($response);
}

function getUser($id)
{
    $app = \Slim\Slim::getInstance();
    $sql = "SELECT * FROM `Users` WHERE id=:id";
    try
    {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if(empty($user))
            $app->halt(404, "This is not the page you are looking for...");
        else
        {
            $user['password'] = '';
            $keys = array_keys($user);
            $response['keys'] = $keys;
            $response['row'] = $user;
            $name = $user['name'];
            $response['page_title'] = "User: $name";
            $response['href'] = $app->request->getUrl()."/users/".$id;
            
            $app->render('individual.html', $response);
        }
    }
    catch (Exception $e)
    {
        // while still debugging
        $response['page_title'] = "Errors";
        $response['message'] = $e->getMessage();
        $app->render('error.html', $response);
    }  

}

function updateUser($id)
{
    $app = \Slim\Slim::getInstance();
    $sql = "UPDATE `Users` SET `name`=:name, `email`=:email,
    `balance`=:balance WHERE `id`=:id";

    try
    {
        $body = $app->request->getBody();
        $user = json_decode($body);

        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':name', $user->name);
        $stmt->bindParam(':email', $user->email);
        $stmt->bindParam(':balance', $user->balance);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $response['success'] = true;
        $response['message'] = "User updated sucessfully";
    }
    catch(Exception $e)
    {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
}

function deleteUser($id)
{
    $app = \Slim\Slim::getInstance();
    $sql = "DELETE FROM `Users` WHERE `id`=:id";

    try
    {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $response['success'] = true;
        $response['message'] = "User deleted sucessfully";

        //give base url in response
        $response['href'] = $app->request->getUrl() . '/users';
    }
    catch(Exception $e)
    {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }
    echo json_encode($response);
}

function changePassword($id)
{
    $app = \Slim\Slim::getInstance();

    // if(!(session() && validateSession('user_id', $id)))
    //     $app->halt(404);
    try
    {
        $body = $app->request->getBody();
        $password = json_decode($body);

        $db = getConnection();

        // verify user has correct password
        $sql = "SELECT `password` FROM `Users` WHERE `id`=:id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if(empty($user) || password_hash($password->old_password, PASSWORD_DEFAULT) != $user['password'])
        {
            $response['success'] = false;
            $response['message'] = "Invalid password. Action refused";
        }
        else
        {
            $sql = "UPDATE `Users` SET `password`=:password, WHERE `id`=:id";

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':password', password_hash($password->new_password, PASSWORD_DEFAULT));
            $stmt->execute();

            $response['success'] = true;
            $response['message'] = "User password updated sucessfully";
        }
    }
    catch(Exception $e)
    {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }
    echo json_encode($response);
}

// NOT FINISHED TODO:: set up reset
function resetPassword($id)
{
    $app = \Slim\Slim::getInstance();

    // if(!(session() && (validateSession('user_id', $id) || validateSession('change_user', true))))
    //     $app->halt(404);
    try
    {
        $new_password = string_gen();
        $sql = "UPDATE `Users` SET `password`=:password WHERE `id`=:id";
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(":password", password_hash($new_password, PASSWORD_DEFAULT));
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        $response['success'] = true;
        $response['message'] = "New password '$new_password' set for user: $id";
    }
    catch(Exception $e)
    {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }
    echo json_encode($response);
}

?>