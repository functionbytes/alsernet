<?php

namespace App\Models\Campaign;

use Illuminate\Database\Eloquent\Model;
use Mika56\SPFCheck\DNSRecordGetterDirect;
use Mika56\SPFCheck\DNSRecordGetter;
use Illuminate\Support\Facades\Log;
use App\Library\Traits\HasUid;
use App\Library\StringHelper;
use Mika56\SPFCheck\SPFCheck;
use App\Library\MtaSync;
use GuzzleHttp\Client;
use Validator;
use Exception;

class CampaignTrackingDomain extends Model
{
    use HasUid;

    public const STATUS_VERIFIED = 'verified';
    public const STATUS_UNVERIFIED = 'unverified';

    public const VERIFICATION_METHOD_HOST = 'host';
    public const VERIFICATION_METHOD_CNAME = 'cname';
    public function customer()
    {
        return $this->belongsTo('Acelle\Model\Customer');
    }

    public static $itemsPerPage = 25;

    public static function boot()
    {
        parent::boot();

        // Create uid when creating list.
        static::creating(function ($item) {
            // Create new uid
            $uid = uniqid();
            $item->uid = $uid;

            // Default status = inactive (until domain verified)
            $item->status = self::STATUS_UNVERIFIED;
        });
    }

    protected $fillable = [
        'name', 'scheme', 'verification_method'
    ];

    public static function createFromRequest($request)
    {
        $rules = [
            'name' => [
                'required',
                'url',
                'max:255',
                function ($attribute, $value, $fail) {
                    $path = parse_url($value, PHP_URL_PATH);
                    if (!is_null($path)) {
                        $fail(trans('messages.tracking_domain.validation.error.path'));
                    }

                    $host = parse_url($value, PHP_URL_HOST);
                    $matched = preg_match('/^(?!\-)(?:(?:[a-zA-Z\d][a-zA-Z\d\-]{0,61})?[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/', $host);

                    if (!$matched) {
                        $fail(trans('messages.tracking_domain.validation.error.name'));
                    }
                },
            ],
        ];
        // save posted data
        $scheme = $request->input('scheme');
        $name = trim(trim($request->input('name')), '/');
        $attributes = [
            'scheme' => $scheme,
            'name' => "{$scheme}://{$name}",
            'verification_method' => $request->input('verification_method'),
        ];

        $validator = Validator::make($attributes, $rules);
        if ($validator->fails()) {
            return [null, $validator];
        }

        // Restore name to domain name and host
        $attributes['name'] = $name;

        // Get current user
        $domain = new self();
        // Save current user info
        $domain->fill($attributes);
        $domain->customer_id = $request->user()->customer->id;
        $domain->status = self::STATUS_UNVERIFIED;
        $domain->save();

        return [$domain, $validator];
    }

    public static function filter($request)
    {
        $user = $request->user();
        $query = self::select('tracking_domains.*');

        // Keyword
        if (!empty(trim($request->keyword))) {
            foreach (explode(' ', trim($request->keyword)) as $keyword) {
                $query = $query->where(function ($q) use ($keyword) {
                    $q->orwhere('tracking_domains.name', 'like', '%'.$keyword.'%');
                });
            }
        }

        // filters
        $filters = $request->all();

        // filter by status
        if (!empty($request->status)) {
            $query = $query->where('tracking_domains.status', '=', $request->status);
        }

        // by customer
        if (!empty($request->customer_id)) {
            $query = $query->where('tracking_domains.customer_id', '=', $request->customer_id);
        }

        return $query;
    }

    public static function search($request, $server = null)
    {
        $query = self::filter($request, $server);

        if (!empty($request->sort_order)) {
            $query = $query->orderBy($request->sort_order, $request->sort_direction);
        }

        return $query;
    }

    public function scopeVerified($query)
    {
        return $query->where('status', '=', self::STATUS_VERIFIED);
    }

    public function isVerified()
    {
        return $this->status == self::STATUS_VERIFIED;
    }

    public function getFQDN($trailingDot = true)
    {
        return $this->name . (($trailingDot) ? '.' : '');
    }

    public function getUrl()
    {
        return $this->scheme.'://'.$this->name;
    }

    public function getVerificationUrl()
    {
        return $this->getUrl().route('appkey', [], false);
    }

    public function setVerified()
    {
        $this->status = self::STATUS_VERIFIED;
    }

    public function verify()
    {
        try {
            $url = $this->getUrl();
            $verifyUrl = "$url/ok";

            $client = curl_init();
            curl_setopt_array($client, array(
                CURLOPT_URL => $verifyUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_SSL_VERIFYPEER => false
            ));

            $result = curl_exec($client);
            curl_close($client);

            if ($result == 'ok') {
                $this->setVerified();
                $this->save();
                return true;
            } else {
                Log::warning(sprintf('Failed verifying tracking domain %s. Error: %s', $url, $result));
                return false;
            }
        } catch (\Exception $ex) {
            Log::warning(sprintf('Error verifying tracking domain %s. Error: %s', $url, $ex->getMessage()));
            return false;
        }
    }

    public function verifyCname($debug = false)
    {
        try {
            $client = new Client(['verify' => false]);
            $response = $client->request('GET', $this->getVerificationUrl());

            if ((string)$response->getBody() == get_app_identity()) {
                $this->setVerified();
                $this->save();
            } else {
                if ($debug) {
                    echo "This app's identity: " . get_app_identity();
                    echo "<br>";
                    echo "Retrieved identity: " . $response->getBody();
                    die;
                }
                throw new \Exception("Verification failed");
            }
            return true;
        } catch (\Exception $ex) {
            if ($debug) {
                echo "This app's identity: " . get_app_identity();
                echo "<br>";
                echo $ex->getMessage();
                die;
            }
            // loggging here
            return false;
        }
    }

    public function buildTrackingUrl(string $url)
    {
        if (!parse_url($url, PHP_URL_HOST)) {
            throw new Exception('Cannot build tracking URL with "'.$url.'", a valid URL is required (with leading http:// or https:// or //');
        }

        // Already a tracking domain's URL
        if (strpos($url, $this->getUrl()) === 0) {
            return $url;
        }

        if ($this->verification_method == self::VERIFICATION_METHOD_HOST) {
            // Replace the default host with the tracking host
            // For example, transform "http://myapp.com/foo" to "http://tracking.com/aHR0cDovL215YXBwLmNvbS9mb28"
            $encodedUrl = StringHelper::base64UrlEncode($url);
            $final = join_url($this->getUrl(), $encodedUrl);
        } elseif ($this->verification_method == self::VERIFICATION_METHOD_CNAME) {
            // Replace the default host with the tracking host
            // For example, transform "http://myapp.com/foo" to "http://tracking.com/foo"
            $default = config('app.url');
            $default = trim($default, '/'); // make sure there is no trailing slash "/"
            $final = str_replace($default, $this->getUrl(), $url);
        }

        return $final;
    }

    public function verifyByDns()
    {
        return $this->verification_method == self::VERIFICATION_METHOD_CNAME;
    }


}
