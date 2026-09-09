<?php
require_once __DIR__ . '/includes/User.php';
session_start();

$pageTitle = 'Регистрация — Кинотеатр';
$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['password'] !== $_POST['confirmPassword']) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($_POST['password']) < 6) {
        $error = 'Пароль должен содержать минимум 6 символов';
    } else {
        $user = new User();
        $data = [
            'full_name' => $_POST['fullName'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'] ?? null,
            'password' => $_POST['password'] // Сохраняем как есть, без хэширования
        ];
        
        if ($user->register($data)) {
            $success = true;
            // Автоматический вход после регистрации
            $_SESSION['user_id'] = $user->getId();
            $_SESSION['user_name'] = $user->getData()['full_name'];
            header('Location: profile.php');
            exit;
        } else {
            $error = 'Пользователь с таким email уже существует';
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<main class="auth-page">
    <div class="container-fluid px-3 px-md-4">
        <div class="auth-wrapper">
            <div class="auth-card">
                <div class="auth-header">
                    <h1>Регистрация</h1>
                    <p>Создайте аккаунт для покупки билетов</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form class="auth-form" method="POST">
                    <div class="form-group">
                        <label for="fullName">Полное имя</label>
                        <input type="text" id="fullName" name="fullName" class="form-control" 
                               placeholder="Иван Иванов" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Электронная почта</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               placeholder="ivan@example.com" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Телефон</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               placeholder="+7 (000) 000-00-00">
                    </div>

                    <div class="form-group">
                        <label for="password">Пароль</label>
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="Минимум 6 символов" required minlength="6">
                        <small style="color: #55515a; font-size: 11px;">Пароль сохраняется в открытом виде</small>
                    </div>

                    <div class="form-group">
                        <label for="confirmPassword">Подтверждение пароля</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" 
                               class="form-control" placeholder="Повторите пароль" required>
                    </div>

                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="agree" required>
                            <span>Я соглашаюсь с <a href="#">правилами покупки</a> и <a href="#">политикой конфиденциальности</a></span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Зарегистрироваться</button>

                    <div class="auth-links">
                        <span>Уже есть аккаунт?</span>
                        <a href="login.php">Войти</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>