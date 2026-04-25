<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentField;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class DocumentService
{
    /**
     * جلب كافة الوثائق مع علاقاتها.
     */
    public function getAllDocuments()
    {
        return Document::with(['documentType', 'documentFields'])->get();
    }

    /**
     * جلب وثيقة محددة مع إدراج محتوى قالب الـ HTML للمعاينة.
     */
    public function getDocumentById(int $id)
    {
        // 1. جلب الوثيقة من قاعدة البيانات
        $document = Document::with(['documentType', 'documentFields'])->findOrFail($id);

        // 2. قراءة محتوى ملف الـ HTML من التخزين (Storage)
        $htmlContent = "";
        if ($document->template_name) {
            $path = 'templates/' . $document->template_name;
            if (Storage::disk('local')->exists($path)) {
                $htmlContent = Storage::disk('local')->get($path);
            }
        }

        // 3. إضافة المحتوى لكائن الوثيقة (سيظهر في الـ JSON تلقائياً)
        $document->raw_html = $htmlContent;

        return $document;
    }

    /**
     * إنشاء وثيقة جديدة مع الحقول والملف.
     */
    public function createDocument(array $data)
    {
        return DB::transaction(function () use ($data) {
            $templateName = null;

            if (isset($data['template_file'])) {
                $templateFile = $data['template_file'];
                $templateName = (string) Str::uuid() . '.html';
                // تخزين الملف في القرص المحلي
                Storage::disk('local')->put('templates/' . $templateName, file_get_contents($templateFile->getRealPath()));
            }

            $document = Document::create([
                'name'             => $data['name'],
                'document_type_id' => $data['document_type_id'],
                'visibility'       => $data['visibility'] ?? 'private',
                'price'            => $data['price'] ?? 0.00,
                'template_name'    => $templateName,
            ]);

            if (!empty($data['fields'])) {
                $document->documentFields()->createMany($data['fields']);
            }

            return $document->load(['documentType', 'documentFields']);
        });
    }

    /**
     * تحديث الوثيقة وإدارة الملفات القديمة.
     */
    public function updateDocument(int $id, array $data)
    {
        $document = Document::findOrFail($id);

        return DB::transaction(function () use ($document, $data) {
            if (isset($data['template_file'])) {
                // حذف الملف القديم في حال وجوده لتوفير المساحة
                if ($document->template_name) {
                    Storage::disk('local')->delete('templates/' . $document->template_name);
                }

                $templateFile = $data['template_file'];
                $templateName = (string) Str::uuid() . '.html';
                Storage::disk('local')->put('templates/' . $templateName, file_get_contents($templateFile->getRealPath()));
                $data['template_name'] = $templateName;
            }

            $document->update($data);

            if (isset($data['fields'])) {
                // تحديث الحقول عبر الحذف والإعادة (أو يمكنك استخدام upsert لمستوى متقدم)
                $document->documentFields()->delete();
                $document->documentFields()->createMany($data['fields']);
            }

            return $document->load(['documentType', 'documentFields']);
        });
    }

    /**
     * حذف الوثيقة وتنظيف الملفات المرتبطة.
     */
    public function deleteDocument(int $id)
    {
        $document = Document::findOrFail($id);

        return DB::transaction(function () use ($document) {
            if ($document->template_name) {
                Storage::disk('local')->delete('templates/' . $document->template_name);
            }
            return $document->delete();
        });
    }

    // --- إدارة حقول الوثيقة (Document Fields) ---

    public function getDocumentFields(int $documentId)
    {
        $document = Document::findOrFail($documentId);
        return $document->documentFields;
    }

    public function createDocumentField(int $documentId, array $data)
    {
        $document = Document::findOrFail($documentId);
        return $document->documentFields()->create($data);
    }

    public function updateDocumentField(int $fieldId, array $data)
    {
        $field = DocumentField::findOrFail($fieldId);
        $field->update($data);
        return $field;
    }

    public function deleteDocumentField(int $fieldId)
    {
        $field = DocumentField::findOrFail($fieldId);
        return $field->delete();
    }
}
