<?php namespace Acme\Commands;

use DirectoryIterator;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use GuzzleHttp\Client;
use ZipArchive;

/**
 * Class DevWebasyst
 * @package Acme\Commands
 */
class InstallFramework extends Command
{
    /**
     * @var Client
     */
    private $client;

    /**
     * Input directory.
     *
     * @var
     */
    private $dir;

    /**
     * Working directory.
     *
     * @var
     */
    private $workingDir;

    /**
     * DevWebasyst constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->client = new Client;
    }

    /**
     * Configure "webasyst framework install" command.
     */
    public function configure()
    {
        $this->setName('pull:framework')
            ->setDescription('Install webasyst framework.')
            ->addArgument('dir', InputArgument::REQUIRED, 'Target directory name.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dir = $input->getArgument('dir');

        $this->setTmpWorkingDir();

        $targetDirName = $this->getTargetDirName();

        $this->assertAppDoesNotExist($targetDirName, $output);

        $this->installing($output)
            ->download($fileName = $this->makeFileName())
            ->extract($fileName, $targetDirName)
            ->finish($output);
    }

    /**
     * @return string
     */
    public function makeFileName()
    {
        return $this->workingDir . DIRECTORY_SEPARATOR . 'webasyst_' . md5(time() . uniqid()) . '.zip';
    }

    /**
     * Download archive.
     * @param  string $zipFile
     * @return $this
     */
    private function download($zipFile)
    {
        $response = $this->client->get('https://github.com/webasyst/webasyst-framework/archive/v1.5.zip')->getBody();

        file_put_contents($zipFile, $response);

        return $this;
    }

    /**
     * @param $zipFile
     * @param $directory
     * @return $this
     */
    private function extract($zipFile, $directory)
    {
        $zip = new ZipArchive;
        $tmpDir = $this->makeFrameworkTmpFolder();

        $zip->open($zipFile);
        $zip->extractTo($tmpDir);

        // detecting first level of archive
        // we only need contents of framework, no preceding folders.
        if (strpos($zip->getNameIndex(0), 'webasyst') !== false)
        {
            $tmpDir = $tmpDir . DIRECTORY_SEPARATOR . rtrim($zip->getNameIndex(0), "/");
        }

        $zip->close();

        rename($tmpDir, $directory);

        $this->cleanUp();

        return $this;
    }

    /**
     * @param $directory
     * @param OutputInterface $output
     */
    private function assertAppDoesNotExist($directory, OutputInterface $output)
    {
        if ($this->dir == '.' || $this->dir == '..')
        {
            $output->writeln("<error>Incorrect folder name!</error>");

            exit(1);
        }

        if (is_dir($directory))
        {
            $output->writeln("<error>App already exist!</error>");

            exit(1);
        }
    }

    /**
     * @return $this
     * @internal param $zipFile
     * @internal param $tmpExtractedFolder
     */
    private function cleanUp()
    {
        chown($this->workingDir, 0777);

        $this->deleteContent($this->workingDir);

        rmdir($this->workingDir);

        return $this;
    }

    /**
     * @param $output
     * @return $this
     */
    private function installing(OutputInterface $output)
    {
        $message = "<info>Installing webasyst framework.</info>";

        $output->writeln($message);

        return $this;
    }

    /**
     * @param $output
     * @return $this
     */
    private function finish(OutputInterface $output)
    {
        $output->writeln("<comment>Webasyst framework installed.</comment>");

        return $this;
    }

    /**
     * Get working directory.
     *
     * @return string
     */
    private function setTmpWorkingDir()
    {
        $tmpDirName = getcwd() . DIRECTORY_SEPARATOR . 'wbs_tmp';

        if ( ! is_dir($tmpDirName))
        {
            mkdir($tmpDirName);
        }

        $this->workingDir = $tmpDirName;
    }

    /**
     * @return string
     */
    private function getTargetDirName()
    {
        return $this->dir;
    }

    /**
     * @return string
     */
    private function makeFrameworkTmpFolder()
    {
        $tmpFolder = $this->workingDir . DIRECTORY_SEPARATOR . 'tmp_webasyst_framework' . md5(time() . uniqid());

        return $tmpFolder;
    }

    /**
     * Delete folder content.
     * @origin http://php.net/manual/ru/function.rmdir.php#116585
     *
     * @param $path
     * @param OutputInterface $output
     * @return bool
     */
    private function deleteContent($path, OutputInterface $output = null)
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
