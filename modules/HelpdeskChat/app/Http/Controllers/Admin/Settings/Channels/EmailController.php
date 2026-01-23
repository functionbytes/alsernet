<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Settings\Channels;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Accounts\Inbox;
use Modules\HelpdeskChat\Models\Channels\Email;

class EmailController extends Controller
{
    /**
     * Display a listing of email channels.
     */
    public function index()
    {
        $emails = auth()->user()->account
            ->inboxes()
            ->where('channel_type', Email::class)
            ->with('channel')
            ->get();

        return view('helpdeskchat::admin.settings.channels.emails.index', compact('emails'));
    }

    /**
     * Show the form for creating a new email channel.
     */
    public function create()
    {
        return view('helpdeskchat::admin.settings.channels.emails.create');
    }

    /**
     * Store a newly created email channel.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'inbox_name' => 'required|string|max:255',
            'email' => 'required|email|unique:channel_emails,email',
            'provider' => 'nullable|in:google,microsoft,zoho,custom',

            // IMAP settings
            'imap_enabled' => 'boolean',
            'imap_address' => 'required_if:imap_enabled,true|nullable|string',
            'imap_port' => 'required_if:imap_enabled,true|nullable|integer',
            'imap_login' => 'required_if:imap_enabled,true|nullable|string',
            'imap_password' => 'required_if:imap_enabled,true|nullable|string',
            'imap_enable_ssl' => 'boolean',

            // SMTP settings
            'smtp_enabled' => 'boolean',
            'smtp_address' => 'required_if:smtp_enabled,true|nullable|string',
            'smtp_port' => 'required_if:smtp_enabled,true|nullable|integer',
            'smtp_login' => 'required_if:smtp_enabled,true|nullable|string',
            'smtp_password' => 'required_if:smtp_enabled,true|nullable|string',
            'smtp_domain' => 'nullable|string',
            'smtp_authentication' => 'nullable|in:plain,login,cram_md5',
            'smtp_enable_starttls_auto' => 'boolean',
            'smtp_enable_ssl_tls' => 'boolean',
            'smtp_openssl_verify_mode' => 'nullable|in:none,peer,client_once,fail_if_no_peer_cert',
        ]);

        // Generate unique forward_to_email
        $forwardToEmail = $this->generateForwardEmail();

        // Create the email channel
        $email = Email::create([
            'account_id' => $request->user()->account_id,
            'email' => $validated['email'],
            'forward_to_email' => $forwardToEmail,
            'provider' => $validated['provider'] ?? 'custom',
            'imap_enabled' => $validated['imap_enabled'] ?? false,
            'imap_address' => $validated['imap_address'] ?? null,
            'imap_port' => $validated['imap_port'] ?? null,
            'imap_login' => $validated['imap_login'] ?? null,
            'imap_password' => $validated['imap_password'] ?? null,
            'imap_enable_ssl' => $validated['imap_enable_ssl'] ?? true,
            'smtp_enabled' => $validated['smtp_enabled'] ?? false,
            'smtp_address' => $validated['smtp_address'] ?? null,
            'smtp_port' => $validated['smtp_port'] ?? null,
            'smtp_login' => $validated['smtp_login'] ?? null,
            'smtp_password' => $validated['smtp_password'] ?? null,
            'smtp_domain' => $validated['smtp_domain'] ?? null,
            'smtp_authentication' => $validated['smtp_authentication'] ?? 'login',
            'smtp_enable_starttls_auto' => $validated['smtp_enable_starttls_auto'] ?? true,
            'smtp_enable_ssl_tls' => $validated['smtp_enable_ssl_tls'] ?? false,
            'smtp_openssl_verify_mode' => $validated['smtp_openssl_verify_mode'] ?? 'none',
        ]);

        // Create inbox for this channel
        $inbox = Inbox::create([
            'account_id' => $request->user()->account_id,
            'name' => $validated['inbox_name'],
            'channel_id' => $email->id,
            'channel_type' => Email::class,
        ]);

        return redirect()->route('admin.helpdesk.channels.emails.index')
            ->with('success', 'Email channel created successfully!');
    }

    /**
     * Display the specified email channel.
     */
    public function show(Email $email)
    {
        $email->load('inbox');

        return view('helpdeskchat::admin.settings.channels.emails.show', compact('email'));
    }

    /**
     * Show the form for editing the specified email channel.
     */
    public function edit(Email $email)
    {
        return view('helpdeskchat::admin.settings.channels.emails.edit', compact('email'));
    }

    /**
     * Update the specified email channel.
     */
    public function update(Request $request, Email $email)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:channel_emails,email,'.$email->id,
            'provider' => 'nullable|in:google,microsoft,zoho,custom',

            // IMAP settings
            'imap_enabled' => 'boolean',
            'imap_address' => 'required_if:imap_enabled,true|nullable|string',
            'imap_port' => 'required_if:imap_enabled,true|nullable|integer',
            'imap_login' => 'required_if:imap_enabled,true|nullable|string',
            'imap_password' => 'nullable|string',
            'imap_enable_ssl' => 'boolean',

            // SMTP settings
            'smtp_enabled' => 'boolean',
            'smtp_address' => 'required_if:smtp_enabled,true|nullable|string',
            'smtp_port' => 'required_if:smtp_enabled,true|nullable|integer',
            'smtp_login' => 'required_if:smtp_enabled,true|nullable|string',
            'smtp_password' => 'nullable|string',
            'smtp_domain' => 'nullable|string',
            'smtp_authentication' => 'nullable|in:plain,login,cram_md5',
            'smtp_enable_starttls_auto' => 'boolean',
            'smtp_enable_ssl_tls' => 'boolean',
            'smtp_openssl_verify_mode' => 'nullable|in:none,peer,client_once,fail_if_no_peer_cert',
        ]);

        // Only update password if provided
        $updateData = $validated;
        if (empty($validated['imap_password'])) {
            unset($updateData['imap_password']);
        }
        if (empty($validated['smtp_password'])) {
            unset($updateData['smtp_password']);
        }

        $email->update($updateData);

        return redirect()->route('admin.helpdesk.channels.emails.index')
            ->with('success', 'Email channel updated successfully!');
    }

    /**
     * Remove the specified email channel.
     */
    public function destroy(Email $email)
    {
        $email->inbox()->delete();
        $email->delete();

        return redirect()->route('admin.helpdesk.channels.emails.index')
            ->with('success', 'Email channel deleted successfully!');
    }

    /**
     * Test SMTP connection.
     */
    public function testSmtp(Request $request, Email $email)
    {
        try {
            $transport = (new \Swift_SmtpTransport($email->smtp_address, $email->smtp_port))
                ->setUsername($email->smtp_login)
                ->setPassword($email->smtp_password);

            if ($email->smtp_enable_ssl_tls) {
                $transport->setEncryption('ssl');
            } elseif ($email->smtp_enable_starttls_auto) {
                $transport->setEncryption('tls');
            }

            $mailer = new \Swift_Mailer($transport);
            $mailer->getTransport()->start();

            return back()->with('success', 'SMTP connection successful!');
        } catch (\Exception $e) {
            return back()->with('error', 'SMTP connection failed: '.$e->getMessage());
        }
    }

    /**
     * Generate a unique forward email address.
     */
    private function generateForwardEmail(): string
    {
        $domain = config('app.inbound_email_domain', 'mail.'.parse_url(config('app.url'), PHP_URL_HOST));

        return Str::random(16).'@'.$domain;
    }
}
