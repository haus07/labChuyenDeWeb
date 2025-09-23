<?php
require_once 'configs/database.php';

abstract class BaseModel {
    protected static $_connection;

    public function __construct() {
        if (!isset(self::$_connection)) {
            self::$_connection = mysqli_connect(
                DB_HOST,
                DB_USER,
                DB_PASSWORD,
                DB_NAME,
                DB_PORT
            );

            if (mysqli_connect_errno()) {
                die("Connect failed: " . mysqli_connect_error());
            }
        }
    }

    protected function query($sql) {
        return self::$_connection->query($sql);
    }

    protected function select($sql) {
        $result = $this->query($sql);
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    protected function delete($sql) {
        return $this->query($sql);
    }

    
    protected function update($sql) {
        return $this->query($sql);
    }

    protected function insert($sql) {
        return $this->query($sql);
    }
}
