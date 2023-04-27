<?php

namespace App\Models;

use App\Services\ExperienceService;

class Assets extends AbstractExperienceModel
{
    protected $table = 'assets';
    protected $fillable = [
        'asset_name',
        'asset_url',
        'flow_id',
        'position',
        'asset_type',
        'is_asynchronous',
    ];
    public $timestamps = false;

    public function getID(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->asset_name;
    }

    public function getUrl(): string
    {
        return $this->asset_url;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getAssetType(): string
    {
        return $this->asset_type;
    }

    public function getFlowID(): string
    {
        return $this->flow_id;
    }

    public function isAsynchronous(): bool
    {
        return (int) $this->is_asynchronous === 1;
    }

    public function setCompleteAssetUrl(): void
    {
        $assetsUrl = app()->make(ExperienceService::class)->getAssetsUrl();
        $this->asset_url = str_replace('{{assets_url}}', $assetsUrl, $this->asset_url);
    }
}
