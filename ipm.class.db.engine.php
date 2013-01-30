<?php
include_once("ipm.class.db.engine.basic.php");
/** 
 * IPM Database Class 
 * 
 * <p>Database Class Subsystem</p>
 * 
 * @author    Aaron Snyder <aaron.snyder@hki.com>
 * @date      2013-01-24
 * @version   1.8.x
 * @revision  460921
 * @package   IPM\Core\System\Database
 */
/**
 * IPM Database Engine
 * 
 * <p>Note: this is an abstraction and cannot be created directly</p>
 * 
 * @package   IPM\Core\System\Database
 */
class IPM_db_engine extends aIPM_db_engine {
    
    /**
     * An array of query templates
     * @var array 
     */
    protected $templates = Array();
    
    /**
     * Array to hold the query queue
     * @var Array 
     */
    protected $queue = Array();
    
    /**
     * Opens/creates a connection to the database
     * <p>Note: Not permitted on table engine instances</p>
     * @param String $user User name
     * @param String $pass Password
     * @param String $host Server host name/IP
     * @return Boolean True is connection created
     */
    public function open($user,$pass,$host) {
        $this->plugin->open($user,$pass,$host);
        $this->conn = $this->plugin->conn;
    }
    
    /**
     * Sets the current database as active
     * <p>Note: Not permitted on table engine instances</p>
     * @param String $db Database name
     * @return Boolean True on success
     */
    public function setdb($db) {
        return $this->plugin->setdb($db);
    }
    
    /**
     * Sets session variables for current database connection
     * <p>Note: Not permitted on table engine instances</p>
     * @param String $dbvar Session variable to set
     * @param String $value Value to assign to the session variable
     * @return Boolean True on success
     */
    public function setvar($variable,$value) {
        return $this->plugin->setvar($variable,$value);
    }
    
    /**
     * Closes the current database connection
     * <p>Note: Not permitted on table engine instances</p>
     */
    public function close() {
        return $this->plugin->close();
    }
    
    /**
     * Clears the active queue
     * <p>Note: Not permitted on table engine instances</p>
     */ 
    public function clearQueue() {
        if ($this->tableConfig !== false) return false;
        $this->queue = Array();
    }
        
    /**
     * Adds a query to the query queue
     * <p>Note: Not permitted on table engine instances</p>
     * @param String $query The query to add to the queue
     * @param Array $tokens (Optional) An array of name/value pairs used to compile the query or template
     * @param Closure $callback Function to execute once data has been returned, not fully implimented
     */ 
    public function addQuery($query,$tokens = false,$callback = false) {
        if ($this->tableConfig !== false) return false;

        //Allows executing a callback on standard queries
        //by allowing a closure to be passed instead of 
        //the tokens
        if (gettype($tokens) == "object") {
            if (get_class($tokens) == "Closure") {
                $queue_item->callback = $tokens;
                $tokens = false;
            }
        }

        if ($tokens !== false) {
            if ($this->isTemplate($query)) $query = $this->compileTemplate($query,$tokens);
            else $query = $this->compileQuery($query,$tokens);
        }

        $queue_item = new StdClass();
        $queue_item->query = $query;
        $queue_item->callback = $callback;
        $this->queue[] = $queue_item;

    }
        
    /**
     * Executes all queries in the queue.
     * <p>Any values returned by a query will be passed as tokens to
     * the next executing query.  Each time data is returned, it is merged into the tokens from the previous
     * query before passed to the next.  In this way, all values can be passed instead of just the last.<br><br>
     * Note: Not permitted on table engine instances</p>
     * @param Closure $callback (Optional) Function to execute once all queries in the queue have been executed.
     * @return Array Returns an array containing the result of the final query in the queue, or the 
     * result of the callback.
     */ 
    public function runQueue($callback = false) {
        if ($this->tableConfig !== false) return false;
        $tokens = Array();

        for ($x = 0; $x < count($this->queue); $x++) {
            $queue_item = $this->queue[$x];
            $result = $this->query($queue_item->query,$tokens); //,$queue_item->callback);
            if (is_array($result[0])) $tokens = array_merge($result[0]);
        }

        if (gettype($callback) == "object") {
            if (get_class($callback) == "Closure") {
                return $callback($result,$tokens);
            }
        }

        return $result;
    }
        
