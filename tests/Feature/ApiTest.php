<?php
declare(strict_types=1);

namespace Tests\Feature;

use GuzzleHttp\Promise\PromiseInterface;
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

        Http::preventingStrayRequests();

        Http::fake([
            self::API_URL . '?api-key=' . self::API_KEY => $this->getResponse('no_parameters.json'),
            self::API_URL . '?author=Martin&api-key=' . self::API_KEY   => $this->getResponse('with_author.json'),
            self::API_URL . '?offset=40&api-key=' . self::API_KEY => $this->getResponse('offset.json'),
            self::API_URL . '?offset=41&api-key=' . self::API_KEY => Http::response(null, 422),
            self::API_URL . '?author=George RR Martin&title=A CLASH OF KINGS&api-key=' . self::API_KEY => $this->getResponse('author_and_title.json'),
            self::API_URL . '?title=King&offset=40&api-key=' . self::API_KEY => $this->getResponse('title_and_offset.json'),
        ]);

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
        $json = $response->json();
        $this->assertArrayHasKey('status', $json);
        $this->assertArrayHasKey('results', $json);
        $this->assertArrayHasKey('num_results', $json);
        $this->assertEquals(36464, $json['num_results']);
        $response->assertJsonPath('results.0.author', 'Diana Gabaldon');
        $response->assertJsonPath('results.0.title', '"I GIVE YOU MY BODY ..."');
    }

    public function testWithAuthor(): void
    {
        $author = 'Martin';
        $response = $this->getJson(self::ENDPOINT . '?author=' . $author);
        $response->assertStatus(200);
        Http::assertSent(function (Request $request) use ($author) {
            return $request->url() === self::API_URL . '?author=' . $author . '&api-key=' . self::API_KEY;
        });


    }

    public function testWithSingleIsbn(): void
    {
        $isbn = '9780061122415';
        $response = $this->getJson(self::ENDPOINT . '?isbn[0]=' . $isbn);
        $response->assertStatus(200);
        Http::assertSent(function (Request $request) use ($isbn) {
            return $request->url() === self::API_URL . '?isbn=' . $isbn . '&api-key=' . self::API_KEY;
        });
    }

    public function testWithInvalidIsbnLength(): void {
        $isbn = '97800611';
        $response = $this->getJson(self::ENDPOINT . '?isbn=' . $isbn);
        $response->assertStatus(422);
    }


    public function testWithMultipleIsbn(): void
    {
        $isbns = ['1234567890', '1234567890123'];
        $url = self::ENDPOINT . '?isbn[]=' . $isbns[0] . '&isbn[]=' . $isbns[1];
        $response = $this->getJson($url);
        $response->assertStatus(200);
        Http::assertSent(function (Request $request) use ($isbns) {
            return $request->url() === self::API_URL . '?isbn=' . implode(rawurlencode(';'), $isbns) . '&api-key=' . self::API_KEY;
        });
    }

    public function testOffset(): void
    {
        $offset = 40;
        $response = $this->getJson(self::ENDPOINT . '?offset=' . $offset);
        $response->assertStatus(200);
        Http::assertSent(function (Request $request) use ($offset) {
            return $request->url() === self::API_URL . '?offset=' . $offset . '&api-key=' . self::API_KEY;
        });
        $json = $response->json();
        $this->assertArrayHasKey('status', $json);
        $this->assertArrayHasKey('results', $json);
        $this->assertArrayHasKey('num_results', $json);
        $this->assertEquals(36464, $json['num_results']);
        $response->assertJsonPath('results.0.author', 'Clint Emerson');
        $response->assertJsonPath('results.0.title', '100 DEADLY SKILLS');
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

    public function testWithAuthorAndTitle(): void
    {
        $author = rawurlencode('George RR Martin');
        $title = rawurlencode('A CLASH OF KINGS');
        $response = $this->getJson(self::ENDPOINT . '?author=' . $author . '&title=' . $title);
        Http::assertSent(function (Request $request) use ($author, $title) {
            return $request->url() === self::API_URL . '?author=' . $author . '&title=' . $title . '&api-key=' . self::API_KEY;
        });
        $response->assertStatus(200);
    }

    public function testWithTitleAndOffset(): void
    {
        $title = 'King';
        $offset = 40;
        $response = $this->getJson(self::ENDPOINT . '?title=' . $title . '&offset=' . $offset);
        Http::assertSent(function (Request $request) use ($title, $offset) {
            return $request->url() === self::API_URL . '?title=' . $title . '&offset=' . $offset . '&api-key=' . self::API_KEY;
        });
        $response->assertStatus(200);
        $response->assertJsonPath('results.0.title', 'BAKING WITH DORIE');
    }

    /**
     * @param string $file
     * @return PromiseInterface
     */
    public function getResponse(string $file): PromiseInterface
    {
        return Http::response(file_get_contents(base_path("tests/Responses/$file")));
    }
}
