<?php

namespace Antares\Installation\Listeners;

use Antares\Extension\Events\Activated;
use Antares\Extension\Events\ComposerSuccess;
use Antares\Installation\Progress;
use Antares\Memory\Provider;
use Illuminate\Contracts\Container\Container;
use Illuminate\Events\Dispatcher;

class IncrementProgress
{

    /**
     * @var Provider
     */
    protected $memory;

    /**
     * @var Progress
     */
    protected $progress;

    /**
     * IncrementProgress constructor.
     * @param Container $container
     * @param Progress $progress
     */
    public function __construct(Container $container, Progress $progress)
    {
        $this->memory   = $container->make('antares.memory')->make('primary');
        $this->progress = $progress;
    }

    /**
     * @param Activated $event
     */
    public function onExtensionSuccess(Activated $event)
    {
        $this->advanceProgress();
    }

    /**
     * @param ComposerSuccess $event
     */
    public function onComposerSuccess(ComposerSuccess $event)
    {
        $this->advanceProgress();
    }

    public function advanceProgress()
    {
        $this->progress->advanceStep();

        if ($this->progress->isFinished()) {
            $this->memory->put('app.installed', true);
            $this->memory->finish();
        }
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     */
    public function subscribe(Dispatcher $events)
    {
        if (!app()->make('antares.installed')) {

            $events->listen(
                    Activated::class, static::class . '@onExtensionSuccess'
            );

            $events->listen(
                    ComposerSuccess::class, static::class . '@onComposerSuccess'
            );
        }
    }

}
