-- CodingCush Games: tables for accounts, cloud saves and leaderboards (MySQL / MariaDB).
-- Run once in phpMyAdmin. Every table starts with games_ so nothing clashes with other tables.
CREATE TABLE IF NOT EXISTS games_users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  pass_hash VARCHAR(255) NOT NULL,
  name VARCHAR(12) NOT NULL DEFAULT 'PILOT',
  created_at INT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS games_sessions (
  token_hash CHAR(64) NOT NULL PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  expires_at INT UNSIGNED NOT NULL,
  KEY games_sessions_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS games_saves (
  user_id INT UNSIGNED NOT NULL,
  game VARCHAR(32) NOT NULL,
  data MEDIUMTEXT NOT NULL,
  updated_at INT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, game)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS games_scores (
  user_id INT UNSIGNED NOT NULL,
  game VARCHAR(32) NOT NULL,
  level TINYINT UNSIGNED NOT NULL,
  name VARCHAR(12) NOT NULL,
  ship VARCHAR(16) NOT NULL DEFAULT '',
  score INT UNSIGNED NOT NULL,
  updated_at INT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, game, level),
  KEY games_scores_board (game, level, score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS games_attempts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  address VARCHAR(45) NOT NULL,
  made_at INT UNSIGNED NOT NULL,
  KEY games_attempts_address (address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
