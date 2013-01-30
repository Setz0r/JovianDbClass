<?php
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
 * IPM Database Basic Engine
 * 
 * <p>This engine should only be instantiated as a table engine instance.  Any
 * noral database class should extend or instantiate the full engine, which 
 * extends this class.</p>
 * 
 * <p>Note: this is an abstraction and cannot be created directly</p>
 * 
 * @package   IPM\Core\System\Database
 */
class aIPM_db_engine {

    /**
     * The database connection resource.  The class, table engines spawned from 
     * this class, and plugin all share the same connection resource which is 
     * opened through the plugin.
     * @var resource 
     */
    public $conn = false;
    
    /**
     * Database plugin that will peform the actual database communication
     * @var IPM_db_plugin 
     */
    protected $plugin = false; //LOADED PLUGIN OBJECT
    
    /**
     * Name of database plugin to use
     * @var string 
     */
    protected $pluginType = false; //CURRENT DATABASE PLUGIN
    
    /**
     * Name of the default database plugin, use if no plugin type is specified 
     * durring class instnatianation
     * @var string 
     */
    protected $defaultPlugin = "mysql"; //DEFAULT DATABASE PLUGIN
    
    /**
     * Possible table configuration, used if this class is instantiated as a 
     * table engine
     * @var object 
     */
    protected $tableConfig = false; //POSSIBLE TABLE CONFIGURATION INFORMATION

    /**
     * Sets the ability to save to the query history after every execution
     * @var bool 
     */
    protected $saveHistory = false;
    
    /**
     * Holds a history of all queries processed by this engine
     * @var array 
     */
    public $history  = Array();
    
    /**
     * Constructor - automatically loads the database plugin and opens a 
     * connection to the database if the server connections settings are set in 
     * the constructor config parameter array.
     * @param mixed $params The database plugin to load or an array containing 
     * the plugin and connection settings
     * @param resource $conn The connection can be passed through the engine to 
     * the plugin when this class is instantiated as a table engine in order to 
     * share the same connection as the parent class
     * @constructor
     */
    public function __construct($params = false,$conn = false) {
        $this->conn = $conn;
        if ($params === false) $this->pluginType = $this->defaultPlugin;
        else if (is_array($params)) {
            if (isset($params["plugin"])) $this->pluginType = $params["plugin"];
            else $this->pluginType = $this->defaultPlugin;;
        } else $this->pluginType = $params;
        $this->loadPlugin($this->pluginType);
        
        if (is_array($params) && isset($params["host"]))
            $this->plugin->open($params["user"],$params["pass"],$params["host"]);
    }

    /**
     * Loads a database plugin.
     * @param string $plugin Name of the database plugin
     * @param string $path (Optional) Path to the database plugin class file
     * @return bool True if the plugin was loaded successfully, false on failure 
     */
    protected function loadPlugin($plugin,$path = "") {
        $pluginName = "IPM_db_plugin_".strtolower($plugin);//."()";
        $filename = ((strlen($path))?$path:"")."ipm.class.db.plugin.".strtolower($plugin).".php";
        
        if (!class_exists($pluginName)) { require_once ($filename); }
        
        if (class_exists($pluginName)) {
            //$this->plugin = new IPM_db_plugin_mysql((($this->conn!==false)?$this->conn:false));
            eval('$this->plugin = new '.$pluginName.'('.(($this->conn!==false)?'$this->conn':'false').');');
            return true;
        } else return false;
    }
    
    public function toggleHistory($set = 0) {
        $this->saveHistory = ($set !== 0) ? $set:!$this->saveHistory;
    }
    
    /**
     * Loads a database plugin.  Ensures that it doesn't reload the currently 
     * loaded plugin
     * @param string $plugin Name of the database plugin
     * @return bool True if the plugin was loaded successfully, false on failure 
     */
    public function setPlugin($plugin) {
        if ($plugin->getType() != $plugin) return $this->loadPlugin($plugin);
        return false;
    }
    
