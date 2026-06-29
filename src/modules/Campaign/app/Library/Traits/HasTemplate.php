<?php

namespace Modules\Campaign\Library\Traits;

use Cache;
use Exception;
use Illuminate\Support\Facades\File;
use League\Pipeline\PipelineBuilder;
use Modules\Campaign\Library\HtmlHandler\AddDoctype;
use Modules\Campaign\Library\HtmlHandler\AddPreheader;
use Modules\Campaign\Library\HtmlHandler\AppendHtml;
use Modules\Campaign\Library\HtmlHandler\DecodeHtmlSpecialChars;
use Modules\Campaign\Library\HtmlHandler\GenerateSpintax;
use Modules\Campaign\Library\HtmlHandler\GenerateSpintaxForPlainText;
use Modules\Campaign\Library\HtmlHandler\InjectMessageIdToBody;
use Modules\Campaign\Library\HtmlHandler\InjectTrackingPixel;
use Modules\Campaign\Library\HtmlHandler\MakeInlineCss;
use Modules\Campaign\Library\HtmlHandler\ParseRss;
use Modules\Campaign\Library\HtmlHandler\RemoveTitleTag;
use Modules\Campaign\Library\HtmlHandler\ReplaceBareLineFeed;
use Modules\Campaign\Library\HtmlHandler\TransformTag;
use Modules\Campaign\Library\HtmlHandler\TransformUrl;
use Modules\Campaign\Library\HtmlHandler\TransformWidgets;
use Modules\Campaign\Library\Lockable;
use Modules\Campaign\Library\StringHelper;
use Modules\Campaign\Models\Template\Template;
use Modules\CampaignSendingServers\Models\SendingDomain;
use Modules\Core\Models\Setting;
use Soundasleep\Html2Text;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Crypto\DkimSigner;
use Symfony\Component\Mime\Email;

trait HasTemplate
{
    /**
     * Campaign has one template.
     */
    public function template()
    {
        return $this->belongsTo('Modules\Campaign\Models\Template\Template');
    }

    /**
     * Get template.
     */
    public function setTemplate($template, $name = null)
    {
        $campaignTemplate = $template->copyAsPrivate([
            'name' => $name ? $name : trans('messages.campaign.template_name', ['name' => $this->name]),
            'customer_id' => $this->customer_id,
        ]);

        // remove exist template
        if ($this->template) {
            $this->template->deleteAndCleanup();
        }

        $this->template_id = $campaignTemplate->id;
        $this->save();
        $this->refresh();
        if (\Schema::hasColumn($this->getTable(), 'plain')) {
            $this->updatePlainFromHtml();
        }
        if (method_exists($this, 'updateLinks')) {
            $this->updateLinks();
        }
    }

    /**
     * Upload a template.
     */
    public function uploadTemplate($request)
    {
        $template = Template::uploadTemplate($request);
        $this->setTemplate($template);
    }

    /**
     * Check if email has template.
     */
    public function hasTemplate()
    {
        return $this->template()->exists();
    }

    /**
     * Get thumb.
     */
    public function getThumbUrl()
    {
        if ($this->template) {
            return $this->template->getThumbUrl();
        } else {
            return url('images/placeholder.jpg');
        }
    }

    /**
     * Remove email template.
     */
    public function removeTemplate()
    {
        $this->template->deleteAndCleanup();
    }

    /**
     * Update campaign plain text.
     */
    public function updatePlainFromHtml()
    {
        if (! $this->plain) {
            $this->plain = preg_replace('/\s+/', ' ', preg_replace('/\r\n/', ' ', strip_tags($this->getTemplateContent())));
            $this->save();
        }
    }

    /**
     * Set template content.
     */
    public function setTemplateContent($content, $callback = null)
    {
        if (! $this->template) {
            throw new Exception('Cannot set content: campaign/email does not have template!');
        }

        $template = $this->template;
        $template->content = $content;
        $template->save();
        if (! is_null($callback)) {
            $callback($this);
        }
    }

    /**
     * Get template content.
     */
    public function getTemplateContent()
    {
        if (! $this->template) {
            throw new Exception('Cannot get content: campaign/email does not have template!');
        }

        return $this->template->content;
    }

