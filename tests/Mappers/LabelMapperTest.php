<?php

namespace Laraditz\Courier\SfExpress\Tests\Mappers;

use Illuminate\Support\Facades\Http;
use Laraditz\Courier\DTOs\Results\LabelResult;
use Laraditz\Courier\Exceptions\CourierException;
use Laraditz\Courier\SfExpress\Mappers\LabelMapper;
use Laraditz\Courier\SfExpress\Tests\TestCase;

class LabelMapperTest extends TestCase
{
    public function test_downloads_pdf_and_returns_bytes_with_url_in_meta(): void
    {
        Http::fake([
            'https://storage.example.com/label.pdf' => Http::response('%PDF-1.4 fake content', 200),
        ]);

        $data   = ['url' => 'https://storage.example.com/label.pdf'];
        $result = LabelMapper::map($data, 'MYIU1234715622');

        $this->assertInstanceOf(LabelResult::class, $result);
        $this->assertSame('MYIU1234715622', $result->waybillNumber);
        $this->assertSame('pdf', $result->format);
        $this->assertSame('%PDF-1.4 fake content', $result->content);
        $this->assertSame('https://storage.example.com/label.pdf', $result->meta()['label_url']);
    }

    public function test_throws_courier_exception_when_download_fails(): void
    {
        Http::fake([
            '*' => Http::response('Not Found', 404),
        ]);

        $this->expectException(CourierException::class);
        $this->expectExceptionMessageMatches('/Failed to download/');

        LabelMapper::map(['url' => 'https://storage.example.com/missing.pdf'], 'MYIU000');
    }

    public function test_throws_courier_exception_when_url_key_missing(): void
    {
        $this->expectException(CourierException::class);
        $this->expectExceptionMessageMatches('/missing url field/');

        LabelMapper::map([], 'MYIU000');
    }
}
