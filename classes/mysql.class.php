<?php

define('ShowQueryLogInfo',false);

final class mysqler
{

    public $connected;
    public $allowUpdate = true;

    private $DAO = null;

    private $dbhost;
    private $dbuser;
    private $dbpass;
    private $dbname;
    private $dbcharset;
	private $dbport;
    
    /*
	 * Creates a new instance of mysqler to handle MySql database connections and queries.
	 * 
	 * 
	 * @param String $db_host The host system to connect to.
	 * @param String $db_user The user to connect to the host system with
	 * @param String $db_pass The password for the user to connect with
	 * @param String $db_name The default database to connect to
	 * @param String $db_charset The character set to use within the database.
	 */
    public function __construct($db_host=NULL,$db_port=NULL,$db_user=NULL,$db_pass=NULL,$db_name=NULL,$db_charset=NULL)
    {
        if (isset($db_host))  
        {
            $this->connect($db_host,$db_port,$db_user,$db_pass,$db_name,$db_charset);
        }
    }

    public function show_error($text)
    {
        // Todo: Migrate to translation file
        echo "<div class='error'><h3>Chyba databáze</h3>$text</div>";
        /*$f = fopen("error_log","a");
        fwrite($f,$text."\r\n");
        fclose($f);*/
    }
    
	public function connectWithoutPort($db_host,$db_user,$db_pass,$db_name,$db_charset=NULL){
		return $this->connect($db_host,'3306',$db_pass,$db_pass,$db_name,$db_charset);
		
	}

	/*
	 * Creates a connection to the database and returns a value indicating whether the connection succeded or not.
	 * 
	 * 
	 * @param String $db_host String The host system to connect to.
	 * @param String $db_user String The user to connect to the host system with
	 * @param String $db_pass String The password for the user to connect with
	 * @param String $db_name String The default database to connect to
	 * @param String $db_charset String The character set to use within the database.
	 * 
	 * @return int Returns a value indicating the connection is open
	 */
    public function connect($db_host,$db_port,$db_user,$db_pass,$db_name,$db_charset=NULL)
    {
        if (($this->connected == 1))
        {
            return $this->connected;
        }

        if ($db_charset==NULL) 
        {
			// Todo: move to config file
            $db_charset = "utf8";
        }
        
        $this->connected = 0;

        try
        {		
			$this->dbcharset = $db_charset;
			$this->dbhost = $db_host;
			$this->dbname = $db_name;
			$this->dbpass = $db_pass;
			$this->dbport = $db_port;
			$this->dbuser = $db_user;
			
            $this->DAO = new PDO("mysql:dbname=$db_name;hostname=$db_host;port=$db_port;",$db_user,$db_pass);

            if ($this->DAO->getAttribute(PDO::ATTR_CONNECTION_STATUS))
            {
                $this->connected = 1;
                if ($this->query("SET CHARACTER SET $db_charset")->errorCode() == '')
                {
                    // Todo: Migrate to translation file.
                    $this->show_error("Nepodařilo se deklarovat kódování.");
                }
            }
            else
            {
                $this->connected = 0;
                // Todo: Migrate to translation file.
                $this->show_error("Chyba při připojování k mysql databázi.");
            }

        }
        catch (PDOException $exception)
        {
            $this->connected = 0;
            // Todo: Migrate to translation file.
            $this->show_error("A PDO exception occured when attempting to connect to database'$db_host'.<br/>'.".$exception->getMessage().".");
            die();
        }
        catch (Exception $exception)
        {
            $this->connected = 0;
            // Todo: Migrate to translation file.
            $this->show_error("A general exception occured when attempting to connect to database'$db_host'");
            die();
        }
        
        return $this->connected;
    }

    /*
	 * Closes and disposes of the connection.
	 *
	 */
    public function disconnect()
    {
        if ($this->connected == 1)
        {
            $this->connected = 0;

            if (isset($this->DAO))
            {
                $this->DAO = null;
            }
        }
        return true;
    }
    
    /*
	 * Creates a new PDOStatement using the PDO Prepare call
	 * 
	 * @param String $query The query string to be used in creating the PDOStatement.
	 * 
	 * @return PDOStatement The PDOStatement that is created by PDO->prepare.
	 */
    public function query($query)
    {
		if (ShowQueryLogInfo == true)
		{
			Debug::log("Begin query processing $query",LOG_INFO);
		}
		$ret = NULL;
		try{
			if ($this->checkConnectionOpen())
			{
				$ret = $this->DAO->prepare($query);
				if (isset($ret) == false)
				{
					// Todo: Migrate to translation file
					$this->show_error("Chyba při vykonávání příkazu SQL.<br><div class='sql'>$query</div><div class='sql_chyba'>Error Code:".$ret->errorCode().'<br/>Error Information:<br/>'.implode('<br/>',$ret->errorInfo()).'</div>');
					die();
				}
				if ($ret->execute() == true)
				{
					// Todo: Log success
				}else{
					// Todo: Migrate to translation file.... I hope this says "Error occurred"
					$this->show_error("Chyba při vykonávání příkazu SQL.<br><div class='sql'>$query</div><div class='sql_chyba'>Error Code:".$ret->errorCode().'<br/>Error Information:<br/>'.implode('<br/>',$ret->errorInfo()).'</div>');
					//$this->show_error("Chyba při vykonávání příkazu SQL.<br><div class='sql'>$query</div><div class='sql_chyba'>".mysql_error().'</div>');
					die();
				}

			} 
			else 
			{
				// Todo: Migrate to translation file
				$this->show_error("Připojení ještě nebylo provedeno.");
			}
		}
		finally
		{
			if (ShowQueryLogInfo == true)
			{
				Debug::log("End query processing $query",LOG_INFO);
			}
		}
        return $ret;
    }
    
