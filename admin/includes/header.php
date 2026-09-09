<?php
// admin/includes/header.php - заголовок для админ-панели

$pageTitle = $pageTitle ?? 'Админ-панель — Кинотеатр';
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="Панель администратора кинотеатра">
    
    <!-- Основные стили Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Шрифты -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Russo+One&family=Roboto+Condensed:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Основные стили сайта -->
    <link rel="stylesheet" href="../css/style.css">
    
    <!-- Стили для админ-панели -->
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
<header class="site-header">
    <div class="container-fluid px-3 px-md-4">
        <nav class="navbar navbar-expand-lg navbar-dark cinema-nav">
            <a class="navbar-brand brand-mark" href="index.php" aria-label="Кинотеатр">
                <span class="brand-dot"></span>КИНОТЕАТР
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                    aria-controls="mainNav" aria-expanded="false" aria-label="Открыть меню">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav mx-auto main-links">
                    <li class="nav-item"><a class="nav-link active" href="index.php">Админ-панель</a></li>
                    <li class="nav-item"><a class="nav-link" href="movies.php">Фильмы</a></li>
                    <li class="nav-item"><a class="nav-link" href="sessions.php">Сеансы</a></li>
                    <li class="nav-item"><a class="nav-link" href="users.php">Пользователи</a></li>
                    <li class="nav-item"><a class="nav-link" href="news.php">Новости</a></li>
                    <li class="nav-item"><a class="nav-link" href="../index.php">← На сайт</a></li>
                </ul>

                <a class="profile-button" href="../profile.php" title="Личный кабинет" aria-label="Личный кабинет">
                    <span class="profile-icon">●</span>
                </a>
            </div>
        </nav>
    </div>
</header>