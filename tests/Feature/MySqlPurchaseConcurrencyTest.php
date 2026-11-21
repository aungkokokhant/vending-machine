<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class MySqlPurchaseConcurrencyTest extends TestCase
{
    public function test_two_processes_cannot_purchase_the_same_final_unit_on_mysql(): void
    {
        $database = getenv('VENDING_MYSQL_TEST_DATABASE');
        if (! is_string($database) || $database === '') {
            $this->markTestSkipped('Set VENDING_MYSQL_TEST_DATABASE to a separate existing MySQL database ending in _test.');
        }
        $this->assertStringEndsWith('_test', $database, 'The MySQL integration check must use a separate test database.');
        $config = config('database.connections.mysql');
        $config['database'] = $database;
        $config['url'] = null;
        config(['database.default' => 'mysql_test', 'database.connections.mysql_test' => $config]);
        $this->artisan('migrate', ['--database' => 'mysql_test', '--force' => true])->assertSuccessful();
        $user = User::factory()->create();
        $product = Product::factory()->pricedAt('6.885')->create(['quantity_available' => 1]);
        $barrier = sys_get_temp_dir().'/vending-concurrency-'.bin2hex(random_bytes(8));
        mkdir($barrier, 0700);
        $processes = [];
        try {
            foreach (['a', 'b'] as $worker) {
                $process = new Process([PHP_BINARY, base_path('tests/Fixtures/purchase_worker.php'), (string) $user->id, $product->slug, $barrier, $worker], base_path(), ['VENDING_MYSQL_TEST_DATABASE' => $database]);
                $process->setTimeout(20);
                $process->start();
                $processes[] = $process;
            }
            $deadline = microtime(true) + 10;
            while (! is_file($barrier.'/ready-a') || ! is_file($barrier.'/ready-b')) {
                foreach ($processes as $process) {
                    if (! $process->isRunning()) {
                        $this->fail('A MySQL purchase worker exited before the barrier: '.$process->getErrorOutput());
                    }
                }
                if (microtime(true) > $deadline) {
                    $this->fail('MySQL purchase workers did not become ready.');
                }
                usleep(10000);
            }
            touch($barrier.'/go');
            $results = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
                $results[] = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
            }
            $states = array_column($results, 'result');
            sort($states);
            $this->assertSame(['purchased', 'rejected'], $states);
            $this->assertSame(0, $product->fresh()->quantity_available);
            $this->assertSame(1, DB::table('transactions')->where('product_id', $product->id)->count());
            $this->assertSame('6.885', (string) DB::table('transactions')->where('product_id', $product->id)->value('total_price'));
        } finally {
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
            DB::table('transactions')->where('product_id', $product->id)->delete();
            $product->forceDelete();
            $user->forceDelete();
            foreach (glob($barrier.'/*') as $file) {
                unlink($file);
            }
            rmdir($barrier);
            DB::purge('mysql_test');
        }
    }
}
