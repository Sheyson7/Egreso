CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(40) NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reservations (
    id CHAR(36) NOT NULL PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    court_id TINYINT UNSIGNED NOT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    total_amount INT UNSIGNED NOT NULL,
    deposit_amount INT UNSIGNED NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'UYU',
    status ENUM('pending_payment', 'confirmed', 'payment_failed', 'expired', 'cancelled') NOT NULL,
    payment_preference_id VARCHAR(100) NULL,
    payment_id VARCHAR(100) NULL,
    expires_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reservations_user FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_reservations_user (user_id, created_at),
    INDEX idx_reservations_expiration (status, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reservation_slots (
    court_id TINYINT UNSIGNED NOT NULL,
    slot_start DATETIME NOT NULL,
    reservation_id CHAR(36) NOT NULL,
    PRIMARY KEY (court_id, slot_start),
    INDEX idx_reservation_slots_reservation (reservation_id),
    CONSTRAINT fk_reservation_slots_reservation FOREIGN KEY (reservation_id)
        REFERENCES reservations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
