<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Automation;
use App\Models\Lead;
use App\Models\Message;
use App\Models\Setting;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
    private const LEAD_FIELDS = ['name', 'phone', 'email', 'source', 'service', 'status', 'pic', 'value', 'notes'];

    private function ms($dt): ?int
    {
        return $dt ? Carbon::parse($dt)->getTimestampMs() : null;
    }

    private function serializeLead(Lead $l): array
    {
        return [
            'id' => (string) $l->id,
            'name' => $l->name,
            'phone' => $l->phone,
            'email' => $l->email,
            'source' => $l->source,
            'service' => $l->service,
            'status' => $l->status,
            'pic' => $l->pic,
            'value' => (int) $l->value,
            'notes' => $l->notes,
            'createdAt' => $this->ms($l->created_at),
            'lastContactAt' => $this->ms($l->last_contact_at ?: $l->created_at),
            'messages' => $l->messages->map(fn (Message $m) => [
                'from' => $m->direction,
                'text' => $m->body,
                'at' => $this->ms($m->sent_at ?: $m->created_at),
            ])->all(),
        ];
    }

    private function serializeTask(Task $t): array
    {
        return [
            'id' => (string) $t->id,
            'title' => $t->title,
            'assignee' => $t->assignee,
            'due' => $this->ms($t->due_at),
            'priority' => $t->priority,
            'done' => (bool) $t->done,
        ];
    }

    private function serializeAutomation(Automation $a): array
    {
        return [
            'id' => (string) $a->id,
            'name' => $a->name,
            'trigger' => $a->trigger,
            'action' => $a->action,
            'active' => (bool) $a->active,
        ];
    }

    private function defaultSettings(): array
    {
        return [
            'studioName' => 'Pixyo Studio', 'email' => '', 'phone' => '',
            'address' => '', 'userName' => 'Admin', 'userRole' => 'Admin', 'plan' => 'Pelan Percuma',
        ];
    }

    private function ensureDefaultAutomations(): void
    {
        if (Automation::count() > 0) {
            return;
        }
        $defaults = [
            ['name' => 'New Lead Auto-Reply', 'trigger' => 'Trigger: Lead baru diterima', 'action' => 'Action: Hantar WhatsApp greeting', 'active' => true],
            ['name' => 'Peringatan Tiada Balas', 'trigger' => 'Trigger: Tiada balasan selepas 24 jam', 'action' => 'Action: Notifikasi kepada PIC', 'active' => true],
            ['name' => 'Quotation Follow Up', 'trigger' => 'Trigger: Quotation dihantar > 3 hari', 'action' => 'Action: Hantar mesej susulan', 'active' => false],
            ['name' => 'Pengesahan Tempahan', 'trigger' => 'Trigger: Status ditukar ke Booked', 'action' => 'Action: Hantar maklumat pengesahan', 'active' => true],
        ];
        foreach ($defaults as $d) {
            Automation::create($d);
        }
    }

    public function state()
    {
        $this->ensureDefaultAutomations();

        return response()->json([
            'leads' => Lead::with('messages')->orderByDesc('created_at')->get()->map(fn ($l) => $this->serializeLead($l))->all(),
            'tasks' => Task::orderBy('due_at')->get()->map(fn ($t) => $this->serializeTask($t))->all(),
            'automations' => Automation::orderBy('id')->get()->map(fn ($a) => $this->serializeAutomation($a))->all(),
            'activities' => Activity::orderByDesc('created_at')->limit(50)->get()
                ->map(fn ($a) => ['type' => $a->type, 'name' => $a->name, 'at' => $this->ms($a->created_at)])->all(),
            'settings' => array_merge($this->defaultSettings(), Setting::allAsMap()),
        ]);
    }

    // ---- Leads ----
    public function storeLead(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'phone' => 'nullable|string|max:60',
            'email' => 'nullable|string|max:191',
            'source' => 'nullable|string|max:60',
            'service' => 'nullable|string|max:60',
            'status' => 'nullable|string|max:60',
            'pic' => 'nullable|string|max:60',
        ]);
        $now = Carbon::now();
        $lead = Lead::create(array_merge([
            'source' => 'Manual', 'service' => 'Wedding', 'status' => 'New Lead',
        ], $data, ['last_contact_at' => $now]));

        Activity::create(['type' => 'new_lead', 'name' => $lead->name, 'created_at' => $now]);

        return response()->json($this->serializeLead($lead->load('messages')));
    }

    public function updateLead(Request $request, Lead $lead)
    {
        $data = $request->only(self::LEAD_FIELDS);
        $statusChanged = array_key_exists('status', $data) && $data['status'] !== $lead->status;

        $lead->fill($data);
        if ($statusChanged) {
            $lead->last_contact_at = Carbon::now();
        }
        $lead->save();

        if ($statusChanged) {
            Activity::create([
                'type' => $lead->status === 'Booked' ? 'booked' : 'status',
                'name' => $lead->name,
                'created_at' => Carbon::now(),
            ]);
        }

        return response()->json($this->serializeLead($lead->load('messages')));
    }

    public function destroyLead(Lead $lead)
    {
        $lead->delete();
        return response()->json(['ok' => true]);
    }

    // ---- Messages ----
    public function storeMessage(Request $request, Lead $lead)
    {
        $data = $request->validate(['text' => 'required|string']);
        $now = Carbon::now();
        $lead->messages()->create(['direction' => 'me', 'body' => $data['text'], 'sent_at' => $now]);
        $lead->last_contact_at = $now;
        $lead->save();
        Activity::create(['type' => 'message', 'name' => $lead->name, 'created_at' => $now]);

        return response()->json($this->serializeLead($lead->load('messages')));
    }

    // ---- Tasks ----
    public function storeTask(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:191',
            'assignee' => 'nullable|string|max:60',
            'due' => 'nullable|numeric',
            'priority' => 'nullable|string|max:30',
        ]);
        $task = Task::create([
            'title' => $data['title'],
            'assignee' => $data['assignee'] ?? 'Admin',
            'due_at' => isset($data['due']) ? Carbon::createFromTimestampMs((int) $data['due']) : Carbon::now()->addDay(),
            'priority' => $data['priority'] ?? 'Sederhana',
            'done' => false,
        ]);
        return response()->json($this->serializeTask($task));
    }

    public function updateTask(Request $request, Task $task)
    {
        if ($request->has('done')) {
            $task->done = (bool) $request->boolean('done');
        }
        foreach (['title', 'assignee', 'priority'] as $f) {
            if ($request->has($f)) {
                $task->{$f} = $request->input($f);
            }
        }
        if ($request->has('due')) {
            $task->due_at = Carbon::createFromTimestampMs((int) $request->input('due'));
        }
        $task->save();
        return response()->json($this->serializeTask($task));
    }

    public function destroyTask(Task $task)
    {
        $task->delete();
        return response()->json(['ok' => true]);
    }

    // ---- Automations ----
    public function updateAutomation(Request $request, Automation $automation)
    {
        if ($request->has('active')) {
            $automation->active = (bool) $request->boolean('active');
        }
        $automation->save();
        return response()->json($this->serializeAutomation($automation));
    }

    // ---- Settings ----
    public function updateSettings(Request $request)
    {
        $map = $request->all();
        $allowed = array_intersect_key($map, $this->defaultSettings());
        if (! empty($allowed)) {
            Setting::putMany($allowed);
        }
        return response()->json(array_merge($this->defaultSettings(), Setting::allAsMap()));
    }

    // ---- Data management ----
    public function clear()
    {
        DB::table('messages')->delete();
        Lead::withTrashed()->forceDelete();
        Task::query()->delete();
        Activity::query()->delete();
        Setting::query()->delete();
        return $this->state();
    }

    public function seed()
    {
        DB::table('messages')->delete();
        Lead::withTrashed()->forceDelete();
        Task::query()->delete();
        Activity::query()->delete();

        $now = Carbon::now();
        $H = 3600; $D = 86400;
        $rows = [
            ['Nurul Amani', '+60 12-345 6789', 'nurul@email.com', 'FB Live', 'Wedding', 'New Lead', 'Ali', 0, 120, [
                ['customer', 'Assalamualaikum, saya berminat dengan pakej wedding anda', 2400],
                ['me', 'Waalaikumsalam! Terima kasih. Boleh saya tahu tarikh majlis?', 2280],
                ['customer', 'Boleh minta quotation untuk pakej Gold?', 120],
            ]],
            ['Aiman Hakimi', '+60 11-234 5678', 'aiman@gmail.com', 'Content (IG)', 'Wedding', 'Waiting Reply', 'Faris', 0, 900, [
                ['customer', 'Hai, kami dah tengok quotation yang dihantar', 1800],
                ['me', 'Alhamdulillah! Ada sebarang pertanyaan tentang pakej?', 1200],
            ]],
            ['Siti Nabila', '+60 10-987 6543', 'siti@email.com', 'Ads (FB)', 'Nikah', 'Follow Up', 'Fatin', 0, 1680, [
                ['customer', 'Rabu ok. Pukul 3 petang boleh?', 1680],
            ]],
            ['Zul & Mira', '+60 12-567 8901', 'zul@email.com', 'Google Form', 'Sanding', 'Quotation Sent', 'Ali', 5500, $H, []],
            ['Hana Adila', '+60 11-890 1234', 'hana@email.com', 'Referral', 'Wedding', 'Booked', 'Faris', 5500, 3 * $H, []],
            ['Farid Hakimi', '+60 12-123 4567', 'farid@email.com', 'FB Live', 'Wedding', 'Lost', 'Ali', 0, 5 * $H, []],
            ['Syafiqah', '+60 10-456 7890', 'syafiqah@email.com', 'Ads (IG)', 'Nikah', 'Future Follow Up', 'Fatin', 0, $D, []],
            ['Ahmad Rashid', '+60 12-678 9012', 'rashid@email.com', 'Landing Page', 'Wedding', 'New Lead', 'Aiman', 0, $D, []],
            ['Mira & Azri', '+60 11-678 9012', 'mira@email.com', 'FB Live', 'Wedding', 'Booked', 'Faris', 8000, 4 * $D, []],
        ];

        foreach ($rows as $r) {
            [$name, $phone, $email, $source, $service, $status, $pic, $value, $agoSec, $msgs] = $r;
            $ts = (clone $now)->subSeconds($agoSec);
            $lead = Lead::create([
                'name' => $name, 'phone' => $phone, 'email' => $email, 'source' => $source,
                'service' => $service, 'status' => $status, 'pic' => $pic, 'value' => $value,
                'source_ref' => \App\Services\GoogleLeadIngest::canon($phone),
                'created_at' => $ts, 'updated_at' => $ts, 'last_contact_at' => $ts,
            ]);
            foreach ($msgs as $m) {
                $lead->messages()->create([
                    'direction' => $m[0], 'body' => $m[1],
                    'sent_at' => (clone $now)->subSeconds($m[2]),
                ]);
            }
        }

        foreach ([
            ['new_lead', 'Nurul Amani', 120],
            ['quotation', 'Zul & Mira', $H],
            ['reply', 'Siti Nabila', 1680],
            ['booked', 'Hana Adila', 3 * $H],
        ] as $a) {
            Activity::create(['type' => $a[0], 'name' => $a[1], 'created_at' => (clone $now)->subSeconds($a[2])]);
        }

        return $this->state();
    }
}
