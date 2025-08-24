<?php

declare(strict_types=1);

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\File;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;

final class ChangelogController extends Controller
{
    /**
     * Display the changelog page
     */
    public function index(): Response
    {
        $releasesPath = base_path('release_descriptions');
        $markdown = $this->getCombinedChangelog($releasesPath);

        // Configure the Environment
        $environment = new Environment([
            'table_of_contents' => [
                'html_class' => 'toc',
                'position' => 'top',
                'style' => 'bullet',
                'min_heading_level' => 1,
                'max_heading_level' => 6,
                'normalize' => 'relative',
                'placeholder' => null,
            ],
            'heading_permalink' => [
                'html_class' => 'heading-permalink',
                'id_prefix' => 'content',
                'fragment_prefix' => 'content',
                'insert' => 'before',
                'min_heading_level' => 1,
                'max_heading_level' => 6,
                'title' => 'Permalink',
                'symbol' => '#',
                'aria_hidden' => false,
            ],
        ]);

        // Add GFM extension
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new HeadingPermalinkExtension());
        $environment->addExtension(new TableOfContentsExtension());

        // Create the converter
        $converter = new CommonMarkConverter([
            'allow_unsafe_links' => false, // For security
        ], $environment);

        $html = $converter->convert($markdown)->getContent();

        return Inertia::render('Faculty/Changelog', [
            'changelogHtml' => $html,
        ]);
    }

    /**
     * Get combined changelog from all release description files
     */
    private function getCombinedChangelog(string $releasesPath): string
    {
        if (!File::exists($releasesPath)) {
            return "# Changelog\n\nNo release information available.";
        }

        $files = File::files($releasesPath);
        $markdownFiles = array_filter($files, function ($file) {
            return $file->getExtension() === 'md';
        });

        if (empty($markdownFiles)) {
            return "# Changelog\n\nNo release information available.";
        }

        // Sort files by version (newest first)
        usort($markdownFiles, function ($a, $b) {
            $versionA = $this->extractVersionFromFilename($a->getFilename());
            $versionB = $this->extractVersionFromFilename($b->getFilename());
            return version_compare($versionB, $versionA); // Descending order
        });

        $combinedMarkdown = "# DCCPHub Mobile App - Faculty Changelog\n\n";
        $combinedMarkdown .= "This page contains all release notes and changelog information for the DCCPHub mobile application.\n\n";
        $combinedMarkdown .= "---\n\n";

        foreach ($markdownFiles as $file) {
            $content = File::get($file->getPathname());
            $combinedMarkdown .= $content . "\n\n---\n\n";
        }

        return $combinedMarkdown;
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
