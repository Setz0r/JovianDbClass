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
 * IPM Microsoft's SQL Server Database Plugin
 * 
 * <b>Note: This is sqlsrv extension, not PHP's mssql extension
 * 
 * @package   IPM\Core\System\Database
 */
class IPM_db_plugin_sqlsrv extends aIPM_db_plugin implements iIPM_db_plugin {

    /**
     * Result of the last SQL select state as an array
     * @var array 
     */
    private $sqlarray = array();

    /**
     * Constructor - determines if the MySQL PHP extension is loaded and sets 
     * default connection if passed to the constructor
     * @param resource $conn (Optional) If there is already and open connection 
     * to a database, it can be passed into the constructor and the database 
     * plugin will use it instead of creating it's own.
     * @constructor
     */
    public function __construct($conn = false) {
        if (function_exists("sqlsrv_connect")) $this->extensionLoaded = true;
        parent::__construct($conn);
    }

    /**
     * Sets the default table creation values
     * @param mixed Defaults object, could also be an array
     */
    public function setDefaults($defaults) {
        if (is_array($defaults)) $defaults = (Object)$defaults;
        $this->defaults = $defaults;
    }
    
    /**
     * Returns the default table creatoin values as an object
     * @params string $option (Optional) Returns the value for the specified option, 
     * if empty will return all default values
     * @return mixed Requested value or default value object
     */
    public function getDefaults($option = "") { return ($option == "") ? $this->defaults:$this->defaults->$option; }

    private function setError($code,$message = "") {
        if (!is_array($code)) {
            $this->errno = $code;
            $this->error = $message;
        } else {
            $this->errno = 1;
            $this->error = Array();
            for($x = 0; $x < count($code); $x++)
            $this->error[] = $code[$x]["message"];
        }
    }
    
    /**
     * Opens/creates a connection to the database
     * @param String $user User name
     * @param String $pass Password
     * @param String $host Server host name/IP
     * @param String $database Database
     * @return Boolean True is connection created
     */
    public function open($user,$pass,$host,$database = "") {
        if ($this->extensionLoaded === false) return false;
        if (!$database) {
            $this->setError(1,"Database is required on Open()");
            return false;
        }
        
        $this->errno = 0;
        $this->error = "";
        $this->conn = sqlsrv_connect($host,Array(
            "UID" => $user,
            "PWD" => $pass,
            "Database"=> $database
        ));
        if (!$this->conn) {
            $this->setError(sqlsrv_errors(SQLSRV_ERR_ERRORS));
            return false;
        }
        $this->server = $host;
        $this->username = $user;
        $this->password = $pass;
        return true;
    }

    /**
     * Sets the current database as active
     * @param String $db Database name
     * @return Boolean True on success
     */
    public function setdb($db) {
        if ($this->extensionLoaded === false) return false;
        if (!$this->conn) return false;
        
        $this->dbsel = true;
        $this->database = $db;
        return true;
    }

    /**
     * Sets session variables for current database connection
     * @param String $dbvar Session variable to set
     * @param String $value Value to assign to the session variable
     * @return Boolean True on success
     * @deprecated Not used in MSSQL
     */
    public function setvar($variable,$value) {
        return true;
    }
        
    /**
     * Returns current connection status
     * @return Boolean True if connected
     */
    public function connected() {
        if ($this->conn) return true;
        return false;
    }

    /**
     * Closes the current database connection
     */
    public function close() {
        if ($this->extensionLoaded === false) return false;
        
        sqlsrv_free_stmt($this->sqlresult);
        sqlsrv_close($this->conn);
        $this->num_rows = 0;
        $this->dbsel = false;
        $this->database = "";
        return true;
    }

