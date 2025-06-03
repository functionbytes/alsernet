<?php

namespace App\Policies;

use App\Models\PdfDocument;
use App\Models\User;

class PdfDocumentPolicy
{
    public function view(User $user, PdfDocument $pdfDocument): bool
    {
        return $user->id === $pdfDocument->generated_by;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, PdfDocument $pdfDocument): bool
    {
        return $user->id === $pdfDocument->generated_by;
    }

    public function delete(User $user, PdfDocument $pdfDocument): bool
    {
        return $user->id === $pdfDocument->generated_by;
    }
}
