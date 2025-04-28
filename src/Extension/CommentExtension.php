<?php
/*
 * MIT License
 *
 * Copyright (c) 2025 machinateur
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

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
