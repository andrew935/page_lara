<?php

namespace App\Http\Controllers\Api;

use App\Domains\Services\DomainService;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessImportBatchJob;
use App\Models\Domain;
use App\Support\AccountResolver;
use Illuminate\Http\Request;

class DomainApiController extends Controller
{
    public function index()
    {
        $account = AccountResolver::current();
        $domains = Domain::where('account_id', $account->id)
            ->orderByRaw("CASE 
                WHEN status = 'down' THEN 0 
                WHEN status = 'ok' THEN 1 
                WHEN status = 'pending' THEN 2 
                ELSE 3 END")
            ->orderByDesc('id')
            ->paginate(25);

        return response()->json($domains);
    }

    public function store(Request $request, DomainService $service)
    {
        $data = $request->validate([
            'domains' => ['required', 'string'],
        ]);

        [$created, $newDomains] = $service->addDomainsFromText($data['domains']);

        return response()->json([
            'created' => $created,
            'domains' => $newDomains,
        ]);
    }

    public function check(Domain $domain, DomainService $service)
    {
        $account = AccountResolver::current();
        if ($domain->account_id && $domain->account_id !== $account->id) {
            abort(404);
        }

        $service->queueDomain($domain);

        return response()->json([
            'message' => "Queued check for {$domain->domain}",
            'domain' => $service->mapDomain($domain->fresh()),
        ]);
    }

    public function checkAll(DomainService $service)
    {
        $updated = $service->queueAll();

        return response()->json([
            'message' => 'Queued all domains for check.',
            'domains' => $updated,
        ]);
    }

    public function destroy(Domain $domain)
    {
        $account = AccountResolver::current();
        if ($domain->account_id && $domain->account_id !== $account->id) {
            abort(404);
        }

        $domain->delete();

        return response()->json(['message' => 'Domain deleted.']);
    }

    public function importJson(Request $request, DomainService $service)
    {
        $data = $request->validate([
            'json' => ['required', 'string'],
        ]);

        $decoded = json_decode($data['json'], true);
        if (!is_array($decoded)) {
            return response()->json(['message' => 'Invalid JSON array'], 422);
        }

        $created = 0;
        if (count($decoded) > 200) {
            $batch = $service->createImportBatch('json', $decoded);
            ProcessImportBatchJob::dispatch($batch);
        } else {
            $created = $service->importJsonPayload($decoded);
        }

        return response()->json(['message' => "{$created} domain(s) imported."]);
    }

    public function importLatest(DomainService $service)
    {
        $created = $service->importLatestFromFeed();

        return response()->json(['imported' => $created]);
    }
}


