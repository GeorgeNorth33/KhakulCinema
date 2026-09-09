<?php
// install.php - запустить один раз для установки БД

// Параметры подключения
$host = 'localhost';
$dbname = 'cinema_db';
$username = 'root';
$password = '';

try {
    // Подключение без выбора БД
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Читаем SQL файл
    $sql = file_get_contents('schema.sql');
    
    // Выполняем запросы
    $pdo->exec($sql);
    
    echo "База данных успешно установлена!<br>";
    echo "Созданы таблицы и добавлены тестовые данные.<br>";
    echo "<a href='index.php'>Перейти на сайт</a>";
    
} catch (PDOException $e) {
    die("Ошибка установки: " . $e->getMessage());
}
?>