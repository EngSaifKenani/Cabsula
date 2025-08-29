<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Supplier extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'contact_person',
        'note',
        'address',
        'account_balance',
        'is_active',
        'tax_number',
        'commercial_register',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the purchase invoices for the supplier.
     */
    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function manufacturers()
    {
        return $this->belongsToMany(Manufacturer::class, 'manufacturer_supplier');
    }

    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(Payment::class, PurchaseInvoice::class);
    }
    public function supplierReturns(): HasMany
    {
        return $this->hasMany(SupplierReturn::class);
    }

}
