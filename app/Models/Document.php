<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'document_type_id',
        'visibility',
        'price',
        'template_name',
    ];

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function documentFields()
    {
        return $this->hasMany(DocumentField::class);
    }
}