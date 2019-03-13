<?php

use Swlib\SaberGM;

require_once('../vendor/autoload.php');

error_reporting(E_ERROR);

Swoole\Coroutine::set(['log_level' => SWOOLE_LOG_ERROR]);

$workflow = $argv[1] ?: 'test';

echo "workflow:$workflow\n";

go(function () use ($workflow) {
    SaberGM::default([
        'use_pool' => true,
        'content_type' => \Swlib\Http\ContentType::JSON
    ]);

    $response = SaberGM::get('http://localhost:9876', ['data' => ['workflow' => $workflow, 'data' => ['id' => 123]]]);

    var_export($response->body->read(100));
});
