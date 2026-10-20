<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DietTemplateItem extends Model
{
    protected $fillable = ['diet_template_id', 'item_type', 'item_name', 'quantity', 'notes', 'sort_order'];

    public function dietTemplate(): BelongsTo
    {
        return $this->belongsTo(DietTemplate::class);
    }
}
