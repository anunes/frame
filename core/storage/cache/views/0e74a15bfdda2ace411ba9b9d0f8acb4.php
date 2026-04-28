<?php ?><!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars((APP_NAME) ?? '', ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars(($title) ?? '', ENT_QUOTES, 'UTF-8'); ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars((asset_url('img/favicon/favicon.ico')) ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo htmlspecialchars((asset_url('img/favicon/favicon-16x16.png')) ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo htmlspecialchars((asset_url('img/favicon/favicon-32x32.png')) ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo htmlspecialchars((asset_url('img/favicon/apple-touch-icon.png')) ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo htmlspecialchars((asset_url('img/favicon/android-chrome-192x192.png')) ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?php echo htmlspecialchars((asset_url('img/favicon/android-chrome-512x512.png')) ?? '', ENT_QUOTES, 'UTF-8'); ?>">

    <script>
        // Theme initialization (run immediately to prevent flash)
        (function () {
            const normalizeTheme = theme => {
                if (theme === 'auto' || theme === 'system') {
                    return 'system';
                }

                return theme === 'light' || theme === 'dark' ? theme : 'system';
            };
            const theme = normalizeTheme(localStorage.getItem('theme'));
            const actualTheme = theme === 'system'
                ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                : theme;
            document.documentElement.setAttribute('data-bs-theme', actualTheme);
        })();
    </script>

    <link rel="stylesheet" href="<?php echo htmlspecialchars((asset_url('css/main.css')) ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars((asset_url('bootstrap-icons/font/bootstrap-icons.min.css')) ?? '', ENT_QUOTES, 'UTF-8'); ?>">
</head>

<body class="page-loading">
    <?php echo $this->includeTemplate('layouts.partials.nav', []); ?>

    <?php \app\core\Session::flash(); ?>

    <main class="container py-4">
        <?php echo $this->compileAndRenderSlot($slot ?? ""); ?>
    </main>

    <?php echo $this->includeTemplate('layouts.partials.footer-nav', []); ?>

    <script src="<?php echo htmlspecialchars((asset_url('js/jquery.js')) ?? '', ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="<?php echo htmlspecialchars((asset_url('js/bootstrap.bundle.min.js')) ?? '', ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="<?php echo htmlspecialchars((asset_url('js/theme.js')) ?? '', ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="<?php echo htmlspecialchars((asset_url('js/main.js')) ?? '', ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>

</html>