    /*
	 * Returns the next row from the PDOStatment object
	 * 
	 * @param PDOStatement $statement The statement to retreive the row for.
	 */
    public function row($statement)
    {
        if (isset($statement) && $statement instanceof PDOStatement)
        {
            return $statement->fetch();
        } 
        else 
        {
            // Todo: Migrate to translation file
            $this->show_error("Připojení ještě nebylo provedeno.");
            return null;
        }
    }

    /*
	 * Returns the row count of the PDOStatement object
	 * 
	 * @oaram PDOStatement $statement The statement object created by a query call
	 */
    public function count($statement)
    {
        if (isset($statement) && $statement instanceof PDOStatement)
        {
			// Check query has been executed
            if ($statement->columnCount() == 0 )
			{
				// Has not been executed
				$statement = $this->query($statement->queryString);
				if ($statement->columnCount() == 0){
					// Error occurred and there are not any columns.
					$this->show_error("Error occurred when attempting to execute a query to get the column counts.<br/>No columns returend");
					return -1;
				}
			}
            return $statement->rowCount();
        } 
        else 
        {
            // Todo: Migrate to translation file
            $this->show_error("Připojení ještě nebylo provedeno.");
        }

        return 0;
    }

    /*
	 * Inserts data into a table
	 * 
	 * @param string $table The table to insert data into
	 * @param array $data The data to insert into the table
	 * 
	 * @return PDOStatement Returns the PDOStatement created by the insert statement
	 */
    public function insert($table, $data)
    {
		// Todo: Migrate to parameterized query
        return $this->query("INSERT INTO $table (" . implode(',',array_keys($data)) . ") VALUES (" . $this->arrayToStringForQuery($data) . ")");
    }

    /*
	 * Updates the table with the data based on the where statement
	 * 
	 * @param string @table The table to update data
	 * @param string @where The where statement to filter for updating the table
	 * @param string @data The data to update the table
	 * 
	 * @return PDOStatement Returns the PDOStatement created for the query
	 */
    public function update($table, $where, $data)
    {
        $values = array();

        // Todo: Migrate to stored procedure not inline.
		foreach($data as $key=>$value)
        {
            $values[] = $key.' = '.$this->quoteString($value);
        }

        // Todo: Migrate to parameterized query
		// Don't need to call arrayToStringForQuery beacuse the quotes have already been added above
        return $this->query("UPDATE $table SET " . implode(',',$values) . " WHERE " . $where);
    }
    
    public function insertOrUpdate($table, $where, $data)
    {
        if ($this->count($this->query("SELECT * FROM $table WHERE $where LIMIT 1")) == 0)
        {
            $this->insert($table, $data);
            return $this->DAO->lastInsertId();
        } 
        else 
        {
            if ($this->allowUpdate)
            {
                $this->update($table, $where,$data);
            }
            return 0;
        }
    }
    
    public function getSingleRow($query)
    {
        return $this->row($this->query($query));
    }
    
    public function __destruct()
    {
        $this->disconnect();
    }

    /*
	 * Gets a value indicating whether or not the connection is open.
	 * 
	 * Gets a value indicating whether or not the connection is open, if it is not, creates a new object.
	 */
    private function checkConnectionOpen()
    {
        if (isset($this->dataaccess) == false || $this->connected == 0)
        {
            $this->connect($this->dbhost,$this->dbport,$this->dbuser,$this->dbpass,$this->dbname,$this->dbcharset);
        }

        return $this->connected;
    }

	public function getLastUpdatedId()
	{
		if ($this->checkConnectionOpen() == true)
		{
			return $this->DAO->lastInsertId();
		}
		else
		{
			return -1;
		}
	}

	public function quoteString($string, $parameter_type = PDO::PARAM_STR)
	{
		if ($this->checkConnectionOpen() == true)
		{
			return $this->DAO->quote($string,$parameter_type );
		}

		return $string;
	}

	public function arrayToStringForQuery($data)
	{
		$quoteStringCallBack =  function($string)
									{
										return $this->quoteString($string);
									};

		return implode(',',array_map($quoteStringCallBack,$data));
	}
}

?>
