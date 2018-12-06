<?php

namespace Weiche\Scheduler\Workflow;

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

    /**
     * WorkFlow constructor.
     * @param string $name
     * @throws \Weiche\Scheduler\Exception\FileNotFoundException
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->status = self::STATUS_INIT;

        $this->init($name);
    }

    /**
     * 执行节点
     */
    public function run()
    {
        if ($this->preRun()) {
            $this->runNodes();
        }

        $this->postRun();
    }

    public function status()
    {
        return $this->status;
    }

    /**
     * 执行前的钩子，如果返回 false 则不会执行真正的工作流节点
     * @return bool
     */
    protected function preRun()
    {
        // 子类可以覆盖
        return true;
    }

    /**
     * 执行后的钩子
     */
    protected function postRun()
    {
        // 子类可以覆盖
    }

    /**
     * 基于配置文件初始化工作流对象
     * @param string $name
     * @throws \Weiche\Scheduler\Exception\FileNotFoundException
     */
    protected function init(string $name)
    {
        $cfg = Config::workflow($name);

        $this->maxRetryNum = $cfg['max_retry_num'] ?: 10;
    }

    /**
     * 子类需实现此方法实现节点执行
     * @return mixed
     */
    abstract protected function runNodes();
}
