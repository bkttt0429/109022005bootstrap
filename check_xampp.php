<?php
echo "<h1>XAMPP Header Configuration Diagnostic</h1>";

$modules = apache_get_modules();
if (in_array('mod_headers', $modules)) {
    echo "<p style='color: green;'>✅ mod_headers is ENABLED.</p>";
} else {
    echo "<p style='color: red;'>❌ mod_headers is DISABLED. .htaccess header rules will be IGNORED.</p>";
    echo "<p>To fix this:</p>";
    echo "<ol>";
    echo "<li>Open XAMPP Control Panel</li>";
    echo "<li>Click 'Config' button next to Apache</li>";
    echo "<li>Select 'Apache (httpd.conf)'</li>";
    echo "<li>Search for: <code>#LoadModule headers_module modules/mod_headers.so</code></li>";
    echo "<li>Remove the <code>#</code> at the beginning of the line</li>";
    echo "<li>Save the file and RESTART Apache</li>";
    echo "</ol>";
}

echo "<h2>Current Response Headers (Simulated)</h2>";
header("Cross-Origin-Opener-Policy: same-origin-allow-popups");
header("Cross-Origin-Embedder-Policy: unsafe-none");

echo "Checking if browser receives headers... (Check Network tab in F12)";
?>
