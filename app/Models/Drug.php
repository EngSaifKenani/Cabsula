<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class   Drug extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    protected $translatable = ['name', 'description', 'status', 'admin_notes'];
    // protected $with = ['translations'];
    protected $fillable = [
        'name',
        'total_sold',
        'description',
        'status',
        'admin_notes',
        'image',
        'form_id',
        'manufacturer_id',
    ];
    protected $dates = [
        'production_date',
        'expiry_date',
        'deleted_at'
    ];
    protected $casts = [
        'production_date' => 'date',
        'expiry_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }


    public function latestBatch()
    {
        return $this->hasOne(Batch::class)
            ->where('stock', '>', 0)
            ->where('expiry_date', '>', now())
            ->orderBy('expiry_date', 'asc')
            ->where('status', 'active');
    }

    public function validBatches(){
        return $this->hasMany(Batch::class)
            ->where('stock', '>', 0)
            ->where('expiry_date', '>', now())
            ->orderBy('expiry_date', 'asc')
            ->where('status', 'active');
    }



    public function availableQuantity()
    {
        return $this->batches()
            ->where('expiration_date', '>', now())
            ->sum('quantity');
    }





    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function recommendedDosage()
    {
        return $this->belongsTo(RecommendedDosage::class, 'recommended_dosage_id');
    }


    public function manufacturer()
    {
        return $this->belongsTo(Manufacturer::class);
    }


    public function activeIngredients()
    {
        return $this->belongsToMany(ActiveIngredient::class, 'drug_ingredients')
            ->withPivot('concentration', 'unit')
            ->withTimestamps();
    }

    public function getIngredientsListAttribute()
    {
        return $this->activeIngredients->map(function ($ingredient) {
            return $ingredient->scientific_name . ' ' .
                $ingredient->pivot->concentration->formatted;
        })->implode(', ');
    }

    public function getConcentrationsAttribute()
    {
        return $this->activeIngredients->map(function ($ingredient) {
            return $ingredient->pivot->concentration;
        })->unique('id');
    }


    public function alternativeDrugs()
    {
        // الحصول على قائمة المواد الفعالة وتركيزاتها لهذا الدواء
        $ingredients = $this->activeIngredients()
            ->withPivot('concentration_id')
            ->get()
            ->map(function ($ingredient) {
                return [
                    'active_ingredient_id' => $ingredient->id,
                    'concentration_id' => $ingredient->pivot->concentration_id,
                ];
            })
            ->toArray();

        // البحث عن أدوية أخرى تملك نفس المواد الفعالة والتركيزات
        return Drug::where('id', '!=', $this->id)
            ->whereHas('activeIngredients', function ($query) use ($ingredients) {
                foreach ($ingredients as $ingredient) {
                    $query->whereHas('drugs', function ($subQuery) use ($ingredient) {
                        $subQuery->wherePivot('active_ingredient_id', $ingredient['active_ingredient_id'])
                            ->wherePivot('concentration_id', $ingredient['concentration_id']);
                    });
                }
            })
            ->get();
    }

    public function scopeFilter(Builder $query, array $filters)
    {
        return $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhereHas('activeIngredients', function ($query) use ($search) {
                        $query->where('scientific_name', 'like', '%' . $search . '%')
                            ->orWhere('trade_name', 'like', '%' . $search . '%');
                    });
            });
        })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['form_id'] ?? null, function ($query, $formId) {
                $query->where('form_id', $formId);
            })
            ->when($filters['manufacturer_id'] ?? null, function ($query, $manufacturerId) {
                $query->where('manufacturer_id', $manufacturerId);
            })
            ->when($filters['ingredient_id'] ?? null, function ($query, $ingredientId) {
                $query->whereHas('activeIngredients', function ($query) use ($ingredientId) {
                    $query->where('active_ingredients.id', $ingredientId);
                });
            })
            ->when($filters['min_price'] ?? null, function ($query, $minPrice) {
                $query->where('price', '>=', $minPrice);
            })
            ->when($filters['max_price'] ?? null, function ($query, $maxPrice) {
                $query->where('price', '<=', $maxPrice);
            })
            ->when($filters['is_requires_prescription'] ?? null, function ($query, $isRequiresPrescription) {
                $query->where('is_requires_prescription', filter_var($isRequiresPrescription, FILTER_VALIDATE_BOOLEAN));
            })
            ->when($filters['expiry_date_from'] ?? null, function ($query, $expiryDateFrom) {
                $query->where('expiry_date', '>=', $expiryDateFrom);
            })
            ->when($filters['expiry_date_to'] ?? null, function ($query, $expiryDateTo) {
                $query->where('expiry_date', '<=', $expiryDateTo);
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<=', now());
    }

    public function expire()
    {
        return $this->expiry_date <= now();
    }

    public function getTotalStockAttribute()
    {
        return $this->batches()->sum('stock');
    }


}
