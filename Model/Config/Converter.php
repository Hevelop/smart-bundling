<?php

namespace Hevelop\SmartBundling\Model\Config;

class Converter implements \Magento\Framework\Config\ConverterInterface
{
    /**
     * Convert dom node tree to array
     *
     * @param \DOMDocument $source
     * @return array
     */
    public function convert($source)
    {
        $result = [];
        /** @var \DOMNode $areaNode */
        foreach ($source->documentElement->childNodes as $areaNode) {
            if ($areaNode->nodeType != XML_ELEMENT_NODE) {
                continue;
            }
            $areaName = $areaNode->attributes->getNamedItem('name')->nodeValue;
            if (!array_key_exists($areaName, $result)) {
                $result[$areaName] = [];
            }
            /** @var \DOMNode $themeNode */
            foreach ($areaNode->childNodes as $themeNode) {
                if ($themeNode->nodeType != XML_ELEMENT_NODE) {
                    continue;
                }
                $themeName = $themeNode->attributes->getNamedItem('name')->nodeValue;
                if (!array_key_exists($themeName, $result)) {
                    $result[$areaName][$themeName] = [];
                }
                /** @var \DOMNode $actionNode */
                foreach ($themeNode->childNodes as $actionNode) {
                    if ($actionNode->nodeType != XML_ELEMENT_NODE) {
                        continue;
                    }
                    $actionName = $actionNode->nodeName == 'shared' ?
                        'shared' : $actionNode->attributes->getNamedItem('name')->nodeValue;
                    if (!array_key_exists($actionName, $result[$areaName])) {
                        $result[$areaName][$themeName][$actionName] = [];
                    }
                    /** @var DOMNode $fileNode */
                    foreach ($actionNode->childNodes as $fileNode) {
                        if ($fileNode->nodeType != XML_ELEMENT_NODE) {
                            continue;
                        }
                        $result[$areaName][$themeName][$actionName][] = $fileNode->nodeValue;
                    }
                }
            }
        }
        return $result;
    }
}
