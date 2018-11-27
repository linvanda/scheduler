<?php

$a = [
    'name' => 'test',
    'server' => [
        'ip' => '127.0.0.1',
        'port' => 88,
        'child' => [
            'a', 'b'
        ]
    ],
    'debug' => true
];

$b = [
    'name' => 'prod',
    'server' => [
        'ip' => '192.168.1.1',
        'child' => ['c']
    ]
];

var_export(array_replace($a, $b));