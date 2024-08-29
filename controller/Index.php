<?php

namespace controller;

use core\Controller;
use core\Request;
use core\View;

class Index extends Controller
{
    public function index(): View
    {
        return $this->view('home.index', parent: 'index');
    }

    public function redirect(Request $request, $id): View
    {
        return $this->view("Hola Mundo ($id)");
    }
}