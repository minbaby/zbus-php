<?php

/**
 * @info php > 5.6 支持 const 为数组
 */
const HTTP_STATUS_TABLE = [
    200 => "OK",
    201 => "Created",
    202 => "Accepted",
    204 => "No Content",
    206 => "Partial Content",
    301 => "Moved Permanently",
    304 => "Not Modified",
    400 => "Bad Request",
    401 => "Unauthorized",
    403 => "Forbidden",
    404 => "Not Found",
    405 => "Method Not Allowed",
    416 => "Requested Range Not Satisfiable",
    500 => "Internal Server Error",
];
