<?php

function fmtDateTime(?string $dt): string
{
    if (!$dt) return '—';
    $ts = strtotime($dt);
    if (!$ts) return $dt;
    return date('Y-m-d \a\t g:i A', $ts);
}

function money($amount): string
{
    if ($amount === null || $amount === '') return '—';
    return number_format((float)$amount, 2) . ' €';
}
