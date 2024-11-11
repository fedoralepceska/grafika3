<?php

if (!function_exists('getUnit')) {
    function getUnit($job) {
        $small = \App\Models\SmallMaterial::find($job['small_material_id']);
        $large = \App\Models\LargeFormatMaterial::find($job['large_material_id']);

        if ($small || $large) {
            if (($small && $small['article']['in_meters']) || ($large && $large['article']['in_meters'])) {
                return 'м';
            } elseif (($small && $small['article']['in_kilograms']) || ($large && $large['article']['in_kilograms'])) {
                return 'кг';
            } elseif (($small && $small['article']['in_pieces']) || ($large && $large['article']['in_pieces'])) {
                return 'ком';
            }
        }

        return '';
    }
}

if (!function_exists('getTaxTypeDecimal')) {
    function getVAT($article) {
        if ($article !== null) {
            if ($article['tax_type'] === 1) {
                return 18;
            }
            else if ($article['tax_type'] === 2) {
                return 5;
            }
            else if ($article['tax_type'] === 3) {
                return 10;
            }
        }
        return '';
    }
}

if (!function_exists('formatNumber')) {
    function formatNumber($number) {
        return number_format($number, 2, '.', ',');
    }
}

if (!function_exists('calculateVat')) {
    function calculateVAT($article) {
        $vatPercentage = getVAT($article);
        $vatAmount = ($article['price_1'] * $vatPercentage) / 100;
        return formatNumber($vatAmount);
    }
}

if (!function_exists('priceWithVAT')) {
    function priceWithVAT($article) {
        $vatAmount = calculateVAT($article);
        $totalPrice = floatval($article['price_1']) + floatval($vatAmount);
        return formatNumber($totalPrice);
    }
}
