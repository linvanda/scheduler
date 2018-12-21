<?php

namespace Scheduler\Infrastructure\Signer;
use Scheduler\Utils\Config;

/**
 * 喂车签名器
 * Class WeicheSigner
 * @package Scheduler\Infrastructure\Signer
 */
class WeicheSigner implements ISigner
{
    private $subSystem;

    /**
     * WeicheSigner constructor.
     * @param int|string $subSystemIdOrAlias
     * @throws \Scheduler\Exception\FileNotFoundException
     */
    public function __construct($subSystemIdOrAlias)
    {
        $this->subSystem = Config::subSystem($subSystemIdOrAlias);
    }

    /**
     * 对传入对参数进行签名
     * @param array $params
     * @return string
     */
    public function sign(array $params): string
    {
        ksort($params);
        return md5(http_build_query($params) . $this->subSystem['secret']);
    }

    /**
     * 签名校验
     * @param $signStr
     * @param $params
     * @return string
     */
    public function validate($signStr, $params): string
    {
        return $signStr == $this->sign($params);
    }
}
