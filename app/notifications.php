<?php

function create_notification(PDO $pdo, int $userId, string $type, string $title, ?string $message = null, ?string $link = null, ?int $clubId = null): void {
  $stmt = $pdo->prepare("INSERT INTO notifications (user_id, club_id, type, title, message, link) VALUES (?, ?, ?, ?, ?, ?)");
  $stmt->execute([$userId, $clubId, $type, $title, $message, $link]);
}

function notify_membership_approved(PDO $pdo, int $userId, int $clubId, string $clubName, string $clubSlug): void {
  create_notification(
    $pdo,
    $userId,
    'membership_approved',
    'Membership Approved',
    "Your membership request for {$clubName} has been approved. Welcome to the club!",
    "/public/club.php?slug={$clubSlug}",
    $clubId
  );
}

function notify_membership_rejected(PDO $pdo, int $userId, int $clubId, string $clubName): void {
  create_notification(
    $pdo,
    $userId,
    'membership_rejected',
    'Membership Request Declined',
    "Your membership request for {$clubName} was not approved at this time.",
    null,
    $clubId
  );
}

function notify_new_news(PDO $pdo, int $clubId, string $clubName, string $clubSlug, string $newsTitle): void {
  global $pdo;
  $stmt = $pdo->prepare("
    SELECT user_id FROM club_members WHERE club_id = ? AND membership_status = 'active'
    UNION
    SELECT user_id FROM club_admins WHERE club_id = ?
  ");
  $stmt->execute([$clubId, $clubId]);
  $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
  
  foreach ($userIds as $userId) {
    create_notification(
      $pdo,
      (int)$userId,
      'new_news',
      "New Club News: {$newsTitle}",
      "{$clubName} has posted new news.",
      "/public/club.php?slug={$clubSlug}",
      $clubId
    );
  }
}

function notify_catch_of_month(PDO $pdo, int $userId, int $clubId, string $clubName, string $clubSlug): void {
  create_notification(
    $pdo,
    $userId,
    'catch_of_month',
    'Catch of the Month!',
    "Congratulations! Your catch has been selected as {$clubName}'s Catch of the Month!",
    "/public/catches.php?slug={$clubSlug}",
    $clubId
  );
}

function notify_competition_results(PDO $pdo, int $clubId, string $clubName, string $competitionTitle, int $competitionId): void {
  global $pdo;
  $stmt = $pdo->prepare("
    SELECT user_id FROM club_members WHERE club_id = ? AND membership_status = 'active'
    UNION
    SELECT user_id FROM club_admins WHERE club_id = ?
  ");
  $stmt->execute([$clubId, $clubId]);
  $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
  
  foreach ($userIds as $userId) {
    create_notification(
      $pdo,
      (int)$userId,
      'competition_results',
      "Results Posted: {$competitionTitle}",
      "Competition results for {$competitionTitle} are now available.",
      "/public/competition_results.php?id={$competitionId}",
      $clubId
    );
  }
}

function get_unread_notification_count(PDO $pdo, int $userId): int {
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
  $stmt->execute([$userId]);
  return (int)$stmt->fetchColumn();
}

function get_unread_message_count(PDO $pdo, int $userId): int {
  $stmt = $pdo->prepare("
    SELECT COUNT(*) FROM messages 
    WHERE recipient_id = ? AND is_announcement = 0 AND is_read = 0
  ");
  $stmt->execute([$userId]);
  return (int)$stmt->fetchColumn();
}
