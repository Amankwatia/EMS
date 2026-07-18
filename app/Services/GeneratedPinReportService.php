<?php

namespace App\Services;

use App\Models\Import;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class GeneratedPinReportService
{
    public function exists(Import $import): bool
    {
        return filled($import->generated_pins_path)
            && Storage::disk('local')->exists($import->generated_pins_path);
    }

    public function decryptedContents(Import $import): string
    {
        return Crypt::decryptString(Storage::disk('local')->get($import->generated_pins_path));
    }

    public function delete(Import $import): void
    {
        if ($import->generated_pins_path) {
            Storage::disk('local')->delete($import->generated_pins_path);
            $import->update(['generated_pins_path' => null]);
        }
    }

    public function deleteExpired(): int
    {
        $deleted = 0;

        Import::query()
            ->whereNotNull('generated_pins_path')
            ->where('generated_pins_expires_at', '<=', now())
            ->each(function (Import $import) use (&$deleted): void {
                $this->delete($import);
                $deleted++;
            });

        return $deleted;
    }
}
