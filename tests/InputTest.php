<?php

namespace Neat\Http\Server\Test;

use Neat\Http\ServerRequest;
use Neat\Http\Server\Input;
use Neat\Http\Upload;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

class InputTest extends TestCase
{
    /**
     * Test empty input
     */
    public function testEmpty()
    {
        /** @var ServerRequestInterface|MockObject $psrRequest */
        $psrRequest = $this->getMockForAbstractClass(ServerRequestInterface::class);

        $psrRequest->expects($this->any())->method('getQueryParams')->willReturn([]);
        $psrRequest->expects($this->any())->method('getParsedBody')->willReturn([]);
        $psrRequest->expects($this->any())->method('getUploadedFiles')->willReturn([]);
        $psrRequest->expects($this->any())->method('getCookieParams')->willReturn([]);

        $input = new Input(new ServerRequest($psrRequest), new SessionMock());
        $input->load('query', 'post', 'files', 'cookie');

        $this->assertSame([], $input->all());
        $this->assertFalse($input->has('unknown'));
        $this->assertNull($input->get('unknown'));
        $this->assertSame([], $input->errors());
        $this->assertNull($input->error('unknown'));
        $this->assertTrue($input->valid());
        $this->assertTrue($input->valid('unknown'));
    }

    /**
     * Test input from various source configurations
     */
    public function testFrom()
    {
        /** @var UploadedFileInterface|MockObject $psrUpload */
        $psrUpload = $this->getMockForAbstractClass(UploadedFileInterface::class);
        /** @var ServerRequestInterface|MockObject $psrRequest */
        $psrRequest = $this->getMockForAbstractClass(ServerRequestInterface::class);
        $psrRequest->expects($this->any())->method('getQueryParams')->willReturn(['var' => 'query']);
        $psrRequest->expects($this->any())->method('getParsedBody')->willReturn(['var' => 'post']);
        $psrRequest->expects($this->any())->method('getUploadedFiles')->willReturn(['var' => $psrUpload]);
        $psrRequest->expects($this->any())->method('getCookieParams')->willReturn(['var' => 'cookie']);

        $input = new Input(new ServerRequest($psrRequest), new SessionMock());
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

    /**
     * Test set value
     */
    public function testSet()
    {
        /** @var ServerRequestInterface|MockObject $psrRequest */
        $psrRequest = $this->getMockForAbstractClass(ServerRequestInterface::class);

        $input = new Input(new ServerRequest($psrRequest), new SessionMock());
        $input->set('var', 'value');

        $this->assertSame(['var' => 'value'], $input->all());
        $this->assertTrue($input->has('var'));
        $this->assertSame('value', $input->get('var'));
    }

    /**
     * Test from empty sources
     */
    public function testFromEmpty()
    {
        /** @var ServerRequestInterface|MockObject $psrRequest */
        $psrRequest = $this->getMockForAbstractClass(ServerRequestInterface::class);

        $input = new Input(new ServerRequest($psrRequest), new SessionMock());

        $this->expectExceptionObject(new RuntimeException('Input sources must not be empty'));

        $input->load();
    }

    /**
     * Test from unknown sources
     */
    public function testFromUnknown()
    {
        /** @var ServerRequestInterface|MockObject $psrRequest */
        $psrRequest = $this->getMockForAbstractClass(ServerRequestInterface::class);

        $input = new Input(new ServerRequest($psrRequest), new SessionMock());

        $this->expectExceptionObject(new RuntimeException('Unknown input source: internet'));

        $input->load('internet');
    }

    /**
     * Test input filtering
     */
    public function testFilter()
    {
        /** @var ServerRequestInterface|MockObject $psrRequest */
        $psrRequest = $this->getMockForAbstractClass(ServerRequestInterface::class);
        $psrRequest->expects($this->any())->method('getQueryParams')->willReturn(['var' => ' test ']);

        $input = new Input(new ServerRequest($psrRequest), new SessionMock());
        $input->load('query');

        $this->assertSame(' test ', $input->get('var'));
        $this->assertSame('test', $input->filter('var', 'trim'));
        $this->assertSame('TEST', $input->filter('var', 'trim|strtoupper'));
        $this->assertSame('TEST', $input->filter('var', ['trim', 'strtoupper']));
        $this->assertNull($input->filter('unknown', 'trim'));
    }

    /**
     * Provide custom filter data
     *
     * @return array
     */
    public function provideCustomFilterData()
    {
        return [
            ['test', 'test', 'Not a number'],
            ['3', 3, 'Not an even number'],
            ['2', 2, null],
        ];
    }

    /**
     * Test custom filter
     *
     * @dataProvider provideCustomFilterData
     * @param string      $value
     * @param mixed       $filtered
     * @param string|null $error
     */
    public function testCustomFilter($value, $filtered, $error)
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

        $input = new Input(new ServerRequest($psrRequest), new SessionMock());
        $input->load('query');
        $input->register('even', $even);

        $this->assertSame($filtered, $input->filter('var', 'even'));
        $this->assertSame($error, $input->error('var'));
        $this->assertSame(!$error, $input->valid('var'));
    }

    /**
     * Provide type data
     *
     * @return array
     */
    public function provideTypeData()
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
     * Test type casted input
     *
     * @param string $type
     * @param string $value
     * @param bool   $filtered
     * @dataProvider provideTypeData
     */
    public function testTypeCast($type, $value, $filtered)
    {
        /** @var ServerRequestInterface|MockObject $psrRequest */
        $psrRequest = $this->getMockForAbstractClass(ServerRequestInterface::class);
        $psrRequest->expects($this->any())->method('getQueryParams')->willReturn(['var' => $value]);

        $input = new Input(new ServerRequest($psrRequest), new SessionMock());
        $input->load('query');

        $this->assertSame($filtered, $input->$type('var'));
    }

    /**
     * Test file input
     */
    public function testFile()
    {
        /** @var UploadedFileInterface|MockObject $psrUpload */
        $psrUpload = $this->getMockForAbstractClass(UploadedFileInterface::class);
        /** @var ServerRequestInterface|MockObject $psrRequest */
        $psrRequest = $this->getMockForAbstractClass(ServerRequestInterface::class);
        $psrRequest->expects($this->any())->method('getParsedBody')->willReturn(['bool' => true]);
        $psrRequest->expects($this->any())->method('getUploadedFiles')->willReturn(['upload' => $psrUpload]);

        $input = new Input(new ServerRequest($psrRequest), new SessionMock());
        $input->load('post', 'files');

        $this->assertNull($input->file('bool'));
        $this->assertEquals(new Upload($psrUpload), $input->file('upload'));
    }
}
