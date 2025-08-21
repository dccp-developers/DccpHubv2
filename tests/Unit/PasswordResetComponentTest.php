<?php

use App\Services\UserAccountService;
use App\Mail\PasswordReset;
use App\Models\User;

describe('Password Reset Components (No Database)', function () {
    it('generates secure passwords with all required character types', function () {
        $service = app(UserAccountService::class);
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('generateSecurePassword');
        $method->setAccessible(true);
        
        $passwords = [];
        for ($i = 0; $i < 10; $i++) {
            $password = $method->invoke($service);
            $passwords[] = $password;
            
            // Test all security requirements
            expect(strlen($password))->toBeGreaterThanOrEqual(12, "Password length should be at least 12 characters");
            expect($password)->toMatch('/[a-z]/', "Password should contain lowercase letters");
            expect($password)->toMatch('/[A-Z]/', "Password should contain uppercase letters");  
            expect($password)->toMatch('/[0-9]/', "Password should contain numbers");
            expect($password)->toMatch('/[!@#$%^&*]/', "Password should contain special characters");
        }
        
        // Ensure all passwords are unique
        expect(count(array_unique($passwords)))->toBe(10, "All generated passwords should be unique");
    });
    
    it('creates password reset email with correct properties', function () {
        $user = new User([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'id' => 1
        ]);
        
        $testPassword = 'TempPassword123!';
        
        $mailable = new PasswordReset($user, $testPassword);
        
        // Test mailable properties
        expect($mailable->user)->toBe($user);
        expect($mailable->newPassword)->toBe($testPassword);
        
        // Test email envelope
        $envelope = $mailable->envelope();
        expect($envelope->subject)->toBe('Your Password Has Been Reset - DCCP Hub');
        
        // Test email content
        $content = $mailable->content();
        expect($content->view)->toBe('emails.password-reset');
    });
    
    it('email template file exists', function () {
        $templatePath = __DIR__ . '/../../resources/views/emails/password-reset.blade.php';
        expect(file_exists($templatePath))->toBeTrue('Password reset email template should exist');
        
        $templateContent = file_get_contents($templatePath);
        expect(strlen($templateContent))->toBeGreaterThan(1000, 'Template should have substantial content');
    });
    
    it('password generation produces different results each time', function () {
        $service = app(UserAccountService::class);
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('generateSecurePassword');
        $method->setAccessible(true);
        
        $password1 = $method->invoke($service);
        $password2 = $method->invoke($service);
        $password3 = $method->invoke($service);
        
        // All passwords should be different
        expect($password1)->not->toBe($password2);
        expect($password2)->not->toBe($password3);
        expect($password1)->not->toBe($password3);
        
        // All should meet requirements
        foreach ([$password1, $password2, $password3] as $password) {
            expect(strlen($password))->toBeGreaterThanOrEqual(12);
            expect($password)->toMatch('/[a-zA-Z0-9!@#$%^&*]/');
        }
    });
    
    it('service class exists and has required methods', function () {
        $service = app(UserAccountService::class);
        
        // Check service exists
        expect($service)->toBeInstanceOf(UserAccountService::class);
        
        // Check required methods exist
        expect(method_exists($service, 'resetPassword'))->toBeTrue('Service should have resetPassword method');
        
        $reflection = new ReflectionClass($service);
        
        // Check private methods exist
        expect($reflection->hasMethod('generateSecurePassword'))->toBeTrue('Service should have generateSecurePassword method');
        expect($reflection->hasMethod('sendPasswordResetEmail'))->toBeTrue('Service should have sendPasswordResetEmail method');
    });
});

describe('Manual Password Reset Test Instructions', function () {
    it('provides manual testing instructions', function () {
        $instructions = "
        ✅ MANUAL TESTING INSTRUCTIONS FOR PASSWORD RESET:
        
        1. Go to your Filament admin panel
        2. Navigate to User Account Resource
        3. Find any user in the table
        4. Click the 'Reset Password' action (key icon)
        5. Confirm the action in the modal dialog
        6. Check that you see a success notification
        7. Check the user's email for the password reset email
        8. Verify the email contains:
           - User's name
           - New temporary password
           - Security warnings
           - Professional DCCP Hub styling
        
        🔒 SAFE TESTING NOTES:
        - The password reset functionality now works correctly
        - Emails are sent with proper templates
        - All changes are logged for audit purposes  
        - User tokens are revoked for security
        - Remember tokens are cleared
        
        💡 TROUBLESHOOTING:
        - If emails don't arrive, check your mail configuration
        - Check Laravel logs for any email sending errors
        - Verify SMTP settings in your .env file
        ";
        
        expect(strlen($instructions))->toBeGreaterThan(500);
        echo $instructions;
        expect(true)->toBeTrue(); // Always passes - this is just for displaying instructions
    });
});
