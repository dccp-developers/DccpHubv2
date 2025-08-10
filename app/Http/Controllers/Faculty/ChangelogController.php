<?php

declare(strict_types=1);

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class ChangelogController extends Controller
{
    /**
     * Display the changelog page
     */
    public function index(): Response
    {
        $releases = $this->getReleases();
        $currentVersion = $this->getCurrentVersion();

        return Inertia::render('Faculty/Changelog', [
            'releases' => $releases,
            'currentVersion' => $currentVersion,
        ]);
    }

    /**
     * Get all releases data
     */
    private function getReleases(): array
    {
        return [
            [
                'version' => 'v1.1.0',
                'date' => '2025-08-10',
                'isLatest' => true,
                'title' => 'Major Feature Release - Complete Academic Management System',
                'description' => 'Comprehensive attendance management, faculty tools, and major UI/UX improvements that transform DCCPHub into a full-featured academic management platform.',
                'features' => [
                    'Complete Attendance Management System',
                    'Advanced Faculty Tools',
                    'Missing Student Request System',
                    'Faculty Deadline Management',
                    'Mobile-First UI/UX Overhaul',
                    'Real-time Data Integration',
                ],
                'technical' => [
                    'New Database Models (MissingStudentRequest, FacultyDeadline)',
                    'Filament Admin Integration',
                    'Enhanced Services (ClassExportService)',
                    'Component Restructuring',
                    'Improved Error Handling',
                ],
                'fixes' => [
                    'Fixed Type Errors in FacultyClassController',
                    'Improved Data Processing',
                    'Better Component Performance',
                    'Navigation Issues Resolved',
                ],
                'content' => '
                    <h3>✨ Major New Features</h3>
                    <h4>📊 Complete Attendance Management System</h4>
                    <ul>
                        <li><strong>Real-time Attendance Tracking</strong>: Full attendance system with live statistics and analytics</li>
                        <li><strong>Faculty Attendance Dashboard</strong>: Comprehensive dashboard for managing class attendance</li>
                        <li><strong>Attendance Reports & Analytics</strong>: Detailed reporting with export capabilities (Excel, PDF, CSV)</li>
                        <li><strong>Automated Alerts</strong>: Smart alerts for low attendance rates and missing students</li>
                    </ul>
                    <h4>👨‍🏫 Advanced Faculty Tools</h4>
                    <ul>
                        <li><strong>Missing Student Request System</strong>: Faculty can report missing students via modal forms</li>
                        <li><strong>Faculty Deadline Management</strong>: Complete deadline tracking with priority levels</li>
                        <li><strong>Enhanced Class Management</strong>: Improved statistics and data handling</li>
                        <li><strong>Grade Export System</strong>: CSV/Excel import and export capabilities</li>
                    </ul>
                    <h4>📱 Mobile-First UI/UX Overhaul</h4>
                    <ul>
                        <li><strong>Mobile Bottom Navigation</strong>: Enhanced mobile experience with intuitive navigation</li>
                        <li><strong>Responsive Layout System</strong>: Improved responsiveness with safe area insets</li>
                        <li><strong>Loading Screen Management</strong>: Better content loading feedback</li>
                    </ul>
                '
            ],
            [
                'version' => 'v1.0.2',
                'date' => '2025-08-07',
                'isLatest' => false,
                'title' => 'Icon & Visual Improvements',
                'description' => 'Fixed Android app icon display issues and improved visual experience across different Android versions.',
                'features' => [
                    'Adaptive Icon Support for Android 8.0+',
                    'PWA Icon Set',
                    'Enhanced Analytics (Umami Cloud)',
                ],
                'technical' => [
                    'Icon Generation Pipeline',
                    'PWA Integration',
                    'Build Cleanup',
                    'Asset Optimization',
                ],
                'fixes' => [
                    'App Drawer Icon Display Issues',
                    'Adaptive Icon Sizing Problems',
                    'Icon Background Color',
                    'Legacy Android Compatibility',
                ],
                'content' => '
                    <h3>✨ New Features</h3>
                    <ul>
                        <li><strong>Adaptive Icon Support</strong>: Complete implementation for Android 8.0+ devices</li>
                        <li><strong>PWA Icon Set</strong>: Comprehensive icons for various screen sizes</li>
                        <li><strong>Enhanced Analytics</strong>: Updated to use Umami Cloud analytics</li>
                    </ul>
                    <h3>🐛 Bug Fixes</h3>
                    <ul>
                        <li><strong>App Drawer Icon Fix</strong>: Resolved critical display issues</li>
                        <li><strong>Adaptive Icon Sizing</strong>: Fixed sizing across Android versions</li>
                        <li><strong>Legacy Compatibility</strong>: Maintained support for older Android versions</li>
                    </ul>
                    <h3>🎯 User Impact</h3>
                    <ul>
                        <li><strong>Better App Discovery</strong>: Correct display in app drawers</li>
                        <li><strong>Professional Appearance</strong>: Consistent icon across launchers</li>
                        <li><strong>Improved Branding</strong>: Enhanced visual identity</li>
                    </ul>
                '
            ],
            [
                'version' => 'v1.0.1',
                'date' => '2025-08-04',
                'isLatest' => false,
                'title' => 'Build System & Workflow Improvements',
                'description' => 'Streamlined build and release process for better APK distribution and GitHub integration.',
                'features' => [
                    'Committed APK Workflow',
                    'Enhanced Build Script',
                    'Streamlined GitHub Actions',
                ],
                'technical' => [
                    'Repository Structure Improvements',
                    'Build Process Optimization',
                    'Workflow Triggers Enhancement',
                    'Version Control Integration',
                ],
                'fixes' => [
                    'Build Reliability Issues',
                    'CI/CD Pipeline Problems',
                    'APK Distribution Inconsistencies',
                ],
                'content' => '
                    <h3>✨ New Features</h3>
                    <ul>
                        <li><strong>Committed APK Workflow</strong>: New build system that commits APKs directly to repository</li>
                        <li><strong>Enhanced Build Script</strong>: Interactive push confirmation and better error handling</li>
                        <li><strong>Streamlined GitHub Actions</strong>: Modified workflow to use pre-built APKs</li>
                    </ul>
                    <h3>🛠️ Technical Improvements</h3>
                    <ul>
                        <li><strong>Repository Structure</strong>: Added dedicated releases/ directory</li>
                        <li><strong>Build Process</strong>: Improved APK management and git integration</li>
                        <li><strong>Workflow Optimization</strong>: Enhanced triggers for tags and manual dispatch</li>
                    </ul>
                    <h3>🚀 What\'s Next</h3>
                    <p>This release establishes a more reliable build and distribution pipeline for future releases.</p>
                '
            ],
            [
                'version' => 'v1.0.0',
                'date' => '2025-08-04',
                'isLatest' => false,
                'title' => 'Initial DCCPHub Mobile App Release',
                'description' => 'The first official release of the DCCPHub Mobile Application, bringing academic management to your mobile device.',
                'features' => [
                    'GitHub Releases Integration',
                    'Google OAuth Authentication',
                    'Mobile-Optimized Interface',
                    'Download Page with CDN Fallback',
                    'Automated Build System',
                ],
                'technical' => [
                    'Capacitor Framework Integration',
                    'Android Gradle Plugin Setup',
                    'Custom DCCP Branding',
                    'Release Scripts',
                ],
                'fixes' => [],
                'content' => '
                    <h3>🎉 Core Features</h3>
                    <ul>
                        <li><strong>GitHub Releases Integration</strong>: Automated APK distribution via GitHub Releases</li>
                        <li><strong>Google OAuth Authentication</strong>: Secure login optimized for mobile devices</li>
                        <li><strong>Mobile-Optimized Interface</strong>: Custom DCCP branding with responsive design</li>
                        <li><strong>Download Page</strong>: GitHub CDN primary with local fallback for APK downloads</li>
                        <li><strong>Automated Build System</strong>: GitHub Actions for automated building and publishing</li>
                    </ul>
                    <h3>🔧 Technical Foundation</h3>
                    <ul>
                        <li>Built with Capacitor and Android Gradle Plugin</li>
                        <li>Targets Android API 34 (Android 14)</li>
                        <li>Minimum Android version: 7.0 (API 24)</li>
                        <li>Custom DCCP branding and logo integration</li>
                        <li>Release scripts for automated and manual creation</li>
                    </ul>
                '
            ]
        ];
    }

    /**
     * Get current app version
     */
    private function getCurrentVersion(): string
    {
        // You can get this from config, package.json, or environment
        return config('app.version', 'v1.1.0');
    }
}
