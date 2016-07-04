<?php


namespace Wbs\Commands;


/**
 * Class ShowDownload
 * @package Wbs\Commands
 */
trait ShowDownload
{

    /**
     *
     */
    protected function startDownload()
    {
        $this->progress->start();
    }

    /**
     * @return \Closure
     */
    protected function showProgress()
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
     *
     */
    protected function stopDownload()
    {
        $this->progress->finish();
    }

    /**
     *
     */
    protected function progressClear()
    {
        $this->progress->clear();
    }
}