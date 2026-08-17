<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Enums;

enum Method: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
    case OPTIONS = 'OPTIONS';
    case HEAD = 'HEAD';
    case CONNECT = 'CONNECT';
    case TRACE = 'TRACE';
    case PURGE = 'PURGE';
    case COPY = 'COPY';
    case LOCK = 'LOCK';
    case UNLOCK = 'UNLOCK';
    case PROPFIND = 'PROPFIND';
    case VIEW = 'VIEW';
    case ALL = "GET,POST,PUT,PATCH,DELETE,OPTIONS,HEAD,CONNECT,TRACE,PURGE,COPY,LOCK,UNLOCK,PROPFIND,VIEW";
}
