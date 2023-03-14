<?php

use SecureEnvPHP\SecureEnvPHP;

//nếu chưa đọc dc biến môi trường db_host thì đọc lại


class DbConnect
{

    private $server = null;
    private $dbname = 'ctindatabase';
    private $user = null;
    private $pass = '';
    private static $instance = null;
    private $conn = null;
    private function __construct()
    {
        if (getenv('DB_HOST') == false) (new SecureEnvPHP())->parse('.env.enc', '.env.key');
        $this->server = getenv('DB_HOST');
        $this->user = getenv('DB_USERNAME');
        // Nếu chưa tồn tại, tạo mới object và lưu vào Redis
        try {

            $dsn  = 'mysql:host=' . $this->server . ';dbname=' . $this->dbname . ';charset=utf8mb4';
            $this->conn = new PDO($dsn, $this->user, $this->pass);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
