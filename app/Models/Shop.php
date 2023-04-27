<?php

declare(strict_types=1);

namespace App\Models;

class Shop extends AbstractExperienceModel
{
    protected $table = 'shop';
    protected $fillable = [
        'platform_domain',
        'platform_type',
        'platform_identifier',
        'shop_name',
        'support_email',
        'deleted_at',
        'redacted_at',
    ];
    protected array $empty = ['deleted_at', 'redacted_at'];

    public function getPlatformDomain(): string
    {
        return $this->platform_domain;
    }

    public function getPlatformType(): string
    {
        return $this->platform_type;
    }

    public function getPlatformIdentifier(): string
    {
        return $this->platform_identifier;
    }

    public function getID(): int
    {
        return $this->id;
    }

    public function getShopName(): string
    {
        return $this->shop_name;
    }

    public function getSupportEmail(): string
    {
        return $this->support_email;
    }

    /**
     * Return true if Infos sent back by `shops/v1/info` endpoint are the same as the ones provided by user in the Request parameters.
     */
    public static function compareInfos(array $parameters, array $infos): bool
    {
        $platformDomainParameters = $parameters['platform_domain'] ?? '';
        $platformTypeParameters = $parameters['platform_type'] ?? '';
        $platformIdentiferParameters = $parameters['platform_identifier'] ?? '';

        $infosDomainParameters = $infos['shop_domain'] ?? '';
        $infosTypeParameters = $infos['platform_slug'] ?? '';
        $infosIdentiferParameters = $infos['shop_identifier'] ?? '';

        return !empty($platformDomainParameters) && !empty($platformIdentiferParameters) && !empty($platformTypeParameters) &&
            !empty($infosDomainParameters) && !empty($infosIdentiferParameters) && !empty($infosTypeParameters) &&
            $platformDomainParameters === $infosDomainParameters &&
            $platformIdentiferParameters === $infosIdentiferParameters &&
            $platformTypeParameters === $infosTypeParameters;
    }
}
