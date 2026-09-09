<?php
// check-date.php - проверка наличия сеансов на дату

require_once __DIR__ . '/config/database.php';

$date = isset($_GET['date']) ? $_GET['date'] : '';

if (empty($date)) {
    echo json_encode(['has_sessions' => false]);
    exit;
}

$db = Database::getInstance();

$stmt = $db->prepare("
    SELECT COUNT(*) as count 
    FROM sessions 
    WHERE DATE(session_time) = ? 
        AND is_active = 1
");
$stmt->execute([$date]);
$result = $stmt->fetch();

echo json_encode(['has_sessions' => $result['count'] > 0]);