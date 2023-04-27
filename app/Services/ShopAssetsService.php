<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Assets as AssetsConstants;
use App\Constants\Errors;
use App\Exceptions\AssetNotFoundException;
use App\Exceptions\InvalidAssetException;
use App\Exceptions\ParameterEmptyException;
use App\Exceptions\ResourceMissingException;
use App\Models\Assets;
use App\Models\Shop;
use App\Models\ShopAssets;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;

class ShopAssetsService
{
    public function getAssetsByShopID(int $shopID): Collection
    {
        $shopAsset = ShopAssets::where('shop_id', '=', $shopID)->first();
        if (empty($shopAsset)) {
            throw new ResourceMissingException(sprintf(Errors::UNDEFINED_TEMPLATE, $shopID));
        }

        $template = Assets::where('id', '=', $shopAsset->getAssetID())->first();
        if (empty($template)) {
            throw new ResourceMissingException(sprintf(Errors::TEMPLATE_NOT_EXISTING, $shopAsset->getAssetID()));
        }

        $childrenAssets = Assets::where('parent_id', '=', $shopAsset->getAssetID())->get();
        $template->setCompleteAssetUrl();

        return collect([
            'template' => $template,
            'children' => collect([
                'header' => $childrenAssets->filter(function ($value, $key) {
                    return $value['position'] === AssetsConstants::ASSET_POSITION_HEADER;
                }),
                'body' => $childrenAssets->filter(function ($value, $key) {
                    return $value['position'] === AssetsConstants::ASSET_POSITION_BODY;
                }),
                'footer' => $childrenAssets->filter(function ($value, $key) {
                    return $value['position'] === AssetsConstants::ASSET_POSITION_FOOTER;
                }),
            ]),
        ]);
    }

    /**
     * @throws InvalidAssetException
     * @throws ParameterEmptyException|BindingResolutionException
     */
    public static function insertAsset(Shop $shop, int|string $asset): Assets
    {
        if (empty($asset)) {
            throw new InvalidAssetException(Errors::ASSET_EMPTY);
        }

        $assetService = app()->make(AssetsService::class);
        try {
            if (is_string($asset)) {
                $assetModel = $assetService->getAssetByName($asset);
            } else {
                $assetModel = $assetService->getAssetByID($asset);
            }
        } catch (AssetNotFoundException $e) {
            throw new InvalidAssetException(Errors::INVALID_ASSET, 0, $e);
        }
        $assetID = $assetModel->getID();

        $asset = ShopAssets::find($shop->getID());
        if (empty($asset)) {
            $asset = new ShopAssets();
            $asset->setShopID($shop->getID());
        }

        $asset->populateFromArray(['asset_id' => $assetID]);
        $asset->save();

        return $assetModel;
    }
}
