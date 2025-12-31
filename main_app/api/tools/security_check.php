<?php
// security_check.php - 檢查 Apache 安全標頭是否生效
header("Cross-Origin-Opener-Policy: same-origin-allow-popups");
header("Cross-Origin-Embedder-Policy: unsafe-none");

echo "<h1>Security Headers Diagnostic</h1>";
echo "<p>This page sends the headers required for Google Sign-In.</p>";
echo "<hr>";
echo "<h3>Current Response Headers (sent by this script):</h3>";
echo "<ul>";
foreach (headers_list() as $header) {
    echo "<li><strong>$header</strong></li>";
}
echo "</ul>";

echo "<h3>Action Items:</h3>";
echo "<ol>";
echo "<li>If you DO NOT see <strong>Cross-Origin-Opener-Policy: same-origin-allow-popups</strong> above, your Apache <code>mod_headers</code> is definitely not working.</li>";
echo "<li>Open <code>httpd.conf</code>, enable <code>mod_headers</code>, and RESTART Apache.</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='../client/dist/'>Return to App</a></p>";
?>