    /**
     * Adds a query template.
     * <p>Query templates can be defined using "tokens" that will be replaced
     * with field/value pairs durring query execution.  Templates can also be defined with a default 
     * callback to be executed on the result object.<br><br>
     * Note: Not permitted on table engine instances</p>
     * @param String $name Query template identifier, used in templates object hash
     * @param String $query The query template
     * @param Closure $callback Function to execute once data has been returned
     */ 
    public function addTemplate($name,$query,$callback = false) {
        if ($this->tableConfig !== false) return false;
        $this->templates[strtolower($name)] = Array(
            "query" => $query,
            "callback" => $callback
        );
    }
        
    /**
     * Determine if the named value is a registered template
     * <p>Note: Not permitted on table engine instances</p>
     * @param String $name Query template identifier, used in templates object hash
     * @return Boolean True if the name is defined in the templates object hash
     */ 
    public function isTemplate($name) {
        if ($this->tableConfig !== false) return false;
        return isset($this->templates[$name]);
    }
        
    /**
     * Compiles a query template with the values in the tokens object
     * <p>Note: Not permitted on table engine instances</p>
     * @param String $name Query template identifier, used in templates object hash
     * @param {Object} $tokens Values to replace the tokens with
     * @return String The compiled template
     */ 
    public function compileTemplate($name,$tokens) {
        if ($this->tableConfig !== false) return false;
        $query = $this->templates[$name]["query"];
        if (count($tokens)>0) {
            foreach($tokens as $key => $value) {
                if ($key[0] != ":") $key = ":".$key;
                $query = str_replace($key, $value, $query);
            }
        }
        return $query;
    }

    /**
     * Compiles a query with the values in the tokens object
     * <p>Note: Not permitted on table engine instances</p>
     * @param String $query Query to compile
     * @param Array $tokens Values to replace the tokens with
     * @return String The compiled query
     */
    public function compileQuery($query,$tokens) {
        if ($this->tableConfig !== false) return false;
        if (count($tokens)>0) {
            foreach($tokens as $key => $value) {
                if ($key[0] != ":") $key = ":".$key;
                $query = str_replace($key, $value, $query);
            }
        }
        return $query;
    }

    /**
     * Returns the callback from a named template
     * @param string $name The name of the template
     */
    private function hasCallback($name) { return $this->templates[$name]["callback"]; }
    
    /**
     * Alias of <i>hasCallback</i>
     * @param string $name The name of the template
     */
    private function getTemplateCallback($name) { return $this->hasCallback($name);   }
    
    /**
     * Submits an SQL statement to the database
     * <p>Note: Not permitted on table engine instances</p>
     * @param String $query Query to execute
     * @param Mixed $tokens Values to replace the tokens with or custom callback 
     * function, see the <u>$callback</u> parameter for detauls on closures.  If 
     * a closure is passed instead of a token array, the <u>$callback</u> parameter 
     * will not be processed.
     * @param Closure $closure Custom callback function.  Callbacks are passed 
     * the following parameters:<ul>
     * <li><b>results</b> An array containing the results of the query</li>
     * <li><b>query</b> An object containing the defaults of the query</li>
     * </ul>
     * @param mixed $closureParams (Optional) A parameter to pass into the callback. 
     * Note: parameter pass by reference.
     * @return mixed Array of the result set or the return of a passed callback
     */ 
    public function query($query,$tokens = false,&$closure = false,&$closureParams = false) {
        
        $callback = false;
        $params = "closureParams";
        if ((gettype($closure) == "object" && get_class($closure) == "Closure")) $callback = $closure;
        else if ((gettype($tokens) == "object" && get_class($tokens) == "Closure")) {
            $callback = $tokens;
            $tokens = false;
            $params = "closure";
        }
        
        if ($this->isTemplate($query) && $tokens) {
            $callback = $this->hasCallback($query);
            $query = $this->compileTemplate($query, $tokens);
        } else if ($tokens) $query = $this->compileQuery($query, $tokens);
        
        $results = $this->plugin->query($query);
        if (!$this->plugin->errno) {
            if ($this->saveHistory === true) $this->history[] = $this->plugin->lastQuery;
            if ($callback !== false) return $callback($results,$this->plugin->lastQuery,$$params); 
            return $results;
        }
        return false;
    }
    
    /**
     * Returns the results of the last successfully executed SQL statement
     * <p>Note: table engine instances cannot return the MySQL result resource, 
     * only the result array is allowed to be retrieved</p>
     * @param string $type (Optional) Type of results to return (array or resource), 
     * defaults to array
     * @return mixed MySQL result resource
     */ 
    public function results($type = "array") {
        return $this->plugin->results($type);
    }
    
