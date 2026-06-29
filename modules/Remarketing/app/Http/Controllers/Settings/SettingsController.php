<?php

namespace Modules\Remarketing\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use League\Csv\Reader;
use League\Csv\Writer;
use Modules\Core\Models\Setting;
use Modules\Remarketing\Http\Requests\Settings\ImportSuppressionsRequest;
use Modules\Remarketing\Http\Requests\Settings\UpdateConsentSettingsRequest;
use Modules\Remarketing\Http\Requests\Settings\UpdateGeneralSettingsRequest;
use Modules\Remarketing\Models\Store;
use Modules\Remarketing\Models\Suppression;
use SplTempFileObject;

class SettingsController extends Controller
{
    private const PREFIX = 'remarketing.';

    public function general(): View
    {
        $this->authorize('viewAny', Suppression::class);

        $get = fn (string $key, mixed $default = null) => Setting::get(self::PREFIX.$key, $default);

        return view('remarketing::settings.general', compact('get'));
    }

    public function updateGeneral(UpdateGeneralSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        foreach ($data as $key => $value) {
            Setting::set(self::PREFIX.$key, $value);
        }

        Setting::clearPrefixCache(self::PREFIX);

        return redirect()->route('settings.remarketing.general')
            ->with('success', 'Configuración general actualizada correctamente.');
    }

    public function consent(): View
    {
        $this->authorize('viewAny', Suppression::class);

        $get = fn (string $key, mixed $default = null) => Setting::get(self::PREFIX.$key, $default);

        return view('remarketing::settings.consent', compact('get'));
    }

    public function updateConsent(UpdateConsentSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        foreach ($data as $key => $value) {
            Setting::set(self::PREFIX.$key, $value);
        }

        Setting::clearPrefixCache(self::PREFIX);

        return redirect()->route('settings.remarketing.consent')
            ->with('success', 'Configuración de consent actualizada correctamente.');
    }

    public function suppressions(): View
    {
        $this->authorize('viewAny', Suppression::class);

        $user = auth()->user();
        $storeIds = $this->userStoreIds();

        $suppressions = Suppression::query()
            ->with('store')
            ->whereIn('store_id', $storeIds)
            ->latest()
            ->paginate(30);

        $stores = Store::query()->whereIn('id', $storeIds)->get(['id', 'name']);

        return view('remarketing::settings.suppressions', compact('suppressions', 'stores'));
    }

    public function exportSuppressions(): Response
    {
        $this->authorize('viewAny', Suppression::class);

        $storeIds = $this->userStoreIds();

        $csv = Writer::createFromFileObject(new SplTempFileObject);
        $csv->insertOne(['email', 'store', 'reason', 'notes', 'created_at']);

        Suppression::query()
            ->with('store:id,name')
            ->whereIn('store_id', $storeIds)
            ->orderBy('id')
            ->chunk(1000, function ($chunk) use ($csv): void {
                foreach ($chunk as $sup) {
                    $csv->insertOne([
                        $sup->email,
                        $sup->store?->name ?? '',
                        $sup->reason ?? '',
                        $sup->notes ?? '',
                        $sup->created_at?->toIso8601String() ?? '',
                    ]);
                }
            });

        $filename = 'remarketing-suppressions-'.now()->format('Y-m-d-His').'.csv';

        return response($csv->toString(), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function importSuppressions(ImportSuppressionsRequest $request): RedirectResponse
    {
        $store = Store::query()
            ->whereIn('id', $this->userStoreIds())
            ->findOrFail($request->integer('store_id'));

        $reader = Reader::createFromPath($request->file('file')->getRealPath(), 'r');
        $reader->setHeaderOffset(0);

        $reason = $request->input('reason', 'manual');
        $imported = 0;
        $skipped = 0;
        $invalid = 0;

        foreach ($reader->getRecords() as $row) {
            $email = trim((string) ($row['email'] ?? array_values($row)[0] ?? ''));

            if ($email === '') {
                continue;
            }

            $emailValidator = Validator::make(['email' => $email], ['email' => ['required', 'email']]);

            if ($emailValidator->fails()) {
                $invalid++;

                continue;
            }

            $existed = Suppression::query()
                ->where('store_id', $store->id)
                ->where('email', strtolower($email))
                ->exists();

            if ($existed) {
                $skipped++;

                continue;
            }

            Suppression::create([
                'store_id' => $store->id,
                'email' => strtolower($email),
                'reason' => $row['reason'] ?? $reason,
                'notes' => $row['notes'] ?? null,
            ]);

            $imported++;
        }

        return redirect()->route('settings.remarketing.suppressions')
            ->with('success', sprintf(
                'Importación completada: %d nuevos, %d ya existían, %d inválidos.',
                $imported,
                $skipped,
                $invalid
            ));
    }

    private function userStoreIds(): array
    {
        $user = auth()->user();

        return Store::query()
            ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id')
            ->all();
    }
}
