<?php

namespace App\Utility;

class QueryParser
{
    private static ?self $parserInstance = null;

    public static function parse(string $query): array
    {
        return iterator_to_array(self::instance()->structure($query));
    }

    private static function instance(): self
    {
        if (self::$parserInstance === null) {
            self::$parserInstance = new self();
        }
        return self::$parserInstance;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function structure(string $query): iterable
    {
        $state = "b";
        $acc = "";
        $type = null;
        foreach ($this->strings($query) as list($stringType, $string)) {
            switch ($stringType) {
                case 'q':
                    if ($state === "b") {
                        $state = "q";
                    }
                    $acc .= $string;
                    break;
                case 'r':
                    if ($state === "b") {
                        $state = "r";
                    }
                    $acc .= $string;
                    break;
                case 's':
                    if ($acc !== "") {
                        yield [$type, $acc];
                    }
                    $type = null;
                    $acc = "";
                    break;
                case 'c':
                    if ($state === "r") {
                        $type = $acc;
                        $acc = "";
                        $state = "q";
                    } else {
                        $acc .= $string;
                    }
                    break;
            }
        }
        if ($acc !== "") {
            yield [$type, $acc];
        }
    }

    private function strings(string $query): iterable
    {
        $state = "r";
        $acc = "";
        $escape = false;
        foreach ($this->tokens($query) as list($tokenType, $tokenString)) {
            if ($escape) {
                $escape = false;
                if ($tokenType === 'q' || $tokenType === 'e') {
                    $tokenType = 'r';
                } else {
                    $tokenType = 'r';
                    $tokenString = '\\' . $tokenString;
                }
            }
            switch ($tokenType) {
                case 'q':
                    if ($state === 'r') {
                        $state = $tokenString;
                    } elseif ($state === $tokenString) {
                        yield ["q", $acc];
                        $acc = "";
                    } else {
                        $acc .= $tokenString;
                    }
                    break;
                case 'e':
                    $escape = true;
                    break;
                case 'c':
                case 's':
                    if ($state === 'r') {
                        if ($acc !== '') {
                            yield ['r', $acc];
                            $acc = '';
                        }
                        yield [$tokenType, $tokenString];
                    } else {
                        $acc .= $tokenString;
                    }
                    break;
                case 'r':
                    $acc .= $tokenString;
                    break;
            }
        }
        if ($escape) {
            $acc .= '\\';
        }
        if ($acc !== "") {
            yield ["q", $acc];
        }
    }

    private function tokens(string $query): iterable
    {
        $accType = null;
        $acc = '';
        foreach ($this->chars($query) as $char) {
            if ($char === '"' || $char === "'") {
                if ($accType !== null) {
                    yield [$accType, $acc];
                    $acc = '';
                    $accType = null;
                }
                yield ["q", $char];
            } elseif ($char === '\\') {
                if ($accType !== null) {
                    yield [$accType, $acc];
                    $acc = '';
                    $accType = null;
                }
                yield ["e", $char];
            } elseif ($char === ':') {
                if ($accType !== null) {
                    yield [$accType, $acc];
                    $acc = '';
                    $accType = null;
                }
                yield ["c", $char];
            } elseif (ctype_space($char)) {
                if ($accType !== null && $accType !== 's') {
                    yield [$accType, $acc];
                    $acc = '';
                    $accType = null;
                }
                $accType = 's';
                $acc .= $char;
            } else {
                if ($accType !== null && $accType !== 'r') {
                    yield [$accType, $acc];
                    $acc = '';
                    $accType = null;
                }
                $accType = 'r';
                $acc .= $char;
            }
        }
        if ($accType !== null) {
            yield [$accType, $acc];
        }
    }

    private function chars(string $query): iterable
    {
        $length = strlen($query);
        for ($i = 0; $i < $length; $i++) {
            yield $query[$i];
        }
    }
}
