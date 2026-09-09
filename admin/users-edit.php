<?php
// admin/users-edit.php

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

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получаем данные пользователя
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userData = $stmt->fetch();

if (!$userData) {
    header('Location: users.php');
    exit;
}

// Обработка обновления пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $sql = "UPDATE users SET full_name = ?, phone = ?, role = ?";
    $params = [$_POST['full_name'], $_POST['phone'], $_POST['role']];
    
    if (!empty($_POST['password'])) {
        $sql .= ", password = ?";
        $params[] = $_POST['password'];
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $userId;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    header('Location: users.php?success=updated');
    exit;
}

$pageTitle = 'Редактирование пользователя — Админ-панель';
require_once __DIR__ . '/includes/header.php';
?>

<main class="admin-page">
    <div class="container-fluid px-3 px-md-4">
        <div class="admin-header">
            <div>
                <h1>✏️ Редактирование пользователя</h1>
                <p><?= htmlspecialchars($userData['full_name']) ?> (<?= htmlspecialchars($userData['email']) ?>)</p>
            </div>
            <a href="users.php" class="btn btn-secondary">← Назад</a>
        </div>

        <div class="admin-form">
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Полное имя *</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($userData['full_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($userData['email']) ?>" disabled>
                        <small style="color: #77727e;">Email нельзя изменить</small>
                    </div>
                    <div class="form-group">
                        <label>Телефон</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($userData['phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Роль *</label>
                        <select name="role" class="form-control" required>
                            <option value="user" <?= $userData['role'] == 'user' ? 'selected' : '' ?>>Пользователь</option>
                            <option value="admin" <?= $userData['role'] == 'admin' ? 'selected' : '' ?>>Администратор</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label>Новый пароль</label>
                        <input type="text" name="password" class="form-control" placeholder="Оставьте пустым, чтобы не менять">
                        <small style="color: #77727e;">Пароль будет сохранён в открытом виде</small>
                    </div>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button type="submit" name="update_user" class="btn btn-primary">💾 Сохранить изменения</button>
                    <a href="users.php" class="btn btn-secondary">❌ Отмена</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>