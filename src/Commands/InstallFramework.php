<?php namespace Wbs\Commands;

use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use GuzzleHttp\Client;
use ZipArchive;

/**
 * Class DevWebasyst
 * @package Acme\Commands
 */
class InstallFramework extends WebasystCommand
{

    use TmpOperations;

    /**
     * @var Client
     */
    private $client;

    /**
     * Input directory.
     *
     * @var
     */
    private $targetDir;


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
            ->addArgument('dir', InputArgument::OPTIONAL, 'Target directory for framework.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try
        {
            $this->init($input, $output);

            $this->assertAppDoesNotExist();

            $this->setDirectories();

            $this->installing('Installing webasyst framework...')
                ->download()
                ->extract()
                ->cleanUp()
                ->finish('Framework installed.');
        } catch (Exception $e)
        {
            $this->error($e->getMessage());
            exit(1);
        }
    }

    /**
     * Download archive.
     * @param string $fileName
     * @return $this
     * @throws Exception
     */
    private function download($fileName = 'latest.zip')
    {
        $latestUrl = 'https://api.github.com/repos/webasyst/webasyst-framework/releases/latest';
        $jsonResponse = $this->client->get($latestUrl)->getBody();

        $response = json_decode($jsonResponse);
        $zipFileData = $this->client->get($response->zipball_url)->getBody();

        if ( ! file_put_contents($this->tmpDir . DIRECTORY_SEPARATOR . $fileName, $zipFileData))
        {
            throw new Exception('Can\'t save release file.');
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function extract()
    {
        $zip = new ZipArchive;
        $zip->open($this->tmpDir . DIRECTORY_SEPARATOR . 'latest.zip');

        $shopFolderName = $zip->getNameIndex(0);
        $zip->extractTo($this->tmpDir);

        $filesToMove = scandir($this->tmpDir . DIRECTORY_SEPARATOR . $shopFolderName, 1);

        foreach ($filesToMove as $file)
        {
            if ($file == '.' || $file == '..') continue;

            $source = $this->tmpDir . DIRECTORY_SEPARATOR . "{$shopFolderName}{$file}";
            $destination = $this->targetDir . DIRECTORY_SEPARATOR . $file;

            $this->output->writeln("copying: {$source} to: {$destination}");

            if (is_readable($source))
            {
                rename($source, $destination);
            }
        }

        $zip->close();

        return $this;
    }

    /**
     * Check if target directory name is already exists.
     */
    private function assertAppDoesNotExist()
    {
        $targetDir = $this->input->getArgument('dir');

        $cwdHasFramework = $this->cwdHasFramework();

        if (($targetDir && is_dir($targetDir)) || $cwdHasFramework)
        {
            $this->output->writeln("<error>App already exist!</error>");

            exit(1);
        }
    }

    /**
     * Signal finish.
     *
     * @param string $message
     * @return $this
     */
    private function finish($message = "Installation finished.")
    {
        $this->output->writeln("<comment>{$message}</comment>");

        return $this;
    }

    /**
     * @return string
     */
    private function getTargetDirName()
    {
        return $this->targetDir;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function init(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * Set directories.
     */
    private function setDirectories()
    {
        $this->targetDir = ($this->input->getArgument('dir')) ?
            getcwd() . DIRECTORY_SEPARATOR . $this->input->getArgument('dir') : getcwd();

        if ( ! is_dir($this->targetDir))
        {
            if ( ! mkdir($this->targetDir))
            {
                throw new Exception('Can\'t create directory - ' . $this->targetDir);
            }
        }

        $this->setTmpDir();
    }

    /**
     * Detect framework in current working directory.
     *
     * @return bool
     */
    private function cwdHasFramework()
    {
        $aSignature = ['wa.php', 'wa-apps', 'wa-config'];

        $dirList = scandir('.');

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
