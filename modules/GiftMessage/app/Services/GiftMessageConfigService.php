<?php

namespace Modules\GiftMessage\Services;

use Illuminate\Http\UploadedFile;
use Modules\GiftMessage\Models\GiftMessageConfig;

class GiftMessageConfigService
{
    private const DISK = 'public';

    private const IMAGES_FOLDER = 'giftmessage/images';

    private const POSITION_KEYS = ['t1_x', 't1_y', 't1_w', 't1_h', 't2_x', 't2_y', 't2_w', 't2_h'];

    private const SCOPE_PREFIXES = ['envelope' => 'env', 'card' => 'card'];

    private const FONT_FIELDS = [
        'env_t1_font', 'env_t1_size', 'env_t1_color', 'env_t1_opacity',
        'env_t2_font', 'env_t2_size', 'env_t2_color', 'env_t2_opacity',
        'card_t1_font', 'card_t1_size', 'card_t1_color', 'card_t1_opacity',
        'card_t2_font', 'card_t2_size', 'card_t2_color', 'card_t2_opacity',
    ];

    public function current(): GiftMessageConfig
    {
        return GiftMessageConfig::current();
    }

    /**
     * @param  array{envelope_image?: ?UploadedFile, card_image?: ?UploadedFile}  $files
     */
    public function uploadImages(array $files): GiftMessageConfig
    {
        $config = $this->current();
        $update = [];

        if ($files['envelope_image'] ?? null) {
            $update['envelope_image'] = $this->storeImage($files['envelope_image'], 'envelope');
        }

        if ($files['card_image'] ?? null) {
            $update['card_image'] = $this->storeImage($files['card_image'], 'card');
        }

        if ($update !== []) {
            $config->update($update);
        }

        return $config->fresh();
    }

    /**
     * @param  array<string, float>  $positions  Claves de self::POSITION_KEYS, en
     *                                           porcentaje sobre el tamano de la pagina.
     */
    public function savePositions(string $scope, array $positions): GiftMessageConfig
    {
        $prefix = self::SCOPE_PREFIXES[$scope];
        $config = $this->current();

        $update = [];
        foreach (self::POSITION_KEYS as $key) {
            $update[$prefix.'_'.$key] = $positions[$key];
        }

        $config->update($update);

        return $config->fresh();
    }

    public function saveFonts(array $fonts): GiftMessageConfig
    {
        $config = $this->current();

        // Se filtran los null: el editor ahora guarda sobre y tarjeta por
        // separado (una peticion AJAX por tarjeta), y el resto de campos
        // llegan como null via validated() al no estar en el payload. Sin
        // este filtro, guardar un lado borraria la configuracion del otro.
        $update = array_filter(
            array_intersect_key($fonts, array_flip(self::FONT_FIELDS)),
            fn ($value) => $value !== null
        );

        if ($update !== []) {
            $config->update($update);
        }

        return $config->fresh();
    }

    private function storeImage(UploadedFile $file, string $prefix): string
    {
        $fileName = $prefix.'_'.now()->timestamp.'.'.$file->getClientOriginalExtension();

        return $file->storeAs(self::IMAGES_FOLDER, $fileName, self::DISK);
    }
}
