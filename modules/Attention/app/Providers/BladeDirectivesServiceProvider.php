<?php

namespace Modules\Attention\App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class BladeDirectivesServiceProvider extends ServiceProvider
{
    /**
     * Register custom blade directives for attention permissions.
     */
    public function boot(): void
    {
        // Check if user can view attention
        Blade::if('canViewAttention', function ($attention) {
            return auth()->check() && auth()->user()->can('view', $attention);
        });

        // Check if user can update attention
        Blade::if('canUpdateAttention', function ($attention) {
            return auth()->check() && auth()->user()->can('update', $attention);
        });

        // Check if user can delete attention
        Blade::if('canDeleteAttention', function ($attention) {
            return auth()->check() && auth()->user()->can('delete', $attention);
        });

        // Check if user can manage attention
        Blade::if('canManageAttention', function ($attention) {
            return auth()->check() && auth()->user()->can('manage', $attention);
        });

        // Check if user can assign attention
        Blade::if('canAssignAttention', function ($attention) {
            return auth()->check() && auth()->user()->can('assign', $attention);
        });

        // Check if user can change status
        Blade::if('canChangeAttentionStatus', function ($attention) {
            return auth()->check() && auth()->user()->can('changeStatus', $attention);
        });

        // Check if user can resolve attention
        Blade::if('canResolveAttention', function ($attention) {
            return auth()->check() && auth()->user()->can('resolve', $attention);
        });

        // Check if user can close attention
        Blade::if('canCloseAttention', function ($attention) {
            return auth()->check() && auth()->user()->can('close', $attention);
        });

        // Check if user can send emails
        Blade::if('canSendAttentionEmail', function ($attention) {
            return auth()->check() && auth()->user()->can('sendEmail', $attention);
        });

        // Check if user can manage notes
        Blade::if('canManageAttentionNotes', function ($attention) {
            return auth()->check() && auth()->user()->can('manageNotes', $attention);
        });

        // Check if user can view history
        Blade::if('canViewAttentionHistory', function ($attention) {
            return auth()->check() && auth()->user()->can('viewHistory', $attention);
        });

        // Check if user can view all attentions
        Blade::if('canViewAllAttentions', function () {
            return auth()->check() && auth()->user()->can('viewAll', \Modules\Attention\App\Models\Attention::class);
        });

        // Check if user is assigned to attention
        Blade::if('isAssignedToAttention', function ($attention) {
            return auth()->check() && auth()->user()->isAssignedTo($attention);
        });

        // Check if user is attention creator
        Blade::if('isAttentionCreator', function ($attention) {
            return auth()->check() && auth()->user()->isAttentionCreator($attention);
        });

        // Check if user belongs to attention department
        Blade::if('belongsToAttentionDepartment', function ($attention) {
            return auth()->check() && auth()->user()->belongsToAttentionDepartment($attention);
        });
    }
}
