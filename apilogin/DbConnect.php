<?php

/**
 * Database Connection
 */
class DbConnect
{
    private $server = 'localhost';
    private $dbname = 'ctindatabase';
    private $user = 'root';
    private $pass = '';
    private static $instance = null;
    private $conn = null;
    private $pdo = null;

    private function __construct()
    {

        session_start();

        // Nếu chưa tồn tại, tạo mới object và lưu vào Redis
        try {

            $dsn  = 'mysql:host=' . $this->server . ';dbname=' . $this->dbname . ';charset=utf8mb4';
            $this->conn = new PDO($dsn, $this->user, $this->pass);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            /*
            if (!isset($_SESSION['pdo45'])) {
                echo "123";
                $pdo = new PDO($dsn, $this->user, $this->pass);

                $_SESSION['pdo5'] = $pdo;
            } else {
                echo "124";
                $pdo = $_SESSION['pdo4'];
            }
          */

            // $this->conn = new PDO('mysql:host=' . $this->server . ';dbname=' . $this->dbname, $this->user, $this->pass);
            // $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Lưu kết nối PDO vào phiên

        } catch (\Exception $e) {
            echo "Database Error: " . $e->getMessage();
        }
    }


    public static function getInstance()
    {
        if (self::$instance === null) {

            self::$instance = new DbConnect();
        }
        return self::$instance;
    }

    public function getConnection()
    {

        return $this->conn;
    }
}

$db = DbConnect::getInstance();
$conn = $db->getConnection();
