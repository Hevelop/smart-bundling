<?php

namespace Hevelop\SmartBundling\Bundle;

use Magento\Deploy\Config\BundleConfig;
use Magento\Deploy\Package\BundleInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\File\WriteInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\View\Asset\Minification;

/**
 * RequireJs static files bundle object
 *
 * All files added will be bundled to multiple bundle files compatible with RequireJS AMD format
 */
class RequireJs implements BundleInterface
{
    /**
     * Static files Bundling configuration class
     *
     * @var BundleConfig
     */
    private $bundleConfig;

    /**
     * Helper class for static files minification related processes
     *
     * @var Minification
     */
    private $minification;

    /**
     * Static content directory writable interface
     *
     * @var WriteInterface
     */
    private $staticDir;

    /**
     * Package area
     *
     * @var string
     */
    private $area;

    /**
     * Package theme
     *
     * @var string
     */
    private $theme;

    /**
     * Package locale
     *
     * @var string
     */
    private $locale;

    /**
     * Bundle content pools
     *
     * @var string[]
     */
    private $contentPools = [
        'js' => 'jsbuild',
        'html' => 'text'
    ];

    /**
     * Files to be bundled
     *
     * @var array[]
     */
    private $files = [
        'jsbuild' => [],
        'text' => []
    ];

    /**
     * Files content cache
     *
     * @var string[]
     */
    private $fileContent = [];

    /**
     * Incremental index of bundle file
     *
     * Chosen bundling strategy may result in creating multiple bundle files instead of one
     *
     * @var int
     */
    private $bundleFileIndex = 0;

    /**
     * Relative path to directory where bundle files should be created
     *
     * @var string
     */
    private $pathToBundleDir;

    /**
     * Bundle constructor
     *
     * @param Filesystem $filesystem
     * @param BundleConfig $bundleConfig
     * @param Minification $minification
     * @param string $area
     * @param string $theme
     * @param string $locale
     * @param array $contentPools
     */
    public function __construct(
        Filesystem $filesystem,
        BundleConfig $bundleConfig,
        Minification $minification,
        $area,
        $theme,
        $locale,
        array $contentPools = []
    ) {
        $this->filesystem = $filesystem;
        $this->bundleConfig = $bundleConfig;
        $this->minification = $minification;
        $this->staticDir = $filesystem->getDirectoryWrite(DirectoryList::STATIC_VIEW);
        $this->area = $area;
        $this->theme = $theme;
        $this->locale = $locale;
        $this->contentPools = array_merge($this->contentPools, $contentPools);
        $this->pathToBundleDir = $this->area . '/' . $this->theme . '/' . $this->locale . '/' . BundleInterface::BUNDLE_JS_DIR;
    }

    /**
     * @inheritDoc
     */
    public function addFile($filePath, $sourcePath, $contentType)
    {
        if (!$this->staticDir->isExist($this->minification->addMinifiedSign($sourcePath))) {
            return false;
        }
        // all unknown content types designated to "text" pool
        $contentPoolName = isset($this->contentPools[$contentType]) ? $this->contentPools[$contentType] : 'text';
        $this->files[$contentPoolName][$filePath] = $sourcePath;
        return true;
    }

    /**
     * @inheritDoc
     */
    public function flush()
    {
        $this->flushWithName();
    }

    /**
     * @inheritDoc
     */
    public function flushWithName($bundleName = null)
    {
        $bundleFile = $this->startNewBundleFile($bundleName);
        foreach ($this->files as $contentPoolName => $files) {
            $content = [];
            if (empty($files)) {
                continue;
            }
            $this->startNewBundlePool($bundleFile, $contentPoolName);
            foreach ($files as $filePath => $sourcePath) {
                $fileContent = $this->getFileContent($sourcePath);
                $content[$this->minification->addMinifiedSign($filePath)] = $fileContent;
            }
            $this->endBundlePool($bundleFile, $content);
        }
        if ($bundleFile) {
            $this->endBundleFile($bundleFile);
            $bundleFile->write($this->getInitJs());
        }


        $this->files = [];
    }

    /**
     * @inheritdoc
     */
    public function clear()
    {
        $this->staticDir->delete($this->pathToBundleDir);
    }

    /**
     * Create new bundle file and write beginning content to it
     *
     * @param string $contentPoolName
     * @param string|null $bundleName
     * @return WriteInterface
     */
    private function startNewBundleFile($bundleName = null)
    {
        $bundleNameToUse = 'bundle' . $this->bundleFileIndex . '.js';
        if ($bundleName) {
            $bundleNameToUse = $bundleName;
        }
        $bundleFile = $this->staticDir->openFile(
            $this->minification->addMinifiedSign($this->pathToBundleDir . '/' . $bundleNameToUse)
        );
        $bundleFile->write("require.config({\"config\": {\n");
        if ($bundleName) {
            ++$this->bundleFileIndex;
        }
        return $bundleFile;
    }

    /**
     * @param $name
     */
    private function startNewBundlePool($bundleFile, $contentPoolName)
    {
        $bundleFile->write("        \"{$contentPoolName}\":");
    }

    /**
     * @param $name
     */
    private function endBundlePool(WriteInterface $bundleFile, array $contents)
    {
        if ($contents) {
            $content = json_encode($contents, JSON_UNESCAPED_SLASHES);
            $bundleFile->write("{$content},\n");
        } else {
            $bundleFile->write("{},\n");
        }
    }

    /**
     * Write ending content to bundle file
     *
     * @param WriteInterface $bundleFile
     * @return bool true on success
     */
    private function endBundleFile($bundleFile)
    {
        $bundleFile->write("}});\n");
        return true;
    }

    /**
     * Get content of static file
     *
     * @param string $sourcePath
     * @return string
     */
    private function getFileContent($sourcePath)
    {
        if (!isset($this->fileContent[$sourcePath])) {
            $content = $this->staticDir->readFile($this->minification->addMinifiedSign($sourcePath));
            if (mb_detect_encoding($content) !== "UTF-8") {
                $content = mb_convert_encoding($content, "UTF-8");
            }

            $this->fileContent[$sourcePath] = $content;
        }
        return $this->fileContent[$sourcePath];
    }

    /**
     * Bundle initialization script content (this must be added to the latest bundle file at the very end)
     *
     * @return string
     */
    private function getInitJs()
    {
        return "require.config({\n" .
            "    bundles: {\n" .
            "        'mage/requirejs/static': [\n" .
            "            'jsbuild',\n" .
            "            'buildTools',\n" .
            "            'text',\n" .
            "            'statistician'\n" .
            "        ]\n" .
            "    },\n" .
            "    deps: [\n" .
            "        'jsbuild'\n" .
            "    ]\n" .
            "});\n";
    }
}