    /**
     * Insert a record into the database by generating a safe query using provided
     * parameter array.  Note: table engine instances cannot specifiy table
     * @param array $params A configuration array containing the following 
     * parameters:<ul>
     * <li><b>table</b> The table to perform the query on</li>
     * <li><b>fields</b> (Optional) An array of table fields/value pairs to update the record with, defaults to "*"</li>
     * </ul>
     * @param bool $returnQuery Set to True to return the compiled query without 
     * executing it.
     * @return mixed The ID of the insert statement if the table has an auto 
     * incremeted field or the compiled query.
     */
    public function insert($params,$returnQuery = false) {
        return $this->plugin->insert($params,$returnQuery);
    }
    
    /**
     * Update a record in the database by generating a safe query using provided
     * parameter array.  Note: table engine instances cannot specifiy table
     * @param array $params A configuration array containing the following 
     * parameters:<ul>
     * <li><b>table</b> The table to perform the query on</li>
     * <li><b>fields</b> An array of table fields/value pairs to update the record with, defaults to "*"</li>
     * <li><b>where</b> (Optional) An array of table fields/value pairs to use as the where clause</li>
     * </ul>
     * @param bool $returnQuery Set to True to return the compiled query without 
     * executing it.
     * @return mixed The amount of affected rows or the compiled query.
     */
    public function update($params,$returnQuery = false) {
        return $this->plugin->update($params,$returnQuery);
    }
    
    /**
     * Select records from the database by generating a safe query using provided
     * parameter array.  Note: table engine instances cannot specifiy table
     * @param array $params A configuration array containing the following 
     * parameters:<ul>
     * <li><b>table</b> The table to perform the query on</li>
     * <li><b>fields</b> (Optional) An array of table fields/value pairs to update the record with, defaults to "*"</li>
     * <li><b>where</b> (Optional) An array of table fields/value pairs to use as the where clause</li>
     * <li><b>options</b> (Optional) An array with various options for the query.  As follows<ul>
     * <li><b>order</b> The field to order by or an array containing the <u>fields</u> and <u>dir</u>ection to sort</li>
     * <li><b>limit</b> The number of records to query or an array containing the <u>start</u> and <u>length</u></li>
     * <li><b>group</b> The field to group by</li>
     * </ul></li>
     * </ul>
     * @param bool $returnQuery Set to True to return the compiled query without 
     * executing it.
     * @return mixed The result array from the executed query or the compiled query
     */
    public function select($params,$returnQuery = false) {
        return $this->plugin->select($params,$returnQuery);
    }
    
    /**
     * Delete a set of records from the database by generating a safe query using 
     * provided parameter array.  Note: table engine instances cannot specifiy table
     * @param array $params A configuration array containing the following 
     * parameters:<ul>
     * <li><b>table</b> The table to perform the query on</li>
     * <li><b>where</b> An array of table fields/value pairs to use as the where clause</li>
     * </ul>
     * @param bool $returnQuery Set to True to return the compiled query without 
     * executing it.
     * @return mixed The amount of affected rows or the compiled query.
     */
    public function delete($params,$returnQuery = false) {
        return $this->plugin->delete($params,$returnQuery);
    }

    /**
     * Create a table in the database by generating a safe query using provided
     * parameter array.  Note: table engine instances cannot specifiy table
     * @param array $params An array of table configuration to build the query.  
     * The following are valid<ul>
     * <li><b>role</b> The type of table or role the table in the table will have 
     * (system, content, modules), used in the naming of the table</li>
     * <li><b>primarykey</b> field to use as the primary key</li>
     * <li><b>fields</b> An array of field descriptions, each index should be an 
     * array withhave the following:<ul>
     * <li><b>name</b> name of the field</li>
     * <li><b>type</b> valid database field type</li>
     * <li><b>length</b> the length of the field</li>
     * <li><b>null</b> (bool) is null</li>
     * <li><b>default</b> default value of the field</li>
     * <li><b>charset</b> character set</li>
     * <li><b>collate</b> collation</li>
     * <li><b>auto</b> auto increment field</li>
     * </ul>
     * </li>
     * </ul>
     * @param bool $returnQuery Set to True to return the compiled query without 
     * executing it.
     * @return mixed 
     */
    public function create($params,$returnQuery = false) {
        return $this->plugin->create($params,$returnQuery);
    }

}

?>