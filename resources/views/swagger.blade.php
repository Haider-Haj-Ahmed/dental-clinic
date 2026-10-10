<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crystalline Dental PMS — API Docs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/5.17.14/swagger-ui.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0d1b2a; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }

        .topbar-custom {
            background: #0d1b2a;
            border-bottom: 1px solid rgba(79,219,204,0.2);
            padding: 14px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .topbar-custom .logo-text {
            font-size: 18px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.02em;
        }
        .topbar-custom .logo-text span { color: #4fdbcc; }
        .topbar-custom .badge {
            font-size: 10px;
            background: rgba(79,219,204,0.15);
            color: #4fdbcc;
            border: 1px solid rgba(79,219,204,0.3);
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 600;
            letter-spacing: 0.05em;
        }

        #swagger-ui { background: #f8fafc; }
        .swagger-ui .topbar { display: none; }
        .swagger-ui .info { padding: 20px 0 10px; }
        .swagger-ui .info .title { color: #0d1b2a; }
        .swagger-ui .info a { color: #4fdbcc; }
        .swagger-ui .scheme-container { background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="topbar-custom">
    <div class="logo-text">Crystalline <span>Dental</span></div>
    <div class="badge">API v3.0.0</div>
</div>

<div id="swagger-ui"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/5.17.14/swagger-ui-bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/5.17.14/swagger-ui-standalone-preset.min.js"></script>
<script>
    window.onload = function () {
        SwaggerUIBundle({
            url: "{{ route('api.docs.spec') }}",
            dom_id: '#swagger-ui',
            deepLinking: true,
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset,
            ],
            plugins: [SwaggerUIBundle.plugins.DownloadUrl],
            layout: 'StandaloneLayout',
            persistAuthorization: true,
            displayRequestDuration: true,
            filter: true,
            tryItOutEnabled: true,
            defaultModelsExpandDepth: 1,
            defaultModelExpandDepth: 2,
            syntaxHighlight: { activated: true, theme: 'agate' },
        });
    };
</script>
</body>
</html>
