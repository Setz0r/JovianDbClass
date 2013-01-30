<?
require_once("ipm.interface.db.plugin.php");
require_once("ipm.class.db.engine.php");
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
 * IPM Master Database Class
 * 
 * @package   IPM\Core\System\Database
 */
class IPM_db extends IPM_db_engine { //MAIN DATABASE CLASS
    /**
     * Database connection identifier
     * @type resource 
     */
    public $conn;
    
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
     * Alias of <i>$insert_id</i>, deprecated use <i>$insert_id</i> instead
     * @var Integer 
     * @deprecated use <i>$insert_id</i> instead
     * @legacy
     */
    public $lastid = 0;
    
    /**
     * An array that keeps track of query locations in the query history
     * @var array 
     */
    protected $ex = Array();

    /**
     * Table engine list array
     * @var array 
     */
    protected $tbEngines = Array(); //EACH TABLE GETS IT'S OWN DB ENGINE
    
    /**
     * Table registration array
     * @var array 
     */
    protected $registerArray = Array(); //TABLE REGISTRATION ARRAY
    
    /**
     * Table details array. This array is populated from a cache file and is 
     * required for table registration.
     * @var array 
     */
    protected $dbTables = Array(); //DATABASE TABLE STRUCTURE ARRAY
    
    /**
     * Flag to automatically create the table structure cache file.  If on, 
     * the table structure cache file will be created at the registered interval
     * @var bool 
     */
    protected $autoCreateStructure = true;
    
    /**
     * The number of hours until the table structure cache file is automatcially 
     * recreated.  The time is based on the file timestamp.
     * @var int 
     */
    protected $autoCreateIntervalHours = 1;
    
    /**
     * The filename for the table structure cache file
     * @var stirng 
     */
    protected $tableStructureCacheFile = "ipm.class.db.tables.cfg";
    
    /**
     * The filename for the table registration information
     * @var string 
     */
    protected $registrationFile = "ipm.class.db.registration.cfg";
    
    /**
     * Registration database configration array, used when the file does not exist. 
     * Will always attempt to load the database when the file is missing or 
     * <i>$regUsing</i> is set to database.
     * @var array 
     */
    protected $regDbConfig = Array(
        "value" => "value",
        "table" => TABLE_CONFIG,
        "id" => Array("field"=>"varname","value"=>"DB_REGISTRATION")
    );
    
    /**
     * Determines if the registration should use the <i>database</i> or <i>file</i>, 
     * defaults to <i>file</i>.
     * @var type 
     */
    protected $regUsing = "file";

    private function IS_SET($flag,$bit) { return $flag & $bit; } //BITWISE FUNCTION
    private function SET_BIT(&$var,$bit) { $var |= $bit; } //BITWISE FUNCTION
    private function REMOVE_BIT(&$var,$bit) { $var &= ~$bit; } //BITWISE FUNCTION
    private function TOGGLE_BIT(&$var,$bit) { $var = $var ^ $bit; } //BITWISE FUNCTION

    /*public function loadDefaults() { //LOAD DATABASE DEFAULT PARAMETERS FROM DATABASE
        $sQueryConfig = "select * from ".TABLE_CONFIG." where category='database' and active=1"; //DATABASE CONFIG CONFIG QUERY
        $results = $this->query($sQueryConfig); //EXECUTE DATABASE DATA CALL
        foreach($results as $row) { //WHILE LOOP SUBSTITUTION FOR RETURN ARRAY INSTEAD OF DATABASE RESULT SET
            if ($row["varname"] == "DB_DEFAULT_CHARSET") $defaults["charset"] = $row["value"]; //SET DEFAULT CHARACTERSET FOR CREATING TABLES
            if ($row["varname"] == "DB_COLLATE") $defaults["collate"] = $row["value"]; //SET COLLATE FOR CREATING TABLES
            if ($row["varname"] == "DB_ENGINE") $defaults["engine"] = $row["value"]; //SET DATABASE ENGINE FOR CREATING TABLES
            if ($row["varname"] == "DB_NULLVALUE") $defaults["null"] = ($row["value"] == "false") ? false:true; //SET NULLVALUE FOR CREATING TBALES
            if ($row["varname"] == "DB_PREFIX") $defaults["prefix"] = json_decode($row["value"],true); //SET DATABASE PREFIXES FOR ACCESSING AND CREATING TABLES
        } //END FOREACH
        $this->plugin->setDefaults($defaults); //SET THE DEFAULTS TO THE LOCAL DB ENGINE
    }*/ //END FUNCTION

