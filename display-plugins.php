<?php


// Retrieve the 'plugins' query parameter from the URL
$pluginsData = isset($_GET['plugins']) ? $_GET['plugins'] : '';

// Decode the JSON-encoded plugins data
$plugins = json_decode(urldecode($pluginsData), true);

// Start HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Display Plugins</title>
    <!-- Ensure you have a 'style.css' file for styles -->
</head>
<body>
    <h1>Selected Plugins</h1>
    <?php if (!empty($plugins)): ?>
        <ul>
            <?php foreach ($plugins as $plugin): ?>
                <li>
                    <?php echo esc_html($plugin['name']); ?> - Version: <?php echo esc_html($plugin['version']); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No plugins selected.</p>
    <?php endif; ?>
</body>
</html>
