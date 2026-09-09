document.addEventListener("DOMContentLoaded", () => {
    const viewport = document.getElementById("movieViewport");
    const track = document.getElementById("movieTrack");
    const prev = document.querySelector(".carousel-prev");
    const next = document.querySelector(".carousel-next");
    const progress = document.getElementById("movieProgress");

    if (!viewport || !track) return;

    let index = 0;

    function visibleCount() {
        const width = window.innerWidth;
        if (width <= 560) return 2;
        if (width <= 800) return 3;
        if (width <= 1100) return 4;
        return 5;
    }

    function maxIndex() {
        return Math.max(0, track.children.length - visibleCount());
    }

    function updateCarousel() {
        const cards = [...track.children];
        if (!cards.length) return;

        const gap = parseFloat(getComputedStyle(track).gap) || 0;
        const cardWidth = cards[0].getBoundingClientRect().width;
        const offset = index * (cardWidth + gap);

        track.style.transform = `translateX(-${offset}px)`;

        const max = maxIndex();
        const percentage = max === 0 ? 100 : ((index + visibleCount()) / track.children.length) * 100;
        progress.style.width = `${Math.min(100, Math.max(20, percentage))}%`;

        prev.disabled = index === 0;
        next.disabled = index >= max;
        prev.style.opacity = prev.disabled ? ".35" : "1";
        next.style.opacity = next.disabled ? ".35" : "1";
    }

    prev.addEventListener("click", () => {
        index = Math.max(0, index - 1);
        updateCarousel();
    });

    next.addEventListener("click", () => {
        index = Math.min(maxIndex(), index + 1);
        updateCarousel();
    });

    window.addEventListener("resize", () => {
        index = Math.min(index, maxIndex());
        updateCarousel();
    });

    // Touch / swipe support.
    let startX = 0;
    viewport.addEventListener("touchstart", e => {
        startX = e.touches[0].clientX;
    }, { passive: true });

    viewport.addEventListener("touchend", e => {
        const diff = startX - e.changedTouches[0].clientX;
        if (Math.abs(diff) < 40) return;
        if (diff > 0) index = Math.min(maxIndex(), index + 1);
        else index = Math.max(0, index - 1);
        updateCarousel();
    }, { passive: true });

    document.querySelectorAll(".filter-button").forEach(button => {
        button.addEventListener("click", () => {
            document.querySelectorAll(".filter-button").forEach(b => b.classList.remove("active"));
            button.classList.add("active");
        });
    });

    updateCarousel();
});

// ===== ВКЛАДКИ ПРОФИЛЯ =====
document.addEventListener("DOMContentLoaded", () => {
    // Переключение вкладок в профиле
    const navLinks = document.querySelectorAll('.profile-nav a[data-tab]');
    
    // Проверяем, что мы на странице профиля
    if (navLinks.length > 0) {
        const panels = {
            bookings: document.getElementById('tab-bookings'),
            settings: document.getElementById('tab-settings'),
            history: document.getElementById('tab-history')
        };

        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const tab = link.dataset.tab;
                
                // Убираем активные классы
                navLinks.forEach(l => l.classList.remove('active'));
                link.classList.add('active');
                
                // Показываем нужную панель
                Object.keys(panels).forEach(key => {
                    if (panels[key]) {
                        panels[key].classList.toggle('active', key === tab);
                    }
                });
            });
        });
    }

    // ===== ВЫБОР МЕСТ (только на странице ticket-booking) =====
    const seats = document.querySelectorAll('.seat.available');
    if (seats.length > 0) {
        const selectedSeatsDisplay = document.getElementById('selectedSeats');
        const ticketCountDisplay = document.getElementById('ticketCount');
        const totalPriceDisplay = document.getElementById('totalPrice');
        const pricePerTicket = 450;
        let selectedSeats = [];

        seats.forEach(seat => {
            seat.addEventListener('click', () => {
                const seatId = seat.dataset.seat;
                
                if (seat.classList.contains('selected')) {
                    seat.classList.remove('selected');
                    selectedSeats = selectedSeats.filter(s => s !== seatId);
                } else {
                    if (selectedSeats.length >= 6) {
                        alert('Максимум 6 билетов в одном заказе');
                        return;
                    }
                    seat.classList.add('selected');
                    selectedSeats.push(seatId);
                }
                
                updateOrderSummary();
            });
        });

        function updateOrderSummary() {
            const count = selectedSeats.length;
            const total = count * pricePerTicket;
            
            if (selectedSeatsDisplay) {
                selectedSeatsDisplay.textContent = selectedSeats.join(', ') || 'Не выбрано';
            }
            if (ticketCountDisplay) {
                ticketCountDisplay.textContent = count;
            }
            if (totalPriceDisplay) {
                totalPriceDisplay.textContent = total ? `${total} ₽` : '0 ₽';
            }
        }

        // Кнопка оформления
        const confirmBtn = document.getElementById('confirmBooking');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => {
                if (selectedSeats.length === 0) {
                    alert('Выберите хотя бы одно место');
                    return;
                }
                alert(`Билеты на места ${selectedSeats.join(', ')} успешно забронированы!`);
            });
        }
    }

    // ===== ФИЛЬТРЫ ДАТ (только на странице session-select) =====
    const dateBtns = document.querySelectorAll('.date-btn');
    dateBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            dateBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });

    // ===== ПРОМОКОД (только на странице ticket-booking) =====
    const promoInput = document.querySelector('.order-promo .form-control');
    const promoBtn = document.querySelector('.order-promo .btn');
    if (promoBtn && promoInput) {
        promoBtn.addEventListener('click', () => {
            const code = promoInput.value.trim();
            if (code === 'CINEMA2026') {
                alert('Промокод применён! Скидка 10%');
            } else if (code) {
                alert('Неверный промокод');
            }
        });
    }
});