    /**
     * Submits an SQL statement to the database
     * @param String $query Query to execute
     * @return mixed Array of the result set or the return of a passed callback
     */ 
    public function query($query) {
        if ($this->extensionLoaded === false) return false;
        if ($this->conn === false) {
            $this->error = "Database connection is not open";
            return false;
        }
        
        $this->num_rows = 0;
        $this->insert_id = 0;
        $this->affected_rows = 0;
        
        $this->lastQuery = $query;
        $time_start = microtime(true);
        
        $this->sqlresult = sqlsrv_query($this->conn,$query,null,Array("Scrollable"=>SQLSRV_CURSOR_KEYSET));
        $queryTime = number_format(microtime(true) - $time_start,4);
        
        $errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
        if (!$errors) { $this->errno = 0; $this->error = ""; }
        else {
            $this->errno = $errors[count($errors)-1]["code"];
            $this->error = $errors[count($errors)-1]["message"];
        }
        
        if (!$this->errno) {
            $this->affected_rows = @sqlsrv_rows_affected($this->sqlresult);
            $this->num_rows = @sqlsrv_num_rows($this->sqlresult);
            $results = $this->returnArray();
            
            $this->lastQuery = (Object)Array(
                "query" => $query,
                "qtime" => $queryTime,
                "count" => $this->num_rows,
                "insid" => $this->insert_id,
                //"from" => $_SERVER["SCRIPT_FILENAME"],
                //"file" => __file__,
                "affected" => $this->affected_rows,
                /*"results" => (Object)Array(
                    "array" => $results,
                    "resource" => $this->sqlresult
                ),*/
                "error" => (Object)Array(
                    "code" => $this->errno,
                    "text" => $this->error
                )
            );
            
            return $results;
        }		
        return false;
    }

    /**
     * Returns the results of the last successfully executed SQL statement
     * @param string $type (Optional) Type of results to return (array or resource), 
     * defaults to array
     * @return mixed MySQL result resource
     */ 
    public function results($type = "array") {
        switch($type) {
            case "resource": if ($this->sqlresult) return $this->sqlresult; break;
            default: if ($this->sqlarray) return $this->sqlarray; break;
        }
        return false;
    }

    /**
     * Returns the current result set for the last successfully run sql statement
     * @param Integer $row Row to move the pointer to
     * @param mixed $field (Optional) Field to get the data from, defaults to 0 or the first field
     * @return mixed Contents of specified field or false on failure
     * @todo Move the commented <i>result</i> function the mysqli plugin
     */
    public function result($row,$field = 0) {
        if ($this->sqlresult) {
            $datarow = sqlsrv_fetch_array($this->sqlresult,SQLSRV_FETCH_BOTH,SQLSRV_SCROLL_ABSOLUTE,$row);
            return $datarow[$field];
        }
        return false;
    }

    /**
     * Resets the pointer in the MySQL result set
     * @param Integer $pointer (Optional) Position in the result set to reset to, defaults to 0
     * @return mixed True on success, MySQL error number on failure
     * @fixme This method might not work.  Microsoft didn't see fit to include a 
     * function similar to mysql_data_seek, so all i can hope for is fetching the 
     * first result in the set and then trying to fetch the prior row in the hopes 
     * that when fetching the next row it'll actually fetch the first row.
     */
    public function reset($pointer = 0) {
        if (!$this->errno) {
            reset($this->sqlarray);
            sqlsrv_fetch_array($this->results("resource"),SQLSRV_FETCH_ASSOC,SQLSRV_SCROLL_FIRST);
            $result = sqlsrv_fetch_array($this->results("resource"),SQLSRV_FETCH_ASSOC,SQLSRV_SCROLL_PRIOR);
            if ($result) return true;
        }
        return false;
    }

    /**
     * Converts a MySQL data set to a standard php array
     * @return mixed An array with all records of the result set or fales on failure
     */
    private function returnArray() {
        if (!$this->errno) {
            $this->sqlarray = Array();
            $this->reset();
            while ($row = sqlsrv_fetch_array($this->results("resource"),SQLSRV_FETCH_ASSOC)) {
                $this->sqlarray[] = $row;
            }
            $this->reset();
            return $this->sqlarray;
        }
        return false;
    }

