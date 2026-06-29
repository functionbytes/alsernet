#!/usr/bin/env php
<?php

/**
 * Automated Action to Route Migration Script
 *
 * This script systematically migrates action() helper calls to route() helpers
 * for the Mailing module, based on the pre-defined mapping in the conversion map.
 *
 * Usage:
 *   php scripts/migrate-action-to-route.php [--dry-run] [--controller=ControllerName]
 *
 * Options:
 *   --dry-run     Show what would be changed without modifying files
 *   --controller  Migrate only specific controller (e.g., Automation2Controller)
 *   --verbose     Show detailed output
 */

// Configuration
$baseDir = dirname(__DIR__);
$viewsPath = $baseDir.'/modules/Mailing/resources/views';
$dryRun = in_array('--dry-run', $argv);
$verbose = in_array('--verbose', $argv);
$specificController = null;

foreach ($argv as $arg) {
    if (strpos($arg, '--controller=') === 0) {
        $specificController = substr($arg, 13);
    }
}

// Comprehensive mapping from action() to route()
$controllerMappings = [
    // 1. Automation2Controller → automations.*
    'Automation2Controller' => [
        '@index' => 'automations.index',
        '@listing' => 'automations.listing',
        '@edit' => 'automations.edit',
        '@update' => 'automations.update',
        '@enable' => 'automations.enable',
        '@disable' => 'automations.disable',
        '@delete' => 'automations.delete',
        '@copy' => 'automations.copy',
        '@wizard' => 'automations.wizard',
        '@wizardTrigger' => 'automations.wizard.trigger',
        '@wizardTriggerOption' => 'automations.wizard.trigger.option',
        '@wizardListFieldSelect' => 'automations.wizard.trigger.option.field-select',
        '@settings' => 'automations.settings',
        '@profile' => 'automations.profile',
        '@debug' => 'automations.debug',
        '@insight' => 'automations.insight',
        '@lastSaved' => 'automations.last-saved',
        '@saveData' => 'automations.data.save',
        '@run' => 'automations.run',
        '@triggerNow' => 'automations.trigger.trigger-now',
        '@triggerAll' => 'automations.trigger-all',
        '@triggerEdit' => 'automations.trigger.edit',
        '@triggerSelect' => 'automations.trigger.select',
        '@triggerSelectConfirm' => 'automations.trigger.select-confirm',
        '@triggerSelectPupop' => 'automations.trigger.select-popup',
        '@sendTestEmail' => 'automations.send-test-email',

        // Email routes
        '@email' => 'automations.email.index',
        '@emailSetup' => 'automations.email.setup',
        '@emailConfirm' => 'automations.email.confirm',
        '@emailTemplate' => 'automations.email.template',
        '@emailDelete' => 'automations.email.delete',
        '@emailPreheader' => 'automations.email.preheader',
        '@emailPreheaderAdd' => 'automations.email.preheader.add',
        '@emailPreheaderRemove' => 'automations.email.preheader.remove',
        '@emailAttachmentUpload' => 'automations.email.attachment.upload',
        '@emailAttachmentDownload' => 'automations.email.attachment.download',
        '@emailAttachmentRemove' => 'automations.email.attachment.remove',

        // Template routes
        '@templateLayout' => 'automations.template.layout',
        '@templateLayoutList' => 'automations.template.layout.list',
        '@templateEdit' => 'automations.template.edit',
        '@templateEditClassic' => 'automations.template.edit-classic',
        '@templateEditPlain' => 'automations.template.edit-plain',
        '@templateUpload' => 'automations.template.upload',
        '@templatePreview' => 'automations.template.preview',
        '@templatePreviewContent' => 'automations.template.preview.content',
        '@templateBuilderSelect' => 'automations.template.builder-select',
        '@templateContent' => 'automations.template.content',
        '@templateRemove' => 'automations.template.remove',

        // Webhooks routes
        '@webhooks' => 'automations.webhooks.index',
        '@webhooksList' => 'automations.webhooks.list',
        '@webhooksAdd' => 'automations.webhooks.add',
        '@webhooksEdit' => 'automations.webhooks.edit',
        '@webhooksTest' => 'automations.webhooks.test',
        '@webhooksDelete' => 'automations.webhooks.delete',
        '@webhooksLinkSelect' => 'automations.webhooks.link-select',
        '@webhooksSampleRequest' => 'automations.webhooks.sample.request',

        // Contacts routes
        '@contacts' => 'automations.contacts.index',
        '@contactsList' => 'automations.contacts.list',
        '@subscribersList' => 'automations.subscribers.list',
        '@subscribersShow' => 'automations.subscribers.show',
        '@exportContacts' => 'automations.contacts.export',
        '@copyToNewList' => 'automations.contacts.copy-to-new-list',
        '@removeContact' => 'automations.contacts.remove',
        '@tagContact' => 'automations.contacts.tag',
        '@tagContacts' => 'automations.contacts.tags',
        '@removeTag' => 'automations.contacts.remove-tag',

        // Condition routes
        '@conditionWaitCustom' => 'automations.condition.wait.custom',
        '@conditionSetting' => 'automations.condition.setting',
        '@conditionRemove' => 'automations.condition.remove',
        '@waitTime' => 'automations.wait.time',

        // Action routes
        '@actionSelect' => 'automations.action.select',
        '@actionSelectConfirm' => 'automations.action.select-confirm',
        '@actionSelectPupop' => 'automations.action.select-popup',
        '@actionEdit' => 'automations.action.edit',

        // Operation routes
        '@operationSelect' => 'automations.operation.select',
        '@operationCreate' => 'automations.operation.create',
        '@operationEdit' => 'automations.operation.edit',
        '@operationShow' => 'automations.operation.show',

        // Timeline routes
        '@timeline' => 'automations.timeline.index',
        '@timelineList' => 'automations.timeline.list',

        // Segment routes
        '@segmentSelect' => 'automations.segment-select',

        // Cart routes (WooCommerce integration)
        '@cartList' => 'automations.cart.list',
        '@cartItems' => 'automations.cart.items',
        '@cartStats' => 'automations.cart.stats',
        '@cartWait' => 'automations.cart.wait',
        '@cartChangeList' => 'automations.cart.change-list',
        '@cartChangeStore' => 'automations.cart.change-store',
    ],

    // 2. CampaignController → campaigns.*
    'CampaignController' => [
        '@index' => 'campaigns.index',
        '@create' => 'campaigns.create',
        '@store' => 'campaigns.store',
        '@edit' => 'campaigns.edit',
        '@update' => 'campaigns.update',
        '@show' => 'campaigns.show',
        '@delete' => 'campaigns.delete',
        '@listing' => 'campaigns.listing',
        '@quickView' => 'campaigns.quick-view',
        '@selectType' => 'campaigns.select-type',
        '@select2' => 'campaigns.select2',
        '@copy' => 'campaigns.copy',
        '@pause' => 'campaigns.pause',
        '@restart' => 'campaigns.restart',
        '@deleteConfirm' => 'campaigns.delete.confirm',
        '@copyMoveForm' => 'campaigns.copy-move-form',

        // Wizard steps
        '@setup' => 'campaigns.setup',
        '@recipients' => 'campaigns.recipients',
        '@template' => 'campaigns.template.show',
        '@schedule' => 'campaigns.schedule',
        '@confirm' => 'campaigns.confirm',
        '@plain' => 'campaigns.plain',
        '@resend' => 'campaigns.resend',

        // Template management
        '@templateCreate' => 'campaigns.template.create',
        '@templateEdit' => 'campaigns.template.edit',
        '@templateUpload' => 'campaigns.template.upload',
        '@templateLayout' => 'campaigns.template.layout',
        '@templateLayoutList' => 'campaigns.template.layout.list',
        '@templateSelect' => 'campaigns.template.select',
        '@templateChoose' => 'campaigns.template.choose',
        '@templatePreview' => 'campaigns.template.preview',
        '@templateIframe' => 'campaigns.template.iframe',
        '@templateBuild' => 'campaigns.template.build',
        '@templateRebuild' => 'campaigns.template.rebuild',
        '@templateReview' => 'campaigns.template.review',
        '@templateReviewIframe' => 'campaigns.template.review-iframe',
        '@templateContent' => 'campaigns.template.content',
        '@templateChangeTemplate' => 'campaigns.template.change',
        '@templateBuilderSelect' => 'campaigns.template.builder-select',
        '@builderClassic' => 'campaigns.template.builder-classic',
        '@builderPlainEdit' => 'campaigns.template.builder-plain',

        // Logs and analytics
        '@overview' => 'campaigns.overview',
        '@chart' => 'campaigns.chart',
        '@chart24h' => 'campaigns.chart24h',
        '@chartCountry' => 'campaigns.chart.countries.open',
        '@chartClickCountry' => 'campaigns.chart.countries.click',
        '@links' => 'campaigns.links',
        '@trackingLog' => 'campaigns.tracking-log',
        '@trackingLogListing' => 'campaigns.tracking-log.listing',
        '@trackingLogDownload' => 'campaigns.tracking-log.download',
        '@bounceLog' => 'campaigns.bounce-log',
        '@bounceLogListing' => 'campaigns.bounce-log.listing',
        '@feedbackLog' => 'campaigns.feedback-log',
        '@feedbackLogListing' => 'campaigns.feedback-log.listing',
        '@openLog' => 'campaigns.open-log.show',
        '@openLogListing' => 'campaigns.open-log.listing',
        '@openLogExecute' => 'campaigns.open-log.execute',
        '@clickLog' => 'campaigns.click-log.show',
        '@clickLogListing' => 'campaigns.click-log.listing',
        '@clickLogExecute' => 'campaigns.click-log.execute',
        '@unsubscribeLog' => 'campaigns.unsubscribe-log',
        '@unsubscribeLogListing' => 'campaigns.unsubscribe-log.listing',
        '@openMap' => 'campaigns.open-map',
        '@updateStats' => 'campaigns.update-stats',

        // Subscribers
        '@subscribers' => 'campaigns.subscribers',
        '@subscribersListing' => 'campaigns.subscribers.listing',
        '@listSegmentForm' => 'campaigns.list-segment-form',

        // Preview
        '@preview' => 'campaigns.preview',
        '@previewContent' => 'campaigns.preview.content',
        '@previewAs' => 'campaigns.preview-as',
        '@previewAsList' => 'campaigns.preview-as.list',

        // Preheader
        '@preheader' => 'campaigns.preheader',
        '@preheaderAdd' => 'campaigns.preheader.add',
        '@preheaderRemove' => 'campaigns.preheader.remove',

        // Attachments
        '@uploadAttachment' => 'campaigns.upload-attachment',
        '@removeAttachment' => 'campaigns.remove-attachment',
        '@downloadAttachment' => 'campaigns.download-attachment',

        // Plain text
        '@customPlainOn' => 'campaigns.custom-plain.on',
        '@customPlainOff' => 'campaigns.custom-plain.off',

        // Webhooks
        '@webhooks' => 'campaigns.webhooks.index',
        '@webhooksList' => 'campaigns.webhooks.list',
        '@webhooksAdd' => 'campaigns.webhooks.add',
        '@webhooksEdit' => 'campaigns.webhooks.edit',
        '@webhooksTest' => 'campaigns.webhooks.test',
        '@webhooksTestMessage' => 'campaigns.webhooks.test-message',
        '@webhooksDelete' => 'campaigns.webhooks.delete',
        '@webhooksLinkSelect' => 'campaigns.webhooks.link-select',
        '@webhooksSampleRequest' => 'campaigns.webhooks.sample.request',

        // Execution
        '@run' => 'campaigns.run',
        '@sendTestEmail' => 'campaigns.send-test-email',
        '@spamScore' => 'campaigns.spam-score',

        // Job progress
        '@trackingLogExportProgress' => 'campaigns.job.progress',
        '@download' => 'campaigns.job.download',
    ],

    // 3. SendingServerController → sending_servers.*
    'SendingServerController' => [
        '@index' => 'sending_servers.index',
        '@create' => 'sending_servers.create',
        '@store' => 'sending_servers.store',
        '@edit' => 'sending_servers.edit',
        '@update' => 'sending_servers.update',
        '@delete' => 'sending_servers.delete',
        '@listing' => 'sending_servers.listing',
        '@test' => 'sending_servers.test',
        '@testConnection' => 'sending_servers.test-connection',
        '@sendingLimit' => 'sending_servers.sending-limit',
        '@config' => 'sending_servers.config',
        '@enable' => 'sending_servers.enable',
        '@disable' => 'sending_servers.disable',
        '@fromDropbox' => 'sending_servers.senders.dropbox',
        '@removeDomain' => 'sending_servers.remove-domain',
        '@addDomains' => 'sending_servers.add-domains',
    ],

    // 4. Admin\SendingServerController → settings.sending_servers.*
    'Admin\\SendingServerController' => [
        '@index' => 'settings.sending_servers.index',
        '@create' => 'settings.sending_servers.create',
        '@store' => 'settings.sending_servers.store',
        '@edit' => 'settings.sending_servers.edit',
        '@update' => 'settings.sending_servers.update',
        '@delete' => 'settings.sending_servers.delete',
        '@listing' => 'settings.sending_servers.listing',
        '@test' => 'settings.sending_servers.test',
        '@testConnection' => 'settings.sending_servers.test-connection',
        '@sendingLimit' => 'settings.sending_servers.sending-limit',
        '@config' => 'settings.sending_servers.config',
        '@enable' => 'settings.sending_servers.enable',
        '@disable' => 'settings.sending_servers.disable',
        '@select2' => 'settings.sending_servers.select2',
    ],

    // 5. SubscriberController → lists.subscribers.* or subscribers.*
    'SubscriberController' => [
        '@index' => 'lists.subscribers.index',
        '@create' => 'lists.subscribers.create',
        '@store' => 'lists.subscribers.store',
        '@edit' => 'lists.subscribers.edit',
        '@update' => 'lists.subscribers.update',
        '@delete' => 'lists.subscribers.delete',
        '@listing' => 'lists.subscribers.listing',
        '@subscribe' => 'lists.subscribers.subscribe',
        '@unsubscribe' => 'lists.subscribers.unsubscribe',
        '@copyMoveForm' => 'lists.subscribers.copy-move-form',
        '@copy' => 'subscribers.copy',
        '@move' => 'subscribers.move',
        '@assignValues' => 'lists.subscribers.assign-values',
        '@bulkDelete' => 'lists.subscribers.bulk-delete',
        '@resendConfirmationEmail' => 'lists.subscribers.resend.confirmation-email',
        '@updateTags' => 'lists.subscriber.update-tags',
        '@removeTag' => 'lists.subscriber.remove-tag',
        '@noList' => 'subscribers.no-list',

        // Import/Export
        '@import' => 'lists.subscribers.import',
        '@dispatchImportJob' => 'lists.subscribers.import.dispatch',
        '@importProgress' => 'lists.import.progress',
        '@downloadImportLog' => 'lists.import.log.download',
        '@cancelImport' => 'lists.import.cancel',
        '@export' => 'lists.subscribers.export',
        '@dispatchExportJob' => 'lists.subscribers.export.dispatch',
        '@exportProgress' => 'lists.export.progress',
        '@downloadExportedFile' => 'lists.export.log.download',
        '@cancelExport' => 'lists.export.cancel',

        // Import2 wizard
        '@import2' => 'lists.subscribers.import2.index',
        '@import2Wizard' => 'lists.subscribers.import2.wizard',
        '@import2Upload' => 'lists.subscribers.import2.upload',
        '@import2Mapping' => 'lists.subscribers.import2.mapping',
        '@import2Validate' => 'lists.subscribers.import2.validate',
        '@import2Run' => 'lists.subscribers.import2.run',
        '@import2Progress' => 'lists.subscribers.import2.progress',
        '@import2ProgressContent' => 'lists.subscribers.import2.progress.content',

        // Verification
        '@startVerification' => 'subscriber.verification.start',
        '@resetVerification' => 'subscriber.verification.reset',

        // Avatar
        '@avatar' => 'subscriber.avatar',
        '@avatarOrigin' => 'subscriber.avatar-origin',
    ],

    // ========================================================================
    // PHASE 2: Controllers 6-10 (315 replacements)
    // ========================================================================

    // 6. Admin\TemplateController → settings.mailing.templates.*
    'Admin\\TemplateController' => [
        '@index' => 'settings.mailing.templates.index',
        '@create' => 'settings.mailing.templates.create',
        '@store' => 'settings.mailing.templates.store',
        '@show' => 'settings.mailing.templates.show',
        '@edit' => 'settings.mailing.templates.edit',
        '@update' => 'settings.mailing.templates.update',
        '@destroy' => 'settings.mailing.templates.destroy',
        '@delete' => 'settings.mailing.templates.delete',
        '@listing' => 'settings.mailing.templates.listing',
        '@chat' => 'settings.mailing.templates.chat',
        '@export' => 'settings.mailing.templates.export',
        '@changeName' => 'settings.mailing.templates.change-name',
        '@categories' => 'settings.mailing.templates.categories',
        '@updateThumbUrl' => 'settings.mailing.templates.update-thumb-url',
        '@updateThumb' => 'settings.mailing.templates.update-thumb',
        '@builderChangeTemplate' => 'settings.mailing.templates.builder.change-template',
        '@builderTemplates' => 'settings.mailing.templates.builder.templates',
        '@builderCreate' => 'settings.mailing.templates.builder.create',
        '@uploadTemplateAssets' => 'settings.mailing.templates.builder.edit.asset',
        '@builderEditContent' => 'settings.mailing.templates.builder.edit.content',
        '@builderEdit' => 'settings.mailing.templates.builder.edit',
        '@copy' => 'settings.mailing.templates.copy',
        '@preview' => 'settings.mailing.templates.preview',
        '@uploadTemplate' => 'settings.mailing.templates.upload',
    ],

    // 7. TemplateController → templates.*
    'TemplateController' => [
        '@index' => 'templates.index',
        '@create' => 'templates.create',
        '@store' => 'templates.store',
        '@show' => 'templates.show',
        '@edit' => 'templates.edit',
        '@update' => 'templates.update',
        '@destroy' => 'templates.destroy',
        '@delete' => 'templates.delete',
        '@listing' => 'templates.listing',
        '@choosing' => 'templates.choosing',
        '@chat' => 'templates.chat',
        '@parseRss' => 'templates.rss.parse',
        '@export' => 'templates.export',
        '@changeName' => 'templates.change-name',
        '@categories' => 'templates.categories',
        '@updateThumbUrl' => 'templates.update-thumb-url',
        '@updateThumb' => 'templates.update-thumb',
        '@builderChangeTemplate' => 'templates.builder.change-template',
        '@builderTemplates' => 'templates.builder.templates',
        '@builderCreate' => 'templates.builder.create',
        '@uploadTemplateAssets' => 'templates.builder.edit.asset',
        '@builderEditContent' => 'templates.builder.edit.content',
        '@builderEdit' => 'templates.builder.edit',
        '@copy' => 'templates.copy',
        '@uploadTemplate' => 'templates.upload',
        '@preview' => 'templates.preview',
    ],

    // 8. Store\ProductController → store.products.*
    'Store\\ProductController' => [
        '@index' => 'store.products.index',
        '@create' => 'store.products.create',
        '@store' => 'store.products.store',
        '@show' => 'store.products.show',
        '@edit' => 'store.products.edit',
        '@update' => 'store.products.update',
        '@destroy' => 'store.products.destroy',
        '@delete' => 'store.products.delete',
        '@attributes' => 'store.products.attributes',
        '@list' => 'store.products.list',
        '@search' => 'store.products.search',
        '@deleteSelected' => 'store.products.delete-selected',
        '@updateStatus' => 'store.products.update-status',
        '@multiltask' => 'store.products.multitask',
    ],

    // 9. Pub\CampaignController → pub.campaigns.*
    'Pub\\CampaignController' => [
        '@quickView' => 'pub.campaigns.quick-view',
        '@download' => 'pub.campaigns.job.download',
        '@trackingLogExportProgress' => 'pub.campaigns.job.progress',
        '@overview' => 'pub.campaigns.overview',
        '@links' => 'pub.campaigns.links',
        '@chart24h' => 'pub.campaigns.chart24h',
        '@chart' => 'pub.campaigns.chart',
        '@chartCountry' => 'pub.campaigns.chart.countries.open',
        '@chartClickCountry' => 'pub.campaigns.chart.countries.click',
        '@subscribers' => 'pub.campaigns.subscribers',
        '@subscribersListing' => 'pub.campaigns.subscribers.listing',
        '@openLog' => 'pub.campaigns.open-log.show',
        '@openLogListing' => 'pub.campaigns.open-log.listing',
        '@clickLog' => 'pub.campaigns.click-log.show',
        '@clickLogListing' => 'pub.campaigns.click-log.listing',
        '@unsubscribeLog' => 'pub.campaigns.unsubscribe-log',
        '@unsubscribeLogListing' => 'pub.campaigns.unsubscribe-log.listing',
        '@trackingLog' => 'pub.campaigns.tracking-log',
        '@trackingLogListing' => 'pub.campaigns.tracking-log.listing',
        '@trackingLogDownload' => 'pub.campaigns.tracking-log.download',
        '@bounceLog' => 'pub.campaigns.bounce-log',
        '@bounceLogListing' => 'pub.campaigns.bounce-log.listing',
        '@feedbackLog' => 'pub.campaigns.feedback-log',
        '@feedbackLogListing' => 'pub.campaigns.feedback-log.listing',
        '@openMap' => 'pub.campaigns.open-map',
    ],

    // 10. MailListController → lists.*
    'MailListController' => [
        '@index' => 'lists.index',
        '@create' => 'lists.create',
        '@store' => 'lists.store',
        '@show' => 'lists.show',
        '@edit' => 'lists.edit',
        '@update' => 'lists.update',
        '@destroy' => 'lists.destroy',
        '@delete' => 'lists.delete',
        '@deleteConfirm' => 'lists.delete.confirm',
        '@listing' => 'lists.listing',
        '@sort' => 'lists.sort',
        '@quickView' => 'lists.quick-view',
        '@copy' => 'lists.copy',
        '@selectList' => 'lists.select',
        '@emailVerificationChart' => 'lists.email-verification.chart',
        '@cloneForCustomersChoose' => 'lists.clone-to-customers.choose',
        '@cloneForCustomers' => 'lists.clone-to-customers',
        '@verificationProgress' => 'lists.verification.progress',
        '@verification' => 'lists.verification',
        '@startVerification' => 'lists.verification.start',
        '@stopVerification' => 'lists.verification.stop',
        '@resetVerification' => 'lists.verification.reset',
        '@listGrowthChart' => 'lists.list-growth',
        '@statisticsChart' => 'lists.list-statistics-chart',
        '@overview' => 'lists.overview',
        '@embeddedForm' => 'lists.embedded-form',
        '@embeddedFormFrame' => 'lists.embedded-form-frame',
    ],

    // ========================================================================
    // PHASE 3: Controllers 11-15 (152 replacements)
    // ========================================================================

    // 11. FormController → forms.*
    'FormController' => [
        '@index' => 'forms.index',
        '@create' => 'forms.create',
        '@store' => 'forms.store',
        '@show' => 'forms.show',
        '@edit' => 'forms.edit',
        '@update' => 'forms.update',
        '@destroy' => 'forms.destroy',
        '@delete' => 'forms.delete',
        '@listing' => 'forms.listing',
        '@templates' => 'forms.templates',
        '@changeTemplate' => 'forms.change-template',
        '@preview' => 'forms.preview',
        '@publish' => 'forms.publish',
        '@unpublish' => 'forms.unpublish',
        '@builderContent' => 'forms.edit.content',
        '@disconnect' => 'forms.disconnect',
        '@connect' => 'forms.connect',
        '@settings' => 'forms.settings',
        '@embeddedFormSettings' => 'forms.embedded-form-settings',
    ],

    // 12. AccountController → account.*
    'AccountController' => [
        '@profile' => 'account.profile.show',
        '@changeThemeMode' => 'account.change-theme-mode',
        '@saveAutoThemeMode' => 'account.save-auto-theme-mode',
        '@activity' => 'account.activity',
        '@wizardMenuLayout' => 'account.wizard.menu-layout',
        '@wizardColorScheme' => 'account.wizard.color-scheme',
        '@leftbarState' => 'account.leftbar.state',
        '@removePaymentMethod' => 'account.payment.remove',
        '@editPaymentMethod' => 'account.payment.edit',
        '@api' => 'account.api',
        '@renew' => 'account.api.renew',
        '@contact' => 'account.contact',
        '@contactUpdate' => 'account.contact.update',
        '@logs' => 'account.logs',
        '@logsListing' => 'account.logs.listing',
        '@quota' => 'account.quota',
        '@quotaLog' => 'account.quota.log',
        '@subscription' => 'account.subscription',
        '@subscriptionNew' => 'account.subscription.new',
        '@subscriptionPreview' => 'account.subscription.preview',
    ],

    // 13. SegmentController → segments.*
    'SegmentController' => [
        '@index' => 'segments.index',
        '@create' => 'segments.create',
        '@store' => 'segments.store',
        '@show' => 'segments.show',
        '@edit' => 'segments.edit',
        '@update' => 'segments.update',
        '@destroy' => 'segments.destroy',
        '@delete' => 'segments.delete',
        '@listing' => 'segments.listing',
        '@noList' => 'segments.no-list',
        '@conditionValueControl' => 'segments.condition-value-control',
        '@selectBox' => 'segments.select-box',
        '@sample_condition' => 'segments.sample-condition',
        '@subscribers' => 'segments.subscribers',
    ],

    // 14. Admin\SettingController → settings.mailing.settings.*
    'Admin\\SettingController' => [
        '@index' => 'settings.mailing.settings.index',
        '@general' => 'settings.mailing.settings.general',
        '@updateGeneral' => 'settings.mailing.settings.general.update',
        '@mailer' => 'settings.mailing.settings.mailer',
        '@updateMailer' => 'settings.mailing.settings.mailer.update',
        '@sendingServer' => 'settings.mailing.settings.sending-server',
        '@updateSendingServer' => 'settings.mailing.settings.sending-server.update',
        '@cronjob' => 'settings.mailing.settings.cronjob',
        '@updateCronjob' => 'settings.mailing.settings.cronjob.update',
        '@background' => 'settings.mailing.settings.background',
        '@updateBackground' => 'settings.mailing.settings.background.update',
        '@upgrade' => 'settings.mailing.settings.upgrade',
        '@doUpgrade' => 'settings.mailing.settings.upgrade.do',
        '@license' => 'settings.mailing.settings.license',
        '@updateLicense' => 'settings.mailing.settings.license.update',
    ],

    // 15. WebsiteController → websites.*
    'WebsiteController' => [
        '@index' => 'websites.index',
        '@create' => 'websites.create',
        '@store' => 'websites.store',
        '@show' => 'websites.show',
        '@edit' => 'websites.edit',
        '@update' => 'websites.update',
        '@destroy' => 'websites.destroy',
        '@delete' => 'websites.delete',
        '@list' => 'websites.list',
        '@check' => 'websites.check',
        '@connect' => 'websites.connect',
        '@disconnect' => 'websites.disconnect',
    ],

    // ========================================================================
    // PHASE 4: Controllers 16-20 (107 replacements)
    // ========================================================================

    // 16. SenderController → senders.*
    'SenderController' => [
        '@index' => 'senders.index',
        '@create' => 'senders.create',
        '@store' => 'senders.store',
        '@show' => 'senders.show',
        '@edit' => 'senders.edit',
        '@update' => 'senders.update',
        '@destroy' => 'senders.destroy',
        '@delete' => 'senders.delete',
        '@listing' => 'senders.listing',
        '@sort' => 'senders.sort',
        '@dropbox' => 'senders.dropbox',
    ],

    // 17. Admin\LanguageController → settings.mailing.languages.*
    'Admin\\LanguageController' => [
        '@index' => 'settings.mailing.languages.index',
        '@create' => 'settings.mailing.languages.create',
        '@store' => 'settings.mailing.languages.store',
        '@show' => 'settings.mailing.languages.show',
        '@edit' => 'settings.mailing.languages.edit',
        '@update' => 'settings.mailing.languages.update',
        '@destroy' => 'settings.mailing.languages.destroy',
        '@delete' => 'settings.mailing.languages.delete',
        '@listing' => 'settings.mailing.languages.listing',
        '@disable' => 'settings.mailing.languages.disable',
        '@enable' => 'settings.mailing.languages.enable',
        '@upload' => 'settings.mailing.languages.upload',
        '@download' => 'settings.mailing.languages.download',
    ],

    // 18. SendingDomainController → sending_domains.*
    'SendingDomainController' => [
        '@index' => 'sending_domains.index',
        '@create' => 'sending_domains.create',
        '@store' => 'sending_domains.store',
        '@show' => 'sending_domains.show',
        '@edit' => 'sending_domains.edit',
        '@update' => 'sending_domains.update',
        '@destroy' => 'sending_domains.destroy',
        '@delete' => 'sending_domains.delete',
        '@listing' => 'sending_domains.listing',
        '@sort' => 'sending_domains.sort',
        '@updateDkimSelector' => 'sending_domains.updateDkimSelector',
        '@records' => 'sending_domains.records',
        '@verify' => 'sending_domains.verify',
    ],

    // 19. Store\CategoryController → store.categories.*
    'Store\\CategoryController' => [
        '@index' => 'store.categories.index',
        '@create' => 'store.categories.create',
        '@store' => 'store.categories.store',
        '@show' => 'store.categories.show',
        '@edit' => 'store.categories.edit',
        '@update' => 'store.categories.update',
        '@destroy' => 'store.categories.destroy',
        '@delete' => 'store.categories.delete',
        '@get_attrible_catid' => 'store.categories.get-attributes',
        '@collection' => 'store.categories.collection',
        '@list' => 'store.categories.list',
        '@deleteSelected' => 'store.categories.delete-selected',
        '@statusChangeSelected' => 'store.categories.change-status',
        '@updateStatus' => 'store.categories.update-status',
        '@multiltask' => 'store.categories.multitask',
    ],

    // 20. TrackingDomainController → tracking_domains.*
    'TrackingDomainController' => [
        '@index' => 'tracking_domains.index',
        '@create' => 'tracking_domains.create',
        '@store' => 'tracking_domains.store',
        '@show' => 'tracking_domains.show',
        '@edit' => 'tracking_domains.edit',
        '@update' => 'tracking_domains.update',
        '@destroy' => 'tracking_domains.destroy',
        '@delete' => 'tracking_domains.delete',
        '@listing' => 'tracking_domains.listing',
        '@verify' => 'tracking_domains.verify',
        '@showCname' => 'tracking_domains.cname',
        '@verifyCname' => 'tracking_domains.verify.cname',
    ],
];

