<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ingredient extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', 'key', 'stock_level', 'min_stock_level', 'low_stock_notified',
    ];

    protected function casts(): array
    {
        return [
            'low_stock_notified' => 'boolean',
        ];
    }

    /**
     * Get the products for the ingredient.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_ingredients')
            ->using(ProductIngredient::class)
            ->withPivot('amount')
            ->withTimestamps();
    }
}
