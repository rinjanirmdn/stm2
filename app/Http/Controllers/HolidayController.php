<?php

namespace App\Http\Controllers;

use App\Services\SlotService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    protected $slotService;
    
    public function __construct(SlotService $slotService)
    {
        $this->slotService = $slotService;
    }
    
    /**
     * Check if a date is holiday
     */
    public function checkHoliday(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $isHoliday = $this->slotService->isHoliday($date);
        $holidayName = $this->slotService->getHolidayName($date);
        $isWorkingDay = $this->slotService->isWorkingDay($date);
        
        return response()->json([
            'date' => $date,
            'is_holiday' => $isHoliday,
            'holiday_name' => $holidayName,
            'is_working_day' => $isWorkingDay,
            'is_weekend' => Carbon::parse($date)->isWeekend(),
        ]);
    }
    
    /**
     * Get all holidays for current year
     */
    public function getHolidays()
    {
        $year = now()->year;
        $holidays = $this->slotService->getHolidaysByYear($year);
        
        return response()->json([
            'year' => $year,
            'holidays' => $holidays,
            'total' => count($holidays)
        ]);
    }
    
    /**
     * Demo: Check holiday for specific dates
     */
    public function demo()
    {
        $dates = [
            '2024-01-01', // New Year
            '2024-12-25', // Christmas
            '2024-08-17', // Independence Day
            '2024-03-11', // Nyepi (if available)
            now()->format('Y-m-d'), // Today
        ];
        
        $results = [];
        foreach ($dates as $date) {
            $results[] = [
                'date' => $date,
                'is_holiday' => $this->slotService->isHoliday($date),
                'holiday_name' => $this->slotService->getHolidayName($date),
                'is_working_day' => $this->slotService->isWorkingDay($date),
                'day_name' => Carbon::parse($date)->format('l'),
            ];
        }
        
        return view('holiday.demo', compact('results'));
    }
}
