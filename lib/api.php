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

    public function __construct($session_validation=NULL, $return_as_array=false)
    {
        $dbhost = DB_HOST;
        $dbname = DB_NAME;
        $dbuser = DB_USER;
        $dbpass = DB_PASS;

        $this->db = new Db($dbhost, $dbname, $dbuser, $dbpass, $return_as_array);
        $this->session_validation = (array)$session_validation;
    }

    public function __clone()
    {
        return clone $this;
    }

    //Helper functions

    // function for getting session (though disabling guest sessions)
    // and validating the session.
    public function session()
    {
        // 'false' for disabling guest sessions
        return _session($this->session_validation, false);
    }

    public function switchDbReturnType()
    {
        $this->db->setSelectAsArray(!$this->db->getSelectAsArray());
    }

    // This function requires that the session already has the user_id
    private function loadSessionPermissions($reload=false)
    {
        $sql = "SELECT DISTINCT(`code_name`) AS `code_name`
        FROM `Users` u
        INNER JOIN `Group_Permissions` gp
        ON (u.`group_id`=gp.`group_id`)
        LEFT JOIN `User_Permissions` up
        ON (u.`id`=up.`user_id`)
        INNER JOIN `Permissions` p
        ON (up.`permission_id`=p.`id` OR gp.`permission_id`=p.`id`)
        WHERE u.`id`=:id";

        if(!isset($_SESSION['permissions']) || $reload)
        {
            $permissions = $this->db->select(
                $sql, array(":id" => $_SESSION['user_id'])
            );

            foreach ($permissions as $p)
                $_SESSION['permissions'][$p['code_name']] = true;
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
            // throw the error for calling functions (for now no extra logging)
            throw $e;
        }
    }

    public static function string_gen($length=10)
    { return substr(md5(rand()), 0, $length); }

    // end of helper functions

    /**
    *   Beginning of Main api code.
    *
    *   General Notes:
    *   This api acts as a RESTful API for the VendingMachine project.
    *   For an explanation of REST see
    *       (http://en.wikipedia.org/wiki/Representational_state_transfer).
    *   In addition to serving REST endpoints. This API is also meant to be
    *   easily usable with other PHP code. Thus there are several paramaters
    *   in many functions entitled '$json_request'. This is to allow PHP code
    *   (such as that found in pub/index.php) to access the data directly and
    *   pass it on to the Twig PHP templating engine.
    *
    *   Developer Notes:
    *   These functions are grouped around specific models.
    *   It should be noted that these models' functions are implemented
    *   almosted exactly the same way. Therefore this would be a good place
    *   to refactor. Possible by abstracting these models away in a more 
    *   OOP oriented style.
    */

    // only meant as api json request
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
                    $this->loadSessionPermissions($user->id);
                    $response['success'] = true;
                    $response['message'] = "$user->name logged in successfully";
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

    public function getGroups($json_request=true)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "SELECT * FROM Groups";

        try
        {
            $groups = $this->db->select($sql);

            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($groups);
            }
            else
                return $groups;
        }
        catch (Exception $e)
        {
            $response['message'] = $e->getMessage();
            // while still debugging
            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($response);
            }
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
        $app->contentType('application/json');
        echo json_encode($response);
    }

    public function getGroup($id, $json_request=true)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "SELECT * FROM Groups WHERE id=:id";
        try
        {
            $group = $this->db->select($sql, array(":id" => $id), false);
            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($group);
            }
            else
                return $group;
        }
        catch (Exception $e)
        {
            $response['message'] = $e->getMessage();
            // while still debugging
            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($response);
            }
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
            # get the request
            $body = $app->request->getBody();
            $group = json_decode($body);
            if(empty($group))
                throw new Exception("Invalid JSON '$body'", 1);


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
        $app->contentType('application/json');
        echo json_encode($response);
    }

    // this function is only meant to be an api json request
    public function deleteGroup($id)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "DELETE FROM `Groups` WHERE `id`=:id";
       
        try
        {

            $this->db->delete($sql, array(':id' => $id));

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
        $app->contentType('application/json');
        echo json_encode($response);
    }

    public function getPermissions($json_request=true)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "SELECT * FROM `Permissions`";

        try
        {
            $permissions = $this->db->select($sql);

            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($permissions);
            }
            else
                return $permissions;
        }
        catch (Exception $e)
        {
            $response['message'] = $e->getMessage();
            // while still debugging
            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($response);
            }
            else
                throw $e;
        }
    }

    // only meant to be json api request.
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
            if(empty($permission))
                throw new Exception("Invalid JSON '$body'", 1);

            $args = array(
                ":description" => $permission->description,
                ":code_name" => $permission->code_name
            );

            $last_id = $this->db->insert($sql, $args);
            $response['id'] = $last_id;

            $response['success'] = true;
            $response['message'] = "Permission added sucessfully";
        }
        catch (Exception $e)
        {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }
        $app->contentType('application/json');
        echo json_encode($response);
    }

    public function getPermission($id, $json_request=true)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "SELECT * FROM `Permissions` WHERE id=:id";
        try
        {
            $permission = $this->db->select($sql, array(":id" => $id), false);
            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($permission);
            }
            else
                return $permission;
        }
        catch (Exception $e)
        {
            $response['message'] = $e->getMessage();
            // while still debugging
            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($response);
            }
            else
                throw $e;
        }
    }

    // only meant to be json api request.
    public function updatePermission($id)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "UPDATE `Permissions` SET `description`=:description,
        `code_name`=:code_name WHERE `id`=:id";

        try
        {
            $body = $app->request->getBody();
            $permission = json_decode($body);
            if(empty($permission))
                throw new Exception("Invalid JSON '$body'", 1);

            $args = array(
                ":description" => $permission->description,
                ":code_name" => $permission->code_name,
                ":id" => $id
            );

            $this->db->update($sql, $args);

            $response['success'] = true;
            $response['message'] = "Permission updated sucessfully";
        }
        catch(Exception $e)
        {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }
        $app->contentType('application/json');
        echo json_encode($response);
    }

    // only meant to be json api request.
    public function deletePermission($id)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "DELETE FROM `Permissions` WHERE `id`=:id";
       
        try
        {

            $this->db->delete($sql, array(":id" => $id));

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
        $app->contentType('application/json');
        echo json_encode($response);
    }

    public function getMachines($json_request=true)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "SELECT * FROM `Machines`";

        try
        {
            $machines = $this->db->select($sql);

            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($machines);
            }
            else
                return $machines;
        }
        catch (Exception $e)
        {
            $response['message'] = $e->getMessage();
            // while still debugging
            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($response);
            }
            else
                throw $e;
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
            if(empty($machine))
                throw new Exception("Invalid JSON '$body'", 1);
                
            $stmt->bindParam(':machine_location', $machine->machine_location);
            $args = array(
                ":machine_location" => $machine->machine_location
            );

            $last_id = $this->db->insert($sql, $args);

            $response['id'] = $last_id;
            $response['success'] = true;
            $response['message'] = "Machine added sucessfully";
        }
        catch (Exception $e)
        {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }
        $app->contentType('application/json');
        echo json_encode($response);
    }

    public function getMachine($id, $json_request=true)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "SELECT * FROM `Machines` WHERE id=:id";
        try
        {
            $group = $this->db->select($sql, array(":id" => $id), false);
            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($group);
            }
            else
                return $group;
        }
        catch (Exception $e)
        {
            $response['message'] = $e->getMessage();
            // while still debugging
            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($response);
            }
            else
                throw $e;
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
            if(empty($machine))
                throw new Exception("Invalid JSON '$body'", 1);

            $args = array(
                ":machine_location" => $machine->machine_location,
                ":id" => $id
            );

            $response['success'] = true;
            $response['message'] = "Machine updated sucessfully";
        }
        catch(Exception $e)
        {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }
        $app->contentType('application/json');
        echo json_encode($response);
    }

    public function deleteMachine($id)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "DELETE FROM `Machines` WHERE `id`=:id";
       
        try
        {

            $this->db->delete($sql, array(":id" => $id));

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
        $app->contentType('application/json');
        echo json_encode($response);
    }

    public function getUsers($json_request=true)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "SELECT `id`, `name`, `email`, `group_id`, `balance` FROM `Users`";

        try
        {
            $users = $this->db->select($sql);
            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($users);
            }
            else
                return $users;
        }
        catch (Exception $e)
        {
            $response['message'] = $e->getMessage();
            // while still debugging
            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($response);
            }
            else
                throw $e;
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
            if(empty($user))
                throw new Exception("Invalid JSON '$body'", 1);
            if(!isset($user->password))
                $user = (object) array_merge((array)$user, array("password" => Api::string_gen()));
            $args = array(
                ":id" => $user->id,
                ":password" => new Password($user->password),
                ":name" => $user->name,
                ":email" => $user->email,
                ":group_id" => $user->group_id,
                ":balance" => $user->balance
            );

            // Strangely the return id from this function is always wrong
            // so we make it the id from the request, which will be correct
            // if the function executes properly
            $this->db->insert($sql, $args);

            //todo, email new user with password using swiftmailer.

            $response['id'] = $user->id;

            $response['success'] = true;
            $response['message'] = "User added sucessfully with password: ".$user->password;

        }
        catch (Exception $e)
        {
            // while still debugging
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }
        $app->contentType('application/json');
        echo json_encode($response);
    }

    public function getUser($id, $json_request=true)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "SELECT * FROM `Users` WHERE id=:id";
        try
        {
            $user = $this->db->select($sql, array(":id" => $id), false);
            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($user);
            }
            else
                return $user;
        }
        catch (Exception $e)
        {
            $response['message'] = $e->getMessage();
            // while still debugging
            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($response);
            }
            else
                throw $e;
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
            if(empty($user))
                throw new Exception("Invalid JSON '$body'", 1);

            $args = array(
                ":name" => $user->name,
                ":email" => $user->email,
                ":balance" => $user->balance,
                ":id" => $id
            );

            $this->db->update($sql, $args);

            $response['success'] = true;
            $response['message'] = "User updated sucessfully";
        }
        catch(Exception $e)
        {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }
        $app->contentType('application/json');
        echo json_encode($response);
    }

    public function deleteUser($id)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "DELETE FROM `Users` WHERE `id`=:id";

        try
        {
            $this->db->delete($sql, array(":id" => $id));

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
        $app->contentType('application/json');
        echo json_encode($response);
    }

    public function changePassword($id)
    {
        $app = \Slim\Slim::getInstance();

        // if(!(this->session())
        //     $app->halt(404);
        try
        {
            $body = $app->request->getBody();
            $password = json_decode($body);
            if(empty($password))
                throw new Exception("Invalid JSON '$body'", 1);

            // verify user has correct password
            $sql = "SELECT `password` FROM `Users` WHERE `id`=:id";

            $user = $this->db->select($sql, array(":id" => $id));

            if(empty($user) || !Password::check($password->old_password, $user->password))
            {
                $response['success'] = false;
                $response['message'] = "Invalid password. Action refused";
            }
            else
            {
                $sql = "UPDATE `Users` SET `password`=:password, WHERE `id`=:id";

                $args = array(
                    ":id" => $id,
                    ":password" => new Password($password->new_password)
                );
                
                $this->db->update($sql, $args);

                $response['success'] = true;
                $response['message'] = "User password updated sucessfully";
            }
        }
        catch(Exception $e)
        {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }
        $app->contentType('application/json');
        echo json_encode($response);
    }

    public function resetPassword($id)
    {
        $app = \Slim\Slim::getInstance();

        // if(!(session() && (validateSession('user_id', $id) || validateSession('change_user', true))))
        //     $app->halt(404);
        try
        {
            $new_password = Api::string_gen();
            $sql = "UPDATE `Users` SET `password`=:password WHERE `id`=:id";

            $args = array(
                ":password" => new Password($new_password),
                ":id" => $id
            );

            $this->db->update($sql, $args);
            
            $response['success'] = true;
            $response['message'] = "New password '$new_password' set for user: $id";
        }
        catch(Exception $e)
        {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }
        $app->contentType('application/json');
        echo json_encode($response);
    }

    public function getProducts($json_request=true)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "SELECT * FROM `Products`";

        try
        {
            $products = $this->db->select($sql);

            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($products);
            }
            else
                return $products;
        }
        catch (Exception $e)
        {
            $response['message'] = $e->getMessage();
            // while still debugging
            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($response);
            }
            else
                throw $e;
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
                throw new Exception("Invalid JSON '$body'", 1);
                    
            $args = array(
                ":sku" => $product->sku,
                ":name" => $product->name,
                ":vendor" => $product->vendor,
                ":cost" => $product->cost
            );

            $last_id = $this->db->insert($sql, $args);
            $response['id'] = $last_id;

            $response['success'] = true;
            $response['message'] = "Product added sucessfully";
        }
        catch (Exception $e)
        {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }
        $app->contentType('application/json');
        echo json_encode($response);
    }

    public function getProduct($id, $json_request=true)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "SELECT * FROM `Products` WHERE `id`=:id";

        try
        {
            $product = $this->db->select($sql, array(":id" => $id), false);
            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($product);
            }
            else
                return $product;
        }
        catch (Exception $e)
        {
            $response['message'] = $e->getMessage();
            // while still debugging
            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($response);
            }
            else
                throw $e;
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
                throw new Exception("Invalid JSON '$body'", 1);

            $args = array(
                ":sku" => $product->sku,
                ":name" => $product->name,
                ":vendor" => $product->vendor,
                ":cost" => $product->cost,
                ":id" => $id
            );

            $this->db->update($sql, $args);

            $response['success'] = true;
            $response['message'] = "Product updated sucessfully";
        }
        catch(Exception $e)
        {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }
        $app->contentType('application/json');
        echo json_encode($response);
    }

    public function deleteProduct($id)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "DELETE FROM `Products` WHERE `id`=:id";

        try
        {
            $this->db->delete($sql, array(":id" => $id));

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
        $app->contentType('application/json');
        echo json_encode($response);
    }

    public function getTeams($json_request=true)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "SELECT * FROM `Teams`";

        try
        {
            $teams = $this->db->select($sql);

            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($teams);
            }
            else
                return $teams;
        }
        catch (Exception $e)
        {
            $response['message'] = $e->getMessage();
            // while still debugging
            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($response);
            }
            else
                throw $e;
        }
    }

    public function addTeam()
    {
        $app = \Slim\Slim::getInstance();
        $sql = "INSERT INTO `Teams`(`team_name`, `class`, `expiration_date`,
            `team_balance`)
        VALUES (:team_name, :class, :expiration_date, :team_balance)";

        try
        {
            # get the request
            $body = $app->request->getBody();
            $team = json_decode($body);
            if(empty($team))
                throw new Exception("Invalid JSON '$body'", 1);

            $args = array(
                ":team_name" => $team->team_name,
                ":class" => $team->class,
                ":expiration_date" => $team->expiration_date,
                ":team_balance" => $team->team_balance
            );

            $last_id = $this->db->insert($sql, $args);
            $response['id'] = $last_id;

            $response['success'] = true;
            $response['message'] = "Team added sucessfully";
        }
        catch (Exception $e)
        {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }
        $app->contentType('application/json');
        echo json_encode($response);
    }

    public function getTeam($id, $json_request=true)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "SELECT * FROM `Teams` WHERE `id`=:id";

        try
        {
            $group = $this->db->select($sql, array(":id" => $id), false);
            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($group);
            }
            else
                return $group;
        }
        catch (Exception $e)
        {
            $response['message'] = $e->getMessage();
            // while still debugging
            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($response);
            }
            else
                throw $e;
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
                throw new Exception("Invalid JSON '$body'", 1);

            $args = array(
                ":team_name" => $team->team_name,
                ":class" => $team->class,
                ":expiration_date" => $team->expiration_date,
                ":team_balance" => $team->team_balance,
                ":id" => $id
            );

            $this->db->update($sql, $args);

            $response['success'] = true;
            $response['message'] = "Team updated sucessfully";
        }
        catch(Exception $e)
        {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }
        $app->contentType('application/json');
        echo json_encode($response);
    }

    public function deleteTeam($id)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "DELETE FROM `Teams` WHERE `id`=:id";

        try
        {
            $this->db->delete($sql, array(":id" => $id));

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
        $app->contentType('application/json');
        echo json_encode($response);
    }

    public function getLogs($json_request=true)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "SELECT * FROM `Logs`";

        try
        {
            $logs = $this->db->select($sql);

            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($logs);
            }
            else
                return $logs;
        }
        catch (Exception $e)
        {
            $response['message'] = $e->getMessage();
            // while still debugging
            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($response);
            }
            else
                throw $e;
        }
    }

    public function addLog()
    {
        $app = \Slim\Slim::getInstance();
        $sql = "INSERT INTO `Logs`(`user_id`, `product_id`, `machine_id`,
            `date_purchased`)
        VALUES (:user_id, :product_id, :machine_id, :date_purchased)";

        try
        {
            # get the request
            $body = $app->request->getBody();
            $log = json_decode($body);
            if(empty($log))
                throw new Exception("Invalid JSON '$body'", 1);

            $args = array(
                ":user_id" => $log->user_id,
                ":product_id" => $log->product_id,
                ":machine_id" => $log->machine_id,
                ":date_purchased" => $log->date_purchased
            );

            $last_id = $this->db->insert($sql, $args);
            $response['id'] = $last_id;

            $response['success'] = true;
            $response['message'] = "Log added sucessfully";
        }
        catch (Exception $e)
        {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }
        $app->contentType('application/json');
        echo json_encode($response);
    }

    public function getLog($id, $json_request=true)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "SELECT * FROM `Logs` WHERE `id`=:id";

        try
        {
            $log = $this->db->select($sql, array(":id" => $id), false);
            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($log);
            }
            else
                return $log;
        }
        catch (Exception $e)
        {
            $response['message'] = $e->getMessage();
            // while still debugging
            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($response);
            }
            else
                throw $e;
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
                throw new Exception("Invalid JSON '$body'", 1);

            $args = array(
                ":user_id" => $log->user_id,
                ":product_id" => $log->product_id,
                ":machine_id" => $log->machine_id,
                ":date_purchased" => $log->date_purchased
            );

            $this->db->update($sql, $args);

            $response['success'] = true;
            $response['message'] = "Log updated sucessfully";
        }
        catch(Exception $e)
        {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }
        $app->contentType('application/json');
        echo json_encode($response);
    }

    public function deleteLog($id)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "DELETE FROM `Logs` WHERE `id`=:id";

        try
        {
            $this->db->delete($sql, array(":id" => $id));

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
        $app->contentType('application/json');
        echo json_encode($response);
    }

    public function getGroupPermissions($id, $json_request=true)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "SELECT g.`name` AS `group`, p.`description` AS `permission`
        FROM `Groups` g
        INNER JOIN `Group_Permissions` pg ON (g.`id`=pg.`group_id`)
        INNER JOIN `Permissions` p ON (pg.`permission_id`=p.`id`)
        WHERE g.`id`=:id";

        try
        {
            $group_permissions = $this->db->select($sql, array(":id" => $id));
 
            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($group_permissions);
            }
            else
            {
                //provide the permissions in 'many' form
                $permissions = $this->db->select("SELECT `id` AS `value`,
                    `description` AS `name` FROM `Permissions`");
                return array($group_permissions, $permissions);
            }
        }
        catch (Exception $e)
        {
            if($json_request)
            {
                // while still debugging
                $response['message'] = $e->getMessage();
                $app->contentType('application/json');
                echo json_encode($response);
            }
            else
                throw $e;
        }
    }

    public function addGroupPermissions($id)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "INSERT INTO Group_Permissions (`group_id`, `permission_id`)
        VALUES";
        try
        {
            $body = $app->request->getBody();
            $request = json_decode($body);

            if(empty($request))
                throw new Exception("Invalid JSON '$body'", 1);

            $permissions = $request->permission;
            foreach ($permissions as $key => $p)
            {
                end($permissions);
                if($key === key($permissions))
                    $sql .= " (:id_$key, :$key)";    
                else
                    $sql .= " (:id_$key, :$key),";
                $args[":$key"] = $p;
                $args[":id_$key"] = $id;
            }
            $this->db->insert($sql, $args);

            $response['success'] = true;
            $response['message'] = count($permissions) . " permissions have benn added to Group.";
        }
        catch (Exception $e)
        {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }
        $app->contentType('application/json');
        echo json_encode($response);
    }

    public function getMachineSupplies($id, $json_request=true)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "SELECT m.`machine_location` AS `machine`, p.`name` AS `product`, ms.`quantity` AS `quantity`
        FROM `Machines` m
        INNER JOIN `Machine_Supplies` ms ON (m.`id`=ms.`machine_id`)
        INNER JOIN `Products` p ON (ms.`product_id`=p.`id`)
        WHERE m.`id`=:id";

        try
        {
            $machine_supplies = $this->db->select($sql, array(":id" => $id));

            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($machine_supplies);
            }
            else
            {
                //provide the products in 'many' form
                $products = $this->db->select("SELECT `id` AS `value`,
                    `name` AS `name` FROM `Products`");
                return array($machine_supplies, $products);
            }
        }
        catch (Exception $e)
        {
            if($json_request)
            {
                // while still debugging
                $response['message'] = $e->getMessage();
                $app->contentType('application/json');
                echo json_encode($response);
            }
            else
                throw $e;
        }
    }

    public function getUserPermissions($id, $json_request=false)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "SELECT u.`name` AS `user`, p.`description` AS `permission`
        FROM `Users` u
        INNER JOIN `User_Permissions` up ON (u.`id`=up.`user_id`)
        INNER JOIN `Permissions` p ON (up.`permission_id`=p.`id`)
        WHERE u.`id`=:id";

        try
        {
            $user_permissions = $this->db->select($sql, array(":id" => $id));
 
            if($json_request)
            {
                $app->contentType('application/json');
                echo json_encode($user_permissions);
            }
            else
            {
                //provide the permissions in 'many' form
                $permissions = $this->db->select("SELECT `id` AS `value`,
                    `description` AS `name` FROM `Permissions`");
                return array($user_permissions, $permissions);
            }
        }
        catch (Exception $e)
        {
            if($json_request)
            {
                // while still debugging
                $response['message'] = $e->getMessage();
                $app->contentType('application/json');
                echo json_encode($response);
            }
            else
                throw $e;
        }
    }

    public function addUserPermissions($id)
    {
        $app = \Slim\Slim::getInstance();
        $sql = "INSERT INTO User_Permissions (`user_id`, `permission_id`)
        VALUES";
        try
        {
            $body = $app->request->getBody();
            $request = json_decode($body);

            if(empty($request))
                throw new Exception("Invalid JSON '$body'", 1);

            $permissions = $request->permission;
            foreach ($permissions as $key => $p)
            {
                end($permissions);
                if($key === key($permissions))
                    $sql .= " (:id_$key, :$key)";    
                else
                    $sql .= " (:id_$key, :$key),";
                $args[":$key"] = $p;
                $args[":id_$key"] = $id;
            }
            $this->db->insert($sql, $args);

            $response['success'] = true;
            $response['message'] = count($permissions) . " permissions have benn added to User.";
        }
        catch (Exception $e)
        {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }
        $app->contentType('application/json');
        echo json_encode($response);
    }
}
