<?

/**
* NightKnights Mysql DB interface
*
* @author Will Spurgin
*/
class Db
{
    private $connection;
    private $select_as_array;
    
    function __construct($dbhost, $dbname, $dbuser, $dbpass, $select_as_array=false)
    {
        $dbset = "mysql:host=$dbhost;dbname=$dbname;";

        $this->connection = new PDO($dbset, $dbuser, $dbpass);
        $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->select_as_array = (bool) $select_as_array;
    }

    public function __clone()
    {
        return clone $this;
    }

    public function getSelectAsArray()
    {
        return $this->select_as_array;
    }

    public function setSelectAsArray($select_as_array)
    {
        $this->select_as_array = (bool) $select_as_array;
    }

    /**
    * @param sql is the sql statment
    * @param args is an associative array
    * with keys corresspoinding to the values to plug into the statment
    * and their values the value of that arg. If the args are not
    * named, i.e. normal indexes, then no key is assumed
    *
    * @return returns PDO statment object
    */
    public function query($sql, $args=array())
    {
        $stmt = $this->connection->prepare($sql);
        foreach ($args as $key => $value)
        {
            
            if(is_int($key))
                $stmt->bindValue($key + 1, $value); // to handle question marks
            else
                $stmt->bindValue($key, $value); 
        }
        $stmt->execute();

        return $stmt;
    }


    /**
    * @param sql is the sql statment
    * @param args to be passed to 'query()'
    *
    * @return the last inserted Id as result of query.
    */
    public function insert($sql, $args=array())
    {
        $stmt = $this->query($sql, $args);
        return $this->connection->lastInsertId();   
    }

    /**
    * @param sql is the sql statment
    * @param args to be passed to 'query()'
    * @param fetch_all is a boolean, default true, for whether all
    * results should be returned. If false, only first result will be returned
    *
    * @return dependent on 'fetch_all', either an array of stdObjects of query
    * results, or the a single stdObject of the first row.
    */
    public function select($sql, $args=array(), $fetch_all=true)
    {
        $stmt = $this->query($sql, $args);
        if($fetch_all)
        {
            if($this->select_as_array)
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            else
                return $stmt->fetchAll(PDO::FETCH_CLASS);
        }
        else # only fetch 1st object (good for queries getting only 1 row)
        {
            if($this->select_as_array)
                return $stmt->fetch(PDO::FETCH_ASSOC);
            else
                return $stmt->fetch(PDO::FETCH_OBJ);
        }
    }

    /**
    * @param sql is the sql statment
    * @param args to be passed to 'query()'
    *
    * @return true on success, otherwise errors are thrown inside 'query()'
    */
    public function update($sql, $args=array())
    {
        $stmt = $this->query($sql, $args);
        return true;
    }

    /**
    * @param sql is the sql statment
    * @param args to be passed to 'query()'
    *
    * @return true on success, otherwise errors are thrown inside 'query()'
    */
    public function delete($sql, $args=array())
    {
        $stmt = $this->query($sql, $args);
        return true;
    }
}