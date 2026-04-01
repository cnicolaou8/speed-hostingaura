<?php
/**
 * Database Connection Pool for HostingAura
 * 
 * This class implements a singleton pattern to reuse database connections
 * instead of creating new ones for every request, which significantly
 * improves performance under load.
 * 
 * Usage: Replace getDBConnection() calls with DatabasePool::getInstance()->getConnection()
 * Or use the helper function getDBConnection() which now uses pooling.
 */

class DatabasePool {
    private static $instance = null;
    private $connection = null;
    private $lastPingTime = 0;
    private $pingInterval = 30; // Ping every 30 seconds
    
    /**
     * Private constructor - enforces singleton pattern
     */
    private function __construct() {
        $this->connect();
    }
    
    /**
     * Create the actual database connection
     */
    private function connect() {
        $this->connection = new mysqli(
            DB_HOST, 
            DB_USER, 
            DB_PASS, 
            DB_NAME
        );
        
        if ($this->connection->connect_error) {
            error_log("DB Connection failed: " . $this->connection->connect_error);
            throw new Exception("Database connection failed");
        }
        
        // Optimize connection settings
        $this->connection->set_charset("utf8mb4");
        $this->connection->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
        
        // Set timezone to match PHP
        $this->connection->query("SET time_zone = '+00:00'");
        
        $this->lastPingTime = time();
    }
    
    /**
     * Get the singleton instance
     * 
     * @return DatabasePool
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get the database connection
     * 
     * @return mysqli
     */
    public function getConnection() {
        // Ping connection if it's been a while to ensure it's alive
        if (time() - $this->lastPingTime > $this->pingInterval) {
            if (!$this->connection->ping()) {
                // Connection died, reconnect
                error_log("DB Connection died, reconnecting...");
                $this->connect();
            }
            $this->lastPingTime = time();
        }
        
        return $this->connection;
    }
    
    /**
     * Check if connection is alive
     * 
     * @return bool
     */
    public function isAlive() {
        return $this->connection && $this->connection->ping();
    }
    
    /**
     * Get connection statistics (for monitoring)
     * 
     * @return array
     */
    public function getStats() {
        if (!$this->connection) {
            return ['status' => 'disconnected'];
        }
        
        $stats = [
            'status' => 'connected',
            'host' => DB_HOST,
            'database' => DB_NAME,
            'charset' => $this->connection->character_set_name(),
            'ping_ok' => $this->connection->ping(),
            'last_ping' => date('Y-m-d H:i:s', $this->lastPingTime)
        ];
        
        // Get MySQL status
        try {
            $result = $this->connection->query("SHOW STATUS LIKE 'Threads_connected'");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['threads_connected'] = $row['Value'];
            }
            
            $result = $this->connection->query("SHOW VARIABLES LIKE 'max_connections'");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['max_connections'] = $row['Value'];
            }
        } catch (Exception $e) {
            $stats['error'] = $e->getMessage();
        }
        
        return $stats;
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    private function __wakeup() {}
    
    /**
     * Clean up connection on destruction
     */
    public function __destruct() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}

/**
 * Helper function to maintain backward compatibility
 * This replaces the old getDBConnection() function
 * 
 * @return mysqli
 */
function getDBConnection() {
    return DatabasePool::getInstance()->getConnection();
}

/**
 * Helper function to get connection pool statistics
 * Useful for monitoring and debugging
 * 
 * @return array
 */
function getDBPoolStats() {
    return DatabasePool::getInstance()->getStats();
}
?>
