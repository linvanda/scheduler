<?php

use Weiche\Scheduler\Handler\OneKeyOil;

/**
 * 一键加油工作流模板
 */
return [
    // 节点失败重试次数
    'max_retry_num' => 5,
    // 处理程序类名
    'handler' => OneKeyOil::class,
    // 节点定义
    'nodes' => [
        [
            // 节点名称，一个工作流里面节点名称不能重复
            'name' => 'step1',
        ],
        [
            'name' => 'step2',
        ]
    ]
];
