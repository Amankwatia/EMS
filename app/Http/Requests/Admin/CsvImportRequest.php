<?php

namespace App\Http\Requests\Admin;

use App\Models\Election;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class CsvImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'election_id' => ['required', 'integer', 'exists:elections,id'],
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ];
    }

    public function election(): Election
    {
        return Election::findOrFail($this->integer('election_id'));
    }

    public function csvFile(): UploadedFile
    {
        /** @var UploadedFile $file */
        $file = $this->file('file');

        return $file;
    }
}
