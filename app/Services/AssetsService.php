<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Errors;
use App\Constants\Fields;
use App\Exceptions\AssetNotFoundException;
use App\Models\Assets;
use Illuminate\Support\Collection;

class AssetsService
{
    /**
     * @throws AssetNotFoundException
     */
    public function getAssetByID(int $id): ?Assets
    {
        $asset = Assets::find($id);
        if (empty($asset)) {
            throw new AssetNotFoundException(sprintf(Errors::INVALID_ID, Fields::ASSET, $id));
        }

        return $asset;
    }

    public function getAssetByName(string $name): ?Assets
    {
        $asset = Assets::where('asset_name', '=', $name)->first();
        if (empty($asset)) {
            throw new AssetNotFoundException(sprintf(Errors::INVALID_NAME, Fields::ASSET, $name));
        }

        return $asset;
    }
}
