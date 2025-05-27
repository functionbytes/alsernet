<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log as LaravelLog;
use App\Library\Notification\BackendError;
use App\Models\Notification;
use App;

class IpLocation extends Model
{

    protected $fillable = [
        'country_code', 'country_name', 'region_code',
        'region_name', 'city', 'zipcode',
        'latitude', 'longitude', 'metro_code', 'areacode',
    ];

    public static function add($ip)
    {
        //SELECT * FROM `ip2location_db11` WHERE INET_ATON('116.109.245.204') <= ip_to LIMIT 1
        $location = self::firstOrNew(['ip_address' => $ip]);
        $geoip = App::make('Acelle\Library\Contracts\GeoIpInterface');

        try {
            $geoip->resolveIp($ip);
        } catch (\Exception $e) {
            // Note log
            $title = 'GeoIP Error';
            LaravelLog::warning('Cannot get IP location info for '.$ip.'. Error: '.$e->getMessage());

            Notification::warning([
                'title' => $title,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }

        $location->ip_address = $ip;
        $location->country_code = $geoip->getCountryCode();
        $location->country_name = $geoip->getCountryName();
        $location->region_name = $geoip->getRegionName();
        $location->city = $geoip->getCity();
        $location->zipcode = $geoip->getZipcode();
        $location->latitude = $geoip->getLatitude();
        $location->longitude = $geoip->getLongitude();
        $location->save();

        return $location;
    }

    /**
     * Location name.
     *
     * return Location
     */
    public function name()
    {
        $str = [];
        if (!empty($this->city)) {
            $str[] = $this->city;
        }
        if (!empty($this->region_name)) {
            $str[] = $this->region_name;
        }
        if (!empty($this->country_name)) {
            $str[] = $this->country_name;
        }
        $name = implode(', ', $str);

        return empty($name) ? trans('messages.unknown') : $name;
    }

    public function getFlagPath()
    {
        $path = "/images/flags/" . $this->country_code . ".png";

        if (!file_exists(public_path($path))) {
            $path = "/images/flags/unknown.png";
        }

        return $path;
    }

    public function getCountryName()
    {
        return (empty($this->country_name) ? trans("messages.unknown") : $this->country_name);
    }
}
