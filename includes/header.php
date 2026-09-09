<?php
$pageTitle = $pageTitle ?? 'Кинотеатр';
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="Главная страница информационной системы кинотеатра">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Russo+One&family=Roboto+Condensed:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container-fluid px-3 px-md-4">
        <nav class="navbar navbar-expand-lg navbar-dark cinema-nav">
        

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                    aria-controls="mainNav" aria-expanded="false" aria-label="Открыть меню">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav mx-auto main-links">
                    <li class="nav-item"><a class="nav-link" href="index.php#sessions">Сеансы</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#news">Новости</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#contacts">Контакты</a></li>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="admin/index.php">Админ</a></li>
                    <?php endif; ?>
                </ul>

                <div class="nav-actions">
                    <a class="profile-button" href="profile.php" title="Личный кабинет" aria-label="Личный кабинет">
                        <span class="profile-icon">●</span>
                    </a>
                </div>
            </div>
        </nav>
    </div>
</header>
