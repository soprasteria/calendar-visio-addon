<?php
/**
 * SPDX-FileCopyrightText: "Copyright 2026, Sopra Steria France"
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
declare(strict_types=1);


namespace OCA\CalendarVisioAddon\Tests\Unit\Service;

use OCA\CalendarVisioAddon\Service\VisioApiService;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \OCA\CalendarVisioAddon\Service\VisioApiService
 */
#[CoversClass(VisioApiService::class)]
class VisioApiServiceTest extends TestCase
{

    private MockObject&IClientService $clientService;
    private MockObject&IConfig        $config;
    private MockObject&LoggerInterface $logger;
    private VisioApiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clientService = $this->createMock(IClientService::class);
        $this->config        = $this->createMock(IConfig::class);
        $this->logger        = $this->createMock(LoggerInterface::class);
        $this->service       = new VisioApiService($this->clientService, $this->config, $this->logger);
    }

    // ─── resolveBaseUrl ───────────────────────────────────────────────────────

    public function testResolveBaseUrlFromSystemConfig(): void
    {
        $this->config
            ->method('getSystemValue')
            ->with('visio_api_url', '')
            ->willReturn('https://visio.example.org/');

        $this->assertSame('https://visio.example.org', $this->service->resolveBaseUrl());
    }

    public function testResolveBaseUrlTrimsTrailingSlash(): void
    {
        $this->config->method('getSystemValue')->willReturn('https://visio.example.org///');
        $this->assertSame('https://visio.example.org', $this->service->resolveBaseUrl());
    }

    public function testResolveBaseUrlReturnsEmptyWhenNotSet(): void
    {
        $this->config->method('getSystemValue')->willReturn('');
        $this->assertSame('', $this->service->resolveBaseUrl());
    }

    // ─── isConfigured ─────────────────────────────────────────────────────────

    public function testIsConfiguredReturnsFalseWhenUrlMissing(): void
    {
        $this->config->method('getSystemValue')->willReturnMap([
            ['visio_api_url',    '', ''],
            ['visio_client_id',  '', 'my-id'],
            ['visio_client_secret', '', 'my-secret'],
        ]);
        $this->assertFalse($this->service->isConfigured());
    }

    public function testIsConfiguredReturnsFalseWhenClientIdMissing(): void
    {
        $this->config->method('getSystemValue')->willReturnMap([
            ['visio_api_url',    '', 'https://visio.example.org'],
            ['visio_client_id',  '', ''],
            ['visio_client_secret', '', 'my-secret'],
        ]);
        $this->assertFalse($this->service->isConfigured());
    }

    public function testIsConfiguredReturnsTrueWhenAllSet(): void
    {
        $this->config->method('getSystemValue')->willReturnMap([
            ['visio_api_url',       '', 'https://visio.example.org'],
            ['visio_client_id',     '', 'client-id'],
            ['visio_client_secret', '', 'client-secret'],
        ]);
        $this->assertTrue($this->service->isConfigured());
    }

    // ─── obtainToken ──────────────────────────────────────────────────────────

    public function testObtainTokenReturnsAccessToken(): void
    {
        $this->config->method('getSystemValue')->willReturnMap([
            ['visio_api_url',       '', 'https://visio.example.org'],
            ['visio_client_id',     '', 'cid'],
            ['visio_client_secret', '', 'csec'],
        ]);

        $response = $this->createMock(IResponse::class);
        $response->method('getBody')->willReturn(json_encode(['access_token' => 'jwt-abc']));

        $client = $this->createMock(IClient::class);
        $client->method('post')->willReturn($response);

        $this->clientService->method('newClient')->willReturn($client);

        $token = $this->service->obtainToken('user@example.com');
        $this->assertSame('jwt-abc', $token);
    }

    public function testObtainTokenReturnsNullWhenAccessTokenMissing(): void
    {
        $this->config->method('getSystemValue')->willReturnMap([
            ['visio_api_url',       '', 'https://visio.example.org'],
            ['visio_client_id',     '', 'cid'],
            ['visio_client_secret', '', 'csec'],
        ]);

        $response = $this->createMock(IResponse::class);
        $response->method('getBody')->willReturn(json_encode(['error' => 'invalid_client']));

        $client = $this->createMock(IClient::class);
        $client->method('post')->willReturn($response);

        $this->clientService->method('newClient')->willReturn($client);

        $this->logger->expects($this->once())->method('error');

        $this->assertNull($this->service->obtainToken('user@example.com'));
    }

    public function testObtainTokenReturnsNullOnHttpException(): void
    {
        $this->config->method('getSystemValue')->willReturn('https://visio.example.org');

        $client = $this->createMock(IClient::class);
        $client->method('post')->willThrowException(new \RuntimeException('Connection refused'));

        $this->clientService->method('newClient')->willReturn($client);

        $this->logger->expects($this->once())->method('error');

        $this->assertNull($this->service->obtainToken('user@example.com'));
    }

    // ─── createRoom ───────────────────────────────────────────────────────────

    public function testCreateRoomReturnsUrlField(): void
    {
        $this->config->method('getSystemValue')->willReturn('https://visio.example.org');

        $response = $this->createMock(IResponse::class);
        $response->method('getStatusCode')->willReturn(201);
        $response->method('getBody')->willReturn(json_encode([
            'id'   => 42,
            'url'  => 'https://visio.example.org/rooms/my-room',
            'slug' => 'my-room',
        ]));

        $client = $this->createMock(IClient::class);
        $client->method('post')->willReturn($response);

        $this->clientService->method('newClient')->willReturn($client);

        $url = $this->service->createRoom('valid-token');
        $this->assertSame('https://visio.example.org/rooms/my-room', $url);
    }

    public function testCreateRoomFallsBackToSlugWhenUrlAbsent(): void
    {
        $this->config->method('getSystemValue')->willReturn('https://visio.example.org');

        $response = $this->createMock(IResponse::class);
        $response->method('getStatusCode')->willReturn(201);
        $response->method('getBody')->willReturn(json_encode(['slug' => 'abc-room']));

        $client = $this->createMock(IClient::class);
        $client->method('post')->willReturn($response);

        $this->clientService->method('newClient')->willReturn($client);

        $url = $this->service->createRoom('valid-token');
        $this->assertSame('https://visio.example.org/abc-room', $url);
    }

    public function testCreateRoomReturnsNullOnBadStatusCode(): void
    {
        $this->config->method('getSystemValue')->willReturn('https://visio.example.org');

        $response = $this->createMock(IResponse::class);
        $response->method('getStatusCode')->willReturn(403);
        $response->method('getBody')->willReturn('{}');

        $client = $this->createMock(IClient::class);
        $client->method('post')->willReturn($response);

        $this->clientService->method('newClient')->willReturn($client);

        $this->logger->expects($this->once())->method('error');

        $this->assertNull($this->service->createRoom('token'));
    }

    public function testCreateRoomReturnsNullOnException(): void
    {
        $this->config->method('getSystemValue')->willReturn('https://visio.example.org');

        $client = $this->createMock(IClient::class);
        $client->method('post')->willThrowException(new \RuntimeException('Timeout'));

        $this->clientService->method('newClient')->willReturn($client);

        $this->logger->expects($this->once())->method('error');

        $this->assertNull($this->service->createRoom('token'));
    }
}
