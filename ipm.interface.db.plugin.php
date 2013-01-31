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
 * IPM Database Plugin Interface
 * 
 * @package   IPM\Core\System\Database
 */
interface iIPM_db_plugin {
    /**
     * Sets the default table creation values
     * @param mixed Defaults object, could also be an array
     */
    public function setDefaults($defaults);
    
    /**
     * Returns the default table creatoin values as an object
     * @params string $option (Optional) Returns the value for the specified option, 
     * if empty will return all default values
     * @return mixed Requested value or default value object
     */
    public function getDefaults($varname = "");
    
    /**
     * Opens/creates a connection to the database
     * @param String $user User name
     * @param String $pass Password
     * @param String $host Server host name/IP
     * @param String $database (Optional) Database
     * @return Boolean True is connection created
     */
    public function open($user,$pass,$host,$database = "");

    /**
     * Sets the current database as active
     * @param String $db Database name
     * @return Boolean True on success
     */
    public function setdb($db);
    
    /**
     * Sets session variables for current database connection
     * @param String $dbvar Session variable to set
     * @param String $value Value to assign to the session variable
     * @return Boolean True on success
     */
    public function setvar($variable,$value);

    /**
     * Returns current connection status
     * @return Boolean True if connected
     */
    public function connected();

    /**
     * Closes the current database connection
     */
    public function close();

    /**
     * Submits an SQL statement to the database
     * @param String $query Query to execute
     * @return mixed Array of the result set or the return of a passed callback
     */ 
    public function query($query);

    /**
     * Returns the results of the last successfully executed SQL statement
     * @param string $type (Optional) Type of results to return (array or resource), 
     * defaults to array
     * @return mixed MySQL result resource
     */ 
    public function results($type = "array");

    /**
     * Returns the current result set for the last successfully run sql statement
     * @param Integer $row Row to move the pointer to
     * @param mixed $field (Optional) Field to get the data from, defaults to 0 or the first field
     * @return mixed Contents of specified field or false on failure
     * @todo Move the commented <i>result</i> function the mysqli plugin
     */
    public function result($row,$field = 0);

    /**
     * Resets the pointer in the MySQL result set
     * @param Integer $pointer (Optional) Position in the result set to reset to, defaults to 0
     * @return mixed True on success, MySQL error number on failure
     */
    public function reset($pointer = 0);

    /**
     * Escapes the variable to make it safe to use in a query
     * @param String $var The variable to escape
     * @return String The escaped string
     */
    public function real_escape_string($var);

    /**
     * Insert a record into the database by generating a safe query using provided
     * parameter array
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
    public function insert($params,$returnQuery = false);

    /**
     * Update a record in the database by generating a safe query using provided
     * parameter array
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
    public function update($params,$returnQuery = false);

    /**
     * Select records from the database by generating a safe query using provided
     * parameter array
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
    public function select($params,$returnQuery = false);
    
    /**
     * Delete a set of records from the database by generating a safe query using 
     * provided parameter array
     * @param array $params A configuration array containing the following 
     * parameters:<ul>
     * <li><b>table</b> The table to perform the query on</li>
     * <li><b>where</b> An array of table fields/value pairs to use as the where clause</li>
     * </ul>
     * @param bool $returnQuery Set to True to return the compiled query without 
     * executing it.
     * @return mixed The amount of affected rows or the compiled query.
     */
    public function delete($params,$returnQuery = false);

    /**
     * Create a table in the database by generating a safe query using provided
     * parameter array
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
    public function create($params,$returnQuery = false);
}

/**
 * IPM Database Plugin Abtraction
 * 
 * @package   IPM\Core\System\Database
 */
abstract class aIPM_db_plugin {
    /**
     * Database connection identifier
     * @type resource 
     */
    public $conn;
    
    /**
     * True if a database has been selected
     * @var bool 
     */
    protected $dbsel = false;
    
    /**
     * The currently selected database name
     * @var string 
     */
    public $database;
    
    /**
     * Database Host Name
     * @var string 
     */
    protected $server;
    
    /**
     * Database user name
     * @var string 
     */
    protected $username;
    
    /**
     * Database password
     * @var string 
     */
    protected $password;
    
    /**
     * Results from the last SQL statement
     * @var resource 
     */
    protected $sqlresult;		//results from last sql statement
    
    /**
     * Table defaults for creating new database tables
     * @var object
     */
    protected $defaults;

    /**
     * True if the database PHP extension is loaded
     * @var bool 
     */
    protected $extensionLoaded = false;
    
    /**
     * Plugin Identification
     * @var string 
     */
    protected $pluginid = "mysql";
    
    /**
     * @var Integer
     * Error number returned from latest query
     */
    public $errno = 0;
    
    /**
     * Error string returned form latest query
     * @var String 
     */
    public $error = "";
    
    /**
     * The number of rows affected by the last update query
     * @var Integer 
     */
    public $affected_rows = 0;
    
    /**
     * The row count of the last select statement
     * @var integer
     */
    public $num_rows = 0;
    
    /**
     * The auto incremeted ID generated by the last insert query
     * @var Integer 
     */
    public $insert_id = 0;
    
    /**
     * The last query executed
     * @var object 
     */
    public $lastQuery;
    
    /**
     * Prefix types or roles for creating database tables
     * @var array 
     */
    protected $prefix = Array(
        "system" => "system",
        "content" => "content",
        "modules" => "modules",
        "default" => "content"
    );
    
    /**
     * Array to hold the query queue
     * @var Array 
     */
    protected $queue = Array();
    
    /**
     * List of field types for creating database tables.  This is used to make 
     * sure that only valid field types are allowed to be created
     * @type array 
     */
    protected $fieldTypes = Array(
        "NUMERIC" => Array("TINYINT","SMALLINT","MEDIUMINT","INT","BIGINT","DECIMAL","FLOAT","DOUBLE","REAL","BIT","BOOLEAN","SERIAL"),
        "DATETIME" => Array("DATE","DATETIME","TIMESTAMP","TIME","YEAR"),
        "STRING" => Array("CHAR","VARCHAR","TINYTEXT","TEXT","MEDIUMTEXT","LONGTEXT","BINARY","VARBINARY","TINYBLOB","MEDIUMBLOB","BLOB","LONGBLOB","ENUM","SET"),
        "SPATIAL" => Array("GEOMETRY","POINT","LINESTRING","POLYGON","MULTIPOINT","MULTILINESTRING","MULTIPOLYGON","GEOMETRYCOLLECTION")
    );

    /**
     * Constructor - sets default connection if it exists and initializes defaults
     * @constructor
     */
    public function __construct($conn = false) {
        $this->defaults = new StdClass();
        $this->defaults->charset = "latin1";
        $this->defaults->collate = "latin1_general_ci";
        $this->defaults->engine = "MyISAM";
        $this->defaults->null = false;
        $this->defaults->prefix = json_decode('{system":"ipmsys","default":"ipmmod"}',true);
        if ($conn) $this->conn = $conn;
    }
    
    /**
     * Returns the plugin identifier
     * @return string Plugin ID
     */
    public function getType() { return $this->pluginid; }
    
    /**
     * Returns the name of the current active database
     * @return String Database name, false if a database is not selected
     */
    public function getdb() {
        if ($this->dbsel) return $this->database;
        return false;
    }
    
}

?>