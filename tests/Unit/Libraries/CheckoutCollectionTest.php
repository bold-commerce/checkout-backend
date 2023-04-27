<?php

namespace Tests\Unit\Libraries;

use App\Libraries\CheckoutCollection;
use PHPUnit\Framework\TestCase;

class CheckoutCollectionTest extends TestCase
{
    public function getRecursiveProvider()
    {
        return [
            'Given associative array, when getRecursive is passed existing key, then it returns the expected value' => [
                ['testKey' => 'expectedValue'],
                'testKey',
                'expectedValue',
            ],
            'Given associative array, when getRecursive is passed non-existant key, then it returns null' => [
                ['testKey' => 'expectedValue'],
                'nonexistantkey',
                null,
            ],
            'Given nested associative array, when getRecursive is passed existing nested key, then it returns expected value' => [
                ['foo' => ['bar' => ['baz' => 'expectedValue']]],
                'foo.bar.baz',
                'expectedValue',
            ],
            'Given nested associative array, when getRecursive is passed nonexistant nested key, then it returns null' => [
                ['foo' => ['bar' => ['baz' => 'expectedValue']]],
                'foo.notarealkey',
                null,
            ],
            'Given un-nested associative array, when getRecursive is passed nested key, then it returns null' => [
                ['foo' => 'bar'],
                'foo.baz',
                null,
            ],
            'Given non-empty numeric array, when getRecursive is passed index zero, then it returns value at that index' => [
                ['foo'],
                '0',
                'foo',
            ],
            'Given non-empty numeric array, when getRecursive is passed non-zero existing index, then it returns value at that index' => [
                ['foo', 'bar'],
                '1',
                'bar',
            ],
            'Given nested, non-empty numeric array, when getRecursive is passed existing nexted index, then it returns value at that index' => [
                [['foo', 'bar'], ['baz', 'bin']],
                '1.0',
                'baz',
            ],
            'Given nested, non-empty numeric array, when getRecursive is passed non-existant nested index, then it returns null' => [
                [['foo', 'bar'], ['baz', 'bin']],
                '1.3',
                null,
            ],
            'Given any checkout collection, when getRecursive is passed nested query with emtpy keys, then it returns null' => [
                ['foo' => ['bar' => 'baz']],
                '..',
                null,
            ]
        ];
    }

    /**
     * @dataProvider getRecursiveProvider
     */
    public function testGetRecursive($collectionContents, $key, $expectedValue)
    {
        $collection = new CheckoutCollection($collectionContents);
        $result = $collection->getRecursive($key);
        $this->assertSame($result, $expectedValue);
    }

    public function testGivenAnyCheckoutCollectionWhenGetRecursiveIsPassedEmptyKeyThenItReturnsSelf()
    {
        $collection = new CheckoutCollection();
        $result = $collection->getRecursive('');
        $this->assertSame($result, $collection);
    }
}
