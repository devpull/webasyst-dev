<?php


namespace Wbs\Commands;

use Exception;
use GuzzleHttp\Exception\ConnectException;
use Symfony\Component\Console\Command\Command;

/**
 * Class WebasystCommand
 * @package Commands
 */
abstract class WebasystCommand extends Command
{
    /**
     * @var string
     */
    protected $workingDir;

    /**
     * @var string
     */
    public $tmpDir;

    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * @var \GuzzleHttp\Client;
     */
    protected $client;


    /**
     * Webasyst framework is required.
     *
     * @return $this
     */
    protected function assertFrameworkInstalled()
    {
        $this->detectFramework();

        $this->output->writeln("<info>Framework check - OK</info>");

        return $this;
    }

    /**
     * Maker sure shop app is not installed already.
     *
     * @return $this
     */
    protected function assertShopIsNotInstalled()
    {
        $shopScriptSignature = $this->workingDir . DIRECTORY_SEPARATOR . 'wa-apps' . DIRECTORY_SEPARATOR . 'shop';

        if (is_dir($shopScriptSignature))
        {
            $this->output->writeln("<error>Shop-Script is already installed!</error>");

            exit(1);
        }

        return $this;
    }

    /**
     * Installing indication.
     *
     * @param $message
     * @return $this
     */
    protected function installing($message)
    {
        $message = "<comment>Installing {$message}</comment>";

        $this->output->writeln($message);

        return $this;
    }

    /**
     * Get current working dir.
     *
     * @return string
     */
    protected function getWorkingDir()
    {
        return getcwd() . DIRECTORY_SEPARATOR;
    }

    /**
     * Make unique file name.
     *
     * @return string
     */
    protected function makeFileName()
    {
        return $this->workingDir . DIRECTORY_SEPARATOR . uniqid('shop') . '.zip';
    }

    /**
     * Current working directory is where executed.
     */
    protected function setWorkingDir()
    {
        $this->workingDir = getcwd();
    }

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
     * @throws Exception
     */
    protected function detectFramework()
    {
        if( ! is_dir(getcwd() . DIRECTORY_SEPARATOR . 'wa-apps')) {
            throw new Exception('Webasyst framework is not installed in current directory!');
        }

        return $this;
    }

    /**
     * @param $message
     * @return $this
     */
    protected function comment($message)
    {
        $this->output->writeln("<comment>{$message}</comment>");

        return $this;
    }

    /**
     * @param $message
     * @return $this
     */
    protected function error($message)
    {
        $this->output->writeln("<error>{$message}</error>");

        return $this;
    }

    /**
     * @param $message
     * @return $this
     */
    protected function info($message)
    {
        $this->output->writeln("<info>{$message}</info>");

        return $this;
    }

    /**
     * @param $message
     * @return $this
     */
    protected function done($message='Done')
    {
        $this->output->writeln("<info>{$message}</info>");

        return $this;
    }
}