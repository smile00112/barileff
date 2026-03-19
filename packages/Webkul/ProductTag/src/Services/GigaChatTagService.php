<?php

namespace Webkul\ProductTag\Services;

use Tigusigalpa\GigaChat\Laravel\GigaChat;
use Webkul\Product\Contracts\Product;

class GigaChatTagService
{
    /**
     * Generate tag suggestions for a product using GigaChat AI.
     *
     * Returns an array of tag name strings (up to $limit items).
     *
     * @return array<string>
     */
    public function generateTags(Product $product, int $limit = 10): array
    {
        $name = $product->name ?? '';
        $description = strip_tags((string) ($product->description ?? ''));
        $description = mb_substr($description, 0, 500);

        $prompt = <<<PROMPT
        Ты помощник интернет-магазина. Сгенерируй {$limit} коротких тегов (ключевых слов) для товара.
        Теги должны быть на русском языке, через запятую, без нумерации.
        Включи: синонимы, возможные опечатки, транслитерацию, сленговые варианты.

        Название товара: {$name}
        Описание: {$description}

        Ответь только строкой тегов через запятую, например: тег1, тег2, тег3
        PROMPT;

        $response = GigaChat::ask($prompt);

        return $this->parseResponse($response, $limit);
    }

    /**
     * Parse the raw GigaChat response into an array of tag strings.
     *
     * @return array<string>
     */
    protected function parseResponse(string $response, int $limit): array
    {
        $tags = explode(',', $response);

        return array_values(array_filter(
            array_map(fn (string $tag) => mb_strtolower(trim($tag)), $tags),
            fn (string $tag) => $tag !== '' && mb_strlen($tag) <= 100
        ));
    }
}
