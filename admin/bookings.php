<?php
// admin/bookings.php - Управление бронированиями (полная версия)

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

// Обработка отмены бронирования
if (isset($_GET['cancel'])) {
    $stmt = $db->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND status IN ('confirmed', 'pending')");
    $stmt->execute([$_GET['cancel']]);
    header('Location: bookings.php?success=cancelled');
    exit;
}

// Обработка удаления бронирования
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header('Location: bookings.php?success=deleted');
    exit;
}

// Обработка восстановления отменённого бронирования
if (isset($_GET['restore'])) {
    $stmt = $db->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
    $stmt->execute([$_GET['restore']]);
    header('Location: bookings.php?success=restored');
    exit;
}

// Получаем фильтр по статусу
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$searchFilter = isset($_GET['search']) ? trim($_GET['search']) : '';

// Получаем список бронирований с деталями
$sql = "
    SELECT 
        b.*,
        u.full_name as user_name,
        u.email as user_email,
        m.title as movie_title,
        s.session_time,
        h.name as hall_name,
        GROUP_CONCAT(
            CONCAT('Ряд ', se.row_number, ' Место ', se.seat_number) 
            ORDER BY se.id 
            SEPARATOR ', '
        ) as seats,
        COUNT(t.id) as tickets_count
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN sessions s ON b.session_id = s.id
    JOIN movies m ON s.movie_id = m.id
    JOIN halls h ON s.hall_id = h.id
    LEFT JOIN tickets t ON b.id = t.booking_id
    LEFT JOIN seats se ON t.seat_id = se.id
    WHERE 1=1
";

$params = [];

if ($statusFilter !== 'all') {
    $sql .= " AND b.status = ?";
    $params[] = $statusFilter;
}

