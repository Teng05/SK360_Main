<?php
namespace App\Services;
use Illuminate\Support\Facades\Storage;
class FinancialProofService
{
    public function response(?object $file)
    {
        abort_unless($file && $file->storage_disk === 'local' && str_starts_with($file->storage_path, 'financial/expenses/') && !str_contains($file->storage_path, '..'), 404);
        abort_unless(Storage::disk('local')->exists($file->storage_path), 404);
        abort_if(($file->budget_report_id ?? null) !== null || ($file->fund_release_id ?? null) !== null, 404);
        $disk = Storage::disk('local');
        $path = realpath($disk->path($file->storage_path));
        $root = realpath($disk->path('financial/expenses'));
        abort_unless($path && $root && str_starts_with(str_replace('\\', '/', $path), rtrim(str_replace('\\', '/', $root), '/').'/'), 404);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'application/pdf'], true) && $mime === $file->mime_type, 415, 'Unsupported proof format.');
        return response()->file($path, [
            'Content-Type' => $mime, 'Cache-Control' => 'private, no-store, max-age=0, must-revalidate',
            'Pragma' => 'no-cache', 'Expires' => '0', 'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "frame-ancestors 'self'", 'Referrer-Policy' => 'no-referrer',
        ])->setContentDisposition('inline', basename($file->original_name))->setPrivate();
    }
}
