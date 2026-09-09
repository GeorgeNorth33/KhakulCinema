<?php
require_once __DIR__ . '/includes/User.php';
require_once __DIR__ . '/includes/BookingManager.php';
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user = new User($_SESSION['user_id']);
if (!$user->getData()) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$bookingManager = new BookingManager();
$tickets = $bookingManager->getUserTickets($_SESSION['user_id']);
$history = $user->getHistory();

$pageTitle = 'Личный кабинет — Кинотеатр';
require __DIR__ . '/includes/header.php';
?>

<main class="profile-page">
    <div class="container-fluid px-3 px-md-4">
        <div class="profile-grid">
            <!-- Боковая навигация -->
            <aside class="profile-sidebar">
                <div class="profile-avatar">
                    <div class="avatar-circle">
                        <?= mb_substr($user->getData()['full_name'], 0, 2) ?>
                    </div>
                    <h3><?= htmlspecialchars($user->getData()['full_name']) ?></h3>
                    <p><?= htmlspecialchars($user->getData()['email']) ?></p>
                </div>
                <nav class="profile-nav">
                    <a href="#" class="active" data-tab="bookings">Мои билеты</a>
                    <a href="#" data-tab="settings">Настройки</a>
                    <a href="#" data-tab="history">История</a>
                    <a href="logout.php" class="logout">Выйти</a>
                </nav>
            </aside>

            <!-- Основное содержимое -->
            <section class="profile-content">
                <!-- Вкладка: Билеты -->
                <div class="tab-panel active" id="tab-bookings">
                    <div class="tab-header">
                        <h2>Мои билеты</h2>
                        <span class="badge"><?= count($tickets) ?> билетов</span>
                    </div>

                    <div class="booking-list">
                        <?php if (empty($tickets)): ?>
                            <div class="empty-state">
                                <p>У вас пока нет билетов</p>
                                <a href="index.php#sessions" class="btn btn-primary">Купить билеты</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($tickets as $ticket): ?>
                                <div class="booking-card">
                                    <div class="booking-movie">
                                        <img src="<?= htmlspecialchars($ticket['poster_url'] ?? 'assets/posters/default.svg') ?>" 
                                             alt="<?= htmlspecialchars($ticket['movie_title']) ?>" 
                                             class="booking-poster">
                                        <div class="booking-info">
                                            <h4><?= htmlspecialchars($ticket['movie_title']) ?></h4>
                                            <p class="booking-meta">
                                                <?= htmlspecialchars($ticket['age_rating']) ?> · 
                                                <?= htmlspecialchars($ticket['genre']) ?> · 
                                                <?= floor($ticket['duration'] / 60) ?> ч <?= $ticket['duration'] % 60 ?> мин
                                            </p>
                                            <div class="booking-details">
                                                <span><?= date('d.m.Y', strtotime($ticket['session_time'])) ?></span>
                                                <span><?= date('H:i', strtotime($ticket['session_time'])) ?></span>
                                                <span><?= htmlspecialchars($ticket['hall_name']) ?></span>
                                                <span><?= htmlspecialchars($ticket['seats']) ?></span>
                                            </div>
                                            <div class="booking-code">
                                                Заказ: <strong><?= htmlspecialchars($ticket['booking_code']) ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="booking-actions">
                                        <span class="booking-status <?= $ticket['status'] ?>">
                                            <?php 
                                            $statusMap = [
                                                'confirmed' => 'Подтверждён',
                                                'pending' => 'Ожидает оплаты',
                                                'cancelled' => 'Отменён',
                                                'expired' => 'Истёк'
                                            ];
                                            echo $statusMap[$ticket['status']] ?? $ticket['status'];
                                            ?>
                                        </span>
                                        <?php if ($ticket['used']): ?>
                                            <span class="booking-status used">🎬 Использован</span>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-secondary" onclick="showTicketDetails(<?= $ticket['booking_id'] ?>)">
                                            QR-билет
                                        </button>
                                        <?php if ($ticket['status'] === 'confirmed' && !$ticket['used']): ?>
                                            <button class="btn btn-sm btn-danger">Отменить</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Вкладка: Настройки -->
                <div class="tab-panel" id="tab-settings">
                    <h2>Настройки профиля</h2>
                    <form class="settings-form" method="POST" action="profile-update.php">
                        <div class="form-group">
                            <label>Полное имя</label>
                            <input type="text" name="full_name" class="form-control" 
                                   value="<?= htmlspecialchars($user->getData()['full_name']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?= htmlspecialchars($user->getData()['email']) ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Телефон</label>
                            <input type="tel" name="phone" class="form-control" 
                                   value="<?= htmlspecialchars($user->getData()['phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Новый пароль</label>
                            <input type="password" name="password" class="form-control" 
                                   placeholder="Оставьте пустым, чтобы не менять">
                        </div>
                        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                    </form>
                </div>

                <!-- Вкладка: История -->
                <div class="tab-panel" id="tab-history">
                    <h2>История посещений</h2>
                    <div class="history-list">
                        <?php if (empty($history)): ?>
                            <div class="empty-state">
                                <p>История посещений пуста</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($history as $item): ?>
                                <div class="history-item">
                                    <span class="history-date"><?= date('d.m.Y', strtotime($item['session_time'])) ?></span>
                                    <span class="history-movie"><?= htmlspecialchars($item['movie_title']) ?></span>
                                    <span class="history-status <?= $item['used'] ? 'watched' : 'upcoming' ?>">
                                        <?= $item['used'] ? 'Просмотрен' : 'Предстоит' ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </div>
</main>

<!-- Модальное окно для QR-билета -->
<div class="modal" id="ticketModal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <div class="modal-body" id="ticketModalBody">
            <!-- Содержимое загружается через JS -->
        </div>
    </div>
</div>

<script>
function showTicketDetails(bookingId) {
    // В реальном проекте здесь был бы AJAX запрос
    // Пока показываем заглушку
    const modal = document.getElementById('ticketModal');
    const body = document.getElementById('ticketModalBody');
    
    body.innerHTML = `
        <div class="ticket-qr">
            <div class="qr-placeholder">
                <svg width="200" height="200" viewBox="0 0 200 200">
                    <rect width="200" height="200" fill="white"/>
                    ${generateQRPattern()}
                </svg>
            </div>
            <h3>Билет #${bookingId}</h3>
            <p>Покажите этот QR-код при входе в зал</p>
            <button class="btn btn-secondary" onclick="closeModal()">Закрыть</button>
        </div>
    `;
    
    modal.style.display = 'block';
}

function generateQRPattern() {
    // Генерация простого QR-подобного паттерна
    let pattern = '';
    for (let i = 0; i < 10; i++) {
        for (let j = 0; j < 10; j++) {
            if (Math.random() > 0.5) {
                pattern += `<rect x="${i*20}" y="${j*20}" width="18" height="18" fill="#000"/>`;
            }
        }
    }
    return pattern;
}

function closeModal() {
    document.getElementById('ticketModal').style.display = 'none';
}

// Закрытие модального окна при клике вне его
window.onclick = function(event) {
    const modal = document.getElementById('ticketModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>