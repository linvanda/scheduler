<?php

namespace Weiche\Scheduler\Workflow;

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
    // 节点失败重试次数，Node 中可覆盖
    protected $maxRetryNum;
    // 节点集合
    protected $nodes = [];
    // 当前正在执行的节点
    protected $currentNode;
    // 工作流控制器（处理程序）
    protected $controller;

    /**
     * WorkFlow constructor.
     * @param string $name
     * @throws ClassNotFoundException
     * @throws InvalidConfigException
     * @throws \Weiche\Scheduler\Exception\FileNotFoundException
     */
    public function __construct(string $name)
    {
        $this->name = $name;
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
        $this->maxRetryNum = $cfg['max_retry_num'] ?: 10;

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
            $this->nodes[$name] = new Node($name, $nodeCfg, $cfg);
        }
    }

    /**
     * 子类需实现此方法实现节点执行
     * @return mixed
     */
    abstract protected function runNodes();
}
