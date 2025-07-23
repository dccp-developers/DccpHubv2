# Development Modal Feature

## Overview

The Development Modal is a reusable component that displays a user-friendly notification when users try to access features that are still under development. This provides a better user experience than broken links or empty pages.

## Components

### 1. DevelopmentModal.vue
**Location**: `resources/js/Components/ui/DevelopmentModal.vue`

A reusable Shadcn UI-based modal component that displays:
- Clear "Section Under Development" message
- Professional orange-themed design
- Information about what to expect
- User-friendly "Got it, thanks!" button

**Props:**
- `open` (Boolean): Controls modal visibility

**Events:**
- `update:open`: Emitted when modal should be closed

### 2. Updated Navigation Components

#### SidebarNavigation.vue
- Handles `isDevelopment` flag on navigation items
- Shows "Dev" badge for development items
- Emits `showDevelopmentModal` event when development items are clicked

#### MobileBottomNav.vue
- Similar functionality for mobile navigation
- Shows "Dev" badge for development items
- Emits `showDevelopmentModal` event

#### FacultyLayout.vue
- Integrates the development modal
- Handles events from both navigation components
- Manages modal state

## Usage

### Marking Navigation Items as Under Development

In `FacultyLayout.vue`, add `isDevelopment: true` to navigation items:

```javascript
const navigation = ref([
  { name: 'Dashboard', href: route('faculty.dashboard'), icon: HomeIcon, current: route().current('faculty.dashboard') },
  { name: 'Schedule', href: '#', icon: CalendarIcon, current: false, isDevelopment: true },
  { name: 'Grades', href: '#', icon: ChartBarIcon, current: false, isDevelopment: true },
])
```

### Using in Other Layouts

To use the development modal in other layouts:

1. Import the component:
```javascript
import DevelopmentModal from '@/Components/ui/DevelopmentModal.vue'
```

2. Add reactive state:
```javascript
const showDevelopmentModal = ref(false)
```

3. Add the modal to your template:
```vue
<DevelopmentModal 
  :open="showDevelopmentModal" 
  @update:open="showDevelopmentModal = $event"
/>
```

4. Trigger the modal:
```javascript
// For buttons or other interactive elements
@click="showDevelopmentModal = true"
```

## Features

- **Responsive Design**: Works on both desktop and mobile
- **Accessible**: Uses proper ARIA attributes via Shadcn UI
- **Professional Appearance**: Orange theme indicates "in progress" status
- **Reusable**: Can be used across different layouts and components
- **Efficient**: Single modal instance per layout, no performance impact

## Styling

The modal uses:
- Shadcn UI AlertDialog components for consistency
- Orange color scheme (orange-500, orange-100, etc.)
- Heroicons for visual elements
- Tailwind CSS for responsive design

## Benefits

1. **Better UX**: Users get clear feedback instead of broken links
2. **Professional**: Maintains app quality during development
3. **Consistent**: Same experience across all development features
4. **Maintainable**: Easy to remove when features are ready
5. **Scalable**: Works for any number of development features
