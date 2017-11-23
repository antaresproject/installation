<?php

namespace Antares\Installation\Listeners;

use Antares\Events\Compontents\ComponentInstallationFailed;
use Illuminate\Events\Dispatcher;
use Antares\Installation\Progress;
use Antares\Events\Composer\Failed;
use Antares\Extension\Contracts\ProgressContract;

class FailedListener
{

    /**
     * @var ProgressContract
     */
    protected $progress;

    /**
     * FailedListener constructor.
     * @param Progress $progress
     */
    public function __construct(Progress $progress)
    {
        $this->progress = $progress;
    }

    /**
     * @param ComponentInstallationFailed $event
     */
    public function onExtensionFailed(ComponentInstallationFailed $event)
    {
        \Illuminate\Support\Facades\Log::error($event->exception);
        $this->progress->setFailed($event->exception->getMessage());
    }

    /**
     * @param Failed $event
     */
    public function onComposerFailed(Failed $event)
    {
        \Illuminate\Support\Facades\Log::error($event->exception);
        $this->progress->setFailed($event->exception->getMessage());
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
                ComponentInstallationFailed::class, static::class . '@onExtensionFailed'
            );

            $events->listen(
                    Failed::class, static::class . '@onComposerFailed'
            );
        }
    }

}
