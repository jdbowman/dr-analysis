<?php

class drTable {
    
    // ----------------
    // Member variables
    // ----------------
    
    private $m_name;
    private $m_columns;
    private $m_rows;


    // ------------------------
    // Constructor / destructor
    // ------------------------
    
    function __construct() {

        // Init the default column attributes
        $this->m_name = NULL;
        $this->m_columns = [];
        $this->m_rows = [];
    }


    // ---------------------------
    // Public functions
    // ---------------------------
    public function setName($name) {
        $this->m_name = $name;
    }

    public function getName() {
        return $this->m_name;
    }

    public function addRow($row) {
        if (is_array($row)) {
            array_push($this->m_rows, $row);
            return TRUE;
        } 

        return FALSE;
    }

    public function &getRows() {
        return $this->m_rows;
    }

    public function deleteRows() {
        $this->m_rows = [];
    }

    public function setRows($rows) {
        if (is_array($rows)) {
            $this->m_rows = $rows;
            return TRUE;
        } 

        return FALSE;
    }

    public function addColumn($column) {
        if (is_a($column, "drTableColumn")) {
            array_push($this->m_columns, $column);
            return TRUE;
        }

        return FALSE;
    }

    public function &getColumn($column_name) {
        foreach ($this->m_columns as $c) {
            if ($c->getName() == $column_name) {
                return $c;
            }
        }
        return NULL;
    }

    public function getColumns() {
        return $this->m_columns;
    }

    public function display() {

        $str = "Table: " . $this->m_name . "\n\n";
        foreach ($this->m_columns as $c) {
            $str .= $c->getName() . ":  " . $c->getDescription() . "\n";
        }
        return $str . "\n";
    }

} // end class


?>


