<?php

declare(strict_types=1);

namespace Machinateur\Twig\Token;

use Twig\Token;

/**
 * Custom token type mapping.
 */
final class CommentToken
{
    public const COMMENT_TEXT_TYPE  = Token::TEXT_TYPE;
    public const COMMENT_START_TYPE = Token::BLOCK_START_TYPE;
    public const COMMENT_END_TYPE   = Token::BLOCK_END_TYPE;

    private function __construct()
    {}
}
