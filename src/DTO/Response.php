<?php

namespace Weiche\Scheduler\DTO;

/**
 * 节点响应数据
 *
 * Class Response
 * @package Weiche\Scheduler\DTO
 */
class Response
{
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
