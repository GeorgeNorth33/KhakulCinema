<?php
// session-select.php

require_once __DIR__ . '/includes/MovieManager.php';
session_start();

$movieManager = new MovieManager();

// Получаем ID фильма и дату из URL
$movieId = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$movie = $movieManager->getMovieById($movieId);

if (!$movie) {
    header('Location: index.php');
    exit;
}

// Получаем сеансы для фильма на выбранную дату
$sessions = $movieManager->getSessionsForMovieByDate($movieId, $date);

$pageTitle = htmlspecialchars($movie['title']) . ' — Выбор сеанса';
require __DIR__ . '/includes/header.php';
?>

<main class="session-page">
    <div class="container-fluid px-3 px-md-4">
        <div class="session-grid">
            <!-- Информация о фильме -->
            <div class="movie-details">
                <div class="movie-poster-large">
                    <img src="<?= htmlspecialchars($movie['poster_url'] ?? 'assets/posters/default.svg') ?>" 
                         alt="<?= htmlspecialchars($movie['title']) ?>" 
                         class="poster-main">
                    <span class="age-badge"><?= htmlspecialchars($movie['age_rating']) ?></span>
                </div>
                <div class="movie-detail-info">
                    <h1><?= htmlspecialchars($movie['title']) ?></h1>
                    <p class="movie-genre">
                        <?= htmlspecialchars($movie['genre']) ?> · 
                        <?= floor($movie['duration'] / 60) ?> ч <?= $movie['duration'] % 60 ?> мин
                    </p>
                    <p class="movie-description"><?= htmlspecialchars($movie['description'] ?? '') ?></p>
                </div>
            </div>

            <!-- Выбор сеанса -->
            <div class="session-selector">
                <h2>Выберите сеанс</h2>
                
                <div class="session-date-info">
                    <span><?= date('d.m.Y', strtotime($date)) ?></span>
                    <a href="index.php?date=<?= $date ?>" class="change-date">← Изменить дату</a>
                </div>
                
                <div class="session-list">
                    <?php if (empty($sessions)): ?>
                        <div class="no-sessions">
                            <p>На эту дату нет сеансов</p>
                            <a href="index.php?date=<?= $date ?>" class="btn btn-secondary">← Вернуться к выбору фильмов</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($sessions as $session): ?>
                            <div class="session-item">
                                <div class="session-time">
                                    <span class="time"><?= date('H:i', strtotime($session['session_time'])) ?></span>
                                    <span class="hall"><?= htmlspecialchars($session['hall_name']) ?></span>
                                    <?php if ($session['has_imax']): ?>
                                        <span class="badge-imax">IMAX</span>
                                    <?php endif; ?>
                                    <?php if ($session['has_3d']): ?>
                                        <span class="badge-3d">3D</span>
                                    <?php endif; ?>
                                </div>
                                <div class="session-price"><?= number_format($session['price'], 0, '', ' ') ?> ₽</div>
                                <a href="ticket-booking.php?session=<?= $session['id'] ?>" 
                                   class="btn btn-sm btn-primary">Выбрать</a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>