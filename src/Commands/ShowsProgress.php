<?php


namespace Wbs\Commands;


/**
 * Class ShowDownload
 * @package Wbs\Commands
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
                return;
            }

            $current = round(($dlSizeSoFar / $dlTotalSize) * $this->progress->getMaxSteps());

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