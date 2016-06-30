<?php

namespace Wbs\Commands;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use GuzzleHttp\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Class InstallShopScript
 * @package Wbs\Commands
 */
class InstallShopScript extends WebasystCommand
{
    use TmpOperations;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $releaseZip;

    /**
     * @var ProgressBar
     */
    private $progress;

    /**
     * Progress count max
     */
    const DOWNLOAD_COUNT_MAX = 500;

    /**
     * Application directory for shop-script is always "shop".
     */
    const TARGET_DIR_NAME = 'shop';

    /**
     * InstallShopScript constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->workingDir = $this->getWorkingDir();
        $this->client = new Client;
    }

    public function configure()
    {
        $this->setName('pull:shop')
            ->setDescription('Install shop-script application.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        try
        {
            $this->init($input, $output);

            $this->assertFrameworkInstalled();
            $this->assertShopIsNotInstalled();

            $this->installing('Shop-Script...')
                ->downloadRelease()
                ->extract()
                ->cleanUp()
                ->done();

        } catch (ConnectException $e)
        {
            $this->error($e->getMessage())->cleanUp();
            exit(1);
        } catch (ClientException $e)
        {
            $this->error($e->getMessage())
                ->renewToken()
                ->info('Try again.')
                ->cleanUp();
            exit(1);
        } catch (Exception $e)
        {
            $this->error($e->getMessage())->cleanUp();
            exit(1);
        }
    }

    /**
     * Receive download url from GitHub API.
     *
     * @return mixed
     */
    private function getDownloadUrl()
    {
        $latestUrl = 'https://api.github.com/repos/webasyst/shop-script/releases/latest';
        $token = $this->getToken();

        $jsonResponse = $this->client->get($latestUrl, [
            'headers' => [
                'Authorization' => "token $token",
            ]
        ])->getBody();

        $response = json_decode($jsonResponse);

        return $response->zipball_url;
    }

    /**
     * Download current release.
     *
     * @return $this
     */
    private function downloadRelease()
    {
        $zipUrl = $this->getDownloadUrl();
        $token = $this->getToken();
        $this->releaseZip = $this->makeFileName();

        $this->progress->start();

        $releaseArchive = $this->client->get($zipUrl, [
            'headers' => [
                'Authorization' => "token $token",
            ],
            'progress' => $this->progress(),
            'end' => function() {
                $this->progress->finish();
            }
        ])->getBody();

        $this->save($releaseArchive);

        return $this;
    }

    /**
     * Extract archive content to wa-apps.
     *
     * @return $this
     */
    private function extract()
    {
        $this->comment('Extracting...')
            ->detectFramework()
            ->extractFirstSubFolder($this->releaseZip, $this->getTargetDir());

        return $this;
    }

    /**
     * Save token to text file.
     *
     * @param $content
     * @return $this
     */
    private function saveToken($content)
    {
        if( ! file_put_contents('token.txt', trim($content))) {
            $this->error('Error saving token. Check permissions.');
        }

        $this->comment('Token saved');

        return $this;
    }

    /**
     * @return string
     * @throws Exception
     */
    private function getToken()
    {
        if( ! is_file($this->workingDir . DIRECTORY_SEPARATOR . 'token.txt')) {
            file_put_contents('token.txt', '');
        }

        $token = file_get_contents('token.txt');

        if ( ! $token)
        {
            $token = $this->promptToken();
            $this->saveToken($token);
        }

        return $token;
    }

    /**
     * If no token, prompting fo one.
     *
     * @return mixed
     */
    private function promptToken()
    {
        $helper = $this->getHelper('question');

        $question = new Question('<question>Paste github access token(<error>check "repo" option)</error>:</question>');

        return $helper->ask($this->input, $this->output, $question);
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
        $this->progress->setFormat("<comment>Downloading</comment> [%bar%] %percent%%\n");

        $this->initDirectories();
    }

    /**
     * Prompt token and save it.
     *
     * @return $this
     */
    private function renewToken()
    {
        $newToken = $this->promptToken();
        $this->saveToken($newToken);

        return $this;
    }

    /**
     * Save release archive.
     *
     * @param $releaseArchive
     */
    protected function save($releaseArchive)
    {
        file_put_contents($this->releaseZip, $releaseArchive);
    }

    /**
     * @return \Closure
     */
    private function progress()
    {
        return function ($dlTotalSize, $dlSizeSoFar, $ulTotalSize, $ulSizeSoFar)
        {
            if($dlTotalSize == 0 || $dlSizeSoFar == 0) {
                return;
            }

            $current = round(($dlSizeSoFar / $dlTotalSize) * self::DOWNLOAD_COUNT_MAX);

            $this->progress->setProgress($current);
        };
    }

    /**
     * @return mixed
     */
    private function getTargetDir()
    {
        return $this->workingDir . DIRECTORY_SEPARATOR . 'wa-apps' . DIRECTORY_SEPARATOR . self::TARGET_DIR_NAME;
    }

    private function initDirectories()
    {
        $this->setWorkingDir();
        $this->setTmpDir();
    }
}
