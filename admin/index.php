<?php
require_once __DIR__ . '/includes/User.php';
require_once __DIR__ . '/includes/MovieManager.php';
require_once __DIR__ . '/includes/BookingManager.php';
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

$movieManager = new MovieManager();
$bookingManager = new BookingManager();

// Получаем статистику
$db = Database::getInstance();

// Количество фильмов
$stmt = $db->prepare("SELECT COUNT(*) as count FROM movies WHERE is_active = 1");
$stmt->execute();
$moviesCount = $stmt->fetch()['count'];

// Количество пользователей
$stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
$stmt->execute();
$usersCount = $stmt->fetch()['count'];

// Количество сеансов сегодня
$stmt = $db->prepare("SELECT COUNT(*) as count FROM sessions WHERE DATE(session_time) = CURDATE() AND is_active = 1");
$stmt->execute();
$sessionsToday = $stmt->fetch()['count'];

// Выручка сегодня
$stmt = $db->prepare("
    SELECT SUM(total_amount) as total 
    FROM bookings 
    WHERE DATE(created_at) = CURDATE() AND status = 'confirmed'
");
$stmt->execute();
$revenueToday = $stmt->fetch()['total'] ?? 0;

// Последние бронирования
$stmt = $db->prepare("
    SELECT 
        b.*,
        u.full_name as user_name,
        m.title as movie_title
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN sessions s ON b.session_id = s.id
    JOIN movies m ON s.movie_id = m.id
    ORDER BY b.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recentBookings = $stmt->fetchAll();

$pageTitle = 'Панель администратора — Кинотеатр';

// Подключаем header админ-панели
require_once __DIR__ . '/includes/header.php';
?>

<main class="admin-page">
    <div class="container-fluid px-3 px-md-4">
        <div class="admin-header">
            <div>
                <h1>Панель администратора</h1>
                <p>Управление кинотеатром</p>
            </div>
            <a href="../index.php" class="btn btn-secondary">← На сайт</a>
        </div>

        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"></div>
                <div class="stat-info">
                    <span class="stat-value"><?= $moviesCount ?></span>
                    <span class="stat-label">Активных фильмов</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"></div>
                <div class="stat-info">
                    <span class="stat-value"><?= $usersCount ?></span>
                    <span class="stat-label">Пользователей</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"></div>
                <div class="stat-info">
                    <span class="stat-value"><?= $sessionsToday ?></span>
                    <span class="stat-label">Сеансов сегодня</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"></div>
                <div class="stat-info">
                    <span class="stat-value"><?= number_format($revenueToday, 0, '', ' ') ?> ₽</span>
                    <span class="stat-label">Выручка сегодня</span>
                </div>
            </div>
        </div>

        <div class="admin-grid">
            <!-- Быстрые действия -->
            <section class="admin-section quick-actions">
                <h2>Быстрые действия</h2>
                <div class="actions-grid">
                    <a href="movies.php" class="action-card">
                        <span class="action-icon"></span>
                        <span class="action-label">Управление фильмами</span>
                    </a>
                    <a href="sessions.php" class="action-card">
                        <span class="action-icon"></span>
                        <span class="action-label">Управление сеансами</span>
                    </a>
                    <a href="users.php" class="action-card">
                        <span class="action-icon"></span>
                        <span class="action-label">Управление пользователями</span>
                    </a>
                    <a href="bookings.php" class="action-card">
                        <span class="action-icon"></span>
                        <span class="action-label">Бронирования</span>
                    </a>
                    <a href="news.php" class="action-card">
                        <span class="action-icon"></span>
                        <span class="action-label">Новости</span>
                    </a>
                    <a href="promo.php" class="action-card">
                        <span class="action-icon"></span>
                        <span class="action-label">Промокоды</span>
                    </a>
                </div>
            </section>

            <!-- Последние бронирования -->
            <section class="admin-section recent-bookings">
                <h2>Последние бронирования</h2>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Код</th>
                                <th>Пользователь</th>
                                <th>Фильм</th>
                                <th>Сумма</th>
                                <th>Статус</th>
                                <th>Дата</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentBookings)): ?>
                                <tr>
                                    <td colspan="6" style="text-align:center; color:#77727e; padding:30px;">
                                        Нет бронирований
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentBookings as $booking): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($booking['booking_code']) ?></strong></td>
                                        <td><?= htmlspecialchars($booking['user_name']) ?></td>
                                        <td><?= htmlspecialchars($booking['movie_title']) ?></td>
                                        <td><?= number_format($booking['total_amount'], 0, '', ' ') ?> ₽</td>
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
                                        <td><?= date('d.m.Y H:i', strtotime($booking['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>