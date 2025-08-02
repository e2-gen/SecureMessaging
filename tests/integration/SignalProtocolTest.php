<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class SignalProtocolTest extends TestCase {
    private $pdo;
    private $protocol;
    
    protected function setUp(): void {
        $this->pdo = new PDO('sqlite::memory:');
        // Initialize test database schema
        $this->protocol = new SignalProtocol($this->pdo, 'test-user');
    }
    
    public function testSessionInitialization(): void {
        $result = $this->protocol->initializeSession('recipient-user');
        $this->assertArrayHasKey('session_id', $result);
        $this->assertArrayHasKey('identity_key', $result);
    }
    
    public function testMessageEncryption(): void {
        $this->protocol->initializeSession('recipient-user');
        $encrypted = $this->protocol->encryptMessage('test', 'recipient-user');
        $this->assertNotEmpty($encrypted);
    }
}
