<?php
// admin/includes/footer.php - подвал для админ-панели
?>
<footer class="site-footer" id="contacts">
    <div class="container-fluid px-3 px-md-4">
        <div class="footer-grid">
            <div>
                <div class="footer-logo">КИНОТЕАТР</div>
                <p class="footer-muted">Панель администратора</p>
            </div>
            <div>
                <div class="footer-title">Навигация</div>
                <a href="index.php">Главная</a>
                <a href="movies.php">Фильмы</a>
                <a href="sessions.php">Сеансы</a>
            </div>
            <div>
                <div class="footer-title">Управление</div>
                <a href="users.php">Пользователи</a>
                <a href="news.php">Новости</a>
                <a href="promo.php">Промокоды</a>
            </div>
            <div>
                <div class="footer-title">Контакты</div>
                <span>+7 (000) 000-00-00</span>
                <span>info@cinema.local</span>
                <span>Ежедневно 10:00–23:00</span>
            </div>
        </div>
        <div class="footer-bottom">© <?= date('Y') ?> Кинотеатр. Панель администратора.</div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Основной JS сайта -->
<script src="../js/main.js"></script>
</body>
</html>