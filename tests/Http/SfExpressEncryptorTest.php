<?php

namespace Laraditz\Courier\SfExpress\Tests\Http;

use Laraditz\Courier\SfExpress\Http\SfExpressEncryptor;
use Laraditz\Courier\SfExpress\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SfExpressEncryptor::class)]
class SfExpressEncryptorTest extends TestCase
{
    // Sandbox credentials from apidoc/Credentials for Sandbox ENV
    private const AES_KEY = 'oI1YdU1cb1YC70HVjZRa3wXBLUsrIUYr5lDh0gFrMbe';
    private const APP_KEY = 'cdd169d87a4698d314754a79f180308a';

    private SfExpressEncryptor $encryptor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->encryptor = new SfExpressEncryptor(self::AES_KEY, self::APP_KEY);
    }

    public function test_encrypt_decrypt_roundtrip(): void
    {
        $plaintext = '{"customerCode":"OSMYICRM-OSMY00009Z38","sfWaybillNos":["MYIU1234567890"]}';

        $result    = $this->encryptor->encrypt($plaintext, 'test-token', '1234567890', 'test-nonce');
        $decrypted = $this->encryptor->decrypt($result['encrypt']);

        $this->assertSame($plaintext, $decrypted);
    }

    public function test_signature_is_sha256_of_sorted_values(): void
    {
        $result = $this->encryptor->encrypt('test', 'token-abc', '1000', 'nonce-xyz');

        $arr = [$result['encrypt'], 'token-abc', '1000', 'nonce-xyz'];
        sort($arr, SORT_STRING);

        $this->assertSame(hash('sha256', implode('', $arr)), $result['signature']);
    }

    public function test_returns_encrypt_and_signature_keys(): void
    {
        $result = $this->encryptor->encrypt('hello', 'tok', '1', 'n');

        $this->assertArrayHasKey('encrypt', $result);
        $this->assertArrayHasKey('signature', $result);
        $this->assertNotEmpty($result['encrypt']);
        $this->assertNotEmpty($result['signature']);
    }

    public function test_different_plaintexts_produce_different_ciphertexts(): void
    {
        $a = $this->encryptor->encrypt('hello', 'tok', '1', 'nonce');
        $b = $this->encryptor->encrypt('world', 'tok', '1', 'nonce');

        $this->assertNotSame($a['encrypt'], $b['encrypt']);
    }
}
