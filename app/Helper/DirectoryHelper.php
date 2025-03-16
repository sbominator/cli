<?php

namespace App\Helper;

class DirectoryHelper
{
    public static function scanDirectoryForFilename(string $dir, string $filename, int $maxDepth = 10): array
    {
        $files = [];
        $it = new \RecursiveDirectoryIterator($dir);
        $it = new \RecursiveIteratorIterator($it);
        $it->setMaxDepth($maxDepth);
        foreach ($it as $file) {
            if ($file->getFilename() === $filename) {
                $files[] = $file->getPathname();
            }
        }



        return $files;
    }

}
