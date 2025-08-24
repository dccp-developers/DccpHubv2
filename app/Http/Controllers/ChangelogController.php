<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\File;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;


use Carbon\Carbon;

final class ChangelogController extends Controller
{
    public function index(): Response
    {
        $releasesPath = base_path('release_descriptions');
        $releases = $this->getStructuredReleases($releasesPath);

        return Inertia::render('Changelog/Index', [
            'releases' => $releases,
            'totalReleases' => count($releases),
            'latestVersion' => $releases[0]['version'] ?? null,
        ]);
    }

    /**
     * Get structured release data instead of combined markdown
     */
    private function getStructuredReleases(string $releasesPath): array
    {
        if (!File::exists($releasesPath)) {
            return [];
        }

        $files = File::files($releasesPath);
        $markdownFiles = array_filter($files, function ($file) {
            return $file->getExtension() === 'md';
        });

        if (empty($markdownFiles)) {
            return [];
        }

        // Sort files by version (newest first)
        usort($markdownFiles, function ($a, $b) {
            $versionA = $this->extractVersionFromFilename($a->getFilename());
            $versionB = $this->extractVersionFromFilename($b->getFilename());
            return version_compare($versionB, $versionA); // Descending order
        });

        $releases = [];
        foreach ($markdownFiles as $file) {
            $content = File::get($file->getPathname());
            $release = $this->parseReleaseContent($content, $file->getFilename());
            if ($release) {
                $releases[] = $release;
            }
        }

        return $releases;
    }

    /**
     * Parse individual release content into structured data
     */
    private function parseReleaseContent(string $content, string $filename): ?array
    {
        $version = $this->extractVersionFromFilename($filename);

        // Simple markdown converter
        $environment = new Environment();
        $environment->addExtension(new GithubFlavoredMarkdownExtension());

        $converter = new CommonMarkConverter([
            'allow_unsafe_links' => false,
        ], $environment);

        $html = $converter->convert($content)->getContent();

        // Extract title from first heading
        $title = "Version {$version}";
        if (preg_match('/^# (.+)/m', $content, $matches)) {
            $title = trim($matches[1]);
        }

        // Try to extract date from content
        $date = null;
        if (preg_match('/Built:\s*(.+)/i', $content, $matches)) {
            try {
                $date = Carbon::parse(trim($matches[1]))->format('Y-m-d');
            } catch (\Exception) {
                $date = null;
            }
        }

        return [
            'version' => $version,
            'title' => $title,
            'content' => $html,
            'date' => $date,
            'filename' => $filename,
        ];
    }



    /**
     * Extract version number from filename (e.g., "v1.1.0.md" -> "1.1.0")
     */
    private function extractVersionFromFilename(string $filename): string
    {
        // Remove .md extension
        $filename = str_replace('.md', '', $filename);
        
        // Extract version number (e.g., "v1.1.0" -> "1.1.0")
        if (preg_match('/v?(\d+\.\d+\.\d+)/', $filename, $matches)) {
            return $matches[1];
        }
        
        return '0.0.0'; // Default version for sorting
    }
}