    /**
     * Escapes the variable to make it safe to use in a query
     * @param String $var The variable to escape
     * @return String The escaped string
     */
    public function real_escape_string($var) {
        if (function_exists("mysql_connect")) $var = mysql_real_escape_string($var);
        else $var = addslashes($var);
        return $var;
    }

    /**
     * Converts MSSQL field types to MySQL field types for create queries
     * @param string $type The MSSQL field type
     * @param int $length The length of the field
     * @return string The MySQL field type
     */
    public function myFieldType($type, $length = 1) {
        if ($length == "" || $length == 0) $length = 1;
        switch ($type) {
            case '1': return "char(".$length.") collate latin1_general_ci"; break;
            case '2': return "decimal(".$length.")"; break;
            case '3': return "decimal(".$length.")"; break;
            case '4': return "int(".$length.")"; break;
            case '5': return "smallint(".$length.")"; break;
            case '6': return "float(".$length.")"; break;
            case '7': return "double(".$length.")"; break;
            case '91': return "date"; break;
            case '93': return "datetime"; break;
            case '-5': return "bigint(".$length.")"; break;
            case '-6': return "tinyint(".$length.")"; break;
            case '-1': return "text"; break;
            case '-2': return "timestamp"; break;
            case '-7': return "char(1)"; break;
            case '12': return "varchar(".$length.") collate latin1_general_ci"; break;
            default: return "varchar(".$length.") collate latin1_general_ci"; break;
        }
    }
    
    /**
     * Simulates the <i>show</i> command in MySQL.  Paramters expect also expect 
     * a syntax similar to the MySQL syntax.  For example:
     * <pre class="brush: js;" style="background-color:white">
     *      $reslults = $db->show("tables");
     *      ...
     *      $reslults = $db->show("columns","users_tables");
     * </pre>
     * @param string $command Show command to perform (tables, columns)
     * @param string $object (Optoinal) Depending on the command, extra data for the query
     * @return array An array of the result set formatted as a MySQL result array
     */
    public function show($command,$object = "") {
        switch ($command) {
            case "tables":
                $showQuery = "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'";
                $results = $this->query($showQuery);
                for ($x = 0; $x < count($results); $x++) $tables[$x]["Tables_in_".$results[$x]["TABLE_CATALOG"]] = $results[$x]["TABLE_NAME"];
                return $tables;
                break;
            case "columns":
                $showQuery = "SELECT * FROM information_schema.columns WHERE table_name = '".$object."' ORDER BY ordinal_position";
                $results = $this->query($showQuery);
                for ($x = 0; $x < count($results); $x++) {
                    $tables[$x] = Array( 
                        "Field" => $results[$x]["COLUMN_NAME"],
                        "Type" => $results[$x]["DATA_TYPE"]."(".((strlen($results[$x]["CHARACTER_MAXIMUM_LENGTH"]))?$results[$x]["CHARACTER_MAXIMUM_LENGTH"]:$results[$x]["NUMERIC_PRECISION"]).")",
                        "Null" => $results[$x]["IS_NULLABLE"],
                        "Key" => "",
                        "Default" => $results[$x]["COLUMN_DEFAULT"],
                        "Extra" => ""
                    );
                }
                return $tables;
                break;
        }
        return false;
    }
//    COLUMN_NAME,
//    ORDINAL_POSITION,
//    COLUMN_DEFAULT,
//    IS_NULLABLE,
//    DATA_TYPE
//    CHARACTER_MAXIMUM
//    CHARACTER_OCTET_LENGTH
//    NUMERIC_PRECISION
//    NUMERIC_PRECISION_RADIX
//    NUMERIC_SCALE
//    DATETIME_PRECISION
//    CHARACTER_SET_CATALOG
//    CHARACTER_SET_SCHEMA
//    CHARAVTER_SET_NAME
//    COLLATION_CATALOG
//    COLLATION_SCHEMA
//    COLLATION_NAME
//    DOMAIN_CATALOG
//    DOMAIN_SCHEMA
//    DOMAIN_NAME
    
    /**
     * Simulates the <i>describe</i> command in MySQL.
     * @param type $table Table name to describe
     * @return array An array of the result set formatted as a MySQL result array
     */
    public function describe($table) { return $this->show("columns",$table); }
    
