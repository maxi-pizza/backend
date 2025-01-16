<?php

namespace App\Classes;

class Receipt
{
    public $emojis = [
        'document' => "\xF0\x9F\x93\x83 "
    ];

    protected $txt = "";

    public function __construct($txt = "")
    {
        $this->txt = $txt;

    }

    public function map($array, \Closure $callable): Receipt {
        $cl = $callable->bindTo($this);
        foreach($array as $item) {
            $cl($item);
        }
        return $this;
    }

    public function product($name, $count): Receipt
    {
        return $this->hyphen()
            ->space()
            ->p($name)
            ->space()
            ->p("x")
            ->p($count);
    }

    public function headline($headline): Receipt
    {
        return $this->emoji($this->emojis['document'])
            ->b($headline)
            ->newLine()
            ->newLine();
    }

    public function field($key, $txt): Receipt
    {
        if (!isset($txt)) {
            return $this;
        }
        if (empty($txt)) {
            return $this;
        }
        return $this->b($key)
            ->colon()
            ->space()
            ->p($txt)
            ->newLine();
    }

    public function colon(): Receipt
    {
        return $this->p(":");
    }

    public function space(): Receipt
    {
        return $this->p(" ");
    }

    public function hyphen(): Receipt
    {
        return $this->p("-");
    }

    public function newLine(): Receipt
    {
        return $this->p("\n");
    }

    public function b($txt): Receipt
    {
        return $this->p("<b>" . $txt . "</b>");
    }

    public function emoji($emoji): Receipt
    {
        return $this->p($emoji);
    }

    public function p($txt): Receipt
    {
        if (isset($txt)) {
            $this->txt .= $txt;
        }
        return $this;
    }

    public function getText(): string
    {
        return $this->txt;
    }
}
