<?php

namespace App\Services;

use App\Models\Faktura;

/**
 * Net (amount), VAT (tax), and gross (total) for All Invoices list — aligned with
 * preview/print logic: article-based lines for regular fakturas; job salePrice×qty + 18% for split.
 */
class FakturaListTotalsService
{
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
        /** Align with invoices/outgoing_invoice_v2.blade.php: row net = salePrice × qty; VAT follows PDF rules. */
        $rateMap = [1 => 18, 2 => 5, 3 => 10];
        $jobQtyOverrides = $faktura->faktura_overrides['job_quantities'] ?? [];
        $jobVatOverrides = $faktura->faktura_overrides['job_vat_rates'] ?? [];

        foreach ($faktura->invoices as $invoice) {
            foreach ($invoice->jobs as $job) {
                $qty = (float) ($job->quantity ?? 0);
                $jid = $job->id;
                if (isset($jobQtyOverrides[$jid])) {
                    $qty = (float) $jobQtyOverrides[$jid];
                } elseif ($jid !== null && isset($jobQtyOverrides[(string) $jid])) {
                    $qty = (float) $jobQtyOverrides[(string) $jid];
                }
                $lineNet = (float) ($job->salePrice ?? 0) * $qty;
                $materialsSubtotal += $lineNet;

                $vatOvr = null;
                if ($jid !== null && isset($jobVatOverrides[$jid])) {
                    $vatOvr = (int) $jobVatOverrides[$jid];
                } elseif ($jid !== null && isset($jobVatOverrides[(string) $jid])) {
                    $vatOvr = (int) $jobVatOverrides[(string) $jid];
                }
                if ($vatOvr !== null && in_array($vatOvr, [5, 10, 18], true)) {
                    $materialsVat += $lineNet * ($vatOvr / 100);

                    continue;
                }

                $articles = $job->articles;
                if ($articles->isEmpty()) {
                    $materialsVat += $lineNet * 0.18;

                    continue;
                }

                $ratesFound = [];
                foreach ($articles as $art) {
                    $tt = (int) ($art->tax_type ?? 0);
                    if (isset($rateMap[$tt])) {
                        $ratesFound[$rateMap[$tt]] = true;
                    }
                }
                $uniqueRates = array_keys($ratesFound);

                if (count($uniqueRates) === 1) {
                    $r = (int) $uniqueRates[0];
                    $materialsVat += $lineNet * ($r / 100);
                } elseif (count($uniqueRates) > 1) {
                    foreach ($articles as $article) {
                        $tt = (int) ($article->tax_type ?? 0);
                        $r = $rateMap[$tt] ?? 0;
                        $pq = (float) ($article->pivot->quantity ?? 0);
                        $unitPrice = (float) ($article->price_1 ?? 0);
                        $articleLine = $pq * $unitPrice;
                        if ($r > 0) {
                            $materialsVat += $articleLine * ($r / 100);
                        }
                    }
                } else {
                    $materialsVat += $lineNet * 0.18;
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
        $jobVatOverrides = $faktura->faktura_overrides['job_vat_rates'] ?? [];

        $net = 0.0;
        $vat = 0.0;
        foreach ($faktura->jobs as $job) {
            $qty = (float) ($overrides[$job->id] ?? $job->quantity);
            $line = (float) ($job->salePrice ?? 0) * $qty;
            $net += $line;
            $jid = $job->id;
            $pct = $taxRatePercent;
            if ($jid !== null && isset($jobVatOverrides[$jid])) {
                $ovr = (int) $jobVatOverrides[$jid];
                if (in_array($ovr, [5, 10, 18], true)) {
                    $pct = (float) $ovr;
                }
            } elseif ($jid !== null && isset($jobVatOverrides[(string) $jid])) {
                $ovr = (int) $jobVatOverrides[(string) $jid];
                if (in_array($ovr, [5, 10, 18], true)) {
                    $pct = (float) $ovr;
                }
            }
            $vat += $line * ($pct / 100);
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