    /**
     * Returns the structure of a table
     * @param string $table (Optional) The name of the table
     * @return array An array with table structure, if a table name is not provided,
     * all table structures will be returned.
     */
    public function getStructure($table = "") {
        if ($table) return $this->dbTables[$table];
        else return $this->dbTables;
    }
    
    /**
     * Creates a table engine instance
     * A table engine instance is an instance of this database class, with limited 
     * functionality, that performs actions only on the table the engine is for
     * @param string $engine Name of the engine
     * @param string $tablename Name of the table, used for creating a table constant
     */
    protected function createEngine($engine, $tablename = "") {
//        if ($tablename == "") $tablename = $engine;
        if (!defined($engine)) define($engine,$this->getdb().".".$tablename); //CREATE CONSTANT FOR TABLE REGISTERED NAME
        $this->tbEngines[$engine] = new aIPM_db_engine($this->pluginType,$this->conn); //INTANTIATE DATABASE ENGINE
        
        $config["table"] = $this->getdb().".".$tablename; //CREATE TABLE CONFIG INFORMATION
        $config["fields"] = $this->dbTables[$tablename]["fields"]; //CREATE TABLE CONFIG INFORMATION
        $config["query"] = $this->dbTables[$tablename]["query"]; //CREATE TABLE CONFIG INFORMATION
        $this->tbEngines[$engine]->setTableConfig($config); //SET TABLE CONFIG INFORMATION IN NEW DB ENGINE
    }

    /**
     * Creates the table structure cache file
     * @return bool True if the file was created
     */
    public function createTableStructureCache() {
        $dbTables = Array();
        $sQueryShowTables = "show tables"; //GET A LIST OF DATABASE TABLES
        $results = $this->query($sQueryShowTables); //EXECUTE DATABASE DATA CALL
        for ($x = 0; $x < count($results); $x++) //LOOP THROUGH RESULT ARRAY TO SET TABLE STRUCTURE ARRAY
        foreach ($results[$x] as $db => $table) $dbTables[$table] = Array("fields"=>Array(),"query"=>""); //SET TABLE STRUCTURE ARRAY WITH EMPTY ARRAYS
        unset($results); //FREE RESULTS VARIABLE FOR PROCESSING DIFFERENT RESULTS

        foreach ($dbTables as $tablename => $v) { //LOOP THROUGH TABLE STRUCTURE ARRAY TO FILL IN DATA
            $sQueryExplain = "explain `".$tablename."`"; //GET TABLE STRUCTURE
            $results = $this->query($sQueryExplain); //EXECUTE DATABASE DATA CALL
            for ($x = 0; $x < count($results); $x++) { //LOOP THROUGH FIELDS TO FILL TABLE STRUCTURE ARRAY
                $temp = explode("(",$results[$x]["Type"]); //SEPERATE FIELD TYPE FROM LENGTH
                $dbTables[$tablename]["fields"][] = Array( //FILL FIELDS DATA IN STRUCTURE ARRAY
                    "name" => $results[$x]["Field"], //FIELD NAME
                    "key" => $results[$x]["Key"], //TYPE OF KEY FOR FIELD (PRIMARY/SECONDARY)
                    "type" => $temp[0], //FIELD TYPE (INT,VARCHAR,ECT)
                    "length" => substr($temp[1],0,strlen($temp[1])-1), //LENGTH OF FIELD DATA
                    "null" => (strtoupper($results[$x]["null"]) == "YES")? true:false, //NULL ALLOWED?
                    "auto" => ($results[$x]["Extra"] == "auto_increment") ? true:false, //AUTO INCREMENT SET?
                    "extra" => $results[$x]["Extra"] //SET ANY OTHER EXTRA VALUES
                ); //CLOSE ARRAY
            } //END FOR
            $sQueryShowCreate = "show create table `".$tablename."`"; //GET TABLE CREATE SQL QUERY
            $results = $this->query($sQueryShowCreate); //EXECUTE DATABASE DATA CALL
            $dbTables[$tablename]["query"] = $results[0]["Create Table"]; //SET CREATE SQL IN STRUCTURE ARRAY
        }
        
        $structure = serialize($dbTables);
        $structure = base64_encode($structure);
        $written = file_put_contents(__dir__."/".$this->tableStructureCacheFile,$structure);
        return ($written > 0);
    }
    
