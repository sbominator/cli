<?php

declare(strict_types=1);

namespace SBOMinator\Cli\Command\Scan;

use SBOMinator\Cli\Helper\DirectoryHelper;
use SBOMinator\Dependency;
use SBOMinator\DependencyGraph;
use SBOMinator\Parser\ComposerParser;
use Minicli\Command\CommandController;

class ComposerController extends CommandController
{
    public function handle(): void
    {
        $composerParser = new ComposerParser();

        function printDependencyTree(Dependency $dependency, string $prefix = ""): void
        {
            echo $prefix . "- " . $dependency->getName() . " (" . $dependency->getVersion() . ")" . PHP_EOL;
            foreach ($dependency->getDependencies() as $dep) {
                printDependencyTree($dep, $prefix . "  ");
            }
        }

        $files = DirectoryHelper::scanDirectoryForFilename(getcwd(), 'composer.lock', 10);

        if (empty($files)) {
            // Try to find installed.php as fallback
            $installedPhpFiles = DirectoryHelper::scanDirectoryForFilename(getcwd(), 'installed.php', 10);
            
            if (!empty($installedPhpFiles)) {
                foreach ($installedPhpFiles as $file) {
                    if (strpos($file, 'vendor/composer/installed.php') !== false) {
                        $this->info("No composer.lock found, using vendor/composer/installed.php instead.");
                        try {
                            // Get installed.php data
                            $installedData = include $file;
                            
                            // Convert to composer.lock format
                            $composerLockData = $this->convertInstalledPhpToComposerLock($installedData);
                            
                            // Parse dependencies
                            $composerParser->loadFromArray($composerLockData);
                            $dependencies = $composerParser->parseDependencies();
                            
                            foreach ($dependencies as $dependency) {
                                printDependencyTree($dependency);
                                
                                if ($this->hasFlag('graph')) {
                                    $filename = 'dependency_graph_' . str_replace(['/', '\\'], '_', $dependency->getName()) . '.png';
                                    $graph = new DependencyGraph($dependency, $filename);
                                    $graph->generateGraph();
                                    $this->info("Graph generated: $filename");
                                }
                            }
                        } catch (\Exception $e) {
                            $this->error($e->getMessage());
                            return;
                        }
                    }
                }
            } else {
                $this->error("No composer.lock or vendor/composer/installed.php found.");
                return;
            }
        } else {
            foreach ($files as $file) {
                $packageLock = file_get_contents($file);
                try {
                    $composerParser->loadFromString($packageLock);
                    $dependencies = $composerParser->parseDependencies();

                    foreach ($dependencies as $dependency) {
                        printDependencyTree($dependency);

                        if ($this->hasFlag('graph')) {
                            $filename = 'dependency_graph_' . str_replace(['/', '\\'], '_', $dependency->getName()) . '.png';
                            $graph = new DependencyGraph($dependency, $filename);
                            $graph->generateGraph();
                            $this->info("Graph generated: $filename");
                        }
                    }
                } catch (\Exception $e) {
                    $this->error($e->getMessage());
                    return;
                }
            }
        }
    }

    /**
     * Convert installed.php data to composer.lock format.
     *
     * @param array $installedData The data from installed.php
     * @return array Data in composer.lock format
     */
    private function convertInstalledPhpToComposerLock(array $installedData): array
    {
        $composerLock = [
            'content-hash' => $installedData['root']['reference'] ?? md5(json_encode($installedData)),
            'packages' => [],
            'packages-dev' => []
        ];

        if (isset($installedData['versions']) && is_array($installedData['versions'])) {
            foreach ($installedData['versions'] as $pkgName => $pkgInfo) {
                // Skip the root package
                if ($pkgName === $installedData['root']['name']) {
                    continue;
                }

                $package = [
                    'name' => $pkgName,
                    'version' => $pkgInfo['version'] ?? $pkgInfo['pretty_version'] ?? '0.0.0',
                ];

                // Add dependencies if available
                if (isset($pkgInfo['require']) && is_array($pkgInfo['require'])) {
                    $package['require'] = $pkgInfo['require'];
                }

                // Determine if it's a dev package
                if (isset($pkgInfo['dev_requirement']) && $pkgInfo['dev_requirement'] === true) {
                    $composerLock['packages-dev'][] = $package;
                } else {
                    $composerLock['packages'][] = $package;
                }
            }
        }

        return $composerLock;
    }
}