    /**
     * Alias of <i>describe()</i> as is the MySQL command.
     * @param type $table Table name to describe
     * @return array An array of the result set formatted as a MySQL result array
     */
    public function explain($table) { return $this->describe($table); }
    
    /**
     * Compiles an insert query for safe execution
     * @param string $tablename The table to perform the query on
     * @param array $vars An array of table fields/value pairs
     * @return string The compiled query
     */
    private function createInsert($tablename,$vars) {
        $fields = array();
        $query = "INSERT INTO $tablename (";
        $eQueryTableMetadata = "exec sp_columns ".$tablename;
        $this->query($eQueryTableMetadata); //EXECUTE QUERY
        while($row = mysql_fetch_array($this->results("resource"))) { //LOOP THROUGH RESULT SET AND CREATE FIELDS ARRAY
            $fields[] = Array(
                "name" => $row["COLUMN_NAME"],
                "key" => $row["Key"],
                "type" => $row["TYPE_NAME"],
                "auto" => ($row["Extra"] == "auto_increment") ? true:false
            );
        }

        $queryParams = array();
        $fieldParams = array();
        for ($x = 0; $x < count($fields); $x++) {
            if(isset($vars[$fields[$x]["name"]])) {
                $fieldParams[] = $fields[$x]["name"];
                if (substr($fields[$x]["type"],0,3) == "int") $queryParams[] = ((!$fields[$x]["auto"]) ? $vars[$fields[$x]["name"]]:"null");
                else if (substr($fields[$x]["type"],0,7) == "tinyint") $queryParams[] = ((!$fields[$x]["auto"]) ? $vars[$fields[$x]["name"]]:"null");
                else $queryParams[] = ((!$fields[$x]["auto"]) ? "'".$vars[$fields[$x]["name"]]."'":"null");
            }
        }

        $query .= implode(',',$fieldParams).") VALUES(".implode(',',$queryParams).");";
        return $query;
    }

    /**
     * Compiles an update query for safe execution
     * @param string $tablename The table to perform the query on
     * @param array $vars An array of table fields/value pairs to update the record with
     * @param array $where (Optional) An array of table fields/value pairs to use as the where clause
     * @return string The compiled query
     */
    private function createUpdate($tablename,$vars,$where = array()) {
        $fields = array();
        $query = "UPDATE $tablename SET ";
        $eQueryTableMetadata = "explain ".$tablename;
        $this->query($eQueryTableMetadata); //EXECUTE QUERY
        while($row = mysql_fetch_array($this->results("resource"))) { //LOOP THROUGH RESULT SET AND CREATE FIELDS ARRAY
            $fields[] = Array(
                "name" => $row["Field"],
                "key" => $row["Key"],
                "type" => $row["Type"],
                "auto" => ($row["Extra"] == "auto_increment") ? true:false
            );
        }

        $queryParams = array();
        $whereParams = array();
        for ($x = 0; $x < count($fields); $x++) {
            if ($fields[$x]["type"] != "PRI") {
                if(isset($vars[$fields[$x]["name"]]) && !$fields[$x]["auto"]) {
                    if (substr($fields[$x]["type"],0,3) == "int") $queryParams[] = "{$fields[$x]["name"]} = ".$vars[$fields[$x]["name"]];
                    else if (substr($fields[$x]["type"],0,7) == "tinyint") $queryParams[] = "{$fields[$x]["name"]} = ".$vars[$fields[$x]["name"]];
                    else $queryParams[] = "{$fields[$x]["name"]} = '".$vars[$fields[$x]["name"]]."'";
                }
            }
        }
        for ($x = 0; $x < count($fields); $x++) {
            if(isset($where[$fields[$x]["name"]])) {
                if (substr($fields[$x]["type"],0,3) == "int") $whereParams[] = "{$fields[$x]["name"]} = ".$where[$fields[$x]["name"]];
                else if (substr($fields[$x]["type"],0,7) == "tinyint") $whereParams[] = "{$fields[$x]["name"]} = ".$where[$fields[$x]["name"]];
                else $whereParams[] = "{$fields[$x]["name"]} = '".$where[$fields[$x]["name"]]."'";
            }
        }
        $query .= implode(',',$queryParams).((count($whereParams))?" WHERE ".implode(' and ',$whereParams):"");
        return $query;
    }

