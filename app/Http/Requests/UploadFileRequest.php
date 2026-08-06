<?php

namespace App\Http\Requests;

use App\Helpers\Helper;
use App\Http\Traits\ConvertsBase64ToFiles;
use enshrined\svgSanitize\Sanitizer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadFileRequest extends Request
{
    use ConvertsBase64ToFiles;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $max_file_size = Helper::file_upload_max_size();

        return [
            // Validate by client extension, not sniffed MIME: signed files (.p7m/.p7c) sniff
            // as application/octet-stream, so `mimes:` rejected them despite being allowed.
            // `extensions:` still runs shouldBlockPhpUpload(), so PHP-disguise is blocked.
            'file.*' => 'required|file|extensions:'.config('filesystems.allowed_upload_extensions_for_validator').'|max:'.$max_file_size,
        ];
    }

    /**
     * Sanitizes (if needed) and Saves a file to the appropriate location
     * Returns the 'short' (storage-relative) filename
     */
    public function handleFile(string $dirname, string $name_prefix, $file): string
    {

        $extension = $file->getClientOriginalExtension();
        $file_name = $name_prefix.'-'.str_random(8).'-'.str_slug(basename($file->getClientOriginalName(), '.'.$extension)).'.'.$file->guessExtension();

        // Store at exactly $dirname.$file_name — the same key every caller rebuilds by raw
        // concatenation to look up integrity/download, so the two can never diverge.
        $storagePath = $dirname.$file_name;

        try {
            if ($file->getMimeType() === 'image/svg+xml') {
                // SVG must be sanitized before it is stored, so it can't be streamed as-is.
                $stored = Storage::put($storagePath, $this->handleSVG($file));
            } else {
                // Stream the uploaded temp file straight to disk instead of reading the whole
                // file into memory (file_get_contents). A large PDF no longer spikes memory or
                // slows the request — which is what let big uploads run long enough to trip the
                // front-tier read timeout (record saved, but the browser saw the connection drop).
                $stream = fopen($file->getRealPath(), 'rb');
                $stored = Storage::put($storagePath, $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            if ($stored === false) {
                Log::error('File upload failed to store: '.$storagePath);
            }
        } catch (\Throwable $e) {
            // Was Log::debug — a failed/partial write must be visible in production, not silent.
            Log::error('File upload failed to store '.$storagePath.': '.$e->getMessage());
        }

        return $file_name;
    }

    public function handleSVG($file)
    {
        $sanitizer = new Sanitizer;
        $dirtySVG = file_get_contents($file->getRealPath());

        return $sanitizer->sanitize($dirtySVG);
    }

    /**
     * Get the validation error messages that apply to the request, but
     * replace the attribute name with the name of the file that was attempted and failed
     * to make it clearer to the user which file is the bad one.
     */
    public function attributes(): array
    {
        $attributes = [];

        if (($this->file) && (is_array($this->file))) {

            for ($i = 0; $i < count($this->file); $i++) {

                try {

                    if ($this->file[$i]) {
                        $attributes['file.'.$i] = $this->file[$i]->getClientOriginalName();
                    }

                } catch (\Exception $e) {
                    $attributes['file.'.$i] = 'Invalid file';
                }

            }
        }

        return $attributes;

    }
}
