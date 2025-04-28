# machinateur/twig-comment-lexer

A custom twig lexer and extension to make comments available for parsing.

Comments like the one below are usually not available in twig for parsing.

```
{# some comment text #}
```

This package provides a lexer extension for twig, which makes comments available for node visitors!
 That means we can now implement parsing of twig comments, to extract template meta-data for certain applications!

Additionally, in debug mode, this extension exposes the `comment` tag in the compiled twig template class,
 which should make deep debugging easier by providing more information than only the line number (as done by default).

```
{% comment 'expose a comment to the raw template class' %}
{% comment 'no warranty if you break things, but */ works' %}
```

The value accepted by the `comment` tag is a constant string only, no interpolation, no nothing. Just a raw string.
 Quotes are not necessary for the native comment usage.

## Requirements

- PHP  `>=8.1.0`
- Twig `^3.15`

## Installation

Use composer to install this twig extension.

```bash
composer require machinateur/twig-comment-lexer
```

## Intended usage

The lexer can be installed for an environment by calling `\Machinateur\Twig\Extension\CommentExtension::setLexer($twig)`,
 where `$twig` is the `\Twig\Environment` to set the lexer for. Have a look at the tests for an overview.

More out of necessity than as an actual feature, this twig extension allows for exposing comments in template code.
 This only works when in debug mode, for security reasons. Debug mode should not be active on production environments.

```twig
{% comment 'Comment test using real tag' %}
```

Which will become:

```php
 /* comment on line 1
 Comment test using real tag
  */
```

Apart from that, this lexer extension allows for quite creative implementations.
 A more interesting way this can be used, is to extract template context based on comments (at compile time):

```php
<?php

declare(strict_types=1);

namespace Machinateur\Twig\NodeVisitor;

use Machinateur\Twig\Node\CommentNode;
use Twig\Environment;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * some simple example class
 */
class CommentNodeVisitor implements NodeVisitorInterface
{
    public function enterNode(Node $node, Environment $env): Node
    {
        if ($node instanceof ModuleNode) {
            // TODO: Set up collector node to expose methods in compiled source, if needed at runtime.
        }

        return $node;
    }

    public function leaveNode(Node $node, Environment $env): ?Node
    {
        if ($node instanceof CommentNode) {
            // TODO: Do stuff to the comment's content...
            //  And track it inside the collector node, if needed at runtime.
        }

        return $node;
    }

    /**
     * Priority `0` is the default.
     */
    public function getPriority(): int
    {
        return 0;
    }
}
```

Useful reading, regarding collecting context during compile time:

> https://matthiasnoback.nl/2013/01/symfony2-twig-collecting-data-across-templates-using-a-node-visitor/

An example on how one can expose compile-time information at runtime,
 is the [`machinateur/twig-context-tags`](https://github.com/machinateur/twig-context-tags) library.

## Limitations and compatibility

Since this library is quite dependent on private code structure of twig's own lexer class.
 So it could break easily when internals change with newer versions.

## Tests

Run tests using the composer script:

```bash
composer test
```

## License

It's MIT.
