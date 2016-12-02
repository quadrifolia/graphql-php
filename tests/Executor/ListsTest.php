<?php

namespace GraphQL\Tests\Executor;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Executor\Executor;
use GraphQL\Error\FormattedError;
use GraphQL\Executor\Promise\Adapter\ReactPromiseAdapter;
use GraphQL\Language\Parser;
use GraphQL\Language\SourceLocation;
use GraphQL\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class ListsTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Executor::setPromiseAdapter(new ReactPromiseAdapter());
    }

    public static function tearDownAfterClass()
    {
        Executor::setPromiseAdapter(null);
    }

    // Describe: Execute: Handles list nullability

    /**
     * @describe [T]
     */
    public function testHandlesNullableListsWithArray()
    {
        // Contains values
        $this->checkHandlesNullableLists(
            [ 1, 2 ],
            [ 'data' => [ 'nest' => [ 'test' => [ 1, 2 ] ] ] ]
        );

        // Contains null
        $this->checkHandlesNullableLists(
            [ 1, null, 2 ],
            [ 'data' => [ 'nest' => [ 'test' => [ 1, null, 2 ] ] ] ]
        );

        // Returns null
        $this->checkHandlesNullableLists(
            null,
            [ 'data' => [ 'nest' => [ 'test' => null ] ] ]
        );
    }

    /**
     * @describe [T]
     */
    public function testHandlesNullableListsWithPromiseArray()
    {
        // Contains values
        $this->checkHandlesNullableLists(
            \React\Promise\resolve([ 1, 2 ]),
            [ 'data' => [ 'nest' => [ 'test' => [ 1, 2 ] ] ] ]
        );

        // Contains null
        $this->checkHandlesNullableLists(
            \React\Promise\resolve([ 1, null, 2 ]),
            [ 'data' => [ 'nest' => [ 'test' => [ 1, null, 2 ] ] ] ]
        );

        // Returns null
        $this->checkHandlesNullableLists(
            \React\Promise\resolve(null),
            [ 'data' => [ 'nest' => [ 'test' => null ] ] ]
        );

        // Rejected
        $this->checkHandlesNullableLists(
            function () {
                return \React\Promise\reject(new \Exception('bad'));
            },
            [
                'data' => ['nest' => ['test' => null]],
                'errors' => [
                    [
                        'message' => 'bad',
                        'locations' => [['line' => 1, 'column' => 10]],
                        'path' => ['nest', 'test']
                    ]
                ]
            ]
        );
    }

    /**
     * @describe [T]
     */
    public function testHandlesNullableListsWithArrayPromise()
    {
        // Contains values
        $this->checkHandlesNullableLists(
            [ \React\Promise\resolve(1), \React\Promise\resolve(2) ],
            [ 'data' => [ 'nest' => [ 'test' => [ 1, 2 ] ] ] ]
        );

        // Contains null
        $this->checkHandlesNullableLists(
            [ \React\Promise\resolve(1), \React\Promise\resolve(null), \React\Promise\resolve(2) ],
            [ 'data' => [ 'nest' => [ 'test' => [ 1, null, 2 ] ] ] ]
        );

        // Returns null
        $this->checkHandlesNullableLists(
            \React\Promise\resolve(null),
            [ 'data' => [ 'nest' => [ 'test' => null ] ] ]
        );

        // Contains reject
        $this->checkHandlesNullableLists(
            function () {
                return [ \React\Promise\resolve(1), \React\Promise\reject(new \Exception('bad')), \React\Promise\resolve(2) ];
            },
            [
                'data' => ['nest' => ['test' => [1, null, 2]]],
                'errors' => [
                    [
                        'message' => 'bad',
                        'locations' => [['line' => 1, 'column' => 10]],
                        'path' => ['nest', 'test', 1]
                    ]
                ]
            ]
        );
    }

    /**
     * @describe [T]!
     */
    public function testHandlesNonNullableListsWithArray()
    {
        // Contains values
        $this->checkHandlesNonNullableLists(
            [ 1, 2 ],
            [ 'data' => [ 'nest' => [ 'test' => [ 1, 2 ] ] ] ]
        );

        // Contains null
        $this->checkHandlesNonNullableLists(
            [ 1, null, 2 ],
            [ 'data' => [ 'nest' => [ 'test' => [ 1, null, 2 ] ] ] ]
        );

        // Returns null
        $this->checkHandlesNonNullableLists(
            null,
            [
                'data' => [ 'nest' => null ],
                'errors' => [
                    FormattedError::create(
                        'Cannot return null for non-nullable field DataType.test.',
                        [ new SourceLocation(1, 10) ]
                    )
                ]
            ]
        );
    }

    /**
     * @describe [T]!
     */
    public function testHandlesNonNullableListsWithPromiseArray()
    {
        // Contains values
        $this->checkHandlesNonNullableLists(
            \React\Promise\resolve([ 1, 2 ]),
            [ 'data' => [ 'nest' => [ 'test' => [ 1, 2 ] ] ] ]
        );

        // Contains null
        $this->checkHandlesNonNullableLists(
            \React\Promise\resolve([ 1, null, 2 ]),
            [ 'data' => [ 'nest' => [ 'test' => [ 1, null, 2 ] ] ] ]
        );

        // Returns null
        $this->checkHandlesNonNullableLists(
            null,
            [
                'data' => [ 'nest' => null ],
                'errors' => [
                    FormattedError::create(
                        'Cannot return null for non-nullable field DataType.test.',
                        [ new SourceLocation(1, 10) ]
                    )
                ]
            ]
        );

        // Rejected
        $this->checkHandlesNonNullableLists(
            function () {
                return \React\Promise\reject(new \Exception('bad'));
            },
            [
                'data' => ['nest' => null],
                'errors' => [
                    [
                        'message' => 'bad',
                        'locations' => [['line' => 1, 'column' => 10]],
                        'path' => ['nest', 'test']
                    ]
                ]
            ]
        );
    }

    /**
     * @describe [T]!
     */
    public function testHandlesNonNullableListsWithArrayPromise()
    {
        // Contains values
        $this->checkHandlesNonNullableLists(
            [ \React\Promise\resolve(1), \React\Promise\resolve(2) ],
            [ 'data' => [ 'nest' => [ 'test' => [ 1, 2 ] ] ] ]
        );

        // Contains null
        $this->checkHandlesNonNullableLists(
            [ \React\Promise\resolve(1), \React\Promise\resolve(null), \React\Promise\resolve(2) ],
            [ 'data' => [ 'nest' => [ 'test' => [ 1, null, 2 ] ] ] ]
        );

        // Contains reject
        $this->checkHandlesNonNullableLists(
            function () {
                return [ \React\Promise\resolve(1), \React\Promise\reject(new \Exception('bad')), \React\Promise\resolve(2) ];
            },
            [
                'data' => ['nest' => ['test' => [1, null, 2]]],
                'errors' => [
                    [
                        'message' => 'bad',
                        'locations' => [['line' => 1, 'column' => 10]],
                        'path' => ['nest', 'test', 1]
                    ]
                ]
            ]
        );
    }

    /**
     * @describe [T!]
     */
    public function testHandlesListOfNonNullsWithArray()
    {
        // Contains values
        $this->checkHandlesListOfNonNulls(
            [ 1, 2 ],
            [ 'data' => [ 'nest' => [ 'test' => [ 1, 2 ] ] ] ]
        );

        // Contains null
        $this->checkHandlesListOfNonNulls(
            [ 1, null, 2 ],
            [
                'data' => [ 'nest' => [ 'test' => null ] ],
                'errors' => [
                    FormattedError::create(
                        'Cannot return null for non-nullable field DataType.test.',
                        [ new SourceLocation(1, 10) ]
                    )
                ]
            ]
        );

        // Returns null
        $this->checkHandlesListOfNonNulls(
            null,
            [ 'data' => [ 'nest' => [ 'test' => null ] ] ]
        );
    }

    /**
     * @describe [T!]
     */
    public function testHandlesListOfNonNullsWithPromiseArray()
    {
        // Contains values
        $this->checkHandlesListOfNonNulls(
            \React\Promise\resolve([ 1, 2 ]),
            [ 'data' => [ 'nest' => [ 'test' => [ 1, 2 ] ] ] ]
        );

        // Contains null
        $this->checkHandlesListOfNonNulls(
            \React\Promise\resolve([ 1, null, 2 ]),
            [
                'data' => [ 'nest' => [ 'test' => null ] ],
                'errors' => [
                    FormattedError::create(
                        'Cannot return null for non-nullable field DataType.test.',
                        [ new SourceLocation(1, 10) ]
                    )
                ]
            ]
        );

        // Returns null
        $this->checkHandlesListOfNonNulls(
            \React\Promise\resolve(null),
            [ 'data' => [ 'nest' => [ 'test' => null ] ] ]
        );

        // Rejected
        $this->checkHandlesListOfNonNulls(
            function () {
                return \React\Promise\reject(new \Exception('bad'));
            },
            [
                'data' => ['nest' => ['test' => null]],
                'errors' => [
                    [
                        'message' => 'bad',
                        'locations' => [['line' => 1, 'column' => 10]],
                        'path' => ['nest', 'test']
                    ]
                ]
            ]
        );
    }

    /**
     * @describe [T]!
     */
    public function testHandlesListOfNonNullsWithArrayPromise()
    {
        // Contains values
        $this->checkHandlesListOfNonNulls(
            [ \React\Promise\resolve(1), \React\Promise\resolve(2) ],
            [ 'data' => [ 'nest' => [ 'test' => [ 1, 2 ] ] ] ]
        );

        // Contains null
        $this->checkHandlesListOfNonNulls(
            [ \React\Promise\resolve(1), \React\Promise\resolve(null), \React\Promise\resolve(2) ],
            [ 'data' => [ 'nest' => [ 'test' => null ] ] ]
        );

        // Contains reject
        $this->checkHandlesListOfNonNulls(
            function () {
                return [ \React\Promise\resolve(1), \React\Promise\reject(new \Exception('bad')), \React\Promise\resolve(2) ];
            },
            [
                'data' => ['nest' => ['test' => null]],
                'errors' => [
                    [
                        'message' => 'bad',
                        'locations' => [['line' => 1, 'column' => 10]],
                        'path' => ['nest', 'test', 1]
                    ]
                ]
            ]
        );
    }

    /**
     * @describe [T!]!
     */
    public function testHandlesNonNullListOfNonNullsWithArray()
    {
        // Contains values
        $this->checkHandlesNonNullListOfNonNulls(
            [ 1, 2 ],
            [ 'data' => [ 'nest' => [ 'test' => [ 1, 2 ] ] ] ]
        );


        // Contains null
        $this->checkHandlesNonNullListOfNonNulls(
            [ 1, null, 2 ],
            [
                'data' => [ 'nest' => null ],
                'errors' => [
                    FormattedError::create(
                        'Cannot return null for non-nullable field DataType.test.',
                        [ new SourceLocation(1, 10) ]
                    )
                ]
            ]
        );

        // Returns null
        $this->checkHandlesNonNullListOfNonNulls(
            null,
            [
                'data' => [ 'nest' => null ],
                'errors' => [
                    FormattedError::create(
                        'Cannot return null for non-nullable field DataType.test.',
                        [ new SourceLocation(1, 10) ]
                    )
                ]
            ]
        );
    }

    /**
     * @describe [T!]!
     */
    public function testHandlesNonNullListOfNonNullsWithPromiseArray()
    {
        // Contains values
        $this->checkHandlesNonNullListOfNonNulls(
            \React\Promise\resolve([ 1, 2 ]),
            [ 'data' => [ 'nest' => [ 'test' => [ 1, 2 ] ] ] ]
        );

        // Contains null
        $this->checkHandlesNonNullListOfNonNulls(
            \React\Promise\resolve([ 1, null, 2 ]),
            [
                'data' => [ 'nest' => null ],
                'errors' => [
                    FormattedError::create(
                        'Cannot return null for non-nullable field DataType.test.',
                        [ new SourceLocation(1, 10) ]
                    )
                ]
            ]
        );

        // Returns null
        $this->checkHandlesNonNullListOfNonNulls(
            \React\Promise\resolve(null),
            [
                'data' => [ 'nest' => null ],
                'errors' => [
                    FormattedError::create(
                        'Cannot return null for non-nullable field DataType.test.',
                        [ new SourceLocation(1, 10) ]
                    )
                ]
            ]
        );

        // Rejected
        $this->checkHandlesNonNullListOfNonNulls(
            function () {
                return \React\Promise\reject(new \Exception('bad'));
            },
            [
                'data' => ['nest' => null ],
                'errors' => [
                    [
                        'message' => 'bad',
                        'locations' => [['line' => 1, 'column' => 10]],
                        'path' => ['nest', 'test']
                    ]
                ]
            ]
        );
    }

    /**
     * @describe [T!]!
     */
    public function testHandlesNonNullListOfNonNullsWithArrayPromise()
    {
        // Contains values
        $this->checkHandlesNonNullListOfNonNulls(
            [ \React\Promise\resolve(1), \React\Promise\resolve(2) ],
            [ 'data' => [ 'nest' => [ 'test' => [ 1, 2 ] ] ] ]
        );

        // Contains null
        $this->checkHandlesNonNullListOfNonNulls(
            [ \React\Promise\resolve(1), \React\Promise\resolve(null), \React\Promise\resolve(2) ],
            [
                'data' => [ 'nest' => null ],
                'errors' => [
                    FormattedError::create(
                        'Cannot return null for non-nullable field DataType.test.',
                        [ new SourceLocation(1, 10) ]
                    )
                ]
            ]
        );

        // Contains reject
        $this->checkHandlesNonNullListOfNonNulls(
            function () {
                return [ \React\Promise\resolve(1), \React\Promise\reject(new \Exception('bad')), \React\Promise\resolve(2) ];
            },
            [
                'data' => ['nest' => null ],
                'errors' => [
                    [
                        'message' => 'bad',
                        'locations' => [['line' => 1, 'column' => 10]],
                        'path' => ['nest', 'test']
                    ]
                ]
            ]
        );
    }

    private function checkHandlesNullableLists($testData, $expected)
    {
        $testType = Type::listOf(Type::int());;
        $this->check($testType, $testData, $expected);
    }

    private function checkHandlesNonNullableLists($testData, $expected)
    {
        $testType = Type::nonNull(Type::listOf(Type::int()));
        $this->check($testType, $testData, $expected);
    }

    private function checkHandlesListOfNonNulls($testData, $expected)
    {
        $testType = Type::listOf(Type::nonNull(Type::int()));
        $this->check($testType, $testData, $expected);
    }

    public function checkHandlesNonNullListOfNonNulls($testData, $expected)
    {
        $testType = Type::nonNull(Type::listOf(Type::nonNull(Type::int())));
        $this->check($testType, $testData, $expected);
    }

    private function check($testType, $testData, $expected)
    {
        $data = ['test' => $testData];
        $dataType = null;

        $dataType = new ObjectType([
            'name' => 'DataType',
            'fields' => function () use (&$testType, &$dataType, $data) {
                return [
                    'test' => [
                        'type' => $testType
                    ],
                    'nest' => [
                        'type' => $dataType,
                        'resolve' => function () use ($data) {
                            return $data;
                        }
                    ]
                ];
            }
        ]);

        $schema = new Schema([
            'query' => $dataType
        ]);

        $ast = Parser::parse('{ nest { test } }');

        $result = Executor::execute($schema, $ast, $data);
        $this->assertArraySubset($expected, self::awaitPromise($result));
    }

    /**
     * @param \GraphQL\Executor\Promise\Promise $promise
     * @return array
     */
    private static function awaitPromise($promise)
    {
        $results = null;
        $promise->then(function (ExecutionResult $executionResult) use (&$results) {
            $results = $executionResult->toArray();
        });
        return $results;
    }
}