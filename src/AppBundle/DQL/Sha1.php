<?php
namespace App\AppBundle\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class Sha1 extends FunctionNode
{
    public $value;

    public function parse(Parser $parser): void
    {
        // Parse the function name (SHA1)
        $parser->match(Lexer::T_IDENTIFIER);
        
        // Open parenthesis
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        // Parse the value inside the function (string or column)
        $this->value = $parser->StringPrimary();

        // Close parenthesis
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        // Generate the corresponding SQL for the SHA1 function
        return 'SHA1(' . $this->value->dispatch($sqlWalker) . ')';
    }
}