    /**
     * Build Email Custom Headers.
     *
     * @return Hash list of custom headers
     */
    public function getCustomHeaders($subscriber, $server)
    {
        $msgId = StringHelper::generateMessageId(StringHelper::getDomainFromEmail($this->from_email));

        if ($this->isStdClassSubscriber($subscriber)) {
            $unsubscribeUrl = null;
        } else {
            $unsubscribeUrl = $subscriber->generateUnsubscribeUrl($msgId);
            if ($this->trackingDomain) {
                $unsubscribeUrl = $this->trackingDomain->buildTrackingUrl($unsubscribeUrl);
            }
        }

        $headers = [
            'X-Campaign-Id' => $this->uid,
            'X-Subscriber-Id' => $subscriber->uid,
            'X-Campaign-Message-Id' => $msgId,
            'X-Sending-Server-Id' => $server->uid ?? '',
            'Precedence' => 'bulk',
        ];

        if ($unsubscribeUrl) {
            $headers['List-Unsubscribe'] = "<{$unsubscribeUrl}>";
            // RFC 8058: Gmail/Yahoo bulk sender requirement (Feb 2024).
            // Permite "one-click unsubscribe" sin landing page.
            $headers['List-Unsubscribe-Post'] = 'List-Unsubscribe=One-Click';
        } else {
            // Fallback de pruebas: link "vacío" que sólo informa al usuario
            $headers['List-Unsubscribe'] = '<mailto:noreply@'.($this->from_email ? StringHelper::getDomainFromEmail($this->from_email) : 'localhost').'>';
        }

        return $headers;
    }

    /**
     * Check if the given variable is a subscriber object (for actually sending a email)
     * Or a stdClass subscriber (for sending test email).
     *
     * @param  object  $object
     */
    public function isStdClassSubscriber($object)
    {
        return get_class($object) == 'stdClass';
    }

    /**
     * Prepara el mensaje a enviar usando Symfony Mailer.
     *
     * @return array{0: Email, 1: string}
     */
    public function prepareEmail($subscriber, $server = null, $fromCache = false, $expiresInSeconds = 600)
    {
        $customHeaders = $this->getCustomHeaders($subscriber, $server ?: $this);
        $msgId = $customHeaders['X-Campaign-Message-Id'];

        $email = new Email;

        // Custom headers + Message-ID
        $headers = $email->getHeaders();
        $headers->addIdHeader('Message-ID', $msgId);
        foreach ($customHeaders as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $headers->addTextHeader($key, (string) $value);
        }

        // Return-Path (VERP) — sólo si el servidor lo permite y hay handler de bounces
        if (! is_null($server) && method_exists($server, 'allowCustomReturnPath') && $server->allowCustomReturnPath()) {
            $returnPath = method_exists($server, 'getVerp')
                ? $server->getVerp($subscriber->email)
                : null;
            if ($returnPath) {
                $email->returnPath($returnPath);
            }
        }

        // Subject / From / To / Reply-To
        $email->subject($this->getSubject($subscriber, $msgId));

        if (! empty($this->from_name)) {
            $email->from(new Address($this->from_email, $this->from_name));
        } else {
            $email->from($this->from_email);
        }

        $email->to(new Address($subscriber->email));

        if (! empty($this->reply_to)) {
            $email->replyTo($this->reply_to);
        }

        // Bcc/Cc globales desde Setting
        if (class_exists(Setting::class)) {
            if (! empty(Setting::get('campaign.bcc'))) {
                $bcc = array_filter(preg_split('/\s*,\s*/', Setting::get('campaign.bcc')));
                if (! empty($bcc)) {
                    $email->bcc(...$bcc);
                }
            }
            if (! empty(Setting::get('campaign.cc'))) {
                $cc = array_filter(preg_split('/\s*,\s*/', Setting::get('campaign.cc')));
                if (! empty($cc)) {
                    $email->cc(...$cc);
                }
            }
        }

        // Body: HTML + text alternativo (regular) o sólo plain (plain-text)
        if (is_null($this->type) || $this->type === self::TYPE_REGULAR) {
            $html = $this->getHtmlContent($subscriber, $msgId, $server, $fromCache, $expiresInSeconds);
            $plain = Html2Text::convert($html, ['ignore_errors' => true]);

            $email->html($html, 'utf-8');
            $email->text($plain, 'utf-8');
        } else {
            $plain = $this->getPlainContent($subscriber, $msgId, $server);
            $email->text($plain, 'utf-8');
        }

        // Adjuntos. Compat con Email (automation) y Campaign.
        $attachableSource = $this->attachments ?? null;
        if ($attachableSource) {
            foreach ($attachableSource as $file) {
                if (is_object($file) && isset($file->file) && is_file($file->file)) {
                    $email->attachFromPath($file->file);
                }
            }
        } elseif (method_exists($this, 'getAttachmentPath')) {
            $path = $this->getAttachmentPath();
            if (is_dir($path)) {
                foreach (File::allFiles($path) as $file) {
                    $email->attachFromPath((string) $file);
                }
            }
        }

        // DKIM signing real con Symfony\Component\Mime\Crypto\DkimSigner.
        if ($this->sign_dkim && ! empty($this->from_email)) {
            $domain = $this->findSendingDomain($this->from_email);
            if ($domain && ! empty($domain->dkim_private_key)) {
                $signer = new DkimSigner(
                    $domain->dkim_private_key,
                    $domain->name,
                    $domain->dkim_selector ?: 'mail',
                );
                $email = $signer->sign($email);
            }
        }

        return [$email, $msgId];
    }

