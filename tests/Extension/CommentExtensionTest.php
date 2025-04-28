<?php

declare(strict_types=1);

namespace Machinateur\Twig\Tests\Extension;

use Machinateur\Twig\Extension\CommentExtension;
use Machinateur\Twig\Token\CommentToken;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Template;
use Twig\TemplateWrapper;
use Twig\Token;
use Twig\TokenStream;

class CommentExtensionTest extends TestCase
{
    private ?Environment $environment = null;

    protected function setUp(): void
    {
        $this->environment = new Environment(
            new ArrayLoader([
                '_base.html.twig' => /** @lang HTML */ <<<'TWIG'
{# This is the base template. #}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{% block title %}{{ title }}{% endblock %}</title>
</head>
<body>
{% block content %}
{% endblock %}
</body>
</html>
TWIG,
                'test.html.twig' => /** @lang HTML */ <<<'TWIG'
{% extends '_base.html.twig' %}

{# This is the test template. Comments will be lexed and can be parsed. #}
{% comment 'a string comment via twig-tag works as well' %}

{% block content %}
    {# some-comment-tag: my lexed comment #}
    <p>This test is tagged and uses inheritance.</p>
{% endblock %}

{# Some top-level comment, just for testing. #}
TWIG,
                'category.html.twig' => /** @lang HTML */ <<<'TWIG'
{% extends '_base.html.twig' %}

{% block content %}
  {# package: machinateur/twig-comment-lexer@1.0.0-test #}

  <h1>{{ category.name }}</h1>
{% endblock %}
TWIG,
                ]),
            ['debug' => false],
        );
        CommentExtension::setLexer($this->environment);
        $this->environment->addExtension(new CommentExtension());
    }

    protected function tearDown(): void
    {
        $this->environment = null;
    }

    public function testCompileTemplate(): void
    {
        $template    = $this->environment->load('test.html.twig');
        self::assertInstanceOf(TemplateWrapper::class, $template);

        $rawTemplate = $template->unwrap();
        self::assertInstanceOf(Template::class, $rawTemplate);
    }

    public function testCompileSourceDebugComment(): void
    {
        $environment = new Environment($this->environment->getLoader(), ['debug' => true]);
        CommentExtension::setLexer($environment);
        $environment->addExtension(new CommentExtension());

        $source = $environment->compileSource(
            $environment->getLoader()
                ->getSourceContext('test.html.twig')
        );

        $pattern = <<<'TEXT'
        /* comment on line 4
        a string comment via twig-tag works as well
        */
TEXT;
        self::assertStringContainsString($pattern,  $source);
    }

    /**
     * @dataProvider provideTemplateComments
     *
     * @param array<string> $comments
     */
    public function testTokenizeTemplate(string $template, array $comments): void
    {
        $source = $this->environment->getLoader()
            ->getSourceContext($template);
        $stream =$this->environment->tokenize($source);

        foreach ($this->generateComments($stream) as $i => $comment) {
            self::assertSame(\array_shift($comments), $comment->getValue());
        }

        if (\count($comments) > 0) {
            self::fail('Mismatch in comment count.');
        }
    }

    /**
     * @return array<int,array{0:string,1:array}>
     */
    private function provideTemplateComments(): array
    {
        return [
            '_base.html.twig' => ['_base.html.twig', [
                ' This is the base template. ',
            ]],
            'test.html.twig' => ['test.html.twig', [
                ' This is the test template. Comments will be lexed and can be parsed. ',
                ' some-comment-tag: my lexed comment ',
                ' Some top-level comment, just for testing. ',
            ]],
            'category.html.twig' => ['category.html.twig', [
                ' package: machinateur/twig-comment-lexer@1.0.0-test ',
            ]],
        ];
    }

    private function generateComments(TokenStream $stream): \Generator
    {
        while ( ! $stream->isEOF())
        {
            if ( ! $stream->next()->test(CommentToken::COMMENT_START_TYPE, '{#')) {
                continue;
            }
            $stream->expect(Token::NAME_TYPE, 'comment');

            while ( ! ($token = $stream->next())->test(CommentToken::COMMENT_END_TYPE, '#}')) {
                yield $token;
            }
        }
    }
}
