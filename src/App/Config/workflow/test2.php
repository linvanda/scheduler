<?php

return [
    'controller' => \App\Controller\Test2Controller::class,
    'nodes' => [
        'foo' => [

        ],
        'bar' => [
            'conditions' => [
                'foo' => 0
            ]
        ]
    ],
];
