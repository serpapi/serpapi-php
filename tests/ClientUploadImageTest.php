<?php

namespace SerpApi\Tests;

use SerpApi\Client;
use SerpApi\SerpApiException;

/**
 * uploadImage() POSTs an image to the Image API as multipart/form-data.
 * Runs without an API key and without network access.
 */
class ClientUploadImageTest extends SerpApiTestCase
{
  private const IMAGE = __DIR__ . '/fixtures/mona-lisa.jpg';

  private const UPLOADED = '{"message":"Image uploaded successfully.","image_id":"image-123"}';

  protected function requiresApiKey(): bool
  {
    return false;
  }

  public function testUploadImagePostsFileToImageEndpoint()
  {
    $client = (new RecordingClient('secret'))->willReturn(self::UPLOADED);
    $result = $client->uploadImage(self::IMAGE);

    $this->assertEquals('Image uploaded successfully.', $result->message);
    $this->assertEquals('image-123', $result->image_id);

    $this->assertEquals('https://serpapi.com/image', end($client->urls), 'parameters must not leak into the URL');

    $form = $client->lastForm();
    $this->assertEquals('secret', $form['api_key']);
    $this->assertEquals(Client::SOURCE, $form['source']);

    $image = $form['image'];
    $this->assertInstanceOf(\CURLFile::class, $image);
    $this->assertEquals(self::IMAGE, $image->getFilename());
    $this->assertEquals('mona-lisa.jpg', $image->getPostFilename(), 'the local directory must not be sent');
    $this->assertCount(1, $client->uploads);
    $this->assertEquals(sha1_file(self::IMAGE), sha1($client->uploads[0]));
  }

  public function testUploadImageFromStreamSendsItFromTheBeginning()
  {
    $stream = fopen('php://memory', 'w+b');
    fwrite($stream, 'image data');
    fseek($stream, 5);

    $client = (new RecordingClient('secret'))->willReturn(self::UPLOADED);
    $result = $client->uploadImage($stream);

    $this->assertEquals('image-123', $result->image_id);
    $this->assertEquals(['image data'], $client->uploads);

    $image = $client->lastForm()['image'];
    $this->assertEquals('image', $image->getPostFilename());
    $this->assertFalse(file_exists($image->getFilename()), 'the temporary copy must be deleted');
    $this->assertTrue(is_resource($stream), "the caller's stream must stay open");
  }

  public function testUploadImageSendsTheSameParametersAsASearch()
  {
    $client = (new RecordingClient([
      'api_key' => 'secret',
      'hl' => 'en',
      'zero_trace' => true,
    ]))->willReturn(self::UPLOADED);
    $params = ['hl' => 'fr', 'no_cache' => false];

    $client->search($params);
    $query = $client->lastQuery();
    $this->assertNull($client->forms[0], 'a search must stay a GET');

    $client->uploadImage(self::IMAGE, $params);
    $form = $client->lastForm();
    unset($form['image']);

    $this->assertEquals($query, $form);
    $this->assertEquals('1', $form['zero_trace']);
    $this->assertEquals('0', $form['no_cache']);
  }

  public function testUploadImageAcceptsPerCallApiKeyAndTimeout()
  {
    $client = (new RecordingClient(['api_key' => 'secret', 'gl' => 'us']))->willReturn(self::UPLOADED);

    $client->uploadImage(self::IMAGE, ['api_key' => 'other', 'timeout' => 7, 'gl' => null]);

    $form = $client->lastForm();
    $this->assertEquals('other', $form['api_key']);
    $this->assertArrayNotHasKey('timeout', $form);
    $this->assertArrayNotHasKey('gl', $form);
    $this->assertEquals([7], $client->timeouts);
  }

  public function testUploadImageRaisesSerpApiError()
  {
    $client = (new RecordingClient('secret'))->willReturn(
      '{"error":"Invalid image format. Supported format: jpg, jpeg, png, webp"}',
      400
    );

    try {
      $client->uploadImage(self::IMAGE);
      $this->fail('Expected SerpApiException');
    } catch (SerpApiException $e) {
      $this->assertStringContainsString('from url: https://serpapi.com/image', $e->getMessage());
      $this->assertEquals('Invalid image format. Supported format: jpg, jpeg, png, webp', $e->getSerpApiError());
      $this->assertEquals(400, $e->getResponseStatus());
      $this->assertArrayNotHasKey('api_key', (array) $e->getSearchParams());
      $this->assertArrayNotHasKey('image', (array) $e->getSearchParams());
    }
  }

  public function testUploadImageRequiresApiKey()
  {
    $client = new RecordingClient('');

    $this->expectException(SerpApiException::class);
    $this->expectExceptionMessage('api_key must be present');

    $client->uploadImage(self::IMAGE);
  }

  public function testUploadImageRejectsMissingFile()
  {
    $client = new RecordingClient('secret');

    try {
      $client->uploadImage(__DIR__ . '/fixtures/missing.jpg');
      $this->fail('Expected SerpApiException');
    } catch (SerpApiException $e) {
      $this->assertStringContainsString('image is not a readable file', $e->getMessage());
      $this->assertEmpty($client->urls, 'no request must be sent');
    }
  }

  public function testUploadImageRejectsOtherArguments()
  {
    $client = new RecordingClient('secret');

    $this->expectException(SerpApiException::class);
    $this->expectExceptionMessage('image must be a file path or a stream');

    $client->uploadImage(['path' => self::IMAGE]);
  }
}
