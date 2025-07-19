# Enhanced Social Authentication System

## Overview

The enhanced social authentication system ensures that users can only sign in via social providers (Google, etc.) if their email address exists in either the Student or Faculty records. This prevents unauthorized access and ensures proper role assignment.

## Key Features

### 1. **Role Detection and Validation**
- Validates email exists in Student or Faculty tables before account creation
- Automatically assigns correct role (student/faculty) based on record type
- Links User account to Student/Faculty record via `person_id` and `person_type`

### 2. **Consistent User Creation**
- Uses centralized `SocialAuthService` for all social login flows
- Maintains consistency between web and mobile OAuth implementations
- Proper error handling with user-friendly messages

### 3. **Enhanced Security**
- Prevents account creation for unregistered emails
- Validates user identity against institutional records
- Maintains audit trail through comprehensive logging

## Architecture

### Core Components

#### 1. **SocialAuthService**
```php
// Location: app/Services/SocialAuthService.php
class SocialAuthService
{
    public function findOrCreateUser(SocialiteUser $socialiteUser, string $provider): User
    public function validateEmailInRecords(string $email): bool
    public function getPersonDataByEmail(string $email): ?array
}
```

#### 2. **Enhanced Controllers**
- `SocialAuthController` - Mobile OAuth callbacks
- `HandleOauthCallbackAction` - Web OAuth handling
- `OauthController` - Standard OAuth flows

#### 3. **Validation Flow**
```
1. User attempts social login
2. System validates email exists in Student/Faculty tables
3. If found: Create/update User with proper role and person linking
4. If not found: Reject with clear error message
```

## Authentication Flows

### 1. **Regular Registration Flow**
```
User selects role → Email validation → ID verification → Account creation
```
- Validates email exists in chosen role's table
- Verifies ID matches email in records
- Creates User account with proper linking

### 2. **Social Login Flow (Enhanced)**
```
Social provider → Email validation → Role detection → Account creation/update
```
- Checks email in both Student and Faculty tables
- Automatically determines role based on which table contains the email
- Creates User account with proper `person_id`, `person_type`, and `role`

### 3. **Mobile OAuth Flow**
```
Mobile app → External browser → OAuth → Deep link callback → Account handling
```
- Uses same validation as web OAuth
- Handles both Capacitor Social Login plugin and fallback methods
- Returns JSON responses for mobile app consumption

## Implementation Details

### Email Validation Logic
```php
// Check if email exists in Student or Faculty records
$personData = CreateNewUser::determinePersonTypeByEmail($email);

if (!$personData) {
    throw new \InvalidArgumentException(
        'Your email address is not found in our Student or Faculty records. ' .
        'Please contact the administration to ensure your email is registered.'
    );
}
```

### User Creation with Role Detection
```php
// Create user with proper role and person linking
$user = User::create([
    'name' => $socialiteUser->getName(),
    'email' => $socialiteUser->getEmail(),
    'role' => $personData['role'], // 'student' or 'faculty'
    'person_id' => $personData['person']->id,
    'person_type' => $personData['type'], // Students::class or Faculty::class
    'google_id' => $socialiteUser->getId(),
    'email_verified_at' => now(),
    // ... other fields
]);
```

### Error Handling
- **Validation Errors (422)**: Email not found in records
- **Authentication Errors (500)**: OAuth provider failures
- **User-Friendly Messages**: Clear instructions for users

## API Endpoints

### Mobile OAuth Endpoints
```
POST /auth/google/callback/mobile
POST /auth/google/exchange
GET  /auth/mobile/test
```

### Web OAuth Endpoints
```
GET  /auth/redirect/{provider}
GET  /auth/callback/{provider}
DELETE /auth/destroy/{provider}
```

## Configuration

### Required Environment Variables
```env
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=/auth/callback/google
```

### OAuth Provider Configuration
```php
// config/oauth.php
'providers' => [
    [
        'slug' => 'google',
        'active' => true,
        'icon' => 'mdi:google',
    ],
    // ... other providers
],
```

## Testing

### Test Coverage
- Social authentication service functionality
- Mobile OAuth callback handling
- Email validation and role detection
- Error handling for invalid emails
- User creation and updates

### Key Test Scenarios
1. Student email creates student user with correct role
2. Faculty email creates faculty user with correct role
3. Unregistered email is rejected with proper error
4. Existing users are updated correctly
5. Mobile OAuth handles all scenarios properly

## Security Considerations

### 1. **Email Validation**
- Only emails in Student/Faculty tables can create accounts
- Prevents unauthorized access from external users

### 2. **Role Assignment**
- Automatic role detection prevents privilege escalation
- Proper person linking ensures data integrity

### 3. **Audit Trail**
- Comprehensive logging of all authentication attempts
- Clear error messages for troubleshooting

### 4. **Data Protection**
- Secure handling of OAuth tokens
- Proper session management for mobile apps

## Troubleshooting

### Common Issues

#### 1. **"Email not found in records"**
- **Cause**: User's email not in Student/Faculty tables
- **Solution**: Contact administration to add email to appropriate table

#### 2. **OAuth callback failures**
- **Cause**: Incorrect redirect URIs or client configuration
- **Solution**: Verify OAuth provider settings and environment variables

#### 3. **Mobile app authentication issues**
- **Cause**: Deep link configuration or plugin failures
- **Solution**: Check Capacitor configuration and fallback mechanisms

### Debugging Steps
1. Check application logs for detailed error messages
2. Verify email exists in Student/Faculty tables
3. Confirm OAuth provider configuration
4. Test with different user types (student/faculty)

## Future Enhancements

### Planned Features
1. Support for additional OAuth providers
2. Enhanced mobile app integration
3. Advanced role-based permissions
4. Automated email synchronization with institutional systems

### Migration Path
- Current implementation is backward compatible
- Existing users will continue to work normally
- New social logins will use enhanced validation
