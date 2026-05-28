<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f172a">
    <title>Iniciar sesion - Cohort Monitor</title>
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body class="auth-login-page">
    <main class="auth-login-shell">
        <section class="auth-login-brand">
            <div class="auth-login-brand__logo">
                <img src="/assets/images/logo/kodigo.jpg" alt="Kodigo">
            </div>
            <span class="dashboard-hero__eyebrow">
                <i class="bi bi-shield-check"></i>
                Cohort Monitor
            </span>
            <h1>Operacion de cohortes en un solo lugar.</h1>
            <p>Monitorea admisiones, entrenamiento, marketing, coaches y alertas desde un dashboard operativo.</p>
            <div class="auth-login-highlights">
                <span><i class="bi bi-graph-up-arrow"></i> KPIs ejecutivos</span>
                <span><i class="bi bi-calendar-range"></i> Calendario de coaches</span>
                <span><i class="bi bi-exclamation-triangle"></i> Riesgos visibles</span>
            </div>
        </section>

        <section class="auth-login-card" aria-labelledby="login-title">
            <div class="auth-login-card__header">
                <div>
                    <span class="auth-login-card__kicker">Acceso seguro</span>
                    <h2 id="login-title">Iniciar sesion</h2>
                    <p>Ingresa tus credenciales para continuar.</p>
                </div>
                <span class="auth-login-card__icon"><i class="bi bi-box-arrow-in-right"></i></span>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="/login" class="needs-validation auth-login-form" novalidate>
                <div class="mb-3">
                    <label for="username" class="form-label">Usuario o correo</label>
                    <div class="input-group auth-input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Nombre de usuario o correo" required autofocus autocomplete="username">
                        <div class="invalid-feedback">Ingresa tu usuario o correo.</div>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Contrasena</label>
                    <div class="input-group auth-input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Contrasena" required autocomplete="current-password">
                        <button class="btn btn-outline-secondary auth-password-toggle" type="button" data-password-toggle="#password" aria-label="Mostrar contrasena">
                            <i class="bi bi-eye"></i>
                        </button>
                        <div class="invalid-feedback">Ingresa tu contrasena.</div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary auth-login-submit w-100">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Entrar al dashboard
                </button>
            </form>

            <div class="auth-login-card__footer">
                <span><i class="bi bi-code-slash me-1"></i> Cohort Monitor v1.0</span>
            </div>
        </section>
    </main>

    <script src="/assets/js/bootstrap.min.js"></script>
    <script src="/assets/js/auth-login.js"></script>
</body>
</html>
