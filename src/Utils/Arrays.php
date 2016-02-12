<?php

namespace Utils\Arrays;

function isAssociative(array $arr): bool {
    return array_keys($arr) !== range(0, count($arr) - 1);
}