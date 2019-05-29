<?php

namespace Hevelop\SmartBundling\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Config extends AbstractHelper
{
    const XML_PATH_SMART_BUNDLING = 'dev/js/enable_smart_bundling';

    public function isSmartBundling()
    {
        return (bool)$this->scopeConfig->isSetFlag(
            self::XML_PATH_SMART_BUNDLING
        );
    }
}