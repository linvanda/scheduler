<?php

namespace Scheduler\Fundation\Signer;

/**
 * 签名器接口
 * Interface ISigner
 * @package Scheduler\Fundation\Signer
 */
interface ISigner
{
    /**
     * 对传入对参数进行签名
     * @param array $params
     * @return string
     */
    public function sign(array $params) : string;

    /**
     * 签名校验
     * @param $signStr
     * @param $params
     * @return string
     */
    public function validate($signStr, $params) : string;
}
