<?php

namespace Webkul\ProductTag\Repositories;

use Prettus\Repository\Eloquent\BaseRepository;
use Webkul\ProductTag\Contracts\Tag;

class TagRepository extends BaseRepository
{
    public function model(): string
    {
        return Tag::class;
    }

    /**
     * Find or create tags by name for the given locale.
     *
     * @param  array<string>  $names
     * @return array<int> Tag IDs
     */
    public function syncByNames(array $names, string $locale = 'ru'): array
    {
        $ids = [];

        foreach ($names as $name) {
            $name = trim($name);

            if ($name === '') {
                continue;
            }

            $tag = $this->firstOrCreate(
                ['name' => $name, 'locale' => $locale],
                ['name' => $name, 'locale' => $locale]
            );

            $ids[] = $tag->id;
        }

        return $ids;
    }
}
