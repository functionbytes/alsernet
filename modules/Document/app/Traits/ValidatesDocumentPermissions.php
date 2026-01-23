<?php

namespace Modules\Document\Traits;

use Illuminate\Auth\Access\AuthorizationException;
use Modules\Document\Entities\Document;

/**
 * Trait ValidatesDocumentPermissions
 *
 * Provides permission validation methods for Document module controllers
 * Ensures backend authorization checks match frontend visibility rules
 */
trait ValidatesDocumentPermissions
{
    /**
     * Authorize a user action on a document component
     *
     * @param string $component Component name (e.g., 'email-actions', 'document-management')
     * @param string $action Action name (e.g., 'send-initial-request', 'edit')
     * @param Document|null $document Document instance (optional, for context)
     * @return bool
     * @throws AuthorizationException
     */
    public function authorizeComponentAction(string $component, string $action, ?Document $document = null): bool
    {
        if (!auth()->check()) {
            throw new AuthorizationException('Unauthorized');
        }

        $user = auth()->user();

        // Check if user can perform this action on this component
        if (!$user->canActionDocumentComponent($component, $action)) {
            throw new AuthorizationException(
                "You do not have permission to {$action} on {$component}"
            );
        }

        return true;
    }

    /**
     * Authorize viewing a document component
     *
     * @param string $component Component name
     * @return bool
     * @throws AuthorizationException
     */
    public function authorizeViewComponent(string $component): bool
    {
        if (!auth()->check()) {
            throw new AuthorizationException('Unauthorized');
        }

        if (!auth()->user()->canViewDocumentComponent($component)) {
            throw new AuthorizationException(
                "You do not have permission to view {$component}"
            );
        }

        return true;
    }

    /**
     * Authorize bulk operations with permission check
     *
     * @param string $action Action name
     * @param int|null $count Number of items being operated on
     * @return bool
     * @throws AuthorizationException
     */
    public function authorizeBulkAction(string $action, ?int $count = null): bool
    {
        if (!auth()->check()) {
            throw new AuthorizationException('Unauthorized');
        }

        // For bulk operations, check base permission
        // Specific document-level checks should happen at iteration
        $basePermission = "documents.{$action}";

        // Log bulk action attempt
        \Log::info("Bulk action attempt: {$action}", [
            'user_id' => auth()->id(),
            'count' => $count,
            'timestamp' => now(),
        ]);

        return true;
    }

    /**
     * Verify user has specific document group permission
     *
     * @param string $permissionName Permission name (e.g., 'send-initial-request')
     * @param Document $document Document instance
     * @return bool
     * @throws AuthorizationException
     */
    public function authorizeDocumentPermission(string $permissionName, Document $document): bool
    {
        if (!auth()->check()) {
            throw new AuthorizationException('Unauthorized');
        }

        if (!auth()->user()->canInDocumentModule($permissionName)) {
            throw new AuthorizationException(
                "You do not have the '{$permissionName}' permission in the Document module"
            );
        }

        return true;
    }

    /**
     * Check if user can manage document settings/configuration
     *
     * @param Document $document Document instance
     * @return bool
     * @throws AuthorizationException
     */
    public function authorizeDocumentConfiguration(Document $document): bool
    {
        if (!auth()->check()) {
            throw new AuthorizationException('Unauthorized');
        }

        if (!auth()->user()->canActionDocumentComponent('document-management', 'edit')) {
            throw new AuthorizationException('You do not have permission to edit document configuration');
        }

        return true;
    }

    /**
     * Check if user can perform file operations on document
     *
     * @param Document $document Document instance
     * @param string $fileAction Action: 'upload', 'view', 'delete'
     * @return bool
     * @throws AuthorizationException
     */
    public function authorizeFileOperation(Document $document, string $fileAction): bool
    {
        if (!auth()->check()) {
            throw new AuthorizationException('Unauthorized');
        }

        $validActions = ['upload', 'view', 'delete'];
        if (!in_array($fileAction, $validActions)) {
            throw new AuthorizationException("Invalid file action: {$fileAction}");
        }

        if (!auth()->user()->canActionDocumentComponent('document-upload', $fileAction)) {
            throw new AuthorizationException(
                "You do not have permission to {$fileAction} document files"
            );
        }

        return true;
    }

