<?php
use Overblog\DataLoader\DataLoader;
return [
    'Book' => [
        'author' => function ($book, $args, $context) {
            return $context['loaders']['author']
                    ->load($book['author_id']);
        }
    ],
    'Query' => [
        'getBooks' => function ($root, $args, $context) {
            return $context['db']
                   ->fetchAll("SELECT * FROM book");
        }
    ]
];