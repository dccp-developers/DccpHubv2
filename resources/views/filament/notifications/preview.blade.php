<div class="p-4 space-y-4">
    <div class="flex items-start space-x-3">
        <!-- Notification Icon -->
        <div class="flex-shrink-0">
            <div class="w-10 h-10 rounded-full flex items-center justify-center
                @if($type === 'success') bg-green-100 text-green-600 dark:bg-green-900/20 dark:text-green-400
                @elseif($type === 'warning') bg-yellow-100 text-yellow-600 dark:bg-yellow-900/20 dark:text-yellow-400
                @elseif($type === 'error') bg-red-100 text-red-600 dark:bg-red-900/20 dark:text-red-400
                @else bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400
                @endif">
                @if($type === 'success')
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                @elseif($type === 'warning')
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                @elseif($type === 'error')
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                @else
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                @endif
            </div>
        </div>

        <!-- Notification Content -->
        <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ $title }}
                    </h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ $message }}
                    </p>
                </div>

                <!-- Priority Badge -->
                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    @if($priority === 'urgent') bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400
                    @elseif($priority === 'high') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400
                    @elseif($priority === 'low') bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400
                    @else bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400
                    @endif">
                    {{ ucfirst($priority) }}
                </span>
            </div>

            <!-- Action Button -->
            @if($action_text && $action_url)
                <div class="mt-3">
                    <a href="{{ $action_url }}" 
                       class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        {{ $action_text }}
                        <svg class="ml-1 w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </a>
                </div>
            @endif

            <!-- Timestamp -->
            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                Preview • Just now
            </div>
        </div>
    </div>
</div>
