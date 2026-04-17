CREATE TABLE IF NOT EXISTS participants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_participants_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS matches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    external_id VARCHAR(64) NULL,
    stage VARCHAR(64) NOT NULL,
    match_date DATETIME NOT NULL,
    home_team VARCHAR(120) NOT NULL,
    away_team VARCHAR(120) NOT NULL,
    home_score TINYINT UNSIGNED NULL,
    away_score TINYINT UNSIGNED NULL,
    status ENUM('scheduled','finished') NOT NULL DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_matches_external_id (external_id),
    KEY idx_matches_match_date (match_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS predictions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    participant_id INT UNSIGNED NOT NULL,
    match_id INT UNSIGNED NOT NULL,
    predicted_home_score TINYINT UNSIGNED NOT NULL,
    predicted_away_score TINYINT UNSIGNED NOT NULL,
    points INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_predictions_participant_match (participant_id, match_id),
    CONSTRAINT fk_predictions_participant FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE,
    CONSTRAINT fk_predictions_match FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
