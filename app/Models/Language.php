<?php

namespace App\Models;

use App\Enums\TextDirection;
use Database\Factories\LanguageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Language extends Model
{
    /** @use HasFactory<LanguageFactory> */
    use HasFactory;

    protected $fillable = [
        'iso',
        'name_en',
        'name_native',
        'font',
        'direction',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'direction' => TextDirection::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name_en');
    }

    public function isRtl(): bool
    {
        return $this->direction === TextDirection::Rtl;
    }

    public function label(): string
    {
        if ($this->name_en === $this->name_native) {
            return $this->name_en;
        }

        return $this->name_en.' ('.$this->name_native.')';
    }
}
