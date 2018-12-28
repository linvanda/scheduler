<?php

namespace Scheduler\Workflow;

use Scheduler\Infrastructure\Logger;
use Scheduler\Infrastructure\Request;
use Scheduler\Exception\ClassNotFoundException;
use Scheduler\Exception\InvalidConfigException;
use Scheduler\Exception\RunException;
use Scheduler\Exception\WorkFlowException;
use Scheduler\Utils\Config;

/**
 * 工作流基类
 * 注意：工作流必须正确处理内部抛出的异常，对外只能抛出工作流层面的异常
 *
 * Class WorkFlow
 * @package Scheduler\Workflow
 */
abstract class WorkFlow
{
    // 初始化后尚未开始，等待执行
    const STATUS_INIT = 1;
    // 节点执行中
    const STATUS_DOING = 2;
    // 等待下一次执行
    const STATUS_WAIT = 3;
    // 工作流最终执行成功
    const STATUS_SUC = 4;
    // 工作流最终执行失败
    const STATUS_FAIL = 5;
    // 工作流最大执行次数，主要防止某些意外导致工作流永远无法结束
    const MAX_EXEC_NUM = 1000;

    // 工作流名称
    protected $name;
    // 当前状态
    protected $status;

    protected $ttl;
    // 节点延迟执行的时间，Node 中可覆盖
    protected $delay;
    // 节点失败最大重试次数，Node 中可覆盖
    protected $maxRetryNum;
    // 最大延迟执行次数
    protected $maxDelayNum;
    // 当处于 wait 态时，下次执行时间
    protected $nextExecTime;
    // 节点集合, node_name => $node
    protected $nodes = [];
    // 工作流控制器（处理程序）
    protected $controller;
    /**
     * 请求原始数据对象
     * @var Request
     */
    protected $request;

    /**
     * WorkFlow constructor.
     * @param string $name
     * @param Request $request
     * @throws ClassNotFoundException
     * @throws InvalidConfigException
     * @throws \Scheduler\Exception\FileNotFoundException
     */
    public function __construct(string $name, Request $request)
    {
        $this->name = $name;
        $this->request = $request;
        $this->status = self::STATUS_INIT;
        $this->ttl = self::MAX_EXEC_NUM;

        $this->init($name);
    }

    /**
     * 执行工作流
     * @throws WorkFlowException
     * @throws InvalidConfigException
     */
    public function run()
    {
        Logger::debug("执行工作流{$this->name()}");
        $this->pre();
        // 执行具体的节点，此方法由子类具体实现
        $this->runNodes();
        $this->post();
    }

    public function name()
    {
        return $this->name;
    }

    public function status()
    {
        return $this->status;
    }

    public function fail()
    {
        $this->status = self::STATUS_FAIL;
    }

    public function nodes()
    {
        return $this->nodes;
    }

    /**
     * 工作流是否需要继续执行
     * @return bool
     */
    public function willContinue()
    {
        return $this->status === self::STATUS_WAIT;
    }

    /**
     * 下次执行时间
     * @return int
     */
    public function nextExecTime()
    {
        return $this->nextExecTime;
    }

    /**
     * 执行节点任务
     * 此处采用贪婪模式，即一次执行尽可能多的节点
     * @return mixed
     * @throws \Scheduler\Exception\InvalidConfigException
     */
    protected function runNodes()
    {
        foreach ($this->nodes as $node) {
            if ($this->canNodeExec($node)) {
                try {
                    $this->runNode($node);
                } catch (\Exception $e) {
                    // 将节点设置为执行失败
                    $node->fail($e->getMessage(), $e->getTraceAsString());
                    Logger::emergency("工作流节点执行异常", ['workflow' => $this->name, 'node' => $node->name(), 'error' => $e->getMessage()]);
                }
            }
        }
    }

    /**
     * 执行前
     * @throws WorkFlowException
     */
    protected function pre()
    {
        if ($this->ttl <= 0) {
            throw new WorkFlowException("工作流{$this->name} ttl 已用完，无法继续执行");
        }

        $this->status = self::STATUS_DOING;
        $this->nextExecTime = 0;
    }

    /**
     * 执行后
     */
    protected function post()
    {
        $this->ttl--;

        // 对于执行完成的节点，需要处理相关节点
        $this->prepareFinRelNodes();

        // 对于延迟执行的节点，需要将依赖该节点的所有节点也设置为延迟执行
        $this->delayRelNodes();

        $this->status = $this->calcStatus();

        if ($this->status === self::STATUS_WAIT) {
            // wait 态需要计算 wait 的时间
            $this->nextExecTime = $this->calcNextExecTime();
        }

        Logger::debug("工作流{$this->name}本轮执行完成，状态：{$this->status},下次执行时间：{$this->nextExecTime}");
    }

