<?php
/**
 * @link      http://github.com/zendframework/zend-skeleton-installer for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\SkeletonInstaller;

use Composer\Composer;
use Composer\DependencyResolver\DefaultPolicy;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Pool;
use Composer\DependencyResolver\Request;
use Composer\EventDispatcher\EventDispatcher;
use Composer\Installer as ComposerInstaller;
use Composer\IO\IOInterface;
use Composer\Package\AliasPackage;
use Composer\Package\Link;
use Composer\Package\PackageInterface;
use Composer\Package\Version\VersionParser;
use Composer\Repository\CompositeRepository;
use Composer\Script\PackageEvent;
use Zend\ComponentInstaller\ComponentInstaller;

/**
 * Prompt for and install optional packages.
 */
class OptionalPackagesInstaller
{
    use ComposerJsonRetrievalTrait;

    /**
     * @var callable Factory for creating a ComponentInstaller instance.
     */
    private $componentInstallerFactory = [OptionalPackagesInstaller::class, 'createComponentInstaller'];

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var callable Factory for creating a ComposerInstaller instance.
     */
    private $installerFactory = [OptionalPackagesInstaller::class, 'createInstaller'];

    /**
     * @var IOInterface
     */
    private $io;

    // @codingStandardsIgnoreStart
    /**
     * @var string[]
     */
    private $packageConfigPrompts = [
        'require'     => '<info>    When prompted to install as a module, select application.config.php or modules.config.php</info>',
        'require-dev' => '<info>    When prompted to install as a module, select development.config.php.dist</info>',
    ];
    // @codingStandardsIgnoreEnd

    /**
     * @var VersionParser
     */
    private $versionParser;

    /**
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function __construct(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->versionParser = new VersionParser();
    }

    /**
     * Prompt for and install optional packages
     */
    public function __invoke()
    {
        // Locate and return valid optional packages
        $optionalPackages = Collection::create($this->getOptionalDependencies())
            ->filter([OptionalPackage::class, 'isValidSpec']);

        // If none are found, do nothing.
        if ($optionalPackages->isEmpty()) {
            return;
        }

        // Prompt for minimal install
        if ($this->requestMinimalInstall()) {
            // If a minimal install is requested, remove optional package information
            $this->removeOptionalPackages();
            return;
        }

        // Prompt for each package, and filter accordingly
        $packagesToInstall = $optionalPackages
            ->map(function ($spec) {
                return new OptionalPackage($spec);
            })
            ->filter(function ($package) {
                return $this->promptForPackage($package);
            });

        // If no optional packages were selected, do nothing.
        if ($packagesToInstall->isEmpty()) {
            $this->io->write('<info>    No optional packages selected to install</info>');
            $this->removeOptionalPackages();
            return;
        }

        // Run an installer update
        $package = $this->updateRootPackage($this->composer->getPackage(), $packagesToInstall);
        if (0 !== $this->runInstaller($package, $packagesToInstall)) {
            $this->io->write('<error>Error installing optional packages. Run with verbosity to debug');
            return;
        }

        // Update the composer.json
        $this->updateComposerJson($packagesToInstall);

        // Update application configuration
        $this->updateApplicationConfiguration($packagesToInstall);
    }

    /**
     * Retrieve list of optional dependencies.
     *
     * Looks for a extra.zend-skeleton-installer key with an array value,
     * returning it if found, or an empty array otherwise.
     *
     * @return array
     */
    private function getOptionalDependencies()
    {
        $package = $this->composer->getPackage();
        $extra = $package->getExtra();

        return isset($extra['zend-skeleton-installer']) && is_array($extra['zend-skeleton-installer'])
            ? $extra['zend-skeleton-installer']
            : [];
    }

    /**
     * Prompt the user for a mimimal install.
     *
     * @return bool
     */
    private function requestMinimalInstall()
    {
        $question = [
            "\n    <question>Do you want a minimal install (no optional packages)?</question> <comment>Y/n</comment>\n",
        ];

        while (true) {
            $answer = $this->io->ask($question, 'y');
            $answer = strtolower($answer);

            if ('n' === $answer) {
                return false;
            }

            if ('y' === $answer) {
                return true;
            }

            $this->io->write('<error>Invalid answer</error>');
        }
    }

    /**
     * Create the callback for emitting and handling a package prompt.
     *
     * @param OptionalPackage $package
     * @return bool
     */
    private function promptForPackage(OptionalPackage $package)
    {
        $question = [sprintf(
            "\n    <question>%s</question> <comment>y/N</comment>\n",
            $package->getPrompt()
        )];

        while (true) {
            $answer = $this->io->ask($question, 'n');
            $answer = strtolower($answer);

            if ('n' === $answer) {
                return false;
            }

            if ('y' === $answer) {
                $this->io->write(sprintf(
                    '<info>    Will install %s (%s)</info>',
                    $package->getName(),
                    $package->getConstraint()
                ));
                if ($package->isModule()) {
                    $extra = $package->isDev()
                        ? $this->packageConfigPrompts['require-dev']
                        : $this->packageConfigPrompts['require'];
                    $this->io->write($extra);
                }
                return true;
            }

            $this->io->write('<error>Invalid answer</error>');
        }
    }

    /**
     * Remove optional packages after a minimal install or failure to select packages.
     */
    private function removeOptionalPackages()
    {
        $this->io->write('<info>    Removing optional packages from composer.json</info>');
        $this->updateComposerJson(new Collection([]));
    }

