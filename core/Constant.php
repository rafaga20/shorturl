<?php

namespace core;

class Constant
{
    // TODO: constant from views
    public const PREG_VARIABLE = '/\{\{([a-zA-Z0-9_]+)\}\}/';
    public const PREG_INCLUDE = '/\[\:([a-zA-Z0-9.]+)\:\]/';
    public const PREG_PARENT = '/\@\{\.\.\.\}\@/';
    public const ASSETS_VERSION = '1.0';

    // TODO: constant header html
    public const HTML_HEADER_TITLE = 'ShortUrl';
    public const HTML_HEADER_DESCRIPTION = 'Acortador de url';
    public const HTML_HEADER_ICON = 'assets/img/favicon.ico';

    // TODO: constant from pool connection
    public const SQL_LOCALHOST = 'localhost';
    public const SQL_USERNAME = '';
    public const SQL_PASSWORD = '';
    public const SQL_DATABASE = '';

    // TODO: constant from endpoints
    public const AUTH_ENDPOINT_DEFAULT = '/index';
    public const NO_AUTH_ENDPOINT_DEFAULT = '/';

    // TODO: constant from http header
    public const CONTENT_TYPE = 'Content-Type';
    public const CONTENT_LENGTH = 'Content-Length';
    public const CACHE = 'Cache-Control';
    public const CACHE_NONE = 'none';
    public const CONTENT_TYPE_JSON = 'application/json';
    public const CONTENT_TYPE_TEXT = 'text/plain';
    public const CONTENT_TYPE_PDF = 'application/pdf';
    public const CONTENT_TYPE_HTML = 'text/html; charset=utf-8';
    public const CONTENT_TYPE_CSS = 'text/css';
    public const CONTENT_TYPE_JS = 'text/javascript';
    public const ALLOW_METHODS = ['GET', 'POST', 'DELETE', 'PUT'];
    public const ALLOW_HEADERS = ['Content-Type', 'Authorization'];

    // TODO: constant from user session
    public const SESSION_USER_KEY = '__user_data__';
    public const SESSION_EXPIRE = '+1 hour';

    // TODO: constant from configuration
    public const TIMEZONE = 'America/Santo_Domingo';
    public const KEY_CRYPT = 'Km*&vAZK&YuYW3WgaR3Gx)^#jFw%TDr&';

    // TODO: constant from request
    public const LOG_INFO = 1;
    public const LOG_WARNING = 2;
    public const LOG_ERROR = 3;
    public const LOG_INTERNAL_ERROR = 4;
    public const LOG_LEVEL = 1;

    // TODO: constant from email credentials
    public const EMAIL_USER = 'bug@minayabeltran.com';
    public const EMAIL_ERROR = 'bug@minayabeltran.com';

    // TODO: restricted page
    public const IP_ALLOW = ['148.255.31.140'];
}