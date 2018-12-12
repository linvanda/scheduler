<?php

error_reporting(E_ERROR);

$arr =  [
    // 失败重试次数，节点可覆盖。不超过10
    // 次数与时间的关系：[1 => 5, 2 => 15, 3 => 30, 4 => 180, 5 => 600, 6 => 1800, 7 => 3600, 8 => 10800, 9 => 18000, 10 => 36000]
    'max_retry_num' => 6,
    // 最多允许延迟执行多少次，可被节点配置覆盖
    'max_delay_num' => 5,
    // 当节点需要延迟执行时，延迟的秒数，可在节点配置中覆盖
    'delay' => 5,
    // 处理程序类名
    'controller' => OneKeyOilController::class,
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
            // 节点执行的前置条件，多个条件是 and 的关系。注意不要出现循环依赖，否则这些节点都得不到执行
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

function normalizeConfig(array $cfg)
{
    $cfg['delay'] = $cfg['delay'] ?: 5;
    $cfg['max_retry_num'] = $cfg['max_retry_num'] ?: 6;
    $cfg['max_delay_num'] = $cfg['max_delay_num'] ?: 5;

    foreach ($cfg['nodes'] as $nodeName => $nodeCfg) {
        if (is_int($nodeName) && is_string($nodeCfg)) {
            $cfg['nodes'][$nodeCfg] = [];
            unset($cfg['nodes'][$nodeName]);
        }
    }

    return $cfg;
}






function hasLoopDependence(array $cfg)
{

    foreach (array_keys($cfg) as $nodeName) {
        if (isLoopDepend($nodeName, $cfg, $nodeName)) {
            return 1;
        }
    }

    return 0;
}

function isLoopDepend($startName, $dependList, $findName = null)
{
    if (!$findName) {
        $findName = $startName;
    }

    $startList = $dependList[$startName];

    if (!$startList) {
        return 0;
    }

    $has = 0;
    foreach ($startList as $dependItem) {
        if ($dependItem == $findName) {
            return 1;
        }

        // 继续查找
        $has |= isLoopDepend($dependItem, $dependList, $findName);
    }

    return $has;
}

$arr = [
    'a' => ['b', 'c', 'd', 'e'],
    'b' => ['c', 'd'],
    'c' => ['d'],
    'd' => ['e'],
//    'e' => ['a']
];

echo hasLoopDependence($arr);

//echo isLoopDepend('c', $arr);