<?php

/**
 * PHP version 8
 *
 * @category Application
 * @package  Tests\Libraries
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.1
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Tests\Libraries;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\TestCase;
use Spotlibs\PhpLib\Exceptions\ParameterException;
use Spotlibs\PhpLib\Exceptions\RuntimeException;
use Spotlibs\PhpLib\Libraries\ClientProxy;

/**
 * ClientProxyTest
 *
 * Unit tests for ClientProxy
 *
 * @category Test
 * @package  Tests\Libraries
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 * @covers   \Spotlibs\PhpLib\Libraries\ClientProxy
 */
class ClientProxyTest extends TestCase
{
    public function createApplication()
    {
        return require __DIR__ . '/../../bootstrap/app.php';
    }

    // =========================================================================
    // call — JSON response
    // =========================================================================

    /** @test */
    public function testCallReturnsResponseOnJsonSuccess(): void
    {
        $mock = new MockHandler([
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode(['status' => 'ok', 'message' => 'well done'])
            ),
        ]);
        $handlerStack = new HandlerStack($mock);
        $request = new Request('POST', 'https://example.com/api/test');

        $client = new ClientProxy(['handler' => $handlerStack]);
        $response = $client->call($request);

        $contents = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('ok', $contents['status']);
    }

    /** @test */
    public function testCallWithResponseCodeNon00ThrowsRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);

        $mock = new MockHandler([
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode(['responseCode' => '99', 'responseDesc' => 'Internal Server Error'])
            ),
        ]);
        $handlerStack = new HandlerStack($mock);
        $request = new Request('POST', 'https://example.com/api/test');

        $client = new ClientProxy(['handler' => $handlerStack]);
        $client->call($request);
    }

    /** @test */
    public function testCallWithResponseCode00PassesValidation(): void
    {
        $mock = new MockHandler([
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'responseCode' => '00',
                    'responseDesc' => 'Sukses',
                    'responseData' => ['id' => 1],
                ])
            ),
        ]);
        $handlerStack = new HandlerStack($mock);
        $request = new Request('POST', 'https://example.com/api/test');

        $client = new ClientProxy(['handler' => $handlerStack]);
        $response = $client->call($request);

        $contents = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('00', $contents['responseCode']);
    }

    /** @test */
    public function testCallWithNoContentTypeAssumesJsonAndValidates(): void
    {
        // Empty Content-Type — isJsonResponse() treats it as JSON, responseCode check applies
        // responseCode '01' maps to ParameterException via StdException::create()
        $this->expectException(ParameterException::class);

        $mock = new MockHandler([
            new Response(
                200,
                [],
                json_encode(['responseCode' => '01', 'responseDesc' => 'Error'])
            ),
        ]);
        $handlerStack = new HandlerStack($mock);
        $request = new Request('POST', 'https://example.com/api/test');

        $client = new ClientProxy(['handler' => $handlerStack]);
        $client->call($request);
    }

    // =========================================================================
    // call — binary / non-JSON response (skips JSON decode)
    // =========================================================================

    /** @test */
    public function testCallWithPdfContentTypeSkipsJsonDecode(): void
    {
        $pdfBytes = '%PDF-1.4 fake pdf content';

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/pdf'], $pdfBytes),
        ]);
        $handlerStack = new HandlerStack($mock);
        $request = new Request('GET', 'https://example.com/files/doc.pdf');

        $client = new ClientProxy(['handler' => $handlerStack]);
        $response = $client->call($request);

        // Must not throw — binary body is not JSON-decoded
        $this->assertStringContainsString('application/pdf', $response->getHeaderLine('Content-Type'));
        $this->assertEquals($pdfBytes, $response->getBody()->getContents());
    }

    /** @test */
    public function testCallWithImageContentTypeSkipsJsonDecode(): void
    {
        $imageBytes = "\x89PNG\r\n\x1a\n fake png bytes";

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'image/png'], $imageBytes),
        ]);
        $handlerStack = new HandlerStack($mock);
        $request = new Request('GET', 'https://example.com/images/photo.png');

        $client = new ClientProxy(['handler' => $handlerStack]);
        $response = $client->call($request);

        $this->assertStringContainsString('image/png', $response->getHeaderLine('Content-Type'));
        $this->assertEquals($imageBytes, $response->getBody()->getContents());
    }

    /** @test */
    public function testCallWithOctetStreamContentTypeSkipsJsonDecode(): void
    {
        $binaryData = "\x00\x01\x02\x03 some binary";

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/octet-stream'], $binaryData),
        ]);
        $handlerStack = new HandlerStack($mock);
        $request = new Request('GET', 'https://example.com/files/archive.zip');

        $client = new ClientProxy(['handler' => $handlerStack]);
        $response = $client->call($request);

        $this->assertStringContainsString('application/octet-stream', $response->getHeaderLine('Content-Type'));
        $this->assertEquals($binaryData, $response->getBody()->getContents());
    }

    // =========================================================================
    // call — injectRequestHeader / injectResponseHeader
    // =========================================================================

    /** @test */
    public function testCallAppliesInjectedResponseHeaders(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['ok' => true])),
        ]);
        $handlerStack = new HandlerStack($mock);
        $request = new Request('POST', 'https://example.com/api/test');

        $client = new ClientProxy(['handler' => $handlerStack]);
        $response = $client
            ->injectRequestHeader(['X-Custom-Header' => 'value'])
            ->injectResponseHeader(['X-Response-Tag' => 'tagged'])
            ->call($request);

        $this->assertEquals('tagged', $response->getHeaderLine('X-Response-Tag'));
    }

    // =========================================================================
    // callFile — always streams, never JSON-decodes
    // =========================================================================

    /** @test */
    public function testCallFileReturnsBinaryStream(): void
    {
        $pdfBytes = '%PDF-1.4 fake pdf content for stream test';

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/pdf'], $pdfBytes),
        ]);
        $handlerStack = new HandlerStack($mock);
        $request = new Request('GET', 'https://example.com/files/report.pdf');

        $client = new ClientProxy(['handler' => $handlerStack]);
        $response = $client->callFile($request);

        $this->assertStringContainsString('application/pdf', $response->getHeaderLine('Content-Type'));
        $this->assertEquals($pdfBytes, $response->getBody()->getContents());
    }

    /** @test */
    public function testCallFileWithJsonBodyDoesNotThrow(): void
    {
        // callFile never validates responseCode even if body happens to be JSON
        $mock = new MockHandler([
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode(['responseCode' => '99', 'responseDesc' => 'Error'])
            ),
        ]);
        $handlerStack = new HandlerStack($mock);
        $request = new Request('GET', 'https://example.com/files/data');

        $client = new ClientProxy(['handler' => $handlerStack]);
        $response = $client->callFile($request);

        // Should NOT throw — callFile skips JSON validation entirely
        $contents = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('99', $contents['responseCode']);
    }

    /** @test */
    public function testCallFileAppliesInjectedResponseHeaders(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/pdf'], 'pdf bytes'),
        ]);
        $handlerStack = new HandlerStack($mock);
        $request = new Request('GET', 'https://example.com/files/doc.pdf');

        $client = new ClientProxy(['handler' => $handlerStack]);
        $response = $client
            ->injectResponseHeader(['X-Download-Tag' => 'yes'])
            ->callFile($request);

        $this->assertEquals('yes', $response->getHeaderLine('X-Download-Tag'));
    }

    // =========================================================================
    // setProxy
    // =========================================================================

    /** @test */
    public function testSetProxyReturnsSelf(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['ok' => true])),
        ]);
        $handlerStack = new HandlerStack($mock);

        $client = new ClientProxy(['handler' => $handlerStack]);
        $result = $client->setProxy('http://proxy.internal:8080');

        $this->assertInstanceOf(ClientProxy::class, $result);
    }

    // =========================================================================
    // non-200 HTTP status — passes through without JSON check
    // =========================================================================

    /** @test */
    public function testCallWithNon200StatusPassesThroughWithoutJsonCheck(): void
    {
        $mock = new MockHandler([
            new Response(404, ['Content-Type' => 'application/json'], json_encode(['error' => 'not found'])),
        ]);
        $handlerStack = new HandlerStack($mock);
        $request = new Request('GET', 'https://example.com/api/missing');

        $client = new ClientProxy(['handler' => $handlerStack]);
        $response = $client->call($request);

        $this->assertEquals(404, $response->getStatusCode());
    }
}
