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

        Http::preventStrayRequests();

        $apiKey = self::API_KEY;
        $apiUrl = self::API_URL;

        Http::fake([
            "$apiUrl?api-key=$apiKey" => $this->getResponse('no_parameters.json'),
            "$apiUrl?author=Martin&api-key=$apiKey" => $this->getResponse('with_author.json'),
            "$apiUrl?offset=40&api-key=$apiKey" => $this->getResponse('offset.json'),
            "$apiUrl?offset=41&api-key=$apiKey" => Http::response(null, 422),
            "$apiUrl?author=George%20RR%20Martin&title=A%20CLASH%20OF%20KINGS&api-key=$apiKey" => $this->getResponse('author_and_title.json'),
            "$apiUrl?title=King&offset=40&api-key=$apiKey" => $this->getResponse('title_and_offset.json'),
            "$apiUrl?isbn=0744080045&api-key=$apiKey" => $this->getResponse('with_isbn.json'),
            "$apiUrl?isbn=9780446579933%3B0061374229&api-key=$apiKey" => $this->getResponse('with_multiple_isbn.json'),
        ]);


        config([
            'env.bestSellers.apiUrl' => $apiUrl,
            'env.bestSellers.apiKey' => $apiKey
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
        $response->assertJsonPath('results.0.author', 'George RR Martin');
        $response->assertJsonPath('results.0.title', 'A CLASH OF KINGS');
        $this->assertEquals(253, $response->json()['num_results']);
        $this->assertEquals("OK", $response->json()['status']);
    }

    public function testWithSingleIsbn(): void
    {
        $isbn = '0744080045';
        $response = $this->getJson(self::ENDPOINT . '?isbn[0]=' . $isbn);
        $response->assertStatus(200);
        Http::assertSent(function (Request $request) use ($isbn) {
            return $request->url() === self::API_URL . '?isbn=' . $isbn . '&api-key=' . self::API_KEY;
        });
        $response->assertJsonPath('results.0.author', 'B. Dylan Hollis');
        $response->assertJsonPath('num_results', 1);
    }

    public function testWithInvalidIsbnLength(): void {
        $isbn = '97800611';
        $response = $this->getJson(self::ENDPOINT . '?isbn=' . $isbn);
        $response->assertStatus(422);
    }


    public function testWithMultipleIsbn(): void
    {
        $response = $this->getJson(self::ENDPOINT . '?isbn[]=9780446579933&isbn[]=0061374229&api-key=' . self::API_KEY );

        $response->assertStatus(200)
            ->assertJsonPath('num_results', 0)
            ->assertJsonCount(0, 'results');
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
        $this->assertArrayHasKey('results', $json);
        $response->assertJsonPath('status', 'OK')
            ->assertStatus(200)
            ->assertJsonPath('num_results', 36464);
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
        $response->assertJsonPath('results.0.author', 'George RR Martin');
        $response->assertJsonPath('results.0.title', 'A CLASH OF KINGS');
        $response->assertJsonPath('num_results', 1);
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
        $response->assertJsonPath('num_results', 742);
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
