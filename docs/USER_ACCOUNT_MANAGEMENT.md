# User Account Control Management System

## Overview

This comprehensive User Account Control management system provides a centralized, efficient, and organized approach to managing user accounts in your Laravel application using Filament. The system is built with reusable components, centralized logic, and follows best practices for maintainability and scalability.

## Features

### 🔧 Core Components

1. **Centralized Enums**
   - `UserRole`: Defines user roles (Admin, Faculty, Student, Guest, Staff)
   - `AccountStatus`: Manages account states (Active, Inactive, Suspended, etc.)
   - `PersonType`: Links users to person records (Students, Faculty, SHS Students)

2. **Service Layer**
   - `UserAccountService`: Centralized business logic for account operations
   - Handles activation/deactivation, role changes, password resets
   - Provides bulk operations and statistics

3. **Filament Resource**
   - `UserAccountResource`: Complete CRUD interface for user management
   - Advanced filtering, searching, and sorting capabilities
   - Comprehensive form validation and data handling

### 🎯 Key Features

#### User Management
- ✅ Create, view, edit, and delete user accounts
- ✅ Activate/deactivate accounts (individual and bulk)
- ✅ Change user roles with validation
- ✅ Reset passwords with email notifications
- ✅ Send email verification
- ✅ Link users to person records (Students/Faculty)
- ✅ Profile photo management
- ✅ Two-factor authentication status tracking

#### Advanced Filtering & Search
- ✅ Filter by role, status, verification state
- ✅ Date range filtering for registration dates
- ✅ Person type filtering
- ✅ Global search across name, email, username
- ✅ Tabbed interface for quick access to user segments

#### Bulk Operations
- ✅ Bulk activate/deactivate users
- ✅ Bulk role changes
- ✅ Bulk email verification sending
- ✅ Bulk deletion with confirmation

#### Relation Management
- ✅ OAuth connections management
- ✅ API tokens oversight
- ✅ Faculty notifications tracking
- ✅ Token refresh and revocation

#### Dashboard & Analytics
- ✅ User statistics overview
- ✅ Role distribution charts
- ✅ Recent activity tracking
- ✅ Growth metrics and trends

## File Structure

```
app/
├── Enums/
│   ├── UserRole.php              # User role definitions
│   ├── AccountStatus.php         # Account status states
│   └── PersonType.php            # Person type mappings
├── Services/
│   └── UserAccountService.php    # Centralized business logic
└── Filament/Resources/
    ├── UserAccountResource.php   # Main resource file
    ├── UserAccountResource/
    │   ├── Pages/
    │   │   ├── ListUserAccounts.php
    │   │   ├── CreateUserAccount.php
    │   │   ├── ViewUserAccount.php
    │   │   └── EditUserAccount.php
    │   ├── RelationManagers/
    │   │   ├── OauthConnectionsRelationManager.php
    │   │   ├── ApiTokensRelationManager.php
    │   │   └── FacultyNotificationsRelationManager.php
    │   └── Widgets/
    │       ├── UserAccountStatsWidget.php
    │       ├── UserRoleDistributionWidget.php
    │       └── RecentUserActivitiesWidget.php
```

## Usage Examples

### Service Layer Usage

```php
use App\Services\UserAccountService;
use App\Enums\UserRole;

$service = app(UserAccountService::class);

// Activate a user account
$service->activateAccount($user);

// Change user role
$service->changeUserRole($user, UserRole::FACULTY);

// Reset password
$newPassword = $service->resetPassword($user, sendEmail: true);

// Get user statistics
$stats = $service->getUserStatistics();

// Bulk operations
$results = $service->bulkActivateUsers([1, 2, 3]);
```

### Enum Usage

```php
use App\Enums\UserRole;
use App\Enums\AccountStatus;
use App\Enums\PersonType;

// Get role information
$role = UserRole::FACULTY;
echo $role->getLabel();        // "Faculty Member"
echo $role->getDescription();  // "Faculty member with teaching..."
echo $role->getColor();        // "success"
echo $role->getIcon();         // "heroicon-o-user-group"

// Check permissions
$permissions = $role->getPermissions();
$canAccessPanel = $role->canAccessPanel();

// Account status checks
$status = AccountStatus::ACTIVE;
$canLogin = $status->canLogin();
$requiresAction = $status->requiresAction();
```

## Security Features

### Role-Based Access Control
- Admin-only actions for sensitive operations
- Role change validation and restrictions
- Impersonation controls with proper authorization

### Audit Logging
- All account changes are logged with user attribution
- Password reset tracking
- Role change history
- Account status modifications

### Data Protection
- Password hashing with Laravel's built-in security
- API token management with proper scoping
- OAuth connection security
- Email verification workflows

## Customization

### Adding New Roles
1. Add the role to `UserRole` enum
2. Define permissions and access levels
3. Update UI colors and icons
4. Add role-specific validation logic

### Extending Account Status
1. Add new status to `AccountStatus` enum
2. Define behavior methods (canLogin, requiresAction, etc.)
3. Update UI representations
4. Add status-specific business logic

### Custom Actions
Add custom actions to the resource:

```php
Tables\Actions\Action::make('custom_action')
    ->label('Custom Action')
    ->icon('heroicon-o-star')
    ->action(function ($record) {
        // Custom logic here
    });
```

## Performance Considerations

### Caching
- User statistics are cached for 5 minutes
- Role distribution data cached for 10 minutes
- User growth charts cached for 1 hour

### Database Optimization
- Proper indexing on frequently queried fields
- Efficient relationship loading
- Pagination for large datasets

### Memory Management
- Bulk operations use chunking for large datasets
- Lazy loading for relation managers
- Optimized queries with select statements

## Best Practices

1. **Always use the service layer** for business logic operations
2. **Leverage enums** for consistent data representation
3. **Implement proper validation** before state changes
4. **Log important operations** for audit trails
5. **Use bulk operations** for efficiency with large datasets
6. **Cache expensive operations** to improve performance
7. **Follow Laravel conventions** for maintainability

## Testing

The system includes comprehensive test coverage for:
- Service layer operations
- Enum functionality
- Resource CRUD operations
- Bulk actions
- Security validations

Run tests with:
```bash
php artisan test --filter UserAccount
```

## Maintenance

### Regular Tasks
- Monitor user growth trends
- Review inactive accounts
- Clean up expired tokens
- Audit role distributions
- Check email verification rates

### Updates
- Keep enum definitions current with business requirements
- Update permissions as features are added
- Maintain widget performance with growing datasets
- Review and optimize database queries

This system provides a solid foundation for user account management that can scale with your application's needs while maintaining security, performance, and usability.
