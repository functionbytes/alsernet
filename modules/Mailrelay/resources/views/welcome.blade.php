<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>MailRelay - Email Validation & Newsletter Management</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .hero-section {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            color: white;
        }

        .feature-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin-bottom: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            border: none;
            padding: 12px 32px;
            font-weight: 500;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        .btn-outline-light:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .stats-badge {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
            margin: 0 8px;
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-3 fw-bold mb-4">
                        <i class="bi bi-envelope-check-fill"></i> MailRelay
                    </h1>
                    <p class="lead mb-4">
                        Advanced email validation and newsletter management system powered by Laravel 11 and Mailrelay API.
                    </p>

                    <div class="mb-4">
                        <span class="stats-badge">
                            <i class="bi bi-check-circle-fill me-2"></i>Multi-Level Validation
                        </span>
                        <span class="stats-badge">
                            <i class="bi bi-lightning-fill me-2"></i>Real-Time Processing
                        </span>
                        <span class="stats-badge">
                            <i class="bi bi-shield-fill-check me-2"></i>95% Cost Savings
                        </span>
                    </div>

                    <div class="d-flex gap-3 flex-wrap">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg">
                                <i class="bi bi-speedometer2 me-2"></i>Go to Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Login
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn btn-outline-light btn-lg">
                                    <i class="bi bi-person-plus me-2"></i>Register
                                </a>
                            @endif
                        @endauth
                    </div>
                </div>

                <div class="col-lg-6 d-none d-lg-block text-center">
                    <i class="bi bi-envelope-paper-fill" style="font-size: 20rem; opacity: 0.15;"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 bg-light">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-3">Powerful Features</h2>
                <p class="lead text-muted">Everything you need for email validation and newsletter management</p>
            </div>

            <div class="row g-4">
                <!-- Feature 1 -->
                <div class="col-md-6 col-lg-3">
                    <div class="card feature-card shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mx-auto">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <h5 class="card-title">Multi-Level Validation</h5>
                            <p class="card-text text-muted">
                                Syntax, DNS, SMTP, and external API validation with smart fallback system.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Feature 2 -->
                <div class="col-md-6 col-lg-3">
                    <div class="card feature-card shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mx-auto">
                                <i class="bi bi-trash3"></i>
                            </div>
                            <h5 class="card-title">Disposable Detection</h5>
                            <p class="card-text text-muted">
                                Identify and block 10,000+ disposable email domains automatically.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Feature 3 -->
                <div class="col-md-6 col-lg-3">
                    <div class="card feature-card shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mx-auto">
                                <i class="bi bi-upload"></i>
                            </div>
                            <h5 class="card-title">Bulk Import</h5>
                            <p class="card-text text-muted">
                                Import CSV/Excel files with real-time validation and progress tracking.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Feature 4 -->
                <div class="col-md-6 col-lg-3">
                    <div class="card feature-card shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mx-auto">
                                <i class="bi bi-megaphone"></i>
                            </div>
                            <h5 class="card-title">Campaign Management</h5>
                            <p class="card-text text-muted">
                                Create, schedule, and track newsletter campaigns with Mailrelay integration.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Feature 5 -->
                <div class="col-md-6 col-lg-3">
                    <div class="card feature-card shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mx-auto">
                                <i class="bi bi-lightning"></i>
                            </div>
                            <h5 class="card-title">Real-Time Processing</h5>
                            <p class="card-text text-muted">
                                Queue-based async processing with Redis for high performance.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Feature 6 -->
                <div class="col-md-6 col-lg-3">
                    <div class="card feature-card shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mx-auto">
                                <i class="bi bi-graph-up"></i>
                            </div>
                            <h5 class="card-title">Analytics Dashboard</h5>
                            <p class="card-text text-muted">
                                Track campaign performance with detailed analytics and metrics.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Feature 7 -->
                <div class="col-md-6 col-lg-3">
                    <div class="card feature-card shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mx-auto">
                                <i class="bi bi-code-square"></i>
                            </div>
                            <h5 class="card-title">RESTful API</h5>
                            <p class="card-text text-muted">
                                Complete API with OpenAPI documentation for programmatic access.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Feature 8 -->
                <div class="col-md-6 col-lg-3">
                    <div class="card feature-card shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mx-auto">
                                <i class="bi bi-cpu"></i>
                            </div>
                            <h5 class="card-title">Python ML Integration</h5>
                            <p class="card-text text-muted">
                                Pattern recognition with Python validator for enhanced detection.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Tech Stack Section -->
    <section class="py-5">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-3">Built with Modern Technology</h2>
                <p class="lead text-muted">Powered by industry-leading frameworks and services</p>
            </div>

            <div class="row g-4 text-center">
                <div class="col-6 col-md-3">
                    <div class="p-4">
                        <i class="bi bi-filetype-php text-primary" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">Laravel 11</h5>
                        <p class="text-muted">PHP Framework</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-4">
                        <i class="bi bi-bootstrap-fill text-primary" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">Bootstrap 5</h5>
                        <p class="text-muted">CSS Framework</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-4">
                        <i class="bi bi-database-fill text-primary" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">MySQL & Redis</h5>
                        <p class="text-muted">Database Layer</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-4">
                        <i class="bi bi-envelope-at-fill text-primary" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">Mailrelay API</h5>
                        <p class="text-muted">Email Service</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="bi bi-envelope-fill me-2"></i>MailRelay</h5>
                    <p class="text-white-50 small">
                        Advanced email validation and newsletter management system.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-white-50 small mb-0">
                        Laravel v{{ Illuminate\Foundation\Application::VERSION }} (PHP v{{ PHP_VERSION }})
                    </p>
                    <p class="text-white-50 small">
                        &copy; {{ date('Y') }} MailRelay. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
