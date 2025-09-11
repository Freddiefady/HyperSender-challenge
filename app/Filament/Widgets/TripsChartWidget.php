<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\TripStatusEnum;
use App\Models\Trip;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

final class TripsChartWidget extends ChartWidget
{
    public ?string $filter = '30days';

    protected static ?string $title = 'Trips Overview';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getFilters(): ?array
    {
        return [
            '7days' => 'Last 7 days',
            '30days' => 'Last 30 days',
            '90days' => 'Last 90 days',
            'year' => 'This year',
        ];
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;

        $startDate = match ($activeFilter) {
            '7days' => Carbon::now()->subDays(7),
            '30days' => Carbon::now()->subDays(30),
            '90days' => Carbon::now()->subDays(90),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->subDays(30),
        };

        // Get trip counts by status for each day
        $trips = Trip::select(
            DB::raw('DATE(created_at) as date'),
            'status',
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date', 'status')
            ->orderBy('date')
            ->get();

        // Generate all dates in the range
        $dates = [];
        $currentDate = $startDate->copy();
        while ($currentDate->lte(Carbon::now())) {
            $dates[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }

        // Initialize data arrays
        $scheduled = [];
        $inProgress = [];
        $completed = [];
        $cancelled = [];

        // Fill data for each date
        foreach ($dates as $date) {
            $dayTrips = $trips->where('date', $date);

            $scheduled[] = $dayTrips->where('status', TripStatusEnum::SCHEDULED->value)->sum('count');
            $inProgress[] = $dayTrips->where('status', TripStatusEnum::IN_PROGRESS->value)->sum('count');
            $completed[] = $dayTrips->where('status', TripStatusEnum::COMPLETED->value)->sum('count');
            $cancelled[] = $dayTrips->where('status', TripStatusEnum::CANCELLED->value)->sum('count');
        }

        // Format labels based on the filter
        $labels = collect($dates)->map(function ($date) use ($activeFilter) {
            $carbon = Carbon::parse($date);

            return match ($activeFilter) {
                '7days' => $carbon->format('M j'),
                '30days' => $carbon->format('M j'),
                '90days' => $carbon->format('M j'),
                'year' => $carbon->format('M'),
                default => $carbon->format('M j'),
            };
        })->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Scheduled',
                    'data' => $scheduled,
                    'backgroundColor' => 'rgb(59, 130, 246)', // Blue
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
                [
                    'label' => 'In Progress',
                    'data' => $inProgress,
                    'backgroundColor' => 'rgb(245, 158, 11)', // Amber
                    'borderColor' => 'rgb(245, 158, 11)',
                ],
                [
                    'label' => 'Completed',
                    'data' => $completed,
                    'backgroundColor' => 'rgb(34, 197, 94)', // Green
                    'borderColor' => 'rgb(34, 197, 94)',
                ],
                [
                    'label' => 'Cancelled',
                    'data' => $cancelled,
                    'backgroundColor' => 'rgb(239, 68, 68)', // Red
                    'borderColor' => 'rgb(239, 68, 68)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'elements' => [
                'point' => [
                    'radius' => 3,
                ],
                'line' => [
                    'tension' => 0.1,
                ],
            ],
        ];
    }
}
