<?php
// payment.php - страница оплаты (только банковская карта)

require_once __DIR__ . '/includes/MovieManager.php';
require_once __DIR__ . '/includes/BookingManager.php';
require_once __DIR__ . '/includes/User.php';
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=payment');
    exit;
}

$movieManager = new MovieManager();
$bookingManager = new BookingManager();

// Получаем данные из сессии
$sessionId = isset($_SESSION['booking_session_id']) ? $_SESSION['booking_session_id'] : 0;
$selectedSeats = isset($_SESSION['selected_seats']) ? $_SESSION['selected_seats'] : [];

// Если данные не найдены в сессии, пробуем из GET
if (empty($selectedSeats) && isset($_GET['session'])) {
    $sessionId = (int)$_GET['session'];
    if (isset($_GET['seats'])) {
        $selectedSeats = explode(',', $_GET['seats']);
    }
}

if (empty($selectedSeats) || !$sessionId) {
    header('Location: index.php');
    exit;
}

// Получаем информацию о сеансе
$session = $movieManager->getSessionById($sessionId);
if (!$session) {
    header('Location: index.php');
    exit;
}

// Получаем информацию о местах
$seatDetails = [];
foreach ($selectedSeats as $seatId) {
    $stmt = db()->prepare("SELECT row_number, seat_number FROM seats WHERE id = ?");
    $stmt->execute([$seatId]);
    $seat = $stmt->fetch();
    if ($seat) {
        $seatDetails[] = 'Ряд ' . $seat['row_number'] . ' Место ' . $seat['seat_number'];
    }
}

// Рассчитываем сумму
$pricePerSeat = $session['price'];
$totalAmount = $pricePerSeat * count($selectedSeats);
$serviceFee = 50;
$finalAmount = $totalAmount + $serviceFee;

// Обработка формы оплаты
$paymentSuccess = false;
$bookingResult = null;
$paymentError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    try {
        $promoCode = isset($_POST['promo_code']) && !empty($_POST['promo_code']) ? trim($_POST['promo_code']) : null;
        
        // Создаём бронирование
        $bookingResult = $bookingManager->createBooking(
            $_SESSION['user_id'],
            $sessionId,
            $selectedSeats,
            $promoCode
        );
        
        if ($bookingResult) {
            $paymentSuccess = true;
            // Очищаем сессию
            unset($_SESSION['selected_seats']);
            unset($_SESSION['booking_session_id']);
            
            // Добавляем запись в историю
            $user = new User($_SESSION['user_id']);
            $user->logActivity('payment_success', null, null, 'Билеты оплачены: ' . $bookingResult['booking_code']);
        }
    } catch (Exception $e) {
        $paymentError = $e->getMessage();
    }
}

$pageTitle = 'Оплата билетов — Кинотеатр';
require __DIR__ . '/includes/header.php';
?>

