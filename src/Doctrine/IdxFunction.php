<?php

declare(strict_types=1);

namespace Pander\DataLoaderSupport\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

class IdxFunction extends FunctionNode {
  private array $elements = [];
  private Node|string $index;

  public function parse(Parser $parser): void {
    $parser->match(TokenType::T_IDENTIFIER);
    $parser->match(TokenType::T_OPEN_PARENTHESIS);

    // Parse array elements until we hit a comma followed by the index
    while (true) {
      $this->elements[] = $parser->ArithmeticPrimary();

      if ($parser->getLexer()->isNextToken(TokenType::T_COMMA)) {
        $parser->match(TokenType::T_COMMA);

        // Check if next token is our index rather than another array element
        if (!$parser->getLexer()->isNextToken(TokenType::T_INTEGER)) {
          $this->index = $parser->ArithmeticPrimary();
          break;
        }
      }
    }

    $parser->match(TokenType::T_CLOSE_PARENTHESIS);
  }

  public function getSql(SqlWalker $sqlWalker): string {
    $arrayElements = array_map(
      fn ($element) => $element->dispatch($sqlWalker),
      $this->elements
    );

    return \sprintf(
      'IDX(ARRAY[%s], %s)',
      implode(',', $arrayElements),
      $this->index->dispatch($sqlWalker)
    );
  }
}
