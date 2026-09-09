<?php
// admin/sessions-edit.php

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

$sessionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получаем данные сеанса
$stmt = $db->prepare("
    SELECT s.*, m.title as movie_title, h.name as hall_name 
    FROM sessions s
    JOIN movies m ON s.movie_id = m.id
    JOIN halls h ON s.hall_id = h.id
    WHERE s.id = ?
");
$stmt->execute([$sessionId]);
$session = $stmt->fetch();

if (!$session) {
    header('Location: sessions.php');
    exit;
}

// Получаем список фильмов и залов
$stmt = $db->prepare("SELECT id, title FROM movies WHERE is_active = 1");
$stmt->execute();
$movies = $stmt->fetchAll();

$stmt = $db->prepare("SELECT id, name FROM halls WHERE is_active = 1");
$stmt->execute();
$halls = $stmt->fetchAll();

// Обработка обновления сеанса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_session'])) {
    $stmt = $db->prepare("
        UPDATE sessions 
        SET movie_id = ?, hall_id = ?, session_time = ?, price = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $_POST['movie_id'],
        $_POST['hall_id'],
        $_POST['session_time'],
        $_POST['price'],
        $sessionId
    ]);
    header('Location: sessions.php?success=updated');
    exit;
}

$pageTitle = 'Редактирование сеанса — Админ-панель';
require_once __DIR__ . '/includes/header.php';
?>

<main class="admin-page">
    <div class="container-fluid px-3 px-md-4">
        <div class="admin-header">
            <div>
                <h1>✏️ Редактирование сеанса</h1>
                <p><?= htmlspecialchars($session['movie_title']) ?> — <?= date('d.m.Y H:i', strtotime($session['session_time'])) ?></p>
            </div>
            <a href="sessions.php" class="btn btn-secondary">← Назад</a>
        </div>

        <div class="admin-form">
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Фильм *</label>
                        <select name="movie_id" class="form-control" required>
                            <?php foreach ($movies as $movie): ?>
                                <option value="<?= $movie['id'] ?>" <?= $movie['id'] == $session['movie_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($movie['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Зал *</label>
                        <select name="hall_id" class="form-control" required>
                            <?php foreach ($halls as $hall): ?>
                                <option value="<?= $hall['id'] ?>" <?= $hall['id'] == $session['hall_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($hall['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Дата и время *</label>
                        <input type="datetime-local" name="session_time" class="form-control" 
                               value="<?= date('Y-m-d\TH:i', strtotime($session['session_time'])) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Цена (₽) *</label>
                        <input type="number" name="price" class="form-control" value="<?= $session['price'] ?>" required>
                    </div>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button type="submit" name="update_session" class="btn btn-primary">💾 Сохранить изменения</button>
                    <a href="sessions.php" class="btn btn-secondary">❌ Отмена</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>