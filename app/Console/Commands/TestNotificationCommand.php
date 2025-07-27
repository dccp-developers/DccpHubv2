<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Facades\Notify;
use App\Models\User;

class TestNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:test {--user=} {--type=info} {--all-faculty}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send test notifications to users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $userId = $this->option('user');
        $allFaculty = $this->option('all-faculty');

        if ($allFaculty) {
            $this->sendToAllFaculty($type);
        } elseif ($userId) {
            $this->sendToUser($userId, $type);
        } else {
            $this->info('Please specify either --user=ID or --all-faculty');
            return;
        }
    }

    /**
     * Send notification to a specific user.
     */
    private function sendToUser(int $userId, string $type)
    {
        $user = User::find($userId);

        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return;
        }

        $messages = [
            'info' => 'This is a test information notification sent via command line.',
            'success' => 'Test notification sent successfully via command line!',
            'warning' => 'This is a test warning notification sent via command line.',
            'error' => 'This is a test error notification sent via command line.',
        ];

        $message = $messages[$type] ?? $messages['info'];

        try {
            Notify::$type(
                $user,
                'Test Notification from Command',
                $message,
                ['source' => 'command_line', 'timestamp' => now()->toISOString()],
                route('faculty.dashboard'),
                'View Dashboard'
            );

            $this->info("Test {$type} notification sent to {$user->name} ({$user->email})");
        } catch (\Exception $e) {
            $this->error("Failed to send notification: {$e->getMessage()}");
        }
    }

    /**
     * Send notification to all faculty.
     */
    private function sendToAllFaculty(string $type)
    {
        $messages = [
            'info' => 'This is a test information notification sent to all faculty via command line.',
            'success' => 'Test notification sent successfully to all faculty via command line!',
            'warning' => 'This is a test warning notification sent to all faculty via command line.',
            'error' => 'This is a test error notification sent to all faculty via command line.',
        ];

        $message = $messages[$type] ?? $messages['info'];

        try {
            Notify::toFaculty(
                'Test Notification to All Faculty',
                $message,
                $type,
                ['source' => 'command_line', 'timestamp' => now()->toISOString()],
                route('faculty.dashboard'),
                'View Dashboard'
            );

            $facultyCount = User::where('role', 'faculty')->count();
            $this->info("Test {$type} notification sent to {$facultyCount} faculty members");
        } catch (\Exception $e) {
            $this->error("Failed to send notification: {$e->getMessage()}");
        }
    }
}
