<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentField;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentService
{
    public function getAllDocuments()
    {
        return Document::with(['documentType', 'documentFields'])->get();
    }

    public function getDocumentById(int $id)
    {
        return Document::with(['documentType', 'documentFields'])->findOrFail($id);
    }

    public function createDocument(array $data)
    {
        $templateFile = $data['template_file'];
        $templateName = (string) Str::uuid() . '.html';
        Storage::disk('local')->put('templates/' . $templateName, file_get_contents($templateFile->getRealPath()));

        $document = Document::create([
            'name' => $data['name'],
            'document_type_id' => $data['document_type_id'],
            'visibility' => $data['visibility'] ?? 'private',
            'price' => $data['price'] ?? 0.00,
            'template_name' => $templateName,
        ]);

        if (isset($data['fields'])) {
            foreach ($data['fields'] as $field) {
                $document->documentFields()->create($field);
            }
        }

        return $document->load(['documentType', 'documentFields']);
    }

    public function updateDocument(int $id, array $data)
    {
        $document = Document::findOrFail($id);

        if (isset($data['template_file'])) {
            // Delete old template if exists
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
            $document->documentFields()->delete(); // Simple approach: delete all and re-create
            foreach ($data['fields'] as $field) {
                $document->documentFields()->create($field);
            }
        }

        return $document->load(['documentType', 'documentFields']);
    }

    public function deleteDocument(int $id)
    {
        $document = Document::findOrFail($id);
        if ($document->template_name) {
            Storage::disk('local')->delete('templates/' . $document->template_name);
        }
        $document->delete();
        return true;
    }

    public function getDocumentFields(int $documentId)
    {
        return DocumentField::where('document_id', $documentId)->get();
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
        $field->delete();
        return true;
    }
}