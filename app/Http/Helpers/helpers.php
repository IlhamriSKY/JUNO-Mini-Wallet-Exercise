<?php

function generateToken()
{
    return bin2hex(random_bytes(32));
}

function getToken($reqeust)
{
    $token = substr($reqeust, 6);
    return $token;
}
