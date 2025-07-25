--TEST--
swoole_server/base: shutdown
--SKIPIF--
<?php require __DIR__ . '/../../include/skipif.inc'; ?>
--FILE--
<?php
require __DIR__ . '/../../include/bootstrap.php';
$pm = new SwooleTest\ProcessManager;
$pm->initRandomData(1);
$pm->parentFunc = function () use ($pm) {
    Co\run(function () use ($pm) {
        $client = new Co\Client(SWOOLE_SOCK_TCP);
        Assert::assert($client->connect('127.0.0.1', $pm->getFreePort()));
        Assert::assert($client->send($pm->getRandomData()) > 0);
    });
    $pm->wait(-1);
};
$pm->childFunc = function () use ($pm) {
    $server = new Swoole\Server('127.0.0.1', $pm->getFreePort(), SWOOLE_BASE);
    $server->set([
        'worker_num' => mt_rand(2, 4),
        'log_file' => '/dev/null',
    ]);
    $server->on('start', function () use ($pm) {
        echo "START\n";
    });
    $server->on('managerStart', function () use ($pm) {
        $pm->wakeup();
    });
    $server->on('receive', function (Swoole\Server $server, int $fd, int $rid, string $data) use ($pm) {
        Assert::same($data, $pm->getRandomData());
        $server->shutdown();
    });
    $server->on('shutdown', function () use ($pm) {
        echo "SHUTDOWN\n";
        $pm->wakeup();
    });
    $server->start();
};
$pm->childFirst();
$pm->run();
$pm->expectExitCode(0);
?>
--EXPECT--
START
SHUTDOWN
