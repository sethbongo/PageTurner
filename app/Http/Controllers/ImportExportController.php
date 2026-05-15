<?php

namespace App\Http\Controllers;

use App\Exports\BooksExport;
use App\Exports\BooksTemplateExport;
use App\Exports\ImportFailuresExport;
use App\Exports\OrdersExport;
use App\Exports\RevenueSummaryExport;
use App\Exports\UsersExport;
use App\Exports\UsersTemplateExport;
use App\Imports\BooksImport;
use App\Imports\UsersImport;
use App\Models\Book;
use App\Models\Category;
use App\Models\ImportExportLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use Barryvdh\DomPDF\Facade\Pdf;

class ImportExportController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('name')->get();
        $logs = ImportExportLog::latest()->take(25)->get();
        $customers = User::where('role', 'customer')->orderBy('first_name')->get();

        return view('admin.import-export', compact('categories', 'logs', 'customers'));
    }

    public function downloadBookTemplate()
    {
        return Excel::download(new BooksTemplateExport(), 'book-import-template.xlsx');
    }

    public function downloadUserTemplate()
    {
        return Excel::download(new UsersTemplateExport(), 'user-import-template.xlsx');
    }

    public function downloadImportFailures(ImportExportLog $log)
    {
        return Excel::download(new ImportFailuresExport($log->id), 'import-failures-' . $log->id . '.csv');
    }

    public function downloadExportFile(ImportExportLog $log)
    {
        if (!$log->stored_path || !Storage::disk('local')->exists($log->stored_path)) {
            return redirect()->back()->withErrors(['error' => 'Export file is not available.']);
        }

        return Storage::disk('local')->download($log->stored_path, basename($log->stored_path));
    }

    public function importBooks(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt'],
            'duplicate_mode' => ['required', Rule::in(['skip', 'update'])],
        ]);

        $file = $validated['file'];
        $storedPath = $file->store('imports/books');

        $log = ImportExportLog::create([
            'user_id' => auth()->id(),
            'module' => 'book',
            'type' => 'import',
            'status' => 'processing',
            'format' => $file->getClientOriginalExtension(),
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'duplicate_mode' => $validated['duplicate_mode'],
            'started_at' => now(),
        ]);

        if (!$this->validateHeadings($storedPath, ['isbn', 'title', 'author', 'price', 'stock', 'category', 'description'])) {
            $log->update([
                'status' => 'failed',
                'error_summary' => 'Missing required headers in import file.',
                'finished_at' => now(),
            ]);

            return redirect()->back()->withErrors(['error' => 'Missing required headers in the import file.']);
        }

        $import = new BooksImport($log->id, $validated['duplicate_mode']);

        Excel::queueImport($import, $storedPath, 'local');

        return redirect()->back()->with('success', 'Book import queued successfully.');
    }

    public function exportBooks(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'min:0'],
            'stock_status' => ['nullable', Rule::in(['in_stock', 'out_of_stock', 'low_stock'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'format' => ['required', Rule::in(['xlsx', 'csv', 'pdf'])],
            'columns' => ['nullable', 'array'],
        ]);

        $columns = $this->sanitizeBookColumns($validated['columns'] ?? []);
        $filters = $this->buildBookFilters($validated);

        $log = ImportExportLog::create([
            'user_id' => auth()->id(),
            'module' => 'book',
            'type' => 'export',
            'status' => 'processing',
            'format' => $validated['format'],
            'filters' => $filters,
            'columns' => $columns,
            'started_at' => now(),
        ]);

        if ($validated['format'] === 'pdf') {
            $books = $this->buildBookQuery($filters)->get();
            $pdf = Pdf::loadView('pdf.books-export', compact('books', 'columns'));
            $log->update([
                'status' => 'completed',
                'finished_at' => now(),
            ]);

            return $pdf->download('books-export.pdf');
        }

        $export = new BooksExport($filters, $columns);
        $count = $this->buildBookQuery($filters)->count();

        if ($count > 10000) {
            $filename = 'exports/books-export-' . now()->format('Ymd_His') . '.' . $validated['format'];
            Excel::queue($export, $filename, 'local');

            $log->update([
                'status' => 'completed',
                'stored_path' => $filename,
                'total_rows' => $count,
                'processed_rows' => $count,
                'finished_at' => now(),
            ]);

            return redirect()->back()->with('success', 'Export queued. Download it from the logs when ready.');
        }

        $log->update([
            'status' => 'completed',
            'total_rows' => $count,
            'processed_rows' => $count,
            'finished_at' => now(),
        ]);

        return Excel::download($export, 'books-export.' . $validated['format']);
    }

    public function exportOrders(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string'],
            'customer_id' => ['nullable', 'exists:users,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'format' => ['required', Rule::in(['xlsx', 'csv', 'pdf'])],
        ]);

        $filters = [
            'status' => $validated['status'] ?? null,
            'customer_id' => $validated['customer_id'] ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
        ];

        $log = ImportExportLog::create([
            'user_id' => auth()->id(),
            'module' => 'order',
            'type' => 'export',
            'status' => 'processing',
            'format' => $validated['format'],
            'filters' => $filters,
            'started_at' => now(),
        ]);

        if ($validated['format'] === 'pdf') {
            $orders = $this->buildOrderQuery($filters)->get();
            $pdf = Pdf::loadView('pdf.orders-export', compact('orders'));
            $log->update([
                'status' => 'completed',
                'finished_at' => now(),
            ]);

            return $pdf->download('orders-export.pdf');
        }

        $export = new OrdersExport($filters);
        $count = $this->buildOrderQuery($filters)->count();

        if ($count > 10000) {
            $filename = 'exports/orders-export-' . now()->format('Ymd_His') . '.' . $validated['format'];
            Excel::queue($export, $filename, 'local');

            $log->update([
                'status' => 'completed',
                'stored_path' => $filename,
                'total_rows' => $count,
                'processed_rows' => $count,
                'finished_at' => now(),
            ]);

            return redirect()->back()->with('success', 'Export queued. Download it from the logs when ready.');
        }

        $log->update([
            'status' => 'completed',
            'total_rows' => $count,
            'processed_rows' => $count,
            'finished_at' => now(),
        ]);

        return Excel::download($export, 'orders-export.' . $validated['format']);
    }

    public function exportFinancialReport(Request $request)
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'format' => ['required', Rule::in(['xlsx', 'csv', 'pdf'])],
        ]);

        $filters = [
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
        ];

        $log = ImportExportLog::create([
            'user_id' => auth()->id(),
            'module' => 'financial',
            'type' => 'export',
            'status' => 'processing',
            'format' => $validated['format'],
            'filters' => $filters,
            'started_at' => now(),
        ]);

        if ($validated['format'] === 'pdf') {
            $rows = (new RevenueSummaryExport($filters))->collection();
            $taxRate = config('reports.tax_rate', 0);
            $pdf = Pdf::loadView('pdf.financial-report', compact('rows', 'taxRate'));

            $log->update([
                'status' => 'completed',
                'finished_at' => now(),
            ]);

            return $pdf->download('financial-report.pdf');
        }

        $export = new RevenueSummaryExport($filters);
        $filename = 'financial-report.' . $validated['format'];

        $log->update([
            'status' => 'completed',
            'finished_at' => now(),
        ]);

        return Excel::download($export, $filename);
    }

    public function importUsers(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt'],
            'default_role' => ['required', Rule::in(['customer', 'admin'])],
        ]);

        $file = $validated['file'];
        $storedPath = $file->store('imports/users');

        $log = ImportExportLog::create([
            'user_id' => auth()->id(),
            'module' => 'user',
            'type' => 'import',
            'status' => 'processing',
            'format' => $file->getClientOriginalExtension(),
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'started_at' => now(),
        ]);

        if (!$this->validateHeadings($storedPath, ['first_name', 'middle_name', 'last_name', 'suffix', 'email', 'role', 'password'])) {
            $log->update([
                'status' => 'failed',
                'error_summary' => 'Missing required headers in import file.',
                'finished_at' => now(),
            ]);

            return redirect()->back()->withErrors(['error' => 'Missing required headers in the import file.']);
        }

        $import = new UsersImport($log->id, $validated['default_role']);

        Excel::queueImport($import, $storedPath, 'local');

        return redirect()->back()->with('success', 'User import queued successfully.');
    }

    public function exportUsers(Request $request)
    {
        $validated = $request->validate([
            'format' => ['required', Rule::in(['xlsx', 'csv', 'pdf'])],
            'redact' => ['nullable', 'boolean'],
        ]);

        $redact = (bool) ($validated['redact'] ?? false);

        $log = ImportExportLog::create([
            'user_id' => auth()->id(),
            'module' => 'user',
            'type' => 'export',
            'status' => 'processing',
            'format' => $validated['format'],
            'filters' => ['redact' => $redact],
            'started_at' => now(),
        ]);

        if ($validated['format'] === 'pdf') {
            $users = User::query()->orderBy('id')->get();
            $pdf = Pdf::loadView('pdf.users-export', compact('users', 'redact'));
            $log->update([
                'status' => 'completed',
                'finished_at' => now(),
            ]);

            return $pdf->download('users-export.pdf');
        }

        $export = new UsersExport($redact);
        $log->update([
            'status' => 'completed',
            'finished_at' => now(),
        ]);

        return Excel::download($export, 'users-export.' . $validated['format']);
    }

    private function validateHeadings(string $storedPath, array $required): bool
    {
        $headings = (new HeadingRowImport())->toArray(Storage::disk('local')->path($storedPath));
        $firstSheet = $headings[0][0] ?? [];

        $normalizedHeadings = array_map(fn($value) => Str::slug((string) $value), $firstSheet);
        $normalizedRequired = array_map(fn($value) => Str::slug((string) $value), $required);

        $missing = array_diff($normalizedRequired, $normalizedHeadings);

        return count($missing) === 0;
    }

    private function sanitizeBookColumns(array $columns): array
    {
        $allowed = ['isbn', 'title', 'author', 'price', 'stock', 'category', 'description', 'created_at'];
        $selected = array_values(array_intersect($columns, $allowed));

        return $selected ?: $allowed;
    }

    private function buildBookFilters(array $validated): array
    {
        return [
            'category_id' => $validated['category_id'] ?? null,
            'price_min' => $validated['price_min'] ?? null,
            'price_max' => $validated['price_max'] ?? null,
            'stock_status' => $validated['stock_status'] ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
        ];
    }

    private function buildBookQuery(array $filters)
    {
        $query = Book::query()->with('category');

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['price_min'])) {
            $query->where('price', '>=', $filters['price_min']);
        }

        if (!empty($filters['price_max'])) {
            $query->where('price', '<=', $filters['price_max']);
        }

        if (!empty($filters['stock_status'])) {
            if ($filters['stock_status'] === 'in_stock') {
                $query->where('stock_quantity', '>', 0);
            }
            if ($filters['stock_status'] === 'out_of_stock') {
                $query->where('stock_quantity', '=', 0);
            }
            if ($filters['stock_status'] === 'low_stock') {
                $query->where('stock_quantity', '>', 0)->where('stock_quantity', '<=', 10);
            }
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('id');
    }

    private function buildOrderQuery(array $filters)
    {
        $query = Order::query()->with(['user', 'orderItems']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['customer_id'])) {
            $query->where('user_id', $filters['customer_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->where('status', '!=', 'Cart')->orderBy('id');
    }
}
