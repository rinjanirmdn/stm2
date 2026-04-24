<?php

namespace App\Services;

/**
 * Single source of truth for truck type normalization.
 *
 * Both DashboardStatsService and SlotFilterService MUST use
 * this service for truck type CASE expressions to guarantee
 * data parity between dashboard statistics and index filters.
 */
class TruckTypeService
{
    /**
     * Canonical truck type labels in display order.
     */
    public const LABELS = [
        'Container 40ft (Loose)',
        'Container 40ft (Paletize)',
        'Container 20ft (Loose)',
        'Container 20ft (Paletize)',
        'Wingbox (Loose)',
        'Wingbox (Paletize)',
        'Fuso',
        'CDD/CDE',
    ];

    /**
     * Return the SQL CASE expression that normalizes raw
     * s.truck_type values into canonical labels.
     *
     * @param  string  $col  The column reference (e.g. "s.truck_type")
     * @param  string  $fallback  ELSE value: 'NULL' to exclude unrecognised, or column name to keep original
     */
    public static function normalizeExpression(string $col = 's.truck_type', string $fallback = 'NULL'): string
    {
        return "CASE
            WHEN LOWER({$col}) LIKE '%container 40ft%' AND LOWER({$col}) LIKE '%loose%' THEN 'Container 40ft (Loose)'
            WHEN LOWER({$col}) LIKE '%kontainer 40ft%' AND LOWER({$col}) LIKE '%loose%' THEN 'Container 40ft (Loose)'
            WHEN LOWER({$col}) LIKE '%container 40ft%' AND LOWER({$col}) LIKE '%paletize%' THEN 'Container 40ft (Paletize)'
            WHEN LOWER({$col}) LIKE '%kontainer 40ft%' AND LOWER({$col}) LIKE '%paletize%' THEN 'Container 40ft (Paletize)'
            WHEN LOWER({$col}) LIKE '%container 20ft%' AND LOWER({$col}) LIKE '%loose%' THEN 'Container 20ft (Loose)'
            WHEN LOWER({$col}) LIKE '%kontainer 20ft%' AND LOWER({$col}) LIKE '%loose%' THEN 'Container 20ft (Loose)'
            WHEN LOWER({$col}) LIKE '%container 20ft%' AND LOWER({$col}) LIKE '%paletize%' THEN 'Container 20ft (Paletize)'
            WHEN LOWER({$col}) LIKE '%kontainer 20ft%' AND LOWER({$col}) LIKE '%paletize%' THEN 'Container 20ft (Paletize)'
            WHEN LOWER({$col}) LIKE '%wingbox%' AND LOWER({$col}) LIKE '%loose%' THEN 'Wingbox (Loose)'
            WHEN LOWER({$col}) LIKE '%wingbox%' AND LOWER({$col}) LIKE '%paletize%' THEN 'Wingbox (Paletize)'
            WHEN LOWER({$col}) LIKE '%fuso%' THEN 'Fuso'
            WHEN LOWER({$col}) LIKE '%cdd%' OR LOWER({$col}) LIKE '%cde%' THEN 'CDD/CDE'
            ELSE {$fallback}
        END";
    }

    /**
     * Return the SQL ORDER BY expression for canonical truck type labels.
     */
    public static function orderExpression(string $alias = 'truck_type'): string
    {
        $cases = [];
        foreach (self::LABELS as $i => $label) {
            $cases[] = "WHEN '{$label}' THEN ".($i + 1);
        }

        return "CASE {$alias} ".implode(' ', $cases).' ELSE 99 END';
    }
}