    /**
     * Get tagged Subject.
     *
     * @return string
     */
    public function getSubject($subscriber = null, $msgId = null)
    {
        $pipeline = new PipelineBuilder;

        if (! is_null($subscriber)) {
            if (is_null($msgId)) {
                throw new Exception('MessageID must not be null');
            }
            $pipeline->add(new TransformTag($this, $subscriber, $msgId));
        }

        $pipeline->add(new DecodeHtmlSpecialChars);
        $pipeline->add(new GenerateSpintaxForPlainText);
        $pipeline->add(new ReplaceBareLineFeed);

        return $pipeline->build()->process($this->subject);
    }

    /**
     * Check if email footer enabled.
     *
     * @return string
     *
     * @deprecated this is a very poorly designed function with dependencies session!
     *
     * @todo so, we are adding if/else to facilitate testing only
     */
    public function footerEnabled()
    {
        if (is_null($this->customer)) {
            return;
        }

        return (get_tmp_quota($this->customer, 'email_footer_enabled') == 'yes') ? true : false;
    }

    /**
     * Get HTML footer.
     *
     * @return string
     *
     * @deprecated this is a very poorly designed function with dependencies session!
     *
     * @todo so, we are adding if/else to facilitate testing only
     */
    public function getHtmlFooter()
    {
        if (is_null($this->customer)) {
            return;
        }

        return get_tmp_quota($this->customer, 'html_footer');
    }

    /**
     * Find sending domain from email.
     *
     * @return mixed
     */
    public function findSendingDomain($email)
    {
        $domainName = substr(strrchr($email, '@'), 1);

        if ($domainName == false) {
            return;
        }

        // Recursos globales: buscar en sending_server_domains directamente.
        return SendingDomain::where('name', $domainName)
            ->where('signing_enabled', true)
            ->where('status', SendingDomain::STATUS_VERIFIED)
            ->first();
    }

    /**
     * Sign the message with DKIM.
     *
     * @return mixed
     */
    public function sign($message)
    {
        $sendingDomain = $this->findSendingDomain($this->from_email);

        if (is_null($sendingDomain)) {
            return $message;
        }

        $privateKey = $sendingDomain->dkim_private;
        $domainName = $sendingDomain->name;
        $selector = $sendingDomain->getDkimSelectorParts()[0];
        $signer = new \Swift_Signers_DKIMSigner($privateKey, $domainName, $selector);
        $signer->ignoreHeader('Return-Path');
        $message->attachSigner($signer);

        return $message;
    }

    public function getCachedHtmlId()
    {
        return "{$this->uid}-html";
    }

    public function clearTemplateCache()
    {
        Cache::forget($this->getCachedHtmlId());
    }

    /**
     * Build Email HTML content.
     *
     * @return string
     */
    public function getHtmlContent($subscriber = null, $msgId = null, $server = null, $fromCache = false, $expiresInSeconds = 600)
    {
        $baseHtml = $this->getBaseHtmlContent($fromCache, $expiresInSeconds);

        // Bind subscriber/message/server information to email content
        $pipeline = new PipelineBuilder;
        $pipeline->add(new TransformTag($this, $subscriber, $msgId, $server));

        if (! $this->isStageExcluded(InjectTrackingPixel::class)) {
            $pipeline->add(new InjectTrackingPixel($this, $msgId));
        }

        $pipeline->add(new InjectMessageIdToBody($msgId));

        if (! $this->isStageExcluded(TransformUrl::class)) {
            $pipeline->add(new TransformUrl($this->template, $msgId, $this->trackingDomain));
        }

        $pipeline->add(new DecodeHtmlSpecialChars);
        $pipeline->add(new GenerateSpintax);
        $pipeline->add(new ReplaceBareLineFeed);

        // ReplaceBareLineFeed should go into the base HTML content instead
        // However, we need it here, as the last step, to make sure there is no bare LF produced

        // Actually push HTML to pipeline for processing
        $html = $pipeline->build()->process($baseHtml);

        // Return subscriber's bound html
        return $html;
    }

