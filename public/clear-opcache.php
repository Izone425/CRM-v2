<?php
// Clear OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache cleared successfully!\n";
} else {
    echo "OPcache not available\n";
}

// Clear Filament panels cache
$filamentCache = __DIR__ . '/../bootstrap/cache/filament/panels/admin.php';
if (file_exists($filamentCache)) {
    unlink($filamentCache);
    echo "Filament admin panel cache cleared!\n";
} else {
    echo "No Filament admin panel cache found.\n";
}
