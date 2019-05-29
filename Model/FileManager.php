<?php

namespace Hevelop\SmartBundling\Model;

use Magento\Framework\App\State as AppState;
use Magento\Framework\View\Asset\File\FallbackContext as FileFallbackContext;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\RequireJs\Config;

class FileManager
{
    /**
     * @var AssetRepository
     */
    private $assetRepo;

    /**
     * @var FileFallbackContext
     */
    private $staticContext;

    /**
     * @var AppState
     */
    private $appState;

    /**
     * @param AssetRepository $assetRepo
     * @param AppState $appState
     */
    public function __construct(
        AssetRepository $assetRepo,
        AppState $appState
    ) {
        $this->assetRepo = $assetRepo;
        $this->appState = $appState;
        $this->staticContext = $assetRepo->getStaticViewFileContext();
    }

    /**
     * Create a view asset representing the JS bundle for the page
     *
     * @return \Magento\Framework\View\Asset\File
     */
    public function createJsBundleAsset($fullActionName)
    {
        // TODO: Just generate paths with underscores in the Chrome extension
        $formattedActionName = str_replace("_", "-", $fullActionName);
        $relPath = $this->staticContext->getConfigPath() . '/js/bundle/' . $formattedActionName . '.js';
        return $this->assetRepo->createArbitrary($relPath, '');
    }

    public function createSharedJsBundleAsset()
    {
        $relPath = $this->staticContext->getConfigPath() . '/js/bundle/shared.js';
        return $this->assetRepo->createArbitrary($relPath, '');
    }

    public function getMixinRelUrl()
    {
        return $this->staticContext->getPath() . '/' . Config::MIXINS_FILE_NAME;
    }

    public function getRequirejsMinResolverRelUrl()
    {
        return $this->staticContext->getPath() . '/' . Config::MIN_RESOLVER_FILENAME;
    }

    public function getRequirejsRelUrl()
    {
        return $this->staticContext->getPath() . '/' . Config::REQUIRE_JS_FILE_NAME;
    }

    public function getRequirejsConfigRelUrl()
    {
        return $this->staticContext->getPath() . '/' . Config::CONFIG_FILE_NAME;
    }

    /**
     * Create a view asset representing the static js functionality
     *
     * @return \Magento\Framework\View\Asset\File|false
     */
    public function createStaticJsAsset()
    {
        if ($this->appState->getMode() != AppState::MODE_PRODUCTION) {
            return false;
        }
        return $this->assetRepo->createAsset(Config::STATIC_FILE_NAME);
    }
}
