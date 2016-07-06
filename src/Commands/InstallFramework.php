<?php namespace Wbs\Commands;

use Exception;
use Symfony\Component\Console\Helper\ProgressBar;
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

    use TmpOperations, ShowsProgress;

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
            ->setDescription('Installs latest version of webasyst framework.')
            ->addArgument('name', InputArgument::OPTIONAL, 'Application folder name.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->init($input, $output);

        try
        {
            $this->assertAppDoesNotExist();

            $this->setDirectories();

            $this->installing('Installing webasyst framework...')
                ->download()
                ->extract()
                ->cleanUp()
                ->makeConfig()
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
        $response = $this->getLatestZipUrl();

        $this->progressStart('Download');

        $zipFileData = $this->client->get($response->zipball_url, [
            'progress' => $this->showProgress(),
        ])->getBody();

        $this->progressStop()
            ->progressClear();

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

        // progress bar
        $this->progressStart('Extracting', $zip->numFiles);

        for ($i = 0; $i < $zip->numFiles; $i++)
        {
            $zip->extractTo($this->tmpDir, $zip->getNameIndex($i));
            $this->progress->advance();
        }

        $this->progressStop()
            ->progressClear();

        $this->moveFilesToTarget($zip);

        $zip->close();

        return $this;
    }

    /**
     * Check if target directory name is already exists.
     */
    private function assertAppDoesNotExist()
    {
        $targetDir = $this->input->getArgument('name');

        if (($targetDir && is_dir($targetDir)))
        {
            $this->output->writeln("<error>App already exist!</error>");

            exit(1);
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function init(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->progress = new ProgressBar($this->output, self::DOWNLOAD_COUNT_MAX);
        $this->progress->setFormat("<comment>%message%:</comment> [%bar%] %percent%%");
    }

    /**
     * Set directories.
     */
    private function setDirectories()
    {
        $this->setTargetDir();

        $this->setTmpDir();
    }

    /**
     * @return mixed
     */
    private function getLatestZipUrl()
    {
        $latestUrl = 'https://api.github.com/repos/webasyst/webasyst-framework/releases/latest';
        $jsonResponse = $this->client->get($latestUrl)->getBody();
        $response = json_decode($jsonResponse);

        return $response;
    }

    /**
     * @param $zip
     */
    private function moveFilesToTarget(&$zip)
    {
        $shopFolderName = $this->getZipFirstDirPath($zip);
        $filesToMove = scandir($this->tmpDir . DIRECTORY_SEPARATOR . $shopFolderName, 1);
        $progressSteps = (count($filesToMove) - 2);

        $this->progressStart('Moving files', $progressSteps);

        foreach ($filesToMove as $file)
        {
            if ($file == '.' || $file == '..') continue;

            $source = $this->tmpDir . DIRECTORY_SEPARATOR . "{$shopFolderName}{$file}";
            $destination = $this->targetDir . DIRECTORY_SEPARATOR . $file;

            if (is_readable($source))
            {
                @rename($source, $destination);
            }

            $this->progress->advance();
        }

        $this->progressStop()
            ->progressClear();
    }

    /**
     * Set target directory for framework.
     *
     * @throws Exception
     */
    private function setTargetDir()
    {
        $this->targetDir = ($this->input->getArgument('name')) ?
            getcwd() . DIRECTORY_SEPARATOR . $this->input->getArgument('name') : getcwd();

        if ( ! is_dir($this->targetDir))
        {
            if ( ! mkdir($this->targetDir))
            {
                throw new Exception('Can\'t create target directory - ' . $this->targetDir);
            }
        }
    }

    /**
     * Copying config files.
     *
     * @return $this
     */
    private function makeConfig()
    {
        $cpFiles = [
            'apps.php.example'               => 'apps.php',
            'config.php.example'             => 'config.php',
            'locale.php.example'             => 'locale.php',
            'SystemConfig.class.php.example' => 'SystemConfig.class.php',
        ];

        $configPath = $this->targetDir . DIRECTORY_SEPARATOR . 'wa-config';

        foreach ($cpFiles as $example => $fileName)
        {
            if( ! is_file($configPath . DIRECTORY_SEPARATOR . $fileName)) {
                $exampleFile = $configPath . DIRECTORY_SEPARATOR . $example;
                $configFile = $configPath . DIRECTORY_SEPARATOR . $fileName;
                copy($exampleFile, $configFile);
            }
        }

        // copy db file with default credentials
        $stubsPath = $this->getWbsCommandDir() .
            DIRECTORY_SEPARATOR . 'src' .
            DIRECTORY_SEPARATOR . 'stubs' .
            DIRECTORY_SEPARATOR . 'wa-config';

        $stubFile = $stubsPath . DIRECTORY_SEPARATOR . 'db.php.stub';
        $configDbFile = $configPath . DIRECTORY_SEPARATOR . 'db.php';
        copy($stubFile, $configDbFile);

        $this->info('Config files copied.');

        return $this;
    }
}
