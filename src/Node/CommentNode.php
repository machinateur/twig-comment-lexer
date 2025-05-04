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

namespace Machinateur\Twig\Node;

use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * A node representing a comment inside the template source code.
 */
#[YieldReady]
class CommentNode extends Node
{
    public function __construct(
        public readonly string $text,
        public readonly bool   $exposed = false,
        int                    $lineno  = 0,
    ) {
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

        if ($env->isDebug() && $this->exposed) {
            $compiler->write(\sprintf('/* comment on line %d', $this->getTemplateLine()) . "\n");
            // Allow comments to be transferred to the compiled template source, when in debug mode.
            foreach (\explode("\n", $this->text) as $line) {
                $compiler->write(\str_replace('*/', '', $line) . "\n");
            }
            $compiler->write("*/\n");
        }
    }
}
