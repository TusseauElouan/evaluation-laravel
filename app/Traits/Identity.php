<?php

namespace App\Traits;

use Str;

trait Identity
{
    /** @return string  */
    public function getIdentityAttribute()
    {
        return ucwords($this->prenom, " \t\r\n\f\v-'") . ' ' . ucwords($this->nom, " \t\r\n\f\v-'");
    }

    /** @return string  */
    public function getInitialsAttribute()
    {
        return Str::upper($this->prenom ?? '')[0] . Str::upper($this->nom ?? '')[0];
    }
}
