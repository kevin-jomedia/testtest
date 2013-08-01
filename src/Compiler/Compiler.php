<?php

/*
 * This file is part of the Compiler package.
 *
 * (c) 2013 Kevin Simard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Compiler;

use Compiler\Exception;
use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\AssetManager;
use Assetic\AssetWriter;
use Assetic\Factory\AssetFactory;
use Assetic\Filter\Sass\SassFilter;
use Assetic\Filter\Sass\ScssFilter;
use Assetic\Filter\Yui;
use Assetic\FilterManager;

/**
 * Compiles files to a single compressed CSS/JS file.
 *
 * @author Kevin Simard <ksimard@outlook.com>
 */
class Compiler
{
    /**
     * @var AssetCollection List of files that will be combined
     */
    private $assetCollection;

    /**
     * @var array List of options
     */
    private $options;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->assetCollection = new AssetCollection();

        $this->options["debug"] = true;
        $this->options["sassCompilerPath"] = "/usr/bin/sass";
        $this->options["googleClosureJarPath"] = "/usr/share/closure-compiler/compiler.jar";
        $this->options["yuiJarPath"] = "/usr/share/yui-compressor/yui-compressor.jar";
    }

    /**
     * Adds a file to the AssetCollection from file path
     * 
     * @param string $filePath File path
     */
    public function addFileAsset($filePath)
    {
        $filters = $this->getFiltersFromFileExtension($filePath);
        $fileAsset = new FileAsset($filePath, $filters);
        $this->assetCollection->add($fileAsset);

        return $this;
    }

    /**
     * Adds files to the AssetCollection from pattern
     * 
     * @param string $globPath Path to files with a pattern
     */
    public function addGlobAsset($globPath)
    {
        $files = $this->getFilesFromPattern($globPath);

        foreach ($files as $filePath) {
            $this->addFile($filePath);
        }

        return $this;
    }

    /**
     * Adds a file to the AssetCollection from url
     * 
     * @param string $url File url
     */
    public function addHttpAsset($url) {
        $filters = $this->getFiltersFromFileExtension($url);
        $httpAsset = new HttpAsset($filePath, $filters);
        $this->assetCollection->add($httpAsset);

        return $this;
    }

    /**
     * Sets an option that will overwrite the default option
     * 
     * @param string $key   Option name
     * @param string $value Option value
     */
    public function setOption($key, $value)
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * Sets options
     * 
     * @param array $options List of options that will overwrite the default options
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * Compiles/Compress files into one single file
     */
    public function write($targetPath)
    {
        $filterManager = new FilterManager();
        $filterManager->set('yuiCssCompressor', 
            new Yui\CssCompressorFilter($this->options["yuiJarPath"])
        );
        $filterManager->set("yuiJsCompressor", 
            new Yui\JsCompressorFilter($this->options["yuiJarPath"])
        );

        $assetManager = new AssetManager();
        $assetManager->set("files", $this->assetCollection);

        $assetFactory = new AssetFactory(__DIR__);
        $assetFactory->setFilterManager($filterManager);
        $assetFactory->setAssetManager($assetManager);
        $assetFactory->setDebug($this->options["debug"]);

        $asset = $assetFactory->createAsset(
            array("@files"), 
            // '?' Symbol prevent compression when in debug mode
            array("?" . $this->getCompressorByTargetExtension($targetPath))
        );

        $asset->setTargetPath($targetPath);

        $assetWriter = new AssetWriter(__DIR__);
        $assetWriter->writeAsset($asset);

        return $this;
    }

    /**
     * Returns list of filters based on file extension
     * 
     * @param  string $filePath File path
     * 
     * @return array List of Assetic\Filter\BaseCssFilter
     */
    private function getFiltersFromFileExtension($filePath)
    {
        $fileExt = pathinfo($filePath, PATHINFO_EXTENSION);

        switch ($fileExt) {
            case "sass":
                return array(new SassFilter($this->options["sassCompilerPath"]));
            case "scss":
                return array(new ScssFilter($this->options["sassCompilerPath"]));
            case "less"
                return array(new LessFilter());
            case "coffee"
                return array(new CoffeeScriptFilter());
            default:
                return array();
        }
    }

    /**
     * Returns all files that matched the pattern
     * 
     * @param  string $dir     The root directory
     * @param  string $pattern The pattern to find files
     * 
     * @return array List of files that matched the given pattern
     */
    private function getFilesFromPattern($dir, $pattern = "*")
    {
        $pattern = basename($pattern);
        $files = glob($dir . $pattern);
        $paths = glob($dir . "*", GLOB_MARK | GLOB_ONLYDIR | GLOB_NOSORT);

        foreach ($paths as $path) {
            if (!is_link($path)) {
                $files = array_merge($files, $this->getFilesFromPattern($path, $pattern));
            }
        }

        return $files;
    }

    /**
     * Returns compressor name based on target's extension
     * 
     * @param  string $targetPath Target path
     * 
     * @return string Compressor name
     *
     * @throws InvalidTargetExtension If extension is not one of the followings (css|js)
     */
    private function getCompressorByTargetExtension($targetPath)
    {
        $targetExt = pathinfo($targetPath, PATHINFO_EXTENSION);

        switch ($targetExt) {
            case "css":
                return "yuiCssCompressor";
            case "js":
                return "yuiJsCompressor";
            default:
                throw new InvalidTargetExtException();
        }
    }
}
