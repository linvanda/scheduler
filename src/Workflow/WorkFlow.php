<?php

namespace Weiche\Scheduler\Workflow;

use Weiche\Scheduler\DTO\Request;
use Weiche\Scheduler\Exception\ClassNotFoundException;
use Weiche\Scheduler\Exception\InvalidConfigException;
use Weiche\Scheduler\Utils\Config;

/**
 * 工作流基类
 * Class WorkFlow
 * @package Weiche\Scheduler\Workflow
 */
abstract class WorkFlow
{
    // 初始化后尚未开始，等待执行
    const STATUS_INIT = 1;
    // 节点执行中
    const STATUS_DOING = 2;
    // 工作流最终执行成功
    const STATUS_SUC = 3;
    // 工作流最终执行失败
    const STATUS_FAIL = 4;

    // 工作流名称
    protected $name;
    // 当前状态
    protected $status;
    // 节点延迟执行的时间，Node 中可覆盖
    protected $delay;
    // 节点失败最大重试次数，Node 中可覆盖
    protected $maxRetryNum;
    // 最大延迟执行次数
    protected $maxDelayNum;
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
     * @throws \Weiche\Scheduler\Exception\FileNotFoundException
     */
    public function __construct(string $name, Request $request)
    {
        $this->name = $name;
        $this->request = $request;
        $this->status = self::STATUS_INIT;

        $this->init($name);
    }

    /**
     * 执行工作流
     */
    public function run()
    {
        if ($this->pre()) {
            $this->runNodes();
        }

        $this->post();
    }

    public function status()
    {
        return $this->status;
    }

    /**
     * 执行前的钩子，如果返回 false 则不会执行真正的工作流节点
     * @return bool
     */
    protected function pre()
    {
        // 子类可以覆盖
        return true;
    }

    /**
     * 执行后的钩子
     */
    protected function post()
    {
        // 子类可以覆盖
    }

    /**
     * 基于配置文件初始化工作流对象
     * @param string $name
     * @throws ClassNotFoundException
     * @throws InvalidConfigException
     * @throws \Weiche\Scheduler\Exception\FileNotFoundException
     */
    protected function init(string $name)
    {
        $cfg = Config::workflow($name);

        $cfg['delay'] = $cfg['delay'] ?: 5;
        $cfg['max_retry_num'] = $cfg['max_retry_num'] ?: 6;
        $cfg['max_delay_num'] = $cfg['max_delay_num'] ?: 5;

        if (!$cfg['controller']) {
            throw new InvalidConfigException("未提供工作流{$name}的controller");
        }

        if (!$cfg['nodes']) {
            throw new InvalidConfigException("未提供工作流{$name}的nodes");
        }

        if (!class_exists($cfg['controller'])) {
            throw new ClassNotFoundException("类{$cfg['controller']}不存在");
        }

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
            if (is_int($name) && is_string($nodeCfg)) {
                $name = $nodeCfg;
                $nodeCfg = [];
            }

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
            foreach ($conditions as $preNodeName => $preResponseCode) {
                if (!($preNode = $this->nodes[$preNodeName])) {
                    throw new InvalidConfigException("前置节点不存在：{$preNodeName}");
                }

                // 前置节点没执行完成，本节点不可执行
                if (!$preNode->isFinished()) {
                    return false;
                }

                if ($preResponseCode) {
                    if (is_string($preResponseCode) && strpos($preNode->response()->getCode(), rtrim($preResponseCode, '*')) !== 0) {
                        return false;
                    } elseif (is_array($preResponseCode) && !in_array($preNode->response()->getCode(), $preResponseCode)) {
                        return false;
                    }
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
     */
    protected function getPrevNodeResponse(Node $node)
    {
        return [];
    }

    /**
     * 子类需实现此方法实现节点执行
     * @return mixed
     */
    abstract protected function runNodes();
}
