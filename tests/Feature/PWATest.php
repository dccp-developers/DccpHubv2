<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class PWATest extends TestCase
{

    /**
     * Test that the manifest.json file is accessible
     */
    public function test_manifest_json_is_accessible(): void
    {
        $response = $this->get('/manifest.json');
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        
        $manifest = $response->json();
        
        // Check required PWA manifest fields
        $this->assertArrayHasKey('name', $manifest);
        $this->assertArrayHasKey('short_name', $manifest);
        $this->assertArrayHasKey('start_url', $manifest);
        $this->assertArrayHasKey('display', $manifest);
        $this->assertArrayHasKey('icons', $manifest);
        $this->assertArrayHasKey('theme_color', $manifest);
        $this->assertArrayHasKey('background_color', $manifest);
        
        // Check specific values
        $this->assertEquals('DCCPHub', $manifest['name']);
        $this->assertEquals('DCCPHub', $manifest['short_name']);
        $this->assertEquals(config('app.url') . '/', $manifest['start_url']);
        $this->assertEquals('standalone', $manifest['display']);
        
        // Check icons array is not empty
        $this->assertNotEmpty($manifest['icons']);
        
        // Check first icon has required fields
        $firstIcon = $manifest['icons'][0];
        $this->assertArrayHasKey('src', $firstIcon);
        $this->assertArrayHasKey('sizes', $firstIcon);
        $this->assertArrayHasKey('type', $firstIcon);
    }

    /**
     * Test that the service worker is accessible
     */
    public function test_service_worker_is_accessible(): void
    {
        $response = $this->get('/sw.js');
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/javascript');
    }

    /**
     * Test PWA meta tags are present in the main layout
     */
    public function test_pwa_meta_tags_are_present(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        
        // Check for PWA-related meta tags
        $response->assertSee('name="theme-color"', false);
        $response->assertSee('rel="manifest"', false);
        $response->assertSee('name="apple-mobile-web-app-capable"', false);
        $response->assertSee('name="apple-mobile-web-app-status-bar-style"', false);
    }

    /**
     * Test APK status endpoint
     */
    public function test_apk_status_endpoint(): void
    {
        $response = $this->get('/apk/status');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'apk_files',
            'latest_apk'
        ]);
        
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertIsArray($data['apk_files']);
    }

    /**
     * Test APK generation endpoint (without actually generating)
     */
    public function test_apk_generation_endpoint_exists(): void
    {
        // This test just checks that the route exists and is accessible
        // Actual APK generation would require external dependencies
        $response = $this->post('/apk/generate');
        
        // Should return either success or error, but not 404
        $this->assertNotEquals(404, $response->status());
    }

    /**
     * Test APK download endpoint with non-existent file
     */
    public function test_apk_download_with_nonexistent_file(): void
    {
        $response = $this->get('/apk/download/nonexistent.apk');
        
        $response->assertStatus(404);
    }

    /**
     * Test that required PWA icons exist
     */
    public function test_pwa_icons_exist(): void
    {
        $iconSizes = ['48-48', '72-72', '96-96', '144-144', '192-192', '512-512'];
        
        foreach ($iconSizes as $size) {
            $iconPath = public_path("images/android/android-launchericon-{$size}.png");
            $this->assertFileExists($iconPath, "Icon for size {$size} should exist");
        }
    }

    /**
     * Test PWA configuration file exists
     */
    public function test_pwa_config_exists(): void
    {
        $configPath = config_path('pwa.php');
        $this->assertFileExists($configPath, 'PWA configuration file should exist');
        
        $config = include $configPath;
        $this->assertIsArray($config);
        $this->assertArrayHasKey('name', $config);
        $this->assertArrayHasKey('manifest', $config);
    }

    /**
     * Test workbox configuration exists
     */
    public function test_workbox_config_exists(): void
    {
        $workboxConfigPath = base_path('workbox-config.cjs');
        $this->assertFileExists($workboxConfigPath, 'Workbox configuration should exist');
    }

    /**
     * Test PWA Builder configuration exists
     */
    public function test_pwa_builder_config_exists(): void
    {
        $pwaBuilderConfigPath = base_path('pwa-builder.config.json');
        $this->assertFileExists($pwaBuilderConfigPath, 'PWA Builder configuration should exist');
        
        $config = json_decode(file_get_contents($pwaBuilderConfigPath), true);
        $this->assertIsArray($config);
        $this->assertArrayHasKey('name', $config);
        $this->assertArrayHasKey('platforms', $config);
        $this->assertArrayHasKey('android', $config['platforms']);
    }

    /**
     * Test that APK build script exists and is executable
     */
    public function test_apk_build_script_exists(): void
    {
        $scriptPath = base_path('scripts/build-apk.sh');
        $this->assertFileExists($scriptPath, 'APK build script should exist');
        $this->assertTrue(is_executable($scriptPath), 'APK build script should be executable');
    }

    /**
     * Test that npm scripts for APK building are defined
     */
    public function test_npm_apk_scripts_defined(): void
    {
        $packageJsonPath = base_path('package.json');
        $this->assertFileExists($packageJsonPath, 'package.json should exist');
        
        $packageJson = json_decode(file_get_contents($packageJsonPath), true);
        $this->assertArrayHasKey('scripts', $packageJson);
        $this->assertArrayHasKey('build:apk', $packageJson['scripts']);
        $this->assertArrayHasKey('apk:generate', $packageJson['scripts']);
    }

    /**
     * Test that PWA Builder CLI is installed
     */
    public function test_pwa_builder_cli_installed(): void
    {
        $packageJsonPath = base_path('package.json');
        $packageJson = json_decode(file_get_contents($packageJsonPath), true);
        
        $this->assertArrayHasKey('devDependencies', $packageJson);
        $this->assertArrayHasKey('@pwabuilder/cli', $packageJson['devDependencies']);
    }
}
