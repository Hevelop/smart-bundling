<?php

namespace Hevelop\SmartBundling\Block\Html\Head;

use Hevelop\SmartBundling\Model\FileManager as BundleFileManager;
use Hevelop\SmartBundling\Helper\Config as ConfigHelper;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\RequireJs\Config as RequireJsConfig;
use Magento\Framework\App\State as AppState;
use Magento\Framework\View\Element\Context as ViewElementContext;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\RequireJs\Model\FileManager;
use Magento\Framework\View\Asset\Minification;

class Config extends \Magento\Framework\View\Element\AbstractBlock
{
    /**
     * @var FileManager
     */
    private $fileManager;

    /**
     * @var PageConfig
     */
    private $pageConfig;

    /**
     * @var AppState
     */
    private $appState;

    /**
     * @var DirectoryList
     */
    private $dir;

    /**
     * @var HttpRequest
     */
    private $httpRequest;

    /**
     * @var Minification
     */
    private $minification;

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @param ViewElementContext $context
     * @param AppState $appState
     * @param BundleFileManager $fileManager
     * @param PageConfig $pageConfig
     * @param DirectoryList $dir
     * @param HttpRequest $httpRequest
     * @param Minification $minification
     * @param ConfigHelper $configHelper
     * @param array $data
     */
    public function __construct(
        ViewElementContext $context,
        AppState $appState,
        BundleFileManager $fileManager,
        PageConfig $pageConfig,
        DirectoryList $dir,
        HttpRequest $httpRequest,
        Minification $minification,
        ConfigHelper $configHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->fileManager = $fileManager;
        $this->pageConfig = $pageConfig;
        $this->appState = $appState;
        $this->dir = $dir;
        $this->httpRequest = $httpRequest;
        $this->minification = $minification;
        $this->configHelper = $configHelper;
    }

    /**
     * Include specified AMD bundle as an asset on the page
     *
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    protected function _prepareLayout()
    {
        if ($this->appState->getMode() === AppState::MODE_DEVELOPER) {
            return parent::_prepareLayout();
        }

        if (!$this->configHelper->isSmartBundling()) {
            return parent::_prepareLayout();
        }

        $after = RequireJsConfig::REQUIRE_JS_FILE_NAME;
        if ($this->minification->isEnabled('js')) {
            $after = $this->minification->addMinifiedSign(
                $this->fileManager->getRequirejsMinResolverRelUrl()
            );
        }

        $assetCollection = $this->pageConfig->getAssetCollection();
        $staticDir = $this->dir->getPath('static');

        $shared = $this->fileManager->createSharedJsBundleAsset();
        $sharedBundleRelPath = $shared->getFilePath();

        $assetCollection->insert(
            $sharedBundleRelPath,
            $shared,
            $after
        );
        $after = $sharedBundleRelPath;

        $fullActionName = $this->httpRequest->getFullActionName();
        $bundleConfig = $this->fileManager->createJsBundleAsset($fullActionName);
        $pageSpecificBundleRelPath = $bundleConfig->getFilePath();
        $pageSpecificBundleRelPathMin = $this->minification->addMinifiedSign($pageSpecificBundleRelPath);
        $pageSpecificBundleAbsPath = $staticDir . "/" . $pageSpecificBundleRelPathMin;

        if (file_exists($pageSpecificBundleAbsPath)) {
            $assetCollection->insert(
                $pageSpecificBundleRelPath,
                $bundleConfig,
                $after
            );
            $after = $pageSpecificBundleRelPath;
        }

        $staticAsset = $this->fileManager->createStaticJsAsset();
        if ($staticAsset !== false) {
            $assetCollection->insert(
                $staticAsset->getFilePath(),
                $staticAsset,
                $after
            );
        }

        return parent::_prepareLayout();
    }
}
