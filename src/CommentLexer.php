<?php

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

        // Use the kitchen-thief: https://ocramius.github.io/blog/accessing-private-php-class-members-without-reflection/.
        $this->tokenize = \Closure::bind(function (Lexer $lexer, Source $source): TokenStream {
            $originalRegexComment  = $lexer->regexes['lex_comment'];

            // Always fail when lexing comments... https://regex101.com/r/sMNHfe/1
            $lexer->regexes['lex_comment'] = '{(?!)\n}sx';

            while (true) {
                try {
                    if (!isset($error)) {
                        return $lexer->tokenize($source);
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
                                $lexer->pushToken(CommentToken::COMMENT_START_TYPE, \strlen($lexer->options['tag_comment'][2]));

                                /**
                                 * @var $match array<int,array{0:string,1:int}>
                                 */
                                if (!\preg_match($originalRegexComment, $lexer->code, $match, \PREG_OFFSET_CAPTURE, $lexer->cursor)) {
                                    throw new SyntaxError('Unclosed comment.', $lexer->lineno, $lexer->source);
                                }

                                $text = \substr($lexer->code, $lexer->cursor, $match[0][1] - $lexer->cursor - \strlen($lexer->options['tag_comment'][1]));

                                $lexer->pushToken(CommentToken::COMMENT_TEXT_TYPE, $text);
                                $lexer->pushToken(CommentToken::COMMENT_END_TYPE, \strlen($lexer->options['tag_comment'][1]));
                                $lexer->moveCursor($text . $lexer->options['tag_comment'][1] . $match[0][0]);
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
                    if ('Unclosed comment.' === $error->getMessage()) {
                        $lexer->pushState(CommentLexer::STATE_COMMENT);
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

    public function tokenize(Source $source): TokenStream
    {
        return ($this->tokenize)($this, $source);
    }
}
