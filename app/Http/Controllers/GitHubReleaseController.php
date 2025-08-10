<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;

class GitHubReleaseController extends Controller
{
    private const GITHUB_API_BASE = 'https://api.github.com';
    private const REPO_OWNER = 'yukazakiri';
    private const REPO_NAME = 'DccpHubv2';
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Get the latest release information
     */
    public function getLatestRelease(): JsonResponse
    {
        try {
            $cacheKey = 'github_latest_release';

            $release = Cache::remember($cacheKey, self::CACHE_TTL, function () {
                return $this->fetchLatestReleaseFromGitHub();
            });

            if (!$release) {
                return response()->json([
                    'success' => false,
                    'message' => 'No releases found',
                    'fallback_available' => $this->hasLocalAPK()
                ], 404);
            }

            return response()->json([
                'success' => true,
                'release' => $release,
                'fallback_available' => $this->hasLocalAPK()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch latest release: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch release information',
                'error' => $e->getMessage(),
                'fallback_available' => $this->hasLocalAPK()
            ], 500);
        }
    }

    /**
     * Get all releases
     */
    public function getAllReleases(): JsonResponse
    {
        try {
            $cacheKey = 'github_all_releases';

            $releases = Cache::remember($cacheKey, self::CACHE_TTL, function () {
                return $this->fetchAllReleasesFromGitHub();
            });

            return response()->json([
                'success' => true,
                'releases' => $releases,
                'total' => count($releases),
                'fallback_available' => $this->hasLocalAPK()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch releases: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch releases',
                'error' => $e->getMessage(),
                'fallback_available' => $this->hasLocalAPK()
            ], 500);
        }
    }

    /**
     * Download the latest APK (redirect to GitHub)
     */
    public function downloadLatestAPK(): RedirectResponse
    {
        try {
            $release = Cache::remember('github_latest_release', self::CACHE_TTL, function () {
                return $this->fetchLatestReleaseFromGitHub();
            });

            if ($release && isset($release['apk_download_url'])) {
                Log::info('Redirecting to GitHub release APK download', [
                    'version' => $release['tag_name'],
                    'url' => $release['apk_download_url']
                ]);

                return redirect($release['apk_download_url']);
            }

            // Fallback to local APK if GitHub release not available
            Log::warning('GitHub release not available, falling back to local APK');
            return redirect()->route('apk.download', ['filename' => 'DCCPHub_latest.apk']);
        } catch (\Exception $e) {
            Log::error('Failed to download latest APK: ' . $e->getMessage());

            // Fallback to local APK on error
            return redirect()->route('apk.download', ['filename' => 'DCCPHub_latest.apk']);
        }
    }

