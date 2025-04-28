<?php

declare(strict_types=1);

namespace Machinateur\Twig\Node;

use Twig\Compiler;
use Twig\Node\Node;

/**
 * A node representing a comment inside the template source code.
 */
class CommentNode extends Node
{
    public function __construct(public readonly string $text, int $lineno = 0)
    {
        parent::__construct(lineno: $lineno);
    }

    /**
     * Disallow setting any child nodes, same as with {@see \Twig\Node\EmptyNode}.
     */
    public function setNode(string $name, Node $node): void
    {
        throw new \LogicException('CommentNode cannot have children.');
    }

    /**
     * Compiling a `CommentNode` is no-op, unless in debug mode. There, comments will be added to the compiled template.
     */
    public function compile(Compiler $compiler): void
    {
        $env = $compiler->getEnvironment();

        if ($env->isDebug()) {
            $compiler->write(\sprintf('/* comment: line %d', $this->getTemplateLine()) . "\n");
            // Allow comments to be transferred to the compiled template source, when in debug mode.
            foreach (\explode("\n", $this->text) as $line) {
                $compiler->write(\str_replace('*/', '', $line) . "\n");
            }
            $compiler->write("*/\n");
        }
    }
}
