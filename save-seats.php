<?php
// save-seats.php - сохранение выбранных мест в сессии

session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

// Получаем данные из POST
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['session_id']) || !isset($data['seats'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Недостаточно данных']);
    exit;
}

// Сохраняем в сессию
$_SESSION['booking_session_id'] = (int)$data['session_id'];
$_SESSION['selected_seats'] = $data['seats'];

// Логируем для отладки
error_log('Сохранены места: ' . print_r($data['seats'], true));

echo json_encode(['success' => true]);
?>