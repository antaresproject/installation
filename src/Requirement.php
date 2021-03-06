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
 * @version    0.9.0
 * @author     Original Orchestral https://github.com/orchestral
 * @author     Antares Team
 * @license    BSD License (3-clause)
 * @copyright  (c) 2017, Antares
 * @link       http://antaresproject.io
 */

namespace Antares\Installation;

use Antares\Contracts\Installation\Requirement as RequirementContract;
use Symfony\Component\Process\Process;
use PDOException;

class Requirement implements RequirementContract
{

    /**
     * Application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Installation checklist for Antares.
     *
     * @var array
     */
    protected $checklist = [];

    /**
     * Installable status.
     *
     * @var bool
     */
    protected $installable = true;

    /**
     * Construct a new instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Check all requirement.
     *
     * @return bool
     */
    public function check()
    {
        $this->checklist = [
            'databaseConnection'       => $this->checkDatabaseConnection(),
            'mysqlDumpCommand'         => $this->checkMysqlDumpCommand(),
            'writableStorage'          => $this->checkWritableStorage(),
            'writableAsset'            => $this->checkWritableAsset(),
            'writableLogs'             => $this->checkWritableLogs(),
            'writablePublic'           => $this->checkWritablePublic(),
            'writablePublicPackages'   => $this->checkWritablePublicPackages(),
            'writableComposerVendor'   => $this->checkWritableComposerVendor(),
            'writableComposerJsonFile' => $this->checkWritableComposerJsonFile(),
            'writableComposerLockFile' => $this->checkWritableComposerLockFile(),
            'writableBootstrapCache'   => $this->checkWritableBootstrapCache(),
            'version'                  => $this->getPhpVersion(),
            'phpExtensions'            => $this->getRegisteredPhpExtensions(),
            'apacheModules'            => $this->checkInstalledApacheModules()
        ];


        foreach ($this->checklist as $requirement) {
            if ($requirement['explicit'] && $requirement['is'] !== $requirement['should']) {
                $this->installable = false;
            }
        }
        return $this->installable;
    }

    /**
     * check public packages directory is writable
     *
     * @return array
     */
    protected function checkWritablePublicPackages()
    {
        $path = rtrim($this->app->make('path.public'), '/') . '/packages';
        return $this->isWritable($path);
    }

    /**
     * check if composer vendor is writable
     *
     * @return array
     */
    protected function checkWritableComposerVendor()
    {
        $path = base_path('vendor');
        return $this->isWritable($path);
    }

    /**
     * check if composer.json file is writable
     *
     * @return array
     */
    protected function checkWritableComposerJsonFile()
    {
        $path = base_path('composer.json');
        return $this->isWritable($path);
    }

    /**
     * check if composer.lock file is writable
     *
     * @return array
     */
    protected function checkWritableComposerLockFile()
    {
        $path = base_path('composer.lock');
        return $this->isWritable($path);
    }

    /**
     * check if bootstrap/cache directory is writable
     *
     * @return array
     */
    protected function checkWritableBootstrapCache()
    {
        $path = base_path('bootstrap/cache');
        return $this->isWritable($path);
    }

    /**
     * check public directory is writable
     *
     * @return array
     */
    protected function checkWritablePublic()
    {
        $path = rtrim($this->app->make('path.public'), '/') . '/';
        return $this->isWritable($path);
    }

    /**
     * check logs directory is writable
     *
     * @return array
     */
    protected function checkWritableLogs()
    {
        $path = rtrim($this->app->make('path.storage'), '/') . '/logs';
        return $this->isWritable($path);
    }

    /**
     * does the directory is writable
     *
     * @param String $path
     * @return array
     */
    protected function isWritable($path)
    {

        $schema = [
            'is'   => $this->checkPathIsWritable($path),
            'data' => [
                'path' => $path,
            ],
        ];

        return array_merge($this->getChecklistSchema(), $schema);
    }

