<?php
// admin/users.php

// Правильный путь к database.php - поднимаемся на 2 уровня вверх
require_once __DIR__ . '/../config/database.php';  // <-- ИСПРАВЛЕНО (одна точка назад)
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

// Получаем список пользователей
$stmt = $db->prepare("SELECT * FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll();

$pageTitle = 'Управление пользователями — Админ-панель';
require_once __DIR__ . '/includes/header.php';
?>

<main class="admin-page">
    <div class="container-fluid px-3 px-md-4">
        <div class="admin-header">
            <div>
                <h1>👤 Управление пользователями</h1>
                <p>Список зарегистрированных пользователей</p>
            </div>
            <a href="index.php" class="btn btn-secondary">← Назад</a>
        </div>

        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Имя</th>
                        <th>Email</th>
                        <th>Телефон</th>
                        <th>Роль</th>
                        <th>Дата регистрации</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center; color:#77727e; padding:30px;">
                                Нет пользователей
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $userData): ?>
                            <tr>
                                <td><?= $userData['id'] ?></td>
                                <td><strong><?= htmlspecialchars($userData['full_name']) ?></strong></td>
                                <td><?= htmlspecialchars($userData['email']) ?></td>
                                <td><?= htmlspecialchars($userData['phone'] ?? '-') ?></td>
                                <td>
                                    <span class="status-badge <?= $userData['role'] === 'admin' ? 'status-admin' : 'status-user' ?>">
                                        <?= $userData['role'] === 'admin' ? 'Админ' : 'Пользователь' ?>
                                    </span>
                                </td>
                                <td><?= date('d.m.Y', strtotime($userData['created_at'])) ?></td>
                                <td>
                                    <a href="users-edit.php?id=<?= $userData['id'] ?>" class="btn btn-sm btn-secondary" title="Редактировать">Редактировать</a>
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