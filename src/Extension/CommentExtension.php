<?php

declare(strict_types=1);

namespace Machinateur\Twig\Extension;

use Machinateur\Twig\CommentLexer;
use Machinateur\Twig\TokenParser\CommentTokenParser;
use Twig\Environment;
use Twig\Extension\AbstractExtension;

/**
 * A twig extension to allow processing comments using a custom lexer implementation.
 *  The comment nodes can later be processed using a node-visitor.
 *
 * Out of necessity, this extension also provides a `comment` tag, which can also be used anywhere.
 *  It accepts raw text or constant strings, but no variables.
 *
 * @see CommentLexer
 * @see CommentExtension::setLexer()
 */
class CommentExtension extends AbstractExtension
{
    public static function setLexer(Environment $twig): void
    {
        $twig->setLexer(
            new CommentLexer($twig)
        );
    }

    public function getTokenParsers(): array
    {
        return [
            new CommentTokenParser(),
        ];
    }
}
