<?php

use App\Contracts\Purchasing;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$database = getenv('VENDING_MYSQL_TEST_DATABASE');
if (! is_string($database) || ! str_ends_with($database, '_test')) {
    throw new RuntimeException('A separate MySQL database ending in _test is required.');
}
$config = config('database.connections.mysql');
$config['database'] = $database;
$config['url'] = null;
config(['database.default' => 'mysql_test', 'database.connections.mysql_test' => $config]);
[$script, $userId, $slug, $barrier, $worker] = $argv;
touch($barrier.'/ready-'.$worker);
$deadline = microtime(true) + 10;
while (! is_file($barrier.'/go')) {
    if (microtime(true) > $deadline) {
        throw new RuntimeException('Purchase worker barrier timed out.');
    }
    usleep(10000);
}
try {
    $transaction = $app->make(Purchasing::class)->purchase((int) $userId, $slug, 1);
    echo json_encode(['result' => 'purchased', 'total_price' => $transaction['total_price']], JSON_THROW_ON_ERROR);
} catch (ValidationException $exception) {
    echo json_encode(['result' => 'rejected', 'errors' => $exception->errors()], JSON_THROW_ON_ERROR);
}
