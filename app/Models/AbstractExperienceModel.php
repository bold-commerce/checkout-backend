<?php

declare(strict_types=1);

namespace App\Models;

use App\Constants\Errors;
use App\Exceptions\ParameterEmptyException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractExperienceModel extends Model
{
    use HasFactory;

    protected array $empty = [];

    protected static function getInstance(): static
    {
        return new static();
    }

    public static function parametersListIsComplete(array $parametersList): bool
    {
        foreach (array_diff(self::getInstance()->fillable, self::getInstance()->empty) as $fieldName) {
            if (!in_array($fieldName, array_keys($parametersList)) || empty($parametersList[$fieldName])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws ParameterEmptyException
     */
    public function populateFromArray(array $data): static
    {
        foreach ($this->fillable as $fieldName) {
            if (in_array($fieldName, $this->empty) || !empty($data[$fieldName])) {
                $this->$fieldName = $data[$fieldName] ?? '';
            } else {
                throw new ParameterEmptyException(sprintf(Errors::EMPTY_PARAMETER, $fieldName));
            }
        }

        return $this;
    }
}
