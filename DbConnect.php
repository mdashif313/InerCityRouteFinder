<?php
/**
 * Description of DbConnect
 *
 * @author Ashif
 */
class DbConnect {
    
    private $host = "localhost";
    private $username = "root";
    private $dbname = "intercitybus";
    public $conn;
    
    public function dbConnection(){
        
        $this->conn = NULL;
        
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->dbname, $this->username);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
        } catch (Exception $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