    /**
     * validating php version
     * 
     * @return array
     */
    public function getPhpVersion()
    {
        $phpversion      = phpversion();
        $requiredVersion = config('antares/installer::validation.required_min_php_version');
        $schema          = ['data' => ['version' => $phpversion]];
        $isValid         = true;
        if (!isset($phpversion) or ! preg_match('/^([0-9].[0-9]).*$/i', $phpversion, $matches)) {
            $isValid                 = false;
            $schema['data']['error'] = 'Unable to read php version. It is recommended to contact with your provider.';
        }
        if (isset($matches[1]) and (float) $matches[1] < $requiredVersion) {
            $isValid                 = false;
            $schema['data']['error'] = sprintf('Current PHP Version (%s) does not match the minimum system requirements. Required PHP version is %s.x', $phpversion, $requiredVersion);
        }
        $schema['is'] = $isValid;
        return array_merge($this->getChecklistSchema(), $schema);
    }

    /**
     * validates php extensions
     * 
     * @return array
     */
    public function getRegisteredPhpExtensions()
    {
        $extensions = get_loaded_extensions();
        $required   = config('antares/installer::validation.required_php_extensions', []);
        $missing    = array_diff($required, $extensions);
        $schema     = ['is' => true, 'data' => ['extensions' => $extensions]];
        if (!empty($missing)) {
            $schema['is']            = false;
            $schema['data']['error'] = trans(sprintf('Some of PHP extensions are missing: %s. Required PHP extensions: %s. Please check installation.', implode(', ', $missing), implode(', ', $required)));
        }
        return array_merge($this->getChecklistSchema(), $schema);
    }

    /**
     * validates apache modules
     *
     * @return array
     */
    public function checkInstalledApacheModules()
    {
        $modules  = apache_get_modules();
        $required = config('antares/installer::validation.required_apache_modules');

        $missing = array_diff($required, $modules);
        $schema  = ['is' => true, 'data' => ['modules' => $modules]];
        if (!empty($missing)) {
            $schema['is']            = false;
            $schema['data']['error'] = sprintf('Some of apache modules are missing: %s. Required apache modules: %s. Please check server environment.', implode(', ', $missing), implode(', ', $required));
        }
        return array_merge($this->getChecklistSchema(), $schema);
    }

    /**
     * verify whether mysqldump command is available 
     * 
     * @return array
     */
    protected function checkMysqlDumpCommand()
    {
        $schema  = ['is' => true];
        $process = new Process(config('laravel-backup.mysql.dump_command_path') . 'mysqldump');
        $process->run();
        if (!$process->isSuccessful()) {
            $error = $process->getErrorOutput();
            if (str_contains($error, "'mysqldump' is not recognized")) {
                $schema['is']            = false;
                $schema['data']['error'] = $error;
            }
        }
        return array_merge($this->getChecklistSchema(), $schema);
    }

    /**
     * Check database connection.
     *
     * @return array
     */
    public function checkDatabaseConnection()
    {
        $schema = ['is' => true];
        try {
            $this->app->make('db')->connection()->getPdo();
        } catch (PDOException $e) {
            $schema['is']            = false;
            $schema['data']['error'] = $e->getMessage();
        }

        return array_merge($this->getChecklistSchema(), $schema);
    }

    /**
     * Check whether storage folder is writable.
     *
     * @return array
     */
    public function checkWritableStorage()
    {
        $path   = rtrim($this->app->make('path.storage'), '/') . '/';
        $schema = [
            'is'   => $this->checkPathIsWritable($path),
            'data' => [
                'path' => $path,
            ]
        ];

        return array_merge($this->getChecklistSchema(), $schema);
    }

    /**
     * Check whether asset folder is writable.
     *
     * @return array
     */
    public function checkWritableAsset()
    {
        $path   = rtrim($this->app->make('path.public'), '/') . '/packages/';
        $schema = [
            'is'       => $this->checkPathIsWritable($path),
            'data'     => [
                'path' => $path,
            ],
            'explicit' => false,
        ];

        return array_merge($this->getChecklistSchema(), $schema);
    }

    /**
     * Get checklist schema.
     *
     * @return array
     */
    protected function getCheckListSchema()
    {
        return [
            'is'       => null,
            'should'   => true,
            'explicit' => true,
            'data'     => [],
        ];
    }

    /**
     * Check if path is writable.
     *
     * @param  string   $path
     *
     * @return bool
     */
    protected function checkPathIsWritable($path)
    {
        return $this->app->make('files')->isWritable($path);
    }

    /**
     * Get checklist result.
     *
     * @return array
     */
    public function getChecklist()
    {
        return $this->checklist;
    }

    /**
     * Get installable status.
     *
     * @return bool
     */
    public function isInstallable()
    {
        return $this->installable;
    }

}
