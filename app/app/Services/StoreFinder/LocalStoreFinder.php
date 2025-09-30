<?php
namespace App\Services\StoreFinder;

use App\Models\Restaurant;
use Illuminate\Support\Collection;

class LocalStoreFinder implements StoreFinder
{
    public function search(array $filters): Collection
    {
        $q = Restaurant::query();

        if (!empty($filters['keyword'])) {
            $kw = $filters['keyword'];
            $q->where(fn($x)=>$x->where('name','like',"%{$kw}%")
                               ->orWhere('address','like',"%{$kw}%"));
        }
        if (!empty($filters['cuisine_id'])) {
            $cid = (int)$filters['cuisine_id'];
            $q->whereHas('cuisines', fn($x)=>$x->where('cuisines.id',$cid));
        }

        return $q->limit(100)->get()->map(fn($r)=>[
            'name'     => $r->name,
            'address'  => $r->address,
            'id'       => $r->id,           // ← Eloquent のID
            'place_id' => null,
            'provider' => 'local',
        ]);
    }
}
