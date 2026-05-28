<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d6efd">
    <title><?= htmlspecialchars($pageTitle ?? 'Cohort Monitor') ?> — Cohort Monitor</title>

    <!-- Bootstrap 5 CSS -->
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="/assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="/assets/vendor/sweetalert2/sweetalert2.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/assets/css/app.css" rel="stylesheet">
    <script src="/assets/js/density-init.js"></script>
    <?php foreach (($styles ?? []) as $href): ?>
        <link href="<?= htmlspecialchars($href) ?>" rel="stylesheet">
    <?php endforeach; ?>
</head>
<body class="bg-body-tertiary">
<a href="#page-content" class="skip-link">Saltar al contenido</a>
<div id="app-announcer" class="visually-hidden" aria-live="polite" aria-atomic="true"></div>

<div class="d-flex" id="app-wrapper">
    <!-- Sidebar -->
    <?php require APP_ROOT . '/app/Views/partials/sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <div class="flex-grow-1 d-flex flex-column min-vh-100" id="main-content">
        <!-- Header -->
        <?php require APP_ROOT . '/app/Views/partials/header.php'; ?>

        <!-- Page Content -->
        <main class="flex-grow-1 p-3 p-lg-4" id="page-content" role="main" tabindex="-1">
            <div class="container-fluid px-0">
                <?= $content ?? '' ?>
            </div>
        </main>

        <!-- Footer -->
        <?php require APP_ROOT . '/app/Views/partials/footer.php'; ?>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="/assets/js/bootstrap.min.js"></script>
<!-- SweetAlert2 -->
<script src="/assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>
<!-- Custom JS -->
<script src="/assets/js/app.js"></script>
<?php foreach (($scripts ?? []) as $src): ?>
    <script src="<?= htmlspecialchars($src) ?>"></script>
<?php endforeach; ?>
</body>
</html>
