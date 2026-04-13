@props([
    'location' => 'header',
    'class'    => '',
])

@php
    $items = app('menuManager')->getByLocation($location);

    $renderItems = function ($nodes) use (&$renderItems) {
        $html = '';

        foreach ($nodes as $node) {
            $url = app('menuManager')->resolveUrl($node);

            $html .= '<li class="menu-item">';
            $html .= '<a href="' . e($url) . '" target="' . e($node->target) . '">' . e($node->title) . '</a>';

            if ($node->children->isNotEmpty()) {
                $html .= '<ul class="menu-sub-items">';
                $html .= $renderItems($node->children);
                $html .= '</ul>';
            }

            $html .= '</li>';
        }

        return $html;
    };
@endphp

<nav class="{{ $class }}" data-menu-location="{{ $location }}">
    <ul class="menu-items">
        {!! $renderItems($items) !!}
    </ul>
</nav>
