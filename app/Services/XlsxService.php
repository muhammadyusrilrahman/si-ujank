<?php

namespace App\Services;

use App\Support\SimpleXLSX;
use App\Support\SimpleXLSXGen;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class XlsxService
{
    public function download(array $rows, string $filename): StreamedResponse
    {
        $xlsx = SimpleXLSXGen::fromArray($rows);
        $content = (string) $xlsx;

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(UploadedFile $file): Collection
    {
        $path = $file->getRealPath();
        if ($path === false) {
            throw new \RuntimeException('File tidak dapat diakses.');
        }

        $xlsx = SimpleXLSX::parse($path);
        if (! $xlsx) {
            throw new \RuntimeException(SimpleXLSX::parseError());
        }

        $rows = collect($xlsx->rows());

        if ($rows->isEmpty()) {
            return collect();
        }

        $headings = collect($rows->first())
            ->map(function ($heading) {
                $normalized = strtolower(trim((string) $heading));
                $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized);
                return trim($normalized, '_');
            })
            ->filter()
            ->values();

        $dataRows = $rows->slice(1)->filter(function ($row) {
            if (! is_array($row)) {
                return false;
            }

            foreach ($row as $value) {
                if ($value !== null && $value !== '') {
                    return true;
                }
            }

            return false;
        })->values();

        return $dataRows->map(function ($row) use ($headings) {
            $assoc = [];
            foreach ($headings as $index => $heading) {
                $assoc[$heading] = $row[$index] ?? null;
            }

            return $assoc;
        });
    }
}
