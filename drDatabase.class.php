<?php

class drDatabase {
    
    // ----------------
    // Member variables
    // ----------------
    
    private $m_servername;
    private $m_username;
    private $m_password;
    private $m_dbname;

    protected $m_connection = NULL;


    // ------------------------
    // Constructor / destructor
    // ------------------------

    function __construct($config) {

        // Store the database configuration
        $this->m_servername = $config["db_servername"];
        $this->m_username = $config["db_username"];
        $this->m_password = $config["db_password"];
        $this->m_dbname = $config["db_name"];

        // Connect to the database
        $this->connect();
    }

    function __destruct() {
        $this->disconnect();
    }


    // ----------------
    // Helper functions
    // ----------------

    // Connect to database
    protected function connect() {

        if (is_null($this->m_connection)) {

            // Create new connection
            $this->m_connection = new mysqli($this->m_servername, $this->m_username, $this->m_password, $this->m_dbname);

            // Check connection
            if ($conn->connect_error) {
                echo("Database connection failed: " . $conn->connect_error);
                return(FALSE);
            }
        }

        return(TRUE);
    }

    // Disconnect from database
    protected function disconnect() {
        if (!is_null($this->m_connection)) {
            $this->m_connection->close();
            unset($this->m_connection);
        }
    }


    // ----------------
    // Public functions
    // ----------------

    // ---------------------------
    // Send SQL query
    // ---------------------------
    function query($sql) {

        $bReturn = FALSE;

        if ($this->connect()) {

            if ($this->m_connection->query($sql)) {
                $bReturn = TRUE;
            } else {
                echo("Database query failed: " . mysqli_error($this->m_connection) . "\n"); 
                echo($sql . "\n\n");
            }
        }

        return $bReturn;
    }

    // ----------
    // Drop table
    // ----------
    function dropTable($tableName) {
        $sql = "DROP TABLE " . $tableName . ";";
        return $this->query($sql);
    }

    // ------------
    // Create table
    // ------------
    function createTable($tableName, $columns) {

        $default = "TEXT COLLATE utf8_general_ci";

        $sql = "CREATE TABLE " . $tableName . " ( ";
        foreach($columns as $k) {
            $name = NULL;
            $datatype = NULL;
            $attributes = NULL;

            if (is_array($k)) {
                if (array_key_exists("name", $k)) {
                    $name = $k["name"];
                    if (array_key_exists("datatype", $k))
                        $datatype = $k["datatype"];
                    if (array_key_exists("datalength", $k) and !is_null($datatype))
                        $datatype .= "(" . $k["datalength"] . ")";
                    if (array_key_exists("attributes", $k))
                        $attributes = $k["attributes"];
                } else {
                    echo "Create table failed: missing column name.\n";
                    print_r($k);
                    echo "\n";
                    return FALSE;
                }

                $sql .= $name . " " . $datatype . " " . $attributes . ", ";

            } else {
                $sql .= $k . " " . $default . ", ";
            }
        }     
        $sql = rtrim($sql, " ,");
        $sql .= " );";  

        return $this->query($sql);
    }

    // ------------------------
    // Insert values into table
    // ------------------------
    function insertIntoValues($tableName, $rows, $datatypes=NULL) {

        // Assert that we have a mysqli object so we can use the escape string method below
        if (!$this->connect())
            return FALSE;

        // Loop over rows and add to SQL
        $sql = "INSERT INTO " . $tableName . " VALUES ";
        foreach ($rows as $item) {
            $sql .=  "(";
            foreach ($item as $index=>$i) {
                if (is_null($i)) {
                    $sql .= "NULL, ";
                } else if (is_numeric($i)) {
                    $sql .= $i . ", ";
                } else {
                    $temp = "'" . mysqli_real_escape_string($this->m_connection, $i) . "', ";
                    if (!is_null($datatypes)) {
                        if ($datatypes[$index] == "TIME") {
                            $temp = "TIME( STR_TO_DATE( '" . $i . "', '%h:%i %p' ) ), ";
                        }
                    }
                    $sql .= $temp;
                }
            }
            $sql = rtrim($sql, " ,");
            $sql .= "), ";
        }
        $sql = rtrim($sql, " ,");
        $sql .= ";";

        // Execute the query
        return $this->query($sql);
    }



} // end class


?>