// Stats tracking
$stats = [
    'filesScanned' => 0,
    'filesModified' => 0,
    'replacementsMade' => 0,
    'errors' => [],
];

/**
 * Recursively find all .blade.php files
 */
function findBladeFiles($directory)
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php' &&
            strpos($file->getFilename(), '.blade.') !== false) {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}

/**
 * Process a single file
 */
function processFile($filePath, $mappings, &$stats, $dryRun, $verbose)
{
    $content = file_get_contents($filePath);
    $originalContent = $content;
    $fileReplacements = 0;

    foreach ($mappings as $controller => $methods) {
        foreach ($methods as $method => $route) {
            // Pattern to match action('Controller@method', ...)
            $patterns = [
                // With double quotes
                "/action\(\s*\"".preg_quote($controller.$method, '/')."\"\s*([,)])/",
                // With single quotes
                "/action\(\s*'".preg_quote($controller.$method, '/')."'\s*([,)])/",
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    $replacement = "route('$route'$1";
                    $newContent = preg_replace($pattern, $replacement, $content);

                    if ($newContent !== $content) {
                        $count = 0;
                        $content = preg_replace($pattern, $replacement, $content, -1, $count);
                        $fileReplacements += $count;

                        if ($verbose) {
                            echo "  ✓ {$controller}{$method} → route('$route') ($count occurrences)\n";
                        }
                    }
                }
            }
        }
    }

    if ($content !== $originalContent) {
        if (! $dryRun) {
            file_put_contents($filePath, $content);
        }

        $stats['filesModified']++;
        $stats['replacementsMade'] += $fileReplacements;

        $relativePath = str_replace(dirname(__DIR__).'/', '', $filePath);
        echo ($dryRun ? '[DRY RUN] ' : '')."✓ Modified: $relativePath ($fileReplacements replacements)\n";
    }
}

