<?php

    namespace App\Http\Controllers;

    use App\Http\Resources\ManufacturerResource;
    use App\Models\Manufacturer;
    use App\Models\ManufacturerSalesView;
    use App\Models\MonthlyManufacturerReport;
    use App\Models\WeeklyManufacturerReport;
    use App\Services\ReportService;
    use Carbon\Carbon;
    use Illuminate\Http\Request;

    class ManufacturerController extends Controller
    {

        protected $reportService;

        public function __construct(ReportService $reportService)
        {
            $this->reportService = $reportService;
        }

        public function index(Request $request)
        {
            $validRelations = $this->extractValidRelations(Manufacturer::class, $request);
            $manufacturers = Manufacturer::with($validRelations)->get();

            return $this->success(ManufacturerResource::collection($manufacturers), 'تم جلب الشركات بنجاح');
        }

        public function show(Request $request, $id)
        {
            $validRelations = $this->extractValidRelations(Manufacturer::class, $request);

            $manufacturer = Manufacturer::with(array_merge($validRelations, ['drugs', 'suppliers']))
                ->withCount(['drugs', 'suppliers']) // <-- تم إضافة هذا السطر
                ->findOrFail($id);

            return $this->success(new ManufacturerResource($manufacturer), 'تم جلب الشركة بنجاح');
        }


        public function store(Request $request)
        {

            $request->validate([
                'name' => 'required|string|max:255',
                'country' => 'nullable|string|max:255',
                'website' => 'nullable|url',
                'translations' => 'nullable|array',
                'translations.*.locale' => 'required|string|in:en,ar,fr',
                'translations.*.name' => 'required|string|max:255',
                'translations.*.country' => 'nullable|string|max:255',
            ]);


            $manufacturer = Manufacturer::create([
                'name' => $request->name,
                'country' => $request->country,
                'website' => $request->website,
            ]);

            if ($request->has('translations')) {
                foreach ($request->translations as $translation) {
                    $manufacturer->translations()->create([
                        'locale' => $translation['locale'],
                        'field' => 'name',
                        'value' => $translation['name'],
                    ]);
                    // تخزين ترجمة البلد (country)
                    $manufacturer->translations()->create([
                        'locale' => $translation['locale'],
                        'field' => 'country',
                        'value' => $translation['country'] ?? '',
                    ]);
                }
            }

            return $this->success(new ManufacturerResource($manufacturer), 'تم إنشاء الشركة بنجاح', 201);
        }


        public function update(Request $request, $id)
        {
            // التحقق من صحة المدخلات
            $request->validate([
                'name' => 'required|string|max:255',
                'country' => 'nullable|string|max:255',
                'website' => 'nullable|url',
                'translations' => 'required|array',
                'translations.*.locale' => 'required|string|in:en,ar,fr',
                'translations.*.name' => 'required|string|max:255',
                'translations.*.country' => 'nullable|string|max:255',
            ]);

            $manufacturer = Manufacturer::findOrFail($id);

            $manufacturer->update([
                'name' => $request->name,
                'country' => $request->country,
                'website' => $request->website,
            ]);

            if ($request->has('translations')) {
                foreach ($request->translations as $translation) {
                    $manufacturer->translations()->updateOrCreate(
                        ['locale' => $translation['locale'], 'field' => 'name'],
                        ['value' => $translation['name']]
                    );
                    $manufacturer->translations()->updateOrCreate(
                        ['locale' => $translation['locale'], 'field' => 'country'],
                        ['value' => $translation['country'] ?? '']
                    );
                }
            }

            return $this->success(new ManufacturerResource($manufacturer), 'تم تحديث الشركة بنجاح');
        }


        public function destroy($id)
        {
            $manufacturer = Manufacturer::findOrFail($id);
            $manufacturer->delete();

            return $this->success([], 'تم حذف الشركة بنجاح');
        }

        public function manufacturerSalesReport(Request $request)
        {
            $fromDate = $request->input('fromDate');
            $toDate = $request->input('toDate');
            $manufacturerId = $request->input('manufacturer_id');

            $query = ManufacturerSalesView::query();

            if ($fromDate && $toDate) {
                $query->whereBetween('sale_date', [$fromDate, $toDate]);
            }

            if ($manufacturerId) {
                $query->where('manufacturer_id', $manufacturerId);
            }

            $results = $query->get();

            return response()->json([
                'message' => 'Sales data fetched successfully',
                'data' => $results,
            ]);
        }

        public function updateReports(Request $request,ReportService $reportService)
        {
            $reportService->updateReports();

            return response()->json([
                'message' => 'Manufacturer reports updated successfully.'
            ]);
        }

        public function weeklyReport(Request $request)
        {
            $request->validate([
                'manufacturer_id' => 'required|integer|exists:manufacturers,id',
                'year' => 'required|integer',
                'month' => 'required|integer|min:1|max:12',
                'week_in_month' => 'required|integer|min:1|max:5',
            ]);

            $year = $request->year;
            $month = $request->month;
            $weekInMonth = $request->week_in_month;

            $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();

            $startOfWeek = $startOfMonth->copy()
                ->addWeeks($weekInMonth - 1)
                ->startOfWeek(Carbon::SATURDAY);

            $weekNumber = $startOfWeek->weekOfYear;

            $report = WeeklyManufacturerReport::where('manufacturer_id', $request->manufacturer_id)
                ->where('year', $year)
                ->where('week', $weekNumber)
                ->first();

            if (!$report) {
                return response()->json([
                    'message' => 'No report found for the given parameters.',
                ], 404);
            }

            return response()->json([
                'week_number' => $weekNumber,
                'report' => $report,
            ]);
        }



        public function monthlyReport(Request $request)
        {
            $request->validate([
                'manufacturer_id' => 'required|integer|exists:manufacturers,id',
                'year' => 'required|integer',
                'month' => 'required|integer|min:1|max:12',
            ]);

            $report = MonthlyManufacturerReport::where('manufacturer_id', $request->manufacturer_id)
                ->where('year', $request->year)
                ->where('month', $request->month)
                ->first();

            if (!$report) {
                return response()->json([
                    'message' => 'No report found for the given parameters.',
                ], 404);
            }

            return response()->json($report);
        }


    }