    // Return the HTML content which has been processed through base handlers (pipeline)
    // Which is not associated with any subscriber/message/server
    public function getBaseHtmlContent($fromCache = false, $expiresInSeconds = 600)
    {
        if (! $this->template) {
            throw new Exception('No template available');
        }

        $cacheId = $this->getCachedHtmlId();
        $updateCacheFlag = $fromCache && ! Cache::has($cacheId);
        $html = null;

        if (! $fromCache || $updateCacheFlag) {
            $pipeline = new PipelineBuilder;
            $pipeline->add(new AddDoctype);
            $pipeline->add(new AddPreheader($this->preheader));
            $pipeline->add(new RemoveTitleTag);
            $pipeline->add(new AppendHtml($this->getHtmlFooter()));
            $pipeline->add(new ParseRss);
            $pipeline->add(new MakeInlineCss($this->template->findCssFiles()));
            $pipeline->add(new TransformWidgets);
            // $pipeline->add(new TransformTag($this, $subscriber, $msgId, $server));
            // $pipeline->add(new InjectTrackingPixel($this, $msgId));
            // $pipeline->add(new TransformUrl($this->template, $msgId, $this->trackingDomain));
            // $html = $this->template->wooTransform($html);
            $html = $pipeline->build()->process($this->getTemplateContent());
        }

        if ($updateCacheFlag) {
            $lockfile = storage_path('locks/campaign-cache-'.$this->uid);
            $lock = new Lockable($lockfile);

            $lock->getExclusiveLock(function ($f) use ($cacheId, $html, $expiresInSeconds) {
                Cache::put($cacheId, $html, $expiresInSeconds);
            }, $timeoutSeconds = 3, $timeoutCallback = function () {
                // echo "Quit me mememem";
                // just quit, do not throw exception
            });
        }

        // It is important to return $html in priority here, as cache update may not work!
        return $html ?: Cache::get($cacheId);
    }

    /**
     * Build Email HTML content.
     * Notice: this method is used for PLAIN CAMPAIGN only. To extract plain content from HTML, use Html2Text instead
     *
     * @return string
     */
    public function getPlainContent($subscriber = null, $msgId = null, $server = null)
    {
        $plain = $this->plain.$this->getPlainTextFooter();
        $pipeline = new PipelineBuilder;

        if (! is_null($subscriber)) {
            $pipeline->add(new TransformTag($this, $subscriber, $msgId, $server));
        }

        $pipeline->add(new DecodeHtmlSpecialChars);
        $pipeline->add(new GenerateSpintaxForPlainText);
        $pipeline->add(new ReplaceBareLineFeed);
        $plain = $pipeline->build()->process($plain);

        return $plain;
    }

    /**
     * Get PLAIN TEXT footer.
     *
     * @return string
     *
     * @deprecated this is a very poorly designed function with dependencies session!
     *
     * @todo so, we are adding if/else to facilitate testing only
     */
    public function getPlainTextFooter()
    {
        if (is_null($this->customer)) {
            return;
        }

        return get_tmp_quota($this->customer, 'plain_text_footer');
    }

    /**
     * Create a stdClass subscriber (for sending a campaign test email)
     * The campaign sending functions take a subscriber object as input
     * However, a test email address is not yet a subscriber object, so we have to build a fake stdClass object
     * which can be used as a real subscriber.
     *
     * @param  array  $subscriber
     */
    public function createStdClassSubscriber($subscriber)
    {
        // default attributes that are required
        $jsonObj = [
            'uid' => uniqid(),
        ];

        // append the customer specified attributes and build a stdClass object
        $stdObj = json_decode(json_encode(array_merge($jsonObj, $subscriber)));

        return $stdObj;
    }

    public function makeTrackingPixel($msgId)
    {
        if (! is_null($msgId)) {
            $url = route('campaign.track.open', ['messageId' => $msgId]);
            if ($this->trackingDomain) {
                $url = $this->trackingDomain->buildTrackingUrl($url);
            }
        } else {
            $url = $this->makeSampleLink();
        }

        return '<img alt="" src="'.$url.'" width="1" height="1" style="display:none;visibility:hidden" />';
    }

    public function makeSampleLink()
    {
        $sampleLink = route('campaign_message', ['message' => StringHelper::base64UrlEncode(trans('messages.email.test_link_note'))]);
        if ($this->trackingDomain) {
            $sampleLink = $this->trackingDomain->buildTrackingUrl($sampleLink);
        }

        return $sampleLink;
    }
}
