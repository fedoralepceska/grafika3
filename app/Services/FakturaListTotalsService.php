<?php

namespace App\Services;

use App\Models\Faktura;

/**
 * Net (amount), VAT (tax), and gross (total) for All Invoices list — aligned with
 * preview/print logic: article-based lines for regular fakturas; job salePrice×qty + 18% for split.
 */
class FakturaListTotalsService
{
    /** @var array<int, float> */
    private const TAX_MAP = [1 => 18.0, 2 => 5.0, 3 => 10.0];

    public function compute(Faktura $faktura): array
    {
        $faktura->loadMissing([
            'invoices.jobs.articles',
            'jobs.articles',
            'tradeItems',
            'additionalServices',
        ]);

        if ($faktura->is_split_invoice) {
            return $this->computeSplit($faktura);
        }

        return $this->computeRegular($faktura);
    }

    private function computeRegular(Faktura $faktura): array
    {
        $materialsSubtotal = 0.0;
        $materialsVat = 0.0;

        foreach ($faktura->invoices as $invoice) {
            foreach ($invoice->jobs as $job) {
                if ($job->articles->isNotEmpty()) {
                    foreach ($job->articles as $article) {
                        $qty = (float) ($article->pivot->quantity ?? 0);
                        $unitPrice = (float) ($article->price_1 ?? 0);
                        $line = $qty * $unitPrice;
                        $materialsSubtotal += $line;
                        $rate = (float) (self::TAX_MAP[$article->tax_type] ?? 0);
                        $materialsVat += $line * ($rate / 100);
                    }
                } else {
                    $qty = (float) ($job->quantity ?? 0);
                    $line = (float) ($job->salePrice ?? 0) * $qty;
                    $materialsSubtotal += $line;
                    $materialsVat += $line * 0.18;
                }
            }
        }

        $tradeSubtotal = (float) $faktura->tradeItems->sum('total_price');
        $tradeVat = (float) $faktura->tradeItems->sum('vat_amount');

        [$servicesNet, $servicesVat] = $this->sumAdditionalServices($faktura);

        $amount = $materialsSubtotal + $tradeSubtotal + $servicesNet;
        $tax = $materialsVat + $tradeVat + $servicesVat;
        $total = $amount + $tax;

        return $this->roundTriple($amount, $tax, $total);
    }

    private function computeSplit(Faktura $faktura): array
    {
        $taxRatePercent = 18.0;
        $overrides = $faktura->faktura_overrides['job_quantities'] ?? [];

        $net = 0.0;
        $vat = 0.0;
        foreach ($faktura->jobs as $job) {
            $qty = (float) ($overrides[$job->id] ?? $job->quantity);
            $line = (float) ($job->salePrice ?? 0) * $qty;
            $net += $line;
            $vat += $line * ($taxRatePercent / 100);
        }

        $tradeSubtotal = (float) $faktura->tradeItems->sum('total_price');
        $tradeVat = (float) $faktura->tradeItems->sum('vat_amount');

        [$servicesNet, $servicesVat] = $this->sumAdditionalServices($faktura);

        $amount = $net + $tradeSubtotal + $servicesNet;
        $tax = $vat + $tradeVat + $servicesVat;
        $total = $amount + $tax;

        return $this->roundTriple($amount, $tax, $total);
    }

    private function sumAdditionalServices(Faktura $faktura): array
    {
        $net = 0.0;
        $vat = 0.0;
        foreach ($faktura->additionalServices as $service) {
            $line = (float) $service->quantity * (float) $service->sale_price;
            $net += $line;
            $vat += $line * ((float) $service->vat_rate / 100);
        }

        return [$net, $vat];
    }

    private function roundTriple(float $amount, float $tax, float $total): array
    {
        return [
            'amount' => round($amount, 2),
            'tax' => round($tax, 2),
            'total' => round($total, 2),
        ];
    }
}
