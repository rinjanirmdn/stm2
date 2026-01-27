@extends('layouts.app')

@section('title', 'Holiday Library Demo')
@section('page_title', 'Holiday Library Demo')

@section('content')
<div class="st-card">
    <div class="st-card__header">
        <h2 class="st-card__title">
            <i class="fas fa-calendar-alt"></i>
            Holiday Library Demo (Yasumi)
        </h2>
    </div>
    <div class="st-card__body">
        <div class="alert alert-info">
            <h5><i class="fas fa-info-circle"></i> Library Information</h5>
            <p>Using <strong>azuyalabs/yasumi</strong> library for Indonesian holidays without database table!</p>
            <ul>
                <li>No need to create holiday master data table</li>
                <li>Automatic holiday calculation based on Indonesian calendar</li>
                <li>Supports Islamic holidays, national holidays, etc.</li>
                <li>Working day validation (weekend + holiday check)</li>
            </ul>
        </div>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Is Holiday</th>
                        <th>Holiday Name</th>
                        <th>Is Working Day</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($results as $result)
                    <tr>
                        <td>{{ $result['date'] }}</td>
                        <td>{{ $result['day_name'] }}</td>
                        <td>
                            @if($result['is_holiday'])
                                <span class="badge bg-danger">Yes</span>
                            @else
                                <span class="badge bg-secondary">No</span>
                            @endif
                        </td>
                        <td>{{ $result['holiday_name'] ?? '-' }}</td>
                        <td>
                            @if($result['is_working_day'])
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="badge bg-warning">No</span>
                            @endif
                        </td>
                        <td>
                            @if($result['is_holiday'])
                                <span class="text-danger">
                                    <i class="fas fa-times-circle"></i> Holiday
                                </span>
                            @elseif(!$result['is_working_day'])
                                <span class="text-warning">
                                    <i class="fas fa-exclamation-triangle"></i> Weekend
                                </span>
                            @else
                                <span class="text-success">
                                    <i class="fas fa-check-circle"></i> Working Day
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            <h5>Usage Examples:</h5>
            <div class="row">
                <div class="col-md-6">
                    <h6>In Controller/Service:</h6>
                    <pre><code>// Check if date is holiday
$isHoliday = $slotService->isHoliday('2024-12-25');

// Get holiday name
$holidayName = $slotService->getHolidayName('2024-12-25');

// Check if working day
$isWorkingDay = $slotService->isWorkingDay('2024-12-25');

// Get all holidays for year
$holidays = $slotService->getHolidaysByYear(2024);</code></pre>
                </div>
                <div class="col-md-6">
                    <h6>Direct Helper Usage:</h6>
                    <pre><code>use App\Helpers\HolidayHelper;

// Check holiday
$isHoliday = HolidayHelper::isHoliday($date);

// Check working day
$isWorkingDay = HolidayHelper::isWorkingDay($date);

// Get holiday name
$name = HolidayHelper::getHolidayName($date);</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