    /**
     * Compiles a select query for safe execution
     * @param string $tablename The table to perform the query on
     * @param array $fieldlist (Optional) An array of table fields/value pairs to update the record with, defaults to "*"
     * @param array $where (Optional) An array of table fields/value pairs to use as the where clause
     * @param array $options (Optional) An array with various options for the query.  As follows<ul>
     * <li><b>order</b> The field to order by or an array containing the <u>fields</u> and <u>dir</u>ection to sort</li>
     * <li><b>limit</b> The number of records to query or an array containing the <u>start</u> and <u>length</u></li>
     * <li><b>group</b> The field to group by</li>
     * </ul>
     * @return string The compiled query
     */
    private function createSelect($tablename,$fieldlist = "*",$where = array(),$options = array()) {
        $fields = array(); //INITIALIZE FIELDS ARRAY

        if (is_string($options["order"])) $orderBy = " order by ".$options["order"]." asc";
        if (is_array($options["order"])) $orderBy = " order by ".$options["order"]["fields"]." ".$options["order"]["dir"];
        if (is_string($options["group"])) $groupBy = " group by ".$options["group"]["fields"];
        if (is_string($options["limit"])) $limit = " limit 0,".$options["limit"];
        if (is_array($options["limit"])) $limit = " limit ".$options["limit"]["start"].",".$options["limit"]["length"];
        if ($options["distinct"] === true) $distinct = "distinct";

        $query = "SELECT SQL_CALC_FOUND_ROWS ".$distinct." "; //INITIALIZE QUERY STRING

        ////----GET TABLE METADATA USED FOR CREATING----////
        ////----QUERIES FOR ONLY FIELDS IN TABLE    ----////
        $eQueryTableMetadata = "explain ".$tablename; //QUERY TO GET METADATA FROM TABLE
        $this->query($eQueryTableMetadata); //EXECUTE QUERY
        while($row = mysql_fetch_array($this->results("resource"))) { //LOOP THROUGH RESULT SET AND CREATE FIELDS ARRAY
            $fields[] = Array(
                "name" => $row["Field"],
                "key" => $row["Key"],
                "type" => $row["Type"],
                "auto" => ($row["Extra"] == "auto_increment") ? true:false
            );
        }

        $queryParams = array(); //INITIALIZE QUERY PARAMETERS
        $whereParams = array(); //INITIALIZE WHERE CLAUSE ARRAY
        if ($fieldlist != "*") {
            if (is_array($fieldlist)) $tmpFields = $fieldlist; 
            else $tmpFields = explode(",",$fieldlist);
            for($x = 0; $x < count($tmpFields); $x++) {
                $vars[$tmpFields[$x]] = $tmpFields[$x];
            }

            ////----CREATE FIELDS ARRAY WITH FIELDS FROM----////
            ////----THE TABLE METADATA ONLY             ----////
            for ($x = 0; $x < count($fields); $x++) {
                if(isset($vars[$fields[$x]["name"]])) {
                    $queryParams[] = $fields[$x]["name"];
                }
            }
        } else $queryParams[] = "*";

        ////----CREATE WHERE CLAUSE ARRAY WITH FIELDS----////
        ////----FROM THE TABLE METADATA ONLY         ----////
        for ($x = 0; $x < count($fields); $x++) {
            if(isset($where[$fields[$x]["name"]])) {
                if (is_array($where[$fields[$x]["name"]])) {
                    if (substr($fields[$x]["type"],0,3) == "int") $whereParams[] = "{$fields[$x]["name"]} ".$where[$fields[$x]["name"]]["oper"]." ".$where[$fields[$x]["name"]]["value"];
                    else if (substr($fields[$x]["type"],0,7) == "tinyint") $whereParams[] = "{$fields[$x]["name"]} ".$where[$fields[$x]["name"]]["oper"]." ".$where[$fields[$x]["name"]]["value"];
                    else $whereParams[] = "{$fields[$x]["name"]} ".$where[$fields[$x]["name"]]["oper"]." '".$where[$fields[$x]["name"]]["value"]."'";
                } else {
                    if (substr($fields[$x]["type"],0,3) == "int") $whereParams[] = "{$fields[$x]["name"]} = ".$where[$fields[$x]["name"]];
                    else if (substr($fields[$x]["type"],0,7) == "tinyint") $whereParams[] = "{$fields[$x]["name"]} = ".$where[$fields[$x]["name"]];
                    else $whereParams[] = "{$fields[$x]["name"]} = '".$where[$fields[$x]["name"]]."'";
                }
            }
        }

        $query .= implode(',',$queryParams)." FROM ".$tablename." ".((count($whereParams))?" WHERE ".implode(' and ',$whereParams):"").$groupBy.$orderBy.$limit;
        return $query;
    }