<main class="payment-page">
    <div class="container-fluid px-3 px-md-4">
        <?php if ($paymentSuccess && $bookingResult): ?>
            <!-- УСПЕШНАЯ ОПЛАТА -->
            <div class="payment-success">
                <div class="success-icon">✅</div>
                <h1>Оплата прошла успешно!</h1>
                <p>Ваши билеты забронированы и подтверждены. Номер заказа: <strong><?= htmlspecialchars($bookingResult['booking_code']) ?></strong></p>
                <div class="success-actions">
                    <a href="profile.php" class="btn btn-primary">Перейти к билетам</a>
                    <a href="index.php" class="btn btn-secondary">На главную</a>
                </div>
                
                <div class="order-confirmation">
                    <h3>📋 Детали заказа</h3>
                    <div class="confirmation-grid">
                        <div class="confirmation-item">
                            <span class="label">Фильм</span>
                            <span class="value"><?= htmlspecialchars($session['movie_title']) ?></span>
                        </div>
                        <div class="confirmation-item">
                            <span class="label">Дата и время</span>
                            <span class="value"><?= date('d.m.Y H:i', strtotime($session['session_time'])) ?></span>
                        </div>
                        <div class="confirmation-item">
                            <span class="label">Зал</span>
                            <span class="value"><?= htmlspecialchars($session['hall_name']) ?></span>
                        </div>
                        <div class="confirmation-item">
                            <span class="label">Места</span>
                            <span class="value"><?= implode(', ', $seatDetails) ?></span>
                        </div>
                        <div class="confirmation-item">
                            <span class="label">Количество билетов</span>
                            <span class="value"><?= count($selectedSeats) ?></span>
                        </div>
                        <div class="confirmation-item">
                            <span class="label">Статус</span>
                            <span class="value" style="color: #6fcb8a;">Подтверждён</span>
                        </div>
                        <div class="confirmation-item">
                            <span class="label">Итого</span>
                            <span class="value total"><?= number_format($bookingResult['total_amount'], 0, '', ' ') ?> ₽</span>
                        </div>
                        <?php if ($bookingResult['discount'] > 0): ?>
                        <div class="confirmation-item">
                            <span class="label">Скидка</span>
                            <span class="value discount">-<?= number_format($bookingResult['discount'], 0, '', ' ') ?> ₽</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- ФОРМА ОПЛАТЫ -->
            <div class="payment-grid">
                <!-- Левая колонка: информация о заказе -->
                <section class="payment-info">
                    <h1>Оплата билетов</h1>
                    
                    <div class="payment-movie">
                        <div class="payment-poster">
                            <img src="<?= htmlspecialchars($session['poster_url'] ?? 'assets/posters/default.svg') ?>" 
                                 alt="<?= htmlspecialchars($session['movie_title']) ?>">
                        </div>
                        <div class="payment-movie-info">
                            <h2><?= htmlspecialchars($session['movie_title']) ?></h2>
                            <p><?= htmlspecialchars($session['age_rating']) ?> · <?= htmlspecialchars($session['genre'] ?? '') ?></p>
                            <div class="payment-details">
                                <span><?= date('d.m.Y', strtotime($session['session_time'])) ?></span>
                                <span><?= date('H:i', strtotime($session['session_time'])) ?></span>
                                <span><?= htmlspecialchars($session['hall_name']) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="payment-seats">
                        <h3>Выбранные места</h3>
                        <div class="seats-list">
                            <?php foreach ($seatDetails as $seat): ?>
                                <span class="seat-tag"><?= htmlspecialchars($seat) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="payment-promo">
                        <h3>Промокод</h3>
                        <div class="promo-input-group">
                            <input type="text" id="promoCode" class="form-control" placeholder="Введите промокод">
                            <button class="btn btn-secondary" id="applyPromo">Применить</button>
                        </div>
                        <div id="promoMessage" class="promo-message"></div>
                    </div>
                </section>

                <!-- Правая колонка: итог и форма оплаты -->
                <aside class="payment-summary">
                    <h2>Итого к оплате</h2>
                    
                    <div class="summary-items">
                        <div class="summary-row">
                            <span>Билеты (<?= count($selectedSeats) ?> шт.)</span>
                            <span><?= number_format($totalAmount, 0, '', ' ') ?> ₽</span>
                        </div>
                        <div class="summary-row">
                            <span>Сервисный сбор</span>
                            <span><?= number_format($serviceFee, 0, '', ' ') ?> ₽</span>
                        </div>
                        <div class="summary-row discount-row" style="display: none;">
                            <span>Скидка</span>
                            <span class="discount-amount">-0 ₽</span>
                        </div>
                        <div class="summary-row total">
                            <span>Итого</span>
                            <span class="total-amount" id="totalAmount"><?= number_format($finalAmount, 0, '', ' ') ?> ₽</span>
                        </div>
                    </div>

                    <form method="POST" class="payment-form" id="paymentForm">
                        <input type="hidden" name="promo_code" id="promoCodeInput" value="">
                        
                        <div class="payment-methods">
                            <h3>Способ оплаты</h3>
                            <div class="method-option">
                                <input type="radio" name="payment_method" id="card" value="card" checked>
                                <label for="card">Банковская карта</label>
                            </div>
                        </div>

                        <div class="card-details" id="cardDetails">
                            <div class="form-group">
                                <label>Номер карты</label>
                                <input type="text" class="form-control" placeholder="0000 0000 0000 0000" maxlength="19" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Срок действия</label>
                                    <input type="text" class="form-control" placeholder="ММ/ГГ" maxlength="5" required>
                                </div>
                                <div class="form-group">
                                    <label>CVV</label>
                                    <input type="password" class="form-control" placeholder="***" maxlength="3" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Владелец карты</label>
                                <input type="text" class="form-control" placeholder="IVAN IVANOV" required>
                            </div>
                        </div>

                        <?php if ($paymentError): ?>
                            <div class="alert alert-error"><?= htmlspecialchars($paymentError) ?></div>
                        <?php endif; ?>

                        <div class="payment-agreement">
                            <label class="checkbox-label">
                                <input type="checkbox" name="agree" required>
                                <span>Я соглашаюсь с <a href="#">правилами оплаты</a> и <a href="#">политикой возврата</a></span>
                            </label>
                        </div>

                        <button type="submit" name="confirm_payment" class="btn btn-primary btn-block btn-pay" id="payButton">
                            Оплатить <span id="payAmount"><?= number_format($finalAmount, 0, '', ' ') ?> ₽</span>
                        </button>

                        <a href="ticket-booking.php?session=<?= $sessionId ?>" class="btn btn-secondary btn-block">
                            ← Вернуться к выбору мест
                        </a>
                    </form>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const promoInput = document.getElementById('promoCode');
    const promoBtn = document.getElementById('applyPromo');
    const promoMessage = document.getElementById('promoMessage');
    const promoCodeInput = document.getElementById('promoCodeInput');
    const discountRow = document.querySelector('.discount-row');
    const discountAmount = document.querySelector('.discount-amount');
    const totalAmountSpan = document.getElementById('totalAmount');
    const payAmountSpan = document.getElementById('payAmount');
    const payButton = document.getElementById('payButton');
    const originalTotal = <?= $finalAmount ?>;
    
    // Маска для номера карты
    const cardInput = document.querySelector('input[placeholder="0000 0000 0000 0000"]');
    if (cardInput) {
        cardInput.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 16) value = value.slice(0, 16);
            let formatted = '';
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formatted += ' ';
                }
                formatted += value[i];
            }
            this.value = formatted;
        });
    }
    
    // Маска для срока действия
    const dateInput = document.querySelector('input[placeholder="ММ/ГГ"]');
    if (dateInput) {
        dateInput.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 4) value = value.slice(0, 4);
            if (value.length >= 2) {
                let month = parseInt(value.slice(0, 2));
                if (month > 12) month = 12;
                value = String(month).padStart(2, '0') + value.slice(2);
            }
            let formatted = '';
            for (let i = 0; i < value.length; i++) {
                if (i === 2) {
                    formatted += '/';
                }
                formatted += value[i];
            }
            this.value = formatted;
        });
    }
    
    // Маска для CVV
    const cvvInput = document.querySelector('input[placeholder="***"]');
    if (cvvInput) {
        cvvInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').slice(0, 3);
        });
    }
    
    // Промокод
    if (promoBtn && promoInput) {
        promoBtn.addEventListener('click', function() {
            const code = promoInput.value.trim().toUpperCase();
            
            if (!code) {
                promoMessage.textContent = 'Введите промокод';
                promoMessage.className = 'promo-message error';
                return;
            }
            
            promoBtn.disabled = true;
            promoBtn.textContent = 'Проверка...';
            
            fetch('check-promo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ code: code })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const discountPercent = data.discount_percent;
                    const discountValue = (originalTotal * discountPercent) / 100;
                    const newTotal = Math.round(originalTotal - discountValue);
                    
                    promoMessage.textContent = '' + data.message;
                    promoMessage.className = 'promo-message success';
                    
                    discountRow.style.display = 'flex';
                    discountAmount.textContent = '-' + Math.round(discountValue).toLocaleString() + ' ₽';
                    totalAmountSpan.textContent = newTotal.toLocaleString() + ' ₽';
                    payAmountSpan.textContent = newTotal.toLocaleString() + ' ₽';
                    
                    promoCodeInput.value = code;
                    promoInput.disabled = true;
                    promoBtn.textContent = 'Применён';
                    promoBtn.style.background = '#2a7a3a';
                } else {
                    promoMessage.textContent = '' + data.message;
                    promoMessage.className = 'promo-message error';
                    promoBtn.disabled = false;
                    promoBtn.textContent = 'Применить';
                }
            })
            .catch(error => {
                promoMessage.textContent = 'Ошибка при проверке промокода';
                promoMessage.className = 'promo-message error';
                promoBtn.disabled = false;
                promoBtn.textContent = 'Применить';
            });
        });
        
        promoInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                promoBtn.click();
            }
        });
    }
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>