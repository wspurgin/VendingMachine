<?php

/** 
* Session represented in object form for friendly and easy interaction.
* @author Will Spurign
*/
class Session implements arrayaccess
{
    #region private members

    private $session_validation = array();

    private $guests_enabled = false;

    #endregion

    #region internal session handling functions

    private function getSession($set_guest=false)
    {
        //if there is no active session
        if (!isset($_SESSION))
        {
            if (isset($_COOKIE))
            {
                reset($_COOKIE);
                $name = key($_COOKIE);
            }
            if (!isset($name) && $set_guest)
            {
                $name = sha1("GUEST".time());
                newSession($name);
            }
            else if (!isset($name))
            {
                //do not activate session
                return false;
            }
            else
            {
                session_name($name); // set session name
                session_start(); // resume session
            }
            // to avoid garabage collector greediness
            $_SESSION['last_access'] = time();
        }
        return true;
    }

    #endregion

    public function __construct()
    {
    }

    public static function createSession($name)
    {
        $instance = new self();
        $instance->newSession($name);
        return $instance;
    }

    public static function currentSession($validation=array())
    {
        $instance = new self();
        $instance->setValidation($validation);
        $good_session = $instance->initializeSession();
        if ($good_session)
            return $instance;
        else
            return false;
    }

    // Session must be active
    public static function destroySession()
    {
        // destroying active session
        session_unset();
        if (ini_get("session.use_cookies"))
        {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', 1,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]);
        }
        session_destroy();
    }

    // this function will destory any active sessions
    // and delete any site-cookies that are already set
    public static function newSession($name)
    {
        // clear all site-cookies (unless the cookie id is name)
        // otherwise the new session cookie will get deleted right
        // after we create it
        foreach ($_COOKIE as $c_id => $c_value)
        {
            if ($c_id != $name)
            {
                $params = session_get_cookie_params();
                setcookie($c_id, NULL, 1,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]);
            }
        }
        if (isset($_SESSION))
            self::destroySession();

        session_name($name); // set session name
        session_start(); // start session
        $_SESSION['last_access'] = time();
    }

    public function initializeSession()
    {
        $this->session_validation = (array)$this->session_validation;
        $good_session = true;
        if (!$this->getSession($this->guests_enabled))
            return false;
        // validate session if validation is required
        if (!empty($this->session_validation))
        {
            foreach ($this->session_validation as $key => $validator)
            {
                if (is_array($validator))
                    $good_session = $this->validateSession($validator["key"], $validator["value"]);
                else
                    $good_session = $this->validateSession($validator);

                // if the session is invalid
                if (!$good_session)
                    return false;
            }
        }
        return $good_session;
    }

    public function setValidation($validation) { $this->session_validation = $validation; }

    public function setGuestEnabled($enabled)
    {
        if (!is_bool($enabled))
            trigger_error("Constructor paramater 1 must be a bool", E_USER_WARNING);
        else
            $this->guests_enabled = $enabled;
    }

    // where $key and $value are the key and value pair to be checked in $_SESSION
    // If looking only for existence of $key, Usage validateSession($key);
    public function validateSession($key, $value=NULL)
    {
        $is_good = false;
        if (isset($_SESSION))
        {
            if (isset($_SESSION[$key]))
            {
                if (!isset($value))
                    $is_good = true;
                else
                {
                    if ($_SESSION[$key] == $value)
                        $is_good = true;
                }
            }
        }

        return $is_good;
    }

    #region implementation for arrayaccess

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $_SESSION[] = $value;
        } else {
            $_SESSION[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($_SESSION[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($_SESSION[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($_SESSION[$offset]) ? $_SESSION[$offset] : null;
    }

    #endregion
}
