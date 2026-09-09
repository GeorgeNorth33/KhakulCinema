<?php
require_once __DIR__ . '/includes/MovieManager.php';
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=ticket-booking');
    exit;
}

$movieManager = new MovieManager();

$sessionId = isset($_GET['session']) ? (int)$_GET['session'] : 0;
$session = $movieManager->getSessionById($sessionId);

if (!$session) {
    header('Location: index.php');
    exit;
}

// Получаем доступные места
$seats = $movieManager->getAvailableSeats($sessionId);

$pageTitle = 'Выбор мест — ' . htmlspecialchars($session['movie_title']);
require __DIR__ . '/includes/header.php';
?>

<main class="booking-page">
    <div class="container-fluid px-3 px-md-4">
        <div class="booking-wrapper">
            <!-- Левая колонка: выбор мест -->
            <section class="seat-selection">
                <h2>Выбор мест</h2>
                
                <!-- Информация о выбранных местах -->
                <div class="selected-seats-info" id="selectedSeatsInfo">
                    <div class="info-header">
                        <span class="info-label">Выбрано мест:</span>
                        <span class="info-count" id="selectedCount">0</span>
                    </div>
                    <div class="selected-seats-list" id="selectedSeatsList">
                        <span class="empty-message">Места не выбраны</span>
                    </div>
                    <button class="btn btn-sm btn-danger clear-all-btn" id="clearAllSeats" style="display: none;">
                        Очистить все
                    </button>
                </div>
                
                <div class="hall-scheme">
                    <div class="screen-label">ЭКРАН</div>
                    <div class="screen-line"></div>
                    
                    <div class="seats-grid" id="seatsGrid">
                        <?php if (empty($seats)): ?>
                            <div class="empty-state">
                                <p>Места для этого сеанса не найдены</p>
                            </div>
                        <?php else: ?>
                            <?php
                            $currentRow = null;
                            $rowSeats = [];
                            
                            // Группируем места по рядам
                            foreach ($seats as $seat) {
                                $rowSeats[$seat['row_number']][] = $seat;
                            }
                            
                            foreach ($rowSeats as $rowNumber => $rowSeatList):
                                $rowLabel = 'Ряд ' . $rowNumber;
                            ?>
                                <div class="seat-row">
                                    <span class="row-label"><?= $rowLabel ?></span>
                                    <div class="seat-group">
                                        <?php foreach ($rowSeatList as $seat): ?>
                                            <?php 
                                            $seatLabel = $rowNumber . '-' . $seat['seat_number'];
                                            $isTaken = (bool)$seat['is_taken'];
                                            ?>
                                            <button class="seat <?= $isTaken ? 'taken' : 'available' ?>" 
                                                    data-seat="<?= $seatLabel ?>"
                                                    data-id="<?= $seat['id'] ?>"
                                                    data-row="<?= $seat['row_number'] ?>"
                                                    data-number="<?= $seat['seat_number'] ?>"
                                                    <?= $isTaken ? 'disabled' : '' ?>>
                                                <?= $seat['seat_number'] ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="seats-legend">
                        <span><span class="legend-dot available"></span> Свободно</span>
                        <span><span class="legend-dot selected"></span> Выбрано</span>
                        <span><span class="legend-dot taken"></span> Занято</span>
                    </div>
                </div>
            </section>

            <!-- Правая колонка: информация о заказе -->
            <aside class="order-summary">
                <h2>Ваш заказ</h2>
                
                <div class="order-movie">
                    <img src="<?= htmlspecialchars($session['poster_url'] ?? 'assets/posters/default.svg') ?>" 
                         alt="<?= htmlspecialchars($session['movie_title']) ?>" 
                         class="order-poster">
                    <div class="order-movie-info">
                        <h3><?= htmlspecialchars($session['movie_title']) ?></h3>
                        <p><?= htmlspecialchars($session['age_rating'] ?? '0+') ?> · <?= floor(($session['duration'] ?? 0) / 60) ?> ч <?= ($session['duration'] ?? 0) % 60 ?> мин</p>
                    </div>
                </div>

                <div class="order-details">
                    <div class="order-row">
                        <span>Дата</span>
                        <span><?= date('d.m.Y', strtotime($session['session_time'])) ?></span>
                    </div>
                    <div class="order-row">
                        <span>Время</span>
                        <span><?= date('H:i', strtotime($session['session_time'])) ?></span>
                    </div>
                    <div class="order-row">
                        <span>Зал</span>
                        <span><?= htmlspecialchars($session['hall_name'] ?? '') ?></span>
                    </div>
                    <div class="order-row">
                        <span>Места</span>
                        <span id="selectedSeatsDisplay">Не выбрано</span>
                    </div>
                </div>

                <div class="order-total">
                    <div class="order-row">
                        <span>Количество билетов</span>
                        <span id="ticketCount">0</span>
                    </div>
                    <div class="order-row total-price">
                        <span>Итого</span>
                        <span id="totalPrice">0 ₽</span>
                    </div>
                </div>

                <div class="order-actions">
                    <form action="payment.php" method="GET" id="bookingForm">
                        <input type="hidden" name="session" value="<?= $sessionId ?>">
                        <input type="hidden" name="seats" id="seatsInput" value="">
                        <button type="submit" class="btn btn-primary btn-block" id="confirmBooking" disabled>
                            Перейти к оплате
                        </button>
                    </form>
                    <button class="btn btn-secondary btn-block" onclick="window.location.href='session-select.php?id=<?= $session['movie_id'] ?? 1 ?>'">
                        ← Назад к сеансам
                    </button>
                </div>
            </aside>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const seats = document.querySelectorAll('.seat.available');
    const selectedSeatsDisplay = document.getElementById('selectedSeatsDisplay');
    const selectedSeatsList = document.getElementById('selectedSeatsList');
    const selectedCount = document.getElementById('selectedCount');
    const ticketCountDisplay = document.getElementById('ticketCount');
    const totalPriceDisplay = document.getElementById('totalPrice');
    const seatsInput = document.getElementById('seatsInput');
    const confirmBtn = document.getElementById('confirmBooking');
    const clearAllBtn = document.getElementById('clearAllSeats');
    const selectedSeatsInfo = document.getElementById('selectedSeatsInfo');
    const bookingForm = document.getElementById('bookingForm');
    const pricePerTicket = <?= $session['price'] ?? 0 ?>;
    
    let selectedSeats = [];
    let selectedSeatIds = [];

    // Функция обновления отображения
    function updateOrderSummary() {
        const count = selectedSeats.length;
        const total = count * pricePerTicket;
        
        // Обновляем отображение мест в заказе
        let seatsDisplay = selectedSeats.map(s => {
            let parts = s.split('-');
            return 'Ряд ' + parts[0] + ' Место ' + parts[1];
        }).join(', ');
        
        selectedSeatsDisplay.textContent = seatsDisplay || 'Не выбрано';
        ticketCountDisplay.textContent = count;
        totalPriceDisplay.textContent = total ? total.toLocaleString() + ' ₽' : '0 ₽';
        
        // Обновляем список выбранных мест
        updateSelectedSeatsList();
        
        // Обновляем скрытое поле и кнопку
        seatsInput.value = selectedSeatIds.join(',');
        confirmBtn.disabled = count === 0;
        
        // Показываем/скрываем кнопку очистки
        clearAllBtn.style.display = count > 0 ? 'inline-block' : 'none';
        
        // Обновляем счетчик
        selectedCount.textContent = count;
        
        // Анимируем обновление
        if (count > 0) {
            selectedSeatsInfo.classList.add('has-seats');
        } else {
            selectedSeatsInfo.classList.remove('has-seats');
        }
    }

    // Обновление списка выбранных мест
    function updateSelectedSeatsList() {
        if (selectedSeats.length === 0) {
            selectedSeatsList.innerHTML = '<span class="empty-message">Места не выбраны</span>';
            return;
        }
        
        let html = '';
        selectedSeats.forEach((seat, index) => {
            let parts = seat.split('-');
            html += `
                <div class="selected-seat-item" data-index="${index}">
                    <span class="seat-label">Ряд ${parts[0]}, Место ${parts[1]}</span>
                    <button class="remove-seat-btn" data-seat="${seat}" title="Убрать место">
                        ×
                    </button>
                </div>
            `;
        });
        selectedSeatsList.innerHTML = html;
        
        // Добавляем обработчики для кнопок удаления
        document.querySelectorAll('.remove-seat-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const seatLabel = this.dataset.seat;
                removeSeat(seatLabel);
            });
        });
    }

    // Функция удаления места
    function removeSeat(seatLabel) {
        const index = selectedSeats.indexOf(seatLabel);
        if (index !== -1) {
            selectedSeats.splice(index, 1);
            const seatId = selectedSeatIds[index];
            selectedSeatIds.splice(index, 1);
            
            // Находим кнопку места и снимаем выделение
            const seatButton = document.querySelector(`.seat[data-seat="${seatLabel}"]`);
            if (seatButton) {
                seatButton.classList.remove('selected');
                seatButton.classList.add('available');
            }
            
            updateOrderSummary();
        }
    }

    // Обработчик клика по месту
    seats.forEach(seat => {
        seat.addEventListener('click', function() {
            const seatLabel = this.dataset.seat;
            const seatDbId = this.dataset.id;
            
            // Проверяем, не занято ли место
            if (this.classList.contains('taken') || this.disabled) {
                return;
            }
            
            if (this.classList.contains('selected')) {
                // Если место уже выбрано - убираем его
                removeSeat(seatLabel);
            } else {
                // Добавляем место
                if (selectedSeats.length >= 6) {
                    alert('Максимум 6 билетов в одном заказе');
                    this.style.animation = 'shake 0.3s ease';
                    setTimeout(() => {
                        this.style.animation = '';
                    }, 300);
                    return;
                }
                
                // Убираем класс available и добавляем selected
                this.classList.remove('available');
                this.classList.add('selected');
                
                selectedSeats.push(seatLabel);
                selectedSeatIds.push(seatDbId);
                
                // Анимация добавления
                this.style.animation = 'popIn 0.3s ease';
                setTimeout(() => {
                    this.style.animation = '';
                }, 300);
                
                updateOrderSummary();
            }
        });
    });

    // Очистка всех мест
    clearAllBtn.addEventListener('click', function() {
        if (selectedSeats.length === 0) return;
        
        if (confirm('Очистить все выбранные места?')) {
            // Убираем выделение со всех мест
            document.querySelectorAll('.seat.selected').forEach(seat => {
                seat.classList.remove('selected');
                seat.classList.add('available');
            });
            
            selectedSeats = [];
            selectedSeatIds = [];
            updateOrderSummary();
        }
    });

    // Обработка отправки формы - переход на оплату
    bookingForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (selectedSeatIds.length === 0) {
            alert('Выберите хотя бы одно место');
            return;
        }
        
        // Сохраняем выбранные места в сессию
        fetch('save-seats.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                session_id: <?= $sessionId ?>,
                seats: selectedSeatIds
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'payment.php?session=<?= $sessionId ?>';
            } else {
                alert('Ошибка при сохранении выбранных мест');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Произошла ошибка. Попробуйте еще раз.');
        });
    });

    // Добавляем стили для анимаций
    const style = document.createElement('style');
    style.textContent = `
        @keyframes popIn {
            0% { transform: scale(0.8); opacity: 0.5; }
            100% { transform: scale(1); opacity: 1; }
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
    `;
    document.head.appendChild(style);

    // Инициализация
    updateOrderSummary();
});
</script>

