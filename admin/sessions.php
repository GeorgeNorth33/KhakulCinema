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

// Получаем список фильмов и залов для формы
$stmt = $db->prepare("SELECT id, title FROM movies WHERE is_active = 1");
$stmt->execute();
$movies = $stmt->fetchAll();

$stmt = $db->prepare("SELECT id, name FROM halls WHERE is_active = 1");
$stmt->execute();
$halls = $stmt->fetchAll();

// Обработка добавления сеанса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_session'])) {
    $stmt = $db->prepare("
        INSERT INTO sessions (movie_id, hall_id, session_time, price) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $_POST['movie_id'],
        $_POST['hall_id'],
        $_POST['session_time'],
        $_POST['price']
    ]);
    header('Location: sessions.php?success=added');
    exit;
}

// Обработка удаления сеанса
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("UPDATE sessions SET is_active = 0 WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header('Location: sessions.php?success=deleted');
    exit;
}

// Получаем список сеансов
$stmt = $db->prepare("
    SELECT 
        s.*,
        m.title as movie_title,
        h.name as hall_name
    FROM sessions s
    JOIN movies m ON s.movie_id = m.id
    JOIN halls h ON s.hall_id = h.id
    WHERE s.is_active = 1
    ORDER BY s.session_time DESC
");
$stmt->execute();
$sessions = $stmt->fetchAll();

$pageTitle = 'Управление сеансами — Админ-панель';
require __DIR__ . '/includes/header.php';
?>

<main class="admin-page">
    <div class="container-fluid px-3 px-md-4">
        <div class="admin-header">
            <h1>Управление сеансами</h1>
            <button class="btn btn-primary" onclick="document.getElementById('addSessionForm').style.display='block'">
                + Добавить сеанс
            </button>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php 
                $messages = [
                    'added' => 'Сеанс успешно добавлен',
                    'deleted' => 'Сеанс удалён'
                ];
                echo $messages[$_GET['success']] ?? 'Действие выполнено';
                ?>
            </div>
        <?php endif; ?>

        <!-- Форма добавления сеанса -->
        <div class="admin-form" id="addSessionForm" style="display: none;">
            <h3>Добавить новый сеанс</h3>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Фильм</label>
                        <select name="movie_id" class="form-control" required>
                            <option value="">Выберите фильм</option>
                            <?php foreach ($movies as $movie): ?>
                                <option value="<?= $movie['id'] ?>"><?= htmlspecialchars($movie['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Зал</label>
                        <select name="hall_id" class="form-control" required>
                            <option value="">Выберите зал</option>
                            <?php foreach ($halls as $hall): ?>
                                <option value="<?= $hall['id'] ?>"><?= htmlspecialchars($hall['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Дата и время</label>
                        <input type="datetime-local" name="session_time" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Цена (₽)</label>
                        <input type="number" name="price" class="form-control" required>
                    </div>
                </div>
                <button type="submit" name="add_session" class="btn btn-primary">Сохранить</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('addSessionForm').style.display='none'">Отмена</button>
            </form>
        </div>

        <!-- Список сеансов -->
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Фильм</th>
                        <th>Зал</th>
                        <th>Дата и время</th>
                        <th>Цена</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sessions as $session): ?>
                        <tr>
                            <td><?= $session['id'] ?></td>
                            <td><strong><?= htmlspecialchars($session['movie_title']) ?></strong></td>
                            <td><?= htmlspecialchars($session['hall_name']) ?></td>
                            <td><?= date('d.m.Y H:i', strtotime($session['session_time'])) ?></td>
                            <td><?= number_format($session['price'], 0, '', ' ') ?> ₽</td>
                            <td>
                                <a href="sessions-edit.php?id=<?= $session['id'] ?>" class="btn btn-sm btn-secondary">Редактировать</a>
                                <a href="?delete=<?= $session['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Удалить сеанс?')">Удалить</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>