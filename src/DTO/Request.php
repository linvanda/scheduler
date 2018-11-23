<?php

namespace Weiche\Scheduler\DTO;

/**
 * 请求数据
 *
 * Class Request
 * @package Weiche\Scheduler\DTO
 */
class Request
{
    protected $data = [];

    /**
     * IRequest constructor.
     * @param string|array $rawRequestData
     */
    public function __construct($rawRequestData)
    {
        $this->data = is_string($rawRequestData) ? json_decode($rawRequestData, true) : $rawRequestData;
    }

    /**
     * 请求数据
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
}
