<?php

namespace App;

use Carbon\Carbon;
use DateTimeZone;

trait ParsesWordPressDates
{
    protected function parseWordPressDate(string $date): Carbon
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $date, new DateTimeZone('GMT'));
    }
}
