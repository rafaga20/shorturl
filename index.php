<?php

namespace core;

include 'core/Config.php';

Config::init();
User::init();
Session::init();
Http::init();