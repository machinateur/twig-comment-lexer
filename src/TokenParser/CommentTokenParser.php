<?php

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
        $isComment = true;

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
            $isComment = false;
        }

        $stream->expect(CommentToken::COMMENT_TEXT_TYPE);
        $text = $token->getValue();

        // Finish the tag, which never has a corresponding "endtag".
        $stream->expect(Token::BLOCK_END_TYPE);

        return new CommentNode($text, $isComment, $token->getLine());
    }

    public function getTag(): string
    {
        return 'comment';
    }
}
