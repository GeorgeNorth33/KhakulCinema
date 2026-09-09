<?php
require_once __DIR__ . '/includes/User.php';
session_start();

$pageTitle = 'Вход — Кинотеатр';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = new User();
    if ($user->login($_POST['email'], $_POST['password'])) {
        header('Location: profile.php');
        exit;
    } else {
        $error = 'Неверный email или пароль';
    }
}

require __DIR__ . '/includes/header.php';
?>

<main class="auth-page">
    <div class="container-fluid px-3 px-md-4">
        <div class="auth-wrapper">
            <div class="auth-card">
                <div class="auth-header">
                    <h1>Вход в аккаунт</h1>
                    <p>Войдите, чтобы бронировать билеты</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form class="auth-form" method="POST">
                    <div class="form-group">
                        <label for="email">Электронная почта</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               placeholder="ivan@example.com" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Пароль</label>
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="Введите пароль" required>
                        <a href="#" class="forgot-password">Забыли пароль?</a>
                    </div>

                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember">
                            <span>Запомнить меня</span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Войти</button>

                    <div class="auth-links">
                        <span>Нет аккаунта?</span>
                        <a href="register.php">Зарегистрироваться</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>