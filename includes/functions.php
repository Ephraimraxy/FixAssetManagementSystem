<?php
/**
 * Calculate depreciation using the straight-line method.
 * @param float $cost Asset purchase cost
 * @param float $salvage Salvage value at end of useful life
 * @param int $life Useful life in years
 * @return float Annual depreciation expense
 */
function calculateStraightLineDepreciation($cost, $salvage, $life) {
    if ($life <= 0) return 0;
    return ($cost - $salvage) / $life;
}

/**
 * Format currency value to Naira (₦)
 * @param float $amount The amount to format
 * @param int $decimals Number of decimal places
 * @return string Formatted currency string
 */
function formatCurrency($amount, $decimals = 2) {
    return '₦' . number_format($amount, $decimals, '.', ',');
}
