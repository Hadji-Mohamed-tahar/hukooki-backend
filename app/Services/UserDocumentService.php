<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserDocumentService
{
    protected $pythonMicroserviceUrl;

    public function __construct()
    {
        $this->pythonMicroserviceUrl = config("services.python_pdf_generator.url");
    }

    public function getUserDocuments(User $user)
    {
        return $user->userDocuments()->with("document")->get();
    }

    public function getUserDocumentById(User $user, int $userDocumentId)
    {
        return $user->userDocuments()->with("document")->findOrFail($userDocumentId);
    }

    public function generatePdf(User $user, int $documentId, array $inputData)
    {
        $document = Document::findOrFail($documentId);

        // Prepare data for Python microservice
        $templateContent = Storage::disk("local")->get("templates/" . $document->template_name);

        // Send template and data to Python microservice
        $response = Http::post($this->pythonMicroserviceUrl . "/generate-pdf", [
            "template_name" => $document->template_name, // Or pass the content directly
            "data" => $inputData,
            "template_content" => $templateContent, // Sending content directly for simplicity
        ]);

        if ($response->failed()) {
            // Handle error from microservice
            throw new \Exception("Failed to generate PDF: " . $response->body());
        }

        $pdfContent = $response->body();

        // Store the generated PDF
        $pdfPath = "pdfs/" . (string) Str::uuid() . ".pdf";
        Storage::disk("public")->put($pdfPath, $pdfContent);

        // Save user document record
        $userDocument = UserDocument::create([
            "user_id" => $user->id,
            "document_id" => $document->id,
            "generated_pdf_path" => $pdfPath,
            "input_data" => $inputData,
        ]);

        return $userDocument->load("document");
    }

    public function getPdfPath(User $user, int $userDocumentId)
    {
        $userDocument = $user->userDocuments()->findOrFail($userDocumentId);

        if (!Storage::disk("public")->exists($userDocument->generated_pdf_path)) {
            throw new \Exception("PDF file not found.");
        }

        // إرجاع المسار الكامل للملف على القرص
        return storage_path('app/public/' . $userDocument->generated_pdf_path);
    }
}
