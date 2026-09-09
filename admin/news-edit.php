<?php
// admin/news-edit.php

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

$newsId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получаем данные новости
$stmt = $db->prepare("SELECT * FROM news WHERE id = ?");
$stmt->execute([$newsId]);
$newsItem = $stmt->fetch();

if (!$newsItem) {
    header('Location: news.php');
    exit;
}

// Обработка обновления новости
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_news'])) {
    $stmt = $db->prepare("
        UPDATE news 
        SET title = ?, content = ?, tag = ?, date_published = ?, is_published = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $_POST['title'],
        $_POST['content'],
        $_POST['tag'],
        $_POST['date_published'],
        isset($_POST['is_published']) ? 1 : 0,
        $newsId
    ]);
    header('Location: news.php?success=updated');
    exit;
}

$pageTitle = 'Редактирование новости — Админ-панель';
require_once __DIR__ . '/includes/header.php';
?>

<main class="admin-page">
    <div class="container-fluid px-3 px-md-4">
        <div class="admin-header">
            <div>
                <h1>✏️ Редактирование новости</h1>
                <p><?= htmlspecialchars($newsItem['title']) ?></p>
            </div>
            <a href="news.php" class="btn btn-secondary">← Назад</a>
        </div>

        <div class="admin-form">
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Заголовок *</label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($newsItem['title']) ?>" required>
                    </div>
                    <div class="form-group full-width">
                        <label>Содержание *</label>
                        <textarea name="content" class="form-control" rows="6" required><?= htmlspecialchars($newsItem['content']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Тег</label>
                        <input type="text" name="tag" class="form-control" value="<?= htmlspecialchars($newsItem['tag'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Дата публикации *</label>
                        <input type="date" name="date_published" class="form-control" value="<?= $newsItem['date_published'] ?>" required>
                    </div>
                    <div class="form-group full-width">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_published" <?= $newsItem['is_published'] ? 'checked' : '' ?>>
                            <span>Опубликовано</span>
                        </label>
                    </div>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button type="submit" name="update_news" class="btn btn-primary">💾 Сохранить изменения</button>
                    <a href="news.php" class="btn btn-secondary">❌ Отмена</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>