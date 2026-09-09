<?php
require_once __DIR__ . '/../includes/User.php';
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

// Обработка добавления новости
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_news'])) {
    $stmt = $db->prepare("
        INSERT INTO news (title, content, tag, date_published) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $_POST['title'],
        $_POST['content'],
        $_POST['tag'],
        $_POST['date_published']
    ]);
    header('Location: news.php?success=added');
    exit;
}

// Обработка удаления новости
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM news WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header('Location: news.php?success=deleted');
    exit;
}

// Получаем список новостей
$stmt = $db->prepare("SELECT * FROM news ORDER BY date_published DESC");
$stmt->execute();
$news = $stmt->fetchAll();

$pageTitle = 'Управление новостями — Админ-панель';
require __DIR__ . '/includes/header.php';
?>

<main class="admin-page">
    <div class="container-fluid px-3 px-md-4">
        <div class="admin-header">
            <h1>Управление новостями</h1>
            <button class="btn btn-primary" onclick="document.getElementById('addNewsForm').style.display='block'">
                + Добавить новость
            </button>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php 
                $messages = [
                    'added' => 'Новость успешно добавлена',
                    'deleted' => 'Новость удалена'
                ];
                echo $messages[$_GET['success']] ?? 'Действие выполнено';
                ?>
            </div>
        <?php endif; ?>

        <!-- Форма добавления новости -->
        <div class="admin-form" id="addNewsForm" style="display: none;">
            <h3>Добавить новость</h3>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Заголовок</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="form-group full-width">
                        <label>Содержание</label>
                        <textarea name="content" class="form-control" rows="5" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Тег</label>
                        <input type="text" name="tag" class="form-control" placeholder="Новости, Акции, Премьеры">
                    </div>
                    <div class="form-group">
                        <label>Дата публикации</label>
                        <input type="date" name="date_published" class="form-control" required>
                    </div>
                </div>
                <button type="submit" name="add_news" class="btn btn-primary">Сохранить</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('addNewsForm').style.display='none'">Отмена</button>
            </form>
        </div>

        <!-- Список новостей -->
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Заголовок</th>
                        <th>Тег</th>
                        <th>Дата</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($news as $item): ?>
                        <tr>
                            <td><?= $item['id'] ?></td>
                            <td><strong><?= htmlspecialchars($item['title']) ?></strong></td>
                            <td><?= htmlspecialchars($item['tag'] ?? '-') ?></td>
                            <td><?= date('d.m.Y', strtotime($item['date_published'])) ?></td>
                            <td>
                                <a href="news-edit.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-secondary">Редактировать</a>
                                <a href="?delete=<?= $item['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Удалить новость?')">Удалить</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>