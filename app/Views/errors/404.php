<!-- 404 Error View -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4361ee">
    <title>404 — Página no encontrada</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .error-wrapper {
            max-width: 480px;
            padding: 2rem;
        }
        .error-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 15px 35px -10px rgba(255, 193, 7, 0.4);
        }
        .error-icon i {
            font-size: 2.5rem;
            color: white;
        }
        .error-code {
            font-size: 4rem;
            font-weight: 800;
            background: linear-gradient(135deg, #1a1a2e 0%, #4361ee 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
        }
        .btn-home {
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            border: none;
            padding: 0.75rem 2rem;
            font-weight: 500;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px -8px rgba(67, 97, 238, 0.5);
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center">
    <div class="error-wrapper text-center">
        <div class="error-icon">
            <i class="bi bi-exclamation-lg"></i>
        </div>
        <div class="error-code mb-2">404</div>
        <h4 class="fw-semibold mb-2">Página no encontrada</h4>
        <p class="text-muted mb-4">La página que buscas no existe o ha sido movida a otra ubicación.</p>
        <a href="/" class="btn btn-primary btn-home">
            <i class="bi bi-house me-2"></i> Volver al inicio
        </a>
    </div>
</body>
</html>
