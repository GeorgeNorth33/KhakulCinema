<?php
require_once __DIR__ . '/includes/MovieManager.php';
session_start();

$movieManager = new MovieManager();

// Получаем выбранную дату (по умолчанию сегодня)
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Проверяем, что дата корректна
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = date('Y-m-d');
}

// Получаем фильмы на выбранную дату
$movies = $movieManager->getMoviesByDate($selectedDate);
$news = $movieManager->getNews();

// Если на выбранную дату нет фильмов, показываем ближайшие
if (empty($movies)) {
    $movies = $movieManager->getActiveMovies();
}

// Получаем даты с сеансами для календаря
$availableDates = $movieManager->getAvailableDates();

// Подготовка данных для календаря
$dayNames = [
    'Mon' => 'Пн', 'Tue' => 'Вт', 'Wed' => 'Ср', 'Thu' => 'Чт',
    'Fri' => 'Пт', 'Sat' => 'Сб', 'Sun' => 'Вс'
];
$monthNames = [
    'Jan' => 'Янв', 'Feb' => 'Фев', 'Mar' => 'Мар', 'Apr' => 'Апр',
    'May' => 'Май', 'Jun' => 'Июн', 'Jul' => 'Июл', 'Aug' => 'Авг',
    'Sep' => 'Сен', 'Oct' => 'Окт', 'Nov' => 'Ноя', 'Dec' => 'Дек'
];

// Текущая неделя для отображения
$weekOffset = isset($_GET['week']) ? (int)$_GET['week'] : 0;
$startDate = new DateTime();
$startDate->modify('-' . (date('N') - 1) . ' days'); // Начало недели с понедельника
$startDate->modify($weekOffset . ' weeks');

$pageTitle = 'Кинотеатр — Главная';
require __DIR__ . '/includes/header.php';
?>