<style>
/* Информация о выбранных местах */
.selected-seats-info {
    background: #121014;
    border: 1px solid #24202a;
    border-radius: 8px;
    padding: 15px 18px;
    margin-bottom: 20px;
    transition: border-color 0.3s;
}

.selected-seats-info.has-seats {
    border-color: #6247c1;
}

.info-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.info-label {
    color: #8c8792;
    font-size: 13px;
}

.info-count {
    background: #6247c1;
    color: #fff;
    padding: 2px 12px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 700;
}

.selected-seats-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    min-height: 32px;
    align-items: center;
}

.selected-seat-item {
    display: flex;
    align-items: center;
    gap: 6px;
    background: rgba(98, 71, 193, 0.15);
    border: 1px solid rgba(98, 71, 193, 0.3);
    border-radius: 4px;
    padding: 4px 8px 4px 12px;
    font-size: 12px;
    color: #f1f0f4;
    animation: popIn 0.3s ease;
}

.selected-seat-item .seat-label {
    font-size: 12px;
}

.remove-seat-btn {
    background: none;
    border: none;
    color: #a03a3a;
    font-size: 16px;
    cursor: pointer;
    padding: 0 2px;
    line-height: 1;
    transition: transform 0.2s;
}

.remove-seat-btn:hover {
    transform: scale(1.3);
    color: #ff4444;
}

