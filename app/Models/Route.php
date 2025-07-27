<?php

namespace App\Models;

use App\Enums\RouteStatus;
use App\Enums\RouteType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Route extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'display_name',
        'path',
        'method',
        'controller',
        'action',
        'type',
        'status',
        'description',
        'icon',
        'sort_order',
        'parent_id',
        'is_navigation',
        'is_mobile_visible',
        'is_desktop_visible',
        'required_permissions',
        'middleware',
        'parameters',
        'metadata',
        'disabled_message',
        'maintenance_message',
        'development_message',
        'redirect_url',
        'is_external',
        'target',
        'created_by',
        'updated_by',
        'disabled_at',
        'enabled_at',
    ];

    protected $casts = [
        'type' => RouteType::class,
        'status' => RouteStatus::class,
        'is_navigation' => 'boolean',
        'is_mobile_visible' => 'boolean',
        'is_desktop_visible' => 'boolean',
        'is_external' => 'boolean',
        'required_permissions' => 'array',
        'middleware' => 'array',
        'parameters' => 'array',
        'metadata' => 'array',
        'disabled_at' => 'datetime',
        'enabled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the parent route.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Route::class, 'parent_id');
    }

    /**
     * Get the child routes.
     */
    public function children()
    {
        return $this->hasMany(Route::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Get the user who created the route.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the route.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if the route is accessible.
     */
    public function isAccessible(): bool
    {
        return $this->status->isAccessible();
    }

    /**
     * Check if the route is visible in navigation.
     */
    public function isVisibleInNavigation(): bool
    {
        return $this->is_navigation && $this->isAccessible();
    }

    /**
     * Check if the route is visible on mobile.
     */
    public function isVisibleOnMobile(): bool
    {
        return $this->is_mobile_visible && $this->isAccessible();
    }

    /**
     * Check if the route is visible on desktop.
     */
    public function isVisibleOnDesktop(): bool
    {
        return $this->is_desktop_visible && $this->isAccessible();
    }

    /**
     * Get the full URL for the route.
     */
    public function getUrlAttribute(): string
    {
        if ($this->is_external) {
            return $this->path;
        }

        if ($this->redirect_url) {
            return $this->redirect_url;
        }

        return route($this->name, $this->parameters ?? []);
    }

    /**
     * Get the appropriate message based on status.
     */
    public function getStatusMessage(): string
    {
        return match ($this->status) {
            RouteStatus::DISABLED => $this->disabled_message ?: $this->status->getModalMessage(),
            RouteStatus::MAINTENANCE => $this->maintenance_message ?: $this->status->getModalMessage(),
            RouteStatus::DEVELOPMENT => $this->development_message ?: $this->status->getModalMessage(),
            default => '',
        };
    }

    /**
     * Scope for active routes.
     */
    public function scopeActive($query)
    {
        return $query->where('status', RouteStatus::ACTIVE);
    }

    /**
     * Scope for navigation routes.
     */
    public function scopeNavigation($query)
    {
        return $query->where('is_navigation', true);
    }

    /**
     * Scope for routes by type.
     */
    public function scopeByType($query, RouteType $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for mobile visible routes.
     */
    public function scopeMobileVisible($query)
    {
        return $query->where('is_mobile_visible', true);
    }

    /**
     * Scope for desktop visible routes.
     */
    public function scopeDesktopVisible($query)
    {
        return $query->where('is_desktop_visible', true);
    }

    /**
     * Scope for parent routes (no parent_id).
     */
    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Get navigation routes for a specific type.
     */
    public static function getNavigationRoutes(RouteType $type, bool $mobileOnly = false): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "navigation_routes_{$type->value}" . ($mobileOnly ? '_mobile' : '');
        
        return Cache::remember($cacheKey, 3600, function () use ($type, $mobileOnly) {
            $query = static::active()
                ->navigation()
                ->byType($type)
                ->parents()
                ->with('children')
                ->orderBy('sort_order');

            if ($mobileOnly) {
                $query->mobileVisible();
            } else {
                $query->desktopVisible();
            }

            return $query->get();
        });
    }

    /**
     * Clear navigation cache.
     */
    public static function clearNavigationCache(): void
    {
        $types = RouteType::cases();
        foreach ($types as $type) {
            Cache::forget("navigation_routes_{$type->value}");
            Cache::forget("navigation_routes_{$type->value}_mobile");
        }
    }

    /**
     * Boot method to handle model events.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            static::clearNavigationCache();
        });

        static::deleted(function () {
            static::clearNavigationCache();
        });
    }
}