<main>


    <section class="sessions-section" id="sessions">
        <div class="container-fluid px-3 px-md-4">
            <div class="section-heading">
                <h2>Сеансы</h2>
                <div class="section-meta">
                    <button class="filter-button <?= $selectedDate == date('Y-m-d') ? 'active' : '' ?>" type="button" onclick="window.location.href='?date=<?= date('Y-m-d') ?>&week=0'">Сегодня</button>
                    <button class="filter-button <?= $selectedDate == date('Y-m-d', strtotime('+1 day')) ? 'active' : '' ?>" type="button" onclick="window.location.href='?date=<?= date('Y-m-d', strtotime('+1 day')) ?>&week=0'">Завтра</button>
                    <button class="filter-button <?= $selectedDate == date('Y-m-d', strtotime('+2 days')) ? 'active' : '' ?>" type="button" onclick="window.location.href='?date=<?= date('Y-m-d', strtotime('+2 days')) ?>&week=0'">Послезавтра</button>
                </div>
            </div>

            <!-- Календарь выбора даты -->
            <div class="date-selector">
                <div class="date-nav">
                    <a href="?week=<?= $weekOffset - 1 ?>&date=<?= $selectedDate ?>" class="date-nav-btn" title="Предыдущая неделя">‹</a>
                    <div class="date-list">
                        <?php for ($i = 0; $i < 7; $i++):
                            $date = clone $startDate;
                            $date->modify("+$i days");
                            $dateStr = $date->format('Y-m-d');
                            $dayName = $dayNames[$date->format('D')] ?? $date->format('D');
                            $dayNum = $date->format('d');
                            $month = $monthNames[$date->format('M')] ?? $date->format('M');
                            $isToday = $dateStr == date('Y-m-d');
                            $isSelected = $dateStr == $selectedDate;
                            $hasSessions = in_array($dateStr, $availableDates);
                        ?>
                            <a href="?date=<?= $dateStr ?>&week=<?= $weekOffset ?>" 
                               class="date-item <?= $isSelected ? 'active' : '' ?> <?= $hasSessions ? 'has-sessions' : '' ?> <?= $isToday ? 'today' : '' ?>"
                               data-date="<?= $dateStr ?>">
                                <span class="day-name"><?= $dayName ?></span>
                                <span class="day-number"><?= $dayNum ?></span>
                                <span class="day-month"><?= $month ?></span>
                                <?php if ($isToday): ?>
                                    <span class="today-badge">Сегодня</span>
                                <?php endif; ?>
                                <?php if ($hasSessions): ?>
                                    <span class="session-dot"></span>
                                <?php endif; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                    <a href="?week=<?= $weekOffset + 1 ?>&date=<?= $selectedDate ?>" class="date-nav-btn" title="Следующая неделя">›</a>
                </div>
            </div>

            <!-- Заголовок с выбранной датой -->
            <div class="selected-date-info">
                <?php
                $dateObj = new DateTime($selectedDate);
                $formattedDate = $dateObj->format('d');
                $monthName = $monthNames[$dateObj->format('M')] ?? $dateObj->format('M');
                $dayName = $dayNames[$dateObj->format('D')] ?? $dateObj->format('D');
                $year = $dateObj->format('Y');
                ?>
                <span><?= $dayName ?>, <?= $formattedDate ?> <?= $monthName ?> <?= $year ?></span>
                <?php if (empty($movies)): ?>
                    <span class="no-movies-msg">— Нет фильмов на эту дату</span>
                <?php else: ?>
                    <span class="movies-count">— <?= count($movies) ?> фильмов</span>
                <?php endif; ?>
            </div>

            <div class="carousel-wrap">
                <button class="carousel-arrow carousel-prev" type="button" aria-label="Предыдущие фильмы">‹</button>

                <div class="movie-viewport" id="movieViewport">
                    <div class="movie-track" id="movieTrack">
                        <?php if (empty($movies)): ?>
                            <div class="empty-movies">
                                <p>На выбранную дату нет сеансов</p>
                                <p style="color: #77727e; font-size: 14px; margin-top: 8px;">Выберите другую дату или посмотрите <a href="?date=<?= date('Y-m-d') ?>&week=0" style="color: #6247c1;">сегодняшние сеансы</a></p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($movies as $movie): ?>
                                <article class="movie-card">
                                    <div class="poster-wrap">
                                        <img src="<?= htmlspecialchars($movie['poster_url'] ?? 'assets/posters/default.svg') ?>"
                                             alt="<?= htmlspecialchars($movie['title'] ?? 'Фильм') ?>"
                                             class="movie-poster">
                                        <span class="age-badge"><?= htmlspecialchars($movie['age_rating'] ?? '0+') ?></span>
                                        <div class="poster-overlay">
                                            <a href="session-select.php?id=<?= $movie['id'] ?>&date=<?= $selectedDate ?>" class="quick-book">Выбрать сеанс</a>
                                        </div>
                                    </div>
                                    <div class="movie-info">
                                        <h3><?= htmlspecialchars($movie['title'] ?? 'Без названия') ?></h3>
                                        <p><?= htmlspecialchars($movie['genre'] ?? '') ?> · <?= floor(($movie['duration'] ?? 0) / 60) ?> ч <?= ($movie['duration'] ?? 0) % 60 ?> мин</p>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <button class="carousel-arrow carousel-next" type="button" aria-label="Следующие фильмы">›</button>
            </div>

            <div class="carousel-progress" aria-hidden="true">
                <span class="progress-bar-fill" id="movieProgress"></span>
            </div>
        </div>
    </section>

    <section class="news-section" id="news">
        <div class="container-fluid px-3 px-md-4">
            <div class="section-heading news-heading">
                <h2>Новости</h2>
                <a href="#" class="all-news">Все новости <span>→</span></a>
            </div>

            <div class="row g-3 g-lg-4">
                <?php foreach ($news as $item): ?>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <article class="news-card">
                            <div class="news-image">
                                <span class="news-symbol">✦</span>
                                <span class="news-tag"><?= htmlspecialchars($item['tag'] ?? 'Новости') ?></span>
                            </div>
                            <div class="news-body">
                                <div class="news-date"><?= date('d.m.Y', strtotime($item['date_published'] ?? 'now')) ?></div>
                                <h3><?= htmlspecialchars($item['title'] ?? 'Новость') ?></h3>
                                <p><?= htmlspecialchars(mb_substr($item['content'] ?? '', 0, 100)) ?>...</p>
                                <a href="#" class="read-more">Подробнее <span>→</span></a>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Переключение дат через кнопки "Сегодня", "Завтра", "Послезавтра"
    document.querySelectorAll('.filter-button').forEach(button => {
        button.addEventListener('click', function() {
            const date = this.dataset.date;
            if (date) {
                window.location.href = '?date=' + date + '&week=0';
            }
        });
    });
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>