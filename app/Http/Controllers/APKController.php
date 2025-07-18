<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Inertia\Inertia;

class APKController extends Controller
{
    /**
     * Show the APK download page
     */
    public function downloadPage()
    {
        return Inertia::render('APK/Download');
    }

    /**
     * Generate APK using Capacitor
     */
    public function generateAPK(Request $request)
    {
        try {
            // Ensure the APK directory exists
            $apkPath = storage_path('app/apk');
            if (!file_exists($apkPath)) {
                mkdir($apkPath, 0755, true);
            }

            Log::info('Starting APK generation process');

            // Run the Capacitor build script
            $scriptPath = base_path('scripts/build-apk-capacitor.sh');

            if (!file_exists($scriptPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Build script not found'
                ], 500);
            }

            // Make script executable
            chmod($scriptPath, 0755);

            // Run the build script
            $result = Process::path(base_path())
                ->timeout(600) // 10 minutes timeout
                ->run('bash ' . $scriptPath);

            if ($result->failed()) {
                Log::error('APK generation failed: ' . $result->errorOutput());
                return response()->json([
                    'success' => false,
                    'message' => 'APK generation failed',
                    'error' => $result->errorOutput(),
                    'output' => $result->output()
                ], 500);
            }

            // Find the generated APK file
            $apkFiles = glob($apkPath . '/*.apk');
            if (empty($apkFiles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'APK file not found after generation',
                    'output' => $result->output()
                ], 500);
            }

            // Get the latest APK file
            $latestApk = $apkPath . '/DCCPHub_latest.apk';
            if (file_exists($latestApk)) {
                $fileName = 'DCCPHub_latest.apk';
            } else {
                // Fallback to the most recent file
                usort($apkFiles, function($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                $latestApk = $apkFiles[0];
                $fileName = basename($latestApk);
            }

            Log::info('APK generation completed successfully', ['filename' => $fileName]);

            return response()->json([
                'success' => true,
                'message' => 'APK generated successfully',
                'filename' => $fileName,
                'download_url' => route('apk.download', ['filename' => $fileName]),
                'size' => filesize($latestApk),
                'created_at' => date('Y-m-d H:i:s', filemtime($latestApk))
            ]);

        } catch (\Exception $e) {
            Log::error('APK generation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during APK generation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download APK file
     */
    public function downloadAPK(Request $request, string $filename)
    {
        // Sanitize filename to prevent directory traversal
        $filename = basename($filename);
        $apkPath = storage_path('app/apk/' . $filename);

        if (!file_exists($apkPath)) {
            Log::warning('APK download attempted for non-existent file', ['filename' => $filename]);
            abort(404, 'APK file not found');
        }

        // Verify it's actually an APK file
        if (!str_ends_with(strtolower($filename), '.apk')) {
            abort(400, 'Invalid file type');
        }

        Log::info('APK download started', [
            'filename' => $filename,
            'size' => filesize($apkPath),
            'user_agent' => $request->userAgent()
        ]);

        // Determine download filename
        $downloadName = str_starts_with($filename, 'DCCPHub_') ? $filename : 'DCCPHub.apk';

        return response()->download($apkPath, $downloadName, [
            'Content-Type' => 'application/vnd.android.package-archive',
            'Content-Disposition' => 'attachment; filename="' . $downloadName . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    }

    /**
     * Get APK status and available downloads
     */
    public function getAPKStatus()
    {
        $apkPath = storage_path('app/apk');
        $apkFiles = [];

        if (file_exists($apkPath)) {
            $files = glob($apkPath . '/*.apk');

            // Sort files by modification time (newest first)
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });

            foreach ($files as $file) {
                $filename = basename($file);

                // Skip symlinks in the listing but still process them
                if (is_link($file)) {
                    continue;
                }

                $apkFiles[] = [
                    'filename' => $filename,
                    'size' => filesize($file),
                    'size_human' => $this->formatBytes(filesize($file)),
                    'created_at' => date('Y-m-d H:i:s', filemtime($file)),
                    'download_url' => url('/storage/apk/' . $filename),
                    'is_latest' => $filename === 'DCCPHub_latest.apk' ||
                                  (file_exists($apkPath . '/DCCPHub_latest.apk') &&
                                   realpath($file) === realpath($apkPath . '/DCCPHub_latest.apk'))
                ];
            }
        }

        // Find the latest APK (either the symlink target or the most recent file)
        $latestApk = null;
        $latestApkPath = $apkPath . '/DCCPHub_latest.apk';

        if (file_exists($latestApkPath)) {
            $latestApk = [
                'filename' => 'DCCPHub_latest.apk',
                'size' => filesize($latestApkPath),
                'size_human' => $this->formatBytes(filesize($latestApkPath)),
                'created_at' => date('Y-m-d H:i:s', filemtime($latestApkPath)),
                'download_url' => url('/storage/apk/DCCPHub_latest.apk'),
                'is_latest' => true
            ];
        } elseif (!empty($apkFiles)) {
            $latestApk = $apkFiles[0];
        }

        return response()->json([
            'success' => true,
            'apk_files' => $apkFiles,
            'latest_apk' => $latestApk,
            'total_files' => count($apkFiles),
            'storage_path' => $apkPath
        ]);
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
