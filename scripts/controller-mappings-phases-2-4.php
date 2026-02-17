<?php

/**
 * Controller Mappings for Phases 2-4
 *
 * This file contains the route mappings for controllers 6-20
 * to be integrated into the main migration script.
 *
 * Phase 2: Controllers 6-10 (315 replacements estimated)
 * Phase 3: Controllers 11-15 (152 replacements estimated)
 * Phase 4: Controllers 16-20 (107 replacements estimated)
 */

return [
    // ========================================================================
    // PHASE 2: Controllers 6-10 (315 replacements)
    // ========================================================================

    // 6. Admin\TemplateController → settings.mailing.templates.* (ADMIN CONTEXT)
    // This is the ADMIN version in settings area
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

    // 7. TemplateController → templates.* (FRONTEND CONTEXT)
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
