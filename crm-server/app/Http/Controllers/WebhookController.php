<?php

namespace App\Http\Controllers;

use App\Services\GoogleLeadIngest;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function google(string $secret, Request $request, GoogleLeadIngest $ingest)
    {
        $expected = (string) config('wedding.webhook_secret');
        if ($expected === '' || ! hash_equals($expected, $secret)) {
            return response()->json(['ok' => false, 'message' => 'Invalid secret'], 403);
        }

        // Accept JSON body, form-encoded, or a `payload` field (Apps Script default).
        $data = $request->json()->all();
        if (empty($data)) {
            $data = $request->all();
        }
        if (isset($data['payload']) && is_string($data['payload'])) {
            $decoded = json_decode($data['payload'], true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }

        if (empty($data) || ! is_array($data)) {
            return response()->json(['ok' => false, 'message' => 'Empty payload'], 422);
        }

        $result = $ingest->ingest($data);

        return response()->json([
            'ok' => true,
            'created' => $result['created'],
            'lead_id' => $result['lead']->id,
        ]);
    }
}
