<?php
$output = [];
exec("cd .. && php artisan migrate --force 2>&1", $output, $return_var);
echo "Return Code: $return_var\n\n";
echo implode("\n", $output);
