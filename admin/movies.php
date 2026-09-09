<?php
// admin/movies.php

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

// Обработка добавления фильма
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_movie'])) {
    $stmt = $db->prepare("
        INSERT INTO movies (title, genre, age_rating, duration, description, poster_url, release_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_POST['title'],
        $_POST['genre'],
        $_POST['age_rating'],
        $_POST['duration'],
        $_POST['description'],
        $_POST['poster_url'],
        $_POST['release_date']
    ]);
    header('Location: movies.php?success=added');
    exit;
}

// Обработка удаления фильма
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("UPDATE movies SET is_active = 0 WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header('Location: movies.php?success=deleted');
    exit;
}

// Обработка восстановления фильма
if (isset($_GET['restore'])) {
    $stmt = $db->prepare("UPDATE movies SET is_active = 1 WHERE id = ?");
    $stmt->execute([$_GET['restore']]);
    header('Location: movies.php?success=restored');
    exit;
}

// Получаем список фильмов
$stmt = $db->prepare("SELECT * FROM movies ORDER BY created_at DESC");
$stmt->execute();
$movies = $stmt->fetchAll();

$pageTitle = 'Управление фильмами — Админ-панель';
require_once __DIR__ . '/includes/header.php';
?>

<main class="admin-page">
    <div class="container-fluid px-3 px-md-4">
        <div class="admin-header">
            <div>
                <h1>🎬 Управление фильмами</h1>
                <p>Добавление, редактирование и удаление фильмов</p>
            </div>
            <div>
                <button class="btn btn-primary" onclick="document.getElementById('addMovieForm').style.display='block'">
                    + Добавить фильм
                </button>
                <a href="index.php" class="btn btn-secondary">← Назад</a>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php 
                $messages = [
                    'added' => '✅ Фильм успешно добавлен',
                    'deleted' => '🗑️ Фильм удалён',
                    'restored' => '↩️ Фильм восстановлен'
                ];
                echo $messages[$_GET['success']] ?? 'Действие выполнено';
                ?>
            </div>
        <?php endif; ?>

        <!-- Форма добавления фильма -->
        <div class="admin-form" id="addMovieForm" style="display: none;">
            <h3>📝 Добавить новый фильм</h3>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Название фильма *</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Жанр *</label>
                        <input type="text" name="genre" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Возрастной рейтинг</label>
                        <input type="text" name="age_rating" class="form-control" placeholder="16+">
                    </div>
                    <div class="form-group">
                        <label>Длительность (мин) *</label>
                        <input type="number" name="duration" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Дата выхода</label>
                        <input type="date" name="release_date" class="form-control">
                    </div>
                    <div class="form-group full-width">
                        <label>URL постера</label>
                        <input type="text" name="poster_url" class="form-control" placeholder="../assets/posters/movie-1.svg">
                        <small style="color: #77727e;">Путь к постеру относительно корня сайта</small>
                    </div>
                    <div class="form-group full-width">
                        <label>Описание</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button type="submit" name="add_movie" class="btn btn-primary">Сохранить</button>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('addMovieForm').style.display='none'">Отмена</button>
                </div>
            </form>
        </div>

        <!-- Список фильмов -->
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Постер</th>
                        <th>Название</th>
                        <th>Жанр</th>
                        <th>Рейтинг</th>
                        <th>Длительность</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movies)): ?>
                        <tr>
                            <td colspan="8" style="text-align:center; color:#77727e; padding:30px;">
                                Нет фильмов. Добавьте первый фильм!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($movies as $movie): ?>
                            <tr>
                                <td><?= $movie['id'] ?></td>
                                <td>
                                    <?php 
                                    // Определяем путь к постеру
                                    $posterPath = $movie['poster_url'] ?? 'assets/posters/default.svg';
                                    
                                    // Если путь начинается с assets/, добавляем ../ для выхода из папки admin
                                    if (strpos($posterPath, 'assets/') === 0) {
                                        $posterPath = '../' . $posterPath;
                                    }
                                    
                                    // Проверяем существование файла
                                    $fullPath = __DIR__ . '/../' . $movie['poster_url'] ?? 'assets/posters/default.svg';
                                    if (!file_exists($fullPath)) {
                                        $posterPath = '../assets/posters/default.svg';
                                    }
                                    ?>
                                    <img src="<?= htmlspecialchars($posterPath) ?>" 
                                         alt="<?= htmlspecialchars($movie['title']) ?>" 
                                         class="admin-poster"
                                         onerror="this.src='../assets/posters/default.svg'">
                                </td>
                                <td><strong><?= htmlspecialchars($movie['title']) ?></strong></td>
                                <td><?= htmlspecialchars($movie['genre']) ?></td>
                                <td><?= htmlspecialchars($movie['age_rating'] ?? '-') ?></td>
                                <td><?= $movie['duration'] ?> мин</td>
                                <td>
                                    <span class="status-badge <?= $movie['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                        <?= $movie['is_active'] ? 'Активен' : 'Неактивен' ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="movies-edit.php?id=<?= $movie['id'] ?>" class="btn btn-sm btn-secondary" title="Редактировать">Редактировать</a>
                                    <?php if ($movie['is_active']): ?>
                                        <a href="?delete=<?= $movie['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Удалить фильм?')" title="Удалить">Удалить</a>
                                    <?php else: ?>
                                        <a href="?restore=<?= $movie['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Восстановить фильм?')" title="Восстановить">↩</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>