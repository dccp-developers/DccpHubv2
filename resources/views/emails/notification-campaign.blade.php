@component('mail::message')
# {{ $title }}

Hello {{ $user->name }},

{{ $message }}

@if($actionUrl && $actionText)
@component('mail::button', ['url' => $actionUrl, 'color' => match($priority) {
    'urgent' => 'error',
    'high' => 'warning',
    'normal' => 'primary',
    'low' => 'secondary',
    default => 'primary'
}])
{{ $actionText }}
@endcomponent
@endif

@if($priority === 'urgent')
**This is an urgent notification. Please take immediate action if required.**
@elseif($priority === 'high')
**This is a high priority notification.**
@endif

Best regards,<br>
{{ config('app.name') }}

---
<small>This is an automated notification. If you have any questions, please contact the administration.</small>
@endcomponent
