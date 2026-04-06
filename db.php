<?php
// C:\xampp\htdocs\mesashop-samp\db.php

class SQLiteDB {
    private $pdo;

    public function __construct($dbFile) {
        try {
            $this->pdo = new PDO("sqlite:" . $dbFile);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->initializeTables();
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    private function initializeTables() {
        // Table: user
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS user (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) DEFAULT '',
            username VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            phone_number VARCHAR(20) DEFAULT '',
            money INTEGER DEFAULT 0,
            rule VARCHAR(50) DEFAULT 'customer',
            is_verified INTEGER DEFAULT 0,
            lat VARCHAR(50) DEFAULT '',
            lng VARCHAR(50) DEFAULT '',
            address_details TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Table: product
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS product (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name_product VARCHAR(255) NOT NULL,
            details_product TEXT NOT NULL,
            picture_product VARCHAR(1000) NOT NULL,
            price_product INTEGER NOT NULL,
            Category VARCHAR(255) NOT NULL,
            ammo_product INTEGER DEFAULT 0,
            link_download VARCHAR(1000) DEFAULT '',
            video_url VARCHAR(1000) DEFAULT '',
            seller_id INTEGER DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Table: inventory
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS inventory (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(255) NOT NULL,
            product_id INTEGER NOT NULL,
            purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Table: slip_topup
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS slip_topup (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(255) NOT NULL,
            amount INTEGER NOT NULL,
            slip_image VARCHAR(1000) NOT NULL,
            status VARCHAR(50) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }

    public function query($sql) {
        try {
            if (stripos($sql, 'SELECT') === 0 || stripos($sql, 'SHOW') === 0 || stripos($sql, 'DESCRIBE') === 0 || stripos($sql, 'EXPLAIN') === 0) {
                $stmt = $this->pdo->query($sql);
                return new MySQLiCompatibleResult($stmt);
            } else {
                return $this->pdo->exec($sql);
            }
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage() . " - SQL: " . $sql);
            return false;
        }
    }

    public function real_escape_string($string) {
        $quoted = $this->pdo->quote($string);
        // PDO::quote adds surrounding quotes, mysqli::real_escape_string does not.
        // We strip the quotes for compatibility with the existing SQL construction logic.
        return substr($quoted, 1, -1);
    }

    public function set_charset($charset) {
        // SQLite is UTF-8 by default
        return true;
    }

    public function get_pdo() {
        return $this->pdo;
    }
    
    public $error = ""; // Placeholder for error handling
}

class MySQLiCompatibleResult {
    private $stmt;
    public $num_rows;
    private $currentIndex = 0;

    public function __construct($stmt) {
        $this->stmt = $stmt;
        if ($stmt) {
            // SQLite doesn't directly support rowCount for SELECT in a way that works everywhere.
            // We fetch all then reset if needed, or just return the count of results.
            $results = $stmt->fetchAll();
            $this->num_rows = count($results);
            $this->stmt = $results; // Store the array instead of the statement for easier fetch
            $this->currentIndex = 0;
        } else {
            $this->num_rows = 0;
        }
    }

    public function fetch_assoc() {
        if ($this->currentIndex < $this->num_rows) {
            return $this->stmt[$this->currentIndex++];
        }
        return null;
    }
}

// Global connection object
$dbFile = __DIR__ . DIRECTORY_SEPARATOR . "mesa_shop.db";
$conn = new SQLiteDB($dbFile);
?>
