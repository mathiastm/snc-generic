<?php
/**
 * @link      http://github.com/zendframework/zend-skeleton-installer for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\SkeletonInstaller;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event as ScriptEvent;

/**
 * Plugin that uninstalls itself following a create-project operation.
 */
class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * Provide composer event listeners.
     *
     * This particular combination will ensure that the plugin works under each
     * of the following scenarios:
     *
     * - create-project
     * - install, with or without a composer.lock
     * - update, with or without a composer.lock
     *
     * After any of the above have run at least once, the plugin will uninstall
     * itself.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        $subscribers = [
            ['installOptionalDependencies', 1000],
            ['uninstallPlugin'],
        ];

        return [
            'post-install-cmd' => $subscribers,
            'post-update-cmd' => $subscribers,
        ];
    }

    /**
     * Activate the plugin
     *
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * Install optional dependencies, if any.
     *
     * @param ScriptEvent $event
     */
    public function installOptionalDependencies(ScriptEvent $event)
    {
        $installer = new OptionalPackagesInstaller($this->composer, $this->io);
        $installer();
    }

    /**
     * Remove the installer after project installation.
     *
     * @param ScriptEvent $event
     */
    public function uninstallPlugin(ScriptEvent $event)
    {
        $uninstall = new Uninstaller($this->composer, $this->io);
        $uninstall();
    }
}
