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

        if (!$bReturn) {
            drLog::log($sql, drLog::DEBUG);
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

    // -----------------------------
    // SQL for query to CREATE TABLE
    // -----------------------------
    public function getCreateTableSQL($table) {

        $sql = "CREATE TABLE " . $table->getName() . " (";
        $prefix = "";

        foreach ($table->getColumns() as $c) {
            $sql .= $prefix . $c->getSQL();
            $prefix = ", ";
        }
        $sql .= ");";

        return $sql;
    }

    // ------------------------------------------
    // SQL for query to INSERT INTO * VALUES rows
    // ------------------------------------------
    public function getInsertIntoValuesSQL($table) {

        $columns = $table->getColumns();
        $rows = $table->getRows();

        $sql = "INSERT INTO " . $table->getName() . " VALUES ";
        $row_prefix = "";

        foreach ($rows as $row) {

            $sql .=  $row_prefix . "(";
            $row_prefix = ", ";
            $entry_prefix = "";
            $index = 0;

            foreach ($columns as $column) {
            
                // If the array is associative and has this column name as a key, use the 
                // associated value
                if (isset($row[$column->getName()])) {
                    $entry = $row[$column->getName()];

                // Otherwise, assume row is a sequential array with the same order as the
                // the columns are stored in the table and take the value from the current
                // index location.
                } else {
                    $entry = $row[$index++];
                }
                

                if (is_null($entry)) {
                    $sql .= $entry_prefix . "''";
            
                } else if (is_numeric($entry)) {
                    $sql .= $entry_prefix . $entry;
            
                } else {

                    if ($column->getDatatype() == "TIME") {
                        $sql .= $entry_prefix . "TIME( STR_TO_DATE( '" . $entry . "', '%h:%i %p' ) )";
                    } else {
                        $sql .= $entry_prefix . "'" . mysqli_real_escape_string($this->m_connection, $entry) . "'";
                    }
                }

                $entry_prefix = ", ";
            }

            $sql .= ")";
        }

        $sql .= ";";

        return $sql;
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