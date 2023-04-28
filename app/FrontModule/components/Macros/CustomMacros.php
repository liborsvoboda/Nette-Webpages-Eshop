<?php


namespace App\Components\Macros;


use Latte\Compiler;
use Latte\MacroNode;
use Latte\Macros\MacroSet;
use Latte\PhpWriter;

class CustomMacros extends MacroSet
{
    public static function install(Compiler $compiler)
    {
        $set = new static($compiler);
        $set->addMacro('test1', array($set, 'macroTest1'));
        return $set;
    }

    public function macroTest1(MacroNode $node, PhpWriter $writer)
    {
        return $writer->write('echo App\Components\Macros\CustomMacros::renderMacroTest1(%node.word)');
    }

    public static function renderMacroTest1($word = null)
    {
        return 'MacroTest1';
    }
}