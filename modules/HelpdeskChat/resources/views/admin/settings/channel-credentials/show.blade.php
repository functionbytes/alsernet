@extends('layouts.admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>
                    <i class="bi {{ $channelStatus['icon'] }}"></i>
                    {{ $channelStatus['name'] }}
                </h2>
            </div>
            <a href="{{ route('admin.settings.channel-credentials.index') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to All Channels
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Configuration Status</h5>
            </div>
            <div class="card-body">
                @if($isConfigured)
                    <div class="alert alert-success">
                        <i class="fa fa-check-circle"></i>
                        <strong>✓ Configured</strong>
                        <p class="mb-0 mt-2">This channel is properly configured and ready to use.</p>
                    </div>
                @else
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle"></i>
                        <strong>⚠ Not Configured</strong>
                        <p class="mb-0 mt-2">
                            The following credentials are missing:
                            <code>{{ implode(', ', $missing) }}</code>
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Setup Instructions</h5>
            </div>
            <div class="card-body">
                @switch($channel)
                    @case('facebook')
                        <h6>Step 1: Create a Facebook App</h6>
                        <ol>
                            <li>Go to <a href="https://developers.facebook.com/apps/" target="_blank">Facebook Developers</a></li>
                            <li>Click "Create App"</li>
                            <li>Choose "Consumer" as app type</li>
                            <li>Fill in the app details</li>
                        </ol>

                        <h6 class="mt-4">Step 2: Get Your Credentials</h6>
                        <ol>
                            <li>In App Dashboard, go to Settings → Basic</li>
                            <li>Copy your <strong>App ID</strong> to <code>FACEBOOK_APP_ID</code></li>
                            <li>Copy your <strong>App Secret</strong> to <code>FACEBOOK_APP_SECRET</code></li>
                        </ol>

                        <h6 class="mt-4">Step 3: Configure Messenger Product</h6>
                        <ol>
                            <li>Go to your App Dashboard</li>
                            <li>Add the "Messenger" product</li>
                            <li>Configure your webhook URL: <code>{{ url('/webhooks/facebook') }}</code></li>
                            <li>Set <code>FACEBOOK_VERIFY_TOKEN</code> to any secure string</li>
                        </ol>

                        <h6 class="mt-4">Step 4: Get Page Access Token</h6>
                        <ol>
                            <li>In Messenger settings, click "Add or remove pages"</li>
                            <li>Select your Facebook Page</li>
                            <li>Grant "Manage messages" permission</li>
                        </ol>

                        <div class="alert alert-info mt-4">
                            <strong>Documentation:</strong>
                            <a href="https://developers.facebook.com/docs/messenger-platform/quickstart" target="_blank">
                                Facebook Messenger Platform
                            </a>
                        </div>
                        @break

                    @case('instagram')
                        <h6>Step 1: Use Your Facebook App</h6>
                        <p>Instagram uses the same Meta app as Facebook. Make sure you have Facebook configured first.</p>

                        <h6 class="mt-4">Step 2: Configure Instagram Product</h6>
                        <ol>
                            <li>Go to your App Dashboard</li>
                            <li>Add the "Instagram" product</li>
                            <li>In Instagram Messaging, click "Add or remove pages"</li>
                            <li>Connect your Instagram Business Account</li>
                        </ol>

                        <h6 class="mt-4">Step 3: Optional - Separate Credentials</h6>
                        <p>If you want to use separate Instagram app credentials:</p>
                        <ol>
                            <li>Create a new app on Facebook Developers</li>
                            <li>Add your Instagram App ID to <code>INSTAGRAM_APP_ID</code></li>
                            <li>Add your Instagram App Secret to <code>INSTAGRAM_APP_SECRET</code></li>
                        </ol>

                        <div class="alert alert-info mt-4">
                            <strong>Documentation:</strong>
                            <a href="https://developers.facebook.com/docs/instagram-api/getting-started" target="_blank">
                                Instagram Graph API
                            </a>
                        </div>
                        @break

                    @case('whatsapp')
                        <h6>Step 1: Choose Your WhatsApp Provider</h6>
                        <p>We support two providers:</p>
                        <ul>
                            <li><strong>WhatsApp Cloud API</strong> (Meta) - Recommended</li>
                            <li><strong>360Dialog</strong> - Alternative provider</li>
                        </ul>

                        <h6 class="mt-4">Step 2: Get Your Credentials</h6>
                        <p>Depending on your provider:</p>
                        <ul>
                            <li><strong>Cloud API:</strong> Go to <a href="https://developers.facebook.com/" target="_blank">Meta for Developers</a></li>
                            <li><strong>360Dialog:</strong> Go to <a href="https://www.360dialog.com/" target="_blank">360Dialog Dashboard</a></li>
                        </ul>

                        <h6 class="mt-4">Step 3: Configure Environment Variables</h6>
                        <ol>
                            <li>Add <code>WHATSAPP_VERIFY_TOKEN</code> - any secure string</li>
                            <li>Add <code>WHATSAPP_APP_SECRET</code> - from your provider dashboard</li>
                        </ol>

                        <h6 class="mt-4">Step 4: Configure Webhook</h6>
                        <ol>
                            <li>In your provider settings, set webhook URL to: <code>{{ url('/webhooks/whatsapp') }}</code></li>
                            <li>Set verify token to the value of <code>WHATSAPP_VERIFY_TOKEN</code></li>
                        </ol>

                        <div class="alert alert-info mt-4">
                            <strong>Documentation:</strong>
                            <a href="https://developers.facebook.com/docs/whatsapp/cloud-api/get-started" target="_blank">
                                WhatsApp Cloud API
                            </a>
                        </div>
                        @break

                    @case('telegram')
                        <h6>Step 1: Create a Telegram Bot</h6>
                        <ol>
                            <li>Open Telegram and search for <strong>@BotFather</strong></li>
                            <li>Send <code>/newbot</code> command</li>
                            <li>Follow the prompts to create your bot</li>
                        </ol>

                        <h6 class="mt-4">Step 2: Get Your Bot Token</h6>
                        <ol>
                            <li>BotFather will provide your bot token</li>
                            <li>Add it to <code>TELEGRAM_BOT_TOKEN</code> in your .env file</li>
                        </ol>

                        <h6 class="mt-4">Step 3: Configure Webhook</h6>
                        <ol>
                            <li>We automatically set up the webhook when you create a Telegram channel</li>
                            <li>Webhook URL: <code>{{ url('/webhooks/telegram') }}</code></li>
                        </ol>

                        <div class="alert alert-info mt-4">
                            <strong>Documentation:</strong>
                            <a href="https://core.telegram.org/bots" target="_blank">
                                Telegram Bot API
                            </a>
                        </div>
                        @break

                    @case('email')
                        <h6>Step 1: Choose Your Email Provider</h6>
                        <p>We support Gmail and Outlook with OAuth 2.0</p>

                        <h6 class="mt-4">Gmail Setup</h6>
                        <ol>
                            <li>Go to <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
                            <li>Create a new project</li>
                            <li>Enable "Gmail API"</li>
                            <li>Create OAuth 2.0 credentials (Desktop app)</li>
                            <li>Add to <code>GMAIL_CLIENT_ID</code> and <code>GMAIL_CLIENT_SECRET</code></li>
                        </ol>

                        <h6 class="mt-4">Outlook Setup</h6>
                        <ol>
                            <li>Go to <a href="https://portal.azure.com/" target="_blank">Azure Portal</a></li>
                            <li>Register a new application</li>
                            <li>Create client credentials</li>
                            <li>Add to <code>OUTLOOK_CLIENT_ID</code> and <code>OUTLOOK_CLIENT_SECRET</code></li>
                        </ol>

                        <div class="alert alert-info mt-4">
                            <strong>Documentation:</strong>
                            <a href="https://support.google.com/accounts/answer/185833" target="_blank">
                                Gmail Security
                            </a>
                        </div>
                        @break

                    @case('twilio')
                        <h6>Step 1: Create a Twilio Account</h6>
                        <ol>
                            <li>Go to <a href="https://www.twilio.com/" target="_blank">Twilio.com</a></li>
                            <li>Sign up for a free account</li>
                            <li>Verify your phone number</li>
                        </ol>

                        <h6 class="mt-4">Step 2: Get Your Credentials</h6>
                        <ol>
                            <li>In the Twilio Console, go to Account</li>
                            <li>Copy your <strong>Account SID</strong> to <code>TWILIO_ACCOUNT_SID</code></li>
                            <li>Copy your <strong>Auth Token</strong> to <code>TWILIO_AUTH_TOKEN</code></li>
                            <li>Get a Twilio phone number and add to <code>TWILIO_PHONE_NUMBER</code></li>
                        </ol>

                        <h6 class="mt-4">Step 3: Configure Webhooks</h6>
                        <ol>
                            <li>In Phone Numbers → Manage Numbers, select your number</li>
                            <li>Set webhook URL: <code>{{ url('/webhooks/twilio/sms') }}</code></li>
                            <li>Set webhook method to POST</li>
                        </ol>

                        <div class="alert alert-info mt-4">
                            <strong>Documentation:</strong>
                            <a href="https://www.twilio.com/docs/sms/quickstart" target="_blank">
                                Twilio SMS
                            </a>
                        </div>
                        @break

                    @default
                        <p class="text-muted">No setup instructions available for this channel.</p>
                @endswitch
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4 @if($isConfigured) border-success @else border-danger @endif">
            <div class="card-header @if($isConfigured) bg-success @else bg-danger @endif">
                <h5 class="mb-0 text-white">
                    @if($isConfigured)
                        <i class="fa fa-check-circle"></i> Ready to Use
                    @else
                        <i class="bi bi-exclamation-circle"></i> Action Required
                    @endif
                </h5>
            </div>
            <div class="card-body">
                @if($isConfigured)
                    <p class="text-success mb-2">
                        <i class="fa fa-check-circle-fill"></i>
                        All credentials are properly configured.
                    </p>
                    <p class="small text-muted">
                        You can now create {{ $channelStatus['name'] }} channels in your inbox settings.
                    </p>
                @else
                    <p class="text-danger mb-3">
                        <strong>Missing Credentials:</strong>
                    </p>
                    <ul class="small mb-3">
                        @foreach($missing as $credential)
                            <li><code>{{ $credential }}</code></li>
                        @endforeach
                    </ul>
                    <p class="small text-muted">
                        Follow the setup instructions on the left to configure these credentials.
                    </p>
                @endif
            </div>
        </div>

        <div class="card bg-light">
            <div class="card-body">
                <h6 class="card-title"><i class="bi bi-shield-check"></i> Security Tips</h6>
                <ul class="small mb-0">
                    <li>Never share your App Secret</li>
                    <li>Use strong, unique tokens</li>
                    <li>Rotate credentials periodically</li>
                    <li>Use environment-specific configs</li>
                    <li>Monitor webhook activity</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
