<?php 
// By Flowlance
class mssql {
    var $shard;
    var $account;
    var $params;
    
    function __construct() {
        // Database username
        $id = "username"; 
        // Database password
        $pw = "password";
        // Shard and Account database names
        $con_shard     = array("UID"=>$id, "PWD"=>$pw, "Database"=>"SRO_VT_SHARD_INIT"); 
        $con_account   = array("UID"=>$id, "PWD"=>$pw, "Database"=>"SRO_VT_ACCOUNT");
        // Shard And Account hostname / Connection
        $this->shard   = sqlsrv_connect("localhost", $con_shard) or die( print_r( sqlsrv_errors(), true));
        $this->account = sqlsrv_connect("localhost", $con_account) or die( print_r( sqlsrv_errors(), true));
        
        $this->params  = array("Scrollable" => SQLSRV_CURSOR_KEYSET);
    }
    
    function __destruct() {
        sqlsrv_close($this->shard);    
        sqlsrv_close($this->account);
    }
    
    public function db($db) {
        switch($db) {
            case 'SHARD':
                return $this->shard;
            break;
            
            case 'ACCOUNT':
                return $this->account;
            break;    
        }
    }
        
    public function query($db, $query, $values=array()) {
        $db = $this->db($db);
        $sql = sqlsrv_query($db, $query, $values, $this->params);
        
        if($query === false )
            return "Query error";
        else
            return $sql;
    }
    
    public function fetch($query) {
        return sqlsrv_fetch_array($query);
    }
    
    public function get_num($db, $query) {
        $db = $this->db($db);
        $sql = sqlsrv_query($db, $query, array(), $this->params);
        
        if($query === false )
            return var_dump(sqlsrv_errors());
        else
            return sqlsrv_num_rows($sql);
    }
    
    public function num($query) {
        return sqlsrv_num_rows($query);
    }
    
    public function last($query) {
        sqlsrv_next_result($query);
        sqlsrv_fetch($query);
        return sqlsrv_get_field($query, 0);
    }
    
    public function safe($data) {
        if ( !isset($data) or empty($data) ) return '';
        if ( is_numeric($data) ) return $data;
        $non_displayables = array(
            '/%0[0-8bcef]/',
            '/%1[0-9a-f]/',
            '/[\x00-\x08]/',
            '/\x0b/',
            '/\x0c/',
            '/[\x0e-\x1f]/'
        );
        foreach ( $non_displayables as $regex )
        $data = preg_replace( $regex, '', $data );
        $data = str_replace("'", "''", $data );
        return $data;
    }
}

$mssql = new mssql();
?>
