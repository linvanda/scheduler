<?php

/**
 * 子系统配置
 */
return [
    'PY' => [
        'name' => '支付模块',
        'servers'  => [
            'dev' => ['url' => 'http://gateway.wcc.cn'],
        ],
    ],
    'PT' => [
        'name' => '支付模块',
        'servers'  => [
            'dev' => ['url' => 'http://gateway.wcc.cn', 'weight' => 100],
        ],
    ],
    'PA' => [
        'name' => '支付模块',
        'servers'  => [
            'dev' => ['url' => 'http://gateway.wcc.cn', 'weight' => 100],
        ],
    ],
    'OL' => [
        'name' => '油号',
        'servers' => [
            'dev' => ['url' => 'http://192.168.85.201:8081', 'weight' => 100],
        ],
    ],
    'YZ' => [
        'name' => '油站',
        'servers' => [
            'dev' => ['url' => 'http://192.168.85.201:8082', 'weight' => 100],
        ],
    ],
    'SS' => [
        'app_id'    => 10141,
        'secret'    => '2iiigbbXfM0VbgpwSCAUpjYbbEZAokLl',
        'servers' => [
            'dev' => ['url' => 'http://weios.wecar.me:10842/webservice/ipsrv', 'weight' => 100],
        ],
    ]
];
