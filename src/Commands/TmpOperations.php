<?php
namespace Wbs\Commands;

use Symfony\Component\Console\Output\OutputInterface;
use DirectoryIterator;
use Exception;
use ZipArchive;

/**
 * Trait TmpOperations
 * @package Commands
 */
trait TmpOperations
{
    /**
     * @param $zipFile
     * @param $targetDir
     * @param string $match
     * @return $this
     * @throws Exception
     */
    protected function extractFirstSubFolder($zipFile, $targetDir, $match='shop-script')
    {
        $zip = new ZipArchive;

        $zip->open($zipFile);
        $zip->extractTo($this->tmpDir);

        // detecting first level of archive
        // we only need contents of framework, no preceding folders.
        if (strpos($zip->getNameIndex(0), $match) !== false)
        {
            $tmpDir = $this->tmpDir . DIRECTORY_SEPARATOR . rtrim($zip->getNameIndex(0), "/");
        }

        $zip->close();

        if( ! is_dir($tmpDir)) {
            throw new \Exception("Folder that matches - \"$match\" not present.");
        }

        rename($tmpDir, $targetDir);

        return $this;
    }

    /**
     * @return $this
     */
    protected function cleanUp()
    {
        if( ! is_dir($this->tmpDir)) {
            return $this;
        }

        chown($this->tmpDir, 0777);

        $this->deleteContent($this->tmpDir);

        rmdir($this->tmpDir);

        return $this;
    }

    /**
     * Delete folder content.
     * @origin http://php.net/manual/ru/function.rmdir.php#116585
     *
     * @param $path
     * @param OutputInterface $output
     * @return bool
     */
    protected function deleteContent($path, OutputInterface $output = null)
    {
        try
        {
            $iterator = new DirectoryIterator($path);
            foreach ($iterator as $fileInfo)
            {
                if ($fileInfo->isDot()) continue;
                if ($fileInfo->isDir())
                {
                    if ($this->deleteContent($fileInfo->getPathname()))
                        @rmdir($fileInfo->getPathname());
                }
                if ($fileInfo->isFile())
                {
                    @unlink($fileInfo->getPathname());
                }
            }
        } catch (Exception $e)
        {
            if ($output instanceof OutputInterface)
            {
                $output->writeln("<error>Delete content error: {$e->getMessage()}</error>");
            }
        }

        return $this;
    }

    /**
     * @param ZipArchive $zip
     * @return mixed
     */
    protected function getZipFirstDirPath($zip)
    {
        $shopFolderName = $zip->getNameIndex(0);

        return $shopFolderName;
    }
}