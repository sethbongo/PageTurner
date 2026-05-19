<?php

namespace App\Http\Controllers;

use App\Services\GdprDataExportService;
use App\Services\OrderExportService;
use App\Services\ReadingHistoryExportService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class UserDataPortabilityController extends Controller
{
    protected GdprDataExportService $gdprService;
    protected OrderExportService $orderService;
    protected ReadingHistoryExportService $readingHistoryService;

    public function __construct(
        GdprDataExportService $gdprService,
        OrderExportService $orderService,
        ReadingHistoryExportService $readingHistoryService
    ) {
        $this->gdprService = $gdprService;
        $this->orderService = $orderService;
        $this->readingHistoryService = $readingHistoryService;
        $this->middleware('auth');
    }

    /**
     * Show data portability dashboard
     */
    public function dashboard()
    {
        $user = Auth::user();

        $orderSummary = $this->orderService->getOrderSummary($user);

        return view('customer.data-portability.dashboard', compact(
            'user',
            'orderSummary'
        ));
    }

    /**
     * Export personal data as JSON
     */
    public function exportPersonalDataJson()
    {
        $user = Auth::user();
        $this->gdprService->logExportRequest($user, 'json');

        try {
            $path = $this->gdprService->exportPersonalDataAsJson($user);
            $filename = basename($path);

            return Storage::disk('private')->download($path, $filename);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to export personal data. Please try again.');
        }
    }

    /**
     * Export order history as PDF
     */
    public function exportOrderHistoryPdf()
    {
        $user = Auth::user();
        $this->orderService->logExportRequest($user, 'pdf');

        try {
            $path = $this->orderService->exportAsPdf($user);
            $filename = basename($path);

            return Storage::disk('private')->download($path, $filename);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to export order history. Please try again.');
        }
    }

    /**
     * Export order history as Excel
     */
    public function exportOrderHistoryExcel()
    {
        $user = Auth::user();
        $this->orderService->logExportRequest($user, 'excel');

        try {
            $path = $this->orderService->exportAsExcel($user);
            $filename = basename($path);

            return Storage::disk('private')->download($path, $filename);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to export order history. Please try again.');
        }
    }

    /**
     * Export reading history as JSON
     */
    public function exportReadingHistoryJson()
    {
        $user = Auth::user();
        $this->readingHistoryService->logExportRequest($user, 'json');

        try {
            $path = $this->readingHistoryService->exportAsJson($user);
            $filename = basename($path);

            return Storage::disk('private')->download($path, $filename);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to export reading history. Please try again.');
        }
    }

    /**
     * Export reading history as PDF
     */
    public function exportReadingHistoryPdf()
    {
        $user = Auth::user();
        $this->readingHistoryService->logExportRequest($user, 'pdf');

        try {
            $path = $this->readingHistoryService->exportAsPdf($user);
            $filename = basename($path);

            return Storage::disk('private')->download($path, $filename);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to export reading history. Please try again.');
        }
    }

    /**
     * Show available exports
     */
    public function availableExports()
    {
        $user = Auth::user();

        return view('customer.data-portability.available-exports', compact('user'));
    }

    /**
     * Request data deletion
     */
    public function requestDataDeletion(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'reason' => 'nullable|string|max:500',
            'confirm' => 'required|accepted',
        ]);

        // Log the deletion request
        activity()
            ->performedBy($user)
            ->withProperties([
                'reason' => $request->reason,
                'type' => 'data_deletion_request',
            ])
            ->log('user_requested_data_deletion');

        // Create a record for admin review (implement this as needed)
        // DataDeletionRequest::create([...])

        return back()->with('success', 'Your data deletion request has been submitted. We will process it according to GDPR regulations.');
    }
}
