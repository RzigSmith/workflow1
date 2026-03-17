<?php

class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        try {
            $dbPath = __DIR__ . '/../database/workflow.sqlite';
            $this->conn = new PDO("sqlite:" . $dbPath);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec('PRAGMA foreign_keys = ON;');
            $this->conn->exec('PRAGMA journal_mode = WAL;');
        } catch (PDOException $e) {
            throw new RuntimeException('Impossible de se connecter à la base de données. ' . $e->getMessage(), 0, $e);
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConn() {
        return $this->conn;
    }
}