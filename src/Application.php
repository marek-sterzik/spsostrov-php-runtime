<?php

namespace App;

class Application
{
    public function __construct()
    {
    }

    public function run()
    {
        header("Content-Type: text/html; charset=utf-8");
        readfile(APP_ROOT."/templates/index.html");
    }
}
