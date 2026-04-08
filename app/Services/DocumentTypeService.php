<?php

namespace App\Services;

use App\Models\DocumentType;
use Illuminate\Support\Str;

class DocumentTypeService
{
    public function getAllDocumentTypes()
    {
        return DocumentType::all();
    }

    public function getDocumentTypeById(int $id)
    {
        return DocumentType::findOrFail($id);
    }

    public function createDocumentType(array $data)
    {
        $data["slug"] = Str::slug($data["name"]);
        return DocumentType::create($data);
    }

    public function updateDocumentType(int $id, array $data)
    {
        $documentType = DocumentType::findOrFail($id);
        if (isset($data["name"])) {
            $data["slug"] = Str::slug($data["name"]);
        }
        $documentType->update($data);
        return $documentType;
    }

    public function deleteDocumentType(int $id)
    {
        $documentType = DocumentType::findOrFail($id);
        $documentType->delete();
        return true;
    }
}