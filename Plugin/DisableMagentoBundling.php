<?php

namespace Hevelop\SmartBundling\Plugin;

use Magento\Framework\View\Asset\ConfigInterface;
use Hevelop\SmartBundling\Helper\Config;

class DisableMagentoBundling
{
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function afterIsBundlingJsFiles(ConfigInterface $subject, $result)
    {
        if ($this->config->isSmartBundling()) {
            return false;
        }
        return $result;
    }
}