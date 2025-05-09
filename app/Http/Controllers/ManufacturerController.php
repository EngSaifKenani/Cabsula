<?php

    namespace App\Http\Controllers;

    use App\Http\Resources\ManufacturerResource;
    use App\Models\Manufacturer;
    use Illuminate\Http\Request;

    class ManufacturerController extends Controller
    {
        public function index(Request $request)
        {
            $validRelations = $this->extractValidRelations(Manufacturer::class, $request);
            $manufacturers = Manufacturer::with($validRelations)->get();

            return $this->success(ManufacturerResource::collection($manufacturers), 'تم جلب الشركات بنجاح');
        }

        public function show(Request $request, $id)
        {
            $validRelations = $this->extractValidRelations(Manufacturer::class, $request);
            $manufacturer = Manufacturer::with($validRelations)->findOrFail($id);

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
    }
