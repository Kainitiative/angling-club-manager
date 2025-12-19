CREATE TABLE notifications (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  user_id bigint unsigned NOT NULL,
  club_id bigint unsigned NULL,
  type varchar(50) NOT NULL,
  title varchar(255) NOT NULL,
  message text NULL,
  link varchar(500) NULL,
  is_read tinyint(1) NOT NULL DEFAULT 0,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_notifications_user (user_id, is_read, created_at),
  KEY idx_notifications_club (club_id),
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_notifications_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE messages (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  club_id bigint unsigned NOT NULL,
  sender_id bigint unsigned NOT NULL,
  recipient_id bigint unsigned NULL,
  subject varchar(255) NOT NULL,
  body text NOT NULL,
  is_announcement tinyint(1) NOT NULL DEFAULT 0,
  is_read tinyint(1) NOT NULL DEFAULT 0,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_messages_recipient (recipient_id, is_read, created_at),
  KEY idx_messages_club (club_id),
  KEY idx_messages_sender (sender_id),
  CONSTRAINT fk_messages_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_messages_recipient FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
