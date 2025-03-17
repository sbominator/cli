<?php

namespace SBOMinator\Cli\Command\Convert;

use Minicli\Command\CommandController;
use SBOMinator\Transformatron\Converter;
use SBOMinator\Transformatron\Exception\ConversionException;
use SBOMinator\Transformatron\Exception\ValidationException;

class DefaultController extends CommandController
{

    private string $helpMessage = <<<HELP
Usage:
  convert input-file=FILEPATH output-file=FILEPATH [input-format=FORMAT] [output-format=FORMAT

Description:
  This command converts a Software Bill of Materials (SBOM) file between SPDX and CycloneDX formats.
  It reads an input SBOM file, auto-detects its format (if not explicitly provided), and converts it
  to the specified output format.

Arguments:
  input-file       Path to the input SBOM file.
  output-file      Path where the converted SBOM file will be saved.
  input-format     Format of the input file. Available formats: "spdx", "cyclonedx".
                     Default: "autodetect" (automatically detects the format).
  output-format    Format for the converted file. Available formats: "spdx", "cyclonedx".
                     Default: "autodetect" (selects the opposite of the detected input format).
Examples:
  Convert an SBOM file with auto-detection:
    convert input-file=./sbom.json output-file=./converted.json

  Convert specifying the formats explicitly:
    convert input-file=./sbom.json output-file=./converted.json input-format=spdx output-format=cyclonedx

Available Formats:
  - spdx      : Software Package Data Exchange format.
  - cyclonedx : CycloneDX format.

HELP;

    public function handle(): void
    {
        // Retrieve command line arguments using the $this->arguments property.

        $arguments = $this->getArgs();

        $inputFile = $this->getParam('input-file');
        $outputFile = $this->getParam('output-file');
        $inputFormat = $this->getParam('input-format') ?: 'autodetect';
        $outputFormat = $this->getParam('output-format') ?: 'autodetect';



        if (!$inputFile || !$outputFile) {
            $this->display($this->helpMessage);
            return;
        }

        if (!file_exists($inputFile)) {
            $this->error("Input file not found: {$inputFile}");
            return;
        }

        $json = file_get_contents($inputFile);
        $converter = new Converter();

        try {
            // Auto-detect input format if requested
            if ($inputFormat === 'autodetect') {
                $detected = $converter->detectFormat($json);
                if (!$detected) {
                    $this->error("Could not auto-detect input format.");
                    return;
                }
                $inputFormat = $detected;
                $this->info("Detected input format: {$inputFormat}");
            }

            // If output format is autodetect, choose the opposite of the input
            if ($outputFormat === 'autodetect') {
                if ($inputFormat === Converter::FORMAT_SPDX) {
                    $outputFormat = Converter::FORMAT_CYCLONEDX;
                } elseif ($inputFormat === Converter::FORMAT_CYCLONEDX) {
                    $outputFormat = Converter::FORMAT_SPDX;
                } else {
                    $this->error("Unknown input format detected; cannot auto-detect output format.");
                    return;
                }
                $this->info("Auto-selected output format: {$outputFormat}");
            }

            // Perform the conversion using auto-detection internally
            $result = $converter->convert($json, $outputFormat);

            // Save the converted content to the output file
            file_put_contents($outputFile, $result->getContent());
            $this->success("Conversion successful. Output saved to: {$outputFile}");

            // Display warnings if there are any
            if ($result->hasWarnings()) {
                $this->info("Conversion completed with warnings:", true);
                foreach ($result->getWarnings() as $warning) {
                    $this->error("  - {$warning}");
                }
            }
        } catch (ValidationException $e) {
            $this->error("Validation error: " . $e->getMessage());
            print_r($e->getValidationErrors());
        } catch (ConversionException $e) {
            $this->error("Conversion error: " . $e->getMessage());
            $this->error("Source format: " . $e->getSourceFormat());
            $this->error("Target format: " . $e->getTargetFormat());
        }
    }
}
