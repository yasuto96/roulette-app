<?php
namespace App\Services\StoreFinder;

use Illuminate\Support\Collection;

interface StoreFinder {
    /** @return Collection<int, array{name:string,address:?string,id:?int,place_id:?string,provider:string}> */
    public function search(array $filters): Collection;
}
