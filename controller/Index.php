<?php

namespace controller;

use core\Controller;
use core\Db;
use core\Http;
use core\View;

class Index extends Controller
{
    public function index(): View
    {
        if (!$this->request->isMethod('post', 'get')) {
            Http::redirect('/index');
        }

        $view = $this->view('home.index', parent: 'index');
        if ($this->request->isMethod('get')) {
            return $view;
        }

        $id = uniqid();
        $host = $this->request->getHost();
        Db::insert('url', [
            'id' => $id,
            'url' => $this->request->post['url'],
            'expire_at' => strtotime('+1 day')
        ]);

        $view->addVariable('URL', sprintf('https://%s/%s', $host, $id));

        $view->addVariable('MESSAGE', 'Error', true, true);

        return $view;
    }
}