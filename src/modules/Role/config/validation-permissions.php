<?php

/**
 * Validation Permissions Configuration
 *
 * Defines which actions are allowed for each validation stage.
 * Used by Document validation workflow to enforce stage-specific permissions.
 *
 * Stages:
 * - documentacion (1st stage): Initial document review
 * - licencias (2nd stage): License/weapon validation (if applicable)
 * - contabilidad (3rd stage): Financial/accounting validation (if applicable)
 */

// Note: Using string values instead of enum to avoid autoload issues during config loading
// ValidationAction enum values: APPROVE, REJECT, SEND_APPROVAL_EMAIL, ADD_COMMENT,
// REQUEST_ADDITIONAL_DOCS, MOVE_TO_NEXT_STAGE, ACCESS_ADDITIONAL_FILES

return [
    // =========================================================================
    // STAGE: DOCUMENTACIÓN (Stage 1)
    // Purpose: Verify that all required documents are submitted correctly
    // =========================================================================
    'documentacion' => [
        'order' => 1,
        'label' => 'Documentación',
        'description' => 'Verificación de documentos requeridos',
        'allowed_actions' => [
            'approve',                  // ✅ Validate docs and move to next
            'reject',                   // ❌ Reject if docs incomplete
            'send_approval_email',      // 📧 Send approval email to customer
            'add_comment',              // 💬 Add internal comments
            'request_additional_docs',  // 📎 Request more docs if needed
        ],
        'restricted_actions' => [
            'move_to_next_stage',       // Cannot skip full approval
            'access_additional_files',  // Not applicable at this stage
        ],
    ],

    // =========================================================================
    // STAGE: LICENCIAS (Stage 2)
    // Purpose: Validate licenses and weapon-related documents (conditional stage)
    // =========================================================================
    'licencias' => [
        'order' => 2,
        'label' => 'Licencias',
        'description' => 'Validación de licencias y documentos de armas',
        'allowed_actions' => [
            'approve',                 // ✅ Confirm docs ok, move to next
            'add_comment',             // 💬 Add internal comments
            'request_additional_docs', // 📎 Request more docs if needed
        ],
        'restricted_actions' => [
            'reject',                  // ❌ Cannot reject directly
            'send_approval_email',     // 📧 No approval emails at this stage
            'move_to_next_stage',      // Must fully approve to advance
            'access_additional_files', // Not applicable at this stage
        ],
        'notes' => 'Intermediate stage: Can only confirm and advance, not reject',
    ],

    // =========================================================================
    // STAGE: CONTABILIDAD (Stage 3)
    // Purpose: Final financial/accounting validation and approval
    // =========================================================================
    'contabilidad' => [
        'order' => 3,
        'label' => 'Contabilidad',
        'description' => 'Validación final de contabilidad',
        'allowed_actions' => [
            'approve',                 // ✅ Final approval
            'reject',                  // ❌ Final rejection
            'send_approval_email',     // 📧 Send final approval email to customer
            'access_additional_files', // 📎 Access additional attachments
            'add_comment',             // 💬 Add internal comments
            'request_additional_docs', // 📎 Request more docs if needed
        ],
        'restricted_actions' => [
            'move_to_next_stage',      // No next stage after this
        ],
        'notes' => 'Final stage: Full permissions for complete validation and approval',
    ],

    // =========================================================================
    // DEFAULT ACTIONS (available on ALL stages)
    // =========================================================================
    'default_allowed_actions' => [
        'add_comment',             // 💬 Comments always allowed
        'request_additional_docs', // 📎 Can request docs at any stage
    ],

    // =========================================================================
    // RULES & CONSTRAINTS
    // =========================================================================
    'rules' => [
        // Only first and last stages can send approval emails
        'send_approval_email_only_first_last' => true,

        // Only first and last stages can reject
        'reject_only_first_last' => true,

        // Intermediate stages can only advance, not approve completely
        'intermediate_stages_advance_only' => true,

        // Additional files only accessible on final stage
        'additional_files_final_stage_only' => true,
    ],
];