    /**
     * Loads the table structure cache into the dbTables array
     */
    protected function loadTableStructure() {
        $createfile = false;
        $path = __dir__;
        if (file_exists($path."/".$this->tableStructureCacheFile)) {
            $timestamp = time();
            $interval = 60 * 60 * $this->autoCreateIntervalHours;
            $filetime = filemtime($path."/".$this->tableStructureCacheFile);
            if (($timestamp - $filetime) > $interval) $createfile = true;
        } else $createfile = true;

        if ($this->autoCreateStructure === true && $createfile === true)
            $this->createTableStructureCache();
        
        if (file_exists($path."/".$this->tableStructureCacheFile)) {
            $structure = file_get_contents($path."/".$this->tableStructureCacheFile);
            $structure = base64_decode($structure);
            $this->dbTables = unserialize($structure);
        }
        unset($structure);
    }

    /**
     * Loads the table registration.  All tables are given a table engine 
     * instance
     */
    public function loadRegistration() { //LOAD TABLE REGISTRATION
        $path = __dir__;
        if (file_exists($path."/".$this->registrationFile) && $this->regUsing == "file") $restration = file_get_contents($path."/".$this->registrationFile);
        else {
            $sQueryConfig = "select {$this->regDbConfig["value"]} as registration from {$this->regDbConfig["table"]} where {$this->regDbConfig["id"]["field"]}='{$this->regDbConfig["id"]["value"]}'"; //LOAD DATABASE REGISTRATION
            $results = $this->query($sQueryConfig); //EXECUTE DATABASE DATA CALL
            $restration = $results[0]["registration"];
        }
        $this->registerArray = json_decode($restration,true); //SET DATABASE REGISTRY
        foreach($this->registerArray as $table) { //LOOP THROUGH REGISTERED TABLES AND CREATE A DB ENGINE
            $engine = $table["varname"]; //INITIALIZE ENGINE NAME
            $this->createEngine($engine,$table["tablename"]);
        } //END FOREACH
        
//        foreach($this->dbTables as $table => $data) { //LOOP THROUGH TABLES TO DETERMINE IF A TABLE NEEDS TO BE REGISTERED
//            if (!$this->isRegistered($table)) { //REGISTER THE TABLE IF NOT REGISTERED
//                $tablename = $table; //TABLE NAME FROM STRUCTURE
//                $prefixFound = false; //INITIALIZED PREFIX FOUND BOOLEAN
//                foreach ($this->getDefaults("prefix") as $role => $prefix) { //LOOP THROUGH POSSIBLE PREFIXES TO DETERMINE IF TABLE IS USING ONE
//                    if (preg_match('/'.$prefix.'_/',$table)) { //CHECK IF A PREFIX IF PART OF THE TABLE NAME
//                        $engine = strtoupper(str_replace($prefix."_","table_",$table)); //REMOVE THE PREFIX AND ADD GENERIC PREFIX FOR REGISTERED NAME
//                        $prefixFound = true; //SET PREFIX FOUND
//                        break; //END LOOP
//                    } //END IF
//                } //END FOR
//                if (!$prefixFound) $engine = strtoupper("table_".$table); //IF NO PREFIX FOUND, ADD GENERIC PREFIX
//                $this->createEngine($engine,$tablename); //REGISTER THE TABLE
//            } //END IF
//        } //END FOREACH
//        echo 'IPM_db_engine, loadRegistration - $this->tbEngines: '.print_r($this->tbEngines,true)."<br>\r\n";

    } //END FUNCTION

