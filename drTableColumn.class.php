<?php

class drTableColumn {
    
    // ----------------
    // Member variables
    // ----------------
    
    private $m_name;
    private $m_description;
    private $m_datatype;
    private $m_datalength;
    private $m_collate;
    private $m_notnull;
    private $m_autoincrement;
    private $m_unique;
    private $m_primarykey;    
    private $m_set;


    // ------------------------
    // Constructor / destructor
    // ------------------------
    
    function __construct($name=NULL, $desc=NULL, $datatype=NULL, $datalength=NULL, $collate=NULL, $notnull=FALSE, $autoinc=FALSE, $unique=FALSE, $primary=FALSE, $set=NULL) {

        // Init the default column attributes
        $this->set($name, $desc, $datatype, $datalength, $collate, $notnull, $autoinc, $unique, $primary, $set);
    }


    // ---------------------------
    // Public functions
    // ---------------------------
    public function set($name, $desc=NULL, $datatype=NULL, $datalength=NULL, $collate=NULL, $notnull=FALSE, $autoinc=FALSE, $unique=FALSE, $primary=FALSE, $set=NULL) {
        $this->setName($name);
        $this->setDescription($desc);
        $this->setDataType($datatype);
        $this->setDataLength($datalength);
        $this->setCollate($collate);
        $this->setNotNull($notnull);
        $this->setAutoIncrement($autoinc);
        $this->setUnique($unique);
        $this->setPrimaryKey($primary);
        $this->setSet($set);
    }


    public function setName($name) {
        $this->m_name = $name;
    }

    public function getName() {
        return $this->m_name;
    }

    public function setDescription($desc) {
        $this->m_description = $desc;
    }

    public function getDescription() {
        return $this->m_description;
    }

    public function setDataType($datatype) {
        if (is_null($datatype)) {
            $this->m_datatype = "TEXT";
        } else {
            $this->m_datatype = $datatype;
        }
    }

    public function getDataType() {
        return $this->m_datatype;
    }

    public function setDataLength($datalength) {
        $this->m_datalength = $datalength;
    }

    public function setCollate($collate) {
        $this->m_collate = $collate;
    }

    public function setNotNull($notnull) {
        $this->m_notnull = $notnull;
    }    
    
    public function setAutoIncrement($autoinc) {
        $this->m_autoincrement = $autoinc;
    }

    public function setUnique($unqiue) {
        $this->m_unique = $unique;
    } 

    public function setPrimaryKey($primary) {
        $this->m_primary = $primary;
    } 

    public function setSet($set) {
        $this->m_set = $set;
    } 

    public function getSQL() {

        $sql = $this->m_name . " " . $this->m_datatype;

        if (!is_null($this->m_datalength))
            $sql .= "(" . $this->m_datalength . ")";

        if (!is_null($this->m_collate))
            $sql .= " COLLATE " . $this->m_collate;

        if ($this->m_notnull) 
            $sql .= " NOT NULL";

        if ($this->m_autoincrement)
            $sql .= " AUTO_INCREMENT";

        if ($this->m_unique)
            $sql .= " UNIQUE";

        if ($this->primary)
            $sql .= " PRIMARY KEY";

        if (!is_null($this->m_set)){
            $sql .= "SET(";
            $prefix = "";
            foreach ($this->m_set as $s) {
                $sql .= $prefix . "'" . $s . "'";
                $prefix = ", ";
            }
            $sql .= ")";
        }

        return $sql;
    }

} // end class


?>


