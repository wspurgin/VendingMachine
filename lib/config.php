<?
require_once('api.php');
require_once('routes.php');

$session_validator = array("user_id");
$api = new Api($session_validator);

/*
Example route
    $myroute = new Route([url], [route request method type (e.g. Route::GET)], [callable (i.e 'foo' for function foo]);
*/

// Initalizing routes for application
$ROUTES = array(
    new Route('/api/groups', Route::POST, array($api, 'addGroup')),
    new Route('/api/groups/:id', Route::PUT, array($api, 'updateGroup')),
    new Route('/api/groups/:id', Route::DELETE, array($api, 'deleteGroup')),
    new Route('/api/permissions', Route::POST, array($api, 'addPermission')),
    new Route('/api/permissions/:id', Route::PUT, array($api, 'updatePermission')),
    new Route('/api/permissions/:id', Route::DELETE, array($api, 'deletePermission')),
    new Route('/api/machines', Route::POST, array($api, 'addMachine')),
    new Route('/api/machines/:id', Route::PUT, array($api, 'updateMachine')),
    new Route('/api/machines/:id', Route::DELETE, array($api, 'deleteMachine')),
    new Route('/api/users', Route::POST, array($api, 'addUser')),
    new Route('/api/users/:id', Route::PUT, array($api, 'updateUser')),
    new Route('/api/users/:id', Route::DELETE, array($api, 'deleteUser')),
    new Route('/api/users/:id/change', Route::PATCH, array($api, 'changePassword')),
    new Route('/api/users/:id/reset', Route::PATCH, array($api, 'resetPassword')),
    new Route('/api/products', Route::POST, array($api, 'addProduct')),
    new Route('/api/products/:id', Route::PUT, array($api, 'updateProduct')),
    new Route('/api/products/:id', Route::DELETE, array($api, 'deleteProduct')),
    new Route('/api/teams', Route::POST, array($api, 'addTeam')),
    new Route('/api/teams/:id', Route::PUT, array($api, 'updateTeam')),
    new Route('/api/teams/:id', Route::DELETE, array($api, 'deleteTeam')),
);
