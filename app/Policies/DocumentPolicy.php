<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DocumentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('document.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Document $document): bool
    {
        return $user->hasPermissionTo('document.view') && $user->business_id === $document->business_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('document.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Document $document): bool
    {
        return $user->hasPermissionTo('document.edit') && $user->business_id === $document->business_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Document $document): bool
    {
        return $user->hasPermissionTo('document.delete') && $user->business_id === $document->business_id;
    }

    /**
     * Determine whether the user can sign the model.
     */
    public function sign(User $user, Document $document): bool
    {
        return $user->hasPermissionTo('document.sign') && $user->business_id === $document->business_id;
    }
}
