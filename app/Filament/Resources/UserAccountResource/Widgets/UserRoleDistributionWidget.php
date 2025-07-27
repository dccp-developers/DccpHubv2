<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserAccountResource\Widgets;

use App\Models\User;
use App\Enums\UserRole;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class UserRoleDistributionWidget extends ChartWidget
{
    protected static ?string $heading = 'User Role Distribution';

    protected static ?string $description = 'Distribution of users by role';

    protected static ?int $sort = 2;

    protected static ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        $data = Cache::remember('user_role_distribution', 600, function () {
            $roleData = User::selectRaw('role, COUNT(*) as count')
                ->groupBy('role')
                ->pluck('count', 'role')
                ->toArray();

            $labels = [];
            $values = [];
            $colors = [];

            foreach (UserRole::cases() as $role) {
                $count = $roleData[$role->value] ?? 0;
                if ($count > 0) {
                    $labels[] = $role->getLabel();
                    $values[] = $count;
                    $colors[] = $this->getRoleColor($role);
                }
            }

            return [
                'labels' => $labels,
                'values' => $values,
                'colors' => $colors,
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Users',
                    'data' => $data['values'],
                    'backgroundColor' => $data['colors'],
                    'borderColor' => $data['colors'],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }

    private function getRoleColor(UserRole $role): string
    {
        return match ($role) {
            UserRole::ADMIN => '#ef4444',      // red
            UserRole::FACULTY => '#10b981',    // green
            UserRole::STUDENT => '#3b82f6',    // blue
            UserRole::GUEST => '#f59e0b',      // yellow
            UserRole::STAFF => '#8b5cf6',      // purple
        };
    }
}