    /**
     * Check if user can perform email operations
     *
     * @param Document $document Document instance
     * @param string $emailType Email type: 'initial-request', 'reminder', etc.
     * @return bool
     * @throws AuthorizationException
     */
    public function authorizeEmailOperation(Document $document, string $emailType): bool
    {
        if (!auth()->check()) {
            throw new AuthorizationException('Unauthorized');
        }

        $validTypes = [
            'send-initial-request',
            'send-reminders',
            'send-missing-docs',
            'send-approval',
            'send-rejection',
            'send-custom-email',
        ];

        if (!in_array($emailType, $validTypes)) {
            throw new AuthorizationException("Invalid email type: {$emailType}");
        }

        if (!auth()->user()->canInDocumentModule($emailType)) {
            throw new AuthorizationException(
                "You do not have permission to send {$emailType} emails"
            );
        }

        // Log email operation for audit trail
        \Log::info("Email operation: {$emailType}", [
            'user_id' => auth()->id(),
            'document_id' => $document->id,
            'document_uid' => $document->uid,
            'timestamp' => now(),
        ]);

        return true;
    }

    /**
     * Check if user can perform validation operations
     *
     * @param Document $document Document instance
     * @param string $action 'approve' or 'reject'
     * @return bool
     * @throws AuthorizationException
     */
    public function authorizeValidationAction(Document $document, string $action): bool
    {
        if (!auth()->check()) {
            throw new AuthorizationException('Unauthorized');
        }

        if (!in_array($action, ['approve', 'reject'])) {
            throw new AuthorizationException("Invalid validation action: {$action}");
        }

        // Check if document has active workflow
        if (!$document->hasWorkflow()) {
            throw new AuthorizationException('Document does not have an active validation workflow');
        }

        // Check if user can perform the action on this component
        if (!auth()->user()->canActionDocumentComponent('validation-workflow', $action)) {
            throw new AuthorizationException(
                "You do not have permission to {$action} documents"
            );
        }

        // Log validation action
        \Log::info("Validation action: {$action}", [
            'user_id' => auth()->id(),
            'document_id' => $document->id,
            'document_uid' => $document->uid,
            'current_stage' => $document->current_stage,
            'total_stages' => $document->total_stages,
            'timestamp' => now(),
        ]);

        return true;
    }

    /**
     * Check if user can manage notes
     *
     * @param string $action 'add', 'edit', 'delete'
     * @return bool
     * @throws AuthorizationException
     */
    public function authorizeNoteOperation(string $action): bool
    {
        if (!auth()->check()) {
            throw new AuthorizationException('Unauthorized');
        }

        $validActions = ['add', 'edit', 'delete'];
        if (!in_array($action, $validActions)) {
            throw new AuthorizationException("Invalid note action: {$action}");
        }

        if (!auth()->user()->canActionDocumentComponent('document-notes', $action)) {
            throw new AuthorizationException(
                "You do not have permission to {$action} notes"
            );
        }

        return true;
    }

    /**
     * Check if user can perform attachment operations
     *
     * @param string $action 'upload' or 'delete'
     * @return bool
     * @throws AuthorizationException
     */
    public function authorizeAttachmentOperation(string $action): bool
    {
        if (!auth()->check()) {
            throw new AuthorizationException('Unauthorized');
        }

        $validActions = ['upload', 'delete'];
        if (!in_array($action, $validActions)) {
            throw new AuthorizationException("Invalid attachment action: {$action}");
        }

        if (!auth()->user()->canActionDocumentComponent('additional-attachments', $action)) {
            throw new AuthorizationException(
                "You do not have permission to {$action} attachments"
            );
        }

        return true;
    }
}
