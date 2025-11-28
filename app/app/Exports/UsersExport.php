<?php

namespace App\Exports;

use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromQuery, WithHeadings, WithMapping
{
    protected ?Carbon $from = null;
    protected ?Carbon $to   = null;
    protected bool $scopeAll = false;

    public function __construct(array $params = [])
    {
        $this->scopeAll = ($params['scope'] ?? '') === 'all';

        if (! $this->scopeAll && !empty($params['from']) && !empty($params['to'])) {
            try {
                $from = Carbon::createFromFormat('Y-m', $params['from'])->startOfMonth();
                $to   = Carbon::createFromFormat('Y-m', $params['to'])->startOfMonth()->addMonth();
                if ($from < $to) {
                    $this->from = $from;
                    $this->to   = $to;
                }
            } catch (\Throwable $e) {
                // 無効入力は全件扱いにフォールバック
                $this->scopeAll = true;
            }
        }
    }

    public function query()
    {
        $q = User::withTrashed()->orderBy('created_at', 'asc');

        if (!$this->scopeAll && $this->from && $this->to) {
            $q->where('created_at', '>=', $this->from)
              ->where('created_at', '<',  $this->to);
        }

        return $q;
    }

    public function headings(): array
    {
        return ['登録日', '名前', 'メールアドレス', '最終ログイン日'];
    }

    public function map($user): array
    {
        return [
            optional($user->created_at)->format('Y-m-d H:i'),
            $user->name,
            $user->email,
            $user->last_login_at ? Carbon::parse($user->last_login_at)->format('Y-m-d H:i') : '—',
        ];
    }
}
