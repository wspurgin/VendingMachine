<?

/**
* Password type that handles hashing and checking hashed passwords for
* validity using Anthony Ferrara password functions.
* @author Will Spurgin 
*/

require_once('password_fun.php');

class Password
{
    public $_password;

    public static function check($unhashed_password, $hashed_password)
    {
        return password_verify($unhashed_password, $hashed_password);
    }

    public function __construct($str)
    {
        if(!is_string($str))
            trigger_error("Constructor paramater 1 must be a string", E_USER_WARNING);
        else
        {
            $this->_password = password_hash($str, PASSWORD_DEFAULT);
        }
    }
    public function __toString()
    {
        return $this->_password;
    }
}