<?php

declare(strict_types=1);

namespace SBOMinator\Cli\Command\Scan;

use SBOMinator\Cli\Helper\DirectoryHelper;
use SBOMinator\Dependency;
use SBOMinator\Parser\ComposerParser;
use Minicli\Command\CommandController;

class ComposerController extends CommandController
{
    public function handle(): void
    {
        $composerParser = new ComposerParser();

        // Function to print dependency tree
        function printDependencyTree(Dependency $dependency, string $prefix = ""): void
        {
            echo $prefix . "- " . $dependency->getName() . " (" . $dependency->getVersion() . ")" . PHP_EOL;
            foreach ($dependency->getDependencies() as $dep) {
                printDependencyTree($dep, $prefix . "  ");
            }
        }

        $files = DirectoryHelper::scanDirectoryForFilename(getcwd(), 'composer.lock', 10);

        foreach($files as $file) {
            $packageLock = file_get_contents($file);
            try {
                $composerParser->loadFromString($packageLock);
                $dependencies = $composerParser->parseDependencies();

                // Output parsed dependencies as a tree
                foreach ($dependencies as $dependency) {
                    printDependencyTree($dependency);
                }

            } catch (\Exception $e) {
                $this->error($e->getMessage());
                return;
            }
        }
    }
}
