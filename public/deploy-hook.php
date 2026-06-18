<?php

/**
 * Deployment Hook — Called by GitHub Actions after FTP upload.
 * 1. Extracts deploy.zip (preserving .env)
 * 2. Runs artisan commands (migrate, cache, etc.)
 *
 * Protected by a secret token in .deploy_token file.
 * URL: https://admin.fynbridals.com/deploy-hook.php?token=YOUR_TOKEN
 */

// ── Increase limits for deploy ─────────────────────────
set_time_limit(300);
ini_set('memory_limit', '256M');

// ── Security: verify deploy token ──────────────────────
$expectedToken = $_GET['token'] ?? '';
$tokenFile = __DIR__ . '/../.deploy_token';

if (!file_exists($tokenFile)) {
    http_response_code(403);
    echo "⛔ No .deploy_token file found\n";
    exit(1);
}

$envToken = trim(file_get_contents($tokenFile));

if (empty($expectedToken) || empty($envToken) || !hash_equals($envToken, $expectedToken)) {
    http_response_code(403);
    echo "⛔ Forbidden — invalid token\n";
    exit(1);
}

// ── Setup ──────────────────────────────────────────────
$basePath = realpath(__DIR__ . '/..');
chdir($basePath);

header('Content-Type: text/plain; charset=utf-8');
echo "🚀 Deploy Hook Started\n";
echo "──────────────────────\n\n";

// ── Helper to run commands ─────────────────────────────
function run(string $label, string $cmd): void {
    echo "▸ {$label}\n";
    echo "  \$ {$cmd}\n";
    $output = [];
    $exitCode = 0;
    exec($cmd . ' 2>&1', $output, $exitCode);
    if (!empty($output)) {
        echo "  " . implode("\n  ", $output) . "\n";
    }
    echo $exitCode === 0 ? "  ✅ OK\n\n" : "  ⚠️ Exit code: {$exitCode}\n\n";
}

// ── Step 1: Backup .env if it exists ───────────────────
$zipFile = $basePath . '/deploy.zip';
$envFile = $basePath . '/.env';
$envBackup = $basePath . '/.env.bak';

if (file_exists($envFile)) {
    copy($envFile, $envBackup);
    echo "▸ .env backed up\n\n";
}

// ── Step 2: Extract deploy.zip ─────────────────────────
if (file_exists($zipFile)) {
    $zip = new ZipArchive();
    if ($zip->open($zipFile) === true) {
        $zip->extractTo($basePath);
        $zip->close();
        unlink($zipFile);
        echo "▸ deploy.zip extracted & deleted ✅\n\n";
    } else {
        echo "▸ ❌ Failed to open deploy.zip\n\n";
    }
} else {
    echo "▸ No deploy.zip found (files were uploaded directly)\n\n";
}

// ── Step 3: Restore .env ───────────────────────────────
if (file_exists($envBackup)) {
    copy($envBackup, $envFile);
    unlink($envBackup);
    echo "▸ .env restored from backup ✅\n\n";
}

// ── Step 4: Ensure directories are writable ────────────
run('Fixing storage permissions', 'chmod -R 775 storage bootstrap/cache');

// ── Step 5: Run Artisan commands ───────────────────────
run('Creating storage symlink',  'php artisan storage:link');
run('Running migrations',        'php artisan migrate --force');
run('Caching config',            'php artisan config:cache');
run('Caching routes',            'php artisan route:cache');
run('Caching views',             'php artisan view:cache');

echo "──────────────────────\n";
echo "✅ Deployment complete!\n";
echo "🌐 https://admin.fynbridals.com\n";
