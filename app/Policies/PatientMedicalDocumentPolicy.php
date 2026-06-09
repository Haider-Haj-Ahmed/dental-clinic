<?php

namespace App\Policies;

use App\Models\PatientMedicalDocument;
use App\Models\User;

class PatientMedicalDocumentPolicy
{
    // All staff can view documents
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            User::ROLE_OWNER,
            User::ROLE_RECEPTIONIST,
            User::ROLE_PROVIDER,
            User::ROLE_ASSISTANT,
        ]);
    }

    public function view(User $user, PatientMedicalDocument $document): bool
    {
        return $this->viewAny($user);
    }

    // Providers and assistants can upload; receptionists and owners too
    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            User::ROLE_OWNER,
            User::ROLE_RECEPTIONIST,
            User::ROLE_PROVIDER,
            User::ROLE_ASSISTANT,
        ]);
    }

    // Only the uploader, receptionists, and owners can update metadata
    public function update(User $user, PatientMedicalDocument $document): bool
    {
        if ($user->isOwner() || $user->isReceptionist()) {
            return true;
        }

        return $document->uploaded_by === $user->id;
    }

    // Only the uploader or owner can delete
    public function delete(User $user, PatientMedicalDocument $document): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        return $document->uploaded_by === $user->id;
    }
}
