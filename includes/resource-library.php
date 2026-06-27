<?php
require_once __DIR__ . '/url.php';

if (!function_exists('resource_library_title_from_filename')) {
    function resource_library_title_from_filename(string $filename): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $name = preg_replace('/-\d+x\d+$/', '', $name);
        $name = str_replace(['_', '-'], ' ', (string) $name);
        $name = preg_replace('/\s+/', ' ', (string) $name);
        return ucwords(trim((string) $name));
    }
}

if (!function_exists('resource_library_human_size')) {
    function resource_library_human_size(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return number_format($bytes / (1024 * 1024), 1) . ' MB';
    }
}

if (!function_exists('resource_library_scan_folder')) {
    function resource_library_scan_folder(string $relativeFolder, callable $matcher, ?array $allowedExtensions = null): array
    {
        $dir = realpath(__DIR__ . '/../' . ltrim($relativeFolder, '/\\'));
        if ($dir === false || !is_dir($dir)) {
            return [];
        }

        $allowed = null;
        if (is_array($allowedExtensions) && $allowedExtensions) {
            $allowed = array_fill_keys(array_map('strtolower', $allowedExtensions), true);
        }
        $items = [];
        $root = str_replace('\\', '/', realpath(__DIR__ . '/..') ?: '');

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo instanceof SplFileInfo || !$fileInfo->isFile()) {
                continue;
            }

            $ext = strtolower($fileInfo->getExtension());
            if (is_array($allowed) && !isset($allowed[$ext])) {
                continue;
            }

            $real = str_replace('\\', '/', $fileInfo->getPathname());
            if ($root === '' || stripos($real, $root . '/') !== 0) {
                continue;
            }

            $publicPath = ltrim(substr($real, strlen($root)), '/');
            $row = [
                'name' => $fileInfo->getBasename(),
                'title' => resource_library_title_from_filename($fileInfo->getBasename()),
                'extension' => $ext,
                'size' => (int) $fileInfo->getSize(),
                'modified' => (int) $fileInfo->getMTime(),
                'path' => $publicPath,
                'url' => url($publicPath),
            ];

            if ($matcher($row)) {
                $items[] = $row;
            }
        }

        usort($items, static function (array $a, array $b): int {
            return $b['modified'] <=> $a['modified'];
        });

        return $items;
    }
}
