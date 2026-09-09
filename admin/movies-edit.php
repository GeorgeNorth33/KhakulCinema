<?php
// admin/movies-edit.php

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

$movieId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получаем данные фильма
$stmt = $db->prepare("SELECT * FROM movies WHERE id = ?");
$stmt->execute([$movieId]);
$movie = $stmt->fetch();

if (!$movie) {
    header('Location: movies.php');
    exit;
}

// Обработка обновления фильма
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_movie'])) {
    $stmt = $db->prepare("
        UPDATE movies 
        SET title = ?, genre = ?, age_rating = ?, duration = ?, description = ?, poster_url = ?, release_date = ?, is_active = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $_POST['title'],
        $_POST['genre'],
        $_POST['age_rating'],
        $_POST['duration'],
        $_POST['description'],
        $_POST['poster_url'],
        $_POST['release_date'],
        isset($_POST['is_active']) ? 1 : 0,
        $movieId
    ]);
    header('Location: movies.php?success=updated');
    exit;
}

$pageTitle = 'Редактирование фильма — Админ-панель';
require_once __DIR__ . '/includes/header.php';
?>

<main class="admin-page">
    <div class="container-fluid px-3 px-md-4">
        <div class="admin-header">
            <div>
                <h1>✏️ Редактирование фильма</h1>
                <p>Редактирование: <?= htmlspecialchars($movie['title']) ?></p>
            </div>
            <a href="movies.php" class="btn btn-secondary">← Назад</a>
        </div>

        <div class="admin-form">
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Название фильма *</label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($movie['title']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Жанр *</label>
                        <input type="text" name="genre" class="form-control" value="<?= htmlspecialchars($movie['genre']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Возрастной рейтинг</label>
                        <input type="text" name="age_rating" class="form-control" value="<?= htmlspecialchars($movie['age_rating'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Длительность (мин) *</label>
                        <input type="number" name="duration" class="form-control" value="<?= $movie['duration'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Дата выхода</label>
                        <input type="date" name="release_date" class="form-control" value="<?= $movie['release_date'] ?>">
                    </div>
                    <div class="form-group full-width">
                        <label>URL постера</label>
                        <input type="text" name="poster_url" class="form-control" value="<?= htmlspecialchars($movie['poster_url'] ?? '') ?>">
                        <small style="color: #77727e;">Текущий постер: <img src="<?= htmlspecialchars($movie['poster_url'] ?? 'assets/posters/default.svg') ?>" style="height: 40px; vertical-align: middle;"></small>
                    </div>
                    <div class="form-group full-width">
                        <label>Описание</label>
                        <textarea name="description" class="form-control" rows="5"><?= htmlspecialchars($movie['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group full-width">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_active" <?= $movie['is_active'] ? 'checked' : '' ?>>
                            <span>Фильм активен</span>
                        </label>
                    </div>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button type="submit" name="update_movie" class="btn btn-primary">💾 Сохранить изменения</button>
                    <a href="movies.php" class="btn btn-secondary">❌ Отмена</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>