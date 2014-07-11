<?php

require_once('session.php');

/**
* Common operations
* @author Will Spurgin
* 07/09/2014
*/
class Common
{
    /**
     * @param $code_names: single or array of permission code_names
     */
    public static function userHasPermission($code_names)
    {
        $session = Session::currentSession();

        if (!$session)
            return false;
        else
        {
            foreach ((array)$code_names as $key => $code)
            {
                if (!$session['permissions'][$code])
                    return false;
            }
            // at this point User has required permission(s)
            return true;
        }
    }
}