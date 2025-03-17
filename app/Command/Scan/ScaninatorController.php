<?php

declare(strict_types=1);

namespace App\Command\Scan;

use App\Helper\DirectoryHelper;
use SBOMinator\Dependency;
use SBOMinator\DependencyGraph;
use SBOMinator\Parser\ComposerParser;
use Minicli\Command\CommandController;
use Sbominator\Scaninator\Scaninator;

class ScaninatorController extends CommandController
{
    public function handle(): void
    {
        $path = $this->hasParam('path') ? $this->getParam('path') : getcwd();

        $scaninator = new Scaninator($path);

        $dependencies = $scaninator->getDependecies();

        $type = $this->hasParam('type') ? $this->getParam('type') : 'both';

        if (empty($dependencies)) {
            $this->info('No dependencies found.');
            return;
        }

        if ('both' === $type) {
            $this->printDependencyList('PHP dependencies:', $dependencies['php']);
            $this->printDependencyList('JS dependencies:', $dependencies['js']);
        } elseif (isset($dependencies[$type])) {
            $this->printDependencyList(ucfirst($type) . ' dependencies:', $dependencies[$type]);
        } else {
            $this->error('Invalid type. Use "both", "php" or "js".');
        }
    }

    /**
     * Print formatted dependency list
     *
     * @param string $title Section title
     * @param array $dependencies List of dependencies to print
     * @return void
     */
    private function printDependencyList(string $title, array $dependencies): void
    {
        $this->info($title);
        
        if (empty($dependencies)) {
            $this->info('  No dependencies found.');
            return;
        }
        
        foreach ($dependencies as $dependency) {
            $name = $dependency['name'] ?? 'Unknown';
            $version = !empty($dependency['version']) ? $dependency['version'] : 'unknown version';
            $this->info("  - {$name} ({$version})");
            
            // Print additional information if available
            if (!empty($dependency['namespace'])) {
                $this->info("    Namespace: {$dependency['namespace']}");
            }
        }
        
        $this->info('');
    }
}
