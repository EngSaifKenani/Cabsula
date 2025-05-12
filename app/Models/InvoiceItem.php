<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = ['invoice_id', 'drug_id', 'quantity', 'cost', 'price', 'profit_amount'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function drug()
    {
        return $this->belongsTo(Drug::class);
    }
}
