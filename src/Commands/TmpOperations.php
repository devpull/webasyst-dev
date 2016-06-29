<?php
namespace Wbs\Commands;

use Symfony\Component\Console\Output\OutputInterface;
use DirectoryIterator;
use Exception;

/**
 * Trait TmpOperations
 * @package Commands
 */
trait TmpOperations
{
    /**
     * Get working directory.
     *
     * @return $this
     */
    public function setTmpDir()
    {
        $tmpDirName = getcwd() . DIRECTORY_SEPARATOR . 'wbs_tmp';

        if ( ! is_dir($tmpDirName))
        {
            mkdir($tmpDirName);
        }

        $this->tmpDir = $tmpDirName;

        return $this;
    }

    /**
     * @return $this
     */
    public function cleanUp()
    {
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
    public function deleteContent($path, OutputInterface $output = null)
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

            // write log
            return false;
        }

        return true;
    }
}