    /**
     * Compiles a delete query for safe execution
     * @param string $tablename The table to perform the query on
     * @param array $where An array of table fields/value pairs to use as the where clause
     * @return mixed The compiled query or false if no where clause array has no values
     */
    private function createDelete($tablename,$where) {
        if (is_array($where) && count($where)) {
            $fields = array();
            $query = "DELETE FROM $tablename WHERE ";
            $eQueryTableMetadata = "explain ".$tablename;
            $this->query($eQueryTableMetadata); //EXECUTE QUERY
            while($row = mysql_fetch_array($this->results("resource"))) { //LOOP THROUGH RESULT SET AND CREATE FIELDS ARRAY
                $fields[] = Array(
                    "name" => $row["Field"],
                    "key" => $row["Key"],
                    "type" => $row["Type"],
                    "auto" => ($row["Extra"] == "auto_increment") ? true:false
                );
            }

            $whereParams = array();
            for ($x = 0; $x < count($fields); $x++) {
                if(isset($where[$fields[$x]["name"]])) {
                    if (substr($fields[$x]["type"],0,3) == "int") $whereParams[] = "{$fields[$x]["name"]} = ".$where[$fields[$x]["name"]];
                    else if (substr($fields[$x]["type"],0,7) == "tinyint") $whereParams[] = "{$fields[$x]["name"]} = ".$where[$fields[$x]["name"]];
                    else $whereParams[] = "{$fields[$x]["name"]} = '".$where[$fields[$x]["name"]]."'";
                }
            }
            $query .= implode(' and ',$whereParams);
            return $query;
        }
        return false;
    }

