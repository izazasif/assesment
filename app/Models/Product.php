<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'products';
    public function purchasedetail() 
    { 
        return $this->hasMany(PurchaseDetail::class); 
    } 
    public function saledetail() { 
        return $this->hasMany(SaleDetail::class);
    }
}
