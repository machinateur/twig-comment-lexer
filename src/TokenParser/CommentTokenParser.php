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

namespace Machinateur\Twig\TokenParser;

use Machinateur\Twig\Node\CommentNode;
use Machinateur\Twig\Token\CommentToken;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class CommentTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $stream = $this->parser->getStream();

        // Assume it was an actual comment by default.
        $exposed = false;

        // Get the actual token the stream is pointing to.
        $token = $stream->getCurrent();
        // Convert a string value to the expected text value.
        if ($token->test(Token::STRING_TYPE)) {
            // Move to next token.
            $stream->next();
            $stream->injectTokens([
                new Token(CommentToken::COMMENT_TEXT_TYPE, $token->getValue(), $token->getLine()),
            ]);

            // Detected that it's the real tag instead.
            $exposed = true;
        }

        $stream->expect(CommentToken::COMMENT_TEXT_TYPE);
        $text = $token->getValue();

        // Finish the tag, which never has a corresponding "endtag".
        $stream->expect(Token::BLOCK_END_TYPE);

        return new CommentNode($text, $exposed, $token->getLine());
    }

    public function getTag(): string
    {
        return 'comment';
    }
}
