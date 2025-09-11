<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\TripStatusEnum;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\Vehicle;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class KPIsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Calculate date ranges
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        // Basic counts
        $totalCompanies = Company::active()->count();
        $totalDrivers = Driver::active()->count();
        $totalVehicles = Vehicle::active()->count();

        // Trip statistics
        $activeTrips = Trip::active()->count();
        $tripsToday = Trip::whereDate('scheduled_start', $today)->count();
        $overdueTrips = Trip::where('status', TripStatusEnum::SCHEDULED->value)
            ->where('scheduled_start', '<', Carbon::now())
            ->count();

        // Monthly comparisons
        $tripsThisMonth = Trip::whereBetween('created_at', [$thisMonth, Carbon::now()])->count();
        $tripsLastMonth = Trip::whereBetween('created_at', [$lastMonth, $thisMonth])->count();
        $monthlyGrowth = $tripsLastMonth > 0
            ? round((($tripsThisMonth - $tripsLastMonth) / $tripsLastMonth) * 100, 1)
            : 0;

        // Completion rate
        $completedTripsThisMonth = Trip::completed()
            ->whereBetween('created_at', [$thisMonth, Carbon::now()])
            ->count();
        $completionRate = $tripsThisMonth > 0
            ? round(($completedTripsThisMonth / $tripsThisMonth) * 100, 1)
            : 0;

        // Driver utilization (drivers with active trips)
        $driversWithActiveTrips = Trip::active()
            ->distinct('driver_id')
            ->count();
        $driverUtilization = $totalDrivers > 0
            ? round(($driversWithActiveTrips / $totalDrivers) * 100, 1)
            : 0;

        // Vehicle utilization
        $vehiclesWithActiveTrips = Trip::active()
            ->distinct('vehicle_id')
            ->count();
        $vehicleUtilization = $totalVehicles > 0
            ? round(($vehiclesWithActiveTrips / $totalVehicles) * 100, 1)
            : 0;

        // Average fuel efficiency for completed trips this month
        $avgFuelEfficiency = (float) Trip::completed()
            ->whereBetween('created_at', [$thisMonth, Carbon::now()])
            ->whereNotNull('distance_km')
            ->whereNotNull('fuel_consumed')
            ->where('fuel_consumed', '>', 0)
            ->selectRaw('AVG(distance_km / fuel_consumed) as avg_efficiency')
            ->value('avg_efficiency');

        // Expired licenses count
        $expiredLicenses = Driver::where('is_active', true)
            ->where('license_expiry', '<', Carbon::now())
            ->count();

        return [
            Stat::make('Active Companies', $totalCompanies)
                ->description('Total active companies')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('success'),

            Stat::make('Active Trips', $activeTrips)
                ->description($overdueTrips > 0 ? "{$overdueTrips} overdue" : 'On track')
                ->descriptionIcon($overdueTrips > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($overdueTrips > 0 ? 'warning' : 'success'),

            Stat::make('Trips Today', $tripsToday)
                ->description('Scheduled for today')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            Stat::make('Monthly Trips', $tripsThisMonth)
                ->description($monthlyGrowth >= 0 ? "+{$monthlyGrowth}% from last month" : "{$monthlyGrowth}% from last month")
                ->descriptionIcon($monthlyGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($monthlyGrowth >= 0 ? 'success' : 'danger'),

            Stat::make('Completion Rate', "{$completionRate}%")
                ->description('Trips completed this month')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color($completionRate >= 80 ? 'success' : ($completionRate >= 60 ? 'warning' : 'danger')),

            Stat::make('Driver Utilization', "{$driverUtilization}%")
                ->description("{$driversWithActiveTrips} of {$totalDrivers} drivers active")
                ->descriptionIcon('heroicon-m-user-group')
                ->color($driverUtilization >= 70 ? 'success' : ($driverUtilization >= 50 ? 'warning' : 'danger')),

            Stat::make('Vehicle Utilization', "{$vehicleUtilization}%")
                ->description("{$vehiclesWithActiveTrips} of {$totalVehicles} vehicles active")
                ->descriptionIcon('heroicon-m-truck')
                ->color($vehicleUtilization >= 70 ? 'success' : ($vehicleUtilization >= 50 ? 'warning' : 'danger')),

            Stat::make('Avg Fuel Efficiency', $avgFuelEfficiency ? round($avgFuelEfficiency, 2).' km/L' : 'N/A')
                ->description('This month average')
                ->descriptionIcon('heroicon-m-bolt')
                ->color($avgFuelEfficiency && $avgFuelEfficiency >= 8 ? 'success' : 'warning'),

            Stat::make('License Alerts', $expiredLicenses)
                ->description($expiredLicenses > 0 ? 'Expired licenses' : 'All licenses valid')
                ->descriptionIcon($expiredLicenses > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-shield-check')
                ->color($expiredLicenses > 0 ? 'danger' : 'success'),
            // System Health
            Stat::make('System Status', 'Optimal')
                ->description("{$totalCompanies} companies, {$totalDrivers} drivers, {$totalVehicles} vehicles")
                ->descriptionIcon('heroicon-m-server')
                ->color('success')
                ->extraAttributes([
                    'class' => 'modern-stat-card system-card',
                ]),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }
}
