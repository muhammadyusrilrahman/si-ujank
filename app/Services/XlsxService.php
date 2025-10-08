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
        if (extension_loaded('zip')) {
            try {
                $normalizedName = $this->ensureExtension($filename, 'xlsx');
                $xlsx = SimpleXLSXGen::fromArray($rows);
                $content = (string) $xlsx;

                return response()->streamDownload(function () use ($content) {
                    echo $content;
                }, $normalizedName, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]);
            } catch (\Throwable $exception) {
                // Fall back to CSV generation when XLSX creation fails.
            }
        }

        $fallbackName = $this->ensureExtension($filename, 'xlsx');
        $csvContent = "\xEF\xBB\xBF" . $this->generateCsv($rows);

        return response()->streamDownload(function () use ($csvContent) {
            echo $csvContent;
        }, $fallbackName, [
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

    private function ensureExtension(string $filename, string $extension): string
    {
        $normalized = trim($filename);
        if ($normalized === '') {
            $normalized = 'export.' . $extension;
        }

        if (strtolower(pathinfo($normalized, PATHINFO_EXTENSION)) !== strtolower($extension)) {
            $normalized = pathinfo($normalized, PATHINFO_FILENAME) . '.' . $extension;
        }

        return $normalized;
    }

    private function generateCsv(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        foreach ($rows as $row) {
            if (! is_array($row)) {
                $row = [$row];
            }

            $normalizedRow = array_map(function ($value) {
                if (is_bool($value)) {
                    return $value ? '1' : '0';
                }

                return is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
            }, $row);

            fputcsv($handle, $normalizedRow, ';');
        }

        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $csv;
    }
}



