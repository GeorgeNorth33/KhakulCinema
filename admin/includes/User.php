<?php
// admin/includes/User.php

require_once __DIR__ . '/../../config/database.php';

class User {
    private $db;
    private $id;
    private $data;
    
    public function __construct($userId = null) {
        $this->db = Database::getInstance();
        if ($userId) {
            $this->loadUser($userId);
        }
    }
    
    public function loadUser($userId) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $this->data = $stmt->fetch();
        if ($this->data) {
            $this->id = $userId;
        }
        return $this->data;
    }
    
    public function login($email, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && $user['password'] === $password) {
            $this->data = $user;
            $this->id = $user['id'];
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            
            $this->logActivity('login');
            
            return true;
        }
        return false;
    }
    
    public function register($data) {
        $stmt = $this->db->prepare("
            INSERT INTO users (full_name, email, phone, password) 
            VALUES (?, ?, ?, ?)
        ");
        
        try {
            $result = $stmt->execute([
                $data['full_name'],
                $data['email'],
                $data['phone'] ?? null,
                $data['password']
            ]);
            
            if ($result) {
                $this->id = $this->db->lastInsertId();
                $this->loadUser($this->id);
                $this->logActivity('register');
                return true;
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }
        return false;
    }
    
    public function update($data) {
        $fields = [];
        $params = [];
        
        if (isset($data['full_name'])) {
            $fields[] = "full_name = ?";
            $params[] = $data['full_name'];
        }
        if (isset($data['phone'])) {
            $fields[] = "phone = ?";
            $params[] = $data['phone'];
        }
        if (isset($data['password']) && !empty($data['password'])) {
            $fields[] = "password = ?";
            $params[] = $data['password'];
        }
        
        if (empty($fields)) return false;
        
        $params[] = $this->id;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);
        
        if ($result) {
            $this->loadUser($this->id);
            $this->logActivity('update_profile');
        }
        return $result;
    }
    
    public function getBookings() {
        $stmt = $this->db->prepare("
            SELECT 
                b.*,
                m.title as movie_title,
                m.age_rating,
                m.genre,
                m.duration,
                m.poster_url,
                s.session_time,
                h.name as hall_name,
                GROUP_CONCAT(
                    CONCAT('Ряд ', se.row_number, ' Место ', se.seat_number)
                    ORDER BY se.id
                ) as seats
            FROM bookings b
            JOIN sessions s ON b.session_id = s.id
            JOIN movies m ON s.movie_id = m.id
            JOIN halls h ON s.hall_id = h.id
            JOIN tickets t ON b.id = t.booking_id
            JOIN seats se ON t.seat_id = se.id
            WHERE b.user_id = ?
            GROUP BY b.id
            ORDER BY b.created_at DESC
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll();
    }
    
    public function getHistory() {
        $stmt = $this->db->prepare("
            SELECT 
                m.title as movie_title,
                s.session_time,
                b.status,
                b.created_at as booking_date,
                t.used,
                t.used_at
            FROM bookings b
            JOIN sessions s ON b.session_id = s.id
            JOIN movies m ON s.movie_id = m.id
            JOIN tickets t ON b.id = t.booking_id
            WHERE b.user_id = ? AND b.status = 'confirmed'
            ORDER BY s.session_time DESC
            LIMIT 20
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll();
    }
    
    public function logActivity($action, $ip = null, $userAgent = null) {
        $stmt = $this->db->prepare("
            INSERT INTO user_activity (user_id, action, ip_address, user_agent) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([
            $this->id,
            $action,
            $ip ?? $_SERVER['REMOTE_ADDR'] ?? null,
            $userAgent ?? $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && $this->id == $_SESSION['user_id'];
    }
    
    public function logout() {
        $this->logActivity('logout');
        session_destroy();
        $this->id = null;
        $this->data = null;
    }
    
    public function getData() {
        return $this->data;
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function isAdmin() {
        return isset($this->data['role']) && $this->data['role'] === 'admin';
    }
}