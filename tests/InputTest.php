<?php

namespace Neat\Http\Server\Test;

use InvalidArgumentException;
use Neat\Http\Server\Exception\FilterNotFoundException;
use Neat\Http\Server\Input;
use Neat\Http\Server\Request;
use Neat\Http\Server\Upload;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use stdClass;

class InputTest extends TestCase
{
    public function testEmpty(): void
    {
        /** @var ServerRequestInterface|MockObject $psrRequest */
        $psrRequest = $this->getMockForAbstractClass(ServerRequestInterface::class);

        $psrRequest->expects($this->any())->method('getQueryParams')->willReturn([]);
        $psrRequest->expects($this->any())->method('getParsedBody')->willReturn([]);
        $psrRequest->expects($this->any())->method('getUploadedFiles')->willReturn([]);
        $psrRequest->expects($this->any())->method('getCookieParams')->willReturn([]);

        $input = new Input(new Request($psrRequest), new SessionMock());
        $input->load('query', 'post', 'files', 'cookie');

        $this->assertSame([], $input->all());
        $this->assertFalse($input->has('unknown'));
        $this->assertNull($input->get('unknown'));
        $this->assertSame([], $input->errors());
        $this->assertNull($input->error('unknown'));
        $this->assertTrue($input->valid());
        $this->assertTrue($input->valid('unknown'));
    }

    public function testFrom(): void
    {
        /** @var UploadedFileInterface|MockObject $psrUpload */
        $psrUpload = $this->getMockForAbstractClass(UploadedFileInterface::class);
        /** @var ServerRequestInterface|MockObject $psrRequest */
        $psrRequest = $this->getMockForAbstractClass(ServerRequestInterface::class);
        $psrRequest->expects($this->any())->method('getQueryParams')->willReturn(['var' => 'query']);
        $psrRequest->expects($this->any())->method('getParsedBody')->willReturn(['var' => 'post']);
        $psrRequest->expects($this->any())->method('getUploadedFiles')->willReturn(['var' => $psrUpload]);
        $psrRequest->expects($this->any())->method('getCookieParams')->willReturn(['var' => 'cookie']);

        $input = new Input(new Request($psrRequest), new SessionMock());
        $input->load('query', 'post', 'files', 'cookie');

        $input->load('query');
        $this->assertSame(['var' => 'query'], $input->all());

        $input->load('post');
        $this->assertSame(['var' => 'post'], $input->all());

        $input->load('cookie');
        $this->assertSame(['var' => 'cookie'], $input->all());

        $input->load('files');
        $this->assertEquals(['var' => new Upload($psrUpload)], $input->all());
    }

    public function testSet(): void
    {
        /** @var ServerRequestInterface|MockObject $psrRequest */
        $psrRequest = $this->getMockForAbstractClass(ServerRequestInterface::class);

        $input = new Input(new Request($psrRequest), new SessionMock());
        $input->set('var', 'value');

        $this->assertSame(['var' => 'value'], $input->all());
        $this->assertTrue($input->has('var'));
        $this->assertSame('value', $input->get('var'));
    }

    public function testFromEmpty(): void
    {
        /** @var ServerRequestInterface|MockObject $psrRequest */
        $psrRequest = $this->getMockForAbstractClass(ServerRequestInterface::class);

        $input = new Input(new Request($psrRequest), new SessionMock());

        $this->expectExceptionObject(new RuntimeException('Input sources must not be empty'));

        $input->load();
    }

    public function testFromUnknown(): void
    {
        /** @var ServerRequestInterface|MockObject $psrRequest */
        $psrRequest = $this->getMockForAbstractClass(ServerRequestInterface::class);

        $input = new Input(new Request($psrRequest), new SessionMock());

        $this->expectExceptionObject(new RuntimeException('Unknown input source: internet'));

        $input->load('internet');
    }

    public function testFilter(): void
    {
        /** @var ServerRequestInterface|MockObject $psrRequest */
        $psrRequest = $this->getMockForAbstractClass(ServerRequestInterface::class);
        $psrRequest->expects($this->any())->method('getQueryParams')->willReturn(['var' => ' test ']);

        $input = new Input(new Request($psrRequest), new SessionMock());
        $input->load('query');

        $this->assertSame(' test ', $input->get('var'));
        $this->assertSame('test', $input->filter('var', 'trim'));
        $this->assertSame('TEST', $input->filter('var', 'trim|strtoupper'));
        $this->assertSame('TEST', $input->filter('var', ['trim', 'strtoupper']));
        require_once 'trim.php';
        $this->assertSame(null, $input->filter('unknown', '\Neat\Http\Server\Test\trim'));
    }

    public function testRequiredFilter(): void
    {
        /** @var ServerRequestInterface|MockObject $psrRequest */
        $psrRequest = $this->getMockForAbstractClass(ServerRequestInterface::class);
        $psrRequest->expects($this->any())->method('getQueryParams')->willReturn(['foo' => 'test', 'bar' => null]);

        $input = new Input(new Request($psrRequest), new SessionMock());
        $input->load('query');
        /** @var MockObject|CallableMock $requiredFilter */
        $requiredFilter = $this->createMock(CallableMock::class);
        $requiredFilter->expects($this->at(0))->method('__invoke')->with('test')->willReturn([]);
        $requiredFilter
            ->expects($this->at(1))->method('__invoke')->with(null)->willReturn([':field is een verplicht veld']);

        $input->register('required', $requiredFilter);

        $input->filter('foo', 'required');
        $this->assertSame([], $input->errors());
        $this->assertSame(null, $input->error('foo'));
        $input->filter('bar', 'required');
        $this->assertSame(['bar' => ':field is een verplicht veld'], $input->errors());
        $this->assertSame(':field is een verplicht veld', $input->error('bar'));
    }

