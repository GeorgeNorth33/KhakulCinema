<?php
// config/database.php - ИСПРАВЛЕННАЯ ВЕРСИЯ

class Database {
    private static $instance = null;
    private $connection;
    private $queryCache = [];
    private $cacheEnabled = true;
    private $dbname = 'cinema_db'; // <-- ДОБАВЛЯЕМ ЭТУ СТРОКУ
    
    // Оптимизированное определение параметров (с кешированием конфигурации)
    private function getConfig() {
        // Проверяем кеш конфигурации
        $cacheFile = __DIR__ . '/.db_config.cache';
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 3600)) {
            $config = unserialize(file_get_contents($cacheFile));
            if ($config && $this->testConnection($config)) {
                return $config;
            }
        }
        
        $configs = [
            // OpenServer (стандартный)
            ['host' => '127.0.0.1', 'port' => 3306, 'user' => 'root', 'pass' => ''],
            ['host' => 'localhost', 'port' => 3306, 'user' => 'root', 'pass' => ''],
            // XAMPP
            ['host' => '127.0.0.1', 'port' => 3307, 'user' => 'root', 'pass' => ''],
            ['host' => 'localhost', 'port' => 3307, 'user' => 'root', 'pass' => ''],
            // WAMP
            ['host' => 'localhost', 'port' => 3306, 'user' => 'root', 'pass' => ''],
            // MAMP
            ['host' => 'localhost', 'port' => 8889, 'user' => 'root', 'pass' => 'root'],
            ['host' => '127.0.0.1', 'port' => 8889, 'user' => 'root', 'pass' => 'root'],
            // Linux
            ['host' => 'localhost', 'port' => 3306, 'user' => 'root', 'pass' => ''],
            // Если ничего не подошло - пробуем с сокетом
            ['host' => 'localhost', 'port' => 3306, 'user' => 'root', 'pass' => '', 'socket' => '/var/run/mysqld/mysqld.sock'],
        ];
        
        $foundConfig = null;
        foreach ($configs as $config) {
            if ($this->testConnection($config)) {
                $foundConfig = $config;
                break;
            }
        }
        
        if (!$foundConfig) {
            throw new Exception("Не удалось подключиться к MySQL. Убедитесь, что сервер запущен.");
        }
        
        // Сохраняем в кеш
        file_put_contents($cacheFile, serialize($foundConfig));
        
        return $foundConfig;
    }
    
    private function testConnection($config) {
        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};charset=utf8mb4";
            if (isset($config['socket'])) {
                $dsn = "mysql:unix_socket={$config['socket']};charset=utf8mb4";
            }
            
            $test = new PDO(
                $dsn,
                $config['user'],
                $config['pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 2 // Таймаут 2 секунды
                ]
            );
            
            // Проверяем наличие базы данных
            $test->exec("USE " . $this->dbname);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    private function __construct() {
        try {
            $config = $this->getConfig();
            
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname=" . $this->dbname . ";charset=utf8mb4";
            if (isset($config['socket'])) {
                $dsn = "mysql:unix_socket={$config['socket']};dbname=" . $this->dbname . ";charset=utf8mb4";
            }
            
            $this->connection = new PDO(
                $dsn,
                $config['user'],
                $config['pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                    PDO::ATTR_TIMEOUT => 10,
                    PDO::ATTR_PERSISTENT => false // Отключаем постоянные соединения
                ]
            );
        } catch (Exception $e) {
            die("
                <h2>Ошибка подключения к базе данных</h2>
                <p>" . htmlspecialchars($e->getMessage()) . "</p>
                <p><strong>Для решения проблемы:</strong></p>
                <ol>
                    <li>Запустите MySQL через OpenServer/XAMPP/WAMP/MAMP</li>
                    <li>Создайте базу данных 'cinema_db' в phpMyAdmin</li>
                    <li>Импортируйте файл cinema_db.sql</li>
                </ol>
                <p><a href='test_db.php'>Проверить подключение</a></p>
            ");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Возвращает объект PDO (оригинальное соединение)
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Подготавливает SQL запрос
     */
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    /**
     * Выполняет SQL запрос с кешированием
     */
    public function query($sql, $params = [], $cacheTime = 0) {
        // Если кеширование отключено или время кеширования 0 - выполняем сразу
        if (!$this->cacheEnabled || $cacheTime === 0) {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }
        
        $cacheKey = md5($sql . serialize($params));
        
        // Проверяем кеш
        if (isset($this->queryCache[$cacheKey])) {
            list($data, $expires) = $this->queryCache[$cacheKey];
            if ($expires > time()) {
                return $data;
            }
        }
        
        // Выполняем запрос
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll();
        
        // Сохраняем в кеш
        $this->queryCache[$cacheKey] = [$result, time() + $cacheTime];
        
        return $result;
    }
    
    /**
     * Выполняет SQL запрос и возвращает количество затронутых строк
     */
    public function exec($sql) {
        return $this->connection->exec($sql);
    }
    
    /**
     * Возвращает ID последней вставленной записи
     */
    public function lastInsertId($name = null) {
        return $this->connection->lastInsertId($name);
    }
    
    /**
     * Начинает транзакцию
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Подтверждает транзакцию
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Откатывает транзакцию
     */
    public function rollBack() {
        return $this->connection->rollBack();
    }
    
    /**
     * Возвращает код ошибки
     */
    public function errorCode() {
        return $this->connection->errorCode();
    }
    
    /**
     * Возвращает информацию об ошибке
     */
    public function errorInfo() {
        return $this->connection->errorInfo();
    }
    
    /**
     * Устанавливает атрибут PDO
     */
    public function setAttribute($attribute, $value) {
        return $this->connection->setAttribute($attribute, $value);
    }
    
    /**
     * Возвращает атрибут PDO
     */
    public function getAttribute($attribute) {
        return $this->connection->getAttribute($attribute);
    }
    
    /**
     * Экранирует строку для использования в SQL
     */
    public function quote($string) {
        return $this->connection->quote($string);
    }
    
    /**
     * Очистка кеша запросов
     */
    public function clearCache() {
        $this->queryCache = [];
    }
    
    /**
     * Включение/отключение кеширования
     */
    public function setCacheEnabled($enabled) {
        $this->cacheEnabled = $enabled;
    }
}

/**
 * Вспомогательная функция для быстрого доступа к БД
 */
function db() {
    return Database::getInstance()->getConnection();
}

/**
 * Функция для выполнения запросов с кешированием
 */
function dbQuery($sql, $params = [], $cacheTime = 60) {
    return Database::getInstance()->query($sql, $params, $cacheTime);
}