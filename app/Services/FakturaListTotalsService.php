<?php

namespace App\Services;

use App\Models\Faktura;
use App\Models\Job;

/**
 * Net (amount), VAT (tax), and gross (total) for All Invoices list — aligned with
 * preview/print logic: article-based lines for regular fakturas; job salePrice×qty + 18% for split.
 *
 * For regular fakturas with merge_groups, materials are counted like InvoiceController / PDF:
 * one line per merge group (group quantity & sale_price when set), constituent jobs excluded.
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
        /** Align with invoices/outgoing_invoice_v2.blade.php */
        $rateMap = [1 => 18, 2 => 5, 3 => 10];
        $jobQtyOverrides = $faktura->faktura_overrides['job_quantities'] ?? [];
        $jobVatOverrides = $faktura->faktura_overrides['job_vat_rates'] ?? [];

        $jobById = [];
        foreach ($faktura->invoices as $invoice) {
            foreach ($invoice->jobs as $job) {
                $jobById[$job->id] = $job;
            }
        }

        $mergedJobIds = [];
        $mergeGroups = is_array($faktura->merge_groups) ? $faktura->merge_groups : [];

        foreach ($mergeGroups as $grp) {
            $ids = isset($grp['job_ids']) && is_array($grp['job_ids']) ? $grp['job_ids'] : [];
            $ids = array_values(array_filter($ids, fn ($jid) => isset($jobById[$jid])));
            if (count($ids) < 2) {
                continue;
            }
            foreach ($ids as $jid) {
                $mergedJobIds[$jid] = true;
            }

            /** @var Job $firstJob */
            $firstJob = $jobById[$ids[0]];
            $repId = $firstJob->id;

            if (isset($grp['quantity'])) {
                $qty = (float) $grp['quantity'];
            } elseif (isset($jobQtyOverrides[$repId])) {
                $qty = (float) $jobQtyOverrides[$repId];
            } elseif (isset($jobQtyOverrides[(string) $repId])) {
                $qty = (float) $jobQtyOverrides[(string) $repId];
            } else {
                $sumQty = 0.0;
                foreach ($ids as $jid) {
                    $sumQty += $this->resolveJobQuantity($jobById[$jid], $jobQtyOverrides);
                }
                $qty = $sumQty;
            }

            $salePrice = isset($grp['sale_price']) ? (float) $grp['sale_price'] : (float) ($firstJob->salePrice ?? 0);
            $lineNet = $salePrice * $qty;
            $materialsSubtotal += $lineNet;
            $materialsVat += $this->computeMaterialsVatForLine($lineNet, $firstJob, $repId, $jobVatOverrides, $rateMap);
        }

        foreach ($faktura->invoices as $invoice) {
            foreach ($invoice->jobs as $job) {
                if (isset($mergedJobIds[$job->id])) {
                    continue;
                }

                $qty = $this->resolveJobQuantity($job, $jobQtyOverrides);
                $lineNet = (float) ($job->salePrice ?? 0) * $qty;
                $materialsSubtotal += $lineNet;
                $materialsVat += $this->computeMaterialsVatForLine($lineNet, $job, $job->id, $jobVatOverrides, $rateMap);
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

    private function resolveJobQuantity(Job $job, array $jobQtyOverrides): float
    {
        $jid = $job->id;
        $qty = (float) ($job->quantity ?? 0);
        if ($jid !== null && isset($jobQtyOverrides[$jid])) {
            return (float) $jobQtyOverrides[$jid];
        }
        if ($jid !== null && isset($jobQtyOverrides[(string) $jid])) {
            return (float) $jobQtyOverrides[(string) $jid];
        }

        return $qty;
    }

    /**
     * VAT for one materials line (same rules as list/PDF): optional job_vat_rates override,
     * else article tax_type distribution, else 18% on line net.
     */
    private function computeMaterialsVatForLine(float $lineNet, Job $job, ?int $jobIdForOverrides, array $jobVatOverrides, array $rateMap): float
    {
        $jid = $jobIdForOverrides;
        $vatOvr = null;
        if ($jid !== null && isset($jobVatOverrides[$jid])) {
            $vatOvr = (int) $jobVatOverrides[$jid];
        } elseif ($jid !== null && isset($jobVatOverrides[(string) $jid])) {
            $vatOvr = (int) $jobVatOverrides[(string) $jid];
        }
        if ($vatOvr !== null && in_array($vatOvr, [5, 10, 18], true)) {
            return $lineNet * ($vatOvr / 100);
        }

        $articles = $job->articles;
        if ($articles->isEmpty()) {
            return $lineNet * 0.18;
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

            return $lineNet * ($r / 100);
        }
        if (count($uniqueRates) > 1) {
            $vat = 0.0;
            foreach ($articles as $article) {
                $tt = (int) ($article->tax_type ?? 0);
                $r = $rateMap[$tt] ?? 0;
                $pq = (float) ($article->pivot->quantity ?? 0);
                $unitPrice = (float) ($article->price_1 ?? 0);
                $articleLine = $pq * $unitPrice;
                if ($r > 0) {
                    $vat += $articleLine * ($r / 100);
                }
            }

            return $vat;
        }

        return $lineNet * 0.18;
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
