<?php

namespace App\Http\Controllers;

use App\Helpers\StorageHelper;
use App\Http\Requests\UploadFileRequest;
use App\Models\Actionlog;
use App\Models\CustomerContract;
use App\Models\Import;
use App\Support\Files\FileIntegrity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * This controller provide the health route  for
 * the Snipe-IT Asset Management application.
 *
 * @version   v1.0
 *
 * @return JsonResponse
 */
class UploadedFilesController extends Controller
{
    /**
     * Accepts a POST to upload a file to the server.
     *
     * @param  string  $object_type  the type of object to upload the file to
     * @param  int  $id  the ID of the object to store so we can check permisisons
     *
     * @since  [v8.2.2]
     *
     * @author [A. Gianotto <snipe@snipe.net>]
     */
    public function store(UploadFileRequest $request, $object_type, $id): RedirectResponse
    {

        // Check the permissions to make sure the user can view the object
        $object = self::$map_object_type[$object_type]::withTrashed()->find($id);
        $this->authorize('files', $object);

        if (! $object) {
            return redirect()->back()->withFragment('files')->with('error', trans('general.file_upload_status.invalid_object'));
        }

        // If the file storage directory doesn't exist, create it
        if (! Storage::exists(self::$map_storage_path[$object_type])) {
            Storage::makeDirectory(self::$map_storage_path[$object_type], 775);
        }

        if ($request->hasFile('file')) {
            // Loop over the attached files and add them to the object
            foreach ($request->file('file') as $file) {
                $file_name = $request->handleFile(self::$map_storage_path[$object_type], self::$map_file_prefix[$object_type].'-'.$object->id, $file);
                $files[] = $file_name;
                $object->logUpload(
                    $file_name,
                    $request->input('notes'),
                    FileIntegrity::metadataForStoredFile(self::$map_storage_path[$object_type].$file_name, $file)
                );
            }

            $files = Actionlog::select('action_logs.*')->where('action_type', '=', 'uploaded')
                ->where('item_type', '=', self::$map_object_type[$object_type])
                ->where('item_id', '=', $id)->whereIn('filename', $files)
                ->get();

            return redirect()->back()->withFragment('files')->with('success', trans_choice('general.file_upload_status.upload.success', count($files)));
        }

        // No files were submitted
        return redirect()->back()->withFragment('files')->with('error', trans('general.file_upload_status.nofiles'));
    }

    /**
     * Check for permissions and display the file.
     * This isn't currently used, but is here for future use.
     *
     * @param  UploadFileRequest  $request
     * @param  string  $object_type  the type of object to upload the file to
     * @param  int  $id  the ID of the object to delete from so we can check permisisons
     * @param  $file_id  the ID of the file to show from the action_logs table
     *
     * @since  [v8.2.2]
     *
     * @author [A. Gianotto <snipe@snipe.net>]
     */
    public function show($object_type, $id, $file_id): RedirectResponse|StreamedResponse|Storage|StorageHelper|BinaryFileResponse
    {
        // Check the permissions to make sure the user can view the object
        $object = self::$map_object_type[$object_type]::withTrashed()->find($id);
        $this->authorize('viewFiles', $object);

        if (! $object) {
            return redirect()->back()->withFragment('files')->with('error', trans('general.file_upload_status.invalid_object'));
        }

        // Check that the file being requested exists for the object
        if (! $log = Actionlog::whereNotNull('filename')->where('item_type', self::$map_object_type[$object_type])->where('item_id', $object->id)->find($file_id)) {
            return redirect()->back()->withFragment('files')->with('error', trans('general.file_upload_status.invalid_id'));
        }

        if (! Storage::exists(self::$map_storage_path[$object_type].$log->filename)) {
            return redirect()->back()->withFragment('files')->with('error', trans('general.file_upload_status.file_not_found'));
        }

        if (FileIntegrity::uploadDeletionRecorded($log)) {
            return redirect()->back()->withFragment('files')->with('error', trans('general.file_upload_status.file_deleted'));
        }

        $verification = FileIntegrity::verificationForLog($log);
        if (($verification['verified'] ?? null) === false && ($verification['status'] ?? null) !== 'not_recorded') {
            return redirect()->back()->withFragment('files')->with('error', trans('general.file_upload_status.integrity_failed'));
        }

        if (request('inline') == 'true') {
            $headers = [
                'Content-Disposition' => 'inline',
            ];

            return Storage::download(self::$map_storage_path[$object_type].$log->filename, $log->filename, $headers);
        }

        return StorageHelper::downloader(self::$map_storage_path[$object_type].$log->filename);

    }

