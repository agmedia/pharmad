<?php

namespace App\Http\Controllers\Back\Settings;

use App\Http\Controllers\Controller;
use App\Models\PriceListExport;
use App\Services\AnchorPriceCsvImporter;
use App\Services\DigitalPriceListGenerator;
use App\Services\PriceListSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DigitalPriceListController extends Controller
{
    public function edit(PriceListSettingsService $settings)
    {
        $exports = PriceListExport::query()->latest('generated_at')->limit(20)->get();
        return view('back.settings.digital-price-list', ['settings' => $settings->all(), 'exports' => $exports]);
    }

    public function update(Request $request, PriceListSettingsService $settings)
    {
        $data = $request->validate([
            'object_type' => 'required|string|max:100', 'address' => 'required|string|max:255',
            'object_code' => 'required|string|max:100', 'generation_time' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'retention_days' => 'required|integer|min:30|max:3650',
        ]);
        $settings->save($data);
        return back()->with('success', 'Postavke digitalnog cjenika su spremljene.');
    }

    public function generate(DigitalPriceListGenerator $generator)
    {
        try {
            $export = $generator->generate();
            return back()->with('success', 'Generiran je '.$export->filename.'.');
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Cjenik nije objavljen: '.$e->getMessage());
        }
    }

    public function import(Request $request, AnchorPriceCsvImporter $importer)
    {
        $request->validate(['csv' => 'required|file|max:5120|mimes:csv,txt']);
        $report = $importer->import($request->file('csv'), optional($request->user())->id);
        session(['anchor_csv_errors' => $report['errors']]);
        return back()->with('csv_report', $report);
    }

    public function errors(Request $request)
    {
        $errors = $request->session()->get('anchor_csv_errors', []);
        abort_if(empty($errors), 404);
        $stream = function () use ($errors) {
            $out = fopen('php://output', 'wb');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['redak', 'sku', 'greska'], ';');
            foreach ($errors as $error) fputcsv($out, $error, ';');
            fclose($out);
        };
        return response()->streamDownload($stream, 'greske-sidrene-cijene.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
