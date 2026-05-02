<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class UserDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'document_id',
        'generated_pdf_path',
        'input_data',
    ];

    protected $casts = [
        'input_data' => 'array',
    ];

    /**
     * Accessor: تحويل مسار الملف إلى رابط كامل عند جلب البيانات
     * سيحول "pdfs/file.pdf" إلى "http://domain.com/storage/pdfs/file.pdf"
     */
    public function getGeneratedPdfPathAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // التحقق مما إذا كان المسار مخزناً كـ URL كامل أصلاً (لتجنب التكرار)
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        return asset(Storage::url($value));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}