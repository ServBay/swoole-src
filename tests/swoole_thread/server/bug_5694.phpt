--TEST--
swoole_thread/server: Github #5694
--SKIPIF--
<?php
require __DIR__ . '/../../include/skipif.inc';
skip_if_nts();
?>
--FILE--
<?php
require __DIR__ . '/../../include/bootstrap.php';

use Swoole\Thread;
use Swoole\Thread\Queue;

$port = get_constant_port(__FILE__);
$server = new Swoole\Http\Server('127.0.0.1', $port, SWOOLE_THREAD);
$server->set([
	'log_file' => '/dev/null',
	'worker_num' => 1,
	'task_worker_num' => 1,
	'max_request' => 1,
	'heartbeat_check_interval'=> 1,
    'heartbeat_idle_time'=> 2,
    'enable_coroutine' => true,
    'hook_flags' => SWOOLE_HOOK_ALL,
    'init_arguments' => function () {
        global $queue;
        $queue = new Queue();
        return [$queue];
    }
]);

$server->on('WorkerStart', function (Swoole\Server $server, $workerId) {
    [$queue] = Thread::getArguments();
    $queue->push('start', Queue::NOTIFY_ALL);
});

$server->on('Task', function (Swoole\Server $server, int $task_id, int $src_worker_id, mixed $data) {
    var_dump($data);
});

$server->addProcess(new Swoole\Process(function ($process) use ($server, $port) {
	[$queue] = Thread::getArguments();
	Assert::true($queue->pop(-1) == 'start');
	Assert::true(file_get_contents("http://127.0.0.1:{$port}/") == 'OK');
	sleep(1);
	Assert::true(file_get_contents("http://127.0.0.1:{$port}/") == 'OK');
	sleep(2);
	$server->shutdown();
}));

$server->on('request', function (Swoole\Http\Request $request, Swoole\Http\Response $response) use ($server) {
    $server->task('12313');
    $response->end('OK');
});
$server->start();
?>
--EXPECT--
string(5) "12313"
string(5) "12313"
