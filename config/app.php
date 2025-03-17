<?php

declare(strict_types=1);

return [
    /****************************************************************************
     * Application Settings
     * --------------------------------------------------------------------------
     *
     * These are the core settings for your application.
     *****************************************************************************/

    'app_name' => envconfig(
        'MINICLI_APP_NAME',
        "
 ▗▄▄▖▗▄▄▖  ▗▄▖ ▗▖  ▗▖▗▄▄▄▖▗▖  ▗▖ ▗▄▖▗▄▄▄▖▗▄▖ ▗▄▄▖
▐▌   ▐▌ ▐▌▐▌ ▐▌▐▛▚▞▜▌  █  ▐▛▚▖▐▌▐▌ ▐▌ █ ▐▌ ▐▌▐▌ ▐▌
 ▝▀▚▖▐▛▀▚▖▐▌ ▐▌▐▌  ▐▌  █  ▐▌ ▝▜▌▐▛▀▜▌ █ ▐▌ ▐▌▐▛▀▚▖
▗▄▄▞▘▐▙▄▞▘▝▚▄▞▘▐▌  ▐▌▗▄█▄▖▐▌  ▐▌▐▌ ▐▌ █ ▝▚▄▞▘▐▌ ▐▌

Usage:
  sbominator [command] [options]

Description:
  SBOMinator CLI is a command-line tool to scan directories for dependency files,
  aggregating them into a consolidated CycloneDX SBOM. It also provides utilities to convert SBOMs between SPDX and CycloneDX formats.

Available Commands:

  scan
    Usage:
      scan dir=DIR [output-file=FILE]

    Description:
      Scans a specified directory for package lock files and SBOM files, combines the found dependency data,
      and outputs a consolidated CycloneDX SBOM file.

      The command searches for common dependency files (e.g. package-lock.json, composer.lock) and any existing SBOMs,
      aggregates the discovered dependencies, and generates a CycloneDX-compliant SBOM.

    Arguments:
      dir            (Required) The directory to scan for dependency files. Use a relative or absolute path.
      output-file    (Optional) The path where the combined CycloneDX SBOM file will be saved.
                     Defaults to 'sbom-cyclonedx.json' in the current working directory if not provided.

    Examples:
      Scan the current directory and output to the default file:
        sbominator scan dir=.

      Scan a specific directory and specify an output file:
        sbominator scan dir=/path/to/project output-file=/path/to/output/sbom.json

  convert
    Usage:
      convert input-file=FILEPATH output-file=FILEPATH [input-format=FORMAT] [output-format=FORMAT]

    Description:
      Converts a Software Bill of Materials (SBOM) file between SPDX and CycloneDX formats.
      It reads an input SBOM file, auto-detects its format (if not explicitly provided), and converts it to the specified output format.

    Arguments:
      input-file       Path to the input SBOM file.
      output-file      Path where the converted SBOM file will be saved.
      input-format     Format of the input file. Available formats: 'spdx', 'cyclonedx'.
                       Default: 'autodetect' (automatically detects the format).
      output-format    Format for the converted file. Available formats: 'spdx', 'cyclonedx'.
                       Default: 'autodetect' (selects the opposite of the detected input format).

    Examples:
      Convert an SBOM file with auto-detection:
        sbominator convert input-file=./sbom.json output-file=./converted.json

      Convert specifying the formats explicitly:
        sbominator convert input-file=./sbom.json output-file=./converted.json input-format=spdx output-format=cyclonedx
    Notes:
      - The 'convert' command supports two SBOM formats:
          * spdx      : Software Package Data Exchange format.
          * cyclonedx : CycloneDX format.
      - The 'scan' command automatically aggregates dependency files and creates a CycloneDX SBOM based on the found data.
      - Use the '--help' flag with any command for more detailed information.


"
    ),

    'app_path' => [
        __DIR__.'/../app/Command',
        '@minicli/command-help'
    ],

    'theme' => '',

    'debug' => true,
];
