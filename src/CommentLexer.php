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

namespace Machinateur\Twig;

use Machinateur\Twig\Token\CommentToken;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Lexer;
use Twig\Source;
use Twig\Token;
use Twig\TokenStream;

/**
 * A lexer extension on top of the default `twig/twig` lexer.
 *  It adds a custom comment state, that allows parsing `comment` tags in the later steps.
 *   Those comments can then be visited and processed by other extensions.
 *
 * @see CommentToken
 */
class CommentLexer extends Lexer
{
    public const STATE_COMMENT = 5;

    private \Closure $tokenize;

    public function __construct(Environment $env, array $options = [])
    {
        parent::__construct($env, $options);

        // Out-scope the original regex, to maintain it for next calls.
        $originalRegexComment = null;

        // Use the kitchen-thief: https://ocramius.github.io/blog/accessing-private-php-class-members-without-reflection/.
        $this->tokenize = \Closure::bind(function (Lexer $lexer, Source $source) use (&$originalRegexComment): TokenStream {
            $lexer->initialize();

            $originalRegexComment ??= $lexer->regexes['lex_comment'];
            // Always fail when lexing comments... https://regex101.com/r/sMNHfe/1
            $lexer->regexes['lex_comment'] = '{(?!)\n}sx';

            $restart = true;
            while (true) {
                try {
                    if ($restart) {
                        $restart = false;
                        /** @var CommentLexer $lexer */
                        return $lexer->tokenize($source, true);
                    }

                    // The below code is copied from the original lexer.

                    while ($lexer->cursor < $lexer->end) {
                        // dispatch to the lexing functions depending
                        // on the current state
                        switch ($lexer->state) {
                            case Lexer::STATE_DATA:
                                $lexer->lexData();
                                break;

                            case Lexer::STATE_BLOCK:
                                $lexer->lexBlock();
                                break;

                            case Lexer::STATE_VAR:
                                $lexer->lexVar();
                                break;

                            case Lexer::STATE_STRING:
                                $lexer->lexString();
                                break;

                            case Lexer::STATE_INTERPOLATION:
                                $lexer->lexInterpolation();
                                break;

                            // Modification: Added comment mode, to add comment tokens/tag.
                            case CommentLexer::STATE_COMMENT:
                                $lexer->pushToken(CommentToken::COMMENT_START_TYPE, $lexer->options['tag_comment'][0]);
                                $lexer->pushToken(Token::NAME_TYPE, 'comment');

                                /**
                                 * @var $match array<int,array{0:string,1:int}>
                                 */
                                if (!\preg_match($originalRegexComment, $lexer->code, $match, \PREG_OFFSET_CAPTURE, $lexer->cursor)) {
                                    $exception = new \UnexpectedValueException('Comment not matched.', $lexer->cursor);

                                    throw new SyntaxError('Unclosed comment.', $lexer->lineno, $lexer->source, $exception);
                                }

                                $text = \substr($lexer->code, $lexer->cursor, $match[0][1] - $lexer->cursor);

                                $lexer->pushToken(CommentToken::COMMENT_TEXT_TYPE, $text);
                                $lexer->pushToken(CommentToken::COMMENT_END_TYPE, $lexer->options['tag_comment'][1]);
                                $lexer->moveCursor($text . $match[0][0]);
                                $lexer->popState();

                                unset($text);
                                break;

                            default:
                                throw new \LogicException('Invalid state.');
                        }
                    }

                    $lexer->pushToken(Token::EOF_TYPE);

                    if ($lexer->brackets) {
                        [$expect, $lineno] = \array_pop($lexer->brackets);
                        throw new SyntaxError(\sprintf('Unclosed "%s".', $expect), $lineno, $lexer->source);
                    }

                    // Stop upon successfully reaching EOF.
                    break;
                } catch (SyntaxError $error) {
                    // If this was a comment error, switch to comment mode and resume.
                    if ('Unclosed comment.' === $error->getRawMessage()
                        && null ===$error->getPrevious()
                    ) {
                        $lexer->pushState(CommentLexer::STATE_COMMENT);
                        // Restart the loop.
                        continue;
                    }

                    // If not a comment error, re-throw the error.
                    throw $error;
                }
            }

            // Return the result.
            return new TokenStream($lexer->tokens, $lexer->source);
        }, null, Lexer::class);
    }

    /**
     * @throws SyntaxError
     */
    public function tokenize(Source $source, bool $bypass = false): TokenStream
    {
        if ($bypass) {
            return parent::tokenize($source);
        }

        return ($this->tokenize)($this, $source);
    }
}