    /**
     * Delete the associated file
     *
     * @param  UploadFileRequest  $request
     * @param  string  $object_type  the type of object to upload the file to
     * @param  int  $id  the ID of the object to delete from so we can check permisisons
     * @param  $file_id  the ID of the file to delete from the action_logs table
     *
     * @since  [v8.2.2]
     *
     * @author [A. Gianotto <snipe@snipe.net>]
     */
    public function destroy($object_type, $id, $file_id): RedirectResponse
    {

        // Check the permissions to make sure the user can view the object
        $object = self::$map_object_type[$object_type]::withTrashed()->find($id);
        $this->authorize('files', $object);

        if (! $object) {
            return redirect()->back()->withFragment('files')->with('error', trans('general.file_upload_status.invalid_object'));
        }

        // Check for the file
        $log = Actionlog::where('id', $file_id)->where('item_type', self::$map_object_type[$object_type])
            ->where('item_id', $object->id)->first();

        if ($log) {
            $metadata = FileIntegrity::metadataForStoredFile(self::$map_storage_path[$object_type].$log->filename);
            $metadata['integrity']['delete_recorded_at'] = now()->toIso8601String();
            $metadata['integrity']['delete_mode'] = $object_type === 'documents' ? 'tombstone_preserve_file' : 'delete_file';

            // Check the file actually exists, and delete it
            if ($object_type !== 'documents' && Storage::exists(self::$map_storage_path[$object_type].$log->filename)) {
                Storage::delete(self::$map_storage_path[$object_type].$log->filename);
            }
            // Delete the record of the file
            if ($log->logUploadDelete($object, $log->filename, $metadata)) {
                return redirect()->back()->withFragment('files')->with('success', trans_choice('general.file_upload_status.delete.success', 1));
            }

        }

        // The file doesn't seem to really exist, so report an error
        return redirect()->back()->withFragment('files')->with('error', trans_choice('general.file_upload_status.delete.error', 1));

    }

    /**
     * Update the note of an already uploaded file. The note is metadata only,
     * so the file content (and its integrity checksum) is left untouched.
     */
    public function update(Request $request, $object_type, $id, $file_id): RedirectResponse
    {
        $object = self::$map_object_type[$object_type]::withTrashed()->find($id);
        $this->authorize('files', $object);

        if (! $object) {
            return redirect()->back()->withFragment('files')->with('error', trans('general.file_upload_status.invalid_object'));
        }

        $request->validate(['notes' => 'nullable|string|max:65535']);

        $log = Actionlog::whereNotNull('filename')
            ->where('item_type', self::$map_object_type[$object_type])
            ->where('item_id', $object->id)
            ->find($file_id);

        if (! $log) {
            return redirect()->back()->withFragment('files')->with('error', trans('general.file_upload_status.file_not_found'));
        }

        $log->note = $request->input('notes');
        $log->save();

        return redirect()->back()->withFragment('files')->with('success', trans('general.file_upload_status.update.success'));
    }

    /**
     * Move an attachment from the contract's customer onto the contract itself.
     * The physical file is moved between storage dirs and the Actionlog is
     * re-parented; integrity stays verifiable because verificationForLog()
     * derives the path from item_type and the file content is unchanged.
     */
    public function moveToContract(Request $request, CustomerContract $contract): RedirectResponse
    {
        $this->authorize('files', $contract);

        $customer = $contract->customer;

        if (! $customer) {
            return redirect()->route('contracts.show', $contract)->withFragment('files')
                ->with('error', trans('general.file_upload_status.invalid_object'));
        }

        $this->authorize('files', $customer);

        $request->validate(['file_id' => 'required|integer']);

        $log = Actionlog::whereNotNull('filename')
            ->where('action_type', 'uploaded')
            ->where('item_type', self::$map_object_type['customers'])
            ->where('item_id', $customer->id)
            ->find($request->integer('file_id'));

        if (! $log || FileIntegrity::uploadDeletionRecorded($log)) {
            return redirect()->route('contracts.show', $contract)->withFragment('files')
                ->with('error', trans('general.file_upload_status.file_not_found'));
        }

        $from = self::$map_storage_path['customers'].$log->filename;
        $to = self::$map_storage_path['contracts'].$log->filename;

        if (! Storage::exists($from)) {
            return redirect()->route('contracts.show', $contract)->withFragment('files')
                ->with('error', trans('general.file_upload_status.file_not_found'));
        }

        if (! Storage::exists(self::$map_storage_path['contracts'])) {
            Storage::makeDirectory(self::$map_storage_path['contracts'], 775);
        }

        Storage::move($from, $to);

        $log->item_type = self::$map_object_type['contracts'];
        $log->item_id = $contract->id;
        // Re-anchor the integrity record to the new location/owner.
        $log->log_meta = json_encode(FileIntegrity::withAuditChain(FileIntegrity::metadataForStoredFile($to), $log));
        $log->save();

        return redirect()->route('contracts.show', $contract)->withFragment('files')
            ->with('success', trans('admin/contracts/general.file_move_success'));
    }

    public function downloadImport(Import $import)
    {

        $this->authorize('import');

        if ($import = Import::find($import->id)) {

            if ((auth()->user()->id != $import->created_by) && (! auth()->user()->isSuperAdmin())) {
                return redirect()->back()->with('error', trans('general.file_upload_status.file_not_found'));
            }

            if (config('filesystems.default') == 's3_private') {
                return redirect()->away(Storage::disk('s3_private')->temporaryUrl('private_uploads/imports/'.$import->file_path, now()->addMinutes(5)));
            }

            if (Storage::exists('private_uploads/imports/'.$import->file_path)) {
                return response()->download(config('app.private_uploads').'/imports/'.$import->file_path);
            }

        }

        return redirect()->back()->with('error', trans('general.file_upload_status.file_not_found'));

    }
}
