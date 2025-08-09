<?php
/**
 * Test script for premium_key generation
 * This demonstrates the premium_key format: dd + 10-digit ID + mmyy
 */

// Test the premium_key generation logic
function generatePremiumKey($rowId) {
    $now = new DateTime();
    $dayStr = $now->format('d'); // dd format (day)
    $monthYearStr = $now->format('my'); // mmyy format (month + year)
    $idStr = str_pad($rowId, 10, '0', STR_PAD_LEFT); // 10-digit zero-padded
    return $dayStr . $idStr . $monthYearStr;
}

// Test examples
echo "Premium Key Generation Examples:\n";
echo "================================\n";

// Example 1: First premium subscription today
$key1 = generatePremiumKey(1);
echo "Row ID 1: $key1\n";

// Example 2: 131st premium subscription today  
$key2 = generatePremiumKey(131);
echo "Row ID 131: $key2\n";

// Example 3: 1000th premium subscription today
$key3 = generatePremiumKey(1000);
echo "Row ID 1000: $key3\n";

// Show date format
echo "\nDate format explanation:\n";
echo "Today's date: " . date('d/m/y') . "\n";
echo "Day format used: " . date('d') . " (dd)\n";
echo "Month/Year format used: " . date('my') . " (mmyy)\n";

// Show the complete format
echo "\nComplete format: dd + 0000000000 (10 digits) + mmyy\n";
echo "Example for today, row 1: " . date('d') . "0000000001" . date('my') . "\n";
echo "Breakdown:\n";
echo "- Day: " . date('d') . "\n";
echo "- ID (10 digits): 0000000001\n";
echo "- Month/Year: " . date('my') . "\n";
?>
