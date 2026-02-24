<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d6efd">
    <title><?= htmlspecialchars($pageTitle ?? 'Cohort Monitor') ?> — Cohort Monitor</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-body-tertiary">

<div class="d-flex" id="app-wrapper">
    <!-- Sidebar -->
    <?php require APP_ROOT . '/app/Views/partials/sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <div class="flex-grow-1 d-flex flex-column min-vh-100" id="main-content">
        <!-- Header -->
        <?php require APP_ROOT . '/app/Views/partials/header.php'; ?>

        <!-- Page Content -->
        <main class="flex-grow-1 p-3 p-lg-4">
            <div class="container-fluid px-0">
                <?= $content ?? '' ?>
            </div>
        </main>

        <!-- Footer -->
        <?php require APP_ROOT . '/app/Views/partials/footer.php'; ?>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="/assets/js/app.js"></script>
</body>
</html>
