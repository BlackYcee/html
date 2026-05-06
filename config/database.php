<?php
require_once __DIR__ . '/../config/config.php';

class Database {
    private static $instance = null;
    private $connection = null;
    private $connected = false;
    private $error = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        try {
            $host = Config::get('db_host');
            $port = Config::get('db_port');
            $dbname = Config::get('db_name');
            $user = Config::get('db_user');
            $password = Config::get('db_password');

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->connection = new PDO($dsn, $user, $password, $options);
            $this->connected = true;
        } catch (PDOException $e) {
            $this->connected = false;
            $this->error = $e->getMessage();
        }
    }

    public function getConnection() {
        return $this->connection;
    }

    public function isConnected() {
        return $this->connected;
    }

    public function getError() {
        return $this->error;
    }

    public function initSchema() {
        if (!$this->connected) {
            return false;
        }

        $sql = "CREATE TABLE IF NOT EXISTS images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            file_path VARCHAR(500) NOT NULL,
            s3_url VARCHAR(500) NULL,
            thumbnail_path VARCHAR(500) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";

        try {
            $this->connection->exec($sql);
            return true;
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
}