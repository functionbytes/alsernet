<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Página no encontrada | A-ALVAREZ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- IMPORTANTE PARA SEO EN 404 -->
    <meta name="robots" content="noindex, nofollow">

    <!-- Meta básica (opcional) -->
    <meta name="description" content="La página que buscas no existe o ya no está disponible.">

    <!-- Estilos mínimos, sin recursos externos -->
    <style>
        :root {
            /* Ajusta estos colores a tu identidad de marca */
            --brand-color: #00529b;
            --brand-color-dark: #003766;
            --text-color: #222;
            --bg-color: #f5f5f5;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            height: 100%;
        }

        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .container {
            max-width: 720px;
            width: 100%;
            background: #fff;
            border-radius: 8px;
            padding: 2rem 1.75rem 1.75rem;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
        }

        .logo {
            display: flex;
            justify-content: center;
            /* <-- centra horizontalmente */
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }


        .logo__mark {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--brand-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #fff;
            font-size: 1.1rem;
        }

        .logo__text {
            font-weight: 700;
            letter-spacing: 0.06em;
            font-size: 0.95rem;
            text-transform: uppercase;
            color: var(--brand-color-dark);
        }

        .content-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .code {
            font-size: 2.7rem;
            font-weight: 800;
            color: var(--brand-color);
            line-height: 1;
        }

        h1 {
            font-size: 1.4rem;
            line-height: 1.3;
        }

        p {
            margin-top: 0.4rem;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .hint {
            margin-top: 0.4rem;
            font-size: 0.85rem;
            opacity: 0.85;
        }

        .search-block {
            margin: 1.5rem 0 1.25rem;
        }

        .search-label {
            font-size: 0.9rem;
            margin-bottom: 0.4rem;
            display: block;
        }

        .search-form {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1 1 180px;
            padding: 0.6rem 0.7rem;
            border-radius: 4px;
            border: 1px solid #ddd;
            font-size: 0.95rem;
        }

        .search-button {
            padding: 0.6rem 0.9rem;
            border-radius: 4px;
            border: none;
            background: var(--brand-color);
            color: #fff;
            font-size: 0.9rem;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.15s ease-in-out;
            white-space: nowrap;
        }

        .search-button:hover {
            background: var(--brand-color-dark);
        }

        .links {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .link-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 0.8rem;
            border-radius: 999px;
            font-size: 0.85rem;
            border: 1px solid #ddd;
            text-decoration: none;
            color: var(--text-color);
            background: #fafafa;
        }

        .link-pill:hover {
            border-color: var(--brand-color);
        }

        .footer-note {
            text-align: center;
            margin-top: 0.5rem;
            font-size: 0.8rem;
            opacity: 0.8;
        }

        @media (max-width: 480px) {
            .container {
                padding: 1.5rem 1.25rem 1.25rem;
            }

            .code {
                font-size: 2.2rem;
            }

            h1 {
                font-size: 1.2rem;
            }
        }
    </style>
</head>

<body>
    <main class="container" role="main">
        <!-- Logo / Marca (sustituir por el logo real si se desea) -->
        <div class="logo">
            <a href="/" class="logo ml-lg-0">
                <img src="/themes/alvarez/assets/img/theme/logo/{$iso_code}/logo.svg" alt="logo" width="200">
            </a>
        </div>

        <!-- Mensaje principal 404 -->
        <header class="content-header">
            <div class="code" aria-hidden="true">404</div>
            <div>
                <h1>{l s='Page not found' d='Shop.Theme.Catalog'}</h1>
                <p>
                    {l s='Page not found description' d='Shop.Theme.Catalog'}
                </p>
            </div>
        </header>

        <p class="footer-note">
            {l s='Url not found' d='Shop.Theme.Catalog'}
            <a href="/{$iso_code}">{l s='Home' d='Shop.Theme.Global'}</a>.
        </p>
    </main>
</body>

</html>




