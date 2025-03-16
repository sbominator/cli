<?php

declare(strict_types=1);

namespace App\Command\Scan;

use App\Helper\DirectoryHelper;
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
