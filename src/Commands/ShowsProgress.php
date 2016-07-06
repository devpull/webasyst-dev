<?php


namespace Wbs\Commands;


/**
 * Class ShowDownload
 * @package Wbs\Commands
 *
 * @property \Symfony\Component\Console\Helper\ProgressBar $progress
 * @property \Symfony\Component\Filesystem\Filesystem $fs
 */
trait ShowsProgress
{

    /**
     * @param string $message
     * @param null $max
     */
    protected function progressStart($message='', $max=null)
    {
        $this->progress->setMessage($message);
        $this->progress->start($max);
    }

    /**
     * For guzzle "progress" option.
     *
     * @return \Closure
     */
    protected function showProgress()
    {
        return function ($dlTotalSize, $dlSizeSoFar, $ulTotalSize, $ulSizeSoFar)
        {
            // workaround for guzzle repetative download/dlsofar sizes.
            if($dlTotalSize == 0 || $dlSizeSoFar == 0) {
                $totalToSoFar = 0;
            } else {
                $totalToSoFar = round($dlSizeSoFar / $dlTotalSize, 2);
            }

            $current = $totalToSoFar * $this->progress->getMaxSteps();

            $this->progress->setProgress($current);
        };
    }

    /**
     *
     */
    protected function progressStop()
    {
        $this->progress->finish();

        return $this;
    }

    /**
     *
     */
    protected function progressClear()
    {
        $this->progress->clear();

        return $this;
    }
}