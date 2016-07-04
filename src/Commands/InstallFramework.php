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

    use TmpOperations, ShowDownload;

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
        $this->init($input, $output);

//        $this->info('installing');
//
//        $pg1 = new ProgressBar($output, 3);
//        $pg1->start();
//        $pg1->advance();
//        $pg1->advance();
//        $pg1->advance();
//        sleep(1);
//        $pg1->finish();
//        sleep(1);
//        $pg1->clear();
//
//        $this->info('finished');
//
//        $this->info('installing');
//
//        $pg2 = new ProgressBar($output, 5);
//        $pg2->start();
//        $pg2->advance();
//        $pg2->advance();
//        $pg2->advance();
//        $pg2->advance();
//        sleep(1);
//        $pg2->advance();
//        sleep(1);
//        $pg2->finish();
//        $pg2->clear();
//
//        $this->info('finished');
//
//        exit();

        try
        {
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
        $response = $this->getLatestZipUrl();

        $this->startDownload();

        $zipFileData = $this->client->get($response->zipball_url, [
            'progress' => $this->showProgress(),
        ])->getBody();

        $this->stopDownload();
        $this->progress->clear();

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
        // progress bar
        $this->comment('Extracting...');

        $zip = new ZipArchive;
        $zip->open($this->tmpDir . DIRECTORY_SEPARATOR . 'latest.zip');

        // progress
        $this->progress = new ProgressBar($this->output, $zip->numFiles);
        $this->progress->start();

        for ($i=0; $i<$zip->numFiles; $i++) {
            $zip->extractTo($this->tmpDir, $zip->getNameIndex($i));
            $this->progress->advance();
        }

        $this->progress->finish();
        $this->progress->clear();

        $this->moveFilesToTarget($zip);

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

        $this->progress = new ProgressBar($this->output, self::DOWNLOAD_COUNT_MAX);
        $this->progress->setFormat("<comment>Downloading</comment> [%bar%] %percent%%");
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

        $progressSteps = (count($filesToMove)-2);

        $extractingProgress = new ProgressBar($this->output, $progressSteps);

        $extractingProgress->start();

        foreach ($filesToMove as $file)
        {
            if ($file == '.' || $file == '..') continue;

            $source = $this->tmpDir . DIRECTORY_SEPARATOR . "{$shopFolderName}{$file}";
            $destination = $this->targetDir . DIRECTORY_SEPARATOR . $file;

            if (is_readable($source))
            {
                rename($source, $destination);
            }

            $extractingProgress->advance();
        }

        $extractingProgress->finish();
        $extractingProgress->clear();
    }
}