    public function testFilterNotFoundException(): void
    {
        /** @var ServerRequestInterface|MockObject $psrRequest */
        $psrRequest = $this->getMockForAbstractClass(ServerRequestInterface::class);
        $psrRequest->expects($this->any())->method('getQueryParams')->willReturn(['foo' => 'test', 'bar' => null]);

        $input = new Input(new Request($psrRequest), new SessionMock());
        $input->load('query');

        $this->expectExceptionObject(
            new FilterNotFoundException("Filter 'required' is not a registered filter or global function"),
        );
        $input->filter('foo', 'required');
    }

    public function testInvalidFilterException(): void
    {
        /** @var ServerRequestInterface|MockObject $psrRequest */
        $psrRequest = $this->getMockForAbstractClass(ServerRequestInterface::class);
        $psrRequest->expects($this->any())->method('getQueryParams')->willReturn(['foo' => 'test', 'bar' => null]);

        $input = new Input(new Request($psrRequest), new SessionMock());
        $input->load('query');

        $this->expectExceptionObject(
            new InvalidArgumentException(
                "Neat\Http\Server\Input::normalizeFilters expects null, string or array as first argument 'object' given",
            ),
        );
        /** @noinspection PhpParamsInspection */
        $input->filter('foo', new stdClass());
    }

    public function provideCustomFilterData(): array
    {
        return [
            ['test', 'test', 'Not a number'],
            ['3', 3, 'Not an even number'],
            ['2', 2, null],
        ];
    }

    /**
     * @dataProvider provideCustomFilterData
     * @param mixed $filtered
     */
    public function testCustomFilter(string $value, $filtered, ?string $error): void
    {
        /** @var ServerRequestInterface|MockObject $psrRequest */
        $psrRequest = $this->getMockForAbstractClass(ServerRequestInterface::class);
        $psrRequest->expects($this->any())->method('getQueryParams')->willReturn(['var' => $value]);

        $even = function (&$value) {
            if (!is_numeric($value)) {
                return ['Not a number'];
            }

            $value = intval($value);
            if ($value % 2) {
                return ['Not an even number'];
            }

            return [];
        };

        $input = new Input(new Request($psrRequest), new SessionMock());
        $input->load('query');
        $input->register('even', $even);

        $this->assertSame($filtered, $input->filter('var', 'even'));
        $this->assertSame($error, $input->error('var'));
        $this->assertSame(!$error, $input->valid('var'));
    }

    public function testFiltersWithParameters(): void
    {
        /** @var ServerRequestInterface|MockObject $psrRequest */
        $psrRequest = $this->getMockForAbstractClass(ServerRequestInterface::class);
        $psrRequest->expects($this->any())->method('getQueryParams')->willReturn(['var' => 'test']);

        $input = new Input(new Request($psrRequest), new SessionMock());
        $input->load('query');
        /** @var MockObject|CallableMock $filter */
        $filter = $this->createMock(CallableMock::class);
        $filter->expects($this->any())->method('__invoke')->with('test', 'bla')->willReturn([]);
        $input->register('test', $filter);
        $this->assertSame('test', $input->filter('var', 'test:bla|trim'));
        $this->assertSame('test', $input->filter('var', ['test:bla', 'trim']));
        $this->assertSame('test', $input->filter('var', ['test' => 'bla', 'trim']));
        $this->assertSame('test', $input->filter('var', ['test' => ['bla'], 'trim']));
    }

    public function provideTypeData(): array
    {
        return [
            ['bool', null, null],
            ['bool', '', false],
            ['bool', '0', false],
            ['bool', '1', true],
            ['bool', '3.14', true],
            ['bool', 'any-other-string', true],
            ['int', null, null],
            ['int', '', 0],
            ['int', '0', 0],
            ['int', '1', 1],
            ['int', '3.14', 3],
            ['int', 'any-other-string', 0],
            ['float', null, null],
            ['float', '', 0.0],
            ['float', '0', 0.0],
            ['float', '1', 1.0],
            ['float', '3.14', 3.14],
            ['float', 'any-other-string', 0.0],
            ['string', null, null],
            ['string', '', ''],
            ['string', '0', '0'],
            ['string', '1', '1'],
            ['string', '3.14', '3.14'],
            ['string', 'any-other-string', 'any-other-string'],
        ];
    }

    /**
     * @dataProvider provideTypeData
     * @param bool|int|null|string $filtered
     */
    public function testTypeCast(string $type, ?string $value, $filtered): void
    {
        /** @var ServerRequestInterface|MockObject $psrRequest */
        $psrRequest = $this->getMockForAbstractClass(ServerRequestInterface::class);
        $psrRequest->expects($this->any())->method('getQueryParams')->willReturn(['var' => $value]);

        $input = new Input(new Request($psrRequest), new SessionMock());
        $input->load('query');

        $this->assertSame($filtered, $input->$type('var'));
    }

    public function testFile(): void
    {
        /** @var UploadedFileInterface|MockObject $psrUpload */
        $psrUpload = $this->getMockForAbstractClass(UploadedFileInterface::class);
        /** @var ServerRequestInterface|MockObject $psrRequest */
        $psrRequest = $this->getMockForAbstractClass(ServerRequestInterface::class);
        $psrRequest->expects($this->any())->method('getParsedBody')->willReturn(['bool' => true]);
        $psrRequest->expects($this->any())->method('getUploadedFiles')->willReturn(['upload' => $psrUpload]);

        $input = new Input(new Request($psrRequest), new SessionMock());
        $input->load('post', 'files');

        $this->assertNull($input->file('bool'));
        $this->assertEquals(new Upload($psrUpload), $input->file('upload'));
    }
}
