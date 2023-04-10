<?php

use Illuminate\Support\Str;

function generateRandom($strLength = 12)
{
    return  Str::random($strLength);
}
