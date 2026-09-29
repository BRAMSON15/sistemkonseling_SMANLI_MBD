<?php
/**
 * Header Template
 * Sistem Konseling SMA Negeri 5 Maluku Barat Daya
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

$user = auth_user();
$page_title = $page_title ?? 'Sistem Konseling SMA Negeri 5 MBD';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - SMAN 5 MBD</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/custom.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body>
<div class="app-wrapper">
    <?php if (is_logged_in()): ?>
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="d-flex flex-column flex-grow-1" style="min-width: 0;">
            <?php include __DIR__ . '/navbar.php'; ?>
            <main class="main-content">
                <?= render_flash() ?>
    <?php else: ?>
        <div class="d-flex flex-column flex-grow-1">
            <main class="main-content p-0">
                <?= render_flash() ?>
    <?php endif; ?>