    /**
     * Download a specific release APK (redirect to GitHub)
     */
    public function downloadReleaseAPK(string $version): RedirectResponse
    {
        try {
            $cacheKey = "github_release_{$version}";

            $release = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($version) {
                return $this->fetchSpecificReleaseFromGitHub($version);
            });

            if ($release && isset($release['apk_download_url'])) {
                Log::info('Redirecting to specific GitHub release APK download', [
                    'version' => $version,
                    'url' => $release['apk_download_url']
                ]);

                return redirect($release['apk_download_url']);
            }

            return redirect()->route('apk.download', ['filename' => 'DCCPHub_latest.apk'])
                ->with('warning', "Release {$version} not found, downloading latest local APK instead.");
        } catch (\Exception $e) {
            Log::error("Failed to download release {$version}: " . $e->getMessage());

            return redirect()->route('apk.download', ['filename' => 'DCCPHub_latest.apk'])
                ->with('error', 'Failed to download requested version, downloading latest local APK instead.');
        }
    }

    /**
     * Render a changelog page built from GitHub releases and commit messages.
     */
    public function releasesPage(): Response
    {
        $releases = Cache::remember('github_all_releases_with_commits', self::CACHE_TTL, function () {
            $releases = $this->fetchAllReleasesFromGitHub();
            // Releases API returns latest first; compute commits between current and next
            foreach ($releases as $index => &$release) {
                $currentTag = $release['tag_name'] ?? null;
                $previousTag = $releases[$index + 1]['tag_name'] ?? null;
                $release['commits'] = [];
                if ($currentTag && $previousTag) {
                    $release['commits'] = $this->fetchCommitsBetweenTags($previousTag, $currentTag);
                }
                // Prepare HTML from body markdown if present
                $release['body_html'] = $this->convertMarkdownToHtml($release['body'] ?? '');
                // Group commits by conventional type
                $release['commit_groups'] = $this->groupCommitsByType($release['commits']);
            }
            return $releases;
        });

        return Inertia::render('Changelog/Releases', [
            'releases' => $releases,
            'repo' => [
                'owner' => self::REPO_OWNER,
                'name' => self::REPO_NAME,
                'url' => 'https://github.com/' . self::REPO_OWNER . '/' . self::REPO_NAME,
            ],
        ]);
    }

    /**
     * Fetch latest release from GitHub API
     */
    private function fetchLatestReleaseFromGitHub(): ?array
    {
        $url = self::GITHUB_API_BASE . '/repos/' . self::REPO_OWNER . '/' . self::REPO_NAME . '/releases/latest';

        $response = Http::timeout(10)->get($url);

        if (!$response->successful()) {
            Log::warning('GitHub API request failed', [
                'status' => $response->status(),
                'url' => $url
            ]);
            return null;
        }

        $data = $response->json();
        return $this->formatReleaseData($data);
    }

    /**
     * Fetch all releases from GitHub API
     */
    private function fetchAllReleasesFromGitHub(): array
    {
        $url = self::GITHUB_API_BASE . '/repos/' . self::REPO_OWNER . '/' . self::REPO_NAME . '/releases';

        $response = $this->httpClient()->get($url, [
            'per_page' => 10 // Limit to last 10 releases
        ]);

        if (!$response->successful()) {
            Log::warning('GitHub API request for all releases failed', [
                'status' => $response->status(),
                'url' => $url
            ]);
            return [];
        }

        $releases = $response->json();
        return array_map([$this, 'formatReleaseData'], $releases);
    }

    /**
     * Fetch specific release from GitHub API
     */
    private function fetchSpecificReleaseFromGitHub(string $version): ?array
    {
        // Ensure version has 'v' prefix for GitHub API
        $tag = str_starts_with($version, 'v') ? $version : "v{$version}";

        $url = self::GITHUB_API_BASE . '/repos/' . self::REPO_OWNER . '/' . self::REPO_NAME . '/releases/tags/' . $tag;

        $response = $this->httpClient()->get($url);

        if (!$response->successful()) {
            Log::warning("GitHub API request for release {$tag} failed", [
                'status' => $response->status(),
                'url' => $url
            ]);
            return null;
        }

        $data = $response->json();
        return $this->formatReleaseData($data);
    }

    /**
     * Format release data for consistent response
     */
    private function formatReleaseData(array $releaseData): array
    {
        // Find APK asset
        $apkAsset = null;
        foreach ($releaseData['assets'] ?? [] as $asset) {
            if (str_ends_with(strtolower($asset['name']), '.apk')) {
                $apkAsset = $asset;
                break;
            }
        }

        return [
            'id' => $releaseData['id'],
            'tag_name' => $releaseData['tag_name'],
            'name' => $releaseData['name'],
            'body' => $releaseData['body'],
            'published_at' => $releaseData['published_at'],
            'html_url' => $releaseData['html_url'],
            'prerelease' => $releaseData['prerelease'],
            'draft' => $releaseData['draft'],
            'apk_available' => $apkAsset !== null,
            'apk_name' => $apkAsset['name'] ?? null,
            'apk_size' => $apkAsset['size'] ?? null,
            'apk_size_human' => $apkAsset ? $this->formatBytes($apkAsset['size']) : null,
            'apk_download_url' => $apkAsset['browser_download_url'] ?? null,
            'apk_download_count' => $apkAsset['download_count'] ?? 0,
        ];
    }

    /**
     * Check if local APK exists as fallback
     */
    private function hasLocalAPK(): bool
    {
        return file_exists(storage_path('app/apk/DCCPHub_latest.apk'));
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Clear release cache
     */
    public function clearCache(): JsonResponse
    {
        Cache::forget('github_latest_release');
        Cache::forget('github_all_releases');
        Cache::forget('github_all_releases_with_commits');

        // Clear specific release caches (this is a simple approach)
        for ($i = 1; $i <= 10; $i++) {
            Cache::forget("github_release_v{$i}.0.0");
            Cache::forget("github_release_{$i}.0.0");
        }

        return response()->json([
            'success' => true,
            'message' => 'Release cache cleared successfully'
        ]);
    }

    /**
     * Fetch commits between two tags using GitHub compare API
     * @return array<int, array{sha:string,message:string,author:?string,author_avatar:?string,url:string,date:?string}>
     */
    private function fetchCommitsBetweenTags(string $baseTag, string $headTag): array
    {
        // Ensure both tags have 'v' prefix consistency
        $base = str_starts_with($baseTag, 'v') ? $baseTag : "v{$baseTag}";
        $head = str_starts_with($headTag, 'v') ? $headTag : "v{$headTag}";

        $url = self::GITHUB_API_BASE . '/repos/' . self::REPO_OWNER . '/' . self::REPO_NAME . "/compare/{$base}...{$head}";
        $response = $this->httpClient()->get($url);
        if (!$response->successful()) {
            Log::warning('GitHub compare API failed', [
                'status' => $response->status(),
                'url' => $url,
                'base' => $base,
                'head' => $head,
            ]);
            return [];
        }
        $data = $response->json();
        $commits = $data['commits'] ?? [];
        return array_map(function ($commit) {
            $c = $commit['commit'] ?? [];
            return [
                'sha' => $commit['sha'] ?? '',
                'message' => $c['message'] ?? '',
                'author' => $commit['author']['login'] ?? ($c['author']['name'] ?? null),
                'author_avatar' => $commit['author']['avatar_url'] ?? null,
                'url' => $commit['html_url'] ?? '',
                'date' => $c['author']['date'] ?? null,
            ];
        }, $commits);
    }

    /**
     * Group commits by conventional commit type derived from the message prefix
     */
    private function groupCommitsByType(array $commits): array
    {
        $groups = [
            'feat' => [],
            'fix' => [],
            'perf' => [],
            'refactor' => [],
            'docs' => [],
            'chore' => [],
            'test' => [],
            'build' => [],
            'ci' => [],
            'others' => [],
        ];

        foreach ($commits as $commit) {
            $message = $commit['message'] ?? '';
            if (preg_match('/^(feat|fix|perf|refactor|docs|chore|test|build|ci)(\(.+\))?:/i', $message, $m)) {
                $type = strtolower($m[1]);
                $groups[$type][] = $commit;
            } else {
                $groups['others'][] = $commit;
            }
        }

        // Remove empty groups
        return array_filter($groups, fn($items) => !empty($items));
    }

    /**
     * Minimal Markdown to HTML conversion for release body
     */
    private function convertMarkdownToHtml(string $markdown): string
    {
        if ($markdown === '') {
            return '';
        }
        $environment = new Environment();
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $converter = new CommonMarkConverter([], $environment);
        return $converter->convert($markdown)->getContent();
    }

    /**
     * GitHub Http client with optional token and headers
     */
    private function httpClient()
    {
        $token = config('services.github.token') ?? env('GITHUB_TOKEN');
        $client = Http::timeout(15)
            ->withHeaders([
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
            ]);
        if (!empty($token)) {
            $client = $client->withToken($token);
        }
        return $client;
    }
}
