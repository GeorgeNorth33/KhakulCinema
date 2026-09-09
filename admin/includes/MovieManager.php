<?php
// admin/includes/MovieManager.php

require_once __DIR__ . '/../../config/database.php';

class MovieManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getActiveMovies() {
        $stmt = $this->db->prepare("
            SELECT * FROM movies 
            WHERE is_active = 1 
            ORDER BY release_date DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getMovieById($id) {
        $stmt = $this->db->prepare("
            SELECT 
                m.*,
                m.poster_url,
                m.age_rating,
                m.title,
                m.genre,
                m.duration,
                m.description
            FROM movies m
            WHERE m.id = ? AND m.is_active = 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getSessionsForMovie($movieId, $date = null) {
        $sql = "
            SELECT 
                s.*,
                h.name as hall_name,
                h.capacity,
                h.has_imax,
                h.has_3d,
                (
                    SELECT COUNT(*) 
                    FROM tickets t 
                    JOIN bookings b ON t.booking_id = b.id 
                    WHERE b.session_id = s.id AND b.status NOT IN ('cancelled', 'expired')
                ) as booked_seats
            FROM sessions s
            JOIN halls h ON s.hall_id = h.id
            WHERE s.movie_id = ? AND s.is_active = 1
        ";
        
        if ($date) {
            $sql .= " AND DATE(s.session_time) = ?";
            $params = [$movieId, $date];
        } else {
            $sql .= " AND s.session_time >= NOW()";
            $params = [$movieId];
        }
        
        $sql .= " ORDER BY s.session_time";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getAvailableSeats($sessionId) {
        $stmt = $this->db->prepare("
            SELECT 
                se.*,
                CASE 
                    WHEN t.id IS NOT NULL AND b.status NOT IN ('cancelled', 'expired') THEN 1 
                    ELSE 0 
                END as is_taken
            FROM seats se
            JOIN halls h ON se.hall_id = h.id
            JOIN sessions s ON s.hall_id = h.id
            LEFT JOIN tickets t ON t.seat_id = se.id
            LEFT JOIN bookings b ON t.booking_id = b.id AND b.session_id = ? AND b.status NOT IN ('cancelled', 'expired')
            WHERE s.id = ? AND se.is_active = 1
            ORDER BY se.row_number, se.seat_number
        ");
        $stmt->execute([$sessionId, $sessionId]);
        return $stmt->fetchAll();
    }
    
    public function getSessionById($id) {
        $stmt = $this->db->prepare("
            SELECT 
                s.*,
                m.title as movie_title,
                m.age_rating,
                m.duration,
                m.poster_url,
                m.description,
                h.name as hall_name,
                h.capacity
            FROM sessions s
            JOIN movies m ON s.movie_id = m.id
            JOIN halls h ON s.hall_id = h.id
            WHERE s.id = ? AND s.is_active = 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getMoviesByDate($date) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT 
                m.*
            FROM movies m
            JOIN sessions s ON m.id = s.movie_id
            WHERE DATE(s.session_time) = ? 
                AND m.is_active = 1 
                AND s.is_active = 1
            ORDER BY m.title
        ");
        $stmt->execute([$date]);
        return $stmt->fetchAll();
    }
    
    public function getNews() {
        $stmt = $this->db->prepare("
            SELECT * FROM news 
            WHERE is_published = 1 
            ORDER BY date_published DESC 
            LIMIT 6
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}