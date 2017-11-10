<?php

/**
 * Part of the Antares package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.
 *
 * @package    Antares Core
 * @version    0.9.2
 * @author     Antares Team
 * @license    BSD License (3-clause)
 * @copyright  (c) 2017, Antares
 * @link       http://antaresproject.io
 */

namespace Antares\Installation\Console\Commands;

use Antares\Extension\Contracts\ExtensionContract;
use Antares\Extension\Contracts\OperationContract;
use Antares\Extension\Outputs\OperationFileOutput;
use Illuminate\Console\Command;
use Antares\Extension\Processors\Composer;
use Illuminate\Contracts\Container\Container;
use Antares\Extension\Manager;

class InstallCommand extends Command
{

    protected $signature        = 'antares:install';
    protected $description      = 'Perform an install process of the Antares Framework.';
    protected $extensions       = [];
    protected $operationClasses = [];
    protected $path             = null;

    /**
     * Flags for the process.
     *
     * @var array
     */
    protected $flags = [];

    public function __construct()
    {

        $this->extensions = [
            "antaresproject/component-notifications:0.9.2.x-dev",
            "antaresproject/component-logger:0.9.2.x-dev",
            "antaresproject/component-translations:0.9.2.x-dev",
            "antaresproject/component-acl:0.9.2.x-dev",
            "antaresproject/component-automation:0.9.2.x-dev",
            "antaresproject/component-customfields:0.9.2.x-dev",
            "antaresproject/component-tester:0.9.2.x-dev",
            "antaresproject/component-installer:0.9.2.x-dev",
            "antaresproject/component-brands:0.9.2.x-dev",
            "antaresproject/component-users:0.9.2.x-dev",
            "antaresproject/module-api:0.9.2.x-dev",
            "antaresproject/module-sample_module:0.9.2.x-dev",
            "antaresproject/module-search:0.9.2.x-dev"
        ];

        $this->operationClasses = [
            //"Antares\Extension\Processors\Installer",
            "Antares\Extension\Processors\Activator"
        ];

        $this->path = "/var/www/main/storage/installation.txt";

        parent::__construct();
    }

    /**
     * Handles the command task.
     * @throws \Exception
     */
    public function handle(Manager $manager, Container $container, Composer $composer)
    {
        $output = new OperationFileOutput($this->path);



        $this->flags = array_merge($this->flags, ['skip-composer']);

        foreach ($this->extensions as $extensionName) {
            $extensionName = explode(':', $extensionName)[0];
            $extension     = $manager->getAvailableExtensions()->findByName($extensionName);
            foreach ($this->operationClasses as $operationClassName) {
                $operation = $container->make($operationClassName);


                if ($extension instanceof ExtensionContract && $operation instanceof OperationContract) {
                    $operation->run($output, $extension, $this->flags);
                }
            }
        }
    }

}