    /**
     * Resets the database plugin to the default plugin type
     */
    public function resetPlugin() { $this->setPlugin($this->defaultPlugin); }
    
    /**
     * Sets table configuration.  This determines if the instance of this class 
     * will be used as a table engine.  Database engines have restricted 
     * functionality
     * @param array $config Table config array
     */
    public function setTableConfig($config) { $this->tableConfig = $config; }
    
    /**
     * Return the table configuration if this class is instantiated as a table engine
     * @return array 
     */
    public function getTableConfig() { return $this->tableConfig; }
    
    //public function getPlugin() { $this->plugin; }
    
    /**
     * Sets the default table creation values
     * @param mixed Defaults object, could also be an array
     */
    public function setDefaults($defaults) { $this->plugin->setDefaults($defaults); }
    
    /**
     * Returns the default table creatoin values as an object
     * @params string $option (Optional) Returns the value for the specified option, 
     * if empty will return all default values
     * @return mixed Requested value or default value object
     */
    public function getDefaults($option = "") { return $this->plugin->getDefaults($option); }
    
    /**
     * Returns the name of the current active database
     * @return String Database name, false if a database is not selected
     */
    public function getdb() { return $this->plugin->database; }
    
    /**
     * Returns current connection status
     * @return Boolean True if connected
     */
    public function connected() { return $this->plugin->connected(); }
    
    /**
     * Returns the auto incremeted ID generated by the last insert query
     * @return Integer Insert ID
     */
    public function insert_id() { return $this->plugin->insert_id; }

    /**
     * Escapes the variable to make it safe to use in a query
     * @param String $var The variable to escape
     * @return String The escaped string
     */
    public function real_escape_string($var) { return $this->plugin->real_escape_string($var); }
    
    /**
     * Returns the last query executed
     * @param String $refid (Optional) Reference to the query in the query history
     * @return String The last query executed
     */
    public function getQuery($refid = "") {
        if ($refid) return $this->history[$refid]->query;
        else return (is_string($this->plugin->lastQuery))?$this->plugin->lastQuery:$this->plugin->lastQuery->query;
    }
    
    public function getLastQuery($refid = "") {
        return $this->plugin->lastQuery;
    }
    
    /**
     * Returns the query history
     * @param String $refid (Optional) Reference to the query in the query history, 
     * if not supplied then the entire query history will be returned
     */
    public function getHistory($refid = "") {
        if ($refid) return $this->history[$refid];
        else return $this->history;
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
        return $this->plugin->results();
    }
    
    /**
     * Returns the current result set for the last successfully run sql statement
     * @param Integer $row Row to move the pointer to
     * @param mixed $field (Optional) Field to get the data from, defaults to 0 or the first field
     * @return mixed Contents of specified field or false on failure
     */
    public function result($row,$field = 0) { return $this->plugin->result($row,$field); }

    /**
     * Returns the number of rows for the last run select statement
     * @return int The count 
     */
    public function num_rows() { return $this->plugin->num_rows; }
    
    /**
     * Returns the number of rows affected for the last run update statement
     * @return int The count 
     */
    public function affected_rows() { return $this->plugin->affected_rows; }
    
    /**
     * Returns the error code generated for the last executed query
     * @return int Error code, 0 if no error
     */
    public function errno() { return $this->plugin->errno; }
    
    /**
     * Returns the error generated for the last executed query
     * @return String Error string or false
     */
    public function error() { return $this->plugin->error; }
    
    /**
     * Resets the pointer in the MySQL result set
     * @param Integer $pointer (Optional) Position in the result set to reset to, defaults to 0
     * @return mixed True on success, MySQL error number on failure
     */
    public function reset($pointer = 0) { return $this->plugin->reset($pointer); }
    
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
        $params["table"] = $this->tableConfig["table"];
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
        $params["table"] = $this->tableConfig["table"];
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
        $params["table"] = $this->tableConfig["table"];
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
        $params["table"] = $this->tableConfig["table"];
        return $this->plugin->delete($params,$returnQuery);
    }

}

?>