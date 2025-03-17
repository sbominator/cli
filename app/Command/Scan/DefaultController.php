<?php

namespace SBOMinator\Cli\Command\Scan;

use Minicli\Command\CommandController;
use SBOMinator\Lib\Generator\CycloneDXSBOMGenerator;
use SBOMinator\Lib\Scanner\FileScanner;
use Sbominator\Scaninator\Scaninator;
use SBOMinator\Transformatron\Converter;
use SBOMinator\Transformatron\Exception\ConversionException;
use SBOMinator\Transformatron\Exception\ValidationException;

class DefaultController extends CommandController
{

    private string $helpMessage = <<<HELP
Usage:
  scan dir=DIR output-file=FILE

Description:
  This command scans a specified directory for package lock files and SBOM files,
  combines the found dependency data, and outputs a consolidated CycloneDX SBOM file.

  The command leverages a file scanner to search for common dependency files (e.g. package-lock.json, composer.lock)
  and any pre-existing SBOM files. It then aggregates the discovered dependencies and generates a CycloneDX compliant SBOM.

Arguments:
  dir            (Required) The directory to scan for dependency files. Use a relative or absolute path.
  output-file    (Optional) The path where the combined CycloneDX SBOM file will be saved.
                   Defaults to 'sbom-cyclonedx.json' in the current working directory if not provided.

Examples:
  Scan the current directory and output to the default file:
    scan dir=.

  Scan a specific directory and specify an output file:
    scan dir=/path/to/project output-file=/path/to/output/sbom.json

Notes:
  - The command scans for common lock files (e.g. package-lock.json, composer.lock) and existing SBOMs.
  - Make sure the directory contains valid dependency files for an accurate scan.
  - The generated SBOM will adhere to the CycloneDX format.

HELP;

    public function handle(): void
    {
        $directory = $this->getParam('dir');
        $outputFile = $this->getParam('output-file') ?? getcwd() . '/sbom-cyclonedx.json';

        if (!$directory) {
            $this->display($this->helpMessage);
            return;
        }

        if (!file_exists($directory)) {
            $this->error("Directory not found: {$directory}");
            return;
        }

        $fileScanner = new FileScanner(10, ['json', 'lock']);
        $generator = null;
        try {
            $dependencies = $fileScanner->scanForDependencies($directory);
            $generator = new CycloneDXSBOMGenerator($dependencies);
        } catch (\Exception $e) {
            $this->error("Error while scanning directory: " . $e->getMessage());
        }

        try {
            file_put_contents($outputFile, $generator->generate());
            $this->success("CycloneDX SBOM File written to: " . $outputFile);
        } catch (\Exception $e) {
            $this->error("Error while writing SBOM File: " . $e->getMessage());
        }

        try {
            $scaninator = new Scaninator($directory);
            $dependencies = $scaninator->getDependecies();

            if (!empty($dependencies['php'])) {
                $this->printDependencyList('Scanninator found PHP dependencies:', $dependencies['php']);
            }

            if (!empty($dependencies['js'])) {
                $this->printDependencyList('Scanninator found JS dependencies:', $dependencies['js']);
            }
        } catch (\Exception) {

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
        }
    }
}
