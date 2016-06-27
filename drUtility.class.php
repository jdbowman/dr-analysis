<?php

class drUtility {
    
    public static function getStringList($keys) {
        $list = "";
        $prefix = "";
        foreach ($keys as $k) {
            $list .= $prefix . "'" . $k . "'";
            $prefix = ", ";
        }
        return $list;
    }

    public static function countDim($array) {
        if (is_array(reset($array)))
        {
            $return = self::countDim(reset($array)) + 1;
        } else {
            $return = 1;
        }
        return $return;
    }


    
} // end class

?>


