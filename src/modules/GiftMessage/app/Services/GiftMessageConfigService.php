<?php

namespace Modules\GiftMessage\Services;

use Illuminate\Http\UploadedFile;
use Modules\GiftMessage\Models\GiftMessageConfig;

class GiftMessageConfigService
{
    private const DISK = 'public';

    private const IMAGES_FOLDER = 'giftmessage/images';

    private const POSITION_FIELDS = [
        'envelope' => ['t1_x' => 'env_t1_x', 't1_y' => 'env_t1_y', 't2_x' => 'env_t2_x', 't2_y' => 'env_t2_y'],
        'card' => ['t1_x' => 'card_t1_x', 't1_y' => 'card_t1_y', 't2_x' => 'card_t2_x', 't2_y' => 'card_t2_y'],
    ];

    private const FONT_FIELDS = [
        'env_t1_font', 'env_t1_size', 'env_t2_font', 'env_t2_size',
        'card_t1_font', 'card_t1_size', 'card_t2_font', 'card_t2_size',
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
     * @param  array{t1_x: float, t1_y: float, t2_x: float, t2_y: float}  $positions
     */
    public function savePositions(string $scope, array $positions): GiftMessageConfig
    {
        $fieldMap = self::POSITION_FIELDS[$scope];
        $config = $this->current();

        $update = [];
        foreach ($fieldMap as $positionKey => $column) {
            $update[$column] = $positions[$positionKey];
        }

        $config->update($update);

        return $config->fresh();
    }

    public function saveFonts(array $fonts): GiftMessageConfig
    {
        $config = $this->current();

        $update = array_intersect_key($fonts, array_flip(self::FONT_FIELDS));

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
