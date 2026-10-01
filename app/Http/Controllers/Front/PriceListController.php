<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\PriceListExport;
use App\Services\PriceListSettingsService;
use Illuminate\Support\Facades\File;

class PriceListController extends Controller
{
    public function index(PriceListSettingsService $settings)
    {
        $exports = PriceListExport::query()->latest('generated_at')->paginate(30);
        return view('front.price-list.index', ['exports' => $exports, 'settings' => $settings->all()]);
    }

    public function current()
    {
        $export = PriceListExport::query()->latest('generated_at')->firstOrFail();
        return $this->xml($export);
    }

    public function archive(string $filename)
    {
        abort_unless(basename($filename) === $filename, 404);
        return $this->xml(PriceListExport::query()->where('filename', $filename)->firstOrFail());
    }

    private function xml(PriceListExport $export)
    {
        $path = storage_path('app/'.$export->path);
        abort_unless(is_file($path), 404);
        $response = response()->file($path, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="'.$export->filename.'"',
        ]);
        $response->setLastModified($export->generated_at);
        $response->setEtag($export->checksum);
        $response->isNotModified(request());
        return $response;
    }
}
