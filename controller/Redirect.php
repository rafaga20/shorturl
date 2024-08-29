<?php

namespace controller;

use core\Controller;
use core\View;

class Redirect extends Controller

{
    public function redirect(): View
    {
        return $this->view('Hola Mundo');
    }
}