<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * PricingService - Service untuk menghitung harga rental PC
 * 
 * Ini adalah CORE BUSINESS LOGIC untuk perhitungan harga yang kompleks
 * 
 * STRUKTUR HARGA:
 * 1. TIER PRICING (Harga Bertingkat):
 *    - REGULER: Jam 1 = Rp 7,000, Jam 2+ = Rp 6,000/jam
 *    - VIP:     Jam 1 = Rp 10,000, Jam 2+ = Rp 8,000/jam
 * 
 * 2. NIGHT DISCOUNT (22:00 - 05:00):
 *    - Diskon 20% dari harga base
 *    - TIDAK ada tier pricing saat night hours (flat rate)
 * 
 * 3. MIXED PERIOD CALCULATION:
 *    - Session bisa melintasi batas waktu (misal: 21:00 - 23:00)
 *    - System otomatis split jadi multiple periods
 *    - Setiap period dihitung terpisah dengan rule masing-masing
 * 
 * CONTOH PERHITUNGAN:
 * Session REGULER, 21:00 - 23:30 (150 menit = 2.5 jam → dibulatkan 3 jam)
 * 
 * Period 1 (21:00 - 22:00): 1 jam DAY
 *   - Tier 1: Rp 7,000
 * 
 * Period 2 (22:00 - 23:30): 2 jam NIGHT
 *   - Base: 2 × Rp 7,000 = Rp 14,000
 *   - Discount 20%: Rp 2,800
 *   - Subtotal: Rp 11,200
 * 
 * TOTAL: Rp 7,000 + Rp 11,200 = Rp 18,200
 */
class PricingService
{
    // Base prices per hour (in Rupiah)
    const REGULER_BASE = 7000;
    const VIP_BASE = 10000;
    
    // Tier 2+ prices (hour 2 onwards) - NOT applied during night hours
    const REGULER_TIER2 = 6000;
    const VIP_TIER2 = 8000;
    
    // Night hours definition
    const NIGHT_START = 22; // 22:00
    const NIGHT_END = 5;    // 05:00
    const NIGHT_DISCOUNT = 0.20; // 20% discount
    
    /**
     * Calculate total cost for PC rental session
     * 
     * @param string $tier - REGULER or VIP
     * @param \Carbon\Carbon $startTime
     * @param int $durationMinutes
     * @return array ['total' => int, 'breakdown' => array]
     */
    public static function calculatePrice(string $tier, Carbon $startTime, int $durationMinutes): array
    {
        $endTime = $startTime->copy()->addMinutes($durationMinutes);
        
        // Split into periods if session crosses night boundaries
        $periods = self::splitIntoPeriods($startTime, $endTime);
        
        $breakdown = [];
        $total = 0;
        
        foreach ($periods as $period) {
            $periodCost = self::calculatePeriodCost($tier, $period);
            $breakdown[] = $periodCost;
            $total += $periodCost['cost'];
        }
        
        return [
            'total' => $total,
            'breakdown' => $breakdown,
            'currency' => 'IDR'
        ];
    }
    
    /**
     * Split session into night and day periods
     * 
     * @param \Carbon\Carbon $start
     * @param \Carbon\Carbon $end
     * @return array
     */
    private static function splitIntoPeriods(Carbon $start, Carbon $end): array
    {
        $periods = [];
        $current = $start->copy();
        
        while ($current->lt($end)) {
            $isNight = self::isNightTime($current);
            $nextBoundary = self::getNextBoundary($current);
            
            // Determine period end: either next boundary or session end, whichever is earlier
            $periodEnd = $nextBoundary && $nextBoundary->lt($end) ? $nextBoundary : $end;
            
            $periods[] = [
                'start' => $current->copy(),
                'end' => $periodEnd->copy(),
                'is_night' => $isNight,
                'minutes' => $current->diffInMinutes($periodEnd)
            ];
            
            $current = $periodEnd;
        }
        
        return $periods;
    }
    
    /**
     * Calculate cost for a single period
     * 
     * @param string $tier
     * @param array $period
     * @return array
     */
    private static function calculatePeriodCost(string $tier, array $period): array
    {
        $minutes = $period['minutes'];
        $hours = ceil($minutes / 60); // Always round up to next hour
        
        $baseRate = $tier === 'VIP' ? self::VIP_BASE : self::REGULER_BASE;
        $tier2Rate = $tier === 'VIP' ? self::VIP_TIER2 : self::REGULER_TIER2;
        
        $subtotal = 0;
        
        if ($period['is_night']) {
            // Night hours: flat rate with discount, no tier pricing
            $subtotal = $hours * $baseRate;
            $discount = $subtotal * self::NIGHT_DISCOUNT;
            $subtotal -= $discount;
            
            return [
                'period' => $period['start']->format('H:i') . ' - ' . $period['end']->format('H:i'),
                'type' => 'NIGHT',
                'hours' => $hours,
                'base_cost' => $hours * $baseRate,
                'discount' => $discount,
                'cost' => (int) $subtotal
            ];
        } else {
            // Day hours: tier pricing (first hour full price, subsequent hours discounted)
            if ($hours > 0) {
                $subtotal += $baseRate; // First hour
                if ($hours > 1) {
                    $subtotal += ($hours - 1) * $tier2Rate; // Remaining hours
                }
            }
            
            return [
                'period' => $period['start']->format('H:i') . ' - ' . $period['end']->format('H:i'),
                'type' => 'DAY',
                'hours' => $hours,
                'base_cost' => $subtotal,
                'discount' => 0,
                'cost' => (int) $subtotal
            ];
        }
    }
    
    /**
     * Check if given time is within night hours (22:00 - 05:00)
     * 
     * @param \Carbon\Carbon $time
     * @return bool
     */
    private static function isNightTime(Carbon $time): bool
    {
        $hour = $time->hour;
        return $hour >= self::NIGHT_START || $hour < self::NIGHT_END;
    }
    
    /**
     * Get next boundary time (22:00 or 05:00)
     * 
     * @param \Carbon\Carbon $time
     * @return \Carbon\Carbon|null
     */
    private static function getNextBoundary(Carbon $time): ?Carbon
    {
        $hour = $time->hour;
        
        if ($hour < self::NIGHT_END) {
            // Before 05:00 → next boundary is 05:00 today
            return $time->copy()->setTime(self::NIGHT_END, 0, 0);
        } elseif ($hour < self::NIGHT_START) {
            // Between 05:00 and 22:00 → next boundary is 22:00 today
            return $time->copy()->setTime(self::NIGHT_START, 0, 0);
        } else {
            // After 22:00 → next boundary is 05:00 tomorrow
            return $time->copy()->addDay()->setTime(self::NIGHT_END, 0, 0);
        }
    }
    
    /**
     * Quick estimate for display purposes (without breakdown)
     * 
     * @param string $tier
     * @param int $durationMinutes
     * @return int
     */
    public static function estimatePrice(string $tier, int $durationMinutes): int
    {
        $hours = ceil($durationMinutes / 60);
        $baseRate = $tier === 'VIP' ? self::VIP_BASE : self::REGULER_BASE;
        $tier2Rate = $tier === 'VIP' ? self::VIP_TIER2 : self::REGULER_TIER2;
        
        // Simple estimate: first hour + remaining hours (no night discount)
        $estimate = $baseRate;
        if ($hours > 1) {
            $estimate += ($hours - 1) * $tier2Rate;
        }
        
        return (int) $estimate;
    }
}
