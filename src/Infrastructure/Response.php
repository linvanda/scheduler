<?php

namespace Weiche\Scheduler\Infrastructure;

/**
 * 节点响应数据
 *
 * Class Response
 * @package Weiche\Scheduler\Infrastructure
 */
class Response
{
    /**
     * 返回状态码定义，三位数字。处理程序或者自定义 Response 类需要根据约定设置code，否则会出现无法预料的问题
     * 基类定义了一些默认的状态码常量
     * 20* 业务执行成功
     * 30* 需延迟执行，外界根据配置决定延迟多少时间执行
     * 40* 执行失败，需要重试，外界根据情况决定是否重试以及重试时间间隔
     * 50* 执行失败，且重试也无法解决，外界直接标记其为彻底失败
     */
    const CODE_OK = 200;
    const CODE_DELAY = 300;
    const CODE_FAIL = 400;
    const CODE_FATAL = 500;

    protected $code;
    protected $message;
    protected $data = [];

    public function __construct(int $code, array $data = [], string $message = '')
    {
        $this->code = $code;
        $this->data = $data;
        $this->message = $message;
    }

    /**
     * 响应码
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * 响应信息
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * 数据
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
}
