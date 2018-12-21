<?php

namespace Scheduler\Infrastructure;


/**
 * 基础路由器
 * Class Scheduler\Infrastructure\Router
 */
class Router implements IRouter
{
    protected $workflow;
    protected $request;

    /**
     * Router constructor.
     * 请求体结构：
     * [
     *      'workflow' => '...',
     *      'data' => [...],
     *      ...
     * ]
     * @param string|array|\Swoole\Http\Request $request 原始请求参数
     */
    public function __construct($request)
    {
        if (!$request) {
            throw new \InvalidArgumentException("request参数缺失");
        }

        $request = $this->parseRequest($request);

        if (!$request) {
            throw new \InvalidArgumentException("请求参数为空");
        }

        if (!$this->validateRequest($request)) {
            throw new \InvalidArgumentException("请求参数校验失败：" . print_r($request, true));
        }

        $this->request = new Request($request['data']);
        $this->workflow = $request['workflow'];

        if (!$this->workflow) {
            throw new \InvalidArgumentException("未指定工作流名称:" . print_r($request, true));
        }
    }

    /**
     * 工作流名称
     * @return string
     */
    public function workflow()
    {
        return $this->workflow;
    }

    /**
     * @return Request
     */
    public function request()
    {
        return $this->request;
    }

    /**
     * 解析请求参数
     * @param $request
     * @throws \InvalidArgumentException
     * @return array|string
     */
    protected function parseRequest($request)
    {
        if (is_array($request)) {
            return $request;
        } elseif (is_string($request)) {
            return json_decode($request, true);
        } elseif ($request instanceof \Swoole\Http\Request) {
            if ($request->post) {
                return $request->post;
            } elseif ($request->get) {
                return $request->get;
            }

            if ($request->header['content-type'] === 'application/json') {
                return json_decode($request->rawcontent(), true);
            }
        }

        throw new \InvalidArgumentException("请求参数不合法:" . $request->rawcontent());
    }

    /**
     * 校验请求参数
     * @param array $request
     * @return bool
     */
    protected function validateRequest(array $request)
    {
        if (!is_array($request) || !$request['workflow'] || !$request['data']) {
            return false;
        }

        return true;
    }
}
