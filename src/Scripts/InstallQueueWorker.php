<?php

namespace Antares\Installation\Scripts;

use Antares\Monitor\ProcessMonitor;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException;

class InstallQueueWorker
{

    /**
     * Artisan command.
     *
     * @var string
     */
    protected static $command = 'artisan queue:listen --queue=install --timeout=0 --tries=0';

    /**
     * Process PID.
     *
     * @var int|null
     */
    protected $pid = null;

    /**
     * Absolute path to the application.
     *
     * @var string
     */
    protected $scriptPath = '';

    /**
     * @var ProcessMonitor
     */
    protected $processMonitor;

    /**
     * InstallQueueWorker constructor.
     * @param ProcessMonitor $processMonitor
     */
    public function __construct(ProcessMonitor $processMonitor)
    {
        $this->processMonitor = $processMonitor;
        $this->scriptPath = base_path();
    }

    /**
     * Runs process if it is not running.
     *
     * @return InstallQueueWorker
     * @throws RuntimeException
     * @throws LogicException
     */
    public function run(): self
    {
        $process = $this->processMonitor->getProcessByCommand(static::$command);

        if( ! $process->isRunning()) {
            $process = $this->processMonitor->run(static::$command);
        }

        $this->pid = $process->getInfo()->getPid();

        return $this;
    }

    /**
     * Sets the process PID.
     *
     * @param int $pid
     */
    public function setPid(int $pid)
    {
        $this->pid = $pid;
    }

    /**
     * Returns the process PID.
     *
     * @return int|null
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Stops the running process.
     */
    public function stop()
    {
        if($this->pid) {
            exec('kill ' . $this->pid);
        }
    }

}
