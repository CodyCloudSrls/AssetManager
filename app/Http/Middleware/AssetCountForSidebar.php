<?php

namespace App\Http\Middleware;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AssetCountForSidebar
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /**
         * This needs to be set for the /setup process, since the tables might not exist yet
         */
        $total_assets = 0;
        $total_due_for_checkin = 0;
        $total_overdue_for_checkin = 0;
        $total_due_for_audit = 0;
        $total_overdue_for_audit = 0;
        $total_documents = 0;
        $total_documents_active = 0;
        $total_documents_draft = 0;
        $total_documents_in_review = 0;
        $total_documents_obsolete = 0;
        $total_documents_archived = 0;
        $total_documents_deleted = 0;
        $total_documents_due_review = 0;
        $total_documents_overdue_review = 0;

        try {
            $settings = Setting::getSettings();
            view()->share('settings', $settings);
        } catch (\Exception $e) {
            Log::debug($e);
        }

        try {
            $total_assets = Asset::AssetsForShow()->count();
            view()->share('total_assets', $total_assets);
        } catch (\Exception $e) {
            Log::debug($e);
        }

        try {
            $total_rtd_sidebar = Asset::RTD()->count();
            view()->share('total_rtd_sidebar', $total_rtd_sidebar);
        } catch (\Exception $e) {
            Log::debug($e);
        }

        try {
            $total_deployed_sidebar = Asset::Deployed()->count();
            view()->share('total_deployed_sidebar', $total_deployed_sidebar);
        } catch (\Exception $e) {
            Log::debug($e);
        }

        try {
            $total_archived_sidebar = Asset::Archived()->count();
            view()->share('total_archived_sidebar', $total_archived_sidebar);
        } catch (\Exception $e) {
            Log::debug($e);
        }

        try {
            $total_pending_sidebar = Asset::Pending()->count();
            view()->share('total_pending_sidebar', $total_pending_sidebar);
        } catch (\Exception $e) {
            Log::debug($e);
        }

        try {
            $total_undeployable_sidebar = Asset::Undeployable()->count();
            view()->share('total_undeployable_sidebar', $total_undeployable_sidebar);
        } catch (\Exception $e) {
            Log::debug($e);
        }

        try {
            $total_byod_sidebar = Asset::where('byod', '=', '1')->count();
            view()->share('total_byod_sidebar', $total_byod_sidebar);
        } catch (\Exception $e) {
            Log::debug($e);
        }

        try {
            $total_due_for_audit = Asset::DueForAudit($settings)->count();
            view()->share('total_due_for_audit', $total_due_for_audit);
        } catch (\Exception $e) {
            Log::debug($e);
        }

        try {
            $total_overdue_for_audit = Asset::OverdueForAudit()->count();
            view()->share('total_overdue_for_audit', $total_overdue_for_audit);
        } catch (\Exception $e) {
            Log::debug($e);
        }

        try {
            $total_due_for_checkin = Asset::DueForCheckin($settings)->count();
            view()->share('total_due_for_checkin', $total_due_for_checkin);
        } catch (\Exception $e) {
            Log::debug($e);
        }

        try {
            $total_overdue_for_checkin = Asset::OverdueForCheckin()->count();
            view()->share('total_overdue_for_checkin', $total_overdue_for_checkin);
        } catch (\Exception $e) {
            Log::debug($e);
        }

        try {
            $total_documents = Document::count();
            view()->share('total_documents', $total_documents);
        } catch (\Exception $e) {
            Log::debug($e);
        }

        try {
            $total_documents_active = Document::Active()->count();
            view()->share('total_documents_active', $total_documents_active);
        } catch (\Exception $e) {
            Log::debug($e);
        }

        try {
            $total_documents_draft = Document::Draft()->count();
            view()->share('total_documents_draft', $total_documents_draft);
        } catch (\Exception $e) {
            Log::debug($e);
        }

        try {
            $total_documents_in_review = Document::InReview()->count();
            view()->share('total_documents_in_review', $total_documents_in_review);
        } catch (\Exception $e) {
            Log::debug($e);
        }

        try {
            $total_documents_obsolete = Document::Obsolete()->count();
            view()->share('total_documents_obsolete', $total_documents_obsolete);
        } catch (\Exception $e) {
            Log::debug($e);
        }

        try {
            $total_documents_archived = Document::Archived()->count();
            view()->share('total_documents_archived', $total_documents_archived);
        } catch (\Exception $e) {
            Log::debug($e);
        }

        try {
            $total_documents_deleted = Document::onlyTrashed()->count();
            view()->share('total_documents_deleted', $total_documents_deleted);
        } catch (\Exception $e) {
            Log::debug($e);
        }

        try {
            $total_documents_due_review = Document::DueForReview()->count();
            view()->share('total_documents_due_review', $total_documents_due_review);
        } catch (\Exception $e) {
            Log::debug($e);
        }

        try {
            $total_documents_overdue_review = Document::OverdueForReview()->count();
            view()->share('total_documents_overdue_review', $total_documents_overdue_review);
        } catch (\Exception $e) {
            Log::debug($e);
        }

        view()->share('total_due_and_overdue_for_checkin', ($total_due_for_checkin + $total_overdue_for_checkin));
        view()->share('total_due_and_overdue_for_audit', ($total_due_for_audit + $total_overdue_for_audit));

        return $next($request);
    }
}
