<?php

putenv('NO_COLOR=1');
ob_start();
passthru('php vendor/bin/pest packages/Webkul/Markup/tests/Feature/MarkupGroupTest.php --no-coverage 2>&1', $exitCode);
$output = ob_get_clean();

// Strip ANSI escape codes
$clean = preg_replace('/\e\[[0-9;]*m/', '', $output);
file_put_contents('D:/test_out.txt', $clean);
echo "EXIT CODE: {$exitCode}\n";
