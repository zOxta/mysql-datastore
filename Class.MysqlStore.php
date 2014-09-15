<?php
 
/*
 * PHP class to allow interaction with a MySQL table as a simple datastore (PHP APC in particular)
 *
 * Author: zOxta
 * Version: 0.1
 * Date: 10.02.2012
 * Requirements: PHP5.2+, MySQL5,
 */
 
class MysqlStore {
 
        private $db;
 
        public function __construct() {
                $this->db = new Database(DB__HOST, DB__USER__READ, DB__PASS__READ, DB__NAME);
                $this->db->connect();
        }
 
        /**
         * Increase a stored number
         * @param type $key
         * @return int|boolean
         */
        public function inc($key)
        {
                $key = mysql_real_escape_string($key);
 
                $current_value = $this->fetch($key);
 
                if( $current_value == '' )
                {
                        if( $this->store($key, '1') ) { return 1; }
                }
                else if( is_int($current_value) )
                {
                        $sql = "UPDATE `cache` SET `cache_value`=`cache_value`+1 WHERE `cache_key` = '$key'";
                        $result = $this->db->query( $sql, 1 );
 
                        if( (int)$result > 0 ) { return ++$current_value; }
                }
 
                return false;
        }
 
       
        /**
         * Decrease a stored number
         * @param type $key
         * @return boolean
         */
        public function dec($key)
        {
                $key = mysql_real_escape_string($key);
 
                $current_value = $this->fetch($key);
 
                if( $current_value == '' )
                {
                        if( $this->store($key, '-1') ) { return -1; }
                }
                else if( is_int($current_value) )
                {
                        $sql = "UPDATE `cache` SET `cache_value`=`cache_value`-1 WHERE `cache_key` = '$key'";
                        $result = $this->db->query( $sql, 1 );
 
                        if( (int)$result > 0 ) { return --$current_value; }
                }
 
                return false;
        }
         
        /**
         * Store a variable in the datastore
         * @param type $key
         * @param type $value
         * @param type $ttl
         * @return boolean
         */
        public function store($key, $value, $ttl = 0)
        {
                if( is_array($value) ) { $value = json_encode($value); }
                $key = mysql_real_escape_string($key);
                $value = mysql_real_escape_string($value);
                $ttl = $ttl > 0 ? time() + $ttl : 0;
               
                $sql = "INSERT INTO `cache` (`cache_key`,`cache_value`,`cache_ttl`) VALUES ('{$key}','{$value}',{$ttl}) ON DUPLICATE KEY UPDATE `cache_value`=VALUES(`cache_value`), `cache_ttl`=VALUES(`cache_ttl`)";
                $result = $this->db->query( $sql, 1 );
               
                return ( (int)$result > 0 )  ? true : false;
        }
       
        /**
         * Fetch a stored variable from the datastore
         * @param type $key
         * @return boolean
         */
        public function fetch($key)
        {
                $key = mysql_real_escape_string($key);
                $time = time();
 
                $sql = "SELECT `cache_value` FROM `cache` WHERE `cache_key` = '$key' AND (`cache_ttl` = '0' OR `cache_ttl` >= '{$time}')";
                $result = $this->db->query_first( $sql );              
 
                if( @array_key_exists('cache_value', $result) )
                {
                        if( $this->is_json( $result['cache_value'] ) )
                        {
                                $result['cache_value'] = json_decode( $result['cache_value'], TRUE);
                        }
 
                        return $result['cache_value'];
                }
                else
                {
                        return false;
                }
        }
       
        /**
         * Check if a key exists
         * @param type $key
         * @return boolean
         */
        public function exists($key)
        {
                $key = mysql_real_escape_string($key);
               
                $sql = "SELECT `id` FROM `cache` WHERE `cache_key` = '$key'";
                $result = $this->db->query_first( $sql );              
 
                if( (int) @$result['id'] > 0 ) { return true; }
                else { return false; }
        }      
       
        /**
         * Delete a variable
         * @param type $key
         * @return type
         */
        public function delete($key)
        {
                $key = mysql_real_escape_string($key);
 
                $sql = "DELETE FROM `cache` WHERE `cache_key` = '$key'";
                $result = $this->db->query( $sql, 1 );
 
                return ( (int)$result > 0 )  ? true : false;                           
        }
 
        /**
         * Check if a string is valid JSON
         * @param type $string
         * @return type
         */
        private function is_json( $string ) {
                json_decode($string);
                return (json_last_error() == JSON_ERROR_NONE);
        }
       
        public function __destruct() {
                $this->db->close();
        }
}
