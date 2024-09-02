<?php

namespace controller;

use core\Controller;
use core\Db;
use core\Http;
use core\Request;
use core\View;

class Redirect extends Controller
{
    public function redirect(Request $request, $id): void
    {
        if (!$data = Db::get('url', $id, 'id')) {
            Http::redirect('/index');
        }

        if ($data['status'] != 'active') {
            Http::redirect('/index');
        }

        if ($data['expire_at'] <= time()) {
            Db::update('url', ['status' => 'expired'], ['id' => $id]);
            Http::redirect('/index');
        }

        Http::redirect($data['url']);
    }
}