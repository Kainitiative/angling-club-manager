CREATE TABLE club_news (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  club_id bigint unsigned NOT NULL,
  author_id bigint unsigned NOT NULL,
  title varchar(255) NOT NULL,
  content text NOT NULL,
  is_pinned tinyint(1) NOT NULL DEFAULT 0,
  published_at datetime NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_club_news_club (club_id),
  KEY idx_club_news_published (club_id, published_at),
  CONSTRAINT fk_club_news_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_club_news_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
