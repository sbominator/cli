<?php

declare(strict_types=1);

namespace SBOMinator\Cli\Command\Scan;

use SBOMinator\Cli\Helper\DirectoryHelper;
use SBOMinator\Dependency;
use Minicli\Command\CommandController;
use SBOMinator\Parser\CycloneDXParser;

class CycloneDXController extends CommandController
{
    public function handle(): void
    {
        $cycloneDXParser = new CycloneDXParser();

        // Function to print dependency tree
        function printDependencyTree(Dependency $dependency, string $prefix = ""): void
        {
            echo $prefix . "- " . $dependency->getName() . " (" . $dependency->getVersion() . ")" . PHP_EOL;
            foreach ($dependency->getDependencies() as $dep) {
                printDependencyTree($dep, $prefix . "  ");
            }
        }

        $files = DirectoryHelper::scanDirectoryForFilename(getcwd(), 'bom.json', 10);

        foreach($files as $file) {
            $packageLock = file_get_contents($file);
            try {
                $cycloneDXParser->loadFromString($packageLock);
                $dependencies = $cycloneDXParser->parseDependencies();

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
