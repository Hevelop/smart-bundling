<?php

namespace Hevelop\SmartBundling\Service;

use Psr\Log\LoggerInterface;
use Magento\Deploy\Config\BundleConfig;
use Magento\Deploy\Package\BundleInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Utility\Files;
use Hevelop\SmartBundling\Bundle\RequireJs;
use Hevelop\SmartBundling\Bundle\RequireJsFactory;
use Hevelop\SmartBundling\Model\Config\Data;
use Hevelop\SmartBundling\Helper\Config;

class Bundle
{
    /**
     * Path to package subdirectory wher bundle files are located
     */
    const BUNDLE_JS_DIR = 'js/bundle';

    /**
     * Matched file extension name for JavaScript files
     */
    const ASSET_TYPE_JS = 'js';

    /**
     * Matched file extension name for template files
     */
    const ASSET_TYPE_HTML = 'html';

    /**
     * Matched file extension name for template files
     */
    const ASSET_TYPE_JSON = 'json';

    /**
     * Public static directory writable interface
     *
     * @var Filesystem\Directory\WriteInterface
     */
    private $pubStaticDir;

    /**
     * Factory for Bundle object
     *
     * @see BundleInterface
     * @var RequireJsFactory
     */
    private $bundleFactory;

    /**
     * Utility class for collecting files by specific pattern and location
     *
     * @var Files
     */
    private $utilityFiles;

    /**
     * @var BundleConfig
     */
    private $bundleConfig;

    /**
     * List of supported types of static files
     *
     * @var array
     * */
    public static $availableTypes = [
        self::ASSET_TYPE_JS,
        self::ASSET_TYPE_HTML,
        self::ASSET_TYPE_JSON
    ];

    /**
     * @var Data
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Config
     */
    private $configHelper;

    /**
     * Bundle constructor
     *
     * @param Filesystem $filesystem
     * @param RequireJsFactory $bundleFactory
     * @param BundleConfig $bundleConfig
     * @param Files $files
     * @param Data $config
     * @param LoggerInterface $logger
     * @param Config $configHelper
     */
    public
    function __construct(
        Filesystem $filesystem,
        RequireJsFactory $bundleFactory,
        BundleConfig $bundleConfig,
        Files $files,
        Data $config,
        LoggerInterface $logger,
        Config $configHelper
    ) {
        $this->pubStaticDir = $filesystem->getDirectoryWrite(DirectoryList::STATIC_VIEW);
        $this->bundleFactory = $bundleFactory;
        $this->bundleConfig = $bundleConfig;
        $this->utilityFiles = $files;
        $this->config = $config;
        $this->logger = $logger;
        $this->configHelper = $configHelper;
    }

    /**
     * Deploy bundles for the given area, theme and locale
     *
     * @param $subject
     * @param $proceed
     * @param string $area
     * @param string $theme
     * @param string $locale
     * @return void
     * @throws
     */
    public function aroundDeploy(
        $subject,
        callable $proceed,
        $area,
        $theme,
        $locale
    ) {
        if (!$this->configHelper->isSmartBundling()) {
            return $proceed($area, $theme, $locale);
        }

        /** @var RequireJs $bundle */
        $bundle = $this->bundleFactory->create([
            'area' => $area,
            'theme' => $theme,
            'locale' => $locale
        ]);

        // delete all previously created bundle files
        $bundle->clear();
        $packageDir = $this->pubStaticDir->getAbsolutePath($area . '/' . $theme . '/' . $locale);
        $skippedFiles = [];

        $themeData = $this->config->get($area . '/' . $theme);

        // If there isn't an exact match, then check if there is a regexp match
        if (!$themeData) {
            $areaData = $this->config->get($area);
            if (!$areaData) {
                return;
            }
            $themes = array_keys($areaData);
            if ($themes) {
                foreach ($themes as $themePattern) {
                    if (preg_match($themePattern, $theme) !== false) {
                        $themeData = $areaData[$themePattern];
                        break;
                    }
                }
            }
        }

        if (!$themeData) {
            return;
        }

        foreach ($themeData as $moduleName => $files) {
            foreach ($files as $filePath) {
                $sourcePath = $packageDir . '/' . $filePath;
                $contentType = pathinfo($filePath, PATHINFO_EXTENSION);
                if (!in_array($contentType, self::$availableTypes)) {
                    continue;
                }
                $added = $bundle->addFile(
                    $filePath,
                    $sourcePath,
                    $contentType
                );
                if (!$added) {
                    $skippedFiles [] = $sourcePath;
                }
            }
            $bundle->flushWithName($moduleName . '.js');
        }

        if (!empty($skippedFiles)) {
            $this->logger->warning(
                sprintf(
                    "There are skipped files for %s: %s",
                    $area . '/' . $theme,
                    implode(', ', $skippedFiles)
                )
            );
        }

    }
}
