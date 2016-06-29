<?php

namespace Wbs\Commands;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use PhpSpec\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use GuzzleHttp\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

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
    private $releaseFileName;

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
        $this->init($input, $output);

        $this->assertFrameworkInstalled();
        $this->assertShopIsNotInstalled();

        try
        {
            $this->installing('Shop-Script...')
                ->downloadRelease()
                ->extract()
                ->cleanUp();

        } catch (ConnectException $e)
        {
            $this->error($e->getMessage());
            exit(1);
        } catch (ClientException $e)
        {
            $this->error($e->getMessage())
                ->renewToken()
                ->info('Try again.');
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
            ],
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
        $this->releaseFileName = $this->makeFileName();

        $this->comment('Downloading...');

        $releaseArchive = $this->client->get($zipUrl, [
            'headers' => [
                'Authorization' => "token $token",
            ],
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
        $this->comment('Extracting...');

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
     */
    private function getToken()
    {
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

        $this->setWorkingDir();
        $this->setTmpDir();
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
        file_put_contents($this->releaseFileName, $releaseArchive);
    }
}
