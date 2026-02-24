<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4361ee">
    <title>Iniciar Sesión — Cohort Monitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --gradient-start: #1a1a2e;
            --gradient-mid: #16213e;
            --gradient-end: #0f3460;
        }
        body {
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-mid) 50%, var(--gradient-end) 100%);
            min-height: 100vh;
        }
        .login-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 1rem;
        }
        .login-card {
            border-radius: 1rem;
            border: none;
            overflow: hidden;
        }
        .login-brand {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 10px 30px -10px rgba(67, 97, 238, 0.5);
        }
        .login-brand i {
            font-size: 1.75rem;
            color: white;
        }
        .form-control:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.15);
        }
        .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
        }
        .input-group .form-control {
            border-left: none;
        }
        .input-group:focus-within .input-group-text {
            border-color: #4361ee;
        }
        .btn-login {
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px -5px rgba(67, 97, 238, 0.5);
        }
        .version-badge {
            background: rgba(255,255,255,0.1);
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.75rem;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center">

<div class="login-wrapper">
    <div class="card login-card shadow-lg">
        <div class="card-body p-4 p-sm-5">
            <div class="text-center mb-4">
                <div class="login-brand">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <h4 class="fw-bold mb-1">Cohort Monitor</h4>
                <p class="text-muted small">Inicia sesión para continuar</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 small d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="/login" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label for="username" class="form-label small fw-medium">Usuario</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="username" name="username"
                               placeholder="Nombre de usuario" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label small fw-medium">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Contraseña" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-login w-100">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar Sesión
                </button>
            </form>
        </div>
    </div>
    
    <div class="text-center mt-4">
        <span class="version-badge text-white-50">
            <i class="bi bi-code-slash me-1"></i> Cohort Monitor v1.0
        </span>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