if (!empty($searchFilter)) {
    $sql .= " AND (b.booking_code LIKE ? OR u.full_name LIKE ? OR u.email LIKE ? OR m.title LIKE ?)";
    $searchParam = "%$searchFilter%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$sql .= " GROUP BY b.id ORDER BY b.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Получаем статистику
$stmt = $db->prepare("SELECT COUNT(*) as total FROM bookings");
$stmt->execute();
$totalBookings = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM bookings WHERE status = 'confirmed'");
$stmt->execute();
$confirmedBookings = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM bookings WHERE status = 'pending'");
$stmt->execute();
$pendingBookings = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM bookings WHERE status = 'cancelled'");
$stmt->execute();
$cancelledBookings = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM bookings WHERE status = 'expired'");
$stmt->execute();
$expiredBookings = $stmt->fetch()['total'];

$pageTitle = 'Управление бронированиями — Админ-панель';
require_once __DIR__ . '/includes/header.php';
?>

<main class="admin-page">
    <div class="container-fluid px-3 px-md-4">
        <div class="admin-header">
            <div>
                <h1>Управление бронированиями</h1>
                <p>Просмотр и управление всеми бронированиями</p>
            </div>
            <a href="index.php" class="btn btn-secondary">← Назад</a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php 
                $messages = [
                    'cancelled' => 'Бронирование отменено',
                    'deleted' => 'Бронирование удалено',
                    'restored' => 'Бронирование восстановлено'
                ];
                echo $messages[$_GET['success']] ?? 'Действие выполнено';
                ?>
            </div>
        <?php endif; ?>

        <!-- Статистика -->
        <div class="stats-grid" style="margin-bottom: 25px;">
            <div class="stat-card">
                <div class="stat-icon"></div>
                <div class="stat-info">
                    <span class="stat-value"><?= $totalBookings ?></span>
                    <span class="stat-label">Всего бронирований</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"></div>
                <div class="stat-info">
                    <span class="stat-value"><?= $confirmedBookings ?></span>
                    <span class="stat-label">Подтверждённых</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"></div>
                <div class="stat-info">
                    <span class="stat-value"><?= $pendingBookings ?></span>
                    <span class="stat-label">В ожидании</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"></div>
                <div class="stat-info">
                    <span class="stat-value"><?= $cancelledBookings ?></span>
                    <span class="stat-label">Отменённых</span>
                </div>
            </div>
        </div>

        <!-- Фильтры -->
        <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 20px; background: #121014; padding: 15px 20px; border-radius: 8px; border: 1px solid #24202a;">
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="?status=all" class="btn <?= $statusFilter == 'all' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 6px 16px; font-size: 12px;">
                    Все (<?= $totalBookings ?>)
                </a>
                <a href="?status=confirmed" class="btn <?= $statusFilter == 'confirmed' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 6px 16px; font-size: 12px;">
                    Подтверждён (<?= $confirmedBookings ?>)
                </a>
                <a href="?status=pending" class="btn <?= $statusFilter == 'pending' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 6px 16px; font-size: 12px;">
                    Ожидают (<?= $pendingBookings ?>)
                </a>
                <a href="?status=cancelled" class="btn <?= $statusFilter == 'cancelled' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 6px 16px; font-size: 12px;">
                    Отменён (<?= $cancelledBookings ?>)
                </a>
                <a href="?status=expired" class="btn <?= $statusFilter == 'expired' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 6px 16px; font-size: 12px;">
                    Истекли (<?= $expiredBookings ?>)
                </a>
            </div>
            <div style="flex: 1; min-width: 200px;">
                <form method="GET" style="display: flex; gap: 8px;">
                    <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                    <input type="text" name="search" class="form-control" placeholder="Поиск по коду, пользователю, фильму..." value="<?= htmlspecialchars($searchFilter) ?>" style="flex: 1;">
                    <button type="submit" class="btn btn-primary" style="padding: 6px 16px;">Найти</button>
                    <?php if (!empty($searchFilter)): ?>
                        <a href="?status=<?= htmlspecialchars($statusFilter) ?>" class="btn btn-secondary" style="padding: 6px 16px;">× Очистить</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Таблица бронирований -->
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Код</th>
                        <th>Пользователь</th>
                        <th>Фильм</th>
                        <th>Дата/Время</th>
                        <th>Зал</th>
                        <th>Места</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="10" style="text-align:center; color:#77727e; padding:40px;">
                                <?php if (!empty($searchFilter)): ?>
                                    По вашему запросу ничего не найдено
                                <?php else: ?>
                                    Нет бронирований
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?= $booking['id'] ?></td>
                                <td><strong><?= htmlspecialchars($booking['booking_code']) ?></strong></td>
                                <td>
                                    <?= htmlspecialchars($booking['user_name']) ?><br>
                                    <small style="color:#77727e;"><?= htmlspecialchars($booking['user_email']) ?></small>
                                </td>
                                <td><strong><?= htmlspecialchars($booking['movie_title']) ?></strong></td>
                                <td>
                                    <?= date('d.m.Y', strtotime($booking['session_time'])) ?><br>
                                    <small style="color:#77727e;"><?= date('H:i', strtotime($booking['session_time'])) ?></small>
                                </td>
                                <td><?= htmlspecialchars($booking['hall_name']) ?></td>
                                <td>
                                    <small><?= htmlspecialchars($booking['seats'] ?? '-') ?></small>
                                    <br><small style="color:#77727e;"><?= $booking['tickets_count'] ?? 0 ?> билетов</small>
                                </td>
                                <td><strong><?= number_format($booking['total_amount'], 0, '', ' ') ?> ₽</strong></td>
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
                                    <?php if ($booking['status'] == 'confirmed'): ?>
                                        <br><small style="color:#6fcb8a;">Автоматически</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                        <a href="bookings-view.php?id=<?= $booking['id'] ?>" class="btn btn-sm btn-secondary" title="Просмотр">👁️</a>
                                        
                                        <?php if ($booking['status'] == 'confirmed' || $booking['status'] == 'pending'): ?>
                                            <a href="?cancel=<?= $booking['id'] ?>" class="btn btn-sm btn-warning" onclick="return confirm('Отменить бронирование?')" title="Отменить">Отменить</a>
                                        <?php endif; ?>
                                        
                                        <?php if ($booking['status'] == 'cancelled'): ?>
                                            <a href="?restore=<?= $booking['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Восстановить бронирование?')" title="Восстановить">↩</a>
                                        <?php endif; ?>
                                        
                                        <a href="?delete=<?= $booking['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Удалить бронирование навсегда?')" title="Удалить">Удалить</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Информация о системе -->
        <div style="margin-top: 20px; padding: 15px 20px; background: #121014; border: 1px solid #24202a; border-radius: 8px; color: #77727e; font-size: 13px;">
            <p style="margin: 0;">
                <strong>Информация:</strong> Бронирования подтверждаются автоматически после оплаты. 
                Администратор может только <strong>отменить</strong> или <strong>удалить</strong> бронирование.
            </p>
        </div>
    </div>
</main>

<style>
/* Дополнительные стили для страницы бронирований */
.status-badge.status-confirmed {
    background: #1a3a2a;
    color: #6fcb8a;
}

.status-badge.status-pending {
    background: #3a3a1a;
    color: #cbcb6f;
}

.status-badge.status-cancelled {
    background: #2a1a1a;
    color: #cb6f6f;
}

.status-badge.status-expired {
    background: #2a2a2a;
    color: #77727e;
}

.admin-table td {
    vertical-align: middle;
}

/* Адаптив для фильтров */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    
    .stat-card {
        padding: 12px 15px;
    }
    
    .stat-value {
        font-size: 18px;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .admin-table {
        font-size: 11px;
    }
    
    .admin-table th,
    .admin-table td {
        padding: 6px 8px;
    }
    
    .admin-table .btn-sm {
        padding: 2px 6px;
        font-size: 10px;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>