<?php
// includes/BookingManager.php

require_once __DIR__ . '/../config/database.php';

class BookingManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Создание бронирования
     */
    public function createBooking($userId, $sessionId, $seatIds, $promoCode = null) {
        try {
            $this->db->beginTransaction();
            
            $session = $this->getSessionInfo($sessionId);
            if (!$session) {
                throw new Exception("Сеанс не найден");
            }
            
            $availableSeats = $this->checkSeatsAvailability($sessionId, $seatIds);
            if (count($availableSeats) !== count($seatIds)) {
                throw new Exception("Некоторые места уже заняты");
            }
            
            $pricePerSeat = $session['price'];
            $totalAmount = $pricePerSeat * count($seatIds);
            
            $discount = 0;
            if ($promoCode) {
                $promo = $this->validatePromoCode($promoCode);
                if ($promo) {
                    $discount = ($totalAmount * $promo['discount_percent']) / 100;
                    $this->usePromoCode($promoCode);
                }
            }
            
            $finalAmount = $totalAmount - $discount;
            
            $bookingCode = $this->generateBookingCode();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            
            $stmt = $this->db->prepare("
                INSERT INTO bookings (user_id, session_id, booking_code, total_amount, expires_at, status) 
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$userId, $sessionId, $bookingCode, $finalAmount, $expiresAt]);
            $bookingId = $this->db->lastInsertId();
            
            foreach ($seatIds as $seatId) {
                $qrCode = $this->generateQRCode($bookingId, $seatId);
                $stmt = $this->db->prepare("
                    INSERT INTO tickets (booking_id, seat_id, price, qr_code) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$bookingId, $seatId, $pricePerSeat, $qrCode]);
            }
            
            $this->db->commit();
            
            return [
                'booking_id' => $bookingId,
                'booking_code' => $bookingCode,
                'total_amount' => $finalAmount,
                'discount' => $discount,
                'expires_at' => $expiresAt
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Подтверждение бронирования
     */
    public function confirmBooking($bookingId) {
        $stmt = $this->db->prepare("
            UPDATE bookings 
            SET status = 'confirmed' 
            WHERE id = ? AND status = 'pending' AND expires_at > NOW()
        ");
        return $stmt->execute([$bookingId]);
    }
    
    /**
     * Отмена бронирования
     */
    public function cancelBooking($bookingId, $userId) {
        $stmt = $this->db->prepare("
            UPDATE bookings 
            SET status = 'cancelled' 
            WHERE id = ? AND user_id = ? AND status IN ('pending', 'confirmed')
        ");
        return $stmt->execute([$bookingId, $userId]);
    }
    
    /**
     * Получение бронирования по ID
     */
    public function getBookingById($bookingId) {
        $stmt = $this->db->prepare("
            SELECT 
                b.*,
                m.title as movie_title,
                s.session_time,
                h.name as hall_name,
                u.full_name as user_name,
                u.email as user_email
            FROM bookings b
            JOIN sessions s ON b.session_id = s.id
            JOIN movies m ON s.movie_id = m.id
            JOIN halls h ON s.hall_id = h.id
            JOIN users u ON b.user_id = u.id
            WHERE b.id = ?
        ");
        $stmt->execute([$bookingId]);
        return $stmt->fetch();
    }
    
    /**
     * Получение билетов для бронирования
     */
    public function getTicketsForBooking($bookingId) {
        $stmt = $this->db->prepare("
            SELECT 
                t.*,
                se.row_number,
                se.seat_number,
                se.seat_type
            FROM tickets t
            JOIN seats se ON t.seat_id = se.id
            WHERE t.booking_id = ?
        ");
        $stmt->execute([$bookingId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Получить все билеты пользователя (ИСПРАВЛЕННАЯ ВЕРСИЯ)
     */
    public function getUserTickets($userId, $limit = null) {
        $sql = "
            SELECT 
                b.id as booking_id,
                b.booking_code,
                b.status,
                b.total_amount,
                b.created_at as booking_date,
                s.session_time,
                m.id as movie_id,
                m.title as movie_title,
                m.age_rating,
                m.genre,
                m.duration,
                m.poster_url,
                h.name as hall_name,
                GROUP_CONCAT(
                    CONCAT('Ряд ', se.row_number, ' Место ', se.seat_number)
                    ORDER BY se.id
                    SEPARATOR ', '
                ) as seats,
                COUNT(t.id) as tickets_count,
                MAX(t.used) as used,
                MAX(t.used_at) as used_at
            FROM bookings b
            JOIN sessions s ON b.session_id = s.id
            JOIN movies m ON s.movie_id = m.id
            JOIN halls h ON s.hall_id = h.id
            JOIN tickets t ON b.id = t.booking_id
            JOIN seats se ON t.seat_id = se.id
            WHERE b.user_id = ?
            GROUP BY b.id, b.booking_code, b.status, b.total_amount, b.created_at, 
                     s.session_time, m.id, m.title, m.age_rating, m.genre, m.duration, 
                     m.poster_url, h.name
            ORDER BY b.created_at DESC
        ";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Получить детали конкретного билета
     */
    public function getTicketDetails($bookingId, $userId) {
        $stmt = $this->db->prepare("
            SELECT 
                b.id as booking_id,
                b.booking_code,
                b.status,
                b.total_amount,
                b.created_at as booking_date,
                b.expires_at,
                s.session_time,
                m.id as movie_id,
                m.title as movie_title,
                m.age_rating,
                m.genre,
                m.duration,
                m.poster_url,
                m.description,
                h.name as hall_name,
                h.has_imax,
                h.has_3d,
                t.id as ticket_id,
                t.qr_code,
                t.used,
                t.used_at,
                se.row_number,
                se.seat_number,
                se.seat_type
            FROM bookings b
            JOIN sessions s ON b.session_id = s.id
            JOIN movies m ON s.movie_id = m.id
            JOIN halls h ON s.hall_id = h.id
            JOIN tickets t ON b.id = t.booking_id
            JOIN seats se ON t.seat_id = se.id
            WHERE b.id = ? AND b.user_id = ?
        ");
        $stmt->execute([$bookingId, $userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Получение информации о сеансе
     */
    private function getSessionInfo($sessionId) {
        $stmt = $this->db->prepare("
            SELECT * FROM sessions WHERE id = ? AND is_active = 1
        ");
        $stmt->execute([$sessionId]);
        return $stmt->fetch();
    }
    
    /**
     * Проверка доступности мест
     */
    private function checkSeatsAvailability($sessionId, $seatIds) {
        if (empty($seatIds)) return [];
        
        $placeholders = implode(',', array_fill(0, count($seatIds), '?'));
        $params = array_merge([$sessionId], $seatIds);
        
        $stmt = $this->db->prepare("
            SELECT se.id
            FROM seats se
            LEFT JOIN tickets t ON t.seat_id = se.id
            LEFT JOIN bookings b ON t.booking_id = b.id AND b.session_id = ? AND b.status NOT IN ('cancelled', 'expired')
            WHERE se.id IN ($placeholders) AND (t.id IS NULL OR b.status IN ('cancelled', 'expired'))
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Генерация кода бронирования
     */
    private function generateBookingCode() {
        $prefix = 'BK';
        $year = date('Y');
        $random = strtoupper(substr(uniqid(), -6));
        return "{$prefix}-{$year}-{$random}";
    }
    
    /**
     * Генерация QR-кода
     */
    private function generateQRCode($bookingId, $seatId) {
        return 'QR-' . $bookingId . '-' . $seatId . '-' . uniqid();
    }
    
    /**
     * Проверка валидности промокода
     */
    public function validatePromoCode($code) {
        $stmt = $this->db->prepare("
            SELECT * FROM promo_codes 
            WHERE code = ? 
                AND is_active = 1 
                AND valid_from <= NOW() 
                AND valid_to >= NOW()
                AND (usage_limit IS NULL OR used_count < usage_limit)
        ");
        $stmt->execute([$code]);
        return $stmt->fetch();
    }
    
    /**
     * Использование промокода (увеличение счетчика)
     */
    private function usePromoCode($code) {
        $stmt = $this->db->prepare("
            UPDATE promo_codes 
            SET used_count = used_count + 1 
            WHERE code = ?
        ");
        return $stmt->execute([$code]);
    }
}