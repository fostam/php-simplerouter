<?php

use PHPUnit\Framework\TestCase;
use Fostam\SimpleRouter\Processor;
use Fostam\SimpleRouter\Route;
use Fostam\SimpleRouter\Http;

/**
 * @covers Route
 */
final class RouteTest extends TestCase {
    public function testCanBeCreated() {
        /** @var Processor|PHPUnit_Framework_MockObject_MockObject $processor*/
        $processor = $this->getMockBuilder(Processor::class)->getMock();

        $this->assertInstanceOf(
            Route::class,
            Route::create('/test/path', Http::METHOD_GET, $processor)
        );
    }

    /**
     * @param $path
     * @param $method
     *
     * @dataProvider testCreateExceptionsProvider
     */
    public function testCreateExceptions($path, $method) {
        /** @var Processor|PHPUnit_Framework_MockObject_MockObject $processor*/
        $processor = $this->getMockBuilder(Processor::class)->getMock();

        $this->expectException(Fostam\SimpleRouter\Exception\InternalApiException::class);
        Route::create($path, $method, $processor);
    }

    public function testCreateExceptionsProvider() {
        return [
            ['', Http::METHOD_GET],
            ['/test', 'test'],
        ];
    }

    /**
     * @param $path
     * @param $method
     * @param $inputPath
     * @param $inputMethod
     *
     * @dataProvider pathPositiveResolveProvider
     */
    public function testPositivePathResolving($path, $method, $inputPath, $inputMethod) {
        /** @var Processor|PHPUnit_Framework_MockObject_MockObject $processor*/
        $processor = $this->getMockBuilder(Processor::class)->getMock();

        $route = Route::create($path, $method, $processor);
        $this->assertInstanceOf(
            Route::class,
            $route
        );

        $this->assertTrue(
            $route->matches($inputPath, $inputMethod, $pathMatched)
        );

        $route->resolve();
        $this->assertInstanceOf(Processor::class, $route->getProcessor());
    }

    public function pathPositiveResolveProvider() {

        return [
            ['/test', Http::METHOD_GET, '/test', 'GET'],
            ['/test', Http::METHOD_POST, '/test', 'POST'],
            ['/test', Http::METHOD_PATCH, '/test', 'PATCH'],
            ['/test', Http::METHOD_PUT, '/test', 'PUT'],
            ['/test', Http::METHOD_DELETE, '/test', 'DELETE'],

            ['/', Http::METHOD_GET, '/', 'GET'],
            ['/test/path', Http::METHOD_GET, '/test/path', 'GET'],
            ['/{param1}', Http::METHOD_GET, '/test', 'GET'],
            ['/{param1}/{param2}', Http::METHOD_GET, '/test/path', 'GET'],

            ['/{param1:\w+}/{param2:\d+}', Http::METHOD_GET, '/test/123', 'GET'],
            ['/{param1:[\w/]+}', Http::METHOD_GET, '/test/path', 'GET'],
        ];
    }

    /**
     * @param $path
     * @param $method
     * @param $inputPath
     * @param $inputMethod
     *
     * @dataProvider pathNegativeResolveProvider
     */
    public function testNegativePathResolving($path, $method, $inputPath, $inputMethod) {
        /** @var Processor|PHPUnit_Framework_MockObject_MockObject $processor*/
        $processor = $this->getMockBuilder(Processor::class)->getMock();

        $route = Route::create($path, $method, $processor);
        $this->assertInstanceOf(
            Route::class,
            $route
        );

        $this->assertFalse(
            $route->matches($inputPath, $inputMethod, $pathMatched)
        );
    }

    /**
     * @return array
     */
    public function pathNegativeResolveProvider() {
        return [
            ['/test', Http::METHOD_GET, '/test', 'POST'],
            ['/test', Http::METHOD_POST, '/test', 'GET'],

            ['/test', Http::METHOD_GET, '/path', 'GET'],
            ['/', Http::METHOD_GET, '/path', 'GET'],
            ['/test', Http::METHOD_GET, '/', 'GET'],
            ['/test', Http::METHOD_GET, '', 'GET'],
            ['/{param1}/{param2}', Http::METHOD_GET, '/test/path/param', 'GET'],

            ['/{param1:\w+}/{param2:\d+}', Http::METHOD_GET, '/test/path', 'GET'],
            ['/{param1:\w+}', Http::METHOD_GET, '/test/path', 'GET'],
        ];
    }

    /**
     * @param $path
     * @param $inputPath
     * @param $expectedValues
     *
     * @dataProvider testParamParsingProvider
     */
    public function testParamParsing($path, $inputPath, $expectedValues) {
        /** @var Processor|PHPUnit_Framework_MockObject_MockObject $processor*/
        $processor = $this->getMockBuilder(Processor::class)->getMock();

        $route = Route::create($path, Http::METHOD_GET, $processor);
        $route->matches($inputPath, Http::METHOD_GET, $pathMatched);
        $route->resolve();

        $this->assertEquals($expectedValues, $route->getParams());
    }


    public function testParamParsingProvider() {
        return [
            ['/path/{param}', '/path/test', ['param' => 'test']],
            ['/path/{param}/{test:\d+}', '/path/test/123', ['param' => 'test', 'test' => '123']],
            ['/path/{param:[\w\/]+}', '/path/test/123', ['param' => 'test/123']],
        ];
    }
}