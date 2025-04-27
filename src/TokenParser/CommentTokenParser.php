<?php

declare(strict_types=1);

namespace Machinateur\Twig\TokenParser;

use Machinateur\Twig\Node\CommentNode;
use Machinateur\Twig\Token\CommentToken;
use Twig\Error\SyntaxError;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class CommentTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $stream = $this->parser->getStream();

        if ( ! $this->parser->peekBlockStack()) {
            throw new SyntaxError('Cannot use "sw_version" outside of a block.', $token->getLine(), $stream->getSourceContext());
        }
        if ( ! $this->parser->isMainScope()) {
            throw new SyntaxError('Cannot use "sw_version" in a macro.', $token->getLine(), $stream->getSourceContext());
        }

        // Get the actual token the stream is pointing to.
        $token = $stream->getCurrent();
        // Convert a string value to the expected text value.
        if ($token->test(Token::STRING_TYPE)) {
            $stream->injectTokens([
                new Token(CommentToken::COMMENT_TEXT_TYPE, $token->getValue(), $token->getLine()),
            ]);
            $stream->next();
        }

        $stream->expect(CommentToken::COMMENT_TEXT_TYPE);
        $text = $token->getValue();
        //if (\is_array($text)) {
        //    $text = \implode("\n", $text);
        //}

        // Finish the tag, which never has a corresponding "endtag".
        $stream->expect(Token::BLOCK_END_TYPE);

        return new CommentNode($text, $token->getLine());
    }

    public function getTag(): string
    {
        return 'comment';
    }
}
