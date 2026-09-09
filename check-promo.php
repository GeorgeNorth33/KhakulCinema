<?php
// check-promo.php - проверка промокода

require_once __DIR__ . '/config/database.php';
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

// Получаем данные
$data = json_decode(file_get_contents('php://input'), true);
$code = isset($data['code']) ? strtoupper(trim($data['code'])) : '';

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Введите промокод']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Проверяем промокод
    $stmt = $db->prepare("
        SELECT * FROM promo_codes 
        WHERE code = ? 
            AND is_active = 1 
            AND valid_from <= NOW() 
            AND valid_to >= NOW()
            AND (usage_limit IS NULL OR used_count < usage_limit)
    ");
    $stmt->execute([$code]);
    $promo = $stmt->fetch();
    
    if ($promo) {
        echo json_encode([
            'success' => true,
            'discount_percent' => $promo['discount_percent'],
            'message' => 'Промокод применён! Скидка ' . $promo['discount_percent'] . '%'
        ]);
    } else {
        // Проверяем, существует ли промокод, но неактивен или истёк
        $stmt = $db->prepare("SELECT * FROM promo_codes WHERE code = ?");
        $stmt->execute([$code]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            if (!$existing['is_active']) {
                echo json_encode(['success' => false, 'message' => 'Промокод неактивен']);
            } elseif (strtotime($existing['valid_from']) > time()) {
                echo json_encode(['success' => false, 'message' => 'Промокод ещё не активен']);
            } elseif (strtotime($existing['valid_to']) < time()) {
                echo json_encode(['success' => false, 'message' => 'Промокод истёк']);
            } elseif ($existing['usage_limit'] !== null && $existing['used_count'] >= $existing['usage_limit']) {
                echo json_encode(['success' => false, 'message' => 'Лимит использований промокода исчерпан']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Промокод недействителен']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Промокод не найден']);
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера: ' . $e->getMessage()]);
}