    /**
     * Update the composer.json definition.
     *
     * Adds all packages to the appropriate require or require-dev sections of
     * the composer.json, and removes the extra.zend-skeleton-installer node.
     *
     * @param Collection $packagesToInstall
     */
    private function updateComposerJson(Collection $packagesToInstall)
    {
        $this->io->write('<info>    Updating composer.json</info>');
        $composerJson = $this->getComposerJson();
        $json = $packagesToInstall->reduce(function ($composer, $package) {
            return $this->updateComposerRequirement($composer, $package);
        }, $composerJson->read());
        unset($json['extra']['zend-skeleton-installer']);
        $composerJson->write($json);
    }

    /**
     * Add a package to the composer definition.
     *
     * @param array $composer
     * @param OptionalPackage $package
     * @return array
     */
    private function updateComposerRequirement(array $composer, OptionalPackage $package)
    {
        $key = $package->isDev() ? 'require-dev' : 'require';
        $composer[$key][$package->getName()] = $package->getConstraint();
        return $composer;
    }

    /**
     * Update the root package definition
     *
     * @param \Composer\Package\RootPackage $package
     * @param Collection $packagesToInstall
     * @return \Composer\Package\RootPackage
     */
    private function updateRootPackage($package, Collection $packagesToInstall)
    {
        $this->io->write('<info>Updating root package</info>');
        $package->setRequires($packagesToInstall->reduce(function ($requires, $package) {
            return $this->addRootPackageRequirement($requires, $package);
        }, $package->getRequires()));
        return $package;
    }

    /**
     * Add a requirement to the root package.
     *
     * @param array $requires
     * @param OptionalPackage $package
     * @return array
     */
    private function addRootPackageRequirement(array $requires, OptionalPackage $package)
    {
        $name = $package->getName();
        $constraint = $package->getConstraint();
        $description = $package->isDev()
            ? 'requires for development'
            : 'requires';

        $requires[$name] = new Link(
            '__root__',
            $name,
            $this->versionParser->parseConstraints($constraint),
            $description,
            $constraint
        );

        return $requires;
    }

    /**
     * Creates and runs a Composer installer instance, returning the results.
     *
     * The instance has the following modifications:
     *
     * - It uses the updated root package
     * - It uses a new EventDispatcher, to prevent triggering already accumulated plugins
     * - It marks the operation as an update
     * - It specifies an update whitelist of only the new packages to install
     * - It disables plugins
     *
     * @param Composer $composer
     * @param PackageInterface $package
     * @param Collection $packagesToInstall
     * @return int
     */
    private function runInstaller(PackageInterface $package, Collection $packagesToInstall)
    {
        $this->io->write('<info>    Running an update to install optional packages</info>');

        $installer = call_user_func(
            $this->installerFactory,
            $this->composer,
            $this->io,
            $package
        );

        $installer->disablePlugins();
        $installer->setUpdate();
        $installer->setUpdateWhitelist(
            $packagesToInstall->map(function ($package) {
                return $package->getName();
            })
            ->toArray()
        );

        return $installer->run();
    }

    /**
     * Create an Installer instance.
     *
     * Private static factory, to allow slip-streaming in a mock as needed for
     * testing.
     */
    private static function createInstaller(Composer $composer, IOInterface $io, PackageInterface $package)
    {
        $eventDispatcher = new EventDispatcher($composer, $io);

        return new ComposerInstaller(
            $io,
            $composer->getConfig(),
            $package,
            $composer->getDownloadManager(),
            $composer->getRepositoryManager(),
            $composer->getLocker(),
            $composer->getInstallationManager(),
            $eventDispatcher,
            $composer->getAutoloadGenerator()
        );
    }

    /**
     * Update application configuration.
     *
     * For each package to install, creates a new PackageEvent with relevant
     * details and passes it to the ComponentInstaller::onPostPackageInstall
     * event handler in order to update the application configuration.
     *
     * @param Collection $packagesToInstall
     * @return void
     */
    private function updateApplicationConfiguration(Collection $packagesToInstall)
    {
        $this->io->write('<info>Updating application configuration...</info>');

        // Initialize the ComponentInstaller
        $componentInstaller = call_user_func($this->componentInstallerFactory);
        $componentInstaller->activate($this->composer, $this->io);

        // Grab the local repository so we can do package lookups
        $localRepository = $this->composer->getRepositoryManager()->getLocalRepository();

        // Empty stubs for the PackageEvent
        $policy = new DefaultPolicy();
        $pool = new Pool();
        $compositeRepository = new CompositeRepository([]);
        $request = new Request();

        $packagesToInstall->each(function ($optionalPackage) use (
            $componentInstaller,
            $localRepository,
            $policy,
            $pool,
            $compositeRepository,
            $request
        ) {
            // Lookup the package
            $package = $localRepository->findPackage($optionalPackage->getName(), $optionalPackage->getConstraint());

            if (! $package) {
                return;
            }

            // Install application configuration
            $componentInstaller->onPostPackageInstall(new PackageEvent(
                'post-package-install',
                $this->composer,
                $this->io,
                true,
                $policy,
                $pool,
                $compositeRepository,
                $request,
                [],
                new InstallOperation($package)
            ));
        });
    }

    /**
     * Create and return a ComponentInstaller instance.
     *
     * Allows slipstreaming a mock into the instance when required.
     *
     * @return ComponentInstaller
     */
    private static function createComponentInstaller()
    {
        return new ComponentInstaller();
    }
}
