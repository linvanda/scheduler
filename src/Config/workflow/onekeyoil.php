<?php

use Weiche\Scheduler\Handler\OneKeyOil;

/**
 * 一键加油工作流模板
 */
return [
    // 失败重试次数，节点可覆盖。不超过10
    // 次数与时间的关系：[1 => 5, 2 => 15, 3 => 30, 4 => 180, 5 => 600, 6 => 1800, 7 => 3600, 8 => 10800, 9 => 18000, 10 => 36000]
    'max_retry_num' => 6,
    // 最多允许延迟执行多少次，可被节点配置覆盖
    'max_delay_num' => 5,
    // 当节点需要延迟执行时，延迟的秒数，可在节点配置中覆盖
    'delay' => 5,
    // 处理程序类名
    'controller' => OneKeyOil::class,
    // 节点定义
    'nodes' => [
        'step1' =>[
            // 处理程序 action，默认同 name
            'action' => 'step1',
            'max_retry_num' => 3,
        ],
        'step2' => [
            'action' => 'step222',
            'max_delay_num' => 3,
        ],
        'step3' => [
            // 节点执行的前置条件，多个条件是 and 的关系
            'conditions' => [
                'step1' => 200, // 节点 step1 返回状态码是 200
                'step2' => 0, // 节点 step2 执行完毕，无论返回什么，即哪怕节点执行失败也照样执行该节点。注意：前置节点处于delay、retry态时属于"中间态"，不会进入后置节点执行
                'step4' => [200, 201, 202],
                'step5' => '20*'
            ],
            'delay' => 10,
        ],
        'step4',
        'step5'
    ]
];