    /**
     * 预处理已完成节点的下层关联节点：检查节点是否需要直接失败掉
     */
    protected function prepareFinRelNodes()
    {
        $finishedNodes = array_filter(
            $this->nodes,
            function (Node $node) {
                return $node->isFinished();
            }
        );

        foreach ($finishedNodes as $node) {
            $this->failRelNodes($node, $this->nodes);
        }
    }

    /**
     * 节点延迟执行级联处理，将延迟处理的下级相关节点都延迟处理
     */
    protected function delayRelNodes()
    {
        $delayedNodes = array_filter(
                $this->nodes,
                function (Node $node) {
                    return $node->isDelayed();
                }
            );

        foreach ($delayedNodes as $node) {
            foreach ($this->relationalNodes($node, $this->nodes) as $relNode) {
                if (!$relNode->isFinished() && $relNode->nextExecTime() < $node->nextExecTime()) {
                    $relNode->delayTo($node->nextExecTime());
                }
            }
        }
    }

    /**
     * 根据完成节点预失败关联节点
     * @param Node $blockNode
     * @param $nodePool
     */
    protected function failRelNodes(Node $blockNode, $nodePool)
    {
        static $tick = 0;

        if (!$nodePool || !$blockNode || !$blockNode->isFinished()) {
            return;
        }

        // 调用深度控制，防止因循环依赖而导致无限调用
        if ($tick++ > 1000) {
            Logger::warning("too deep call");
            return;
        }

        foreach ($nodePool as $key => $node) {
            if (!$node->isFinished() && $node->willBeBlocked($blockNode)) {
                // 被阻塞，强制失败
                Logger::warning("前置条件无法满足，强制失败", ['node' => $node->name(), 'relnode' => $blockNode->name()]);
                $node->fail("前置条件无法满足，强制失败", "相关节点：{$blockNode->name()}", true);
                $this->failRelNodes($node, $nodePool);
            }
        }
    }

    /**
     * 根据节点执行情况确定当前工作流状态：
     *  如果有一个节点处于执行状态，则工作流未完成
     *  如果所有节点都执行成功，则工作流结束，执行成功
     *  如果至少有一个节点执行失败，且后续工作流都依赖于这些失败的工作流而导致后续工作流都无法执行，则工作流结束，执行失败
     *  如果至少有一个节点执行失败，其它节点都执行成功，则工作流结束，执行失败
     * @return int
     */
    protected function calcStatus()
    {
        $failList = $initList = [];
        foreach ($this->nodes as $node) {
            // 只要有一个节点处于执行态，则工作流得等待继续执行
            if ($node->isExecute()) {
                return self::STATUS_WAIT;
            }

            if ($node->isSuc()) {
                continue;
            }

            // 节点执行失败或没有执行
            if ($node->isFail()) {
                $failList[] = $node;
            } else {
                $initList[] = $node;
            }
        }

        if (!$failList && !$initList) {
            return self::STATUS_SUC;
        }

        // 有失败的节点，没有未执行的节点，工作流结束：失败
        if ($failList && !$initList) {
            return self::STATUS_FAIL;
        }

        // 如果有节点失败了，则将 $initList 中受影响的节点剔除
        if ($failList) {
            foreach ($failList as $failNode) {
                // 剔除 $initList 中相关节点
                $initList = array_diff($initList, $this->relationalNodes($failNode, $initList));
            }

            if (!$initList) {
                return self::STATUS_FAIL;
            }
        }

        return self::STATUS_WAIT;
    }

    /**
     * 计算工作流下次执行的时间：取所有节点中最小 wait 时间，返回 0 表示需要立即执行，PHP_INT_MAX 表示不需要执行
     * @return int
     */
    protected function calcNextExecTime()
    {
        $nextTime = PHP_INT_MAX;
        foreach ($this->nodes as $node) {
            $nextTime = min($nextTime, $node->nextExecTime());
        }

        return $nextTime;
    }

    /**
     * 基于配置文件初始化工作流对象
     * @param string $name
     * @throws ClassNotFoundException
     * @throws InvalidConfigException
     * @throws \Scheduler\Exception\FileNotFoundException
     */
    protected function init(string $name)
    {
        $cfg = $this->normalizeConfig(Config::workflow($name));

        $this->validateConfig($cfg);

        $this->controller = new $cfg['controller']();
        $this->delay = $cfg['delay'];
        $this->maxRetryNum = $cfg['max_retry_num'];
        $this->maxDelayNum = $cfg['max_delay_num'];

        $this->initNodes($cfg);
    }

