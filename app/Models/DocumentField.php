<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentField extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'name',
        'label',
        'type',
        'required',
        'placeholder',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}