.empty-message {
    color: #55515a;
    font-size: 13px;
}

.clear-all-btn {
    margin-top: 10px;
    font-size: 12px;
}

/* Стили для мест */
.seat-row {
    display: flex;
    align-items: center;
    margin-bottom: 6px;
}

.row-label {
    width: 60px;
    font-size: 12px;
    color: #77727e;
    text-align: right;
    padding-right: 12px;
    flex-shrink: 0;
}

.seat-group {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.seat {
    width: 36px;
    height: 36px;
    border: 2px solid #2a2632;
    border-radius: 4px 4px 8px 8px;
    background: transparent;
    color: #8c8792;
    font-family: 'Pixelify Sans', 'Courier New', monospace !important;
    font-size: 11px;
    cursor: pointer;
    transition: all 0.15s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

/* Стиль для свободных мест */
.seat.available {
    border-color: #3a3545;
    color: #aaa6af;
}

.seat.available:hover {
    border-color: #6247c1;
    background: rgba(98, 71, 193, 0.2);
    transform: scale(1.05);
}

/* Стиль для выбранных мест - ФИОЛЕТОВЫЙ */
.seat.selected {
    background: #6247c1 !important;
    border-color: #6247c1 !important;
    color: #fff !important;
    transform: scale(1.05);
    box-shadow: 0 0 20px rgba(98, 71, 193, 0.4);
}

.seat.selected::after {
    content: '✓';
    position: absolute;
    top: -6px;
    right: -6px;
    background: #6fcb8a;
    color: #fff;
    font-size: 10px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Стиль для занятых мест */
.seat.taken {
    border-color: #2a1a1a;
    background: #1a0f0f;
    color: #3a2a2a;
    cursor: not-allowed;
    opacity: 0.4;
}

.seat.taken::after {
    content: '✕';
    position: absolute;
    color: #5a3a3a;
    font-size: 14px;
}

.seat:disabled {
    cursor: not-allowed;
}

/* Легенда */
.seats-legend {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-top: 20px;
    font-size: 11px;
    color: #77727e;
}

.legend-dot {
    display: inline-block;
    width: 14px;
    height: 14px;
    border-radius: 3px;
    vertical-align: middle;
    margin-right: 5px;
}

.legend-dot.available {
    border: 1px solid #2a2632;
    background: transparent;
}

.legend-dot.selected {
    background: #6247c1;
}

.legend-dot.taken {
    background: #1a0f0f;
    border: 1px solid #2a1a1a;
}

/* Адаптив */
@media (max-width: 768px) {
    .seat {
        width: 30px;
        height: 30px;
        font-size: 9px;
    }
    
    .row-label {
        width: 45px;
        font-size: 10px;
    }
    
    .seat-group {
        gap: 4px;
    }
    
    .selected-seat-item {
        font-size: 11px;
        padding: 3px 6px 3px 10px;
    }
}

@media (max-width: 480px) {
    .seat {
        width: 26px;
        height: 26px;
        font-size: 8px;
        border-width: 1px;
    }
    
    .row-label {
        width: 35px;
        font-size: 9px;
        padding-right: 6px;
    }
    
    .seat-group {
        gap: 3px;
    }
    
    .selected-seats-info {
        padding: 12px 14px;
    }
    
    .selected-seat-item {
        font-size: 10px;
        padding: 2px 4px 2px 8px;
    }
    
    .remove-seat-btn {
        font-size: 14px;
    }
}
</style>

<?php require __DIR__ . '/includes/footer.php'; ?>