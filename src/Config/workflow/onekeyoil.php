<?php

use Weiche\Scheduler\Handler\OneKeyOil;

/**
 * 一键加油工作流模板
 */
return [
    // 节点失败重试次数
    'max_retry_num' => 5,
    // 当节点需要延迟执行时，延迟的秒数，可在节点配置中覆盖
    'delay' => 5,
    // 处理程序类名
    'controller' => OneKeyOil::class,
    // 节点定义
    'nodes' => [
        'step1' =>[
            // 处理程序 action，默认同 name
            'action' => 'step1',
        ],
        'step2' => [
            'action' => 'step222',
        ],
        'step3' => [
            // 节点执行的前置条件，多个条件是 and 的关系
            'conditions' => [
                'step1' => 200, // 节点 step1 返回状态码是 200
                'step2' => 0, // 节点 step2 执行完毕，无论返回什么
            ],
            'delay' => 10,
        ]
    ]
];
