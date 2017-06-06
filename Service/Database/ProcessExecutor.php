<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Service\Database;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ProcessExecutor
{
    /**
     * @param string $commandLine
     * @param null|int|float $timeout
     * @param \Closure|null $callback
     *
     * @return bool
     *
     * @throws \Symfony\Component\Process\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    public function execute($commandLine, $timeout = null, \Closure $callback = null)
    {
        $process = new Process($commandLine);
        $process->setTimeout($timeout);
        $process->run($callback);

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return true;
    }
}