    /**
     * 初始化工作流节点对象
     * @param array $cfg
     */
    protected function initNodes(array $cfg)
    {
        if (!$cfg || !$cfg['nodes']) {
            return;
        }

        foreach ($cfg['nodes'] as $name => $nodeCfg) {
            $nodeCfg['delay'] = $nodeCfg['delay'] ?: $cfg['delay'];
            $nodeCfg['max_retry_num'] = $nodeCfg['max_retry_num'] ?: $cfg['max_retry_num'];
            $nodeCfg['max_delay_num'] = $nodeCfg['max_delay_num'] ?: $cfg['max_delay_num'];

            $this->nodes[$name] = new Node($name, $nodeCfg);
        }
    }

    /**
     * 节点是否可执行: 处于可执行态、未sleep、前置节点已就绪
     * 注意：前置节点处于delay、retry态时属于"中间态"，不会进入后置节点执行（因为其最终状态是不确定的）
     * @param Node $node
     * @return bool
     * @throws InvalidConfigException
     */
    protected function canNodeExec(Node $node)
    {
        // 正在执行或者已执行完成或者sleep 中的节点不可执行
        if ($node->isFinished() || $node->isExecuting() || $node->isSleep()) {
            return false;
        }

        // 前置节点是否满足条件，有一个不满足则不满足
        if ($conditions = $node->conditions()) {
            foreach (array_keys($conditions) as $preNodeName) {
                if (!($preNode = $this->nodes[$preNodeName])) {
                    throw new InvalidConfigException("前置节点不存在：{$preNodeName}");
                }

                if ($node->willBeBlocked($preNode)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 获取前置节点的响应数据
     * 基于配置 conditions
     * @param Node $node
     * @return array
     * @throws InvalidConfigException
     * @throws RunException
     */
    protected function getPrevNodeResponse(Node $node)
    {
        if (!($conditions = $node->conditions())) {
            return [];
        }

        $preResponse = [];
        foreach (array_keys($conditions) as $preNodeName) {
            if (!array_key_exists($preNodeName, $this->nodes)) {
                throw new InvalidConfigException("工作流{$this->name}节点{$preNodeName}不存在");
            }

            if (!$this->nodes[$preNodeName]->response()) {
                throw new RunException("工作流{$this->name}节点{$node->name()}的前置节点{$this->nodes[$preNodeName]}没有返回值");
            }

            $preResponse[$preNodeName] = $this->nodes[$preNodeName]->response();
        }

        return $preResponse;
    }

    /**
     * 获取节点池中受 $blockNode 影响的 $node 集合
     * @param Node $blockNode
     * @param array $nodePool
     * @return array 相关 Node
     */
    public function relationalNodes(Node $blockNode, array $nodePool)
    {
        static $tick = 0;

        if (!$nodePool || !$blockNode) {
            return [];
        }

        // 调用深度控制，防止因循环依赖而导致无限调用
        if ($tick++ > 1000) {
            return [];
        }

        $nodes = [];
        foreach ($nodePool as $key => $node) {
            if ($node->willBeBlocked($blockNode)) {
                // 被阻塞，记录并递归校验
                $nodes[] = $node;
                $nodes = array_merge($nodes, $this->relationalNodes($node, $nodePool));
            }
        }

        return array_unique($nodes);
    }

    /**
     * @param array $cfg
     * @return array
     * @throws InvalidConfigException
     */
    private function normalizeConfig(array $cfg)
    {
        if (!$cfg) {
            throw new InvalidConfigException("工作流{$this->name}没有有效的配置文件");
        }

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

    /**
     * @param array $cfg
     * @throws ClassNotFoundException
     * @throws InvalidConfigException
     */
    private function validateConfig(array $cfg)
    {
        if (!$cfg['controller']) {
            throw new InvalidConfigException("未提供工作流{$this->name}的controller");
        }

        if (!$cfg['nodes']) {
            throw new InvalidConfigException("未提供工作流{$this->name}的nodes");
        }

        if (!class_exists($cfg['controller'])) {
            throw new ClassNotFoundException("类{$cfg['controller']}不存在");
        }

        if ($this->hasLoopDependence($cfg)) {
            throw new InvalidConfigException("工作流{$this->name}的nodes存在循环依赖");
        }
    }

    /**
     * 节点循环依赖检测
     * @param array $cfg
     * @return bool
     */
    private function hasLoopDependence(array $cfg)
    {
        $dependences = array_map(
            function ($item) {
                return $item['conditions'] ? array_keys($item['conditions']) : [];
            },
            $cfg['nodes']
        );

        foreach (array_keys($dependences) as $nodeName) {
            if ($this->isLoopDepend($nodeName, $dependences)) {
                return true;
            }
        }

        return false;
    }

    private function isLoopDepend($startName, $dependList, $findName = null)
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
            $has |= $this->isLoopDepend($dependItem, $dependList, $findName);
        }

        return $has;
    }

    /**
     * 执行某个节点
     * @param Node $node
     * @return mixed
     */
    abstract protected function runNode(Node $node);
}