    /**
     * Registers a table with the system
     * @param string $engine Name of the table referenced, use as the engine name and table name constant
     * @param string $table Name of the table as it appears in the database
     * @param string $query Table creation query, if specified, will execute the query to attempt to create a new table
     * @todo Add functionality for creating the new table
     */
    public function register($engine,$table,$query = false) { //REGISTERING THE TABLE ALLOWS FOR CUSTOM NAMING OF THE DATABASE ENGINE AND CONSTANT
        if ($this->isRegistered($table) || $this->isRegistered($engine,"engine")) { //ONLY REGISTER AN UNREGISTERED TABLE
            foreach($this->registerArray as $index => $rtable) { //LOOP THROUGH THE LIST OF REGISTERED FUNCTIONS
                if ($rtable["varname"] == $engine || $rtable["tablename"] == $table) {
                    unset($this->registerArray[$index]);
                    break;
                }
            } //END LOOP
        }
        $this->registerArray[] = Array("varname"=>$engine,"tablename"=>$table); //ADD THE NAME TO THE REGISTERED ARRAY
        
        $path = __dir__;
        if ($this->regUsing == "file") file_put_contents($path."/".$this->registrationFile,json_encode($this->registerArray));
        else {
            $uQueryRegister = "update {$this->regDbConfig["table"]} set {$this->regDbConfig["value"]}='".$this->real_escape_string(json_encode($this->registerArray))."' where {$this->regDbConfig["id"]["field"]}='{$this->regDbConfig["id"]["value"]}'"; //UPDATA REGISTRATION CONFIG VARIABLE
            $this->query($uQueryRegister); //EXECUTE DATABASE DATA CALL
        }
            
        if (!array_key_exists($engine, $this->tbEngines)) $this->createEngine($engine,$table);
    } //END FUNCTION

    public function __get($name) { //CLASS OVERLOADING USED TO CALL TABLE ENGINES AS CLASS PROPERTIES
        $name = strtoupper($name); //PROPERTY NAME
        if (array_key_exists($name, $this->tbEngines)) { //CHECK ENGINE ARRAY FOR TABLE
            return $this->tbEngines[$name]; //RETURN TABLE ENGINE IF FOUND
        } else return ""; // RETURN NOTHING IF NOT FOUND
    } //END FUNCTION

    /**
     * Determine if a table is already registered
     * @param string $name Name of the table, registered name or database table name
     * @param string $type (Optional) does nothing
     * @return bool True if the table is registered
     * @todo Either impliment table param or remove it
     */
    public function isRegistered($name,$type = "table") { //FUNCTION TO CHECK IF THE TABLE SPECIFIED IS REGISTERED
        foreach($this->registerArray as $rtable) { //LOOP THROUGH THE LIST OF REGISTERED FUNCTIONS
            if ($rtable["varname"] == $name || $rtable["tablename"] == $name) return true; //CHECK FOR THE DB TABLE NAME OR REGISTERED TABLE NAME
        } //END LOOP
        return false; //RETURN FALSE IF NOT REGISTERED
    } //END FUNCTION
	
	
//	public function rowcount($exid = false) { return $this->engine->rowcount($exid); } //RETURNS NUMBER OF ROWS RETURNED FROM DATABASE CALL FROM LOCAL ENGINE RESULT SET
        
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
        $results = parent::query($query,$tokens,$closure,$closureParams);
        
        $this->errno = $this->plugin->errno;
        $this->error = $this->plugin->error;
        $this->affected_rows = $this->plugin->affected_rows;
        $this->insert_id = $this->plugin->insert_id;
        $this->num_rows = $this->plugin->num_rows;

        //depricated
        $this->lastid = $this->plugin->insert_id;

