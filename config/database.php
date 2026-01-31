<?php 
    class Database {
        private $host = "localhost";
        private $port = "3307";
        private $db_name = "sariph_pos";
        private $username = "root";
        private $password = "";
        public $conn;
    
        public function getConnection() {
            $this->conn = null;

            try {
                $this->conn = new PDO("mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name, $this->username, $this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                echo "Connection Error: " . $e->getMessage();
            }

            return $this->conn;
        }    
    }
?>