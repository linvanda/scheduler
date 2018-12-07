<?php

namespace Weiche\Scheduler\Controller;

use Weiche\Scheduler\DTO\Request;
use Weiche\Scheduler\DTO\Response;
use Weiche\Scheduler\Exception\InvalidCallException;

/**
 * 工作流处理程序基类
 *
 * Class Controller
 * @package Weiche\Scheduler\Controller
 */
class Controller
{
    /**
     * 处理程序入口方法
     *
     * @param string $actionName 真正的 action 方法名称
     * @param Request $request 工作流原始数据对象
     * @param array $prevResponses 前置（条件）工作流的输出对象数组，格式：['nodeName' => Response]
     * @return Response
     * @throws InvalidCallException
     */
    public function handler(string $actionName, Request $request, array $prevResponses = []): Response
    {
        if (!method_exists($this, $actionName)) {
            throw new InvalidCallException("类" . get_called_class() . "没有方法{$actionName}");
        }

        $this->pre($actionName, $request, $prevResponses);
        $response = $this->$actionName($request, $prevResponses);
        $this->post($actionName, $request, $prevResponses, $response);

        return $response;
    }

    /**
     * 前置钩子。子类可重写
     * @param string $actionName
     * @param Request $request
     * @param array $prevResponses
     * @return bool
     */
    protected function pre(string $actionName, Request $request, array $prevResponses = [])
    {
        //TODO
    }

    /**
     * 后置钩子，子类可重写
     * @param string $actionName
     * @param Request $request
     * @param array $prevResponses
     * @param Response $response action 执行结果
     */
    protected function post(string $actionName, Request $request, array $prevResponses = [], Response $response = null)
    {
        //TODO
    }
}
