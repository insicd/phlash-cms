-- Phlash CMS — schema MySQL/MariaDB (utf8mb4)
-- Eseguito automaticamente da install.php

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(32) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','user') NOT NULL DEFAULT 'user',
  karma INT NOT NULL DEFAULT 0,
  bio TEXT NULL,
  status ENUM('active','banned') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL,
  last_login DATETIME NULL,
  UNIQUE KEY uq_username (username),
  UNIQUE KEY uq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS topics (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(80) NOT NULL,
  description VARCHAR(255) NOT NULL DEFAULT '',
  icon VARCHAR(64) NOT NULL DEFAULT '',
  sort_order INT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_topic_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  topic_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  slug VARCHAR(220) NOT NULL,
  dept VARCHAR(80) NOT NULL DEFAULT '',
  body MEDIUMTEXT NOT NULL,
  source_url VARCHAR(500) NULL,
  status ENUM('pending','published','rejected') NOT NULL DEFAULT 'pending',
  score INT NOT NULL DEFAULT 0,
  comment_count INT UNSIGNED NOT NULL DEFAULT 0,
  views INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  published_at DATETIME NULL,
  UNIQUE KEY uq_story_slug (slug),
  KEY idx_status_pub (status, published_at),
  KEY idx_topic_status (topic_id, status),
  CONSTRAINT fk_stories_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_stories_topic FOREIGN KEY (topic_id) REFERENCES topics(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  story_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NULL,
  parent_id INT UNSIGNED NULL,
  author_name VARCHAR(40) NOT NULL DEFAULT '',
  body TEXT NOT NULL,
  score INT NOT NULL DEFAULT 1,
  ip_hash CHAR(64) NOT NULL DEFAULT '',
  status ENUM('visible','hidden') NOT NULL DEFAULT 'visible',
  created_at DATETIME NOT NULL,
  KEY idx_story_status (story_id, status),
  CONSTRAINT fk_comments_story FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
  CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_comments_parent FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS votes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  target_type ENUM('story','comment') NOT NULL,
  target_id INT UNSIGNED NOT NULL,
  value TINYINT NOT NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_vote (user_id, target_type, target_id),
  CONSTRAINT fk_votes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tags (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(40) NOT NULL,
  slug VARCHAR(40) NOT NULL,
  UNIQUE KEY uq_tag_name (name),
  UNIQUE KEY uq_tag_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS story_tags (
  story_id INT UNSIGNED NOT NULL,
  tag_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (story_id, tag_id),
  CONSTRAINT fk_st_story FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
  CONSTRAINT fk_st_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS polls (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  question VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS poll_options (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  poll_id INT UNSIGNED NOT NULL,
  label VARCHAR(120) NOT NULL,
  votes INT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_po_poll FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS poll_votes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  poll_id INT UNSIGNED NOT NULL,
  option_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NULL,
  ip_hash CHAR(64) NOT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_poll_user (poll_id, user_id),
  KEY idx_poll_ip (poll_id, ip_hash),
  CONSTRAINT fk_pv_poll FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  k VARCHAR(64) PRIMARY KEY,
  v TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
