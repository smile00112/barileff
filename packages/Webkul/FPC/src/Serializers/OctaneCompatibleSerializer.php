<?php

namespace Webkul\FPC\Serializers;

use Illuminate\Http\Response;
use Spatie\ResponseCache\Exceptions\CouldNotUnserialize;
use Spatie\ResponseCache\Serializers\Serializer;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Stores response data as a plain JSON array instead of serializing the
 * ResponseHeaderBag PHP object. The default PHP serializer carries Octane
 * worker-specific state (accumulated header bag internals) across requests,
 * which can crash RoadRunner workers when the cached response is served.
 */
class OctaneCompatibleSerializer implements Serializer
{
    public function serialize(SymfonyResponse $response): string
    {
        return json_encode([
            'statusCode' => $response->getStatusCode(),
            'headers' => $response->headers->all(),
            'content' => $response->getContent(),
        ]);
    }

    public function unserialize(string $serializedResponse): SymfonyResponse
    {
        $data = json_decode($serializedResponse, true);

        if (! is_array($data) || ! isset($data['content'], $data['statusCode'])) {
            throw CouldNotUnserialize::serializedResponse($serializedResponse);
        }

        $response = new Response($data['content'], $data['statusCode']);

        foreach ($data['headers'] ?? [] as $name => $values) {
            $response->headers->set($name, $values);
        }

        return $response;
    }
}
