<?php
declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiTest extends TestCase
{

    const ENDPOINT = '/api/1/nyt/best-sellers';
    const API_KEY = 'asdf123';
    const API_URL = 'https://api.nytimes.com/svc/books/v3/lists/best-sellers/history.json';

    public function setUp(): void
    {
        parent::setUp();

        Http::fake();

        config([
            'env.bestSellers.apiUrl' => self::API_URL,
            'env.bestSellers.apiKey' => self::API_KEY
        ]);
    }

    public function testNoParameters(): void
    {
        $response = $this->getJson(self::ENDPOINT);
        $response->assertStatus(200);
        Http::assertSent(function (Request $request) {
            return $request->url() === self::API_URL . '?api-key=' . self::API_KEY;
        });
    }

    public function testWithAuthor(): void
    {
        $author = 'Williams';
        $response = $this->getJson(self::ENDPOINT . '?author=' . $author);
        $response->assertStatus(200);
        Http::assertSent(function (Request $request) use ($author) {
            return $request->url() === self::API_URL . '?api-key=' . self::API_KEY . '&author=' . $author;
        });
    }

    public function testWithSingleIsbn(): void
    {
        $isbn = '9780061122415';
        $response = $this->getJson(self::ENDPOINT . '?isbn[0]=' . $isbn);
        $response->assertStatus(200);
        Http::assertSent(function (Request $request) use ($isbn) {
            return $request->url() === self::API_URL . '?api-key=' . self::API_KEY . '&isbn=' . $isbn;
        });
    }

    public function testWithMultipleIsbn(): void
    {
        $isbns = ['1234567890', '1234567890123'];
        $url = self::ENDPOINT . '?isbn[]=' . $isbns[0] . '&isbn[]=' . $isbns[1];
        $response = $this->getJson($url);
        $response->assertStatus(200);
        Http::assertSent(function (Request $request) use ($isbns) {
            return $request->url() === self::API_URL . '?api-key=' . self::API_KEY . '&isbn=' . implode('%3B', $isbns);
        });
    }

    public function testWithInvalidIsbnLength(): void {
        $isbn = '97800611';
        $response = $this->getJson(self::ENDPOINT . '?isbn=' . $isbn);
        $response->assertStatus(422);
    }

    public function testOffset(): void
    {
        $offset = 40;
        $response = $this->getJson(self::ENDPOINT . '?offset=' . $offset);
        $response->assertStatus(200);
        Http::assertSent(function (Request $request) use ($offset) {
            return $request->url() === self::API_URL . '?api-key=' . self::API_KEY . '&offset=' . $offset;
        });
    }

    public function testInvalidOffsetNotMultipleOf20(): void
    {
        $offset = 41;
        $response = $this->getJson(self::ENDPOINT . '?offset=' . $offset);
        $response->assertStatus(422);
    }

    public function testNegativeInvalidOffset(): void
    {
        $offset = -20;
        $response = $this->getJson(self::ENDPOINT . '?offset=' . $offset);
        $response->assertStatus(422);
    }
}
