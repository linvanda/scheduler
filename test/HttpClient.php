<?php

use Swlib\SaberGM;

require_once('../vendor/autoload.php');

Swoole\Coroutine::set(['log_level' => SWOOLE_LOG_ERROR]);

go(function () {
    SaberGM::default([
        'use_pool' => true,
        'content_type' => \Swlib\Http\ContentType::JSON
    ]);

    $response = SaberGM::get('http://localhost:9876', ['data' => ['workflow' => 'test', 'data' => ['id' => 123]]]);

//    var_export($response);
});
