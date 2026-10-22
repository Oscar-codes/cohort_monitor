<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#dc2626">
    <title>500 - Error inesperado</title>
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
    <style>
        .error-icon {
            font-size: 5rem;
            line-height: 1;
        }

        .error-copy {
            max-width: 420px;
        }

        .error-details {
            max-width: min(960px, 90vw);
            max-height: 50vh;
            overflow: auto;
            white-space: pre-wrap;
        }
    </style>
</head>
<body class="bg-body-tertiary min-vh-100 d-flex align-items-center justify-content-center p-3">
    <main class="card border-0 shadow-sm p-4 p-md-5 text-center">
        <div class="mb-3">
            <i class="bi bi-exclamation-octagon text-danger error-icon"></i>
        </div>
        <h1 class="display-4 fw-bold text-danger mb-2">500</h1>
        <h2 class="h4 text-body-secondary mb-3">Ocurrio un error inesperado</h2>
        <p class="text-muted mx-auto mb-2 error-copy">
            No pudimos completar la solicitud. Intenta nuevamente en unos minutos. Si el problema continua, comparte esta referencia con el equipo de soporte.
        </p>
        <p class="mb-4"><span class="badge bg-secondary-subtle text-secondary fs-6">Ref: <?= htmlspecialchars((string) ($ref ?? '')) ?></span></p>
        <?php if (!empty($details)): ?>
        <pre class="error-details text-start small bg-body-tertiary border rounded p-3 mx-auto mb-4"><?= htmlspecialchars((string) $details) ?></pre>
        <?php endif; ?>
        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
            <a href="/" class="btn btn-primary">
                <i class="bi bi-house me-1"></i> Ir al inicio
            </a>
            <a href="javascript:history.back()" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
        </div>
    </main>
</body>
</html>