        return $results;
    }
    
    /**
     * Returns the results of the last successfully executed SQL statement
     * <p>Note: table engine instances cannot return the MySQL result resource, 
     * only the result array is allowed to be retrieved</p>
     * @param string $type (Optional) Type of results to return (array or resource), 
     * defaults to resource
     * @return mixed MySQL result resource or array
     */ 
    public function results($type = "resource") {
        return $this->plugin->results($type);
    }
    
    /**
     * Sets the current database as active, loads table defaults, and loads
     * table registeration
     * @param String $db Database name
     * @return Boolean True on success
     */
    public function setdb($db) {
        if ($this->plugin->setdb($db)) {
            //$this->loadDefaults(); //ONCE THE DATABASE IS SET LOAD THE DATABASE DEFAULTS
            $this->loadTableStructure();
            $this->loadRegistration(); //ONCE THE DATABASE IS SET LOAD THE TABLE REGISTRATION
            return true;
        }
        return false;
    }
    
    /**
     * Alias of <i>insert_id</i>, deprecated use <i>$insert_id</i> property instead
     * @return Integer Insert ID
     * @see $insert_id
     * @deprecated use <i>$insert_id</i> property instead
     * @legacy
     */
    public function getlastID() { return $this->plugin->insert_id; }
    
    /**
     * Alias of <i>num_rows()</i>, deprecated use <i>$num_rows</i> property instead
     * @return int The count 
     * @see $num_rows
     * @deprecated use <i>$num_rows</i> property instead
     * @legacy
     */
    public function rowcount() { return $this->plugin->num_rows; }
    
    /**
     * Alias of <i>error()</i>, deprecated use <i>$error</i> property instead
     * @return int Error code, 0 if no error
     * @see $error
     * @deprecated use <i>$error</i> property instead
     * @legacy
     */
    public function lasterror() { return $this->plugin->error; } //RETURNS THE ERROR GENERATED BY THE LAST DATABASE CALL ON LOCAL ENGINE
    
    /**
     * Alias of <i>reset()</i>, deprecated use <i>reset()</i> instead
     * @param Integer $pointer (Optional) Position in the result set to reset to, defaults to 0
     * @return mixed True on success, MySQL error number on failure
     * @see reset()
     * @deprecated use <i>reset()</i> instead
     * @legacy
     */
    public function resreset($pointer = 0) { return $this->plugin->reset($pointer = 0); } //RESET THE RESULT SET RETURNED BY LOCAL ENGINE

    /**
     * Submits an SQL statement to the database and keeps a reference to the 
     * query's postion within the history array
     * <p>Note: Not permitted on table engine instances</p>
     * @param String $refid Reference ID used to retrieve the results
     * @param String $query Query to execute
     * @param Mixed $tokens Values to replace the tokens with or custom callback 
     * function, see the <u>$callback</u> parameter for detauls on closures.  If 
     * a closure is passed instead of a token array, the <u>$callback</u> parameter 
     * will not be processed.
     * @param Closure $callback Custom callback function.  Callbacks are passed 
     * the following parameters:<ul>
     * <li><b>results</b> An array containing the results of the query</li>
     * <li><b>query</b> An object containing the defaults of the query</li>
     * </ul>
     * @return mixed Array of the result set or the return of a passed callback
     */ 
    public function eXquery($refid,$query,$tokens = false,&$callback=false,&$params = false) {
        $results = $this->query($query,$tokens,$callback);
        $lastQuery = $this->plugin->lastQuery;
        $lastQuery->results = (Object)Array(
            "array" => $results,
            "resource" => $this->plugin->results("resource")
        );
        if ($this->saveHistory === false) $this->history[] = $this->plugin->lastQuery;
        $this->ex[$refid] = count($this->history) - 1;
        return $results;
    }
    
    /**
     * Returns the results of the SQL statement in the history postion identified 
     * by the given <i>$refid</i>
     * <p>Note: table engine instances cannot return the MySQL result resource, 
     * only the result array is allowed to be retrieved</p>
     * @param String $refid Reference ID used to retrieve the results
     * @param string $type (Optional) Type of results to return (array or resource), 
     * defaults to resource
     * @return mixed MySQL result resource or array
     */ 
    public function eXresults($refid,$type = "resource") {
        $i = $this->ex[$refid];
        return $this->history[$i]->results->{$type};
    }
    
    /**
     * Returns the count of rows for the query at the position of the history 
     * array specified by the given reference ID
     * @param String $refid Reference ID used to retrieve the results
     * @return Integer The row count
     */
    public function eXrowcount($refid) {
        $i = $this->ex[$refid];
        return $this->history[$i]->count;
    }

    /**
     * Resets pointer back to specified point of the query in the position of the 
     * history array specified by the given reference id
     * @param String $refid Reference ID used to retrieve the results
     * @param Integer $pointer (Optional) Position in the result set to reset to, defaults to 0
     * @return Boolean True on success
     */
    public function eXresreset($refid,$pointer = 0) {
        $i = $this->ex[$refid];
        reset($this->history[$i]->results->array);
        mysql_data_seek($this->history[$i]->results->resource,$pointer);
        if (mysql_errno($this->conn)) return mysql_error($this->conn);
        return false;
    }
    
}

?>