<?php


namespace Wbs\Commands;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class WebasystCommand
 * @package Commands
 */
abstract class WebasystCommand extends Command
{
    use TmpOperations;

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
     * @var ProgressBar
     */
    protected $progress;

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * Progress count max
     */
    const DOWNLOAD_COUNT_MAX = 100;

    /**
     * WebasystCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->fs = new Filesystem;
    }

    /**
     * Webasyst framework is required.
     * @return $this
     * @throws Exception
     */
    protected function assertFrameworkInstalled()
    {
        if( ! $this->hasFrameworkIn(getcwd())) {
            throw new Exception('Webasyst framework is not installer');
        }

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

            $this->cleanUp();

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
    protected function installing($message='Installing...')
    {
        $message = "<comment>{$message}</comment>";

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
        return $this->tmpDir . DIRECTORY_SEPARATOR . uniqid('shop') . '.zip';
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
    protected function done($message='Done.')
    {
        $this->output->writeln("<info>{$message}</info>");

        return $this;
    }

    /**
     * Installation complete message.
     *
     * @param string $message
     * @return $this
     */
    protected function finish($message = "Installation complete.")
    {
        $this->output->writeln("<comment>{$message}</comment>");

        return $this;
    }

    /**
     * Get "webasyst" command file directory path.
     *
     * @return mixed
     */
    protected function getWbsCommandDir()
    {
        $executables = get_included_files();
        $pathInfo = pathinfo($executables[0]);
        $wbsCommandDir = $pathInfo['dirname'];

        return $wbsCommandDir;
    }

    /**
     * Detect framework in current working directory.
     *
     * @param $path
     * @return bool
     */
    private function hasFrameworkIn($path)
    {
        $aSignature = ['wa.php', 'wa-apps', 'wa-config'];

        $dirList = scandir($path);

        foreach ($dirList as $dirItem)
        {
            if (in_array($dirItem, $aSignature))
            {
                return true;
            }
        }

        return false;
    }
}