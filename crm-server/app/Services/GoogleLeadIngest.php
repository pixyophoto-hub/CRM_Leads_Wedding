<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Lead;
use Illuminate\Support\Carbon;

class GoogleLeadIngest
{
    /**
     * Canonical Malaysian phone for dedupe: digits only, country code 60 and
     * any leading zero stripped. So "+60 19-888 7766" and "019-888 7766" both
     * become "198887766".
     */
    public static function canon(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }
        $d = preg_replace('/\D/', '', $phone);
        if ($d === '') {
            return null;
        }
        if (str_starts_with($d, '60')) {
            $d = substr($d, 2);
        }
        return ltrim($d, '0') ?: $d;
    }

    /**
     * Accept a loose payload from Google Apps Script (Form or Sheet) and
     * create a lead, deduping by phone. Mirrors the dedupe approach used in
     * the Photobook CRM's LeadSheetSync (matched on normalized phone).
     *
     * @return array{lead: Lead, created: bool}
     */
    public function ingest(array $data): array
    {
        $pick = function (array $keys) use ($data) {
            foreach ($keys as $k) {
                foreach ($data as $dk => $dv) {
                    if (strtolower(trim($dk)) === $k && trim((string) $dv) !== '') {
                        return trim((string) $dv);
                    }
                }
            }
            return null;
        };

        $name  = $pick(['name', 'nama', 'full name', 'full_name']) ?? 'Tanpa Nama';
        $phone = $pick(['phone', 'telefon', 'no telefon', 'no. telefon', 'whatsapp', 'wa', 'hp']);
        $email = $pick(['email', 'emel', 'e-mel', 'e-mail']);
        $source = $pick(['source', 'sumber']) ?? 'Google Form';
        $service = $pick(['service', 'pakej', 'package', 'servis']) ?? 'Wedding';
        $notes = $pick(['notes', 'nota', 'message', 'mesej', 'catatan']);

        $digits = self::canon($phone);

        // Dedupe: same normalized phone already ingested -> skip create.
        if ($digits) {
            $existing = Lead::where('source_ref', $digits)->first();
            if ($existing) {
                return ['lead' => $existing, 'created' => false];
            }
        }

        $now = Carbon::now();
        $lead = Lead::create([
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'source' => $source,
            'service' => $service,
            'status' => 'New Lead',
            'notes' => $notes,
            'source_ref' => $digits,
            'last_contact_at' => $now,
        ]);

        Activity::create(['type' => 'new_lead', 'name' => $name, 'created_at' => $now]);

        return ['lead' => $lead, 'created' => true];
    }
}
