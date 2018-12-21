<?php

namespace Scheduler;

use Scheduler\Infrastructure\DelayResponse;
use Scheduler\Infrastructure\FailResponse;
use Scheduler\Infrastructure\FatalResponse;
use Scheduler\Infrastructure\OkResponse;
use Scheduler\Infrastructure\Request;
use Scheduler\Infrastructure\Response;
use Scheduler\Exception\InvalidCallException;

/**
 * 工作流处理程序（控制器）基类
 * 注意：如果控制器对外抛出任何异常，则该节点会执行失败，不会被重试，但不会影响其它不相干节点的执行
 * 如果需要重试，控制器需要返回相关 Response 对象(或者对应的数组格式)
 * 正常情况下，控制器不应该再对外抛出异常，应当将内部异常转化为相应的 Response
 *
 * Class Controller
 * @package Scheduler\Controller
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

        if ($response === true) {
            $response = ['code' => 200, 'msg' => 'suc', 'data' => []];
        }

        if (!$response) {
            $response = new FatalResponse([], "处理程序未返回任何结果");
        } elseif (is_array($response)) {
            $response = $this->arrayToResponse($response);
        }

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

    /**
     * 数组转响应对象
     * @param array $arr 格式：['code' => 200, 'msg' => '', 'data' => []]
     * @return Response
     */
    protected function arrayToResponse($arr)
    {
        if (!$arr || !$arr['code']) {
            return new FatalResponse([], "非法的返回结果：" . print_r($arr, true));
        }

        $arr['msg'] = $arr['msg'] ?? '';
        $arr['data'] = $arr['data'] ?? [];

        switch ($arr['code']) {
            case Response::CODE_OK:
                return new OkResponse($arr['data'], $arr['msg']);
            case Response::CODE_DELAY:
                return new DelayResponse($arr['data'], $arr['msg']);
            case Response::CODE_FAIL:
                return new FailResponse($arr['data'], $arr['msg']);
            case Response::CODE_FATAL:
                return new FatalResponse($arr['data'], $arr['msg']);
            default:
                return new Response($arr['code'], $arr['data'], $arr['msg']);
        }
    }
}
