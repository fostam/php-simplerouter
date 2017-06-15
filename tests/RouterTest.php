<?php

use PHPUnit\Framework\TestCase;
use Fostam\SimpleRouter\Processor;
use Fostam\SimpleRouter\Router;
use Fostam\SimpleRouter\Http;

/**
 * @covers Router
 */
final class RouterTest extends TestCase {
    /**
     * @param $inputPath
     * @param $inputMethod
     * @param $expectedResult
     *
     * @dataProvider testResolveTargetProvider
     */
    public function testResolveTarget($inputPath, $inputMethod, $expectedResult) {
        // build routes
        $routes = [
            ['/path', Http::METHOD_GET, 1],
            ['/path', Http::METHOD_POST, 2],
            ['/', Http::METHOD_GET, 3],
            ['/path/test', Http::METHOD_GET, 4],
            ['/path/{param}', Http::METHOD_GET, 5],
            ['/path/test2', Http::METHOD_GET, 6],
        ];

        $router = new Router();

        $processors = [];
        foreach ($routes as $routeData) {
            /** @var Processor|PHPUnit_Framework_MockObject_MockObject $processor */
            $processor = $this->getMockBuilder(Processor::class)->getMock();
            if ($expectedResult === $routeData[2]) {
                $processor->expects($this->once())->method('execute');
            }

            $router->createRoute($routeData[0], $routeData[1], $processor);
            $processors[$routeData[2]] = $processor;
        }

        $router->resolve($inputPath, $inputMethod);
    }

    public function testResolveTargetProvider() {
        return [
            ['/path', Http::METHOD_GET, 1],
            ['/path', Http::METHOD_POST, 2],
            ['/', Http::METHOD_GET, 3],
            ['/path/test', Http::METHOD_GET, 4],
            ['/path/param', Http::METHOD_GET, 5],
            ['/path/test2', Http::METHOD_GET, 5],
        ];
    }
}