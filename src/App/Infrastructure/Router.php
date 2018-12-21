<?php

namespace App\Infrastructure;

use Scheduler\Infrastructure\IRouter;
use Scheduler\Infrastructure\Request;


/**
 * 路由器
 * Class App\Infrastructure\Router
 */
class Router implements IRouter
{
    protected $workflow;
    protected $request;

    /**
     * Router constructor.
     * @param string|array $request 原始请求参数
     */
    public function __construct($request)
    {
        if (!$request) {
            throw new \InvalidArgumentException("request参数缺失");
        }

        $request = is_string($request) ? json_decode($request) : $request;

        if (!$request || !is_array($request)) {
            throw new \InvalidArgumentException("request参数格式不合法");
        }

        $this->validateParams($request);

        $request = $request['data'];
        $this->request = new Request($request['data']);
        $this->workflow = $request['workflow'];
    }

    public function workflow()
    {
        return $this->workflow;
    }

    public function request()
    {
        return $this->request;
    }

    /**
     * requestData:
     * [
     *      'token' => 'token',
     *      'app_id' => 'appid',
     *      'data' => [
     *          'workflow' => 'workflow_name',
     *          'data' => [真正对数据]
     *      ]
     * ]
     *
     * @param $requestData
     */
    protected function validateParams($requestData)
    {
        $token  = $requestData['token'];
        $appId = $requestData['app_id'];
        $data = $requestData['data'];

        if (!$token || !$appId ||!$data || !is_array($data) || !$data['workflow'] || !$data['data']) {
            throw new \InvalidArgumentException("request参数格式不合法。" . print_r($requestData, true));
        }

        if (!(new WeicheSigner($appId))->validate($token, $data)) {
            throw new \InvalidArgumentException("request参数校验失败。" . print_r($requestData, true));
        }
    }
}
