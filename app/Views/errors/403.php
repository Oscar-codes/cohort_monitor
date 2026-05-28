<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#dc2626">
    <title>403 - Acceso denegado</title>
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
    </style>
</head>
<body class="bg-body-tertiary min-vh-100 d-flex align-items-center justify-content-center p-3">
    <main class="card border-0 shadow-sm p-4 p-md-5 text-center">
        <div class="mb-3">
            <i class="bi bi-shield-lock text-danger error-icon"></i>
        </div>
        <h1 class="display-4 fw-bold text-danger mb-2">403</h1>
        <h2 class="h4 text-body-secondary mb-3">Acceso denegado</h2>
        <p class="text-muted mx-auto mb-4 error-copy">
            No tienes los permisos necesarios para acceder a esta seccion. Contacta al administrador si crees que deberias tener acceso.
        </p>
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
