<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Modules\Mailing\Http\Controllers\Controller;

class SearchController extends Controller
{
    public function general(Request $request)
    {
        $items = [
            [
                'names' => [trans('messages.settings'), trans('messages.general')],
                'url' => action('Settings\SettingController@general'),
                'keywords' => [
                    trans('messages.api'),
                    trans('messages.site_logo'),
                    trans('messages.site_favicon'),
                    trans('messages.site_offline_message'),
                    trans('messages.site_keyword'),
                    trans('messages.site_description'),
                    trans('messages.site_online'),
                    trans('messages.login_recaptcha'),
                    trans('messages.builder'),
                ],
            ],
            [
                'names' => [trans('messages.settings'), trans('messages.system_email')],
                'url' => action('Settings\SettingController@mailer'),
                'keywords' => [
                    trans('messages.smtp'),
                    trans('messages.system.sendmail'),
                ],
            ],
            [
                'names' => [trans('messages.settings'), trans('messages.system_urls')],
                'url' => action('Settings\SettingController@urls'),
                'keywords' => [],
            ],
            [
                'names' => [trans('messages.settings'), trans('messages.upgrade_manager')],
                'url' => action('Settings\SettingController@upgrade'),
                'keywords' => [],
            ],
            [
                'names' => [trans('messages.settings'), trans('messages.background_job')],
                'url' => action('Settings\SettingController@cronjob'),
                'keywords' => [
                    trans('messages.smtp'),
                    'crontab cron',
                ],
            ],
            [
                'names' => [trans('messages.settings'), trans('messages.license_tab')],
                'url' => action('Settings\SettingController@license'),
                'keywords' => [],
            ],
            [
                'names' => [trans('messages.api')],
                'url' => action('Settings\AccountController@api'),
                'keywords' => [
                    trans('messages.api'),
                ],
            ],
            [
                'names' => [trans('messages.account'), trans('messages.profile')],
                'url' => action('Settings\AccountController@profile'),
                'keywords' => [
                    trans('messages.theme_mode'),
                    trans('messages.account.menu_layout'),
                    trans('messages.account.personality'),
                    trans('messages.profile_photo'),
                    trans('messages.basic_information'),
                    trans('messages.email'),
                    trans('messages.password'),
                    trans('messages.change_password'),
                    trans('messages.color_scheme'),
                ],
            ],
            [
                'names' => [trans('messages.subscriptions')],
                'url' => action('Settings\SubscriptionController@index'),
                'keywords' => [
                    trans('messages.subscription.resume'),
                    trans('messages.subscription.cancel_now'),
                    trans('messages.subscription.change_plan'),
                    trans('messages.invoices'),
                    trans('messages.transactions'),
                    trans('messages.payment_method'),
                ],
            ],
            [
                'names' => [trans('messages.dashboard')],
                'url' => action('Settings\HomeController@index'),
                'keywords' => [
                    trans('messages.home'),
                ],
            ],
            [
                'names' => [trans('messages.templates')],
                'url' => action('Settings\TemplateController@index'),
                'keywords' => [],
            ],
            [
                'names' => [trans('messages.templates'), trans('messages.create')],
                'url' => action('Settings\TemplateController@builderCreate'),
                'keywords' => [
                    trans('messages.new'),
                    trans('messages.add'),
                ],
            ],
            [
                'names' => [trans('messages.templates'), trans('messages.upload')],
                'url' => action('Settings\TemplateController@uploadTemplate'),
                'keywords' => [],
            ],
            [
                'names' => [trans('messages.sending_servers')],
                'url' => action('Settings\SendingServerController@index'),
                'keywords' => [
                    'Amazon SMTP sendgrid mailgun elastic Sparkpost smtp sendmail',
                ],
            ],
            [
                'names' => [trans('messages.sending_servers'), trans('messages.add')],
                'url' => action('Settings\SendingServerController@select'),
                'keywords' => [
                    trans('messages.new'),
                    trans('messages.add'),
                    'Amazon SMTP sendgrid mailgun elastic Sparkpost smtp sendmail',
                ],
            ],
            [
                'names' => [trans('messages.page_form_layout')],
                'url' => action('Settings\LayoutController@index'),
                'keywords' => [
                    trans('messages.sign_up_form'),
                    trans('messages.sign_up_thankyou_page'),
                    trans('messages.final_welcome_email'),
                    trans('messages.unsubscribe_form'),
                    trans('messages.unsubscribe_success_page'),
                    trans('messages.goodbye_email'),
                    trans('messages.unsubscribe'),
                    trans('messages.update_profile'),
                    trans('messages.update_profile_form'),
                    trans('messages.update_profile_success_page'),
                ],
            ],
            [
                'names' => [trans('messages.customers')],
                'url' => action('Settings\CustomerController@index'),
                'keywords' => [],
            ],
            [
                'names' => [trans('messages.customers'), trans('messages.add')],
                'url' => action('Settings\CustomerController@create'),
                'keywords' => [
                    trans('messages.new'),
                    trans('messages.add'),
                ],
            ],
            [
                'names' => [trans('messages.plans')],
                'url' => action('Settings\PlanController@index'),
                'keywords' => [],
            ],
            [
                'names' => [trans('messages.admins')],
                'url' => action('Settings\AdminController@index'),
                'keywords' => [],
            ],
            [
                'names' => [trans('messages.admins'), trans('messages.add')],
                'url' => action('Settings\AdminController@create'),
                'keywords' => [
                    trans('messages.new'),
                    trans('messages.add'),
                ],
            ],
            [
                'names' => [trans('messages.admin_groups')],
                'url' => action('Settings\AdminGroupController@index'),
                'keywords' => [],
            ],
            [
                'names' => [trans('messages.admin_groups'), trans('messages.create')],
                'url' => action('Settings\AdminGroupController@create'),
                'keywords' => [
                    trans('messages.new'),
                    trans('messages.add'),
                ],
            ],
            [
                'names' => [trans('messages.plan'), trans('messages.currencies')],
                'url' => action('Settings\CurrencyController@index'),
                'keywords' => [
                    trans('messages.payment'),
                ],
            ],
            [
                'names' => [trans('messages.plan'), trans('messages.tax_settings')],
                'url' => action('Settings\TaxController@settings'),
                'keywords' => [
                ],
            ],
            [
                'names' => [trans('messages.sending'), trans('messages.sub_accounts')],
                'url' => action('Settings\SubAccountController@index'),
                'keywords' => [],
            ],
            [
                'names' => [trans('messages.sending'), trans('messages.bounce_handlers')],
                'url' => action('Settings\BounceHandlerController@index'),
                'keywords' => [],
            ],
            [
                'names' => [trans('messages.sending'), trans('messages.bounce_handlers'), trans('messages.create')],
                'url' => action('Settings\BounceHandlerController@create'),
                'keywords' => [
                    trans('messages.new'),
                    trans('messages.add'),
                ],
            ],
            [
                'names' => [trans('messages.sending'), trans('messages.feedback_loop_handlers')],
                'url' => action('Settings\FeedbackLoopHandlerController@index'),
                'keywords' => [],
            ],
            [
                'names' => [trans('messages.sending'), trans('messages.feedback_loop_handlers'), trans('messages.create')],
                'url' => action('Settings\FeedbackLoopHandlerController@create'),
                'keywords' => [
                    trans('messages.new'),
                    trans('messages.add'),
                ],
            ],
            [
                'names' => [trans('messages.sending'), trans('messages.email_verification_servers')],
                'url' => action('Settings\EmailVerificationServerController@index'),
                'keywords' => [],
            ],
            [
                'names' => [trans('messages.sending'), trans('messages.email_verification_servers'), trans('messages.create')],
                'url' => action('Settings\EmailVerificationServerController@create'),
                'keywords' => [
                    trans('messages.new'),
                    trans('messages.add'),
                ],
            ],
            [
                'names' => [trans('messages.settings'), trans('messages.languages')],
                'url' => action('Settings\LanguageController@index'),
                'keywords' => [],
            ],
            [
                'names' => [trans('messages.settings'), trans('messages.languages'), trans('messages.add')],
                'url' => action('Settings\LanguageController@create'),
                'keywords' => [
                    trans('messages.new'),
                    trans('messages.add'),
                ],
            ],
            [
                'names' => [trans('messages.settings'), trans('messages.payment_gateways')],
                'url' => action('Settings\PaymentController@index'),
                'keywords' => [
                    'Stripe Braintree Direct PayPal Paystack coinpayments bitcoin digital razorpay',
                    trans('messages.payment'),
                ],
            ],
            [
                'names' => [trans('messages.settings'), trans('messages.plugins')],
                'url' => action('Settings\PluginController@index'),
                'keywords' => [],
            ],
            [
                'names' => [trans('messages.report'), trans('messages.blacklist')],
                'url' => action('Settings\BlacklistController@index'),
                'keywords' => [],
            ],
            [
                'names' => [trans('messages.report'), trans('messages.tracking_log')],
                'url' => action('Settings\TrackingLogController@index'),
                'keywords' => [],
            ],
            [
                'names' => [trans('messages.report'), trans('messages.bounce_log')],
                'url' => action('Settings\BounceLogController@index'),
                'keywords' => [],
            ],
            [
                'names' => [trans('messages.report'), trans('messages.feedback_log')],
                'url' => action('Settings\FeedbackLogController@index'),
                'keywords' => [],
            ],
            [
                'names' => [trans('messages.report'), trans('messages.open_log')],
                'url' => action('Settings\OpenLogController@index'),
                'keywords' => [],
            ],
            [
                'names' => [trans('messages.report'), trans('messages.unsubscribe_log')],
                'url' => action('Settings\UnsubscribeLogController@index'),
                'keywords' => [],
            ],
            [
                'names' => [trans('messages.notifications')],
                'url' => action('Settings\NotificationController@index'),
                'keywords' => [],
            ],
        ];

        $results = filterSearchArray($items, $request->keyword);

        return view('admin.search.general', [
            'keyword' => $request->keyword,
            'results' => $results,
        ]);
    }

    public function customers(Request $request)
    {
        $count = 5;

        $customers = \Modules\Mailing\Models\customer::search($request->keyword)
            ->paginate($count);

        return view('admin.search.customers', [
            'keyword' => $request->keyword,
            'customers' => $customers,
        ]);
    }

    public function templates(Request $request)
    {
        $count = 5;

        $templates = \Modules\Mailing\Models\Template::shared()
            ->notPreserved()
            ->email()
            ->search($request->keyword)
            ->orderBy('templates.name', 'asc')
            ->paginate($count);

        return view('admin.search.templates', [
            'keyword' => $request->keyword,
            'templates' => $templates,
        ]);
    }

    public function plans(Request $request)
    {
        $count = 5;

        $plans = \Modules\Mailing\Models\PlanGeneral::search($request->keyword)
            ->paginate($count);

        return view('admin.search.plans', [
            'keyword' => $request->keyword,
            'plans' => $plans,
        ]);
    }

    public function sending_servers(Request $request)
    {
        $count = 5;

        $sending_servers = \Modules\Mailing\Models\SendingServer::search($request->keyword)
            ->paginate($count);

        return view('admin.search.sending_servers', [
            'keyword' => $request->keyword,
            'sending_servers' => $sending_servers,
        ]);
    }
}
