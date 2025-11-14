<?php
/**
 * Victoria AI - Database Configuration
 * MySQL Connection for VPS
 */

// Database credentials
define('DB_HOST', 'localhost'); // Hoặc IP của VPS nếu remote
define('DB_NAME', 'victoria_ai');
define('DB_USER', 'root');
define('DB_PASS', '123456');
define('DB_CHARSET', 'utf8mb4');

// PDO Options for security and performance
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
]);

/**
 * Get database connection
 * @return PDO Database connection object
 * @throws PDOException if connection fails
 */
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);
            
            // Set timezone to Vietnam
            $pdo->exec("SET time_zone = '+07:00'");
            
        } catch (PDOException $e) {
            // Log error (in production, use proper logging)
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    return $pdo;
}

/**
 * Test database connection
 * @return bool True if connection successful
 */
function testDBConnection() {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query("SELECT 1");
        return $stmt !== false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Execute a prepared statement with parameters
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind
 * @return PDOStatement
 */
function executeQuery($sql, $params = []) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query execution error: " . $e->getMessage());
        throw new Exception("Database query failed");
    }
}

/**
 * Get last insert ID
 * @return string Last inserted ID
 */
function getLastInsertId() {
    return getDBConnection()->lastInsertId();
}

/**
 * Begin transaction
 */
function beginTransaction() {
    return getDBConnection()->beginTransaction();
}

/**
 * Commit transaction
 */
function commitTransaction() {
    return getDBConnection()->commit();
}

/**
 * Rollback transaction
 */
function rollbackTransaction() {
    return getDBConnection()->rollBack();
}
