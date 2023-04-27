<?php

namespace Tests\Unit\Models;

use App\Exceptions\ParameterEmptyException;
use App\Models\AbstractExperienceModel;
use Tests\TestCase;

class AbstractExperienceModelTest extends TestCase
{
    protected $stub;

    public function setUp(): void
    {
        parent::setUp(); //

        $this->stub = new class extends AbstractExperienceModel {
            protected $fillable = ['param1', 'param2', 'param3'];
            protected array $empty = ['param1'];
        };
    }

    /** @dataProvider parametersListIsCompleteReturnFalseDataProvider */
    public function testParametersListIsCompleteReturnFalse($parameters) {
        $result = $this->stub::parametersListIsComplete($parameters);
        $this->assertFalse($result);
    }

    public function testParametersListIsCompleteAllFieldsEmptyable() {
        $this->stub = new class extends AbstractExperienceModel {
            protected $fillable = ['param1', 'param2', 'param3'];
            protected array $empty = ['param1', 'param2', 'param3'];
        };
        $result = $this->stub::parametersListIsComplete(['param4' => 'any value']);
        $this->assertTrue($result);
    }

    public function testParametersListIsCompleteReturnTrue() {
        $parameters = [
            'param2' => 'a parameter value',
            'param3' => 'some other parameter value',
        ];

        $result = $this->stub::parametersListIsComplete($parameters);
        $this->assertTrue($result);
    }

    /** @dataProvider parametersPopulateFromArrayReturnFalseDataProvider */
    public function testPopulateFromArrayReturnFalse($parameters) {
        $this->expectException(ParameterEmptyException::class);
        $result = $this->stub->populateFromArray($parameters);
    }

    /** @dataProvider parametersPopulateFromArrayReturnTrueDataProvider */
    public function testPopulateFromArrayReturnTrue($parameters) {
        $expected = array_merge([
            'param2' => 'some other value',
            'param3' => ' another value',
            'param1' => '',
        ], $parameters);

        $result = $this->stub->populateFromArray($parameters);
        $this->assertEquals($expected, $result->toArray());
    }

    private function parametersListIsCompleteReturnFalseDataProvider(): array {
        return [
            'parameter not in fillable list' => [
                [
                    'param5' => 'some value',
                ],
            ],
            'missing 1 parameter' => [
                [
                    'param2' => 'some parameter value',
                ],
            ],
            '1 parameter is empty' => [
                [
                    'param2' => null,
                    'param3' => 'some parameter value',
                ],
            ],
        ];
    }

    private function parametersPopulateFromArrayReturnFalseDataProvider(): array {
        return [
            'Missing 1 fillable parameter' => [
                [
                    'param1' => 'some value',
                    'param2' => 'some other value',
                ],
            ],
            '1 fillable parameter is empty' => [
                [
                    'param1' => 'some value',
                    'param2' => 'some other value',
                    'param3' => null,
                ],
            ],
        ];
    }

    private function parametersPopulateFromArrayReturnTrueDataProvider(): array {
        return [
            'All fillable parameter are set' => [
                [
                    'param2' => 'some other value',
                    'param3' => ' another value',
                ],
            ],
            'All fillable parameter are set - empty parameter is null' => [
                [
                    'param1' => null,
                    'param2' => 'some other value',
                    'param3' => ' another value',
                ],
            ],
            'All fillable parameter are set - empty parameter is set' => [
                [
                    'param1' => 'emptyable parameter value',
                    'param2' => 'some other value',
                    'param3' => ' another value',
                ],
            ],
        ];
    }
}