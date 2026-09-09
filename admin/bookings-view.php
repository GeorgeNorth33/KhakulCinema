<?php
// admin/bookings-view.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/User.php';
session_start();

// Проверка авторизации и прав администратора
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user = new User($_SESSION['user_id']);
if (!$user->isAdmin()) {
    header('Location: ../index.php');
    exit;
}

$db = Database::getInstance();

$bookingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получаем данные бронирования
$stmt = $db->prepare("
    SELECT 
        b.*,
        u.full_name as user_name,
        u.email as user_email,
        u.phone as user_phone,
        m.title as movie_title,
        m.age_rating,
        m.duration,
        m.poster_url,
        s.session_time,
        h.name as hall_name
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN sessions s ON b.session_id = s.id
    JOIN movies m ON s.movie_id = m.id
    JOIN halls h ON s.hall_id = h.id
    WHERE b.id = ?
");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: bookings.php');
    exit;
}

// Получаем билеты
$stmt = $db->prepare("
    SELECT 
        t.*,
        se.row_number,
        se.seat_number,
        se.seat_type
    FROM tickets t
    JOIN seats se ON t.seat_id = se.id
    WHERE t.booking_id = ?
");
$stmt->execute([$bookingId]);
$tickets = $stmt->fetchAll();

// Функция для получения правильного пути к постеру
function getPosterPath($posterUrl) {
    if (empty($posterUrl)) {
        return '../assets/posters/default.svg';
    }
    
    // Если путь уже содержит ../assets/, оставляем как есть
    if (strpos($posterUrl, '../') === 0) {
        return $posterUrl;
    }
    
    // Если путь начинается с assets/, добавляем ../ для выхода из папки admin
    if (strpos($posterUrl, 'assets/') === 0) {
        return '../' . $posterUrl;
    }
    
    // Если путь начинается с /, убираем слеш и добавляем ../
    if (strpos($posterUrl, '/') === 0) {
        return '..' . $posterUrl;
    }
    
    // В остальных случаях добавляем ../assets/posters/
    return '../assets/posters/' . $posterUrl;
}

$pageTitle = 'Просмотр бронирования — Админ-панель';
require_once __DIR__ . '/includes/header.php';
?>

<main class="admin-page">
    <div class="container-fluid px-3 px-md-4">
        <div class="admin-header">
            <div>
                <h1>Просмотр бронирования</h1>
                <p>Код: <strong><?= htmlspecialchars($booking['booking_code']) ?></strong></p>
            </div>
            <a href="bookings.php" class="btn btn-secondary">← Назад</a>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <!-- Информация о бронировании -->
            <div class="admin-form">
                <h3>Информация о бронировании</h3>
                <table style="width: 100%;">
                    <tr>
                        <td style="color: #77727e; padding: 8px 0;">Код:</td>
                        <td><strong><?= htmlspecialchars($booking['booking_code']) ?></strong></td>
                    </tr>
                    <tr>
                        <td style="color: #77727e; padding: 8px 0;">Статус:</td>
                        <td>
                            <span class="status-badge status-<?= $booking['status'] ?>">
                                <?php 
                                $statusMap = [
                                    'confirmed' => 'Подтверждён',
                                    'pending' => 'Ожидает',
                                    'cancelled' => 'Отменён',
                                    'expired' => 'Истёк'
                                ];
                                echo $statusMap[$booking['status']] ?? $booking['status'];
                                ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td style="color: #77727e; padding: 8px 0;">Сумма:</td>
                        <td><strong><?= number_format($booking['total_amount'], 0, '', ' ') ?> ₽</strong></td>
                    </tr>
                    <tr>
                        <td style="color: #77727e; padding: 8px 0;">Дата создания:</td>
                        <td><?= date('d.m.Y H:i', strtotime($booking['created_at'])) ?></td>
                    </tr>
                    <tr>
                        <td style="color: #77727e; padding: 8px 0;">Истекает:</td>
                        <td><?= date('d.m.Y H:i', strtotime($booking['expires_at'])) ?></td>
                    </tr>
                </table>
            </div>

            <!-- Информация о пользователе -->
            <div class="admin-form">
                <h3>Информация о пользователе</h3>
                <table style="width: 100%;">
                    <tr>
                        <td style="color: #77727e; padding: 8px 0;">Имя:</td>
                        <td><strong><?= htmlspecialchars($booking['user_name']) ?></strong></td>
                    </tr>
                    <tr>
                        <td style="color: #77727e; padding: 8px 0;">Email:</td>
                        <td><?= htmlspecialchars($booking['user_email']) ?></td>
                    </tr>
                    <tr>
                        <td style="color: #77727e; padding: 8px 0;">Телефон:</td>
                        <td><?= htmlspecialchars($booking['user_phone'] ?? '-') ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Информация о фильме и сеансе -->
        <div class="admin-form" style="margin-top: 20px;">
            <h3>🎬 Информация о фильме и сеансе</h3>
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <div>
                    <?php 
                    $posterPath = getPosterPath($booking['poster_url'] ?? '');
                    ?>
                    <img src="<?= htmlspecialchars($posterPath) ?>" 
                         alt="<?= htmlspecialchars($booking['movie_title']) ?>" 
                         style="height: 150px; width: 100px; object-fit: cover; border-radius: 4px; background: #201a36;"
                         onerror="this.src='../assets/posters/default.svg'">
                </div>
                <div style="flex: 1;">
                    <table style="width: 100%;">
                        <tr>
                            <td style="color: #77727e; padding: 8px 0;">Фильм:</td>
                            <td><strong><?= htmlspecialchars($booking['movie_title']) ?></strong></td>
                        </tr>
                        <tr>
                            <td style="color: #77727e; padding: 8px 0;">Рейтинг:</td>
                            <td><?= htmlspecialchars($booking['age_rating'] ?? '0+') ?></td>
                        </tr>
                        <tr>
                            <td style="color: #77727e; padding: 8px 0;">Длительность:</td>
                            <td><?= floor($booking['duration'] / 60) ?> ч <?= $booking['duration'] % 60 ?> мин</td>
                        </tr>
                        <tr>
                            <td style="color: #77727e; padding: 8px 0;">Дата и время:</td>
                            <td><?= date('d.m.Y H:i', strtotime($booking['session_time'])) ?></td>
                        </tr>
                        <tr>
                            <td style="color: #77727e; padding: 8px 0;">Зал:</td>
                            <td><?= htmlspecialchars($booking['hall_name']) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Билеты -->
        <div class="admin-form" style="margin-top: 20px;">
            <h3>Билеты</h3>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ряд</th>
                            <th>Место</th>
                            <th>Тип</th>
                            <th>Цена</th>
                            <th>QR-код</th>
                            <th>Использован</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tickets)): ?>
                            <tr>
                                <td colspan="7" style="text-align:center; color:#77727e; padding:30px;">
                                    Нет билетов
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tickets as $ticket): ?>
                                <tr>
                                    <td><?= $ticket['id'] ?></td>
                                    <td><?= $ticket['row_number'] ?></td>
                                    <td><?= $ticket['seat_number'] ?></td>
                                    <td>
                                        <span class="status-badge <?= $ticket['seat_type'] == 'vip' ? 'status-admin' : 'status-user' ?>">
                                            <?= $ticket['seat_type'] ?>
                                        </span>
                                    </td>
                                    <td><?= number_format($ticket['price'], 0, '', ' ') ?> ₽</td>
                                    <td>
                                        <?php if ($ticket['qr_code']): ?>
                                            <code style="font-size: 10px; background: #1a171f; padding: 2px 6px; border-radius: 3px;">
                                                <?= htmlspecialchars($ticket['qr_code']) ?>
                                            </code>
                                        <?php else: ?>
                                            <span style="color: #77727e;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $ticket['used'] ? 'status-active' : 'status-inactive' ?>">
                                            <?= $ticket['used'] ? 'Да' : 'Нет' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="margin-top: 20px; display: flex; gap: 10px;">
            <a href="bookings.php" class="btn btn-secondary">← Назад к списку</a>
            <?php if ($booking['status'] != 'confirmed'): ?>
                <form method="POST" action="bookings.php" style="display: inline;">
                    <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                    <input type="hidden" name="status" value="confirmed">
                    <input type="hidden" name="update_status" value="1">
                    <button type="submit" class="btn btn-primary">Подтвердить</button>
                </form>
            <?php endif; ?>
            <?php if ($booking['status'] != 'cancelled'): ?>
                <form method="POST" action="bookings.php" style="display: inline;">
                    <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                    <input type="hidden" name="status" value="cancelled">
                    <input type="hidden" name="update_status" value="1">
                    <button type="submit" class="btn btn-danger">Отменить</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>