<?php
// admin/promo.php

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

// Обработка добавления промокода
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_promo'])) {
    $stmt = $db->prepare("
        INSERT INTO promo_codes (code, discount_percent, valid_from, valid_to, usage_limit) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        strtoupper($_POST['code']),
        $_POST['discount_percent'],
        $_POST['valid_from'],
        $_POST['valid_to'],
        $_POST['usage_limit'] ?: null
    ]);
    header('Location: promo.php?success=added');
    exit;
}

// Обработка удаления промокода
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM promo_codes WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header('Location: promo.php?success=deleted');
    exit;
}

// Обработка активации/деактивации промокода
if (isset($_GET['toggle'])) {
    $stmt = $db->prepare("UPDATE promo_codes SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$_GET['toggle']]);
    header('Location: promo.php?success=toggled');
    exit;
}

// Получаем список промокодов
$stmt = $db->prepare("SELECT * FROM promo_codes ORDER BY id DESC");
$stmt->execute();
$promoCodes = $stmt->fetchAll();

$pageTitle = 'Управление промокодами — Админ-панель';
require_once __DIR__ . '/includes/header.php';
?>

<main class="admin-page">
    <div class="container-fluid px-3 px-md-4">
        <div class="admin-header">
            <div>
                <h1>🏷️ Управление промокодами</h1>
                <p>Создание и управление промокодами для скидок</p>
            </div>
            <div>
                <button class="btn btn-primary" onclick="document.getElementById('addPromoForm').style.display='block'">
                    + Добавить промокод
                </button>
                <a href="index.php" class="btn btn-secondary">← Назад</a>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php 
                $messages = [
                    'added' => '✅ Промокод успешно добавлен',
                    'deleted' => '🗑️ Промокод удалён',
                    'toggled' => '🔄 Статус промокода изменён'
                ];
                echo $messages[$_GET['success']] ?? 'Действие выполнено';
                ?>
            </div>
        <?php endif; ?>

        <!-- Форма добавления промокода -->
        <div class="admin-form" id="addPromoForm" style="display: none;">
            <h3>📝 Добавить новый промокод</h3>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Код промокода *</label>
                        <input type="text" name="code" class="form-control" placeholder="SUMMER2026" required>
                        <small style="color: #77727e;">Будет автоматически преобразован в верхний регистр</small>
                    </div>
                    <div class="form-group">
                        <label>Скидка (%) *</label>
                        <input type="number" name="discount_percent" class="form-control" min="1" max="100" required>
                    </div>
                    <div class="form-group">
                        <label>Действителен с *</label>
                        <input type="datetime-local" name="valid_from" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Действителен до *</label>
                        <input type="datetime-local" name="valid_to" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Лимит использований</label>
                        <input type="number" name="usage_limit" class="form-control" placeholder="Оставьте пустым для безлимита" min="1">
                    </div>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button type="submit" name="add_promo" class="btn btn-primary">💾 Сохранить</button>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('addPromoForm').style.display='none'">❌ Отмена</button>
                </div>
            </form>
        </div>

        <!-- Список промокодов -->
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Код</th>
                        <th>Скидка</th>
                        <th>Действителен с</th>
                        <th>Действителен до</th>
                        <th>Использовано</th>
                        <th>Лимит</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($promoCodes)): ?>
                        <tr>
                            <td colspan="9" style="text-align:center; color:#77727e; padding:30px;">
                                Нет промокодов. Создайте первый промокод!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($promoCodes as $promo): 
                            $isExpired = strtotime($promo['valid_to']) < time();
                            $isValid = $promo['is_active'] && !$isExpired;
                        ?>
                            <tr>
                                <td><?= $promo['id'] ?></td>
                                <td><strong><?= htmlspecialchars($promo['code']) ?></strong></td>
                                <td><span style="color: #6fcb8a;">-<?= $promo['discount_percent'] ?>%</span></td>
                                <td><?= date('d.m.Y H:i', strtotime($promo['valid_from'])) ?></td>
                                <td><?= date('d.m.Y H:i', strtotime($promo['valid_to'])) ?></td>
                                <td><?= $promo['used_count'] ?></td>
                                <td><?= $promo['usage_limit'] ?? '∞' ?></td>
                                <td>
                                    <span class="status-badge <?= $isValid ? 'status-active' : 'status-inactive' ?>">
                                        <?php if ($isExpired): ?>
                                            ⏰ Истёк
                                        <?php elseif ($promo['is_active']): ?>
                                            ✅ Активен
                                        <?php else: ?>
                                            ❌ Неактивен
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="?toggle=<?= $promo['id'] ?>" class="btn btn-sm btn-secondary" title="Переключить статус">
                                        <?= $promo['is_active'] ? '🔴' : '🟢' ?>
                                    </a>
                                    <a href="?delete=<?= $promo['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Удалить промокод?')" title="Удалить">🗑️</a>
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