// Main execution
echo "=================================================================\n";
echo "  Action to Route Migration Script\n";
echo "=================================================================\n\n";

if ($dryRun) {
    echo "🔍 DRY RUN MODE - No files will be modified\n\n";
}

if ($specificController) {
    echo "🎯 Migrating only: $specificController\n\n";
    if (! isset($controllerMappings[$specificController])) {
        echo "❌ Error: Controller '$specificController' not found in mappings\n";
        exit(1);
    }
    $mappings = [$specificController => $controllerMappings[$specificController]];
} else {
    $mappings = $controllerMappings;
}

echo "📁 Scanning for .blade.php files...\n";
$files = findBladeFiles($viewsPath);
$stats['filesScanned'] = count($files);
echo "   Found {$stats['filesScanned']} files\n\n";

echo "🔄 Processing files...\n\n";
foreach ($files as $file) {
    if ($verbose) {
        echo "\n📄 Processing: ".basename($file)."\n";
    }

    try {
        processFile($file, $mappings, $stats, $dryRun, $verbose);
    } catch (Exception $e) {
        $stats['errors'][] = [
            'file' => $file,
            'error' => $e->getMessage(),
        ];
        echo "❌ Error processing $file: ".$e->getMessage()."\n";
    }
}

// Print summary
echo "\n=================================================================\n";
echo "  Migration Summary\n";
echo "=================================================================\n\n";
echo "Files scanned:    {$stats['filesScanned']}\n";
echo "Files modified:   {$stats['filesModified']}\n";
echo "Replacements:     {$stats['replacementsMade']}\n";
echo 'Errors:           '.count($stats['errors'])."\n";

if ($dryRun) {
    echo "\n⚠️  This was a DRY RUN - no changes were made\n";
    echo "   Remove --dry-run to apply changes\n";
} else {
    echo "\n✅ Migration complete!\n";
}

if (! empty($stats['errors'])) {
    echo "\n❌ Errors occurred:\n";
    foreach ($stats['errors'] as $error) {
        echo "   - {$error['file']}: {$error['error']}\n";
    }
}

echo "\n";
exit(0);