    /**
     * Compiles a create table query for safe execution
     * @param string $tablename The table to perform the query on
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
     * @return string The compiled query
     */
    private function createCreate($tablename,$params) {
        if ($this->defaults === false || count($this->defaults) == 0) return false;
        $primaryKeySet = false;
        $fields = array();
        if ($this->prefix[$params["role"]] != "") $prefix = $this->prefix[$params["role"]];
        else $prefix = $this->prefix["default"];
        $tablename = $prefix."_".$tablename;

        $query = "CREATE TABLE IF NOT EXISTS ".$tablename." (";
        $fields[] = Array(
            "name" => $row["Field"],
            "key" => $row["Key"],
            "type" => $row["Type"],
            "auto" => ($row["Extra"] == "auto_increment") ? true:false
        );

        for ($x = 0; $x < count($params["fields"]); $x++) {
            $y = 0;
            unset($field);
            unset($fieldType);
            switch ($params["fields"][$x]["charset"]) {
                case ""    : $params["fields"][$x]["collate"] = $this->defaults["collate"]; break;
                case "utf8": $params["fields"][$x]["collate"] .= "utf8_unicode_ci"; break;
                default    : $params["fields"][$x]["collate"] .= "_general_ci"; break;
            }

            if ($params["fields"][$x]["auto"] === true && $primaryKeySet === true) $params["fields"][$x]["auto"] = false;
            if ($params["fields"][$x]["auto"] === true && $primaryKeySet === false) {
                $primaryKeySet = true;
                $params["primarykey"] = $params["fields"][$x]["name"];
                $params["fields"][$x]["type"] = "int";
                $params["fields"][$x]["length"] = "11";
                $params["fields"][$x]["null"] = false;
                $params["fields"][$x]["default"] = "";
            }

            for ($z = 0; $z < count($this->fieldTypes["STRING"]); $z++) {
                if (strtoupper($params["fields"][$x]["type"]) == $this->fieldTypes["STRING"][$z]) {
                    $fieldType = "string";
                    break;
                }
            }

            $field[$y++] = "`".$params["fields"][$x]["name"]."`";
            $field[$y++] = $params["fields"][$x]["type"]."(".$params["fields"][$x]["length"].")";
            if ($fieldType) {
                if ($params["fields"][$x]["charset"] != "") $field[$y++] = "CHARACTER SET ".$params["fields"][$x]["charset"];
                $field[$y++] = "COLLATE ".$params["fields"][$x]["collate"];
            }
            if ($params["fields"][$x]["null"] === true) $field[$y++] = "NULL"; else $field[$y++] = "NOT NULL";
            if ($params["fields"][$x]["default"] != "") $field[$y++] = "DEFAULT '".$params["fields"][$x]["default"]."'";
            if ($params["fields"][$x]["auto"] === true) $field[$y++] = "AUTO_INCREMENT";

            $fields[$x] = implode(",",$field);
        }
        if ($params["primarykey"] != "") $fields[] = "PRIMARY KEY (`".$params["primarykey"]."`)";

        $query .= implode(',',$fields).") ENGINE=".$this->defaults["engine"]."  DEFAULT CHARSET=".$this->defaults["charset"]." COLLATE=".$this->defaults["collate"].(($primaryKeySet)?" AUTO_INCREMENT=1":"");
        return $query;
    }

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
    public function insert($params,$returnQuery = false) {
        $sQueryAutoFunctionRequest = $this->createInsert(
            $params["table"],
            $params["fields"]
        );
        if ($returnQuery === false) {
            $this->query($sQueryAutoFunctionRequest);
            if ($this->insert_id == 0) return $this->affected_rows;
            return $this->insert_id;
        }
        return $sQueryAutoFunctionRequest;
    }

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
    public function update($params,$returnQuery = false) {
        $sQueryAutoFunctionRequest = $this->createUpdate(
            $params["table"],
            $params["fields"],
            ((isset($params["where"]))?$params["where"]:Array())
        );
        if ($returnQuery === false) {
            $this->query($sQueryAutoFunctionRequest);
            return $this->affected_rows;;
        }
        return $sQueryAutoFunctionRequest;
    }

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
    public function select($params,$returnQuery = false) {
        $sQueryAutoFunctionRequest = $this->createSelect(
            $params["table"],
            ((isset($params["fields"]))?$params["fields"]:"*"),
            ((isset($params["where"]))?$params["where"]:Array()),
            ((isset($params["options"]))?$params["options"]:Array())
        );
        if ($returnQuery === false) return $this->query($sQueryAutoFunctionRequest);
        return $sQueryAutoFunctionRequest;
    }

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
    public function delete($params,$returnQuery = false) {
        $sQueryAutoFunctionRequest = $this->createDelete(
            $params["table"],
            $params["where"]
        );
        if ($returnQuery === false) {
            $this->query($sQueryAutoFunctionRequest);
            return $this->affected_rows;;
        }
        return $sQueryAutoFunctionRequest;
    }

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
    public function create($params,$returnQuery = false) {
        $sQueryAutoFunctionRequest = $this->createCreate($params["table"],$params);
        if ($returnQuery === false) {
            $this->query($sQueryAutoFunctionRequest);
            if ($this->errno) return false;
            return true;
        }
        return $sQueryAutoFunctionRequest;
    }

}

?>