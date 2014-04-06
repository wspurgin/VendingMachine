<?
/**
* NightKnights Api Class
* 
* @author Will Spurgin
*/

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../init.php');
require_once('db.php');
require_once('password.php');
require_once('session.php');

Class Api
{

    private $db;

    private $session_validation;

    // function for getting session (though disabling guest sessions)
    // and validating the session.
    public function session()
    {
        // 'false' for disabling guest sessions
        return _session($this->session_validation, false);
    }

    public function __construct($session_validation=NULL)
    {
        $dbhost = DB_HOST;
        $dbname = DB_NAME;
        $dbuser = DB_USER;
        $dbpass = DB_PASS;

        $this->db = new Db($dbhost, $dbname, $dbuser, $dbpass);
        $this->session_validation = (array)$session_validation;
    }

    public function __clone()
    {
        return clone $this;
    }

    // only meant as api
    public function loginUser()
    {
        $app = \Slim\Slim::getInstance();
        $response = array();
        try
        {
            $body = $app->request->getBody();
            $login = json_decode($body);
            if(empty($login))
                throw new Exception("Invalid json: '$body'", 1);
                
            $sql = "SELECT * FROM `Users` WHERE `id`=:id";

            $user = $this->db->select(
                $sql,
                array(":id" => $login->id),
                false # to get only 1 row (as this should only match 1 row)
            );
            if(empty($user))
                throw new Exception("Invalid Credentials", 1);
            else
            {
                $check = Password::check($user->password, $login->password);
                if(!$check)
                    throw new Exception("Invalid Credentials", 1);
                else
                {
                    // user authentication completed. Start session
                    newSession(md5(SALT.$user->username));
                    $_SESSION['user_id'] = $user->id;
                    $_SESSION['name'] = $user->name;
                    $_SESSION['permissions'] = getUserPermissions($user->id);
                    $response['success'] = true;
                    $response['message'] = "$user->name logged in successfully";
                    $response['request'] = $_SESSION;
                }
            }
        }
        catch(PDOException $e)
        {
            $response['success'] = false;
            $app->log->error($e->getMessage());
            $response['message'] = $e->getMessage();

            $app->halt(404, json_encode($response));

        }
        catch(Exception $e)
        {
            $response['success'] = false;
            $app->log->error($e->getMessage());
            $response['message'] = $e->getMessage();
            
            // add message while debugging
            $app->halt(404, json_encode($response));
        }
        echo json_encode($response);
    }

    public function logoutUser()
    {
        $app = \Slim\Slim::getInstance();
        if (!$this->session())
            $app->halt(404);
        try
        {
            destroySession();
            $response['success'] = true;
            $response['message'] = "User logged out.";
        }
        catch(PDOException $e)
        {
            $response['success'] = false;
            $app->log->error($e->getMessage());
            $response['message'] = $e->getMessage();

            $app->halt(404, json_encode($response));

        }
        catch(Exception $e)
        {
            $response['success'] = false;
            $app->log->error($e->getMessage());
            $response['message'] = $e->getMessage();
            
            // add message while debugging
            $app->halt(404, json_encode($response));
        }
        echo json_encode($response);
    }

    public function getUserPermissions($id)
    {
        $sql = "SELECT `permission_id`, `description`, `code_name`
        FROM `User_Permissions` INNER JOIN (`Permissions`) ON
        (`User_Permissions`.`permission_id`=`Permissions`.`id`)
        WHERE `user_id`=:id";

        try
        {
            $permissions = $this->db->select($sql, array(":id" => $id));
            return $permissions;
        }
        catch (Exception $e)
        {
            // throw the error for calling functions
            throw $e;
        }
    }

    // function for getting the attribure names for a given relation in SQL
    public function getRelationKeys($relation)
    {
        $dbname = DB_NAME;

        $sql = "SELECT `COLUMN_NAME` 
        FROM `INFORMATION_SCHEMA`.`COLUMNS` 
        WHERE `TABLE_SCHEMA`='$dbname' 
        AND `TABLE_NAME`='$relation'";

        try
        {
            $col_names = $this->db->select($sql);

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

    public function string_gen($length=10)
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

    public function getGroups($json_request=false)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "SELECT * FROM Groups";

        try
        {
            $groups = $this->db->select($sql);

            if($json_request)
                echo json_encode($groups);
            else
                return $groups;
        }
        catch (Exception $e)
        {
            $response['message'] = $e->getMessage();
            // while still debugging
            if($json_request)
                echo json_encode($response);
            else
                throw $e;
        }
    }

    // this function is only meant to be an api json request
    public function addGroup()
    {
        $app = \Slim\Slim::getInstance();
        $sql = "INSERT INTO `Groups`(`name`) VALUES (:name)";

        try
        {
            # get the request
            $body = $app->request->getBody();
            $group = json_decode($body);
            if(empty($group))
                throw new Exception("Invalid JSON '$body'", 1);

            $last_id = $this->db->insert($sql, array(":name" => $group->name));
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

    public function getGroup($id, $json_request=false)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "SELECT * FROM Groups WHERE id=:id";
        try
        {
            $group = $this->db->select($sql, array(":id" => $id));
            if($json_request)
                echo json_encode($group);
            else
                return $group;
        }
        catch (Exception $e)
        {
            $response['message'] = $e->getMessage();
            // while still debugging
            if($json_request)
                echo json_encode($response);
            else
                throw $e;
        }
    }

    // this function is only meant to be an api json request
    public function updateGroup($id)
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

            $this->db->update($sql, array(
                ":name" => $group->name,
                ":id" => $id
            ));

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

    // this function is only meant to be an api json request
    public function deleteGroup($id)
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

    public function getPermissions($json_request=false)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "SELECT * FROM `Permissions`";

        try
        {
            $permissions = $this->select($sql);

            if($json_request)
                echo json_encode($permissions);
            else
                return $permissions;
        }
        catch (Exception $e)
        {
            $response['message'] = $e->getMessage();
            // while still debugging
            if($json_request)
                echo json_encode($response);
            else
                throw $e;
        }
    }

    public function addPermission()
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

    public function getPermission($id)
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

    public function updatePermission($id)
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

    public function deletePermission($id)
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

    public function getMachines()
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
            }
            $response['href'] = $app->request->getUrl()."/machines";
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

    public function addMachine()
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

    public function getMachine($id)
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

    public function updateMachine($id)
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

    public function deleteMachine($id)
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

    public function getUsers()
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

    public function addUser()
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

    public function getUser($id)
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

    public function updateUser($id)
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

    public function deleteUser($id)
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

    public function changePassword($id)
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
    public function resetPassword($id)
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

    public function getProducts()
    {
        $app = \Slim\Slim::getInstance();
        $sql = "SELECT * FROM `Products`";

        try
        {
            $db = getConnection();
            $stmt = $db->query($sql);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $keys;
            if(empty($products))
            {
                $response['page_title'] = "No Content";
                $response['keys'] = getRelationKeys('products');
            }
            else
            {
                $keys = array_keys($products[0]); //get keys from first 'product'
                $response['keys'] = $keys;
                $response['rows'] = $products;
                $response['page_title'] = "Products";
            }
            $response['href'] = $app->request->getUrl()."/products";
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

    public function addProduct()
    {
        $app = \Slim\Slim::getInstance();
        $sql = "INSERT INTO `Products`(`sku`, `name`, `vendor`, `cost`) VALUES (:sku, :name, :vendor, :cost)";

        try
        {
            # get the request
            $body = $app->request->getBody();
            $product = json_decode($body);
                if(empty($product))
                    throw new Exception("Invalid json '$body'", 1);
                    

            $db = getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':sku', $product->sku);
            $stmt->bindParam(':name', $product->name);
            $stmt->bindParam(':vendor', $product->vendor);
            $stmt->bindParam(':cost', $product->cost);
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

    public function getProduct($id)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "SELECT * FROM `Products` WHERE `id`=:id";

        try
        {
            $db = getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if(empty($product))
                throw new Exception("No content", 1);
            else
            {
                $keys = array_keys($product);
                $response['keys'] = $keys;
                $response['row'] = $product;
                $name = $product['name'];
                $response['page_title'] = "Product: $name";
                $response['href'] = $app->request->getUrl()."/products/".$id;
                
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

    public function updateProduct($id)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "UPDATE `Products` SET `sku`=:sku, `name`=:name, `vendor`=:vendor,
        `cost`=:cost WHERE `id`=:id";

        try
        {
            $body = $app->request->getBody();
            $product = json_decode($body);
            if(empty($product))
                throw new Exception("Invalid json: '$body'", 1);

            $db = getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':sku', $product->sku);
            $stmt->bindParam(':name', $product->name);
            $stmt->bindParam(':vendor', $product->vendor);
            $stmt->bindParam(':cost', $product->cost);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $response['success'] = true;
            $response['message'] = "Product updated sucessfully";
        }
        catch(Exception $e)
        {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }

        echo json_encode($response);
    }

    public function deleteProduct($id)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "DELETE FROM `Products` WHERE `id`=:id";

        try
        {
            $db = getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $response['success'] = true;
            $response['message'] = "Product deleted sucessfully";

            //give base url in response
            $response['href'] = $app->request->getUrl() . '/products';
        }
        catch(Exception $e)
        {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }
        echo json_encode($response);
    }

    public function getTeams()
    {
        $app = \Slim\Slim::getInstance();
        $sql = "SELECT * FROM `Teams`";

        try
        {
            $db = getConnection();
            $stmt = $db->query($sql);
            $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $keys;
            if(empty($teams))
            {
                $response['page_title'] = "No Content";
                $response['keys'] = getRelationKeys('teams');
            }
            else
            {
                $keys = array_keys($teams[0]); //get keys from first 'team'
                $response['keys'] = $keys;
                $response['rows'] = $teams;
                $response['page_title'] = "Teams";
            }
            $response['href'] = $app->request->getUrl()."/teams";
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

    public function addTeam()
    {
        $app = \Slim\Slim::getInstance();
        $app->contentType('application/json');
        $sql = "INSERT INTO `Teams`(`team_name`, `class`, `expiration_date`,
            `team_balance`)
        VALUES (:team_name, :class, :expiration_date, :team_balance)";

        try
        {
            # get the request
            $body = $app->request->getBody();
            $team = json_decode($body);
                if(empty($team))
                    throw new Exception("Invalid json '$body'", 1);

            $db = getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':team_name', $team->team_name);
            $stmt->bindParam(':class', $team->class);
            $stmt->bindParam(':expiration_date', $team->expiration_date);
            $stmt->bindParam(':team_balance', $team->team_balance);
            $stmt->execute();

            $last_id = $db->lastInsertId();
            $response['id'] = $last_id;

            $response['success'] = true;
            $response['message'] = "Team added sucessfully";
        }
        catch (Exception $e)
        {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }

        echo json_encode($response);
    }

    public function getTeam($id)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "SELECT * FROM `Teams` WHERE `id`=:id";

        try
        {
            $db = getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $team = $stmt->fetch(PDO::FETCH_ASSOC);

            if(empty($team))
                throw new Exception("No content", 1);
            else
            {
                $keys = array_keys($team);
                $response['keys'] = $keys;
                $response['row'] = $team;
                $name = $team['team_name'];
                $response['page_title'] = "Teams: $name";
                $response['href'] = $app->request->getUrl()."/teams/".$id;
                
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

    public function updateTeam($id)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "UPDATE `Teams` SET `team_name`=:team_name, `class`=:class,
        `expiration_date`=:expiration_date, `team_balance`=:team_balance
        WHERE `id`=:id";

        try
        {
            $body = $app->request->getBody();
            $team = json_decode($body);
            if(empty($team))
                throw new Exception("Invalid json: '$body'", 1);

            $db = getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':team_name', $team->team_name);
            $stmt->bindParam(':class', $team->class);
            $stmt->bindParam(':expiration_date', $team->expiration_date);
            $stmt->bindParam(':team_balance', $team->team_balance);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $response['success'] = true;
            $response['message'] = "Team updated sucessfully";
        }
        catch(Exception $e)
        {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }

        echo json_encode($response);
    }

    public function deleteTeam($id)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "DELETE FROM `Teams` WHERE `id`=:id";

        try
        {
            $db = getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $response['success'] = true;
            $response['message'] = "Team deleted sucessfully";

            //give base url in response
            $response['href'] = $app->request->getUrl() . '/teams';
        }
        catch(Exception $e)
        {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }
        echo json_encode($response);
    }

    public function getLogs()
    {
        $app = \Slim\Slim::getInstance();
        $sql = "SELECT * FROM `Logs`";

        try
        {
            $db = getConnection();
            $stmt = $db->query($sql);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $keys;
            if(empty($logs))
            {
                $response['page_title'] = "No Content";
                $response['keys'] = getRelationKeys('logs');
            }
            else
            {
                $keys = array_keys($logs[0]); //get keys from first 'log'
                $response['keys'] = $keys;
                $response['rows'] = $logs;
                $response['page_title'] = "Logs";
            }
            $response['href'] = $app->request->getUrl()."/logs";
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

    public function addLog()
    {
        $app = \Slim\Slim::getInstance();
        $app->contentType('application/json');
        $sql = "INSERT INTO `Logs`(`user_id`, `product_id`, `machine_id`,
            `date_purchased`)
        VALUES (:user_id, :product_id, :machine_id, :date_purchased)";

        try
        {
            # get the request
            $body = $app->request->getBody();
            $log = json_decode($body);
                if(empty($log))
                    throw new Exception("Invalid json '$body'", 1);

            $db = getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $log->user_id);
            $stmt->bindParam(':product_id', $log->product_id);
            $stmt->bindParam(':machine_id', $log->machine_id);
            $stmt->bindParam(':date_purchased', $log->date_purchased);
            $stmt->execute();

            $last_id = $db->lastInsertId();
            $response['id'] = $last_id;

            $response['success'] = true;
            $response['message'] = "Log added sucessfully";
        }
        catch (Exception $e)
        {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }

        echo json_encode($response);
    }

    public function getLog($id)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "SELECT * FROM `Logs` WHERE `id`=:id";

        try
        {
            $db = getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $log = $stmt->fetch(PDO::FETCH_ASSOC);

            if(empty($log))
                throw new Exception("No content", 1);
            else
            {
                $keys = array_keys($log);
                $response['keys'] = $keys;
                $response['row'] = $log;
                $name = $log['id'] + ':' + $log['date_purchased'];
                $response['page_title'] = "Logs: $name";
                $response['href'] = $app->request->getUrl()."/logs/".$id;
                
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

    public function updateLog($id)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "UPDATE `Logs` SET `user_id`=:user_id, `product_id`=:product_id,
        `machine_id`=:machine_id, `date_purchased`=:date_purchased
        WHERE `id`=:id";

        try
        {
            $body = $app->request->getBody();
            $log = json_decode($body);
            if(empty($log))
                throw new Exception("Invalid json: '$body'", 1);

            $db = getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $log->user_id);
            $stmt->bindParam(':product_id', $log->product_id);
            $stmt->bindParam(':machine_id', $log->machine_id);
            $stmt->bindParam(':date_purchased', $log->date_purchased);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $response['success'] = true;
            $response['message'] = "Log updated sucessfully";
        }
        catch(Exception $e)
        {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }

        echo json_encode($response);
    }

    public function deleteLog($id)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "DELETE FROM `Logs` WHERE `id`=:id";

        try
        {
            $db = getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $response['success'] = true;
            $response['message'] = "Log deleted sucessfully";

            //give base url in response
            $response['href'] = $app->request->getUrl() . '/logs';
        }
        catch(Exception $e)
        {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }
        echo json_encode($response);
    }

    public function getGroupPermissions($id)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "SELECT g.`name` AS `group`, p.`description` AS `permission`
        FROM `Groups` g
        INNER JOIN `Group_Permissions` pg ON (g.`id`=pg.`group_id`)
        INNER JOIN `Permissions` p ON (pg.`permission_id`=p.`id`)
        WHERE g.`id`=:id";

        try
        {
            $db = getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $group = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if(empty($group))
                $app->halt(404, "This is not the page you are looking for...");
            else
            {
                $stmt = $db->query("SELECT `id` AS `value`, `description` AS `name`
                FROM `Permissions`");
                
                $many = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $response['many'] = $many;

                $keys = array_keys($group[0]); // keys from first row
                $response['keys'] = $keys;
                $response['rows'] = $group;
                
                $name = $group[0]['group'];
                $response['ref_herf'] = $app->request->getUrl()."/groups/$id";
                $response['page_title'] = "Group: $name - Permissions";
                $response['name'] = $name;
                $response['href'] = $app->request->getUrl()."/groups/$id/permissions";
                
                $app->render('one_to_many.html', $response);
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