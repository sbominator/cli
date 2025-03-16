<?php

declare(strict_types=1);

namespace App\Command\Scan;

use App\Helper\DirectoryHelper;
use SBOMinator\Dependency;
use Minicli\Command\CommandController;
use SBOMinator\Parser\SpdxParser;

class SpdxController extends CommandController
{
    public function handle(): void
    {
        $spdxParser = new SpdxParser();

        // Function to print dependency tree
        function printDependencyTree(Dependency $dependency, string $prefix = ""): void
        {
            echo $prefix . "- " . $dependency->getName() . " (" . $dependency->getVersion() . ")" . PHP_EOL;
            foreach ($dependency->getDependencies() as $dep) {
                printDependencyTree($dep, $prefix . "  ");
            }
        }

        $files = DirectoryHelper::scanDirectoryForFilename(getcwd(), 'wp-sbom.json', 10);

        foreach($files as $file) {
            $packageLock = file_get_contents($file);
            try {
                $spdxParser->loadFromString($packageLock);
                $dependencies = $spdxParser->parseDependencies();

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
