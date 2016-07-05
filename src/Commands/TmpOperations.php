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