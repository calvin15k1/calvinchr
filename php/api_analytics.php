<?php
// API: Get analytics data for admin dashboard
// File: php/api_analytics.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
requireLogin('../admin/login.php');

$db = getDB();

// Ensure table exists
$db->exec("CREATE TABLE IF NOT EXISTS analytics (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    page        VARCHAR(255) NOT NULL DEFAULT '/',
    referrer    VARCHAR(500),
    user_agent  VARCHAR(500),
    ip_hash     VARCHAR(64),
    visited_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_page (page),
    INDEX idx_visited (visited_at)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

// ── Total visits ───────────────────────────
$total = $db->query("SELECT COUNT(*) FROM analytics")->fetchColumn();

// ── Visits today ──────────────────────────
$today = $db->query("SELECT COUNT(*) FROM analytics WHERE DATE(visited_at) = CURDATE()")->fetchColumn();

// ── Visits this week ──────────────────────
$week = $db->query("SELECT COUNT(*) FROM analytics WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();

// ── Visits this month ─────────────────────
$month = $db->query("SELECT COUNT(*) FROM analytics WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

// ── Unique visitors today (by ip_hash) ───
$uniqueToday = $db->query("SELECT COUNT(DISTINCT ip_hash) FROM analytics WHERE DATE(visited_at) = CURDATE()")->fetchColumn();

// ── Unique visitors this week ─────────────
$uniqueWeek = $db->query("SELECT COUNT(DISTINCT ip_hash) FROM analytics WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();

// ── Daily visits — last 30 days ───────────
$dailyStmt = $db->query("
    SELECT DATE(visited_at) AS day, COUNT(*) AS visits, COUNT(DISTINCT ip_hash) AS unique_visits
    FROM analytics
    WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(visited_at)
    ORDER BY day ASC
");
$daily = $dailyStmt->fetchAll();

// ── Hourly visits today ───────────────────
$hourlyStmt = $db->query("
    SELECT HOUR(visited_at) AS hour, COUNT(*) AS visits
    FROM analytics
    WHERE DATE(visited_at) = CURDATE()
    GROUP BY HOUR(visited_at)
    ORDER BY hour ASC
");
$hourly = $hourlyStmt->fetchAll();

// ── Top pages ─────────────────────────────
$pagesStmt = $db->query("
    SELECT page, COUNT(*) AS visits
    FROM analytics
    GROUP BY page
    ORDER BY visits DESC
    LIMIT 10
");
$pages = $pagesStmt->fetchAll();

// ── Peak hour ─────────────────────────────
$peakStmt = $db->query("
    SELECT HOUR(visited_at) AS hour, COUNT(*) AS visits
    FROM analytics
    GROUP BY HOUR(visited_at)
    ORDER BY visits DESC
    LIMIT 1
");
$peak = $peakStmt->fetch();

// ── Booking page visits ───────────────────
$bookingVisits = $db->query("SELECT COUNT(*) FROM analytics WHERE page LIKE '%booking%'")->fetchColumn();

jsonResponse([
    'success'      => true,
    'total'        => (int)$total,
    'today'        => (int)$today,
    'week'         => (int)$week,
    'month'        => (int)$month,
    'unique_today' => (int)$uniqueToday,
    'unique_week'  => (int)$uniqueWeek,
    'booking_visits' => (int)$bookingVisits,
    'peak_hour'    => $peak ? (int)$peak['hour'] : null,
    'daily'        => $daily,
    'hourly'       => $hourly,
    'top_pages'    => $pages,
]);
?>
